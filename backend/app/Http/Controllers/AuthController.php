<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\SessionController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AuthenticatedUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends SessionController
{
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
