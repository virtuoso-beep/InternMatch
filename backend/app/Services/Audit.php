<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Audit
{
    public static function record(User $actor, string $action, Model $subject, array $changes, ?int $programId = null): void
    {
        DB::table('audit_logs')->insert([
            'actor_id' => $actor->id, 'program_id' => $programId, 'action' => $action,
            'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey(),
            'changes' => json_encode($changes, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }
}
