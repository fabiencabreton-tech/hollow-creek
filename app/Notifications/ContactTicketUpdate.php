<?php

namespace App\Notifications;

use App\Models\ContactTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mails de la bulle de contact : accusé de réception, réponse de l'équipe, nouveau lien.
 * Le texte de la réponse n'y figure pas : il se lit sur la page de suivi.
 */
class ContactTicketUpdate extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactTicket $ticket, public string $trackingUrl, public string $kind) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reference = $this->ticket->reference;
        $message = (new MailMessage)->greeting('Bonjour '.$this->ticket->name.',');

        $message = match ($this->kind) {
            'answered' => $message
                ->subject("Réponse de l’équipe · {$reference}")
                ->line("Une personne de l’équipe de Tsukimori a répondu à votre demande {$reference}."),
            'link' => $message
                ->subject("Votre lien de suivi · {$reference}")
                ->line("Voici un nouveau lien pour suivre votre demande {$reference}. Les liens précédents ne fonctionnent plus."),
            default => $message
                ->subject("Demande reçue · {$reference}")
                ->line("Votre demande « {$this->ticket->subject} » est bien arrivée. Sa référence : {$reference}.")
                ->line('Une personne de l’équipe vous répondra ; vous recevrez un e-mail à chaque réponse.'),
        };

        return $message
            ->action('Suivre ma demande', $this->trackingUrl)
            ->line('Ce lien est personnel : ne le transmettez pas. Il reste valable '.config('hollow.contact.token_days', 30).' jours.')
            ->salutation('L’équipe de Tsukimori');
    }
}
