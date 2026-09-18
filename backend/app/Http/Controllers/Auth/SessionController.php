<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AuthenticatedUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SessionController extends Controller
{
    public function store(LoginRequest $request): AuthenticatedUserResource
    {
        $request->authenticate();
        $request->session()->regenerate();

        return new AuthenticatedUserResource(Auth::guard('web')->user());
    }

    public function show(Request $request): AuthenticatedUserResource
    {
        return new AuthenticatedUserResource($request->user());
    }

    public function destroy(Request $request): Response
    {
        $userId = $request->user()?->id;
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Log::info('auth.logout', ['user_id' => $userId]);

        return response()->noContent();
    }
}
