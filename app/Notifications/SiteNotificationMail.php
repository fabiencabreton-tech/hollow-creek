<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail simple qui reprend une notification du site.
 */
class SiteNotificationMail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $title, public ?string $body, public string $path) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->title)
            ->greeting('Bonjour '.($notifiable->pseudo ?? '').',')
            ->line($this->title);

        if (filled($this->body)) {
            $message->line($this->body);
        }

        return $message
            ->action('Voir sur Tsukimori', url($this->path))
            ->line('Vous pouvez choisir quels e-mails recevoir dans « Mon compte ».')
            ->salutation('L’équipe de Tsukimori');
    }
}
