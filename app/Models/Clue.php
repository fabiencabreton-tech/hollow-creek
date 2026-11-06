<?php

namespace App\Models;

use App\Models\Concerns\HasManagedImage;
use App\Models\Concerns\LogsContentChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * Indice d'une enquête. Public, il figure sur la page de l'enquête ; sinon, seuls
 * les joueurs dont un personnage le détient peuvent le lire.
 */
#[Fillable(['investigation_id', 'title', 'body', 'image_path', 'image_credit', 'is_public', 'is_shareable', 'sort_order'])]
class Clue extends Model
{
    use HasManagedImage, LogsContentChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_shareable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Investigation, $this>
     */
    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }

    /**
     * Personnages qui détiennent l'indice.
     *
     * @return BelongsToMany<Character, $this>
     */
    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class)->withPivot('shared_by_character_id')->withTimestamps();
    }

    /**
     * Personnages de ce joueur qui détiennent l'indice.
     *
     * @return Collection<int, Character>
     */
    public function holdersOwnedBy(?User $user): Collection
    {
        if ($user === null) {
            return collect();
        }

        return $this->characters()->where('characters.user_id', $user->getKey())->get();
    }

    public function isReadableBy(?User $user): bool
    {
        if ($user?->can('enquetes.gerer')) {
            return true;
        }

        if ($this->investigation === null || ! $this->investigation->is_published) {
            return false;
        }

        return $this->is_public || $this->holdersOwnedBy($user)->isNotEmpty();
    }

    public static function contentLabel(): string
    {
        return 'Indice';
    }

    protected static function contentTitleAttribute(): string
    {
        return 'title';
    }
}
