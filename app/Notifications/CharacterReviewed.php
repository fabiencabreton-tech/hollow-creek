<?php

namespace App\Notifications;

use App\Enums\CharacterStatus;
use App\Models\Character;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Décision de l'équipe sur une fiche de personnage.
 */
class CharacterReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Character $character,
        public CharacterStatus $decision,
        public ?string $comment = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->character->fullName();

        $message = (new MailMessage)->greeting('Bonjour '.($notifiable->pseudo ?? '').',');

        $message = match ($this->decision) {
            CharacterStatus::Accepted => $message
                ->subject("Fiche acceptée : {$name}")
                ->line("Bonne nouvelle : la fiche de {$name} est acceptée. Le personnage est désormais jouable à Hollow Creek."),
            CharacterStatus::ChangesRequested => $message
                ->subject("Modifications demandées : {$name}")
                ->line("L’équipe a relu la fiche de {$name} et vous propose quelques modifications avant validation."),
            default => $message
                ->subject("Fiche refusée : {$name}")
                ->line("L’équipe n’a pas pu accepter la fiche de {$name}. L’avatar choisi redevient disponible."),
        };

        if (filled($this->comment)) {
            $message->line('Le mot de l’équipe :')->line('« '.$this->comment.' »');
        }

        return $message
            ->action('Voir mes personnages', route('characters.mine'))
            ->salutation('L’équipe de Hollow Creek');
    }
}
