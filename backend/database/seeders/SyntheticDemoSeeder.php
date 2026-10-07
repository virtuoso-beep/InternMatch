<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\Document;
use App\Models\Evaluation;
use App\Models\EvaluationRubric;
use App\Models\EventAttendance;
use App\Models\GeneratedReport;
use App\Models\HostEstablishment;
use App\Models\JournalEntry;
use App\Models\Moa;
use App\Models\Opportunity;
use App\Models\Placement;
use App\Models\Program;
use App\Models\ProgramTerm;
use App\Models\RequirementSubmission;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\TimeLog;
use App\Models\User;
use App\Notifications\PortalNotification;
use App\Services\Audit;
use App\Services\ProgramRequirementTemplates;
use App\Services\ReportBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Opt-in local fixtures. Re-running preserves credentials, user edits and history. */
class SyntheticDemoSeeder extends Seeder
{
    public const TERM = 'SYNTHETIC-2026-1';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Synthetic data is restricted to local/testing environments.');
        }
        // Never provision or reset a login account. Validate all five originals first.
        foreach (['student', 'coordinator', 'supervisor', 'dean', 'admin'] as $role) {
            $this->account($role);
        }
        DB::transaction(function () {
            $this->call(DatabaseSeeder::class);
            $bsit = Program::where('code', 'BSIT')->firstOrFail();
            $bscs = Program::where('code', 'BSCS')->firstOrFail();
            $admin = $this->account('admin');
            $coordinator = $this->account('coordinator');
            $dean = $this->account('dean');
            $supervisor = $this->account('supervisor');
            foreach ([$coordinator, $dean] as $staff) {
                $staff->programs()->syncWithoutDetaching([$bsit->id]);
            }
            $academic = AcademicTerm::firstOrCreate(['code' => self::TERM], [
                'academic_year' => '2026-2027', 'name' => 'Synthetic testing cohort 2026',
                'starts_on' => '2026-08-01', 'ends_on' => '2027-05-31', 'is_active' => true,
            ]);
            $term = ProgramTerm::firstOrCreate(['program_id' => $bsit->id, 'academic_term_id' => $academic->id], ['required_minutes' => $bsit->required_ojt_hours * 60]);
            ProgramRequirementTemplates::apply($term);
            $hosts = [];
            $opportunities = [];
            $moas = [];
            foreach ([
                ['CITY', 'Synthetic Tagum City ICT Office', 'Web Development Intern', 'Develop web pages using HTML/CSS, JavaScript and PHP/Laravel. Maintain databases and document technical support.', 7.4474, 125.8094, 3, 'active'],
                ['SOFT', 'Synthetic Davao Software Studio', 'Application Development Intern', 'Build web applications, write software tests and design relational databases.', 7.0744, 125.6128, 4, 'active'],
                ['NET', 'Synthetic Tagum Network Services', 'Network Support Intern', 'Configure networks, troubleshoot workstations and maintain technical documentation.', 7.4600, 125.8000, 3, 'active'],
                ['EXPIRED', 'Synthetic Expired Agreement Host', 'Archived IT Internship', 'Programming and database maintenance.', 7.4300, 125.8100, 2, 'expired'],
            ] as [$code, $name, $title, $tasks, $lat, $lon, $capacity, $status]) {
                $host = HostEstablishment::firstOrCreate(['code' => 'SYN-'.$code], [
                    'name' => $name, 'industry' => 'Information and Communications Technology',
                    'address' => 'Synthetic address for testing only', 'city' => $code === 'SOFT' ? 'Davao City' : 'Tagum City',
                    'latitude' => $lat, 'longitude' => $lon, 'contact_name' => 'Demo Contact '.$code,
                    'contact_email' => strtolower($code).'@example.test', 'contact_number' => '000-000-0000',
                    'description' => 'Fictional host based on the data guide. No real agreement or contact is implied.', 'is_active' => true,
                ]);
                $host->supervisors()->syncWithoutDetaching([$supervisor->id]);
                DB::table('host_program_capacity')->insertOrIgnore(['host_establishment_id' => $host->id, 'program_id' => $bsit->id, 'academic_term_id' => $academic->id, 'capacity' => $capacity]);
                $moa = Moa::firstOrCreate(['reference_number' => 'SYN-MOA-'.$code], ['host_establishment_id' => $host->id, 'status' => $status, 'effective_on' => '2025-06-01', 'expires_on' => $status === 'expired' ? '2026-05-31' : '2027-05-31']);
                $moa->programs()->syncWithoutDetaching([$bsit->id]);
                $opportunity = Opportunity::firstOrCreate(['host_establishment_id' => $host->id, 'academic_term_id' => $academic->id, 'title' => 'Synthetic '.$title], [
                    'description' => 'Fictional testing opportunity. Weekdays, 8am-5pm. '.$tasks,
                    'tasks' => $tasks, 'capacity' => $capacity, 'status' => 'published', 'starts_on' => '2026-08-01', 'ends_on' => '2027-05-31',
                ]);
                if (! $opportunity->programs()->whereKey($bsit->id)->exists()) {
                    $opportunity->programs()->attach($bsit->id, ['capacity' => $capacity]);
                }
                foreach ($bsit->competencies as $skill) {
                    if (! $opportunity->competencies()->whereKey($skill->id)->exists()) {
                        $opportunity->competencies()->attach($skill->id, ['minimum_level' => 50, 'is_required' => true]);
                    }
                }
                if ($code === 'CITY') {
                    // Separate CS capacity, never borrowed by BSIT. CS hours remain unconfirmed.
                    DB::table('host_program_capacity')->insertOrIgnore(['host_establishment_id' => $host->id, 'program_id' => $bscs->id, 'academic_term_id' => $academic->id, 'capacity' => 1]);
                    if (! $opportunity->programs()->whereKey($bscs->id)->exists()) {
                        $opportunity->programs()->attach($bscs->id, ['capacity' => 1]);
                    }
                    $moa->programs()->syncWithoutDetaching([$bscs->id]);
                }
                $hosts[] = $host;
                $opportunities[] = $opportunity;
                $moas[] = $moa;
            }
            $rubric = EvaluationRubric::firstOrCreate(['code' => 'SYNTHETIC-ONLY', 'version' => 1], ['name' => 'Synthetic testing rubric - not department approved', 'is_active' => true]);
            foreach (['Work quality', 'Communication', 'Professional conduct'] as $i => $name) {
                $rubric->criteria()->firstOrCreate(['name' => $name], ['max_score' => 5, 'weight' => 1, 'sort_order' => $i]);
            }
            foreach ([1 => 'student'] as $i => $key) {
                $user = $this->account($key);
                $user->profile()->firstOrCreate([], ['address' => 'Synthetic residence, Tagum City', 'contact_number' => '000-000-0000', 'latitude' => 7.44 + $i * 0.002, 'longitude' => 125.80,
                    'bio' => 'Synthetic BSIT student for testing only.', 'knowledge_areas' => 'Web application development and relational database design', 'preferred_internship_location' => 'Tagum City ICT Office']);
                $student = Student::firstOrCreate(['user_id' => $user->id], ['student_number' => 'SYN-ORIGINAL-STUDENT']);
                foreach ($bsit->competencies as $skill) {
                    $student->studentCompetencies()->firstOrCreate(['competency_id' => $skill->id], ['level' => 65 + $i * 4, 'assessed_at' => now()]);
                }
                $enrollment = StudentEnrollment::firstOrCreate(['student_id' => $student->id, 'program_term_id' => $term->id], [
                    'year_level' => 4, 'required_minutes' => $bsit->required_ojt_hours * 60, 'status' => 'enrolled',
                    'enrolled_on' => '2026-08-01', 'target_completion_on' => '2027-02-28',
                ]);
                // Only initialize new fixture enrollments; never reset a tester's progress.
                if (! $enrollment->wasRecentlyCreated) {
                    continue;
                }
                if ($i >= 1 && $i <= 5) {
                    DB::table('student_enrollments')->where('id', $enrollment->id)->update(['academic_eligibility_confirmed' => true, 'eligibility_confirmed_by' => $coordinator->id, 'eligibility_confirmed_at' => now(), 'eligibility_note' => 'Synthetic eligibility fixture; not an actual academic attestation.']);
                }
                foreach ($term->requirements()->with('requirementType')->get() as $r) {
                    if ($i === 0) {
                        continue;
                    } // Exact empty checklist example from the document.
                    if ($r->requirementType->kind === 'event') {
                        if ($i <= 5) {
                            EventAttendance::create(['student_enrollment_id' => $enrollment->id, 'program_term_requirement_id' => $r->id, 'program_term_id' => $term->id, 'attended_on' => '2026-08-03', 'confirmed_by' => $coordinator->id, 'confirmed_at' => now()]);
                        }

                        continue; // Catalog schedule stays TBA. Historical synthetic attendance is not an announced date.
                    }
                    $document = $this->document($user, 'synthetic/'.$student->student_number.'/'.$r->requirementType->code.'.pdf');
                    $status = $i <= 5 ? 'approved' : ($i === 6 ? 'submitted' : 'rejected');
                    $submission = RequirementSubmission::create(['student_enrollment_id' => $enrollment->id, 'program_term_id' => $term->id, 'program_term_requirement_id' => $r->id, 'document_id' => $document->id, 'revision' => 1, 'status' => $status, 'submitted_at' => now()]);
                    if ($status !== 'submitted') {
                        $submission->reviews()->create(['reviewed_by' => $coordinator->id, 'decision' => $status, 'comments' => 'Synthetic review for testing only.', 'reviewed_at' => now()]);
                    }
                }
                if ($i >= 1 && $i <= 3) {
                    $h = $i - 1;
                    $placement = Placement::create(['student_enrollment_id' => $enrollment->id, 'opportunity_id' => $opportunities[$h]->id, 'host_establishment_id' => $hosts[$h]->id, 'moa_id' => $moas[$h]->id, 'supervisor_id' => $supervisor->id, 'status' => 'active', 'starts_on' => '2026-08-10', 'ends_on' => '2027-02-28']);
                    $placement->decisions()->create(['decided_by' => $coordinator->id, 'decision' => 'approve', 'to_opportunity_id' => $opportunities[$h]->id, 'reason' => 'Synthetic placement fixture, not a real deployment.', 'decided_at' => now()]);
                    foreach (range(0, 4) as $day) {
                        $date = CarbonImmutable::parse('2026-09-21')->addDays($day)->toDateString();
                        TimeLog::create(['placement_id' => $placement->id, 'work_date' => $date, 'time_in' => $date.' 00:00:00', 'time_out' => $date.' 09:00:00', 'break_minutes' => 60, 'credited_minutes' => 480, 'status' => 'verified', 'verified_by' => $supervisor->id, 'verified_at' => now(), 'notes' => 'Synthetic eight-hour workday.']);
                    }
                    JournalEntry::create(['placement_id' => $placement->id, 'week_starts_on' => '2026-09-21', 'content' => 'Synthetic accomplishment report: built a sample page, practiced database queries and documented testing results.', 'status' => 'submitted', 'submitted_at' => now()]);
                    $evaluation = Evaluation::create(['placement_id' => $placement->id, 'evaluation_rubric_id' => $rubric->id, 'evaluator_id' => $supervisor->id, 'period' => 'synthetic-midterm', 'status' => 'submitted', 'comments' => 'Synthetic scores for interface testing; no approved institutional rubric is implied.', 'submitted_at' => now()]);
                    foreach ($rubric->criteria as $criterion) {
                        $evaluation->scores()->create(['evaluation_criterion_id' => $criterion->id, 'evaluation_rubric_id' => $rubric->id, 'score' => 4]);
                    }
                }
                $user->notify(new PortalNotification('Synthetic testing data ready', 'Your fictional profile, checklist and test records are saved. They are not real student records.'));
            }
            foreach (ReportBuilder::KINDS as $kind) {
                if (! GeneratedReport::where('program_term_id', $term->id)->where('kind', $kind)->where('generated_by', $coordinator->id)->exists()) {
                    $payload = app(ReportBuilder::class)->build($term, $kind);
                    GeneratedReport::create(['program_term_id' => $term->id, 'kind' => $kind, 'payload' => $payload, 'content_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)), 'generated_by' => $coordinator->id]);
                }
            }
            Audit::record($admin, 'synthetic.seeded', $term, ['source' => 'SyntheticDemoSeeder', 'real_personal_data' => false], $bsit->id);
        });
    }

    private function account(string $key): User
    {
        return User::where('email', $key.'@example.com')->where('role', $key)->where('status', 'active')->first()
            ?? throw new \RuntimeException('Expected active original account: '.$key.'@example.com. No accounts were created or changed.');
    }

    private function document(User $user, string $path): Document
    {
        // A real, readable one-page PDF, never a missing file or forged certificate.
        $stream = 'BT /F1 18 Tf 45 750 Td (SYNTHETIC TEST DOCUMENT) Tj 0 -30 Td /F1 11 Tf (Not a real certificate, clearance or student record.) Tj ET';
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>', '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', '<< /Length '.strlen($stream).">>\nstream\n".$stream."\nendstream"];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
        Storage::disk('local')->put($path, $pdf);

        return Document::firstOrCreate(['disk' => 'local', 'path' => $path], ['uploaded_by' => $user->id, 'original_name' => 'SYNTHETIC-TEST-DOCUMENT.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => strlen($pdf), 'sha256' => hash('sha256', $pdf)]);
    }
}
