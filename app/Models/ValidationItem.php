<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne de la file de validation : une vue en lecture seule qui rassemble les fiches,
 * les réservations d'avatar, les demandes de famille et les inscriptions en attente.
 * Voir App\Services\ValidationDesk.
 */
class ValidationItem extends Model
{
    public const KINDS = [
        'fiche' => 'Fiche de personnage',
        'avatar' => 'Réservation d’avatar',
        'famille' => 'Demande de famille',
        'inscription' => 'Inscription',
    ];

    protected $table = 'validation_items';

    protected $primaryKey = 'uid';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uid' => 'integer',
            'item_id' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }
}
