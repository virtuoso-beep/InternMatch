<?php

namespace App;

enum MoaStatus: string
{
    case Draft = 'draft';
    case PendingSignature = 'pending_signature';
    case Active = 'active';
    case Expired = 'expired';
    case Terminated = 'terminated';
}
