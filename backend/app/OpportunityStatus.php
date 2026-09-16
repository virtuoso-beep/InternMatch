<?php

namespace App;

enum OpportunityStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';
}
