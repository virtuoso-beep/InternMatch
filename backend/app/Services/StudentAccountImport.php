<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\ProgramTerm;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/** Student-only import. Never creates or modifies placement-side records. */
class StudentAccountImport
{
    public static function inspect(User $actor, array $input): array
    {
        $program = CoordinatorExcelImport::authorize($actor, (int) $input['program_id']);
        $term = AcademicTerm::findOrFail($input['academic_term_id']);
        $rows = [];
        $ids = [];
        $emails = [];
        foreach ($input['rows'] as $i => $raw) {
            $row = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $raw);
            $row['source'] ??= 'Row '.($i + 1);
            if ($row['skip'] ?? false) {
                $rows[] = ['row' => $row, 'errors' => [], 'warnings' => [], 'matched' => [], 'action' => 'skipped'];

                continue;
            }
            $parts = array_intersect_key($row, array_flip(['first_name', 'middle_name', 'last_name']));
            if (array_filter($parts, fn ($v) => filled($v))) {
                $row['name'] = implode(' ', array_filter([$row['first_name'] ?? '', $row['middle_name'] ?? '', $row['last_name'] ?? ''], fn ($v) => filled($v)));
            }
            $rules = ['student_id' => ['required', 'string', 'regex:/^[0-9]{6}$/'], 'name' => 'required|string|max:255',
                'first_name' => 'nullable|string|max:100', 'middle_name' => 'nullable|string|max:100', 'last_name' => 'nullable|string|max:100',
                'email' => 'nullable|email|max:255', 'year_level' => 'required|integer|between:1,10',
                'enrolled_on' => 'nullable|date_format:Y-m-d', 'student_address' => 'nullable|string|max:255'];
            $errors = Validator::make($row, $rules)->errors()->all();
            if ($parts && (empty($row['first_name']) || empty($row['last_name']))) {
                $errors[] = 'First Name and Last Name are required when using separate name columns.';
            }
            foreach ($row as $v) {
                if (is_string($v) && str_contains($v, '[Formula:')) {
                    $errors[] = 'Replace formula cells with confirmed values.';
                }
            }
            if (filled($row['program'] ?? null) && ! in_array(CoordinatorExcelImport::norm($row['program']), [CoordinatorExcelImport::norm($program->code), CoordinatorExcelImport::norm($program->name)], true)) {
                $errors[] = 'Program must match the selected assigned program.';
            }
            if (filled($row['academic_term'] ?? null) && ! in_array(CoordinatorExcelImport::norm($row['academic_term']), [CoordinatorExcelImport::norm($term->code), CoordinatorExcelImport::norm($term->name)], true)) {
                $errors[] = 'Academic Term must match the selected term. Import different terms separately.';
            }
            $matched = [];
            $warnings = [];
            $action = 'new_account';
            $student = Student::with(['user' => fn ($q) => $q->withTrashed()])->where('student_number', $row['student_id'] ?? '')->first();
            $enrollment = null;
            if ($student) {
                $matched['student_id'] = $student->id;
                $action = 'existing_account';
                $user = $student->user;
                if (! $user || $user->trashed() || $user->role !== Role::Student) {
                    $errors[] = 'Student account is unavailable. Administrator review is required.';
                }
                if ($actor->role !== Role::Admin && $student->enrollments()->whereHas('programTerm', fn ($q) => $q->whereNotIn('program_id', $actor->programs()->where('is_active', true)->select('programs.id')))->exists()) {
                    $errors[] = 'Student has records outside your assigned active programs. Administrator review is required.';
                }
                foreach (['name', 'email'] as $field) {
                    if ($user && filled($row[$field] ?? null) && CoordinatorExcelImport::norm($row[$field]) !== CoordinatorExcelImport::norm((string) $user->$field)) {
                        $errors[] = "Existing {$field} conflicts. Correct the source or request an identity review; import cannot overwrite identity.";
                    }
                }
                $enrollment = $student->enrollments()->whereHas('programTerm', fn ($q) => $q->where('program_id', $program->id)->where('academic_term_id', $term->id))->first();
                if ($enrollment) {
                    $matched['enrollment_id'] = $enrollment->id;
                    $action = 'duplicate';
                    if ($enrollment->status->value === 'enrolled' && ($enrollment->year_level === null || ($enrollment->enrolled_on === null && filled($row['enrolled_on'] ?? null)))) {
                        $action = 'update_enrollment';
                    }
                    if (($enrollment->year_level !== null && (int) ($row['year_level'] ?? 0) !== $enrollment->year_level) || (filled($row['enrolled_on'] ?? null) && $row['enrolled_on'] !== $enrollment->enrolled_on?->toDateString())) {
                        $warnings[] = 'Existing enrollment details and status are preserved. Use enrollment management for corrections.';
                    }
                }
            } elseif (filled($row['email'] ?? null)) {
                $user = User::withTrashed()->where('email', $row['email'])->first();
                if ($user) {
                    if ($user->trashed() || $user->role !== Role::Student || $user->student || CoordinatorExcelImport::norm($user->name) !== CoordinatorExcelImport::norm($row['name'] ?? '')) {
                        $errors[] = 'Email belongs to another or unavailable identity. Administrator review is required.';
                    } else {
                        $matched['user_id'] = $user->id;
                        $action = 'existing_account';
                    }
                }
            }
            // Spec §17A-B: every new account is activated by an emailed link, so an e-mail address is required.
            if (! $student && ! isset($matched['user_id']) && ! filled($row['email'] ?? null)) {
                $errors[] = 'E-mail is required for a new student account; the activation link is sent there. Add it and validate again.';
            }
            if (! $enrollment && ! $program->required_ojt_hours) {
                $errors[] = 'Confirm program OJT hours before creating enrollments.';
            }
            $id = $row['student_id'] ?? '';
            $identity = [CoordinatorExcelImport::norm($row['name'] ?? ''), CoordinatorExcelImport::norm($row['email'] ?? ''), (string) ($row['year_level'] ?? ''), (string) ($row['enrolled_on'] ?? '')];
            if (isset($ids[$id])) {
                if ($ids[$id]['identity'] !== $identity) {
                    $errors[] = 'Repeated Student ID has conflicting details.';
                    $rows[$ids[$id]['index']]['errors'][] = 'Repeated Student ID has conflicting details.';
                } else {
                    $action = 'duplicate';
                }
            } else {
                $ids[$id] = ['identity' => $identity, 'index' => $i];
            }
            if (filled($row['email'] ?? null)) {
                $email = strtolower($row['email']);
                if (isset($emails[$email]) && $emails[$email]['id'] !== $id) {
                    $errors[] = 'Email is shared by different Student IDs.';
                    $rows[$emails[$email]['index']]['errors'][] = 'Email is shared by different Student IDs.';
                } else {
                    $emails[$email] = ['id' => $id, 'index' => $i];
                }
            }
            $rows[] = ['row' => $row, 'errors' => $errors, 'warnings' => $warnings, 'matched' => $matched, 'action' => $action];
        }
        $counts = ['new_accounts' => 0, 'existing_accounts' => 0, 'enrollments_to_create' => 0, 'enrollments_to_update' => 0, 'duplicates' => 0, 'invalid' => 0];
        foreach ($rows as $r) {
            if ($r['errors']) {
                $counts['invalid']++;

                continue;
            }
            if ($r['action'] === 'skipped') {
                continue;
            }
            if ($r['action'] === 'duplicate') {
                $counts['duplicates']++;
                if (isset($r['matched']['student_id']) || isset($r['matched']['user_id'])) {
                    $counts['existing_accounts']++;
                }

                continue;
            }
            $counts[$r['action'] === 'new_account' ? 'new_accounts' : 'existing_accounts']++;
            if ($r['action'] === 'update_enrollment') {
                $counts['enrollments_to_update']++;
            }
            if (! isset($r['matched']['enrollment_id'])) {
                $counts['enrollments_to_create']++;
            }
        }

