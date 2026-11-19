<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Local testing only: set a password for one existing account so you can sign in as it
 * (imported synthetic accounts get an unknown random password and .test addresses receive no mail).
 * Refuses to run in production. The password is typed at a hidden prompt, or generated and shown once.
 * --all-synthetic sets the same password on every synthetic (is_synthetic = 1) account except accounts kept by
 * internmatch:purge-synthetic (config internmatch.synthetic_keep_student_numbers) and pending accounts, which
 * use their own activation link. Real accounts are never selected.
 */
class SetLocalPassword extends Command
{
    protected $signature = 'internmatch:set-local-password {email? : Account email address} {--all-synthetic : Every synthetic account except kept and pending ones} {--generate : Generate a random password and print it once}';

    protected $description = 'Set a password for one account (local/testing environments only)';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Refused: this command never runs in production.');

            return self::FAILURE;
        }
        $all = (bool) $this->option('all-synthetic');
        if ($all === (bool) $this->argument('email')) {
            $this->error('Give exactly one of: an email address, or --all-synthetic.');

            return self::INVALID;
        }
        if ($all) {
            $kept = DB::table('students')->whereIn('student_number', config('internmatch.synthetic_keep_student_numbers', []))->pluck('user_id');
            $users = User::where('is_synthetic', true)->whereNotIn('id', $kept)->where('status', '!=', 'pending')->orderBy('id')->get();
            if ($users->isEmpty()) {
                $this->error('No synthetic accounts to update.');

                return self::FAILURE;
            }
        } else {
            $user = User::where('email', (string) $this->argument('email'))->first();
            if (! $user) {
                $this->error('No account uses that email address.');

                return self::FAILURE;
            }
            $users = collect([$user]);
        }
        if ($this->option('generate')) {
            $password = Str::password(16);
        } else {
            $password = (string) $this->secret('New password (min. 12 characters)');
            if (mb_strlen($password) < 12 || $password !== (string) $this->secret('Repeat the password')) {
                $this->error('Passwords must match and be at least 12 characters.');

                return self::FAILURE;
            }
        }
        foreach ($users as $user) {
            $user->forceFill(['password' => Hash::make($password), 'must_change_password' => false, 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        $this->info($all ? "Password set for {$users->count()} synthetic accounts (".$users->countBy(fn ($u) => $u->role->value)->map(fn ($n, $r) => "{$r}: {$n}")->implode(', ').').'
            : "Password set for {$user->email} (role: {$user->role->value}, status: {$user->status->value}).");
        if ($this->option('generate')) {
            $this->line("Generated password (shown once): {$password}");
        }

        return self::SUCCESS;
    }
}
