<?php

namespace App\Policies;

use App\Enums\EvaluationStatus;
use App\Enums\Permission;
use App\Models\Evaluation;
use App\Models\Placement;
use App\Models\User;

class EvaluationPolicy
{
    public function view(User $user, Evaluation $evaluation): bool
    {
        return (new PlacementPolicy)->view($user, $evaluation->placement)
            && ($evaluation->status === EvaluationStatus::Submitted || $evaluation->evaluator_id === $user->id);
    }

    public function create(User $user, Placement $placement): bool
    {
        return $user->hasPermission(Permission::SubmitEvaluations)
            && (new PlacementPolicy)->monitor($user, $placement);
    }

    public function update(User $user, Evaluation $evaluation): bool
    {
        return $evaluation->status === EvaluationStatus::Draft
            && $evaluation->evaluator_id === $user->id
            && $this->create($user, $evaluation->placement);
    }

    public function submit(User $user, Evaluation $evaluation): bool
    {
        return $this->update($user, $evaluation);
    }
}
