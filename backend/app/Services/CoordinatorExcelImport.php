<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\{AcademicTerm, HostEstablishment, Moa, Opportunity, Placement, Program, ProgramTerm, Student, StudentEnrollment, User};
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CoordinatorExcelImport
{
    public static function authorize(User $actor, int $programId): Program
    {
        abort_unless(in_array($actor->role, [Role::Coordinator, Role::Admin], true) && $actor->status === AccountStatus::Active, 403);
        if ($actor->role === Role::Admin) return Program::where('is_active', true)->findOrFail($programId);
        return $actor->programs()->where('programs.id', $programId)->where('is_active', true)
            ->firstOrFail();
    }

    public static function inspect(User $actor, array $input): array
    {
        if ($actor->role === Role::Admin) abort_unless(collect($input['rows'])->every(fn ($row) => ($row['kind'] ?? '') === 'student'), 403, 'Administrator import is limited to student accounts and enrollment records.');
        if (collect($input['rows'])->every(fn ($r) => ($r['kind'] ?? '') === 'student')) return StudentAccountImport::inspect($actor, $input);
        $program = self::authorize($actor, (int) $input['program_id']);
        $term = isset($input['academic_term_id']) ? AcademicTerm::findOrFail($input['academic_term_id']) : null;
        abort_if(! $term && collect($input['rows'])->contains(fn ($row) => ($row['kind'] ?? '') !== 'agreement'), 422, 'Academic term is required.');
        $results = []; $seen = []; $identities = []; $emails = []; $placements = []; $entities = [];
        foreach ($input['rows'] as $index => $raw) {
            $row = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $raw);
            $row['source'] = $row['source'] ?? 'Row '.($index + 1);
            $errors = []; $warnings = []; $matched = [];
            if (($row['skip'] ?? false) === true) {
                $results[] = ['row' => $row, 'errors' => [], 'warnings' => [], 'action' => 'skipped', 'matched' => []];
                continue;
            }
            $validator = Validator::make($row, [
                'kind' => 'required|in:student,host,opportunity,agreement', 'student_id' => 'nullable|string|max:50', 'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255', 'program' => 'nullable|string|max:255', 'student_address' => 'nullable|string|max:255',
                'host_name' => 'nullable|string|max:255', 'host_address' => 'nullable|string|max:255', 'city' => 'nullable|string|max:255',
                'host_code' => 'nullable|string|max:50', 'host_id' => 'nullable|integer|min:1',
                'latitude' => 'nullable|required_with:longitude|numeric|between:-90,90', 'longitude' => 'nullable|required_with:latitude|numeric|between:-180,180',
                'year_level' => 'nullable|integer|between:1,10', 'enrolled_on' => 'nullable|date_format:Y-m-d',
                'starts_on' => 'nullable|date_format:Y-m-d', 'ends_on' => 'nullable|date_format:Y-m-d|after_or_equal:starts_on',
                'opportunity_id' => 'nullable|integer|min:1', 'keep_existing' => 'sometimes|boolean', 'fill_missing' => 'sometimes|boolean',
                'opportunity_title' => 'nullable|string|max:255', 'tasks' => 'nullable|string|max:10000', 'capacity' => 'nullable|integer|between:1,100000',
                'placement_status' => 'nullable|string|max:100', 'source' => 'nullable|string|max:255', 'skip' => 'sometimes|boolean',
                'host_capacity' => 'nullable|integer|between:0,100000', 'reference_number' => 'nullable|string|max:80',
                'effective_on' => 'nullable|date_format:Y-m-d', 'expires_on' => 'nullable|date_format:Y-m-d|after_or_equal:effective_on',
                'notes' => 'nullable|string|max:10000', 'source_status' => 'nullable|string|max:100',
            ]);
            if ($validator->fails()) $errors = $validator->errors()->all();
            foreach ($row as $value) if (is_string($value) && str_contains($value, '[Formula:')) $errors[] = 'Replace formula cells with confirmed values.';
            if ($errors) {
                $results[] = ['row' => $row, 'errors' => $errors, 'warnings' => [], 'action' => 'review', 'matched' => []];
                continue;
            }
            if (! empty($row['reference_warning'])) $warnings[] = $row['reference_warning'];
            if (! empty($row['program'])) {
                $aliases = ['BSA' => ['bsa', 'bs accountancy', 'bachelor of science in accountancy', 'bs accounting education'],
                    'BSIT' => ['bsit', 'bs information technology', 'bachelor of science in information technology'],
                    'BSCS' => ['bscs', 'bs computer science', 'bachelor of science in computer science'],
                    'BSCRIM' => ['bscrim', 'bs criminology', 'bs criminology education', 'bachelor of science in criminology']];
                if (! in_array(strtolower($row['program']), ($aliases[$program->code] ?? [strtolower($program->code), strtolower($program->name)]), true)) $errors[] = 'The row program does not match your selected assigned program.';
            }
            $student = null; $enrollment = null;
            if (($row['kind'] ?? '') === 'student') {
                if (empty($row['student_id'])) $errors[] = 'Student ID is required. Names are never used as student identifiers.';
                else $student = Student::where('student_number', $row['student_id'])->with('user')->first();
                if ($student) {
                    if (! $student->user || $student->user->role !== Role::Student) $errors[] = 'Student account is unavailable; ask the administrator to review it.';
                    if ($student->enrollments()->whereHas('programTerm', fn ($q) => $q->whereNotIn('program_id', $actor->programs()->select('programs.id')))->exists()) {
                        $errors[] = 'Student ID has records outside your assigned programs. Administrator review is required.';
                    } else {
                        $matched['student_id'] = $student->id;
                        $enrollment = $student->enrollments()->whereHas('programTerm', fn ($q) => $q->where('program_id', $program->id)->where('academic_term_id', $term->id))->first();
                        if ($enrollment) $matched['enrollment_id'] = $enrollment->id;
                        if ($enrollment && ((! empty($row['year_level']) && (int) $row['year_level'] !== (int) $enrollment->year_level)
                            || (! empty($row['enrolled_on']) && $row['enrolled_on'] !== $enrollment->enrolled_on?->toDateString()))) {
                            $warnings[] = 'Existing enrollment details differ and will be preserved.';
                            if (! ($row['keep_existing'] ?? false)) $errors[] = 'Keep existing enrollment details or correct year level and enrollment date.';
                        }
                        if ($enrollment && $enrollment->status->value !== 'enrolled') $warnings[] = 'Existing historical enrollment status is preserved.';
                        foreach (['name', 'email'] as $field) {
                            if (! empty($row[$field]) && $student->user && self::norm($row[$field]) !== self::norm($student->user->$field)) {
                                $warnings[] = "Existing {$field} differs. Existing identity will not be overwritten.";
                                if (! ($row['keep_existing'] ?? false)) $errors[] = "Confirm Keep existing identity or correct the {$field}.";
                            }
                        }
                        if (! empty($row['student_address']) && filled($student->user?->profile?->address) && self::norm($row['student_address']) !== self::norm($student->user->profile->address)) {
                            $warnings[] = 'Existing student address differs and will be preserved.';
                            if (! ($row['keep_existing'] ?? false)) $errors[] = 'Confirm Keep existing details or correct the student address.';
                        }
                    }
                } else {
                    if (empty($row['name']) || empty($row['email'])) $errors[] = 'New students require a real full name and email; enter missing information or skip this row.';
                    if (! empty($row['email'])) {
                        $user = User::withTrashed()->where('email', $row['email'])->first();
                        if ($user) {
                            if ($user->trashed() || $user->role !== Role::Student || $user->student || self::norm($user->name) !== self::norm($row['name'] ?? '')) $errors[] = 'Email belongs to another identity or unavailable account; administrator review is required.';
                            else $matched['user_id'] = $user->id;
                        } else $warnings[] = 'New account will be pending activation. No email or default password will be invented.';
                    }
                }
                if (! $enrollment) {
                    if (! $program->required_ojt_hours) $errors[] = 'Confirm this program’s required hours before creating enrollments.';
                    if (empty($row['enrolled_on'])) $errors[] = 'Enrollment date is required for a new enrollment.';
                    if (empty($row['year_level'])) $errors[] = 'Year level is required for a new enrollment.';
                }
                if (! empty($row['student_id'])) {
                    $identity = [self::norm($row['name'] ?? ''), self::norm($row['email'] ?? '')];
                    if (isset($identities[$row['student_id']]) && $identities[$row['student_id']]['identity'] !== $identity) {
                        $errors[] = 'Repeated Student ID has conflicting names or emails. Resolve both rows before importing.';
                        $results[$identities[$row['student_id']]['index']]['errors'][] = 'Repeated Student ID has conflicting names or emails. Resolve both rows before importing.';
                    } else $identities[$row['student_id']] = ['identity' => $identity, 'index' => $index];
                }
                if (! empty($row['email']) && ! empty($row['student_id'])) {
                    $emailKey = self::norm($row['email']);
                    if (isset($emails[$emailKey]) && $emails[$emailKey]['id'] !== $row['student_id']) {
                        $errors[] = 'This email is used by different Student IDs in the workbook. Supply separate verified emails.';
                        $results[$emails[$emailKey]['index']]['errors'][] = 'This email is used by different Student IDs in the workbook. Supply separate verified emails.';
                    } else $emails[$emailKey] = ['id' => $row['student_id'], 'index' => $index];
                }
            }
            $host = null;
            if (! empty($row['host_id'])) {
                $host = HostAccess::query($actor)->find($row['host_id']);
                if (! $host) $errors[] = 'Selected host is outside your scope or no longer exists.';
            } elseif (! empty($row['host_name'])) {
                $hosts = HostEstablishment::all()->filter(fn ($record) => self::norm($record->name) === self::norm($row['host_name']))->values();
                if ($hosts->count() === 1) {
                    $host = $hosts->first();
                    if (! HostAccess::query($actor)->whereKey($host->id)->exists()) $errors[] = 'An existing host with this name needs administrator program assignment before reuse.';
                } elseif ($hosts->count() > 1) $errors[] = 'Multiple hosts share this name. Select the correct existing host.';
                if (! $host && $hosts->isEmpty() && (empty($row['host_address']) || empty($row['city']))) $errors[] = 'New hosts require an address and city/location.';
            } elseif (in_array($row['kind'], ['host', 'opportunity', 'agreement'])) $errors[] = 'Host name or an existing host selection is required.';
            if ($host) {
                $matched['host_id'] = $host->id;
                foreach (['host_name' => 'name', 'host_address' => 'address', 'city' => 'city', 'latitude' => 'latitude', 'longitude' => 'longitude'] as $inputField => $attribute) {
                    if (isset($row[$inputField]) && $row[$inputField] !== '' && $host->$attribute !== null && self::norm((string) $row[$inputField]) !== self::norm((string) $host->$attribute)) {
                        if (in_array($attribute, ['latitude', 'longitude']) && (float) $row[$inputField] === (float) $host->$attribute) continue;
                        $warnings[] = "Host {$attribute} differs; existing data is preserved.";
                        if (! ($row['keep_existing'] ?? false)) $errors[] = 'Confirm Keep existing identity/details or correct the host information.';
                    }
                }
            }
            if ((! empty($row['host_name']) || $host) && empty($row['latitude']) && ! $host?->latitude) $warnings[] = 'No coordinates supplied: listed on the map without a pin.';
            $hostCapacity = $host && $term ? DB::table('host_program_capacity')->where('host_establishment_id', $host->id)->where('program_id', $program->id)->where('academic_term_id', $term->id)->value('capacity') : null;
            if (isset($row['host_capacity']) && $row['host_capacity'] !== '' && $hostCapacity !== null && (int) $row['host_capacity'] !== (int) $hostCapacity) {
                $warnings[] = 'Existing host capacity differs and will be preserved.';
                if (! ($row['keep_existing'] ?? false)) $errors[] = 'Keep existing host capacity or correct the supplied capacity.';
            }
            $effectiveCapacity = $hostCapacity ?? (filled($row['host_capacity'] ?? null) ? (int) $row['host_capacity'] : null);
            if ($row['kind'] === 'opportunity') {
                if (empty($row['opportunity_title']) || empty($row['tasks']) || empty($row['capacity'])) $errors[] = 'Opportunity title, duties and available slots are required.';
                if ($effectiveCapacity === null || (int) ($row['capacity'] ?? 0) > $effectiveCapacity) $errors[] = 'Provide confirmed host capacity for this program and term, at least as large as the opportunity slots.';
                if ($host && ! empty($row['opportunity_title'])) {
                    $matches = Opportunity::where('host_establishment_id', $host->id)->where('academic_term_id', $term->id)->where('title', $row['opportunity_title'])->get();
                    if ($matches->count() > 1) $errors[] = 'Multiple existing opportunities match; resolve the duplicate records before importing.';
                    elseif ($matches->count() === 1) {
                        $existing = $matches->first();
                        if (! $existing->programs()->whereKey($program->id)->exists()) $errors[] = 'Existing opportunity does not cover your selected program.';
                        else {
                            $matched['opportunity_id'] = $existing->id;
                            $capacity = $existing->programs()->whereKey($program->id)->first()->pivot->capacity;
                            if (($existing->tasks !== ($row['tasks'] ?? '') || (int) $capacity !== (int) ($row['capacity'] ?? 0)
                                || ($existing->starts_on?->toDateString() ?? '') !== ($row['starts_on'] ?? '') || ($existing->ends_on?->toDateString() ?? '') !== ($row['ends_on'] ?? '')) && ! ($row['keep_existing'] ?? false)) $errors[] = 'Existing opportunity details differ. Keep the existing record or edit it through the opportunity form.';
                        }
                    }
                }
                $warnings[] = 'New opportunities are saved as drafts. Review and publish using Post opportunity / Edit opportunity.';
            }
            if ($row['kind'] === 'agreement') {
                if (empty($row['reference_number'])) $errors[] = 'The actual agreement reference number is required.';
                else {
                    $existing = Moa::where('reference_number', $row['reference_number'])->first();
                    if ($existing) {
                        if (! $host || $existing->host_establishment_id !== $host->id || $existing->is_institution_wide || ! $existing->programs()->whereKey($program->id)->exists()) $errors[] = 'Agreement reference belongs to a different host or program scope; administrator review is required.';
                        else {
                            $matched['moa_id'] = $existing->id;
                            if ((($existing->effective_on?->toDateString() ?? '') !== ($row['effective_on'] ?? '') || ($existing->expires_on?->toDateString() ?? '') !== ($row['expires_on'] ?? '')) && ! ($row['keep_existing'] ?? false)) $errors[] = 'Existing agreement dates differ. Keep existing dates or register a new agreement reference.';
                        }
                    }
                }
                $warnings[] = 'New agreements are drafts. Attach the executed document and verify dates in Register agreement before activating.';
            }
            $hasPlacement = ($row['kind'] ?? '') === 'student' && (! empty($row['host_name']) || $host || ! empty($row['opportunity_id']));
            if ($hasPlacement) {
                if (empty($row['opportunity_id'])) {
                    if ($effectiveCapacity === null || (int) ($row['capacity'] ?? 0) > $effectiveCapacity) $errors[] = 'Confirmed host capacity for this program and term is required before creating opportunity slots.';
                    if (empty($row['opportunity_title']) || empty($row['tasks']) || empty($row['capacity'])) $errors[] = 'Select an existing opportunity, or provide its title, duties and capacity to create a draft. Source opportunity codes are not database IDs.';
                    elseif ($host) {
                        $matches = Opportunity::where('host_establishment_id', $host->id)->where('academic_term_id', $term->id)->where('title', $row['opportunity_title'])->get();
                        if ($matches->count() > 1) $errors[] = 'Multiple opportunities match this title; select the existing opportunity explicitly.';
                        elseif ($matches->count() === 1) {
                            $opportunity = $matches->first();
                            if (! $opportunity->programs()->whereKey($program->id)->exists()) $errors[] = 'Matching opportunity does not cover this program.';
                            elseif ($opportunity->tasks !== $row['tasks'] || $opportunity->capacity !== (int) $row['capacity']) $errors[] = 'Matching opportunity differs in duties or capacity. Select the existing opportunity explicitly or correct the row.';
                            else $matched['opportunity_id'] = $opportunity->id;
                        }
                    }
                    if (! isset($matched['opportunity_id'])) $warnings[] = 'A draft opportunity will be created. Placement remains pending; publication and approval use the existing workflows.';
                }
                else {
                    $opportunity = Opportunity::where('academic_term_id', $term->id)->whereHas('programs', fn ($q) => $q->where('programs.id', $program->id))->find($row['opportunity_id']);
                    if (! $opportunity || ! $host || $opportunity->host_establishment_id !== $host->id) $errors[] = 'Opportunity must match the selected program, term, and host.';
                    else $matched['opportunity_id'] = $opportunity->id;
                }
                if (empty($row['starts_on'])) $errors[] = 'Placement start date is required.';
                if (! empty($row['placement_status']) && ! in_array(strtolower($row['placement_status']), ['pending', 'proposed'], true)) $errors[] = 'Only pending placements can be imported. Historical/approved status requires review; set Pending to submit for approval.';
                if ($enrollment && ! isset($matched['opportunity_id']) && $enrollment->currentPlacement()->exists()) $errors[] = 'This enrollment already has a current placement. Resolve it through the placement workflow first.';
                if ($enrollment && isset($matched['opportunity_id'])) {
                    $existing = $enrollment->placements()->where('opportunity_id', $matched['opportunity_id'])->whereDate('starts_on', $row['starts_on'] ?? '1000-01-01')->first();
                    if ($existing) {
                        $matched['placement_id'] = $existing->id;
                        if (($row['ends_on'] ?? '') !== ($existing->ends_on?->format('Y-m-d') ?? '') && ! ($row['keep_existing'] ?? false)) $errors[] = 'Existing placement end date differs. Keep existing details or correct the row.';
                    } elseif ($enrollment->currentPlacement()->exists()) $errors[] = 'This enrollment already has a current placement. Resolve it through the placement workflow first.';
                }
                $placementKey = ($matched['host_id'] ?? self::norm($row['host_name'] ?? '')).'|'.($matched['opportunity_id'] ?? self::norm($row['opportunity_title'] ?? '')).'|'.($row['starts_on'] ?? '').'|'.($row['ends_on'] ?? '');
                $studentKey = $row['student_id'] ?? '';
                if (isset($placements[$studentKey]) && $placements[$studentKey]['key'] !== $placementKey) {
                    $errors[] = 'This Student ID has different placement records in the selected term. Only one pending placement is permitted; review and skip the other record or import it under its actual term.';
                    $results[$placements[$studentKey]['index']]['errors'][] = 'This Student ID has conflicting placement records in the selected term.';
                } else $placements[$studentKey] = ['key' => $placementKey, 'index' => $index];
            }
            // Compare shared entities across the whole preview, not only after an earlier row is saved.
            $hostKey = (string) ($matched['host_id'] ?? self::norm($row['host_name'] ?? ''));
            $checks = [];
            if ($hostKey !== '') $checks['host:'.$hostKey] = ['host_address', 'city', 'latitude', 'longitude', 'host_capacity'];
            if (! empty($row['opportunity_title'])) $checks['opportunity:'.$hostKey.':'.self::norm($row['opportunity_title'])] = ['tasks', 'capacity'];
            if (! empty($row['reference_number'])) $checks['agreement:'.self::norm($row['reference_number'])] = ['host_name', 'host_id', 'effective_on', 'expires_on'];
            foreach ($checks as $entityKey => $fields) foreach ($fields as $field) {
                if (! filled($row[$field] ?? null)) continue;
                $value = self::norm((string) $row[$field]);
                if (isset($entities[$entityKey][$field]) && $entities[$entityKey][$field]['value'] !== $value) {
                    $message = "Repeated record has conflicting {$field} values in this workbook. Correct or skip the conflicting row.";
                    $errors[] = $message;
                    $results[$entities[$entityKey][$field]['index']]['errors'][] = $message;
                } else $entities[$entityKey][$field] = ['value' => $value, 'index' => $index];
            }
            $key = ($row['student_id'] ?? '').'|'.$hostKey.'|'.($row['opportunity_id'] ?? $row['opportunity_title'] ?? $row['reference_number'] ?? '').'|'.($row['starts_on'] ?? '');
            if (isset($seen[$key])) $warnings[] = 'Repeated student/host/placement key in this workbook; exact repeats will be skipped.';
            $seen[$key] = true;
            $results[] = ['row' => $row, 'errors' => array_values(array_unique($errors)), 'warnings' => array_values(array_unique($warnings)),
                'matched' => $matched, 'action' => $student || $host ? 'match_existing' : 'create'];
        }
        return ['program_id' => $program->id, 'academic_term_id' => $term?->id, 'rows' => $results];
    }

    public static function saveRow(User $actor, array $preview, array $entry, array &$credentials = []): string
    {
        if ($entry['row']['kind'] === 'student') return StudentAccountImport::save($actor, $preview, $entry, $credentials);
        $r = $entry['row']; $m = $entry['matched'];
        $program = Program::findOrFail($preview['program_id']);
        $changed = false; $created = false;
        $host = isset($m['host_id']) ? HostEstablishment::findOrFail($m['host_id']) : null;
        if (! $host && ! empty($r['host_name'])) {
            $host = HostEstablishment::create(['code' => 'IMP-'.Str::uuid(), 'name' => $r['host_name'], 'address' => $r['host_address'], 'city' => $r['city'],
                'latitude' => filled($r['latitude'] ?? null) ? $r['latitude'] : null, 'longitude' => filled($r['longitude'] ?? null) ? $r['longitude'] : null, 'is_active' => true]);
            $created = true;
        }
        if ($host) {
            $changed = DB::table('import_host_program')->insertOrIgnore(['host_establishment_id' => $host->id, 'program_id' => $program->id]) > 0;
            if (($r['fill_missing'] ?? false) && $host->latitude === null && $host->longitude === null && filled($r['latitude'] ?? null) && filled($r['longitude'] ?? null)) {
                $host->update(['latitude' => $r['latitude'], 'longitude' => $r['longitude']]); $changed = true;
            }
            if ($preview['academic_term_id'] && filled($r['host_capacity'] ?? null)) {
                $changed = DB::table('host_program_capacity')->insertOrIgnore(['host_establishment_id' => $host->id, 'program_id' => $program->id, 'academic_term_id' => $preview['academic_term_id'], 'capacity' => (int) $r['host_capacity'], 'created_at' => now(), 'updated_at' => now()]) > 0 || $changed;
            }
        }
        if ($r['kind'] === 'agreement' && ! isset($m['moa_id'])) {
            $moa = Moa::create(['host_establishment_id' => $host->id, 'reference_number' => $r['reference_number'], 'status' => 'draft',
                'effective_on' => filled($r['effective_on'] ?? null) ? $r['effective_on'] : null, 'expires_on' => filled($r['expires_on'] ?? null) ? $r['expires_on'] : null,
                'is_institution_wide' => false, 'notes' => trim(($r['notes'] ?? '').(! empty($r['source_status']) ? '\nWorkbook reported status: '.$r['source_status'] : ''))]);
            $moa->programs()->attach($program->id); $created = true;
        }
        if ($r['kind'] === 'opportunity' && ! isset($m['opportunity_id'])) {
            self::createOpportunity($actor, $program, $host, $preview, $r); $created = true;
        }
        if ($r['kind'] === 'student') {
            $student = isset($m['student_id']) ? Student::findOrFail($m['student_id']) : null;
            if (! $student) {
                $user = isset($m['user_id']) ? User::findOrFail($m['user_id']) : new User(['name' => $r['name'], 'email' => $r['email'], 'password' => Str::random(64)]);
                if (! $user->exists) { $user->forceFill(['role' => Role::Student, 'status' => AccountStatus::Pending])->save(); }
                $student = Student::create(['user_id' => $user->id, 'student_number' => $r['student_id']]); $created = true;
                if (! empty($r['student_address'])) $user->profile()->firstOrCreate([], ['address' => $r['student_address']]);
            } elseif (($r['fill_missing'] ?? false) && ! empty($r['student_address'])) {
                $profile = $student->user->profile()->firstOrCreate();
                if (! filled($profile->address)) { $profile->update(['address' => $r['student_address']]); $changed = true; }
            }
            $programTerm = ProgramTerm::firstOrCreate(['program_id' => $program->id, 'academic_term_id' => $preview['academic_term_id']], ['required_minutes' => $program->required_ojt_hours * 60]);
            $enrollment = StudentEnrollment::firstOrCreate(['student_id' => $student->id, 'program_term_id' => $programTerm->id], [
                'year_level' => $r['year_level'] ?? null, 'required_minutes' => $program->required_ojt_hours * 60, 'status' => 'enrolled', 'enrolled_on' => $r['enrolled_on'] ?? null]);
            if ($enrollment->wasRecentlyCreated) { ProgramRequirementTemplates::apply($programTerm); $changed = true; }
            if (! isset($m['opportunity_id']) && $host && ! empty($r['opportunity_title'])) {
                $opportunity = self::createOpportunity($actor, $program, $host, $preview, $r);
                $m['opportunity_id'] = $opportunity->id; $changed = true;
            }
            if (isset($m['opportunity_id']) && ! isset($m['placement_id'])) {
                $proposal = DB::table('placement_proposals')->where('program_term_id', $programTerm->id)->where('constraints_snapshot->import_id', $preview['import_id'])->value('id');
                $proposal ??= DB::table('placement_proposals')->insertGetId(['program_term_id' => $programTerm->id, 'created_by' => $actor->id, 'status' => 'under_review',
                    'constraints_snapshot' => json_encode(['import_id' => $preview['import_id'], 'source' => 'Coordinator Excel import']), 'created_at' => now(), 'updated_at' => now()]);
                $itemId = DB::table('placement_proposal_items')->insertGetId(['placement_proposal_id' => $proposal, 'program_term_id' => $programTerm->id,
                    'student_enrollment_id' => $enrollment->id, 'opportunity_id' => $m['opportunity_id'], 'reason' => 'Imported placement requires coordinator review and normal eligibility checks.', 'created_at' => now(), 'updated_at' => now()]);
                Placement::create(['student_enrollment_id' => $enrollment->id, 'host_establishment_id' => $host->id, 'opportunity_id' => $m['opportunity_id'],
                    'status' => 'pending', 'starts_on' => $r['starts_on'], 'ends_on' => filled($r['ends_on'] ?? null) ? $r['ends_on'] : null, 'placement_proposal_item_id' => $itemId]);
                $changed = true;
            }
        }
        return $created ? 'successful' : ($changed ? 'updated' : 'skipped');
    }

    public static function norm(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }

    private static function createOpportunity(User $actor, Program $program, HostEstablishment $host, array $preview, array $row): Opportunity
    {
        return OpportunityManagement::save($actor, ['host_establishment_id' => $host->id, 'academic_term_id' => $preview['academic_term_id'],
            'title' => $row['opportunity_title'], 'description' => $row['tasks'], 'tasks' => $row['tasks'], 'status' => 'draft',
            'starts_on' => filled($row['starts_on'] ?? null) ? $row['starts_on'] : null, 'ends_on' => filled($row['ends_on'] ?? null) ? $row['ends_on'] : null,
            'programs' => [['program_id' => $program->id, 'capacity' => (int) $row['capacity']]], 'competency_ids' => []]);
    }
}
