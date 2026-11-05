<?php

namespace App\Models;

use App\Enums\CharacterStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Recensement d'activité : chaque joueur confirme les personnages qu'il joue encore.
 */
#[Fillable(['title', 'message', 'ends_at'])]
class Census extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ends_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Census>  $query
     * @return Builder<Census>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('closed_at')->where('ends_at', '>', now());
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null && $this->ends_at->isFuture();
    }

    /**
     * @return HasMany<CensusResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(CensusResponse::class);
    }

    /**
     * Personnages acceptés au lancement et non confirmés (les absences annoncées sont à part).
     *
     * @return Collection<int, Character>
     */
    public function missingCharacters(): Collection
    {
        return Character::query()
            ->where('status', CharacterStatus::Accepted->value)
            ->where(fn ($query) => $query->whereNull('accepted_at')->orWhere('accepted_at', '<=', $this->created_at))
            ->whereNotIn('id', $this->responses()->select('character_id'))
            ->with('user')
            ->orderBy('first_name')
            ->get();
    }
}
