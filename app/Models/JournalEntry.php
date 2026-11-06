<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;

/**
 * Entrée du journal personnel d'un personnage. Une entrée privée n'est lisible que
 * par son autrice ou son auteur (et par la modération des scènes).
 */
class JournalEntry extends Model
{
    use SoftDeletes;

    public const MAX_LENGTH = 20000;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
        ];
    }

    /**
     * Entrées lisibles par cette personne.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user?->can('scenes.moderer')) {
            return $query;
        }

        return $query->where(fn (Builder $query) => $query
            ->where('is_private', false)
            ->when($user, fn (Builder $query) => $query->orWhere('user_id', $user->getKey())));
    }

    public function isVisibleTo(?User $user): bool
    {
        return ! $this->is_private
            || ($user !== null && ($this->user_id === $user->getKey() || $user->can('scenes.moderer')));
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function heading(): string
    {
        return $this->title ?: ($this->rp_date ?: 'Entrée du '.$this->created_at->locale('fr')->isoFormat('LL'));
    }

    public function html(): HtmlString
    {
        return \App\Support\SceneText::render($this->body);
    }
}
