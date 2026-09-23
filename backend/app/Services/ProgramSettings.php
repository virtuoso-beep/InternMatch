<?php

namespace App\Services;

use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class ProgramSettings
{
    public static function update(User $actor, Program $program, array $input): Program
    {
        Gate::forUser($actor)->authorize('update', $program);
        $data = Validator::make($input, [
            'required_ojt_hours' => ['present', 'nullable', 'integer', 'min:1', 'max:10000'],
            'internship_term' => ['present', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ])->validate();

        return DB::transaction(function () use ($actor, $program, $data) {
            $program = Program::query()->lockForUpdate()->findOrFail($program->id);
            $before = $program->only(array_keys($data));
            $program->update($data);
            Audit::record($actor, 'program.updated', $program, ['before' => $before, 'after' => $data], $program->id);

            return $program;
        });
    }
}
