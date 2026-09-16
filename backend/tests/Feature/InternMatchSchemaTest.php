<?php

namespace Tests\Feature;

use App\Enums\PlacementStatus;
use App\Models\Competency;
use App\Models\CompetencyEvidence;
use App\Models\Document;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationScore;
use App\Models\HostEstablishment;
use App\Models\JournalEntry;
use App\Models\Moa;
use App\Models\MonitoringFlag;
use App\Models\OpportunityInterest;
use App\Models\Placement;
use App\Models\PlacementDecision;
use App\Models\ProgramTerm;
use App\Models\ProgramTermRequirement;
use App\Models\RequirementReview;
use App\Models\RequirementSubmission;
use App\Models\StudentCompetency;
use App\Models\StudentEnrollment;
use App\Models\TimeLog;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\InternMatchReferenceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InternMatchSchemaTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_enrollment_retains_configured_minutes_when_program_requirements_change(): void
    {
        $term = ProgramTerm::factory()->create(['required_minutes' => 18000]);
        $enrollment = StudentEnrollment::factory()->for($term)->create();

        $term->update(['required_minutes' => 21000]);

        $this->assertSame(18000, $enrollment->fresh()->required_minutes);
        $this->assertSame(21000, $enrollment->fresh()->programTerm->required_minutes);
    }

    public function test_same_student_cannot_enroll_twice_in_the_same_program_term(): void
    {
        $enrollment = StudentEnrollment::factory()->create();

        $this->expectException(QueryException::class);
        StudentEnrollment::factory()->create([
            'student_id' => $enrollment->student_id,
            'program_term_id' => $enrollment->program_term_id,
        ]);
    }

    public function test_placement_connects_student_term_host_opportunity_and_agreement(): void
    {
        $placement = Placement::factory()->create();
        $placement->opportunity->programs()->attach($placement->studentEnrollment->programTerm->program_id);
        $placement->moa->programs()->attach($placement->studentEnrollment->programTerm->program_id);

        $loaded = $placement->fresh(['studentEnrollment.student.user', 'opportunity.programs', 'moa.programs']);

        $this->assertSame(PlacementStatus::Pending, $loaded->status);
        $this->assertSame($loaded->host_establishment_id, $loaded->opportunity->host_establishment_id);
        $this->assertSame($loaded->host_establishment_id, $loaded->moa->host_establishment_id);
        $this->assertTrue($loaded->studentEnrollment->currentPlacement->is($loaded));
        $this->assertTrue($loaded->opportunity->programs->first()->is($loaded->moa->programs->first()));
        $this->assertTrue($loaded->hostEstablishment->placements->contains($loaded));
        $this->assertTrue($loaded->studentEnrollment->student->user->student->is($loaded->studentEnrollment->student));
    }

    public function test_only_one_current_placement_exists_per_enrollment(): void
    {
        $placement = Placement::factory()->create();

        $this->expectException(QueryException::class);
        Placement::factory()->create(['student_enrollment_id' => $placement->student_enrollment_id]);
    }

    public function test_host_with_placement_history_cannot_be_deleted(): void
    {
        $placement = Placement::factory()->create();

        $this->expectException(QueryException::class);
        $placement->hostEstablishment->delete();
    }

    public function test_placement_cannot_reference_another_hosts_moa(): void
    {
        $placement = Placement::factory()->create();
        $otherMoa = Moa::factory()->create();

        $this->expectException(QueryException::class);
        $placement->update(['moa_id' => $otherMoa->id]);
    }

    public function test_placement_cannot_reference_another_hosts_opportunity(): void
    {
        $placement = Placement::factory()->create();
        $other = Placement::factory()->create();

        $this->expectException(QueryException::class);
        $placement->update(['opportunity_id' => $other->opportunity_id]);
    }

    public function test_submission_cannot_use_a_requirement_from_another_program_term(): void
    {
        $submission = RequirementSubmission::factory()->create();
        $otherRequirement = ProgramTermRequirement::factory()->create();

        $this->expectException(QueryException::class);
        $submission->update(['program_term_requirement_id' => $otherRequirement->id]);
    }

    public function test_document_revisions_retain_separate_reviews_and_private_storage_metadata(): void
    {
        $review = RequirementReview::factory()->create();
        $submission = $review->requirementSubmission;
        $next = RequirementSubmission::factory()->create([
            'student_enrollment_id' => $submission->student_enrollment_id,
            'program_term_id' => $submission->program_term_id,
            'program_term_requirement_id' => $submission->program_term_requirement_id,
            'revision' => 2,
        ]);

        $this->assertCount(2, $submission->studentEnrollment->requirementSubmissions);
        $this->assertTrue($submission->reviews->first()->is($review));
        $this->assertTrue($review->reviewer->requirementReviews->contains($review));
        $this->assertNotSame($submission->document_id, $next->document_id);
        $this->assertArrayNotHasKey('path', $submission->document->toArray());
        $this->assertArrayNotHasKey('disk', $submission->document->toArray());
    }

    public function test_evaluation_scores_use_the_same_rubric_as_the_evaluation(): void
    {
        $score = EvaluationScore::factory()->create();
        $criterion = EvaluationCriterion::factory()->create();

        $this->expectException(QueryException::class);
        $score->update(['evaluation_criterion_id' => $criterion->id]);
    }

    public function test_evaluation_relations_preserve_rubric_and_evaluator(): void
    {
        $score = EvaluationScore::factory()->create();

        $this->assertTrue($score->evaluation->evaluationRubric->is($score->evaluationCriterion->evaluationRubric));
        $this->assertTrue($score->evaluation->scores->first()->is($score));
        $this->assertTrue($score->evaluation->evaluator->evaluations->contains($score->evaluation));
        $this->assertSame('4.00', $score->fresh()->score);
    }

    public function test_competency_evidence_and_curriculum_targets_are_separate_records(): void
    {
        $evidence = CompetencyEvidence::factory()->create(['document_id' => Document::factory()]);
        $term = ProgramTerm::factory()->create();
        $competency = $evidence->studentCompetency->competency;
        $term->competencies()->attach($competency, ['target_level' => 80]);
        $placement = Placement::factory()->create();
        $placement->opportunity->competencies()->attach($competency, ['minimum_level' => 60]);

        $this->assertSame(75, $evidence->studentCompetency->level);
        $this->assertSame(80, (int) $term->competencies->first()->pivot->target_level);
        $this->assertTrue($evidence->document->competencyEvidence->contains($evidence));
        $this->assertTrue($competency->opportunities->first()->is($placement->opportunity));
        $this->assertTrue($evidence->studentCompetency->student->studentCompetencies->contains($evidence->studentCompetency));
    }

    public function test_monitoring_and_decision_history_belong_to_the_placement(): void
    {
        $placement = Placement::factory()->create();
        $log = TimeLog::factory()->for($placement)->create();
        $journal = JournalEntry::factory()->for($placement)->create();
        $flag = MonitoringFlag::factory()->for($placement)->create();
        $decision = PlacementDecision::factory()->for($placement)->create();

        $this->assertTrue($placement->timeLogs->contains($log));
        $this->assertTrue($placement->journalEntries->contains($journal));
        $this->assertTrue($placement->monitoringFlags->contains($flag));
        $this->assertTrue($placement->decisions->contains($decision));
        $this->assertTrue($decision->coordinator->placementDecisions->contains($decision));
    }

    public function test_interest_does_not_create_or_approve_a_placement(): void
    {
        $interest = OpportunityInterest::factory()->create();

        $this->assertNull($interest->studentEnrollment->currentPlacement);
        $this->assertTrue($interest->opportunity->interests->contains($interest));
        $this->assertTrue($interest->studentEnrollment->interests->contains($interest));
    }

    public function test_profile_and_access_assignments_are_linked_to_the_user(): void
    {
        $profile = UserProfile::factory()->create();
        $term = ProgramTerm::factory()->create();
        $host = HostEstablishment::factory()->create();
        $profile->user->programTerms()->attach($term);
        $profile->user->hostEstablishments()->attach($host);

        $this->assertTrue($profile->user->profile->is($profile));
        $this->assertTrue($term->staff->contains($profile->user));
        $this->assertTrue($host->supervisors->contains($profile->user));
    }

    public function test_reassignment_preserves_previous_placement_and_monitoring_history(): void
    {
        $previous = Placement::factory()->create();
        $log = TimeLog::factory()->for($previous)->create();
        $previous->update(['status' => PlacementStatus::Cancelled]);

        $current = Placement::factory()->create(['student_enrollment_id' => $previous->student_enrollment_id]);

        $this->assertCount(2, $current->studentEnrollment->placements);
        $this->assertTrue($current->studentEnrollment->currentPlacement->is($current));
        $this->assertTrue($log->fresh()->placement->is($previous));
        $this->assertNotSame($previous->host_establishment_id, $current->host_establishment_id);
    }

    public function test_database_rejects_proficiency_above_one_hundred(): void
    {
        $competency = StudentCompetency::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('student_competencies')->where('id', $competency->id)->update(['level' => 101]);
    }

    public function test_verified_hours_require_a_verifier_and_certification_timestamp(): void
    {
        $log = TimeLog::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('time_logs')->where('id', $log->id)->update(['status' => 'verified', 'credited_minutes' => 480]);
    }

    public function test_local_work_date_can_differ_from_the_utc_clock_in_date(): void
    {
        $log = TimeLog::factory()->create([
            'work_date' => '2026-08-04',
            'time_in' => '2026-08-03 23:00:00',
            'time_out' => '2026-08-04 08:00:00',
        ]);

        $this->assertSame('2026-08-04', $log->fresh()->work_date->format('Y-m-d'));
        $this->assertSame('2026-08-03 23:00:00', $log->fresh()->time_in->format('Y-m-d H:i:s'));
    }

    public function test_database_rejects_unknown_placement_status(): void
    {
        $placement = Placement::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('placements')->where('id', $placement->id)->update(['status' => 'automatically_approved']);
    }

    public function test_reference_seeding_is_repeatable_without_overwriting_custom_names(): void
    {
        $this->seed(InternMatchReferenceSeeder::class);
        Competency::where('code', 'web-development')->update(['name' => 'Custom web curriculum']);

        $this->seed(InternMatchReferenceSeeder::class);

        $this->assertDatabaseCount('competencies', 6);
        $this->assertDatabaseCount('requirement_types', 5);
        $this->assertDatabaseHas('competencies', ['code' => 'web-development', 'name' => 'Custom web curriculum']);
    }

    public function test_domain_migrations_can_be_reversed_without_removing_starter_tables(): void
    {
        $this->assertSame('sqlite', DB::getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        User::factory()->create();

        $this->artisan('migrate:rollback', ['--step' => 10, '--force' => true])->assertExitCode(0);

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertFalse(Schema::hasColumn('users', 'role'));
        $this->assertFalse(Schema::hasTable('placements'));
        $this->assertDatabaseCount('users', 1);
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
    }
}
