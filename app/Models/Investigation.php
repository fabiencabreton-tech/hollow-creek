<?php

namespace App\Models;

use App\Enums\InvestigationStatus;
use App\Models\Concerns\HasManagedImage;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsContentChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Enquête menée par l'équipe : un mystère public et ses indices, publics ou
 * confiés à certains personnages seulement.
 */
#[Fillable(['slug', 'title', 'status', 'summary', 'body', 'image_path', 'image_credit', 'location_id', 'is_published'])]
class Investigation extends Model
{
    use HasManagedImage, HasSlug, LogsContentChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvestigationStatus::class,
            'is_published' => 'boolean',
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
     * @return HasMany<Clue, $this>
     */
    public function clues(): HasMany
    {
        return $this->hasMany(Clue::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    protected static function slugSourceAttribute(): string
    {
        return 'title';
    }

    public static function contentLabel(): string
    {
        return 'Enquête';
    }

    protected static function contentTitleAttribute(): string
    {
        return 'title';
    }
}
