<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

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
            $affectedPrograms = array_unique([...$data['program_ids'], ...$user->programs()->pluck('programs.id')->all()]);
            Program::whereIn('id', $affectedPrograms)->orderBy('id')->lockForUpdate()->get();
            if ($user->role === Role::Coordinator && User::where('role', 'coordinator')->whereKeyNot($user->id)->whereHas('programs', fn ($q) => $q->whereIn('programs.id', $data['program_ids']))->exists()) {
                throw ValidationException::withMessages(['program_ids' => 'Each program may have only one coordinator. Remove the former assignment before assigning its replacement.']);
            }
            $before = $user->programs()->pluck('programs.id')->all();
            $user->programs()->sync($data['program_ids']);
            Audit::record($request->user(), 'account.program_access_updated', $user, ['before' => $before, 'after' => $data['program_ids']]);
        });

        return response()->json(['data' => ['user_id' => $user->id, 'program_ids' => $user->programs()->pluck('programs.id')]]);
    }
}
