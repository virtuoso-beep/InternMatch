<?php

namespace App;

enum EnrollmentStatus: string
{
    case Enrolled = 'enrolled';
    case Withdrawn = 'withdrawn';
    case Completed = 'completed';
}
