<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Notifications\AccountActivationLink;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountActivationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function issue(User $actor, User $user): string
    {
        Notification::fake();
        $this->actingAs($actor)->postJson('/api/v1/users/'.$user->id.'/activation')->assertStatus(202)->assertJsonPath('data.expires_in_hours', 72);
        $token = null;
        Notification::assertSentTo($user, AccountActivationLink::class, function ($notification) use ($user, &$token) {
            $url = $notification->toMail($user)->actionUrl;
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $token = $query['token'];

            return str_starts_with($url, config('internmatch.portal_url').'/activate?token=');
        });
        $this->app['auth']->forgetGuards(); // completing a link does not need a session

        return $token;
    }

    public function test_link_is_hashed_single_use_and_activates_a_pending_account(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $student = User::factory()->withRole(Role::Student)->create(['status' => AccountStatus::Pending]);
        $student->forceFill(['email_verified_at' => null])->save();
        $token = $this->issue($admin, $student);
        $this->assertSame(64, strlen($token));
        $this->assertDatabaseMissing('account_activations', ['token_hash' => $token]); // raw token never stored
        $this->assertDatabaseHas('account_activations', ['user_id' => $student->id, 'token_hash' => hash('sha256', $token)]);

        $this->getJson('/api/v1/activation/'.$token)->assertOk();
        $this->postJson('/api/v1/activation', ['token' => $token, 'password' => 'short', 'password_confirmation' => 'short'])->assertUnprocessable();
        $password = 'Synthetic-Pass-2026!';
        $this->postJson('/api/v1/activation', ['token' => $token, 'password' => $password, 'password_confirmation' => $password])->assertOk();
        $this->assertSame(AccountStatus::Active, $student->fresh()->status);
        $this->assertTrue(\Hash::check($password, $student->fresh()->password));
        $this->assertNotNull($student->fresh()->email_verified_at); // the link proves the address receives mail
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.activated', 'subject_id' => $student->id]);

        // Single use.
        $this->getJson('/api/v1/activation/'.$token)->assertStatus(410);
        $this->postJson('/api/v1/activation', ['token' => $token, 'password' => $password, 'password_confirmation' => $password])->assertStatus(410);
    }

    public function test_reissuing_revokes_the_old_link_and_expired_links_are_refused(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $student = User::factory()->withRole(Role::Student)->create(['status' => AccountStatus::Pending]);
        $first = $this->issue($admin, $student);
        $second = $this->issue($admin, $student);
        $this->getJson('/api/v1/activation/'.$first)->assertStatus(410);
        $this->getJson('/api/v1/activation/'.$second)->assertOk();
        $this->travel(72)->hours();
        $this->travel(1)->minutes();
        $this->getJson('/api/v1/activation/'.$second)->assertStatus(410);
    }

    public function test_coordinators_only_reach_their_programs_students_and_disabled_accounts_are_refused(): void
    {
        $enrollment = StudentEnrollment::factory()->create();
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($enrollment->programTerm->program_id);
        $outsider = StudentEnrollment::factory()->create()->student->user;
        Notification::fake();
        $this->actingAs($coordinator)->postJson('/api/v1/users/'.$enrollment->student->user_id.'/activation')->assertStatus(202);
        $this->postJson('/api/v1/users/'.$outsider->id.'/activation')->assertForbidden();
        $this->actingAs(User::factory()->withRole(Role::Student)->create())->postJson('/api/v1/users/'.$outsider->id.'/activation')->assertForbidden();
        $disabled = User::factory()->withRole(Role::Student)->create(['status' => AccountStatus::Disabled]);
        $this->actingAs(User::factory()->withRole(Role::Admin)->create())->postJson('/api/v1/users/'.$disabled->id.'/activation')->assertUnprocessable();
        $this->assertSame(0, DB::table('account_activations')->where('user_id', $outsider->id)->count());
    }
}
