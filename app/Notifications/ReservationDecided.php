<?php

namespace App\Notifications;

use App\Enums\ClaimStatus;
use App\Models\FaceclaimClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Réponse de l'équipe à une demande de réservation du bottin.
 */
class ReservationDecided extends Notification implements ShouldQueue
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
        $message = (new MailMessage)->greeting('Bonjour '.($notifiable->pseudo ?? $this->claim->guest_pseudo ?? '').',');

        if ($this->claim->status === ClaimStatus::Reserved) {
            $message->subject("Réservation acceptée : {$name}")
                ->line("{$name} vous est réservé(e) jusqu’au ".$this->claim->expires_at?->locale('fr')->isoFormat('LL').'.')
                ->line('Créez votre fiche avant cette date ; vous pourrez prolonger la réservation une fois.');

            if ($this->claim->user_id === null) {
                $message->line('Inscrivez-vous avec cette même adresse e-mail : la réservation sera rattachée à votre compte dès l’adresse confirmée.');
            }
        } else {
            $message->subject("Réservation refusée : {$name}")
                ->line("L’équipe n’a pas pu réserver {$name} pour vous.");

            if (filled($this->claim->note)) {
                $message->line('« '.$this->claim->note.' »');
            }
        }

        return $message->action('Voir le bottin', route('faceclaims.index'))->salutation('L’équipe de Hollow Creek');
    }
}
