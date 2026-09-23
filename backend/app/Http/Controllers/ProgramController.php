<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Program;
use App\Services\ProgramSettings;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Program::query()->withCount('competencies')->orderBy('code');
        if (in_array($user->role, [Role::Coordinator, Role::Dean], true)) {
            $query->whereIn('id', $user->programs()->select('programs.id'));
        } elseif (! $user->hasPermission(Permission::ManageAcademicRecords)) {
            // Only public academic reference fields are returned to students/supervisors.
            $query->where('is_active', true);
        }

        return response()->json(['data' => $query->get()], headers: ['Cache-Control' => 'no-store']);
    }

    public function update(Request $request, Program $program)
    {
        $program = ProgramSettings::update($request->user(), $program, $request->all());

        return response()->json(['data' => $program->fresh()->loadCount('competencies')], headers: ['Cache-Control' => 'no-store']);
    }
}
