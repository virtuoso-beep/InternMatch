<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Competency;
use App\Models\Program;
use App\Models\Student;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompetencyController extends Controller
{
    private function student(Request $request): Student
    {
        abort_unless($request->user()->role === Role::Student, 403);

        return $request->user()->student()->firstOrFail();
    }

    public function index(Request $request)
    {
        $student = $this->student($request);
        $programs = Program::query()->whereHas('programTerms.enrollments', fn ($query) => $query
            ->where('student_id', $student->id)->where('status', 'enrolled'))->pluck('id');
        $vocabulary = Competency::query()->whereIn('id', DB::table('program_competencies')
            ->whereIn('program_id', $programs)->select('competency_id'))->orderBy('name')->get(['id', 'name']);

        return response()->json(['data' => $student->studentCompetencies()->with('competency')->get(), 'vocabulary' => $vocabulary]);
    }

    public function store(Request $request)
    {
        $student = $this->student($request);
        $data = $request->validate(['competency_id' => ['required', 'integer', 'exists:competencies,id'], 'level' => ['required', 'integer', 'between:0,100']]);
        $record = DB::transaction(function () use ($student, $data, $request) {
            // Lock the owner to serialize concurrent edits to a student's competency set.
            Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $record = $student->studentCompetencies()->updateOrCreate(['competency_id' => $data['competency_id']], ['level' => $data['level']]);
            Audit::record($request->user(), 'student.competency_saved', $record, $data);

            return $record;
        });

        return response()->json(['data' => $record->load('competency')]);
    }

    public function destroy(Request $request, int $competency)
    {
        $student = $this->student($request);
        DB::transaction(function () use ($student, $competency, $request) {
            $record = $student->studentCompetencies()->lockForUpdate()->findOrFail($competency);
            abort_if($record->evidence()->exists(), 409, 'This competency has evidence attached and cannot be deleted.');
            Audit::record($request->user(), 'student.competency_deleted', $record, ['competency_id' => $record->competency_id]);
            $record->delete();
        });

        return response()->noContent();
    }
}
