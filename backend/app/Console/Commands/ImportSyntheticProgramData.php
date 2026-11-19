<?php

namespace App\Console\Commands;

use App\Enums\AccountStatus;
use App\Models\Evaluation;
use App\Models\Placement;
use App\Models\User;
use App\Notifications\PortalNotification;
use App\Services\AccountActivation;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Imports one program's synthetic data set (program workbook + the all-programs workbook) exactly as given.
 * Every imported row is flagged is_synthetic = 1 and keeps its source ID in source_id; the untouched source row
 * is copied to synthetic_source_rows. Everything is validated first: any value that does not fit stops the
 * import before a single row is written. All rows are written in one transaction with raw inserts (no model
 * events, no notifications, no e-mail). Only the --activate student receives a real activation link afterwards.
 * Only supervisor and student accounts are created (synthetic). Admin, dean and coordinator rows map to the existing
 * real accounts given by --admin/--dean/--coordinator; the import never creates or changes those accounts or their
 * programs. Values the sheets do not carry are filled only by the documented rules in write().
 */
class ImportSyntheticProgramData extends Command
{
    protected $signature = 'internmatch:import-synthetic
        {program_workbook : Program workbook (e.g. BSA_Synthetic_Data.xlsx)}
        {all_workbook : All_Programs_Data.xlsx}
        {--program=BSA : Program code}
        {--term-name= : Academic term name}
        {--term-start= : Academic term start date (Y-m-d)}
        {--term-end= : Academic term end date (Y-m-d)}
        {--activate= : Student number imported as pending; receives the activation link}
        {--admin= : E-mail of the existing real admin account (admin rows map to it; never created)}
        {--dean= : E-mail of the existing real dean account, already assigned to the program}
        {--coordinator= : E-mail of the existing real coordinator of the program}';

    protected $description = 'Import a synthetic program data set (validated, flagged is_synthetic, one transaction)';

    private const TZ = 'Asia/Manila';

    private array $errors = [];

    private CarbonImmutable $now;

    public function handle(): int
    {
        $this->now = CarbonImmutable::now();
        $this->errors = []; // command instances are reused between calls in one process
        foreach (['term-name', 'term-start', 'term-end', 'admin', 'dean', 'coordinator'] as $option) {
            if (! $this->option($option)) {
                $this->error("--{$option} is required.");

                return self::INVALID;
            }
        }
        if (DB::table('synthetic_source_rows')->exists()) {
            $this->error('Synthetic data is already imported. Run internmatch:purge-synthetic --force first.');

            return self::FAILURE;
        }
        $code = (string) $this->option('program');
        $program = DB::table('programs')->where('code', $code)->first();
        if (! $program) {
            $this->error("Program {$code} not found.");

            return self::FAILURE;
        }

        $P = $this->load((string) $this->argument('program_workbook'));
        $A = $this->load((string) $this->argument('all_workbook'));
        $plan = $this->validate($P, $A, $program);
        if ($this->errors) {
            $this->error('Import stopped; nothing was written. These values do not fit:');
            foreach (array_slice(array_unique($this->errors), 0, 60) as $error) {
                $this->line(' - '.$error);
            }

            return self::FAILURE;
        }

        DB::transaction(fn () => $this->write($P, $plan, $program));

        if ($plan['activate']) {
            $student = DB::table('students')->where('student_number', $plan['activate'])->first();
            $admin = User::findOrFail($plan['accounts']['Admin']);
            AccountActivation::issue($admin, User::findOrFail($student->user_id));
            $this->info("Activation link issued for student {$plan['activate']} (mail driver: ".config('mail.default').').');
        }
        $this->info('Import complete.');

        return self::SUCCESS;
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function load(string $path): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);
        $sheets = [];
        foreach ($book->getWorksheetIterator() as $sheet) {
            $rows = array_values(array_filter($sheet->toArray(null, true, false, false), fn ($r) => count(array_filter($r, fn ($v) => $v !== null && $v !== '')) > 0));
            $head = array_shift($rows) ?? [];
            $sheets[$sheet->getTitle()] = array_map(fn ($r) => array_combine($head, array_slice($r, 0, count($head))), $rows);
        }
        $book->disconnectWorksheets();

