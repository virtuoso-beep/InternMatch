<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RecommendationBatchReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_coordinators_can_read_batch_reports(): void
    {
        foreach ([Role::Student, Role::Supervisor, Role::Dean] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create(), 'web')
                ->getJson('/api/v1/recommendation-batch-reports')->assertForbidden();
        }
        $this->actingAs(User::factory()->withRole(Role::Coordinator)->create(), 'web')
            ->getJson('/api/v1/recommendation-batch-reports')->assertOk()
            ->assertJsonStructure(['data', 'total', 'last_page']);
    }
}


