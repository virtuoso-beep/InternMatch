<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Program;
use App\Models\StudentEnrollment;
use App\Models\User;
use Database\Seeders\SyntheticDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SyntheticDemoTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seed_is_repeatable_preserves_edits_and_provides_complete_downloadable_fixtures(): void
    {
        Storage::fake('local');
        foreach (['student', 'coordinator', 'supervisor', 'dean', 'admin'] as $role) {
            User::factory()->create(['email' => $role.'@example.com', 'role' => $role, 'status' => 'active']);
        }
        $originalAccounts = User::orderBy('id')->get()->map->getAttributes()->all();
        $this->seed(SyntheticDemoSeeder::class);
        $this->assertSame($originalAccounts, User::orderBy('id')->get()->map->getAttributes()->all());
        $user = User::where('email', 'student@example.com')->firstOrFail();
        $user->update(['name' => 'Retain my edited name', 'password' => 'retained-password']);
        $counts = collect(['users', 'student_enrollments', 'placements', 'documents', 'time_logs', 'notifications'])->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()]);
        $this->seed(SyntheticDemoSeeder::class);
        foreach ($counts as $table => $count) {
            $this->assertSame($count, DB::table($table)->count(), $table);
        }
        $this->assertSame('Retain my edited name', $user->fresh()->name);
        $this->assertTrue(Hash::check('retained-password', $user->fresh()->password));
        $this->assertSame(1, StudentEnrollment::count());
        $this->assertDatabaseCount('program_term_requirements', 14);
        $this->assertDatabaseCount('placements', 1);
        $this->assertDatabaseCount('evaluations', 1);
        $this->assertDatabaseCount('time_logs', 5);
        $this->assertDatabaseCount('generated_reports', 7);
        $this->assertNull(Program::where('code', 'BSCS')->first()->required_ojt_hours);
        $this->assertNull(Program::where('code', 'BSIT')->first()->evaluation_rubric_id);
        $this->assertSame(0, DB::table('program_term_requirements')->whereNotNull('scheduled_on')->count());
        foreach (Document::all() as $document) {
            $contents = Storage::disk('local')->get($document->path);
            $this->assertStringStartsWith('%PDF-', $contents);
            $this->assertSame($document->sha256, hash('sha256', $contents));
        }
        $this->actingAs($user)->getJson('/api/v1/dashboard')->assertOk();
    }

    public function test_missing_original_accounts_never_creates_replacement_accounts(): void
    {
        try {
            $this->seed(SyntheticDemoSeeder::class);
            $this->fail('Missing original accounts must fail.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('Expected active original account', $error->getMessage());
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('student_enrollments', 0);
    }

    public function test_production_environment_rejects_demo_seed_before_writing(): void
    {
        $this->app->instance('env', 'production');
        $this->artisan('internmatch:seed-demo')->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }
}
