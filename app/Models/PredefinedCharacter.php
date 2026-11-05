<?php

namespace App\Models;

use App\Enums\PredefinedStatus;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsContentChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rôle proposé par l'équipe, que les joueurs peuvent reprendre (« Je veux ce rôle »).
 */
#[Fillable(['slug', 'title', 'first_name', 'last_name', 'species_id', 'family_id', 'age_range', 'suggested_faceclaims', 'summary', 'identity', 'history', 'personality', 'relations', 'story_hooks', 'status', 'character_id', 'sort_order', 'is_published'])]
class PredefinedCharacter extends Model
{
    use HasSlug, LogsContentChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PredefinedStatus::class,
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @return BelongsTo<Species, $this>
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function isOpen(): bool
    {
        return $this->status === PredefinedStatus::Open;
    }

    protected static function slugSourceAttribute(): string
    {
        return 'title';
    }

    public static function contentLabel(): string
    {
        return 'Prédéfini';
    }

    protected static function contentTitleAttribute(): string
    {
        return 'title';
    }
}
