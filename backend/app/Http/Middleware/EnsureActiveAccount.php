<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->status !== AccountStatus::Active || $request->user()?->role === null) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json(['message' => 'Your account is not active.'], 403);
        }

        if ($request->user()->must_change_password && ! $request->is('api/v1/session', 'api/v1/first-password')) {
            return response()->json(['message' => 'Change your temporary password before accessing the portal.', 'code' => 'password_change_required'], 403);
        }
        return $next($request);
    }
}
