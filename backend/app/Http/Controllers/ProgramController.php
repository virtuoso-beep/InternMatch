<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Program;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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
        Gate::authorize('update', $program);
        $data = $request->validate([
            'required_ojt_hours' => ['present', 'nullable', 'integer', 'min:1', 'max:10000'],
            'internship_term' => ['present', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        DB::transaction(function () use ($program, $data, $request) {
            $before = $program->only(array_keys($data));
            $program->update($data);
            Audit::record($request->user(), 'program.updated', $program, ['before' => $before, 'after' => $data], $program->id);
        });

        return response()->json(['data' => $program->fresh()->loadCount('competencies')], headers: ['Cache-Control' => 'no-store']);
    }
}
