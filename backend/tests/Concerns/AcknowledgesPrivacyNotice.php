<?php

namespace Tests\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

/**
 * For tests that exercise the management panel itself: the acting user has already acknowledged
 * the current privacy notice (the gate is tested separately in ManagementPrivacyNoticeTest).
 */
trait AcknowledgesPrivacyNotice
{
    public function actingAs(Authenticatable $user, $guard = null)
    {
        DB::table('privacy_consents')->insertOrIgnore(['user_id' => $user->getAuthIdentifier(), 'notice_version' => config('privacy.version'),
            'acknowledged_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        return parent::actingAs($user, $guard);
    }
}
