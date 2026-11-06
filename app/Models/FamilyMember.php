<?php

namespace App\Models;

use App\Enums\CharacterStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Nœud de l'arbre généalogique d'une famille : un personnage joué, ou un parent
 * non joué (ancêtre, membre disparu) ajouté par l'équipe.
 */
#[Fillable(['family_id', 'character_id', 'name', 'note', 'parent_id', 'second_parent_id', 'spouse_id', 'is_deceased', 'sort_order'])]
class FamilyMember extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_deceased' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * @return BelongsTo<FamilyMember, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<FamilyMember, $this>
     */
    public function secondParent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'second_parent_id');
    }

    /**
     * @return BelongsTo<FamilyMember, $this>
     */
    public function spouse(): BelongsTo
    {
        return $this->belongsTo(self::class, 'spouse_id');
    }

    /**
     * @return HasMany<FamilyMember, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Nom affiché : celui du personnage quand il est joué.
     */
    public function displayName(): string
    {
        return $this->character?->fullName() ?? $this->name;
    }

    /**
     * Le nœud renvoie vers un profil public (personnage accepté).
     */
    public function isPlayed(): bool
    {
        return $this->character !== null && $this->character->status === CharacterStatus::Accepted;
    }

    /**
     * Identifiants de ce nœud et de tous ses descendants (pour éviter les boucles).
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $ids = [$this->getKey()];
        $frontier = [$this->getKey()];

        while ($frontier !== []) {
            $next = self::query()
                ->where('family_id', $this->family_id)
                ->where(fn ($query) => $query->whereIn('parent_id', $frontier)->orWhereIn('second_parent_id', $frontier))
                ->pluck('id')
                ->diff($ids)
                ->values()
                ->all();
            $ids = array_merge($ids, $next);
            $frontier = $next;
        }

        return $ids;
    }
}
