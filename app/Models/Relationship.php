<?php

namespace App\Models;

use App\Enums\RelationshipKind;
use App\Enums\RelationshipStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lien entre deux personnages, proposé par l'un, accepté par l'autre.
 * Jamais créé automatiquement. Écritures par App\Services\RelationshipService.
 */
class Relationship extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => RelationshipKind::class,
            'status' => RelationshipStatus::class,
            'responded_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Relationship>  $query
     * @return Builder<Relationship>
     */
    public function scopeInvolving(Builder $query, Character $character): Builder
    {
        return $query->where(fn ($inner) => $inner->where('character_id', $character->getKey())->orWhere('target_character_id', $character->getKey()));
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'target_character_id')->withTrashed();
    }

    public function label(): string
    {
        return filled($this->label) ? $this->label : $this->kind->getLabel();
    }

    /**
     * L'autre personnage du lien, vu depuis $character.
     */
    public function otherSide(Character $character): ?Character
    {
        return $this->character_id === $character->getKey() ? $this->target : $this->character;
    }

    /**
     * Description rédigée par le joueur de $character.
     */
    public function descriptionFor(Character $character): ?string
    {
        return $this->character_id === $character->getKey() ? $this->description : $this->target_description;
    }
}
