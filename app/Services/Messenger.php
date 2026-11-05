<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Messagerie entre joueurs : ouverture, envoi, lecture, archivage.
 */
class Messenger
{
    /**
     * @param  array<int, string>  $pseudos
     */
    public function start(User $author, array $pseudos, ?string $subject, string $body): Conversation
    {
        $recipients = $this->recipients($author, $pseudos);

        // Une conversation à deux existe déjà : on la reprend plutôt que d'en ouvrir une autre.
        if ($recipients->count() === 1 && blank($subject)) {
            $existing = $this->existingPrivate($author, $recipients->first());

            if ($existing) {
                $this->send($existing, $author, $body);

                return $existing;
            }
        }

        $conversation = DB::transaction(function () use ($author, $recipients, $subject): Conversation {
            $conversation = new Conversation;
            $conversation->forceFill([
                'subject' => filled($subject) ? trim($subject) : null,
                'created_by' => $author->getKey(),
            ])->save();

            foreach ($recipients->push($author) as $user) {
                $participant = new ConversationParticipant;
                $participant->forceFill([
                    'conversation_id' => $conversation->getKey(),
                    'user_id' => $user->getKey(),
                    'created_at' => now(),
                ])->save();
            }

            return $conversation;
        });

        $this->send($conversation->load('participants.user'), $author, $body);

        return $conversation;
    }

    public function send(Conversation $conversation, User $author, string $body): Message
    {
        $conversation->loadMissing('participants.user');

        $message = DB::transaction(function () use ($conversation, $author, $body): Message {
            $previousId = (int) Message::query()->where('conversation_id', $conversation->getKey())->max('id');

            $message = new Message;
            $message->forceFill([
                'conversation_id' => $conversation->getKey(),
                'user_id' => $author->getKey(),
                'body' => trim($body),
                'created_at' => now(),
            ])->save();

            $conversation->forceFill(['last_message_at' => now()])->save();

            foreach ($conversation->participants as $participant) {
                if ($participant->left_at !== null) {
                    continue;
                }

                if ($participant->user_id === $author->getKey()) {
                    $participant->forceFill(['last_read_message_id' => $message->getKey(), 'archived_at' => null])->save();

                    continue;
                }

                // Un message relance la conversation archivée chez les autres.
                $wasUpToDate = $previousId === 0 || ($participant->last_read_message_id ?? 0) >= $previousId;
                $participant->forceFill(['archived_at' => null])->save();

                // Une seule notification tant que les messages précédents ne sont pas lus.
                if ($wasUpToDate && $participant->user && ! UserBlock::between($participant->user, $author)) {
                    $recipient = $participant->user;
                    DB::afterCommit(fn () => app(Notifier::class)->send(
                        $recipient,
                        'message',
                        'Nouveau message de '.$author->pseudo,
                        route('messages.show', $conversation, absolute: false),
                        filled($conversation->subject) ? 'Conversation : '.$conversation->subject : null,
                    ));
                }
            }

            return $message;
        });

        return $message;
    }

    public function markRead(Conversation $conversation, User $user): void
    {
        $lastId = Message::query()->where('conversation_id', $conversation->getKey())->max('id');
        $participant = $conversation->participantFor($user);

        if ($participant && $lastId && ($participant->last_read_message_id ?? 0) < $lastId) {
            $participant->forceFill(['last_read_message_id' => $lastId])->save();
        }
    }

    public function toggleArchive(Conversation $conversation, User $user): bool
    {
        $participant = $conversation->participantFor($user);
        $archived = $participant->archived_at === null;
        $participant->forceFill(['archived_at' => $archived ? now() : null])->save();

        return $archived;
    }

    public function leave(Conversation $conversation, User $user): void
    {
        $conversation->participantFor($user)?->forceFill(['left_at' => now()])->save();
    }

    /**
     * @param  array<int, string>  $pseudos
     * @return Collection<int, User>
     */
    private function recipients(User $author, array $pseudos): Collection
    {
        $names = collect($pseudos)->map(fn ($name) => trim((string) $name))->filter()->unique(fn ($name) => mb_strtolower($name))->values();

        if ($names->isEmpty()) {
            throw ValidationException::withMessages(['recipients' => 'Indiquez au moins un destinataire.']);
        }

        if ($names->count() > Conversation::MAX_PARTICIPANTS - 1) {
            throw ValidationException::withMessages(['recipients' => 'Une conversation réunit au plus '.Conversation::MAX_PARTICIPANTS.' joueurs.']);
        }

        $users = User::query()->whereIn('pseudo', $names->all())->whereNull('suspended_at')->get();
        $missing = $names->reject(fn ($name) => $users->contains(fn (User $user) => mb_strtolower($user->pseudo) === mb_strtolower($name)));

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages(['recipients' => 'Joueur introuvable : '.$missing->join(', ').'.']);
        }

        $users = $users->reject(fn (User $user) => $user->is($author))->values();

        if ($users->isEmpty()) {
            throw ValidationException::withMessages(['recipients' => 'Indiquez au moins un autre joueur.']);
        }

        $blocked = UserBlock::idsFor($author);

        if ($users->contains(fn (User $user) => in_array($user->getKey(), $blocked, true))) {
            throw ValidationException::withMessages(['recipients' => 'Vous ne pouvez pas écrire à un joueur avec qui un blocage existe.']);
        }

        return $users;
    }

    private function existingPrivate(User $author, User $other): ?Conversation
    {
        return Conversation::query()
            ->whereNull('subject')
            ->whereHas('participants', fn ($query) => $query->where('user_id', $author->getKey())->whereNull('left_at'))
            ->whereHas('participants', fn ($query) => $query->where('user_id', $other->getKey())->whereNull('left_at'))
            ->has('participants', '=', 2)
            ->latest('last_message_at')
            ->first();
    }
}
