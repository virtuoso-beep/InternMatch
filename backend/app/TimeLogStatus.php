<?php

namespace App;

enum TimeLogStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Flagged = 'flagged';
}
