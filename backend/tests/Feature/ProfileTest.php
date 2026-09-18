<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_read_or_edit_a_profile(): void
    {
        $this->getJson('/api/v1/profile')->assertUnauthorized();
        $this->patchJson('/api/v1/profile', ['name' => 'Attempt'])->assertUnauthorized();
    }

    public function test_read_returns_only_own_identity_and_does_not_create_a_profile(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        UserProfile::factory()->create(['address' => 'Another user private address']);

        $this->actingAs($user)->getJson('/api/v1/profile')->assertOk()
            ->assertJsonPath('data.id', $user->id)->assertJsonPath('data.address', null)
            ->assertJsonPath('data.enrollments', [])->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.avatar_path')->assertDontSee('Another user private address');

        $this->assertDatabaseMissing('user_profiles', ['user_id' => $user->id]);
    }

    public function test_saved_profile_survives_a_subsequent_read_and_does_not_change_another_user(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        $other = UserProfile::factory()->create(['bio' => 'Unchanged']);

        $this->actingAs($user)->patchJson('/api/v1/profile', [
            'name' => 'Updated Student', 'contact_number' => '09123456789', 'address' => 'Tagum',
            'bio' => 'Web development', 'latitude' => 0, 'longitude' => 0,
            'notify_email' => false, 'notify_digest' => true,
        ])->assertOk()->assertJsonPath('data.name', 'Updated Student')->assertJsonPath('data.latitude', '0.0000000');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Student']);
        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->id, 'address' => 'Tagum', 'notify_email' => false]);
        $this->assertSame('Unchanged', $other->fresh()->bio);
        $this->getJson('/api/v1/profile')->assertOk()->assertJsonPath('data.bio', 'Web development');
    }

    public function test_repeated_updates_keep_one_profile_record(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        $this->actingAs($user)->patchJson('/api/v1/profile', ['bio' => 'First'])->assertOk();

        $this->patchJson('/api/v1/profile', ['bio' => 'Second'])->assertOk()->assertJsonPath('data.bio', 'Second');

        $this->assertSame(1, $user->profile()->count());
    }

    public function test_profile_cannot_change_ownership_access_or_academic_records(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        $other = User::factory()->create();

        $this->actingAs($user)->patchJson('/api/v1/profile', [
            'user_id' => $other->id, 'role' => 'admin', 'status' => 'active', 'email' => 'changed@example.test',
            'program_term_id' => 1, 'required_minutes' => 1, 'student_number' => 'changed', 'name' => 'Do not save',
        ])->assertUnprocessable()->assertJsonValidationErrors(['user_id', 'role', 'status', 'email', 'program_term_id', 'required_minutes', 'student_number']);

        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertSame(Role::Student, $user->fresh()->role);
        $this->assertDatabaseMissing('user_profiles', ['user_id' => $user->id]);
    }

    public static function invalidCoordinates(): array
    {
        return [
            'latitude too high' => [['latitude' => 91, 'longitude' => 0], 'latitude'],
            'longitude too low' => [['latitude' => 0, 'longitude' => -181], 'longitude'],
            'missing counterpart' => [['latitude' => 7], 'latitude'],
            'partial clear' => [['longitude' => null], 'latitude'],
            'one null' => [['latitude' => null, 'longitude' => 0], 'latitude'],
            'not numeric' => [['latitude' => 'NaN', 'longitude' => 0], 'latitude'],
        ];
    }

    #[DataProvider('invalidCoordinates')]
    public function test_invalid_coordinates_do_not_partially_save_other_fields(array $payload, string $field): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        $profile = UserProfile::factory()->for($user)->create(['latitude' => 7, 'longitude' => 125]);

        $this->actingAs($user)->patchJson('/api/v1/profile', [...$payload, 'name' => 'Do not save'])
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertSame('7.0000000', $profile->fresh()->latitude);
        $this->assertSame('125.0000000', $profile->fresh()->longitude);
    }

    public function test_coordinates_can_be_cleared_together(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        UserProfile::factory()->for($user)->create(['latitude' => 7, 'longitude' => 125]);

        $this->actingAs($user)->patchJson('/api/v1/profile', ['latitude' => null, 'longitude' => null])
            ->assertOk()->assertJsonPath('data.latitude', null)->assertJsonPath('data.longitude', null);

        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->id, 'latitude' => null, 'longitude' => null]);
    }

    public function test_inactive_account_cannot_update_profile(): void
    {
        $user = User::factory()->withRole(Role::Student)->create(['status' => AccountStatus::Disabled]);

        $this->actingAs($user)->patchJson('/api/v1/profile', ['name' => 'Attempt'])->assertForbidden();

        $this->assertSame($user->name, $user->fresh()->name);
    }

    public function test_profile_includes_only_own_academic_enrollment(): void
    {
        $enrollment = StudentEnrollment::factory()->create();
        $other = StudentEnrollment::factory()->create();

        $this->actingAs($enrollment->student->user)->getJson('/api/v1/profile')->assertOk()
            ->assertJsonCount(1, 'data.enrollments')->assertJsonPath('data.enrollments.0.id', $enrollment->id)
            ->assertJsonMissing(['id' => $other->id, 'program' => $other->programTerm->program->name]);
    }

    public function test_long_bio_is_rejected(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();

        $this->actingAs($user)->patchJson('/api/v1/profile', ['bio' => str_repeat('x', 2001)])
            ->assertUnprocessable()->assertJsonValidationErrors('bio');

        $this->assertDatabaseMissing('user_profiles', ['user_id' => $user->id]);
    }
}
