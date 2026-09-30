<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\Role;
use App\Models\Opportunity;
use App\Models\StudentEnrollment;
use App\Services\Audit;
use App\Services\OpportunityEligibility;
use App\Services\StudentAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpportunityInterestController extends Controller
{
    public function index(Request $request, int $enrollment)
    {
        abort_unless($request->user()->role === Role::Student, 403);
        $student = StudentAccess::enrollments($request->user())->findOrFail($enrollment);

        return response()->json(['data' => DB::table('opportunity_interests')->where('student_enrollment_id', $student->id)->whereNull('withdrawn_at')->pluck('opportunity_id')]);
    }

    public function store(Request $request, int $enrollment, Opportunity $opportunity, OpportunityEligibility $eligibility)
    {
        abort_unless($request->user()->role === Role::Student, 403);
        $student = StudentAccess::enrollments($request->user())->findOrFail($enrollment);
        $data = $request->validate(['interested' => ['required', 'boolean']]);
        DB::transaction(function () use ($request, $student, $opportunity, $data, $eligibility) {
            $student = StudentEnrollment::whereKey($student->id)->lockForUpdate()->firstOrFail();
            abort_unless($student->status === EnrollmentStatus::Enrolled, 409, 'This enrollment is closed.');
            abort_if(! $data['interested'] && ! DB::table('opportunity_interests')->where('student_enrollment_id', $student->id)->where('opportunity_id', $opportunity->id)->exists(), 404);
            abort_if($data['interested'] && ! $eligibility->inspect($student, $opportunity), 422, 'This opportunity is no longer eligible.');
            DB::table('opportunity_interests')->updateOrInsert(['student_enrollment_id' => $student->id, 'opportunity_id' => $opportunity->id],
                ['expressed_at' => now(), 'withdrawn_at' => $data['interested'] ? null : now(), 'updated_at' => now()]);
            Audit::record($request->user(), 'opportunity.interest_updated', $student, ['opportunity_id' => $opportunity->id, 'interested' => $data['interested']], $student->programTerm->program_id);
        });

        return response()->noContent();
    }
}
