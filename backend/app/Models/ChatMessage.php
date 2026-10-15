<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::addGlobalScope('participant', fn (Builder $query) => $query->whereHas('conversation'));
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id')->withTrashed();
    }
}
