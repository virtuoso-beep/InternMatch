<?php

namespace App;

enum PlacementStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Active = 'active';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
