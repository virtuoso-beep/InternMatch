<?php

namespace App\Console\Commands;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * First deployment only: create the first administrator account. Production never seeds demo accounts, so
 * this is how the first admin is made; every later account is created from the administration screens.
 * The password is typed at a hidden prompt (never on the command line, in a file or in the shell history)
 * and must meet the same rules as the password screens. Refuses when any administrator already exists.
 */
class CreateFirstAdmin extends Command
{
    protected $signature = 'internmatch:create-first-admin {email : E-mail address of the administrator} {--name= : Display name}';

    protected $description = 'Create the first administrator account (refuses when one already exists)';

    public function handle(): int
    {
        if (User::withTrashed()->where('role', Role::Admin)->exists()) {
            $this->error('An administrator account already exists. Create further accounts from the administration screens.');

            return self::FAILURE;
        }
        $email = (string) $this->argument('email');
        $name = (string) ($this->option('name') ?: $this->ask('Display name'));
        $password = (string) $this->secret('Password (at least 12 characters, upper and lower case, a number and a symbol)');
        $confirmation = (string) $this->secret('Type the password again');

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password, 'password_confirmation' => $confirmation],
            ['email' => ['required', 'email:rfc', 'max:255'], 'name' => ['required', 'string', 'min:2', 'max:255'],
                'password' => ['required', 'confirmed', 'max:128', Password::min(12)->mixedCase()->numbers()->symbols()]],
        );
        $errors = $validator->errors()->all();
        if (User::withTrashed()->where('email', $email)->exists()) {
            $errors[] = 'That e-mail address already belongs to an account.';
        }
        if ($errors) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($email, $name, $password) {
            $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->forceFill(['role' => Role::Admin, 'status' => AccountStatus::Active, 'email_verified_at' => now()])->save();
            Audit::record($user, 'account.first_admin_created', $user, ['email' => $email, 'source' => 'command']);

            return $user;
        });
        $this->info("Administrator account #{$user->id} created for {$email}. Sign in at /manage.");

        return self::SUCCESS;
    }
}
