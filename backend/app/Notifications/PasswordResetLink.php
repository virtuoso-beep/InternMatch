<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetLink extends Notification
{
    use Queueable;

    public function __construct(private readonly string $url, private readonly int $minutes) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your InternMatch password')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('We received a request to reset the password of your InternMatch account at UM Tagum College.')
            ->line('Use the button below to choose a new password. The link works once and expires in '.$this->minutes.' minutes.')
            ->action('Reset my password', $this->url)
            ->line('If you did not ask for this, you can ignore this e-mail. Your password stays the same.');
    }
}
