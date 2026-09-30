<?php

namespace App\Services;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\HostEstablishment;
use App\Models\Opportunity;
use App\Models\Placement;
use App\Models\ProgramTerm;
use App\Models\Recommendation;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Notifications\PortalNotification;
use Illuminate\Support\Facades\DB;

class CohortAllocation
{
    public function __construct(private PlacementReadiness $readiness, private OpportunityEligibility $eligibility) {}

    public function authorize(User $actor, ProgramTerm $term): void
    {
        abort_unless($actor->role === Role::Coordinator && $actor->hasPermission(Permission::DecidePlacements) && $actor->isAssignedToProgramTerm($term->id), 403);
    }

    public function generate(User $actor, ProgramTerm $term): int
    {
        $this->authorize($actor, $term);

        return DB::transaction(function () use ($actor, $term) {
            ProgramTerm::whereKey($term->id)->lockForUpdate()->firstOrFail();
            $enrollments = StudentEnrollment::where('program_term_id', $term->id)->orderBy('id')->get();
            $choices = [];
            $reasons = [];
            $selected = [];
            $hostReserved = [];
            $opportunityReserved = [];
            foreach ($enrollments as $enrollment) {
                $reasons[$enrollment->id] = $this->readiness->reasons($enrollment);
                if ($reasons[$enrollment->id]) {
                    continue;
                }
                $generation = DB::table('recommendation_generations')->where('student_enrollment_id', $enrollment->id)->orderByDesc('id')->value('generation_id');
                if (! $generation) {
                    $reasons[$enrollment->id][] = 'Generate current recommendations first.';

                    continue;
                }
                foreach (Recommendation::where('student_enrollment_id', $enrollment->id)->where('generation_id', $generation)->get() as $recommendation) {
                    $judgment = DB::table('placement_judgments')->where('recommendation_id', $recommendation->id)->whereNotNull('approved_at')->orderByDesc('id')->first();
                    if ($judgment && $judgment->judgment !== 'suitable') {
                        continue;
                    }
                    $opportunity = Opportunity::find($recommendation->opportunity_id);
                    $facts = $opportunity ? $this->eligibility->inspect($enrollment, $opportunity) : null;
                    if ($facts) {
                        $choices[] = compact('enrollment', 'recommendation', 'opportunity', 'facts');
                    }
                }
            }
            // Deterministic feasible greedy fallback; this does not claim a globally optimal assignment or trained ML.
            usort($choices, fn ($a, $b) => ($b['recommendation']->similarity_score <=> $a['recommendation']->similarity_score)
                ?: (($a['recommendation']->distance_km ?? INF) <=> ($b['recommendation']->distance_km ?? INF))
                ?: ($a['enrollment']->id <=> $b['enrollment']->id) ?: ($a['opportunity']->id <=> $b['opportunity']->id));
            foreach ($choices as $choice) {
                ['enrollment' => $e, 'opportunity' => $o, 'facts' => $facts] = $choice;
                if (isset($selected[$e->id]) || ($hostReserved[$o->host_establishment_id] ?? 0) >= $facts['host_capacity_remaining'] || ($opportunityReserved[$o->id] ?? 0) >= $facts['opportunity_capacity_remaining']) {
                    continue;
                }
                $selected[$e->id] = $choice;
                $hostReserved[$o->host_establishment_id] = ($hostReserved[$o->host_establishment_id] ?? 0) + 1;
                $opportunityReserved[$o->id] = ($opportunityReserved[$o->id] ?? 0) + 1;
            }
            $id = DB::table('placement_proposals')->insertGetId(['program_term_id' => $term->id, 'created_by' => $actor->id, 'status' => 'under_review', 'constraints_snapshot' => json_encode(['method' => 'cosine_desc_distance_asc_greedy_v1', 'constraints' => ['academic eligibility', 'approved pre-deployment requirements', 'program and term', 'active MOA', 'host/program and opportunity/program capacity', 'approved suitability judgments when available'], 'automatic_finalization' => false]), 'created_at' => now(), 'updated_at' => now()]);
            foreach ($enrollments as $enrollment) {
                $choice = $selected[$enrollment->id] ?? null;
                DB::table('placement_proposal_items')->insert(['placement_proposal_id' => $id, 'program_term_id' => $term->id, 'student_enrollment_id' => $enrollment->id, 'recommendation_id' => $choice['recommendation']->id ?? null, 'opportunity_id' => $choice['opportunity']->id ?? null, 'reason' => $choice ? 'Feasible recommendation selected; coordinator decision required.' : implode(' ', $reasons[$enrollment->id] ?: ['No eligible recommendation with remaining cohort capacity.']), 'created_at' => now(), 'updated_at' => now()]);
            }
            Audit::record($actor, 'allocation.generated', $term, ['proposal_id' => $id, 'assigned' => count($selected), 'unassigned' => $enrollments->count() - count($selected)], $term->program_id);

            return $id;
        }, 3);
    }

