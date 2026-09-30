<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Opportunity;
use App\Models\ProgramTerm;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Audit;
use App\Services\CohortAllocation;
use App\Services\HostAccess;
use App\Services\PlacementReadiness;
use App\Services\StudentAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AllocationController extends Controller
{
    public function index(Request $request, PlacementReadiness $readiness)
    {
        abort_unless($request->user()->role === Role::Coordinator, 403);
        $terms = ProgramTerm::whereIn('program_id', $request->user()->programs()->select('programs.id'))->with(['program', 'academicTerm'])->get();
        $enrollments = StudentAccess::enrollments($request->user())->with(['student.user', 'programTerm.program'])->get();
        $proposals = DB::table('placement_proposals')->whereIn('program_term_id', $terms->pluck('id'))->orderByDesc('id')->limit(20)->get();
        foreach ($proposals as $proposal) {
            $proposal->items = DB::table('placement_proposal_items as i')->join('student_enrollments as e', 'e.id', '=', 'i.student_enrollment_id')->join('students as s', 's.id', '=', 'e.student_id')->join('users as u', 'u.id', '=', 's.user_id')->leftJoin('opportunities as o', 'o.id', '=', 'i.opportunity_id')->leftJoin('host_establishments as h', 'h.id', '=', 'o.host_establishment_id')->leftJoin('recommendations as r', 'r.id', '=', 'i.recommendation_id')->where('i.placement_proposal_id', $proposal->id)->select(['i.*', 'u.name as student_name', 'o.title as opportunity_title', 'h.name as host_name', 'r.similarity_score', 'r.distance_km'])->orderBy('i.id')->get();
        }
        $hosts = HostAccess::query($request->user())->pluck('id');

        return response()->json(['cohorts' => $terms, 'students' => $enrollments->map(fn ($e) => ['id' => $e->id, 'name' => $e->student->user->name, 'program_term_id' => $e->program_term_id, 'academic_eligibility_confirmed' => $e->academic_eligibility_confirmed === null ? null : (bool) $e->academic_eligibility_confirmed, 'reasons' => $readiness->reasons($e)]), 'proposals' => $proposals,
            'opportunities' => Opportunity::whereIn('host_establishment_id', $hosts)->where('status', 'published')->get(['id', 'title', 'host_establishment_id']),
            'supervisors' => User::where('role', Role::Supervisor)->where('status', 'active')->whereHas('hostEstablishments', fn ($q) => $q->whereIn('host_establishments.id', $hosts))->with('hostEstablishments:id')->get(['id', 'name'])]);
    }

    public function assess(Request $request, int $enrollment, CohortAllocation $allocation)
    {
        $student = StudentAccess::enrollments($request->user())->findOrFail($enrollment);
        $allocation->authorize($request->user(), $student->programTerm);
        $data = $request->validate(['eligible' => ['required', 'boolean'], 'reason' => ['required', 'string', 'min:5', 'max:2000']]);
        DB::transaction(function () use ($request, $student, $data) {
            $student = StudentEnrollment::whereKey($student->id)->lockForUpdate()->firstOrFail();
            abort_unless($student->status->value === 'enrolled', 409, 'This enrollment is closed.');
            DB::table('student_enrollments')->where('id', $student->id)->update(['academic_eligibility_confirmed' => $data['eligible'], 'eligibility_confirmed_by' => $request->user()->id, 'eligibility_confirmed_at' => now(), 'eligibility_note' => $data['reason'], 'updated_at' => now()]);
            Audit::record($request->user(), 'enrollment.eligibility_assessed', $student, $data, $student->programTerm->program_id);
        });

        return response()->noContent();
    }

    public function generate(Request $request, ProgramTerm $programTerm, CohortAllocation $allocation)
    {
        return response()->json(['id' => $allocation->generate($request->user(), $programTerm)], 201);
    }

    public function decide(Request $request, int $item, CohortAllocation $allocation)
    {
        $data = $request->validate(['decision' => ['required', 'in:approve,reject'], 'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'opportunity_id' => ['nullable', 'integer', 'exists:opportunities,id'], 'supervisor_id' => ['required_if:decision,approve', 'integer', 'exists:users,id'],
            'starts_on' => ['required_if:decision,approve', 'date_format:Y-m-d'], 'ends_on' => ['required_if:decision,approve', 'date_format:Y-m-d', 'after_or_equal:starts_on']]);

        return response()->json(['data' => $allocation->decide($request->user(), $item, $data)]);
    }
}
