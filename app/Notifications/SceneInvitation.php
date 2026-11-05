<?php

namespace App\Notifications;

use App\Models\Scene;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invitation dans une scène : le joueur choisit ensuite le personnage avec lequel la rejoindre.
 */
class SceneInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Scene $scene, public User $invitedBy) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invitation : « '.$this->scene->title.' »')
            ->greeting('Bonjour '.($notifiable->pseudo ?? '').',')
            ->line($this->invitedBy->pseudo.' vous invite à écrire la scène « '.$this->scene->title.' ».')
            ->line('Acceptez l’invitation avec le personnage de votre choix, ou déclinez-la.')
            ->action('Voir l’invitation', route('scenes.show', $this->scene))
            ->salutation('L’équipe de Hollow Creek');
    }
}
