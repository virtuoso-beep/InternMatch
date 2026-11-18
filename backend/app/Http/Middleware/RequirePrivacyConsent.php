<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/** Spec §17B / NFR-04: the management panel is unavailable until the current privacy notice is acknowledged. */
class RequirePrivacyConsent
{
    public static function acknowledged(?int $userId): bool
    {
        return $userId !== null && DB::table('privacy_consents')->where('user_id', $userId)
            ->where('notice_version', config('privacy.version'))->exists();
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! self::acknowledged($user->id)) {
            abort_if($request->expectsJson(), 403, 'Acknowledge the privacy notice before continuing.');
            session()->put('url.intended', $request->fullUrl());

            return redirect()->route('manage.privacy');
        }

        return $next($request);
    }
}