    public function decide(User $actor, int $itemId, array $data): ?Placement
    {
        $item = DB::table('placement_proposal_items')->find($itemId);
        abort_unless($item, 404);
        $term = ProgramTerm::findOrFail($item->program_term_id);
        $this->authorize($actor, $term);

        return DB::transaction(function () use ($actor, $itemId, $term, $data) {
            ProgramTerm::whereKey($term->id)->lockForUpdate()->firstOrFail();
            $item = DB::table('placement_proposal_items')->where('id', $itemId)->lockForUpdate()->first();
            abort_unless($item->review_status === 'pending', 409, 'This proposal item has already been reviewed.');
            $enrollment = StudentEnrollment::whereKey($item->student_enrollment_id)->lockForUpdate()->firstOrFail();
            $placement = null;
            if ($data['decision'] === 'approve') {
                $reasons = $this->readiness->reasons($enrollment);
                abort_if($reasons !== [], 422, implode(' ', $reasons));
                $opportunity = Opportunity::findOrFail($data['opportunity_id'] ?? $item->opportunity_id);
                HostEstablishment::whereKey($opportunity->host_establishment_id)->lockForUpdate()->firstOrFail();
                $opportunity = Opportunity::whereKey($opportunity->id)->lockForUpdate()->firstOrFail();
                $facts = $this->eligibility->inspect($enrollment, $opportunity);
                abort_unless($facts, 422, 'Opportunity capacity, program, term or agreement eligibility changed.');
                abort_if($data['ends_on'] > $facts['moa_expires_on'], 422, 'The MOA must cover the entire placement period.');
                abort_if($data['starts_on'] < $facts['moa_effective_on'], 422, 'Placement starts before the MOA takes effect.');
                abort_if($opportunity->starts_on && $data['starts_on'] < $opportunity->starts_on->toDateString(), 422, 'Placement starts before the opportunity period.');
                abort_if($opportunity->ends_on && $data['ends_on'] > $opportunity->ends_on->toDateString(), 422, 'Placement ends after the opportunity period.');
                $supervisor = User::findOrFail($data['supervisor_id']);
                abort_unless($supervisor->role === Role::Supervisor && $supervisor->status->value === 'active' && $supervisor->isAssignedToHost($opportunity->host_establishment_id), 422, 'Select an active supervisor assigned to this host.');
                $placement = Placement::create(['student_enrollment_id' => $enrollment->id, 'host_establishment_id' => $opportunity->host_establishment_id, 'opportunity_id' => $opportunity->id, 'moa_id' => $facts['moa_id'], 'supervisor_id' => $supervisor->id, 'status' => 'approved', 'starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on'], 'placement_proposal_item_id' => $item->id]);
                $placement->decisions()->create(['decided_by' => $actor->id, 'decision' => 'approve', 'from_opportunity_id' => $item->opportunity_id, 'to_opportunity_id' => $opportunity->id, 'reason' => $data['reason'], 'decided_at' => now()]);
                $enrollment->student->user->notify(new PortalNotification('Placement approved', 'Your coordinator approved your placement at '.$opportunity->hostEstablishment->name.'.'));
                $supervisor->notify(new PortalNotification('Intern assigned', 'A coordinator assigned an intern to your host establishment.'));
            }
            DB::table('placement_proposal_items')->where('id', $itemId)->update(['review_status' => $placement ? 'approved' : 'rejected', 'review_reason' => $data['reason'], 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'updated_at' => now()]);
            $items = DB::table('placement_proposal_items')->where('placement_proposal_id', $item->placement_proposal_id);
            if (! (clone $items)->where('review_status', 'pending')->exists()) {
                DB::table('placement_proposals')->where('id', $item->placement_proposal_id)->update(['status' => (clone $items)->where('review_status', 'approved')->exists() ? 'approved' : 'rejected', 'updated_at' => now()]);
            }
            Audit::record($actor, 'allocation.'.$data['decision'], $enrollment, ['proposal_item_id' => $itemId, 'placement_id' => $placement?->id, 'reason' => $data['reason'], 'selected_opportunity_id' => $placement?->opportunity_id], $term->program_id);

            return $placement;
        }, 3);
    }
}
