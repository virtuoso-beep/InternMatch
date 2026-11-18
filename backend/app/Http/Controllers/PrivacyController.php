<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthenticatedUserResource;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Spec §17B privacy notice and acknowledgement. */
class PrivacyController extends Controller
{
    public function notice()
    {
        return response()->json(['data' => config('privacy')], headers: ['Cache-Control' => 'no-store']);
    }

    public function acknowledge(Request $request): AuthenticatedUserResource
    {
        self::record($request->user());

        return new AuthenticatedUserResource($request->user()->fresh());
    }

    /** Management panel (Filament) sign-in path: same notice, same consent record. */
    public function managementNotice()
    {
        return response()->view('admin.privacy-notice', ['notice' => config('privacy')], headers: ['Cache-Control' => 'no-store']);
    }

    public function managementAcknowledge(Request $request)
    {
        $request->validate(['acknowledged' => ['accepted']]);
        self::record($request->user());

        return redirect()->intended('/manage');
    }

    public static function record(User $user): void
    {
        $version = config('privacy.version');
        DB::transaction(function () use ($user, $version) {
            $created = DB::table('privacy_consents')->insertOrIgnore([
                'user_id' => $user->id, 'notice_version' => $version,
                'acknowledged_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($created) {
                Audit::record($user, 'privacy.notice_acknowledged', $user, ['notice_version' => $version]);
            }
        });
    }
}