        return $sheets;
    }

    private function reject(string $message): void
    {
        $this->errors[] = $message;
    }

    /** Maps a sheet value through an allowed-values table; unknown values are reported, never guessed. */
    private function pick(array $allowed, mixed $value, string $what): mixed
    {
        if (! array_key_exists((string) $value, $allowed)) {
            $this->reject("{$what}: unexpected value '".$value."'");

            return null;
        }

        return $allowed[(string) $value];
    }

    private function local(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value, self::TZ)->utc();
    }

    private function validate(array $P, array $A, object $program): array
    {
        foreach (['students', 'htes', 'opportunities', 'competency_vocabulary', 'coordinator_labels', 'placements', 'daily_attendance', 'weekly_logs', 'monitoring', 'evaluations'] as $s) {
            if (! isset($P[$s])) {
                $this->reject("program workbook: missing sheet {$s}");
            }
        }
        foreach (['users', 'role_permissions', 'notifications', 'chat_messages', 'audit_logs'] as $s) {
            if (! isset($A[$s])) {
                $this->reject("all-programs workbook: missing sheet {$s}");
            }
        }
        if ($this->errors) {
            return [];
        }
        foreach ($P as $sheet => $rows) {
            foreach ($rows as $i => $row) {
                if (array_key_exists('is_synthetic', $row) && (int) $row['is_synthetic'] !== 1) {
                    $this->reject("{$sheet} row ".($i + 2).': is_synthetic is not 1');
                }
                if (array_key_exists('program', $row) && $row['program'] !== $program->code) {
                    $this->reject("{$sheet} row ".($i + 2).": program '{$row['program']}' is not {$program->code}");
                }
            }
        }
        if ((int) $program->required_ojt_hours !== 400) {
            $this->reject("{$program->code} required_ojt_hours is {$program->required_ojt_hours}, expected 400");
        }

        $supervisors = array_column($P['htes'], 'supervisor_user_id');
        $users = array_values(array_filter($A['users'], fn ($u) => in_array($u['role'], ['Admin', 'Dean'], true) || $u['program_assignment'] === $program->code || in_array($u['user_id'], $supervisors, true)));
        $userIds = array_column($users, 'user_id');
        $studentByUser = array_column($P['students'], null, 'user_id');
        // Admin, dean and coordinator accounts are fixed real accounts: their sheet rows map to them, never created.
        $accounts = [];
        foreach (['Admin' => 'admin', 'Dean' => 'dean', 'Coordinator' => 'coordinator'] as $role => $option) {
            $account = User::where('email', (string) $this->option($option))->first();
            if (! $account || $account->is_synthetic || $account->role->value !== strtolower($role) || $account->status !== AccountStatus::Active || $account->trashed()) {
                $this->reject("--{$option}: no active, real {$role} account uses ".$this->option($option));

                continue;
            }
            if ($role !== 'Admin' && ! $account->programs()->whereKey($program->id)->exists()) {
                $this->reject("--{$option}: {$account->email} is not assigned to {$program->code} (assign it first; the import never changes programs)");
            }
            $accounts[$role] = $account->id;
        }
        $staff = [];
        foreach ($users as $u) {
            if (in_array($u['role'], ['Admin', 'Dean', 'Coordinator'], true) && isset($accounts[$u['role']])) {
                $staff[$u['user_id']] = $accounts[$u['role']];
            }
        }
        $created = array_values(array_filter($users, fn ($u) => ! in_array($u['role'], ['Admin', 'Dean', 'Coordinator'], true)));
        foreach ($users as $u) {
            $this->pick(['Admin' => 1, 'Dean' => 1, 'Coordinator' => 1, 'Supervisor' => 1, 'Student' => 1], $u['role'], "users {$u['user_id']} role");
            $this->pick(['Active' => 1], $u['account_status'], "users {$u['user_id']} account_status");
            if ($u['role'] === 'Student' && ! isset($studentByUser[$u['user_id']])) {
                $this->reject("users {$u['user_id']}: student account has no row in the students sheet");
            }
        }
        foreach ($P['students'] as $s) {
            if (! in_array($s['user_id'], $userIds, true)) {
                $this->reject("students {$s['student_id']}: user {$s['user_id']} not in the selected users");
            }
            $this->pick(['Active' => 1], $s['account_status'], "students {$s['student_id']} account_status");
        }
        $emails = array_map(fn ($u) => $u['role'] === 'Student' ? $studentByUser[$u['user_id']]['email'] ?? $u['email'] : $u['email'], $created);
        if (count($emails) !== count(array_unique($emails))) {
            $this->reject('selected users: duplicate e-mail addresses');
        }
        foreach (DB::table('users')->whereIn('email', $emails)->pluck('email') as $taken) {
            $this->reject("users: e-mail {$taken} already belongs to an existing account");
        }
        foreach (DB::table('students')->whereIn('student_number', array_map('strval', array_column($P['students'], 'student_id')))->pluck('student_number') as $taken) {
            $this->reject("students: student number {$taken} already exists");
        }
        foreach (DB::table('host_establishments')->whereIn('code', array_column($P['htes'], 'hte_id'))->pluck('code') as $taken) {
            $this->reject("htes: code {$taken} already exists");
        }
        $activate = $this->option('activate') ? (string) $this->option('activate') : null;
        if ($activate && ! in_array($activate, array_map('strval', array_column($P['students'], 'student_id')), true)) {
            $this->reject("--activate {$activate} is not in the students sheet");
        }

        $vocab = array_column($P['competency_vocabulary'], 'competency_name');
        foreach ($P['students'] as $s) {
            foreach (explode('; ', $s['skills']) as $k) {
                in_array($k, $vocab, true) || $this->reject("students {$s['student_id']}: skill '{$k}' not in competency_vocabulary");
            }
        }
        $hteIds = array_column($P['htes'], 'hte_id');
        foreach ($P['htes'] as $h) {
            $this->pick(['Active' => 'active', 'Expired' => 'expired'], $h['moa_status'], "htes {$h['hte_id']} moa_status");
            $this->pick(['Active' => 1, 'Inactive' => 0], $h['establishment_status'], "htes {$h['hte_id']} establishment_status");
            str_contains($h['complete_address'], 'Tagum City') || $this->reject("htes {$h['hte_id']}: city not found in address");
            in_array($h['supervisor_user_id'], $userIds, true) || $this->reject("htes {$h['hte_id']}: supervisor {$h['supervisor_user_id']} not selected");
        }
        foreach ($P['opportunities'] as $o) {
            in_array($o['hte_id'], $hteIds, true) || $this->reject("opportunities {$o['opportunity_id']}: unknown hte {$o['hte_id']}");
            $this->pick(['Published' => 'published', 'Closed' => 'closed'], $o['status'], "opportunities {$o['opportunity_id']} status");
            $o['eligible_program'] === $program->code || $this->reject("opportunities {$o['opportunity_id']}: eligible_program {$o['eligible_program']}");
            foreach (explode('; ', $o['required_skills']) as $k) {
                in_array($k, $vocab, true) || $this->reject("opportunities {$o['opportunity_id']}: skill '{$k}' not in competency_vocabulary");
            }
        }
        $studentIds = array_map('strval', array_column($P['students'], 'student_id'));
        foreach ($P['coordinator_labels'] as $l) {
            in_array((string) $l['student_id'], $studentIds, true) || $this->reject("coordinator_labels {$l['label_id']}: unknown student");
            in_array($l['labeled_by'], $userIds, true) || $this->reject("coordinator_labels {$l['label_id']}: labeled_by {$l['labeled_by']} not selected");
            $decisions = ['For Review' => null, 'Suitable' => 1, 'Not Suitable' => 0];
            $this->pick($decisions, $l['coordinator_decision'], "coordinator_labels {$l['label_id']} decision");
            $suitable = $l['suitable'] === null || $l['suitable'] === '' ? null : (int) $l['suitable'];
            if (array_key_exists((string) $l['coordinator_decision'], $decisions) && $suitable !== $decisions[$l['coordinator_decision']]) {
                $this->reject("coordinator_labels {$l['label_id']}: suitable does not match decision");
            }
        }
        $placements = array_column($P['placements'], null, 'placement_id');
        $htes = array_column($P['htes'], null, 'hte_id');
        foreach ($placements as $id => $p) {
            in_array((string) $p['student_id'], $studentIds, true) || $this->reject("placements {$id}: unknown student");
            isset($htes[$p['hte_id']]) || $this->reject("placements {$id}: unknown hte");
            'OPP-'.substr($p['hte_id'], 4) === $p['opportunity_id'] || $this->reject("placements {$id}: opportunity {$p['opportunity_id']} is not at {$p['hte_id']}");
            ($htes[$p['hte_id']]['supervisor_user_id'] ?? null) === $p['supervisor_id'] || $this->reject("placements {$id}: supervisor differs from the HTE supervisor");
            in_array($p['coordinator_id'], $userIds, true) || $this->reject("placements {$id}: coordinator not selected");
            $this->pick(['Completed' => 'completed', 'Incomplete' => 'incomplete'], $p['placement_status'], "placements {$id} status");
            (int) $p['required_hours'] === 400 || $this->reject("placements {$id}: required_hours {$p['required_hours']}");
        }
        foreach ($P['daily_attendance'] as $a) {
            isset($placements[$a['placement_id']]) || $this->reject("daily_attendance {$a['attendance_id']}: unknown placement");
            $kind = $this->pick(['Present' => 'p', 'Absent' => 'a'], $a['status'], "daily_attendance {$a['attendance_id']} status");
            if ($kind === 'p' && (! $a['time_in'] || ! $a['time_out'] || ! $a['approved_by'])) {
                $this->reject("daily_attendance {$a['attendance_id']}: present without times or approver");
            }
            if ($kind === 'a' && (float) $a['hours'] != 0.0) {
                $this->reject("daily_attendance {$a['attendance_id']}: absent with hours");
            }
        }
        foreach ($P['weekly_logs'] as $w) {
            isset($placements[$w['placement_id']]) || $this->reject("weekly_logs {$w['log_id']}: unknown placement");
            $this->pick(['Approved' => 'approved', 'Not Submitted' => 'not_submitted'], $w['status'], "weekly_logs {$w['log_id']} status");
        }
        foreach ($P['monitoring'] as $m) {
            isset($placements[$m['placement_id']]) || $this->reject("monitoring {$m['monitoring_id']}: unknown placement");
            $this->pick(['Yes' => 1, 'No' => 0], $m['monitoring_visit'], "monitoring {$m['monitoring_id']} monitoring_visit");
        }
        $criteria = array_unique(array_column($P['evaluations'], 'evaluation_rubric'));
        count($criteria) === 1 || $this->reject('evaluations: more than one evaluation_rubric value');
        foreach ($P['evaluations'] as $e) {
            isset($placements[$e['placement_id']]) || $this->reject("evaluations {$e['evaluation_id']}: unknown placement");
            ($placements[$e['placement_id']]['supervisor_id'] ?? null) === $e['supervisor_id'] || $this->reject("evaluations {$e['evaluation_id']}: evaluator is not the placement supervisor");
        }

        $bsaStudentUsers = array_column($P['students'], 'user_id');
        $evaluationIds = array_column($P['evaluations'], 'evaluation_id');
        $notifications = array_values(array_filter($A['notifications'], fn ($n) => in_array($n['user_id'], $bsaStudentUsers, true)));
        $chat = array_values(array_filter($A['chat_messages'], fn ($m) => isset($placements[$m['placement_id']])));
        $audit = array_values(array_filter($A['audit_logs'], fn ($r) => isset($placements[$r['record_id']]) || in_array($r['record_id'], $evaluationIds, true)));
        foreach ($notifications as $n) {
            $this->pick(['Yes' => 1, 'No' => 0], $n['read'], "notifications {$n['notification_id']} read");
        }
        foreach ($chat as $m) {
            in_array($m['sender_id'], $userIds, true) || $this->reject("chat_messages {$m['message_id']}: sender not selected");
        }
        foreach ($audit as $r) {
            in_array($r['user_id'], $userIds, true) || $this->reject("audit_logs {$r['audit_id']}: actor not selected");
            $this->pick(['Placement' => 1, 'Evaluation' => 1], $r['record_type'], "audit_logs {$r['audit_id']} record_type");
        }

        return ['users' => $users, 'staff' => $staff, 'accounts' => $accounts, 'activate' => $activate, 'notifications' => $notifications, 'chat' => $chat, 'audit' => $audit, 'role_permissions' => $A['role_permissions'], 'criteria' => explode('; ', (string) reset($criteria))];
    }

    private function write(array $P, array $plan, object $program): void
    {
        $now = $this->now;
        $ts = ['created_at' => $now, 'updated_at' => $now];
        $syn = ['is_synthetic' => true];
        $source = [];
        $keep = function (string $workbook, string $sheet, array $row, string $id, ?string $table, ?int $recordId) use (&$source, $now) {
            $source[] = ['workbook' => $workbook, 'sheet' => $sheet, 'source_id' => $id, 'table_name' => $table, 'record_id' => $recordId, 'values' => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'created_at' => $now];
        };
        $PW = basename((string) $this->argument('program_workbook'));
        $AW = basename((string) $this->argument('all_workbook'));

        // Term (values supplied by the coordinator, not the sheets).
        $start = (string) $this->option('term-start');
        $end = (string) $this->option('term-end');
        $termId = DB::table('academic_terms')->insertGetId(['code' => 'SYN-'.$program->code.'-'.substr($start, 0, 4).'-1', 'academic_year' => substr($start, 0, 4).'-'.((int) substr($start, 0, 4) + 1),
            'name' => (string) $this->option('term-name'), 'starts_on' => $start, 'ends_on' => $end, 'is_active' => false, ...$ts, ...$syn]);
        $programTermId = DB::table('program_terms')->insertGetId(['program_id' => $program->id, 'academic_term_id' => $termId, 'required_minutes' => 400 * 60, ...$ts, ...$syn]);

        // 1. Users and role permissions.
        $studentByUser = array_column($P['students'], null, 'user_id');
        $userId = [];
        foreach ($plan['users'] as $u) {
            if (isset($plan['staff'][$u['user_id']])) {
                $userId[$u['user_id']] = $plan['staff'][$u['user_id']]; // existing real account; nothing created or changed
                $keep($AW, 'users', $u, $u['user_id'], 'users', $userId[$u['user_id']]);

                continue;
            }
            $student = $studentByUser[$u['user_id']] ?? null;
            $pending = $student && $plan['activate'] === (string) $student['student_id'];
            $userId[$u['user_id']] = DB::table('users')->insertGetId([
                'name' => $u['full_name'], 'email' => $student['email'] ?? $u['email'], 'role' => strtolower($u['role']),
                'status' => $pending ? AccountStatus::Pending->value : AccountStatus::Active->value,
                'email_verified_at' => $pending ? null : $now, 'password' => Hash::make(Str::random(64)), 'must_change_password' => false,
                'source_id' => $u['user_id'], ...$ts, ...$syn,
            ]);
            $keep($AW, 'users', $u, $u['user_id'], 'users', $userId[$u['user_id']]);
        }
        foreach ($plan['role_permissions'] as $r) {
            $id = DB::table('role_permission_references')->insertGetId(['source_id' => $r['permission_id'], 'permission' => $r['permission'], 'admin' => $r['Admin'] === 'Yes', 'dean' => $r['Dean'] === 'Yes',
                'coordinator' => $r['Coordinator'] === 'Yes', 'supervisor' => $r['Supervisor'] === 'Yes', 'student' => $r['Student'] === 'Yes', ...$ts, ...$syn]);
            $keep($AW, 'role_permissions', $r, $r['permission_id'], 'role_permission_references', $id);
        }

        // 2. Competency vocabulary: an existing competency with the exact same name is reused (not duplicated).
        $competency = [];
        foreach ($P['competency_vocabulary'] as $c) {
            $existing = DB::table('competencies')->where('name', $c['competency_name'])->value('id');
            $competency[$c['competency_name']] = $existing ?? DB::table('competencies')->insertGetId(['code' => $c['competency_id'], 'name' => $c['competency_name'], 'description' => $c['description'], 'is_active' => true, 'source_id' => $c['competency_id'], ...$ts, ...$syn]);
            DB::table('program_competencies')->insertOrIgnore(['program_id' => $program->id, 'competency_id' => $competency[$c['competency_name']], ...$ts]);
            $keep($PW, 'competency_vocabulary', $c, $c['competency_id'], 'competencies', $competency[$c['competency_name']]);
        }

        // 3. HTEs and MOAs.
        $host = $moa = [];
        foreach ($P['htes'] as $h) {
            $host[$h['hte_id']] = DB::table('host_establishments')->insertGetId(['code' => $h['hte_id'], 'name' => $h['establishment_name'], 'industry' => $h['industry'], 'address' => $h['complete_address'], 'city' => 'Tagum City',
                'latitude' => $h['latitude'], 'longitude' => $h['longitude'], 'contact_name' => $h['contact_person'], 'contact_email' => $h['email'], 'contact_number' => (string) $h['contact_number'],
                'description' => $h['establishment_description'], 'is_active' => $h['establishment_status'] === 'Active', 'source_id' => $h['hte_id'], ...$ts, ...$syn]);
            $moa[$h['hte_id']] = DB::table('moas')->insertGetId(['host_establishment_id' => $host[$h['hte_id']], 'reference_number' => 'MOA-'.$h['hte_id'], 'status' => strtolower($h['moa_status']),
                'effective_on' => $h['moa_start_date'], 'expires_on' => $h['moa_expiration_date'], 'is_institution_wide' => false, 'source_id' => $h['hte_id'], ...$ts, ...$syn]);
            DB::table('moa_program')->insert(['moa_id' => $moa[$h['hte_id']], 'program_id' => $program->id, ...$ts]);
            DB::table('host_program_capacity')->insert(['host_establishment_id' => $host[$h['hte_id']], 'program_id' => $program->id, 'academic_term_id' => $termId, 'capacity' => (int) $h['available_slots'], ...$ts]);
            DB::table('host_establishment_user')->insert(['host_establishment_id' => $host[$h['hte_id']], 'user_id' => $userId[$h['supervisor_user_id']], ...$ts]);
            $keep($PW, 'htes', $h, $h['hte_id'], 'host_establishments', $host[$h['hte_id']]);
        }

        // 4. Opportunities.
        $opportunity = [];
        foreach ($P['opportunities'] as $o) {
            $opportunity[$o['opportunity_id']] = DB::table('opportunities')->insertGetId(['host_establishment_id' => $host[$o['hte_id']], 'academic_term_id' => $termId, 'title' => $o['opportunity_title'],
                'description' => $o['description'], 'tasks' => $o['internship_duties'], 'capacity' => (int) $o['available_slots'], 'status' => strtolower($o['status']),
                'created_at' => $o['date_created'], 'updated_at' => $o['date_updated'], 'source_id' => $o['opportunity_id'], ...$syn]);
            DB::table('opportunity_program')->insert(['opportunity_id' => $opportunity[$o['opportunity_id']], 'program_id' => $program->id, 'capacity' => (int) $o['available_slots'], ...$ts]);
            foreach (explode('; ', $o['required_skills']) as $skill) {
                DB::table('opportunity_competency')->insert(['opportunity_id' => $opportunity[$o['opportunity_id']], 'competency_id' => $competency[$skill], 'is_required' => true, ...$ts]);
            }
            $keep($PW, 'opportunities', $o, $o['opportunity_id'], 'opportunities', $opportunity[$o['opportunity_id']]);
        }

        // 5. Students: student record, profile (contact, address, coordinates), enrollment, skills (no level in the data).
        $student = $enrollment = [];
        foreach ($P['students'] as $s) {
            $sid = (string) $s['student_id'];
            $uid = $userId[$s['user_id']];
            $student[$sid] = DB::table('students')->insertGetId(['user_id' => $uid, 'student_number' => $sid, 'source_id' => $sid, ...$ts, ...$syn]);
            DB::table('user_profiles')->insert(['user_id' => $uid, 'contact_number' => (string) $s['contact_number'], 'address' => $s['home_address'], 'latitude' => $s['latitude'], 'longitude' => $s['longitude'], ...$ts]);
            $enrollment[$sid] = DB::table('student_enrollments')->insertGetId(['student_id' => $student[$sid], 'program_term_id' => $programTermId, 'year_level' => (int) $s['year_level'], 'required_minutes' => 400 * 60,
                'enrolled_on' => $start, 'source_id' => $sid, ...$ts, ...$syn]);
            foreach (explode('; ', $s['skills']) as $skill) {
                DB::table('student_competencies')->insert(['student_id' => $student[$sid], 'competency_id' => $competency[$skill], 'level' => null, ...$ts]);
            }
            $keep($PW, 'students', $s, $sid, 'students', $student[$sid]);
        }

        // 6. Coordinator labels ("For Review" rows keep suitable = NULL and are skipped by training exports).
        foreach ($P['coordinator_labels'] as $l) {
            $id = DB::table('coordinator_labels')->insertGetId(['source_id' => $l['label_id'], 'student_id' => $student[(string) $l['student_id']], 'program_id' => $program->id, 'hte_code' => $l['hte_id'], 'opportunity_code' => $l['opportunity_id'],
                'opportunity_id' => $opportunity[$l['opportunity_id']] ?? null, 'program_match' => (bool) $l['program_match'], 'skill_match_score' => $l['skill_match_score'], 'distance_km' => $l['distance_km'],
                'available_slots' => (int) $l['available_slots'], 'has_slot' => (bool) $l['has_slot'], 'moa_valid' => (bool) $l['moa_valid'], 'coordinator_decision' => $l['coordinator_decision'],
                'suitable' => $l['suitable'] === null || $l['suitable'] === '' ? null : (bool) $l['suitable'], 'decision_reason' => $l['decision_reason'], 'labeled_by' => $userId[$l['labeled_by']], ...$ts, ...$syn]);
            $keep($PW, 'coordinator_labels', $l, $l['label_id'], 'coordinator_labels', $id);
        }

        // 7. Placements (completed_at = end_date for completed placements).
        $placement = [];
        foreach ($P['placements'] as $p) {
            $completed = $p['placement_status'] === 'Completed';
            $placement[$p['placement_id']] = DB::table('placements')->insertGetId(['student_enrollment_id' => $enrollment[(string) $p['student_id']], 'host_establishment_id' => $host[$p['hte_id']],
                'opportunity_id' => $opportunity[$p['opportunity_id']], 'moa_id' => $moa[$p['hte_id']], 'supervisor_id' => $userId[$p['supervisor_id']], 'status' => strtolower($p['placement_status']),
                'starts_on' => $p['start_date'], 'ends_on' => $p['end_date'], 'completed_at' => $completed ? $this->local($p['end_date']) : null, 'source_id' => $p['placement_id'], ...$ts, ...$syn]);
            $keep($PW, 'placements', $p, $p['placement_id'], 'placements', $placement[$p['placement_id']]);
        }

        // 8. Daily attendance -> time logs (present) / absences; weekly logs; monitoring check-ins.
        $rows = $P['daily_attendance'];
        usort($rows, fn ($a, $b) => [$a['placement_id'], $a['date'], $a['attendance_id']] <=> [$b['placement_id'], $b['date'], $b['attendance_id']]);
        $running = [];
        foreach ($rows as $a) {
            $pid = $placement[$a['placement_id']];
            if ($a['status'] === 'Absent') {
                $id = DB::table('attendance_absences')->insertGetId(['source_id' => $a['attendance_id'], 'placement_id' => $pid, 'absent_on' => $a['date'], ...$ts, ...$syn]);
                $keep($PW, 'daily_attendance', $a, $a['attendance_id'], 'attendance_absences', $id);

                continue;
            }
            $in = $this->local($a['date'].' '.$a['time_in']);
            $out = $this->local($a['date'].' '.$a['time_out']);
            // Credited minutes use running rounding so each placement's total equals its summed source hours.
            $before = $running[$pid] ?? 0.0;
            $running[$pid] = $before + (float) $a['hours'];
            $credited = (int) round($running[$pid] * 60) - (int) round($before * 60);
            $gap = $in->diffInMinutes($out) - (float) $a['hours'] * 60; // ~0 or ~60 in the data
            $id = DB::table('time_logs')->insertGetId(['placement_id' => $pid, 'work_date' => $a['date'], 'time_in' => $in, 'time_out' => $out, 'break_minutes' => $gap > 30 ? 60 : 0,
                'credited_minutes' => $credited, 'hours' => $a['hours'], 'status' => 'verified', 'verified_by' => $userId[$a['approved_by']], 'verified_at' => $out,
                'source_id' => $a['attendance_id'], ...$ts, ...$syn]);
            $keep($PW, 'daily_attendance', $a, $a['attendance_id'], 'time_logs', $id);
        }
        foreach ($P['weekly_logs'] as $w) {
            $id = DB::table('journal_entries')->insertGetId(['placement_id' => $placement[$w['placement_id']], 'week_starts_on' => $w['week_start'],
                'content' => $w['activities']."\n\nLearnings: ".$w['learnings'], 'status' => $w['status'] === 'Approved' ? 'approved' : 'not_submitted', 'source_id' => $w['log_id'], ...$ts, ...$syn]);
            $keep($PW, 'weekly_logs', $w, $w['log_id'], 'journal_entries', $id);
        }
        foreach ($P['monitoring'] as $m) {
            $id = DB::table('monitoring_records')->insertGetId(['source_id' => $m['monitoring_id'], 'placement_id' => $placement[$m['placement_id']], 'monitoring_date' => $m['monitoring_date'], 'hours_rendered' => $m['hours_rendered'],
                'days_absent' => (int) $m['days_absent'], 'attendance_status' => $m['attendance_status'], 'weekly_logs_submitted' => (int) $m['weekly_logs_submitted'], 'accomplishment_report' => $m['accomplishment_report'],
                'monitoring_visit' => $m['monitoring_visit'] === 'Yes', 'issues_concerns' => $m['issues_concerns'] ?: null, 'monitoring_status' => $m['monitoring_status'], 'approved_by' => $userId[$m['approved_by']], ...$ts, ...$syn]);
            $keep($PW, 'monitoring', $m, $m['monitoring_id'], 'monitoring_records', $id);
        }

        // 9. Evaluations on a synthetic 3-criterion rubric matching the sheet's three scores (equal weights).
        $rubricId = DB::table('evaluation_rubrics')->insertGetId(['code' => 'SYN-'.$program->code.'-3CAT', 'version' => 1, 'name' => 'Synthetic '.$program->code.' evaluation (3 categories) - not department approved', 'is_active' => false, ...$ts, ...$syn]);
        $criterion = [];
        foreach ($plan['criteria'] as $i => $name) {
            $criterion[$i] = DB::table('evaluation_criteria')->insertGetId(['evaluation_rubric_id' => $rubricId, 'name' => $name, 'max_score' => 5, 'weight' => 1, 'sort_order' => $i + 1, ...$ts]);
        }
        $evaluation = [];
        foreach ($P['evaluations'] as $e) {
            $at = $this->local($e['evaluation_date']);
            $evaluation[$e['evaluation_id']] = DB::table('evaluations')->insertGetId(['placement_id' => $placement[$e['placement_id']], 'evaluation_rubric_id' => $rubricId, 'evaluator_id' => $userId[$e['supervisor_id']],
                'period' => 'final', 'status' => 'submitted', 'comments' => $e['comments'], 'submitted_at' => $at, 'source_id' => $e['evaluation_id'], 'created_at' => $at, 'updated_at' => $at, ...$syn]);
            foreach ([1, 2, 3] as $n) {
                DB::table('evaluation_scores')->insert(['evaluation_id' => $evaluation[$e['evaluation_id']], 'evaluation_criterion_id' => $criterion[$n - 1], 'evaluation_rubric_id' => $rubricId, 'score' => $e['score_'.$n], 'created_at' => $at, 'updated_at' => $at]);
            }
            $keep($PW, 'evaluations', $e, $e['evaluation_id'], 'evaluations', $evaluation[$e['evaluation_id']]);
        }

        // 10. Notifications, chat messages (one conversation per placement thread), audit logs.
        foreach ($plan['notifications'] as $n) {
            $at = $this->local($n['created_at']);
            $id = (string) Str::uuid();
            DB::table('notifications')->insert(['id' => $id, 'type' => PortalNotification::class, 'notifiable_type' => (new User)->getMorphClass(), 'notifiable_id' => $userId[$n['user_id']],
                'data' => json_encode(['title' => $n['type'], 'body' => $n['message']], JSON_THROW_ON_ERROR), 'read_at' => $n['read'] === 'Yes' ? $at : null, 'created_at' => $at, 'updated_at' => $at,
                'source_id' => $n['notification_id'], ...$syn]);
            $keep($AW, 'notifications', $n, $n['notification_id'], 'notifications', null);
        }
        $conversation = [];
        $sentAt = array_map(fn ($m) => $this->local($m['sent_at']), $plan['chat']);
        foreach ($plan['chat'] as $i => $m) {
            if (! isset($conversation[$m['thread_id']])) {
                $pid = $placement[$m['placement_id']];
                $conversation[$m['thread_id']] = DB::table('conversations')->insertGetId(['context_key' => 'placement:'.$pid, 'student_enrollment_id' => DB::table('placements')->where('id', $pid)->value('student_enrollment_id'),
                    'placement_id' => $pid, 'source_id' => $m['thread_id'], 'created_at' => $sentAt[$i], 'updated_at' => $sentAt[$i], ...$syn]);
            }
            $id = DB::table('chat_messages')->insertGetId(['conversation_id' => $conversation[$m['thread_id']], 'sender_id' => $userId[$m['sender_id']], 'client_id' => (string) Str::uuid(), 'body' => $m['message'],
                'created_at' => $sentAt[$i], 'updated_at' => $sentAt[$i], 'source_id' => $m['message_id'], ...$syn]);
            $keep($AW, 'chat_messages', $m, $m['message_id'], 'chat_messages', $id);
        }
        foreach ($plan['audit'] as $r) {
            $isPlacement = $r['record_type'] === 'Placement';
            $id = DB::table('audit_logs')->insertGetId(['actor_id' => $userId[$r['user_id']], 'program_id' => $program->id, 'action' => $r['action'],
                'subject_type' => $isPlacement ? (new Placement)->getMorphClass() : (new Evaluation)->getMorphClass(), 'subject_id' => $isPlacement ? $placement[$r['record_id']] : $evaluation[$r['record_id']],
                'changes' => '{}', 'created_at' => $this->local($r['timestamp']), 'source_id' => $r['audit_id'], ...$syn]);
            $keep($AW, 'audit_logs', $r, $r['audit_id'], 'audit_logs', $id);
        }

        foreach (array_chunk($source, 500) as $chunk) {
            DB::table('synthetic_source_rows')->insert($chunk);
        }
    }
}
