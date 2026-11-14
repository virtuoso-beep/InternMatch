<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Program;
use App\Models\User;
use App\Services\DepartmentRoster;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DepartmentRosterTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_accountancy_import_is_idempotent_and_hours_are_program_specific(): void
    {
        DepartmentRoster::apply();
        Program::where('code', 'BSIT')->update(['required_ojt_hours' => 486]);
        $source = ['source' => 'test.xlsx', 'sha256' => str_repeat('a', 64), 'sheets' => [
            'Students Records' => [['row' => 2, 'values' => [123, 'BS Accountancy', 'Accountancy', 4, 'Eligible', 'Completed', 'August 2025 - December 2025', 400]]],
            'HTE Records' => [], 'Internship Requirements' => [],
        ]];
        $path = tempnam(sys_get_temp_dir(), 'dae-test');
        file_put_contents($path, json_encode($source));
        try {
            $this->artisan('internmatch:import-accountancy', ['json' => $path, '--apply' => true])->assertSuccessful();
            $this->artisan('internmatch:import-accountancy', ['json' => $path, '--apply' => true])->assertSuccessful();
            $this->assertDatabaseCount('coordinator_source_rows', 1);
            $this->assertSame(400, Program::where('code', 'BSA')->sole()->required_ojt_hours);
            $this->assertSame(486, Program::where('code', 'BSIT')->sole()->required_ojt_hours);
            $this->assertNull(Program::where('code', 'BSCS')->sole()->required_ojt_hours);
            $this->assertNull(Program::where('code', 'BSCRIM')->sole()->required_ojt_hours);
            $this->assertSame(0, User::where('role', Role::Student)->count());
        } finally {
            unlink($path);
        }
    }

    public function test_roster_preserves_passwords_hours_and_retired_history(): void
    {
        $richard = User::factory()->withRole(Role::Coordinator)->create(['email' => 'r.misa@example.com']);
        $password = $richard->password;
        $old = User::factory()->withRole(Role::Coordinator)->create();
        $excluded = Program::factory()->create(['code' => 'BSMA']);
        $bsit = Program::factory()->create(['code' => 'BSIT', 'required_ojt_hours' => 486]);
        $old->programs()->attach($excluded);
        $created = DepartmentRoster::apply();
        $this->assertCount(4, $created);
        $this->assertSame([], DepartmentRoster::apply());
        $this->assertSame(5, User::where('role', Role::Coordinator)->count());
        $this->assertSame(4, Program::where('is_active', true)->count());
        $this->assertFalse($excluded->fresh()->is_active);
        $this->assertTrue(User::withTrashed()->find($old->id)->trashed());
        $this->assertSame($password, $richard->fresh()->password);
        $this->assertSame('r.misa@umindanao.edu.ph', $richard->fresh()->email);
        $this->assertSame(486, $bsit->fresh()->required_ojt_hours);
        $this->assertSame(2, $bsit->coordinators()->count());
        $this->assertNull(Program::where('code', 'BSCS')->sole()->required_ojt_hours);
        $this->assertNull(Program::where('code', 'BSCRIM')->sole()->required_ojt_hours);
        $admin = User::factory()->withRole(Role::Admin)->create();
        $this->actingAs($admin)->getJson('/api/v1/programs')->assertOk()->assertJsonCount(4, 'data');
    }
}
