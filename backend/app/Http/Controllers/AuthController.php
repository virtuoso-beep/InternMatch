<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\SessionController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AuthenticatedUserResource;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends SessionController
{
    public function firstPassword(Request $request): AuthenticatedUserResource
    {
        abort_unless($request->user()->must_change_password, 409, 'Password setup is already complete.');
        $data = $request->validate(['current_password' => 'required|current_password:web',
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(12)->mixedCase()->numbers()->symbols()]]);
        $user = DB::transaction(function () use ($request, $data) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($user->must_change_password && Hash::check($data['current_password'], $user->password), 409, 'Password setup has changed. Sign in again.');
            $user->forceFill(['password' => $data['password'], 'must_change_password' => false, 'remember_token' => Str::random(60)])->save();
            Audit::record($user, 'account.initial_password_changed', $user, []);

            return $user;
        });
        $request->session()->regenerate();
        Auth::guard('web')->setUser($user);

        return new AuthenticatedUserResource($user);
    }

    /** Change password after first sign-in, for every role. First-time setup stays on firstPassword(). */
    public function changePassword(Request $request): AuthenticatedUserResource
    {
        abort_if($request->user()->must_change_password, 409, 'Complete the first password setup instead.');
        $data = $request->validate(['current_password' => 'required|current_password:web',
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(12)->mixedCase()->numbers()->symbols()]]);
        $user = DB::transaction(function () use ($request, $data) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless(Hash::check($data['current_password'], $user->password), 409, 'Your password has changed. Sign in again.');
            $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
            Audit::record($user, 'account.password_changed', $user, []);

            return $user;
        });
        $request->session()->regenerate();
        Auth::guard('web')->setUser($user);

        return new AuthenticatedUserResource($user);
    }

    public function csrf(Request $request): JsonResponse
    {
        return response()->json(['token' => $request->session()->token()])->header('Cache-Control', 'no-store');
    }

    public function login(LoginRequest $request): AuthenticatedUserResource
    {
        $request->authenticate(requireActiveRole: true);
        $request->session()->regenerate();

        return new AuthenticatedUserResource($request->user());
    }

    public function logout(Request $request): Response
    {
        return $this->destroy($request);
    }
}
