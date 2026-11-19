<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Operator tool: change ONLY an account's display name and/or e-mail address (same account, role, password,
 * status and program assignments). The new e-mail must not belong to any other account, including archived
 * ones. The change is recorded in the audit trail (account.identity_updated, before/after) by the named admin.
 */
class UpdateAccountIdentity extends Command
{
    protected $signature = 'internmatch:update-account-identity
        {email : Current e-mail of the account}
        {--name= : New display name}
        {--new-email= : New e-mail address}
        {--actor= : E-mail of the admin recorded as making the change}';

    protected $description = "Change an account's name and/or e-mail only, with an audit entry";

    public function handle(): int
    {
        $user = User::withTrashed()->where('email', (string) $this->argument('email'))->first();
        $actor = User::where('email', (string) $this->option('actor'))->first();
        $changes = array_filter(['name' => $this->option('name'), 'email' => $this->option('new-email')], fn ($v) => $v !== null && $v !== '');
        $errors = [];
        if (! $user) {
            $errors[] = 'No account uses that e-mail address.';
        }
        if (! $actor || $actor->role !== Role::Admin || $actor->is_synthetic || $actor->status->value !== 'active') {
            $errors[] = '--actor must be an active, real admin account.';
        }
        if ($changes === []) {
            $errors[] = 'Give --name and/or --new-email.';
        }
        $validator = Validator::make($changes, ['name' => ['sometimes', 'string', 'min:2', 'max:255'], 'email' => ['sometimes', 'email:rfc', 'max:255']]);
        array_push($errors, ...$validator->errors()->all());
        if ($user && isset($changes['email']) && User::withTrashed()->where('email', $changes['email'])->whereKeyNot($user->id)->exists()) {
            $errors[] = "{$changes['email']} already belongs to another account.";
        }
        if ($errors) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $before = $user->only(array_keys($changes));
        DB::transaction(function () use ($user, $actor, $changes, $before) {
            $user->forceFill($changes)->save();
            Audit::record($actor, 'account.identity_updated', $user, ['before' => $before, 'after' => $changes]);
        });
        $this->info("Updated account #{$user->id} ({$user->role->value}): ".collect($changes)->map(fn ($v, $k) => "{$k} '{$before[$k]}' -> '{$v}'")->implode('; '));

        return self::SUCCESS;
    }
}
