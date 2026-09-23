<?php

namespace App\Services;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\Program;
use App\Models\ProgramTerm;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EnrollmentProvisioning
{
    public static function create(User $actor, array $input): StudentEnrollment
    {
        // Registry provisioning is technical administration, never placement approval.
        abort_unless($actor->hasPermission(Permission::ManageAcademicRecords), 403);
        $data = Validator::make($input, [
            'user_id' => ['required_without:student_email', Rule::prohibitedIf(filled($input['student_email'] ?? null)), 'integer', 'exists:users,id'], 'student_email' => ['required_without:user_id', 'email', 'exists:users,email'], 'student_number' => ['required', 'string', 'max:50'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'academic_term_id' => ['required', 'integer', 'exists:academic_terms,id'],
            'year_level' => ['required', 'integer', 'between:1,10'], 'enrolled_on' => ['required', 'date'],
            'target_completion_on' => ['nullable', 'date', 'after_or_equal:enrolled_on'],
        ])->validate();
        $record = DB::transaction(function () use ($data, $actor) {
            $user = (isset($data['user_id']) ? User::whereKey($data['user_id']) : User::where('email', $data['student_email']))->lockForUpdate()->firstOrFail();
            abort_unless($user->role === Role::Student, 422, 'Enrollment requires a student account.');
            $program = Program::whereKey($data['program_id'])->lockForUpdate()->firstOrFail();
            if (! $program->is_active || $program->required_ojt_hours === null) {
                throw ValidationException::withMessages(['program_id' => 'Configure department-confirmed program hours before enrollment.']);
            }
            abort_unless(AcademicTerm::findOrFail($data['academic_term_id'])->is_active, 422, 'The academic term is inactive.');
            $existing = Student::where('student_number', $data['student_number'])->first();
            if ($existing && $existing->user_id !== $user->id) {
                throw ValidationException::withMessages(['student_number' => 'This student number belongs to another account.']);
            }
            $student = $user->student()->first();
            if ($student && $student->student_number !== $data['student_number']) {
                throw ValidationException::withMessages(['student_number' => 'The account already has a different student number.']);
            }
            $student ??= $user->student()->create(['student_number' => $data['student_number']]);
            $term = ProgramTerm::firstOrCreate(['program_id' => $program->id, 'academic_term_id' => $data['academic_term_id']], ['required_minutes' => $program->required_ojt_hours * 60]);
            if ($student->enrollments()->where('program_term_id', $term->id)->exists()) {
                throw ValidationException::withMessages(['program_id' => 'The student is already enrolled in this program and term.']);
            }
            $enrollment = StudentEnrollment::create([
                'student_id' => $student->id, 'program_term_id' => $term->id, 'year_level' => $data['year_level'],
                'required_minutes' => $program->required_ojt_hours * 60, 'status' => 'enrolled',
                'enrolled_on' => $data['enrolled_on'], 'target_completion_on' => $data['target_completion_on'] ?? null,
            ]);
            Audit::record($actor, 'student.enrolled', $enrollment, ['program_id' => $program->id, 'academic_term_id' => $term->academic_term_id], $program->id);

            return $enrollment;
        });

        return $record;
    }
}
