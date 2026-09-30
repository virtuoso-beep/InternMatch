<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\{HostEstablishment, Placement, StudentEnrollment, User};
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MapTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_map_uses_saved_locations_and_does_not_expose_other_students_or_hosts(): void
    {
        $e = StudentEnrollment::factory()->create();
        $e->student->user->profile()->create(['latitude' => 7.44, 'longitude' => 125.8]);
        $host = HostEstablishment::factory()->create(['latitude' => 7.45, 'longitude' => 125.8]);
        $opportunity = \App\Models\Opportunity::factory()->create(['host_establishment_id' => $host->id, 'academic_term_id' => $e->programTerm->academic_term_id]);
        Placement::factory()->create(['student_enrollment_id' => $e->id, 'opportunity_id' => $opportunity->id, 'host_establishment_id' => $host->id, 'status' => 'approved']);
        HostEstablishment::factory()->create();
        $result = $this->actingAs($e->student->user)->getJson('/api/v1/map')->assertOk()->assertJsonCount(1, 'hosts')->assertJsonPath('student.latitude', 7.44)->assertJsonPath('hosts.0.id', $host->id)->assertJsonPath('distance_kind', 'straight_line');
        $this->assertEqualsWithDelta(1.11195, $result->json('hosts.0.distance_km'), 0.0001);
        $other = StudentEnrollment::factory()->create();
        $this->getJson('/api/v1/map?enrollment_id='.$other->id)->assertNotFound();
        $this->actingAs(User::factory()->withRole(Role::Dean)->create())->getJson('/api/v1/map?enrollment_id='.$e->id)->assertForbidden();
    }

    public function test_missing_coordinates_are_not_replaced_with_demo_locations(): void
    {
        $e = StudentEnrollment::factory()->create();
        $this->actingAs($e->student->user)->getJson('/api/v1/map')->assertOk()->assertJsonPath('student', null)->assertJsonCount(0, 'hosts');
    }
}
