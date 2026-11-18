<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivationLink extends Notification
{
    use Queueable;

    public function __construct(private readonly string $url, private readonly int $hours) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Activate your InternMatch account')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('An InternMatch account has been created for you at UM Tagum College.')
            ->line('Use the button below to set your password. The link works once and expires in '.$this->hours.' hours.')
            ->action('Set my password', $this->url)
            ->line('If the link has expired, ask your practicum coordinator to send a new one. If you did not expect this e-mail, you can ignore it.');
    }
}
