<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PortalNotification extends Notification
{
    /** @param array<string, scalar> $meta extra keys stored with the notification (e.g. for de-duplication) */
    public function __construct(public string $title, public string $body, public array $meta = []) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return ['title' => $this->title, 'body' => $this->body, ...$this->meta];
    }
}
