<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Services\Audit;
use App\Services\CoordinatorAssignment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Admin, dean and coordinator accounts are fixed real accounts; only supervisors and students may be synthetic.
 * This moves every reference to a synthetic admin/dean/coordinator onto the matching real account, then removes
 * the synthetic account. Moved records keep is_synthetic, so internmatch:purge-synthetic still removes them.
 *  - Coordinators map by the program they were assigned to (--coordinator=BSA=email); the real coordinator must
 *    already hold that program (their programs are never changed).
 *  - The real dean receives the synthetic dean's programs (audited); nothing else about real accounts changes.
 *  - Audit rows are moved only when they are synthetic; real audit history is never re-attributed (its actor
 *    becomes NULL through the existing foreign key when the synthetic account is removed).
 * Dry run unless --force: the same work runs in a transaction and is rolled back, so counts are exact.
 */
class ReassignSyntheticStaff extends Command
{
    protected $signature = 'internmatch:reassign-synthetic-staff
        {--admin= : Real admin e-mail}
        {--dean= : Real dean e-mail}
        {--coordinator=* : PROGRAM=e-mail of the real coordinator for that program}
        {--force : Apply (default is a dry run)}';

    protected $description = 'Move synthetic admin/dean/coordinator references to real accounts and remove those synthetic accounts';

    /** Cascade/per-account rows that are dropped with the synthetic account instead of moved. */
    private const DROPPED = ['coordinator_program.user_id', 'privacy_consents.user_id', 'sessions.user_id', 'conversation_reads.user_id', 'account_activations.user_id', 'user_profiles.user_id'];

    private array $counts = [];

