<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\Role;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfilePhotoNotificationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_photo_upload_is_validated_private_and_preserves_account_fields(): void
    {
        Storage::fake('local');
        $user = User::factory()->withRole(Role::Student)->create();
        $other = User::factory()->withRole(Role::Student)->create();
        $this->actingAs($user)->postJson('/api/v1/profile/photo', ['photo' => UploadedFile::fake()->createWithContent('portrait.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a5X0AAAAASUVORK5CYII='))])->assertOk();
        $url = $this->getJson('/api/v1/profile')->assertOk()->json('data.avatar_url');
        $this->get($url)->assertOk();
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame($user->password, $user->fresh()->password);
        $this->postJson('/api/v1/profile/photo', ['photo' => UploadedFile::fake()->create('bad.svg', 1, 'image/svg+xml')])->assertUnprocessable();
        $this->postJson('/api/v1/profile/photo', ['photo' => UploadedFile::fake()->create('large.png', 2049, 'image/png')])->assertUnprocessable();
        $this->actingAs($other)->get($url)->assertNotFound();
    }

    public function test_notification_filters_and_read_all_cover_all_pages_only_for_the_owner(): void
    {
        $user = User::factory()->withRole(Role::Student)->create();
        $other = User::factory()->withRole(Role::Student)->create();
        foreach (range(1, 35) as $number) {
            $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'test', 'data' => ['title' => 'Test '.$number], 'read_at' => null]);
        }
        $foreign = $other->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'test', 'data' => ['title' => 'Private'], 'read_at' => null]);
        $this->actingAs($user)->getJson('/api/v1/notifications?status=unread&page=2')->assertOk()->assertJsonCount(5, 'data');
        $this->getJson('/api/v1/notifications?status=read')->assertOk()->assertJsonCount(0, 'data');
        $this->patchJson('/api/v1/notifications/read-all')->assertNoContent();
        $this->getJson('/api/v1/notifications?status=unread')->assertJsonCount(0, 'data')->assertJsonPath('unread_count', 0);
        $this->getJson('/api/v1/notifications?status=read&page=2')->assertJsonCount(5, 'data');
        $this->assertNull($foreign->fresh()->read_at);
        $this->patchJson('/api/v1/notifications/'.$foreign->id.'/read')->assertNotFound();
        $this->getJson('/api/v1/notifications?status=invalid')->assertUnprocessable();
    }
}

