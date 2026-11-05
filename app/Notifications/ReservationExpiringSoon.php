<?php

namespace App\Notifications;

use App\Models\FaceclaimClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Rappel 48 heures avant la fin d'une réservation.
 */
class ReservationExpiringSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public FaceclaimClaim $claim) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->claim->faceclaim->name;

        return (new MailMessage)
            ->subject("Votre réservation de {$name} se termine bientôt")
            ->greeting('Bonjour '.($notifiable->pseudo ?? $this->claim->guest_pseudo ?? '').',')
            ->line("La réservation de {$name} prend fin le ".$this->claim->expires_at?->locale('fr')->isoFormat('LLLL').'.')
            ->line($this->claim->canBeExtended()
                ? 'Vous pouvez la prolonger une fois depuis le bottin, ou envoyer votre fiche d’ici là.'
                : 'Envoyez votre fiche d’ici là : ensuite, la célébrité redeviendra disponible.')
            ->action('Voir le bottin', route('faceclaims.index'))
            ->salutation('L’équipe de Tsukimori');
    }
}
