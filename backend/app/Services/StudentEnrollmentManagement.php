<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StudentEnrollmentManagement
{
    public static function withdraw(User $actor, StudentEnrollment $enrollment, array $input): StudentEnrollment
    {
        return DB::transaction(function () use ($actor, $enrollment, $input) {
            $record = StudentEnrollment::query()->lockForUpdate()->findOrFail($enrollment->id);
            Gate::forUser($actor)->authorize('update', $record);
            $data = Validator::make($input, ['reason' => ['required', 'string', 'max:2000']])->validate();
            if ($record->status !== EnrollmentStatus::Enrolled || $record->placements()->exists()) {
                throw ValidationException::withMessages(['reason' => 'Only an enrolled student with no placement history can be withdrawn here.']);
            }
            $record->update(['status' => EnrollmentStatus::Withdrawn]);
            Audit::record($actor, 'student.withdrawn', $record, $data, $record->programTerm->program_id);

            return $record;
        });
    }

    public static function update(User $actor, StudentEnrollment $enrollment, array $input): StudentEnrollment
    {
        return DB::transaction(function () use ($actor, $enrollment, $input) {
            $record = StudentEnrollment::query()->lockForUpdate()->findOrFail($enrollment->id);
            Gate::forUser($actor)->authorize('update', $record);
            $data = Validator::make($input, [
                'year_level' => ['required', 'integer', 'between:1,10'],
                'enrolled_on' => ['required', 'date'],
                'target_completion_on' => ['nullable', 'date', 'after_or_equal:enrolled_on'],
            ])->validate();
            if ($record->status !== EnrollmentStatus::Enrolled) {
                throw ValidationException::withMessages(['year_level' => 'Closed enrollment records cannot be edited.']);
            }
            // Identity, program, hours and placement status are never accepted from this form.
            $before = $record->only(array_keys($data));
            $record->update($data);
            Audit::record($actor, 'student.enrollment_updated', $record,
                ['before' => $before, 'after' => $record->only(array_keys($data))], $record->programTerm->program_id);

            return $record;
        });
    }
}
