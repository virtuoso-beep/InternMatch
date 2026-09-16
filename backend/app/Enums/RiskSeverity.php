<?php

namespace App\Enums;

enum RiskSeverity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Critical = 'critical';
}
