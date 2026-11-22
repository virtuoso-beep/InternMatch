<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DepartmentRoster
{
    public const PROGRAMS = [
        'BSA' => ['Bachelor of Science in Accountancy', 'DAE - Accounting Education'],
        'BSCRIM' => ['Bachelor of Science in Criminology', 'DCJE - Criminal Justice Education'],
        'BSIT' => ['Bachelor of Science in Information Technology', 'DCE - Computing Education'],
        'BSCS' => ['Bachelor of Science in Computer Science', 'DCE - Computing Education'],
    ];

    public const COORDINATORS = [
        'jm.flores@umindanao.edu.ph' => ['Joe Mari Flores', 'BSA'],
        'jm.asis@umindanao.edu.ph' => ['Jun Mark Asis', 'BSCRIM'],
        'r.misa@umindanao.edu.ph' => ['Richard Vincent Misa', 'BSIT'],
        'b.mahinay@umindanao.edu.ph' => ['Benjamin Mahinay', 'BSIT'],
        'i.mendoza@umindanao.edu.ph' => ['Iris Mae Mendoza', 'BSCS'],
    ];

    public static function apply(): array
    {
        return DB::transaction(function () {
            $credentials = [];
            foreach (Program::whereNotIn('code', array_keys(self::PROGRAMS))->where('is_active', true)->lockForUpdate()->get() as $program) {
                self::audit($program, 'program.retired', $program->only(['code', 'is_active']));
                $program->update(['is_active' => false]);
            }
            foreach (self::PROGRAMS as $code => [$name, $department]) {
                $program = Program::firstOrCreate(['code' => $code], ['name' => $name]);
                self::audit($program, 'program.department_updated', $program->only(['name', 'cluster', 'is_active']));
                $program->update(['name' => $name, 'cluster' => $department, 'is_active' => true]);
            }
            foreach (self::COORDINATORS as $email => [$name, $code]) {
                $user = User::withTrashed()->where('email', $email)->lockForUpdate()->first();
                if ($email === 'r.misa@umindanao.edu.ph' && ! $user) {
                    $user = User::withTrashed()->where('email', 'r.misa@example.com')->lockForUpdate()->first();
                }
                if ($user && $user->role !== Role::Coordinator) {
                    throw new RuntimeException("Roster email belongs to a non-coordinator: {$email}");
                }
                if ($user) {
                    self::audit($user, 'account.roster_updated', $user->only(['name', 'email', 'status']) + ['program_ids' => $user->programs()->pluck('programs.id')->all()]);
                    if ($user->trashed()) {
                        $user->restore();
                    }
                } else {
                    $credentials[$email] = Str::random(24);
                    $user = new User(['password' => $credentials[$email]]);
                }
                $user->forceFill(['name' => $name, 'email' => $email, 'role' => Role::Coordinator, 'status' => AccountStatus::Active])->save();
                CoordinatorAssignment::set($user, [Program::where('code', $code)->sole()->id]);
                $user->programTerms()->detach();
            }
            foreach (User::where('role', Role::Coordinator)->whereNotIn('email', array_keys(self::COORDINATORS))->lockForUpdate()->get() as $user) {
                self::audit($user, 'account.retired', $user->only(['name', 'email', 'status']) + ['program_ids' => $user->programs()->pluck('programs.id')->all()]);
                CoordinatorAssignment::set($user, []);
                $user->programTerms()->detach();
                $user->forceFill(['status' => AccountStatus::Disabled])->save();
                $user->delete();
            }

            return $credentials;
        });
    }

    private static function audit($subject, string $action, array $before): void
    {
        DB::table('audit_logs')->insert(['actor_id' => null, 'program_id' => $subject instanceof Program ? $subject->id : null,
            'action' => $action, 'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->id,
            'changes' => json_encode(['source' => 'User-confirmed department roster, 2026-11-02', 'before' => $before], JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }
}
