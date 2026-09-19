<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\MoaStatus;
use App\Enums\OpportunityStatus;
use App\Models\Moa;
use App\Models\Opportunity;
use App\Models\Placement;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;

class OpportunityEligibility
{
    /** Paperwork deliberately does not filter browsing or recommendations. */
    public function inspect(StudentEnrollment $enrollment, Opportunity $opportunity): ?array
    {
        $programTerm = $enrollment->programTerm;
        $programId = $programTerm->program_id;
        if ($enrollment->status !== EnrollmentStatus::Enrolled || ! $programTerm->program->is_active
            || ! $programTerm->academicTerm->is_active
            || $opportunity->status !== OpportunityStatus::Published
            || ! $opportunity->hostEstablishment->is_active
            || $opportunity->academic_term_id !== $programTerm->academic_term_id
            || ($opportunity->ends_on && $opportunity->ends_on->isBefore(today()))) {
            return null;
        }
        $capacity = $opportunity->programs()->whereKey($programId)->first()?->pivot->capacity;
        $hostCapacity = DB::table('host_program_capacity')
            ->where('host_establishment_id', $opportunity->host_establishment_id)
            ->where('academic_term_id', $programTerm->academic_term_id)
            ->where('program_id', $programId)->value('capacity');
        if ($capacity === null || $hostCapacity === null) {
            return null;
        }
        $occupied = Placement::query()->whereIn('status', ['pending', 'approved', 'active', 'completed'])
            ->whereHas('studentEnrollment.programTerm', fn ($query) => $query
                ->where('program_id', $programId)->where('academic_term_id', $programTerm->academic_term_id));
        $opportunityRemaining = (int) $capacity - (clone $occupied)->where('opportunity_id', $opportunity->id)->count();
        $hostRemaining = (int) $hostCapacity - (clone $occupied)->where('host_establishment_id', $opportunity->host_establishment_id)->count();
        if (min($opportunityRemaining, $hostRemaining) <= 0) {
            return null;
        }
        $moa = Moa::query()->where('host_establishment_id', $opportunity->host_establishment_id)
            ->where('status', MoaStatus::Active)->whereDate('effective_on', '<=', today())
            ->whereDate('expires_on', '>=', today())
            ->where(fn ($query) => $query->where('is_institution_wide', true)
                ->orWhereHas('programs', fn ($programs) => $programs->whereKey($programId)))
            ->orderByDesc('expires_on')->first();
        if (! $moa) {
            return null;
        }

        return ['program_id' => $programId, 'capacity_remaining' => min($opportunityRemaining, $hostRemaining),
            'opportunity_capacity_remaining' => $opportunityRemaining, 'host_capacity_remaining' => $hostRemaining,
            'moa_id' => $moa->id, 'moa_status' => $moa->status->value,
            'moa_expires_on' => $moa->expires_on->toDateString()];
    }
}
