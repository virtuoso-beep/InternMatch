<?php

namespace App;

enum AccountStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Disabled = 'disabled';
}
