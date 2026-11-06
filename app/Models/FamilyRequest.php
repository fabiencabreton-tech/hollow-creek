<?php

namespace App\Models;

use App\Enums\FamilyRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande d'un joueur : rejoindre une famille existante avec un personnage accepté,
 * ou proposer une nouvelle famille. L'équipe accepte ou refuse.
 */
class FamilyRequest extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FamilyRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function isProposal(): bool
    {
        return $this->family_id === null;
    }

    public function label(): string
    {
        return $this->isProposal()
            ? 'Nouvelle famille : '.$this->proposed_name
            : 'Rejoindre '.($this->family?->name ?? 'une famille');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
