<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Services\AccountActivation;
use App\Services\StudentAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/** Spec §17A-B activation links: issued by staff, completed by the account holder (no sign-in needed). */
class AccountActivationController extends Controller
{
    /** Administrators may send a link to any account; coordinators only to students in their assigned programs. */
    public function issue(Request $request, User $user)
    {
        $actor = $request->user();
        $allowed = $actor->role === Role::Admin
            || ($actor->role === Role::Coordinator && $user->role === Role::Student
                && StudentAccess::enrollments($actor)->whereHas('student', fn ($query) => $query->where('user_id', $user->id))->exists());
        abort_unless($allowed, 403);
        AccountActivation::issue($actor, $user);

        return response()->json(['data' => ['sent_to' => $user->email, 'expires_in_hours' => config('internmatch.activation_hours')]], 202);
    }

    public function show(string $token)
    {
        abort_unless(AccountActivation::find($token), 410, 'This activation link is no longer valid. Ask your coordinator to send a new one.');

        return response()->json(['data' => ['valid' => true]], headers: ['Cache-Control' => 'no-store']);
    }

    public function complete(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        AccountActivation::complete($data['token'], $data['password']);

        return response()->json(['data' => ['activated' => true]]);
    }
}
