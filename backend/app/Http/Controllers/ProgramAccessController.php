<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProgramAccessController extends Controller
{
    public function __invoke(Request $request, User $user)
    {
        Gate::authorize('updateAccess', $user);
        abort_unless(in_array($user->role, [Role::Coordinator, Role::Dean], true), 422, 'Program access is for coordinators and program chairs/deans.');
        $data = $request->validate([
            'program_ids' => ['present', 'array', 'max:100'],
            'program_ids.*' => ['required', 'integer', 'distinct', 'exists:programs,id'],
        ]);
        DB::transaction(function () use ($request, $user, $data) {
            $before = $user->programs()->pluck('programs.id')->all();
            $user->programs()->sync($data['program_ids']);
            Audit::record($request->user(), 'account.program_access_updated', $user, ['before' => $before, 'after' => $data['program_ids']]);
        });

        return response()->json(['data' => ['user_id' => $user->id, 'program_ids' => $user->programs()->pluck('programs.id')]]);
    }
}
