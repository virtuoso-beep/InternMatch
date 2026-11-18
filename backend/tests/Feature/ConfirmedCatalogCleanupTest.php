<?php

namespace Tests\Feature;

use App\Models\Competency;
use App\Models\Program;
use App\Models\ProgramTermRequirement;
use App\Models\RequirementType;
use App\Models\StudentCompetency;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ConfirmedCatalogCleanupTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_cleanup_preserves_accounts_referenced_requirements_and_competency_evidence(): void
    {
        $this->seed([DatabaseSeeder::class, \Database\Seeders\ProgramSeeder::class]);
        $bsit = Program::where('code', 'BSIT')->firstOrFail();
        $account = User::where('email', 'student@example.com')->firstOrFail(); // created once by DatabaseSeeder
        $password = $account->password;
        $unused = RequirementType::factory()->create(['code' => 'medical-clearance']);
        $used = RequirementType::factory()->create(['code' => 'parental-consent']);
        ProgramTermRequirement::factory()->create(['requirement_type_id' => $used->id]);
        $extra = Competency::factory()->create(['code' => 'legacy-synthetic-vocabulary']);
        $bsit->competencies()->attach($extra);
        StudentCompetency::factory()->create(['competency_id' => $extra->id]);
        $otherCount = Program::where('code', 'BSCS')->firstOrFail()->competencies()->count();
        $this->artisan('internmatch:reconcile-bsit')->assertSuccessful();
        $this->assertModelExists($unused);
        $this->artisan('internmatch:reconcile-bsit --apply')->assertSuccessful();
        $this->artisan('internmatch:reconcile-bsit --apply')->assertSuccessful();
        $this->assertModelMissing($unused);
        $this->assertModelExists($used);
        $this->assertModelExists($extra);
        $this->assertSame(4, $bsit->competencies()->count());
        $this->assertSame($otherCount, Program::where('code', 'BSCS')->firstOrFail()->competencies()->count());
        $this->assertSame($password, $account->fresh()->password);
        $this->assertDatabaseCount('users', 5); // four demo accounts + the student created by the competency factory
        $this->assertDatabaseCount('programs', 5); // 4 seeded (BSA, BSCS inactive, BSIT, BSCRIM) + 1 from the factory
    }
}
