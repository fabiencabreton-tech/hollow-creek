<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\ContactTicket;
use App\Models\User;
use App\Notifications\ContactTicketUpdate;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Bulle de contact : dossiers, liens de suivi, réponses de l'équipe.
 */
class ContactDesk
{
    /**
     * @param  array{name: string, email: string, motif: string, subject: string, body: string}  $data
     */
    public function open(array $data, ?User $user): ContactTicket
    {
        [$ticket, $token] = DB::transaction(function () use ($data, $user): array {
            $ticket = new ContactTicket;
            $ticket->forceFill([
                'reference' => ContactTicket::newReference(),
                'user_id' => $user?->getKey(),
                'name' => $user?->pseudo ?? trim($data['name']),
                'email' => $user?->email ?? mb_strtolower(trim($data['email'])),
                'motif' => $data['motif'],
                'subject' => trim($data['subject']),
                'status' => ContactTicket::STATUS_OPEN,
                'last_activity_at' => now(),
            ])->save();

            $this->addMessage($ticket, 'visiteur', $user, $data['body']);

            return [$ticket, $ticket->issueToken()];
        });

        $this->mailVisitor($ticket, $token, 'opened');

        return $ticket;
    }

    public function visitorReply(ContactTicket $ticket, ?User $user, string $body): void
    {
        DB::transaction(function () use ($ticket, $user, $body): void {
            $this->addMessage($ticket, 'visiteur', $user, $body);
            $ticket->forceFill(['status' => ContactTicket::STATUS_OPEN, 'last_activity_at' => now()])->save();
        });
    }

    public function staffReply(ContactTicket $ticket, User $staff, string $body): void
    {
        $token = DB::transaction(function () use ($ticket, $staff, $body): string {
            $this->addMessage($ticket, 'equipe', $staff, $body);
            $ticket->forceFill([
                'status' => ContactTicket::STATUS_ANSWERED,
                'assigned_to' => $ticket->assigned_to ?? $staff->getKey(),
                'last_activity_at' => now(),
            ])->save();

            // Chaque réponse prolonge la validité du lien de suivi.
            return $ticket->issueToken();
        });

        ActivityLogger::record('contact.reponse', $ticket, $ticket->reference, causer: $staff);

        if ($ticket->user) {
            app(Notifier::class)->send(
                $ticket->user,
                'equipe',
                'L’équipe a répondu à votre demande '.$ticket->reference,
                route('contact.mine.show', $ticket, absolute: false),
                null,
                new ContactTicketUpdate($ticket, $ticket->trackingUrl($token), 'answered'),
            );
        } else {
            $this->mailVisitor($ticket, $token, 'answered');
        }
    }

    public function note(ContactTicket $ticket, User $staff, string $body): void
    {
        $this->addMessage($ticket, 'equipe', $staff, $body, internal: true);
    }

    public function close(ContactTicket $ticket, User $staff): void
    {
        $ticket->forceFill(['status' => ContactTicket::STATUS_CLOSED, 'last_activity_at' => now()])->save();
        ActivityLogger::record('contact.clos', $ticket, $ticket->reference, causer: $staff);
    }

    public function reopen(ContactTicket $ticket): void
    {
        $ticket->forceFill(['status' => ContactTicket::STATUS_OPEN, 'last_activity_at' => now()])->save();
    }

    /**
     * Nouveau lien de suivi, demandé avec la référence et l'adresse e-mail.
     */
    public function resendLink(string $reference, string $email): void
    {
        $ticket = ContactTicket::query()
            ->where('reference', mb_strtoupper(trim($reference)))
            ->where('email', mb_strtolower(trim($email)))
            ->first();

        if ($ticket) {
            $this->mailVisitor($ticket, $ticket->issueToken(), 'link');
        }
    }

    /**
     * Un membre de l'équipe a été actif ces cinq dernières minutes.
     */
    public function staffOnline(): bool
    {
        return Cache::remember('hollow.equipe-en-ligne', 60, function (): bool {
            try {
                return User::permission('administration.acceder')
                    ->whereNull('suspended_at')
                    ->where('last_seen_at', '>=', now()->subMinutes(5))
                    ->exists();
            } catch (\Throwable) {
                return false;
            }
        });
    }

    private function addMessage(ContactTicket $ticket, string $author, ?User $user, string $body, bool $internal = false): ContactMessage
    {
        $message = new ContactMessage;
        $message->forceFill([
            'contact_ticket_id' => $ticket->getKey(),
            'author' => $author,
            'user_id' => $user?->getKey(),
            'body' => trim($body),
            'is_internal' => $internal,
            'created_at' => now(),
        ])->save();

        return $message;
    }

    private function mailVisitor(ContactTicket $ticket, string $token, string $kind): void
    {
        Notification::route('mail', $ticket->email)->notify(new ContactTicketUpdate($ticket, $ticket->trackingUrl($token), $kind));
    }
}
