<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Enrolled = 'enrolled';
    case Withdrawn = 'withdrawn';
    case Completed = 'completed';
}
