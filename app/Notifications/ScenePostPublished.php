<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\Scene;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Nouvelle réponse dans une scène ; en tour par tour, annonce aussi « à vous ».
 * Le texte de la réponse n'est pas recopié dans l'e-mail : il se lit sur le site.
 */
class ScenePostPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Scene $scene, public Post $post, public bool $isTheirTurn) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->scene->title;

        $message = (new MailMessage)
            ->subject($this->isTheirTurn ? "À vous d’écrire : « {$title} »" : "Nouvelle réponse : « {$title} »")
            ->greeting('Bonjour '.($notifiable->pseudo ?? '').',')
            ->line($this->post->character_name.' a répondu dans la scène « '.$title.' ».');

        if ($this->isTheirTurn) {
            $message->line('C’est maintenant à vous d’écrire.');
        }

        return $message
            ->action('Lire la réponse', route('scenes.unread', $this->scene))
            ->line('Vous pouvez choisir quels e-mails recevoir dans « Mon compte ».')
            ->salutation('L’équipe de Tsukimori');
    }
}
