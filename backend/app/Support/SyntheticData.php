<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Synthetic test data (is_synthetic = 1) is hidden from program-level dashboards, analytics and
 * reports unless a coordinator, dean or admin explicitly turns on "Include synthetic data".
 * Students and supervisors only ever see their own records, so no filter applies to them.
 */
final class SyntheticData
{
    public const ROLES = [Role::Coordinator, Role::Dean, Role::Admin];

    public static function canInclude(User $user): bool
    {
        return in_array($user->role, self::ROLES, true);
    }

    /** True when synthetic rows must be left out of the current aggregate view. */
    public static function excluded(Request $request): bool
    {
        return self::canInclude($request->user()) && ! $request->boolean('include_synthetic');
    }

    /** Response block telling the UI whether the switch exists and what it is set to. */
    public static function meta(Request $request): array
    {
        return ['can_include' => self::canInclude($request->user()), 'included' => ! self::excluded($request)];
    }
}
