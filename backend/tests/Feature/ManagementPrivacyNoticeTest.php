<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/** Spec §17B / NFR-04: the administrator's Filament sign-in path also requires the privacy notice. */
class ManagementPrivacyNoticeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_panel_is_blocked_until_the_current_notice_is_acknowledged(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $this->actingAs($admin)->get('/manage')->assertRedirect(route('manage.privacy'));
        $this->get(route('manage.privacy'))->assertOk()->assertSee(config('privacy.title'))
            ->assertSee('CONTACT TO BE PROVIDED BY UM TAGUM COLLEGE');
        $this->post(route('manage.privacy.acknowledge'))->assertSessionHasErrors('acknowledged');
        $this->assertDatabaseCount('privacy_consents', 0);

        $this->post(route('manage.privacy.acknowledge'), ['acknowledged' => '1'])->assertRedirect('/manage');
        $this->assertDatabaseHas('privacy_consents', ['user_id' => $admin->id, 'notice_version' => config('privacy.version')]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'privacy.notice_acknowledged']);
        $this->get('/manage')->assertOk();
        $this->post(route('manage.privacy.acknowledge'), ['acknowledged' => '1']); // idempotent
        $this->assertDatabaseCount('privacy_consents', 1);
    }

    public function test_a_new_notice_version_requires_acknowledging_again(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $this->actingAs($admin)->post(route('manage.privacy.acknowledge'), ['acknowledged' => '1']);
        config(['privacy.version' => 'v2-test']);
        $this->get('/manage')->assertRedirect(route('manage.privacy'));
    }

    public function test_guests_are_sent_to_the_panel_sign_in(): void
    {
        $this->get(route('manage.privacy'))->assertRedirect('/manage/login');
    }
}
