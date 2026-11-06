<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Signalement d'un contenu. L'équipe ne voit que l'extrait copié au moment du signalement.
 */
class Report extends Model
{
    public const STATUS_OPEN = 'ouvert';

    public const STATUS_HANDLED = 'traite';

    public const STATUS_DISMISSED = 'sans_suite';

    /**
     * Contenus signalables : nom court ⇒ modèle.
     *
     * @var array<string, class-string<Model>>
     */
    public const TYPES = [
        'reponse' => Post::class,
        'message' => Message::class,
        'profil' => Character::class,
        'murmure' => FeedPost::class,
        'commentaire' => FeedComment::class,
        'annonce' => ClassifiedAd::class,
        'journal' => JournalEntry::class,
    ];

    public const TYPE_LABELS = [
        'reponse' => 'Réponse dans une scène',
        'message' => 'Message privé',
        'profil' => 'Profil de personnage',
        'murmure' => 'Publication',
        'commentaire' => 'Commentaire',
        'annonce' => 'Petite annonce',
        'journal' => 'Entrée de journal',
    ];

    public const STATUS_LABELS = [
        'ouvert' => 'À traiter',
        'traite' => 'Traité',
        'sans_suite' => 'Sans suite',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function target(): ?Model
    {
        $class = self::TYPES[$this->reportable_type] ?? null;

        if ($class === null) {
            return null;
        }

        $query = $class::query();

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($class), true)) {
            $query->withTrashed();
        }

        return $query->find($this->reportable_id);
    }

    public function reasonLabel(): string
    {
        return (string) config("hollow.reports.reasons.{$this->reason}", $this->reason);
    }
}
