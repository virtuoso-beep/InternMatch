<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use App\Services\ChatAccess;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return ChatAccess::enabled() && ChatAccess::scope(Conversation::withoutGlobalScope('participant')->whereKey($conversation->id), $user)->exists();
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation) && ChatAccess::writable($conversation);
    }
}
