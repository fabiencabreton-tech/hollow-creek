<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Dossier ouvert depuis la bulle de contact (référence HC-7F3K).
 * Une personne de l'équipe répond toujours elle-même : aucune réponse automatique.
 */
class ContactTicket extends Model
{
    public const STATUS_OPEN = 'ouvert';

    public const STATUS_ANSWERED = 'repondu';

    public const STATUS_CLOSED = 'clos';

    public const STATUS_LABELS = [
        'ouvert' => 'En attente de l’équipe',
        'repondu' => 'Répondu',
        'clos' => 'Clos',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public static function newReference(): string
    {
        // Sans caractères ambigus (0/O, 1/I/L).
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        do {
            $reference = 'HC-'.collect(range(1, 4))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('');
        } while (static::query()->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Nouveau lien de suivi : le jeton en clair n'est renvoyé qu'une fois, pour l'e-mail.
     */
    public function issueToken(): string
    {
        $token = Str::random(48);

        $this->forceFill([
            'token_hash' => hash('sha256', $token),
            'token_expires_at' => now()->addDays((int) config('hollow.contact.token_days', 30)),
        ])->save();

        return $token;
    }

    public function tokenMatches(string $token): bool
    {
        return $this->token_hash !== null
            && $this->token_expires_at !== null
            && $this->token_expires_at->isFuture()
            && hash_equals($this->token_hash, hash('sha256', $token));
    }

    public function trackingUrl(string $token): string
    {
        return route('contact.track', ['reference' => $this->reference, 'token' => $token]);
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /**
     * @return HasMany<ContactMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ContactMessage::class)->orderBy('id');
    }

    /**
     * @return HasMany<ContactMessage, $this>
     */
    public function publicMessages(): HasMany
    {
        return $this->messages()->where('is_internal', false);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function motifLabel(): string
    {
        return (string) config("hollow.contact.motifs.{$this->motif}", $this->motif);
    }
}
