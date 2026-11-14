<?php
namespace Tests\Feature;

use App\Enums\Role;
use App\Models\{AcademicTerm, Program, User};
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminReferenceWorkspaceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_settings_and_term_changes_persist_are_audited_and_reject_non_admins(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();
        $term = AcademicTerm::factory()->create();
        $this->actingAs($admin);
        $this->getJson('/api/v1/admin/overview')->assertOk()->assertJsonPath('total_users', 1);
        $settings = ['system_name'=>'Synthetic UI test', 'institution'=>'Synthetic Institution', 'display_timezone'=>'UTC'];
        $this->putJson('/api/v1/admin/settings', $settings)->assertOk()->assertJsonPath('data.system_name', 'Synthetic UI test');
        $this->getJson('/api/v1/branding')->assertOk()->assertJsonPath('data.display_timezone', 'UTC');
        $this->putJson('/api/v1/admin/settings', array_replace($settings, ['display_timezone'=>'invalid']))->assertUnprocessable();
        $this->patchJson('/api/v1/admin/terms/'.$term->id, ['is_active'=>false])->assertOk();
        $this->assertDatabaseHas('academic_terms', ['id'=>$term->id, 'is_active'=>false]);
        $this->assertDatabaseHas('audit_logs', ['action'=>'system.preferences_updated']);
        $this->assertDatabaseHas('audit_logs', ['action'=>'academic_term.availability_updated']);
        foreach ([Role::Student, Role::Coordinator, Role::Dean, Role::Supervisor] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create());
            $this->getJson('/api/v1/admin/overview')->assertForbidden();
            $this->putJson('/api/v1/admin/settings', $settings)->assertForbidden();
            $this->patchJson('/api/v1/admin/terms/'.$term->id, ['is_active'=>true])->assertForbidden();
            $this->getJson('/api/v1/branding')->assertOk();
        }
    }

    public function test_account_search_and_role_filter_run_before_pagination(): void
    {
        $this->actingAs(User::factory()->withRole(Role::Admin)->create());
        User::factory()->count(51)->withRole(Role::Student)->create(['name'=>'AAA Student']);
        $target=User::factory()->withRole(Role::Coordinator)->create(['name'=>'ZZZ Target Coordinator']);
        $this->getJson('/api/v1/users?search=ZZZ&role=coordinator&status=active')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $target->id);
        $this->getJson('/api/v1/users?role=student')->assertOk()->assertJsonPath('total', 51);
    }

    public function test_admin_can_preview_and_confirm_student_import_without_program_assignment(): void
    {
        $actor=User::factory()->withRole(Role::Admin)->create();
        $program=Program::factory()->create(['code'=>'BSIT','required_ojt_hours'=>486]);
        $term=AcademicTerm::factory()->create();
        $this->actingAs($actor);
        $this->getJson('/api/v1/coordinator-imports/reference')->assertOk();
        $id=(string) \Illuminate\Support\Str::uuid();
        DB::table('excel_imports')->insert(['id'=>$id,'user_id'=>$actor->id,'module'=>'student','filename'=>'synthetic.xlsx','workbook'=>'[]','created_at'=>now(),'updated_at'=>now()]);
        $body=['program_id'=>$program->id,'academic_term_id'=>$term->id,'rows'=>[['kind'=>'student','student_id'=>'991234','first_name'=>'Synthetic','last_name'=>'Admin Import','program'=>'BSIT','year_level'=>'4']]];
        $forbidden=$body;
        $forbidden['rows']=[['kind'=>'host','host_name'=>'Synthetic Forbidden']];
        $this->postJson("/api/v1/coordinator-imports/$id/preview",$forbidden)->assertUnprocessable()->assertJsonPath('message', 'Record type must match the module where you uploaded this workbook.');
        $preview=$this->postJson("/api/v1/coordinator-imports/$id/preview",$body)->assertOk()->assertJsonPath('rows.0.errors',[])->json();
        $this->postJson("/api/v1/coordinator-imports/$id/confirm",['revision'=>$preview['revision']])->assertOk();
        $this->assertDatabaseHas('students',['student_number'=>'991234']);
        $this->assertDatabaseCount('placements',0);

    }
}
