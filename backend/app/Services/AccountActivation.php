<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Notifications\AccountActivationLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Spec §17A-B: emailed one-time activation links (decision #9, 72 hours by default).
 * - Only a SHA-256 hash of the token is stored; the raw token exists only in the e-mail.
 * - Issuing a new link revokes any earlier unused link for that user.
 * - A link works once, then sets the password, activates a pending account and is marked used.
 * Roster imports provision new student accounts as pending and issue these links; no password is ever handed out.
 */
class AccountActivation
{
    public static function issue(User $actor, User $user): void
    {
        abort_if($user->status === AccountStatus::Disabled, 422, 'Disabled accounts cannot be activated. Re-enable the account first.');
        abort_if(! $user->email, 422, 'The account has no e-mail address to send the link to.');
        $hours = config('internmatch.activation_hours');
        $token = Str::random(64);
        DB::transaction(function () use ($actor, $user, $token, $hours) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            DB::table('account_activations')->where('user_id', $user->id)->whereNull('used_at')->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'updated_at' => now()]);
            DB::table('account_activations')->insert([
                'user_id' => $user->id, 'token_hash' => hash('sha256', $token), 'issued_by' => $actor->id,
                'expires_at' => now()->addHours($hours), 'created_at' => now(), 'updated_at' => now(),
            ]);
            Audit::record($actor, 'account.activation_issued', $user, ['expires_in_hours' => $hours]);
        });
        $user->notify(new AccountActivationLink(config('internmatch.portal_url').'/activate?token='.$token, $hours));
    }

    /**
     * Current activation state per user, from the latest link: activated, link_sent or expired
     * (an account with no link yet is absent from the result).
     *
     * @param  array<int>  $userIds
     * @return array<int, string>
     */
    public static function statusFor(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }
        $latest = DB::table('account_activations')->whereIn('user_id', $userIds)
            ->whereIn('id', DB::table('account_activations')->whereIn('user_id', $userIds)->selectRaw('MAX(id)')->groupBy('user_id'))
            ->get(['user_id', 'used_at', 'revoked_at', 'expires_at']);

        return $latest->mapWithKeys(fn ($row) => [$row->user_id => $row->used_at ? 'activated'
            : ($row->revoked_at || now()->gte($row->expires_at) ? 'expired' : 'link_sent')])->all();
    }

    /** @return object|null the active, unexpired activation row for this raw token */
    public static function find(string $token): ?object
    {
        return DB::table('account_activations')->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')->whereNull('revoked_at')->where('expires_at', '>', now())->first();
    }

    public static function complete(string $token, string $password): User
    {
        return DB::transaction(function () use ($token, $password) {
            $row = DB::table('account_activations')->where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
            abort_unless($row && ! $row->used_at && ! $row->revoked_at && now()->lt($row->expires_at), 410, 'This activation link is no longer valid. Ask your coordinator to send a new one.');
            $user = User::whereKey($row->user_id)->lockForUpdate()->firstOrFail();
            abort_if($user->status === AccountStatus::Disabled, 410, 'This account is disabled. Contact your coordinator.');
            $user->forceFill([
                'password' => $password, 'must_change_password' => false, 'remember_token' => Str::random(60),
                // Using the e-mailed link proves the address receives mail.
                'email_verified_at' => $user->email_verified_at ?? now(),
                'status' => $user->status === AccountStatus::Pending ? AccountStatus::Active : $user->status,
            ])->save();
            DB::table('account_activations')->where('id', $row->id)->update(['used_at' => now(), 'updated_at' => now()]);
            Audit::record($user, 'account.activated', $user, []);

            return $user;
        });
    }
}
