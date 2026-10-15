<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ChatMessageNotification extends Notification
{
    public function __construct(public int $conversationId) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return ['type' => 'chat.message', 'title' => 'New InternMatch message', 'body' => 'Open your inbox to read a new message.', 'conversation_id' => $this->conversationId];
    }
}
