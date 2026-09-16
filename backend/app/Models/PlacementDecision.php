<?php

namespace App\Models;

use App\Enums\PlacementDecisionType;
use Database\Factories\PlacementDecisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['placement_id', 'decided_by', 'decision', 'from_opportunity_id', 'to_opportunity_id', 'reason', 'decided_at'])]
class PlacementDecision extends Model
{
    /** @use HasFactory<PlacementDecisionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'decision' => PlacementDecisionType::class,
            'decided_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Placement, $this> */
    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    /** @return BelongsTo<User, $this> */
    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @return BelongsTo<Opportunity, $this> */
    public function fromOpportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'from_opportunity_id');
    }

    /** @return BelongsTo<Opportunity, $this> */
    public function toOpportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'to_opportunity_id');
    }
}
