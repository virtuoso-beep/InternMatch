<?php

namespace App\Http\Controllers;

use App\AccountStatus;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\SessionUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function csrf(Request $request): JsonResponse
    {
        return response()->json(['token' => $request->session()->token()])->header('Cache-Control', 'no-store');
    }

    public function login(LoginRequest $request): SessionUserResource
    {
        $credentials = $request->safe()->only(['email', 'password']);
        $credentials['status'] = AccountStatus::Active->value;
        $credentials[] = fn ($query) => $query->whereNotNull('role');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            Log::notice('auth.login_failed', ['ip' => $request->ip()]);
            throw ValidationException::withMessages(['email' => 'These credentials do not match an active account.']);
        }

        $request->session()->regenerate();
        Log::info('auth.login', ['user_id' => $request->user()->id]);

        return new SessionUserResource($request->user());
    }

    public function show(Request $request): SessionUserResource
    {
        return new SessionUserResource($request->user());
    }

    public function logout(Request $request): Response
    {
        $userId = $request->user()?->id;
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Log::info('auth.logout', ['user_id' => $userId]);

        return response()->noContent();
    }
}
