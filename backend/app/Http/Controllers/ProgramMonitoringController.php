<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\EvaluationRubric;
use App\Models\Program;
use App\Models\ProgramTerm;
use App\Services\Audit;
use App\Services\EvaluationRubricConfiguration;
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
        $programs = Program::query()->where('is_active', true)->when($user->role === Role::Coordinator, fn ($query) => $query->whereIn('id', $user->programs()->select('programs.id')))
            ->with(['programTerms.academicTerm'])->orderBy('code')->get();
        $rubrics = EvaluationRubric::with('criteria')->whereIn('id', $programs->pluck('evaluation_rubric_id')->filter())->get()->keyBy('id');

        return response()->json(['programs' => $programs, 'rubrics' => $rubrics]);
    }

    public function rules(Request $request, ProgramTerm $programTerm)
    {
        $this->authorizeProgram($request, $programTerm->program_id);
        // Spec §13 rule values (defaults in ProgressSummary::DEFAULTS) plus the optional legacy hours thresholds.
        $data = $request->validate([
            'remaining_days_threshold' => ['nullable', 'required_with:remaining_hours_threshold', 'integer', 'between:0,365'],
            'remaining_hours_threshold' => ['nullable', 'required_with:remaining_days_threshold', 'integer', 'between:1,10000'],
            'behind_schedule_percent' => ['sometimes', 'integer', 'between:1,100'],
            'behind_schedule_after_days' => ['sometimes', 'integer', 'between:0,200'],
            'max_daily_hours' => ['sometimes', 'integer', 'between:1,24'],
            'no_log_days' => ['sometimes', 'integer', 'between:1,60'],
            'uncertified_days' => ['sometimes', 'integer', 'between:1,60'],
            'weekly_report_grace_days' => ['sometimes', 'integer', 'between:0,60'],
            'requirement_grace_days' => ['sometimes', 'integer', 'between:0,60'],
            'evaluation_window_days' => ['sometimes', 'integer', 'between:1,60'],
            'evaluation_grace_days' => ['sometimes', 'integer', 'between:0,60'],
            'approval_reference' => ['required', 'string', 'max:1000'],
        ]);
        $data = array_filter($data, fn ($value) => $value !== null);
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
        $rubric = EvaluationRubricConfiguration::configure($request->user(), $program, $data);

        return response()->json(['data' => $rubric->load('criteria')], 201);
    }
}
