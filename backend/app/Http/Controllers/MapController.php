<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\HostEstablishment;
use App\Models\Opportunity;
use App\Services\Haversine;
use App\Services\HostAccess;
use App\Services\OpportunityEligibility;
use App\Services\StudentAccess;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function __invoke(Request $request, OpportunityEligibility $eligibility)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, [Role::Student, Role::Coordinator, Role::Dean, Role::Admin], true), 403);
        $data = $request->validate(['enrollment_id' => ['nullable', 'integer']]);
        abort_if($user->role === Role::Dean && isset($data['enrollment_id']), 403);
        $enrollment = null;
        if (isset($data['enrollment_id'])) {
            $enrollment = StudentAccess::enrollments($user)->findOrFail($data['enrollment_id']);
        } elseif ($user->role === Role::Student) {
            $enrollment = StudentAccess::enrollments($user)->where('status', 'enrolled')->latest('id')->first();
        }
        $student = null;
        if ($enrollment) {
            $profile = $enrollment->student->user->profile;
            if ($profile?->latitude !== null && $profile?->longitude !== null) {
                $student = ['name' => $enrollment->student->user->name, 'latitude' => (float) $profile->latitude, 'longitude' => (float) $profile->longitude];
            }
            $hostIds = [];
            foreach (Opportunity::where('academic_term_id', $enrollment->programTerm->academic_term_id)->whereHas('programs', fn ($q) => $q->whereKey($enrollment->programTerm->program_id))->get() as $opportunity) {
                if ($eligibility->inspect($enrollment, $opportunity)) { $hostIds[] = $opportunity->host_establishment_id; }
            }
            if ($enrollment->currentPlacement) { $hostIds[] = $enrollment->currentPlacement->host_establishment_id; }
            $hosts = HostEstablishment::whereIn('id', $hostIds);
        } elseif ($user->role === Role::Dean) {
            $hosts = HostEstablishment::whereHas('opportunities.programs', fn ($q) => $q->whereIn('programs.id', $user->programs()->select('programs.id')));
        } else {
            $hosts = HostAccess::query($user);
        }

        return response()->json(['student' => $student, 'hosts' => $hosts->orderBy('name')->get()->map(function ($host) use ($student) {
            $hasCoordinates = $host->latitude !== null && $host->longitude !== null;

            return ['id' => $host->id, 'name' => $host->name, 'city' => $host->city, 'latitude' => $hasCoordinates ? (float) $host->latitude : null, 'longitude' => $hasCoordinates ? (float) $host->longitude : null,
                'distance_km' => $student && $hasCoordinates ? Haversine::kilometers($student['latitude'], $student['longitude'], (float) $host->latitude, (float) $host->longitude) : null];
        }), 'distance_kind' => 'straight_line'], headers: ['Cache-Control' => 'no-store']);
    }
}
