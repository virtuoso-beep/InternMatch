<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * Self-service password reset by e-mail.
 *
 * - The request always gets the same answer, so it never reveals whether an address has an account.
 * - Only active accounts receive a link; pending accounts use their activation link instead.
 * - Links come from Laravel's password broker: hashed token, single use, expiring (auth.passwords.users.expire).
 * - A successful reset applies the activation password rules, rotates the remember token and ends every
 *   other session of the account.
 */
class PasswordResetController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);
        $email = Str::lower(trim($data['email']));
        $key = 'password-reset:'.hash('sha256', $email.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            throw new HttpResponseException(response()->json([
                'message' => 'Too many reset requests. Please wait before trying again.',
                'retry_after' => $seconds,
            ], 429, ['Retry-After' => (string) $seconds]));
        }
        RateLimiter::hit($key, 900);

        $user = User::where('email', $email)->first();
        if ($user && $user->status === AccountStatus::Active) {
            // The broker also limits one link per account per minute; a throttled request is answered the same way.
            if (Password::broker()->sendResetLink(['email' => $user->email]) === Password::RESET_LINK_SENT) {
                Audit::record($user, 'account.password_reset_requested', $user, []);
            }
        }

        return response()->json([
            'message' => 'If an active InternMatch account uses this address, a password reset link is on its way.',
            'expires_in_minutes' => (int) config('auth.passwords.users.expire', 60),
        ], 202);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        $invalid = fn () => ValidationException::withMessages([
            'token' => ['This password reset link is invalid or has expired. Request a new one.'],
        ]);
        $user = User::where('email', Str::lower(trim($data['email'])))->first();
        if (! $user || $user->status !== AccountStatus::Active) {
            throw $invalid();
        }

        $status = Password::broker()->reset(
            ['email' => $user->email, 'token' => $data['token'], 'password' => $data['password'], 'password_confirmation' => $request->input('password_confirmation')],
            function (User $account, string $password) {
                $account->forceFill(['password' => $password, 'remember_token' => Str::random(60), 'must_change_password' => false])->save();
                DB::table('sessions')->where('user_id', $account->id)->delete();
                Audit::record($account, 'account.password_reset', $account, []);
            },
        );
        if ($status !== Password::PASSWORD_RESET) {
            throw $invalid();
        }

        return response()->json(['data' => ['reset' => true]]);
    }
}
