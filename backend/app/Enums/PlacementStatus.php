<?php

namespace App\Enums;

enum PlacementStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Active = 'active';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    // Ended without the required hours (used by imported historical/synthetic records).
    case Incomplete = 'incomplete';
}