        return ['program_id' => $program->id, 'academic_term_id' => $term->id, 'rows' => $rows, 'counts' => $counts];
    }

    public static function save(User $actor, array $preview, array $entry, array &$credentials): string
    {
        $r = $entry['row'];
        $m = $entry['matched'];
        $program = CoordinatorExcelImport::authorize($actor, $preview['program_id']);
        $student = isset($m['student_id']) ? Student::findOrFail($m['student_id']) : null;
        $created = false;
        if (! $student) {
            $user = isset($m['user_id']) ? User::findOrFail($m['user_id']) : null;
            if (! $user) {
                // Spec §17A-B: no usable password. The account stays pending until the student opens the
                // one-time activation link, sets their own password and acknowledges the privacy notice.
                $user = new User(['name' => $r['name'], 'email' => strtolower($r['email']), 'password' => Str::random(64)]);
                $user->forceFill(['role' => Role::Student, 'status' => AccountStatus::Pending, 'must_change_password' => false])->save();
                $credentials[] = ['student_id' => $r['student_id'], 'activate_user_id' => $user->id];
            }
            $student = Student::create(['user_id' => $user->id, 'student_number' => $r['student_id']]);
            $user->profile()->firstOrCreate([], ['address' => $r['student_address'] ?? null]);
            $created = true;
        }
        $pt = ProgramTerm::firstOrCreate(['program_id' => $program->id, 'academic_term_id' => $preview['academic_term_id']], ['required_minutes' => $program->required_ojt_hours * 60]);
        $enrollment = StudentEnrollment::firstOrCreate(['student_id' => $student->id, 'program_term_id' => $pt->id], [
            'year_level' => $r['year_level'], 'required_minutes' => $program->required_ojt_hours * 60, 'status' => 'enrolled', 'enrolled_on' => filled($r['enrolled_on'] ?? null) ? $r['enrolled_on'] : now()->toDateString()]);
        $updated = false;
        if ($enrollment->wasRecentlyCreated) {
            ProgramRequirementTemplates::apply($pt);
        } elseif ($enrollment->status->value === 'enrolled') {
            // Only fill missing enrollment facts; historical values and statuses are immutable here.
            if ($enrollment->year_level === null) {
                $enrollment->year_level = $r['year_level'];
            }
            if ($enrollment->enrolled_on === null && filled($r['enrolled_on'] ?? null)) {
                $enrollment->enrolled_on = $r['enrolled_on'];
            }
            $updated = $enrollment->isDirty();
            if ($updated) {
                $enrollment->save();
            }
        }

        return $created ? 'successful' : ($enrollment->wasRecentlyCreated || $updated ? 'updated' : 'skipped');
    }
}
