<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Conversation privée entre joueurs, à deux ou en groupe.
 */
class Conversation extends Model
{
    public const MAX_PARTICIPANTS = 8;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ConversationParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participantFor(?User $user): ?ConversationParticipant
    {
        if ($user === null) {
            return null;
        }

        return $this->participants->firstWhere('user_id', $user->getKey())
            ?? $this->participants()->where('user_id', $user->getKey())->first();
    }

    public function isGroup(): bool
    {
        return $this->participants->count() > 2;
    }

    /**
     * Les autres participants encore présents.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function others(User $user)
    {
        return $this->participants
            ->where('user_id', '!=', $user->getKey())
            ->whereNull('left_at')
            ->map(fn (ConversationParticipant $participant) => $participant->user)
            ->filter()
            ->values();
    }

    public function title(User $viewer): string
    {
        if (filled($this->subject)) {
            return $this->subject;
        }

        $names = $this->others($viewer)->pluck('pseudo');

        return $names->isEmpty() ? 'Conversation' : $names->join(', ', ' et ');
    }

    /**
     * Nombre de conversations avec au moins un message non lu.
     */
    public static function unreadCountFor(User $user): int
    {
        return ConversationParticipant::query()
            ->where('user_id', $user->getKey())
            ->whereNull('left_at')
            ->whereExists(function ($query) use ($user): void {
                $query->select(DB::raw(1))
                    ->from('messages')
                    ->whereColumn('messages.conversation_id', 'conversation_participants.conversation_id')
                    ->whereNull('messages.deleted_at')
                    ->where(fn ($inner) => $inner->whereNull('messages.user_id')->orWhere('messages.user_id', '!=', $user->getKey()))
                    ->where(fn ($inner) => $inner->whereNull('conversation_participants.last_read_message_id')
                        ->orWhereColumn('messages.id', '>', 'conversation_participants.last_read_message_id'));
            })
            ->count();
    }
}
