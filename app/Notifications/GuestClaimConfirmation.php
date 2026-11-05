<?php

namespace App\Notifications;

use App\Models\FaceclaimClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirmation d'une demande d'avatar faite sans compte.
 */
class GuestClaimConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public FaceclaimClaim $claim, public string $url) {}

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
            ->subject("Confirmez votre demande : {$name}")
            ->greeting('Bonjour '.$this->claim->guest_pseudo.',')
            ->line("Vous avez demandé à réserver {$name} comme avatar sur Hollow Creek.")
            ->line('Confirmez votre adresse pour que l’équipe examine la demande. Sans confirmation sous 48 heures, elle est annulée.')
            ->action('Confirmer ma demande', $this->url)
            ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez simplement cet e-mail.')
            ->salutation('L’équipe de Hollow Creek');
    }
}
