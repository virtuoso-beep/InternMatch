<?php

namespace Tests\Feature;

use App\Console\Commands\ConfigureSpecRubrics;
use App\Enums\Role;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\Program;
use App\Models\User;
use App\Services\EvaluationScoring;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SpecRubricTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_command_configures_spec_rubric_once_per_program_and_is_audited(): void
    {
        User::factory()->withRole(Role::Admin)->create();
        foreach (['BSIT', 'BSA', 'BSCRIM'] as $code) {
            Program::factory()->create(['code' => $code]);
        }
        $this->artisan('internmatch:configure-spec-rubrics')->assertSuccessful();
        $this->artisan('internmatch:configure-spec-rubrics')->assertSuccessful(); // idempotent
        foreach (['BSIT', 'BSA', 'BSCRIM'] as $code) {
            $rubric = Program::where('code', $code)->sole()->evaluationRubric;
            $this->assertSame(ConfigureSpecRubrics::NAME, $rubric->name);
            $this->assertSame(1, $rubric->version);
            $this->assertCount(14, $rubric->criteria);
        }
        $this->assertDatabaseCount('evaluation_rubrics', 3);
        $this->assertDatabaseHas('audit_logs', ['action' => 'evaluation_rubric.configured']);
    }

    public function test_weighted_score_equals_the_spec_area_weighted_average(): void
    {
        User::factory()->withRole(Role::Admin)->create();
        $program = Program::factory()->create(['code' => 'BSA']);
        $this->artisan('internmatch:configure-spec-rubrics')->assertSuccessful();
        $rubric = $program->fresh()->evaluationRubric;
        $evaluation = Evaluation::factory()->create(['evaluation_rubric_id' => $rubric->id]);
        // Career all 4, general all 3, core values all 5: 0.5×4 + 0.3×3 + 0.2×5 = 3.9 of 5 = 78%.
        foreach ($rubric->criteria as $criterion) {
            $score = str_starts_with($criterion->name, 'Career') ? 4 : (str_starts_with($criterion->name, 'General') ? 3 : 5);
            EvaluationScore::factory()->create(['evaluation_id' => $evaluation->id, 'evaluation_rubric_id' => $rubric->id, 'evaluation_criterion_id' => $criterion->id, 'score' => $score]);
        }
        $this->assertEqualsWithDelta(78.0, app(EvaluationScoring::class)->percentage($evaluation->fresh()), 0.0001);
    }
}
