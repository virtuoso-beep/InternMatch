<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BackupStatusTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_admin_can_read_backup_metadata(): void
    {
        foreach ([Role::Student, Role::Supervisor, Role::Coordinator, Role::Dean] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create(), 'web')
                ->getJson('/api/v1/backup-status')->assertForbidden();
        }
        $this->actingAs(User::factory()->withRole(Role::Admin)->create(), 'web')
            ->getJson('/api/v1/backup-status')->assertOk()
            ->assertJsonStructure(['data', 'total', 'schedule']);
    }
}
