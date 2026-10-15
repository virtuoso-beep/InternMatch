<?php

namespace App\Models;

use App\Services\ChatAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::addGlobalScope('participant', fn (Builder $query) => ChatAccess::scope($query, auth()->user()));
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function placement()
    {
        return $this->belongsTo(Placement::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }
}
