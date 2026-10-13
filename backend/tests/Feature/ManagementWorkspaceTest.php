<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ManagementWorkspaceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_administrators_can_access_the_panel_and_embedded_workspaces(): void
    {
        $this->get('/manage/admin-workspace/dashboard')->assertRedirect('/manage/login');
        $this->actingAs(User::factory()->withRole(Role::Admin)->create());
        $this->get('/manage')->assertOk()->assertSee('/manage/admin-workspace/dashboard', false);
        $this->get('/manage/admin-workspace/dashboard')->assertOk();
        $this->get('/manage/admin-workspace/invalid')->assertNotFound();
        $this->get('/manage/administration?section=users')->assertOk();
        foreach ([Role::Coordinator, Role::Student, Role::Supervisor, Role::Dean] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create());
            $this->get('/manage')->assertForbidden();
            $this->get('/manage/administration?section=users')->assertForbidden();
            $this->get('/manage/admin-workspace/users')->assertForbidden();
        }
    }

    public function test_legacy_admin_links_redirect_to_the_canonical_panel(): void
    {
        $this->get('/admin')->assertRedirect('/manage');
        $this->get('/admin/users')->assertRedirect('/manage/administration?section=users');
    }
}
