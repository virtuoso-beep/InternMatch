<?php

namespace App\Enums;

enum PlacementDecisionType: string
{
    case Approve = 'approve';
    case Reject = 'reject';
    case Reassign = 'reassign';
    case Cancel = 'cancel';
}
