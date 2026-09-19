<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\EvaluationRubric;
use App\Models\Program;
use App\Models\ProgramTerm;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgramMonitoringController extends Controller
{
    private function authorizeProgram(Request $request, int $program): void
    {
        $user = $request->user();
        abort_unless($user->hasPermission(Permission::ManageAcademicRecords)
            || ($user->role === Role::Coordinator && $user->programs()->where('programs.id', $program)->exists()), 403);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission(Permission::ManageAcademicRecords) || $user->role === Role::Coordinator, 403);
        $programs = Program::query()->when($user->role === Role::Coordinator, fn ($query) => $query->whereIn('id', $user->programs()->select('programs.id')))
            ->with(['programTerms.academicTerm'])->orderBy('code')->get();
        $rubrics = EvaluationRubric::with('criteria')->whereIn('id', $programs->pluck('evaluation_rubric_id')->filter())->get()->keyBy('id');

        return response()->json(['programs' => $programs, 'rubrics' => $rubrics]);
    }

    public function rules(Request $request, ProgramTerm $programTerm)
    {
        $this->authorizeProgram($request, $programTerm->program_id);
        $data = $request->validate(['remaining_days_threshold' => ['required', 'integer', 'between:0,365'], 'remaining_hours_threshold' => ['required', 'integer', 'between:1,10000'], 'approval_reference' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $programTerm, $data) {
            $programTerm->update(['monitoring_rules' => $data]);
            Audit::record($request->user(), 'monitoring_rules.configured', $programTerm, $data, $programTerm->program_id);
        });

        return response()->json(['data' => $programTerm]);
    }

    public function rubric(Request $request, Program $program)
    {
        $this->authorizeProgram($request, $program->id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'approval_reference' => ['required', 'string', 'max:1000'],
            'criteria' => ['required', 'array', 'min:1', 'max:100'], 'criteria.*.name' => ['required', 'string', 'max:255', 'distinct'],
            'criteria.*.max_score' => ['required', 'numeric', 'between:0.01,999999.99', 'decimal:0,2'],
            'criteria.*.weight' => ['required', 'numeric', 'between:0.0001,9999.9999', 'decimal:0,4'],
        ]);
        $rubric = DB::transaction(function () use ($request, $program, $data) {
            $record = Program::whereKey($program->id)->lockForUpdate()->firstOrFail();
            $code = 'PROGRAM-'.$program->id;
            $rubric = EvaluationRubric::create(['code' => $code, 'name' => $data['name'], 'version' => EvaluationRubric::where('code', $code)->max('version') + 1]);
            foreach ($data['criteria'] as $order => $criterion) {
                $rubric->criteria()->create([...$criterion, 'sort_order' => $order]);
            }
            $record->update(['evaluation_rubric_id' => $rubric->id]);
            Audit::record($request->user(), 'evaluation_rubric.configured', $rubric, $data, $program->id);

            return $rubric;
        });

        return response()->json(['data' => $rubric->load('criteria')], 201);
    }
}
