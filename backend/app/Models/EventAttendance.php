<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['student_enrollment_id', 'program_term_requirement_id', 'program_term_id', 'attended_on', 'confirmed_by', 'confirmed_at'])]
class EventAttendance extends Model
{
    protected function casts(): array
    {
        return ['attended_on' => 'immutable_date:Y-m-d', 'confirmed_at' => 'immutable_datetime'];
    }
}