    public function handle(): int
    {
        $this->counts = []; // command instances are reused between calls in one process
        $synthetic = User::where('is_synthetic', true)->whereIn('role', [Role::Admin, Role::Dean, Role::Coordinator])->orderBy('id')->get();
        if ($synthetic->isEmpty()) {
            $this->info('No synthetic admin, dean or coordinator accounts remain.');

            return self::SUCCESS;
        }
        $coordinators = [];
        foreach ((array) $this->option('coordinator') as $pair) {
            [$code, $email] = array_pad(explode('=', (string) $pair, 2), 2, null);
            $coordinators[strtoupper((string) $code)] = $email;
        }

        $map = [];
        $errors = [];
        $deanPrograms = [];
        foreach ($synthetic as $user) {
            $programs = DB::table('coordinator_program')->join('programs', 'programs.id', '=', 'coordinator_program.program_id')
                ->where('user_id', $user->id)->whereNull('removed_at')->pluck('programs.code', 'programs.id');
            $email = match ($user->role) {
                Role::Admin => $this->option('admin'),
                Role::Dean => $this->option('dean'),
                default => $programs->count() === 1 ? ($coordinators[strtoupper((string) $programs->first())] ?? null) : null,
            };
            $target = $email ? User::where('email', $email)->first() : null;
            $label = "{$user->source_id} {$user->role->value} {$user->email}";
            if (! $target) {
                $errors[] = "{$label}: no real target account given or found".($user->role === Role::Coordinator ? ' (use --coordinator='.($programs->first() ?? 'PROGRAM').'=email)' : '');

                continue;
            }
            if ($target->is_synthetic || $target->role !== $user->role || $target->status->value !== 'active' || $target->trashed()) {
                $errors[] = "{$label}: target {$target->email} must be an active, real {$user->role->value} account";

                continue;
            }
            if ($user->role === Role::Coordinator && ! $target->programs()->whereKey($programs->keys())->exists()) {
                $errors[] = "{$label}: real coordinator {$target->email} is not assigned to ".$programs->implode(',');

                continue;
            }
            if ($user->role === Role::Dean) {
                $deanPrograms = $programs->keys()->all();
            }
            $map[$user->id] = $target;
        }
        if ($errors) {
            $this->error('Nothing was changed:');
            foreach ($errors as $error) {
                $this->line(' - '.$error);
            }

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        DB::beginTransaction();
        try {
            $this->apply($map, $deanPrograms);
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Nothing was changed: '.$e->getMessage());

            return self::FAILURE;
        }
        $force ? DB::commit() : DB::rollBack();

        $this->table(['Synthetic account', 'Moved to'], $synthetic->map(fn ($u) => ["{$u->source_id} {$u->role->value} {$u->email}", $map[$u->id]->email])->all());
        $this->table(['Records', $force ? 'Moved / removed' : 'Would move / remove'], collect($this->counts)->filter()->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->info($force ? 'Done.' : 'Dry run only; nothing was changed. Re-run with --force to apply.');

        return self::SUCCESS;
    }

    /** @param array<int, User> $map synthetic user id => real user */
    private function apply(array $map, array $deanPrograms): void
    {
        $ids = array_keys($map);
        $columns = DB::table('information_schema.key_column_usage')->where('table_schema', DB::raw('DATABASE()'))
            ->where('referenced_table_name', 'users')->where('referenced_column_name', 'id')->get(['table_name as t', 'column_name as c']);
        $before = [];
        foreach ($map as $id => $target) {
            $before[$target->id] = $target->only(['name', 'email', 'status', 'password', 'role']);
        }

        foreach ($columns as $col) {
            $key = "{$col->t}.{$col->c}";
            if (in_array($key, self::DROPPED, true)) {
                $this->counts["{$key} (dropped)"] = DB::table($col->t)->whereIn($col->c, $ids)->delete();

                continue;
            }
            foreach ($map as $from => $to) {
                $query = DB::table($col->t)->where($col->c, $from);
                if ($col->t === 'audit_logs') {
                    $query->where('is_synthetic', true); // never re-attribute real audit history
                }
                $this->counts["{$key} -> {$to->email}"] = ($this->counts["{$key} -> {$to->email}"] ?? 0) + $query->update([$col->c => $to->id]);
            }
        }
        $morph = (new User)->getMorphClass();
        foreach ($map as $from => $to) {
            $this->counts["notifications.notifiable -> {$to->email}"] = DB::table('notifications')->where('notifiable_type', $morph)->where('notifiable_id', $from)->update(['notifiable_id' => $to->id]);
        }
        $this->counts['audit_logs.actor_id (real history, actor cleared on removal)'] = DB::table('audit_logs')->whereIn('actor_id', $ids)->where('is_synthetic', false)->count();

        // Audit entries are recorded by the real admin (the operator account), or the target itself when no admin is mapped.
        $actor = collect($map)->first(fn ($u) => $u->role === Role::Admin);
        foreach ($map as $from => $to) {
            if ($to->role === Role::Dean && $deanPrograms) {
                $change = CoordinatorAssignment::set($to, [...$to->programs()->pluck('programs.id')->all(), ...$deanPrograms]);
                if ($change['before'] !== $change['after']) {
                    Audit::record($actor ?? $to, 'account.program_access_updated', $to, $change + ['reason' => 'Synthetic dean replaced by the real dean']);
                    $this->counts["coordinator_program: {$to->email} programs ".implode(',', $change['before']).' -> '.implode(',', $change['after'])] = 1;
                }
            }
        }
        $this->counts['users (synthetic admin/dean/coordinator removed)'] = DB::table('users')->whereIn('id', $ids)->delete();
        foreach (collect($map)->unique('id') as $to) {
            Audit::record($actor ?? $to, 'synthetic.staff_reassigned', $to, ['replaced_synthetic_accounts' => collect($map)->filter(fn ($u) => $u->id === $to->id)->count()]);
        }

        foreach ($before as $id => $was) {
            $now = User::find($id)->only(['name', 'email', 'status', 'password', 'role']);
            if ($now !== $was) {
                throw new \LogicException("Real account #{$id} changed unexpectedly.");
            }
        }
    }
}
