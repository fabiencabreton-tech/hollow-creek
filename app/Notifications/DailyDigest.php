<?php

namespace App\Notifications;

use App\Models\UserNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Résumé quotidien : la liste des notifications du jour, sans leur contenu.
 */
class DailyDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, UserNotification>  $items
     */
    public function __construct(public array $items, public int $total) {}

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
            ->subject('Tsukimori : '.$this->total.' '.($this->total > 1 ? 'nouveautés' : 'nouveauté'))
            ->greeting('Bonjour '.($notifiable->pseudo ?? '').',')
            ->line('Voici ce qui s’est passé depuis votre dernier résumé :');

        foreach ($this->items as $item) {
            $message->line('• '.$item->title);
        }

        if ($this->total > count($this->items)) {
            $message->line('… et '.($this->total - count($this->items)).' autres.');
        }

        return $message
            ->action('Voir mes notifications', route('notifications.index'))
            ->salutation('L’équipe de Tsukimori');
    }
}
