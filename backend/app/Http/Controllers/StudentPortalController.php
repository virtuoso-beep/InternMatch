<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Opportunity;
use App\Models\Recommendation;
use App\Services\Haversine;
use App\Services\OpportunityEligibility;
use App\Services\StudentAccess;
use Illuminate\Http\Request;

class StudentPortalController extends Controller
{
    public function recommendations(Request $request, int $enrollment)
    {
        abort_unless(in_array($request->user()->role, [Role::Student, Role::Coordinator, Role::Admin], true), 403);
        $student = StudentAccess::enrollments($request->user())->findOrFail($enrollment);
        $query = Recommendation::query()->where('student_enrollment_id', $student->id);
        $generation = (clone $query)->orderByDesc('generated_at')->orderByDesc('id')->value('generation_id');

        // Read only stored generation facts, never join today's capacity/MOA into an old explanation.
        return response()->json(['data' => $generation ? $query->where('generation_id', $generation)->orderBy('rank')->get() : []], headers: ['Cache-Control' => 'no-store']);
    }

    public function enrollments(Request $request)
    {
        abort_if($request->user()->role === Role::Dean, 403, 'Program chairs and deans access approved reports.');

        return response()->json(['data' => StudentAccess::enrollments($request->user())
            ->with(['student.user:id,name', 'programTerm.program', 'programTerm.academicTerm', 'currentPlacement.hostEstablishment', 'currentPlacement.opportunity'])
            ->orderByDesc('enrolled_on')->get()], headers: ['Cache-Control' => 'no-store']);
    }

    public function opportunities(Request $request, int $enrollment, OpportunityEligibility $eligibility)
    {
        abort_unless(in_array($request->user()->role, [Role::Student, Role::Coordinator, Role::Admin], true), 403);
        $student = StudentAccess::enrollments($request->user())->findOrFail($enrollment);
        $result = [];
        $profile = $student->student->user->profile;
        $candidates = Opportunity::query()->where('academic_term_id', $student->programTerm->academic_term_id)
            ->where('status', 'published')->whereHas('programs', fn ($query) => $query->whereKey($student->programTerm->program_id))
            ->with('hostEstablishment')->orderBy('title')->get();
        foreach ($candidates as $opportunity) {
            if ($facts = $eligibility->inspect($student, $opportunity)) {
                $host = $opportunity->hostEstablishment;
                $distance = null;
                if ($profile?->latitude !== null && $profile?->longitude !== null && $host->latitude !== null && $host->longitude !== null) {
                    $distance = Haversine::kilometers((float) $profile->latitude, (float) $profile->longitude, (float) $host->latitude, (float) $host->longitude);
                }
                $result[] = ['id' => $opportunity->id, 'title' => $opportunity->title,
                    'description' => $opportunity->description, 'tasks' => $opportunity->tasks,
                    'host_name' => $opportunity->hostEstablishment->name, 'city' => $opportunity->hostEstablishment->city,
                    'eligibility' => $facts, 'distance_km' => $distance, 'distance_kind' => 'straight_line'];
            }
        }

        return response()->json(['data' => $result], headers: ['Cache-Control' => 'no-store']);
    }
}
