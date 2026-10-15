<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Placement;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function fixture(): array
    {
        $enrollment = StudentEnrollment::factory()->create();
        $student = $enrollment->student->user;
        $coordinator = User::factory()->withRole(Role::Coordinator)->create();
        $coordinator->programs()->attach($enrollment->programTerm->program_id);
        $supervisor = User::factory()->withRole(Role::Supervisor)->create();
        $placement = Placement::factory()->create(['student_enrollment_id' => $enrollment->id, 'supervisor_id' => $supervisor->id, 'status' => 'approved', 'starts_on' => today(), 'ends_on' => today()->addMonth()]);
        $supervisor->hostEstablishments()->attach($placement->host_establishment_id);
        $conversation = Conversation::withoutGlobalScope('participant')->where('placement_id', $placement->id)->firstOrFail();

        return [$enrollment, $student, $coordinator, $supervisor, $placement, $conversation];
    }

    public function test_scoped_enrollment_and_placement_conversations_are_created_automatically(): void
    {
        [$e, $s, $c, $h, $p, $conversation] = $this->fixture();
        $other = StudentEnrollment::factory()->create();
        $this->actingAs($s)->getJson('/api/v1/chat/conversations')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($c)->getJson('/api/v1/chat/conversations')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($h)->getJson('/api/v1/chat/conversations')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($other->student->user)->getJson('/api/v1/chat/conversations/'.$conversation->id)->assertNotFound();
        $outside = User::factory()->withRole(Role::Coordinator)->create();
        $outside->programs()->attach($other->programTerm->program_id);
        $this->actingAs($outside)->getJson('/api/v1/chat/conversations/'.$conversation->id)->assertNotFound();
        $this->assertSame(0, ChatMessage::count());
        foreach ([Role::Admin, Role::Dean] as $role) {
            $user = User::factory()->withRole($role)->create();
            $this->actingAs($user)->getJson('/api/v1/chat/conversations')->assertForbidden();
            $this->getJson('/api/v1/chat/conversations/'.$conversation->id)->assertNotFound();
            $this->assertSame(0, Conversation::count());
        }
    }

    public function test_messages_are_persistent_deduplicated_validated_and_notify_only_participants(): void
    {
        [$e, $s, $c, $h, $p, $conversation] = $this->fixture();
        $url = '/api/v1/chat/conversations/'.$conversation->id;
        $data = ['body' => 'When is the next check-in?', 'client_id' => (string) Str::uuid(), 'sender_id' => $c->id];
        $this->actingAs($s)->postJson($url.'/messages', $data)->assertOk()->assertJsonPath('messages.0.sender_id', $s->id);
        $this->postJson($url.'/messages', $data)->assertOk()->assertJsonCount(1, 'messages');
        $this->postJson($url.'/messages', ['body' => '   ', 'client_id' => (string) Str::uuid()])->assertUnprocessable();
        $this->postJson($url.'/messages', ['body' => str_repeat('a', 2001), 'client_id' => (string) Str::uuid()])->assertUnprocessable();
        $this->assertSame(1, $c->notifications()->count());
        $this->assertSame(1, $h->notifications()->count());
        $this->assertSame(0, $s->notifications()->count());
        $this->assertStringNotContainsString($data['body'], json_encode($c->notifications()->first()->data));
        $this->assertStringNotContainsString($data['body'], DB::table('audit_logs')->where('action', 'chat.message_sent')->value('changes'));
        $id = $this->actingAs($c)->getJson($url)->assertOk()->json('messages.0.id');
        $this->getJson('/api/v1/chat/conversations')->assertJsonPath('data.0.unread_count', 1);
        $this->postJson($url.'/read', ['last_message_id' => $id])->assertNoContent();
        $this->getJson('/api/v1/chat/conversations')->assertJsonPath('data.0.unread_count', 0);
        $this->postJson($url.'/read', ['last_message_id' => 999999])->assertUnprocessable();
    }

    public function test_completed_or_expired_placement_is_read_only_and_supervisor_loses_access(): void
    {
        [$e, $s, $c, $h, $p, $conversation] = $this->fixture();
        $url = '/api/v1/chat/conversations/'.$conversation->id;
        $this->actingAs($s)->postJson($url.'/messages', ['body' => 'Retained history', 'client_id' => (string) Str::uuid()])->assertOk();
        $p->update(['starts_on' => today()->subMonth(), 'ends_on' => today()->subDay()]);
        $this->actingAs($h)->getJson($url)->assertNotFound();
        $this->getJson('/api/v1/chat/conversations')->assertJsonCount(0, 'data');
        foreach ([$s, $c] as $user) {
            $this->actingAs($user)->getJson($url)->assertOk()->assertJsonPath('conversation.writable', false)->assertJsonPath('messages.0.body', 'Retained history');
            $this->postJson($url.'/messages', ['body' => 'No', 'client_id' => (string) Str::uuid()])->assertForbidden();
        }
        $p->update(['ends_on' => today()->addMonth(), 'status' => 'completed', 'completed_at' => now()]);
        $this->actingAs($h)->getJson($url)->assertNotFound();
        $this->actingAs($c)->getJson($url)->assertJsonPath('conversation.writable', false);
        $c->programs()->detach();
        $this->getJson($url)->assertNotFound();
    }

    public function test_admin_can_disable_chat_but_never_read_content_and_retired_accounts_cannot_authenticate(): void
    {
        [$e, $s, $c, $h, $p, $conversation] = $this->fixture();
        $this->actingAs($s)->putJson('/api/v1/chat/settings', ['enabled' => false])->assertForbidden();
        $admin = User::factory()->withRole(Role::Admin)->create();
        $this->actingAs($admin)->putJson('/api/v1/chat/settings', ['enabled' => false])->assertOk()->assertJsonPath('enabled', false);
        $this->getJson('/api/v1/chat/conversations/'.$conversation->id)->assertNotFound();
        $this->actingAs($s)->getJson('/api/v1/chat/conversations')->assertStatus(423);
        $this->actingAs($admin)->putJson('/api/v1/chat/settings', ['enabled' => true])->assertOk();
        $c->delete();
        $this->actingAs($s)->getJson('/api/v1/chat/conversations/'.$conversation->id)->assertJsonPath('conversation.writable', false);
        $this->actingAs($c)->getJson('/api/v1/chat/conversations/'.$conversation->id)->assertNotFound();
    }

    public function test_duplicate_coordinator_assignments_and_role_conversion_are_rejected(): void
    {
        [$e, $s, $c] = $this->fixture();
        $other = User::factory()->withRole(Role::Coordinator)->create();
        $admin = User::factory()->withRole(Role::Admin)->create();
        $this->actingAs($admin)->putJson('/api/v1/users/'.$other->id.'/programs', ['program_ids' => [$e->programTerm->program_id]])->assertUnprocessable();
        $dean = User::factory()->withRole(Role::Dean)->create();
        $this->putJson('/api/v1/users/'.$dean->id.'/programs', ['program_ids' => [$e->programTerm->program_id]])->assertOk();
        $this->patchJson('/api/v1/users/'.$dean->id.'/access', ['role' => 'coordinator'])->assertUnprocessable();
        $this->assertSame('dean', $dean->refresh()->role->value);
    }
}
