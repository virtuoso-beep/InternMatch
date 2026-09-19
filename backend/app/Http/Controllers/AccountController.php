<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission(Permission::ManageAccounts), 403);

        return response()->json(User::select('id', 'name', 'email', 'role', 'status')
            ->with(['programs:id,code', 'hostEstablishments:id,name'])->orderBy('name')->paginate(50), headers: ['Cache-Control' => 'no-store']);
    }

    public function assignHosts(Request $request, User $user)
    {
        Gate::authorize('updateAccess', $user);
        abort_unless($user->role === Role::Supervisor, 422, 'Host assignments require a supervisor account.');
        $data = $request->validate(['host_ids' => ['present', 'array', 'max:100'], 'host_ids.*' => ['required', 'integer', 'distinct', 'exists:host_establishments,id']]);
        DB::transaction(function () use ($request, $user, $data) {
            $before = $user->hostEstablishments()->pluck('host_establishments.id')->all();
            $user->hostEstablishments()->sync($data['host_ids']);
            Audit::record($request->user(), 'account.host_access_updated', $user, ['before' => $before, 'after' => $data['host_ids']]);
        });

        return response()->noContent();
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission(Permission::ManageAccounts), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'max:128', Password::min(12)],
            'role' => ['required', Rule::enum(Role::class)], 'status' => ['required', Rule::enum(AccountStatus::class)],
        ]);
        $user = DB::transaction(function () use ($request, $data) {
            $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
            $user->role = $data['role'];
            $user->status = $data['status'];
            $user->save();
            Audit::record($request->user(), 'account.created', $user, ['role' => $data['role'], 'status' => $data['status']]);

            return $user;
        });

        return response()->json(['data' => $user->only(['id', 'name', 'email', 'role', 'status'])], 201);
    }
}
