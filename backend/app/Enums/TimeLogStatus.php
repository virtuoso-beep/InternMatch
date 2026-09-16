<?php

namespace App\Enums;

enum TimeLogStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Flagged = 'flagged';
}
