<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PrivacyConsentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_session_requires_acknowledgement_once_per_notice_version_and_it_is_audited(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        $this->actingAs($user)->getJson('/api/v1/session')->assertOk()->assertJsonPath('data.privacy_consent_required', true);
        $this->getJson('/api/v1/privacy-notice')->assertOk()->assertJsonPath('data.version', config('privacy.version'))
            ->assertJsonFragment(['heading' => 'Your rights and how to contact us']);
        $this->postJson('/api/v1/privacy-consent')->assertOk()->assertJsonPath('data.privacy_consent_required', false);
        $this->postJson('/api/v1/privacy-consent')->assertOk(); // idempotent
        $this->assertDatabaseCount('privacy_consents', 1);
        $this->assertSame(1, \DB::table('audit_logs')->where('action', 'privacy.notice_acknowledged')->count());
        $this->getJson('/api/v1/session')->assertJsonPath('data.privacy_consent_required', false);

        config(['privacy.version' => 'v2-test']); // a new notice version asks again
        $this->getJson('/api/v1/session')->assertJsonPath('data.privacy_consent_required', true);
    }

    public function test_guests_cannot_acknowledge(): void
    {
        $this->postJson('/api/v1/privacy-consent')->assertUnauthorized();
    }

    public function test_retention_report_is_report_only_and_deletes_nothing(): void
    {
        $user = User::factory()->withRole(Role::Admin)->create();
        \DB::table('audit_logs')->insert(['actor_id' => $user->id, 'action' => 'synthetic.old', 'subject_type' => 'user', 'subject_id' => $user->id, 'changes' => '[]', 'created_at' => now()->subYears(4)]);
        $this->artisan('internmatch:retention-report')->expectsOutputToContain('REPORT-ONLY')->assertSuccessful();
        $this->assertDatabaseHas('audit_logs', ['action' => 'synthetic.old']);
    }
}
