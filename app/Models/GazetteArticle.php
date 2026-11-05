<?php

namespace App\Models;

use App\Enums\GazetteRubric;
use App\Models\Concerns\HasManagedImage;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsContentChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Article de The Hollow Creek Gazette, écrit par l'équipe et signé d'un nom fictif
 * ou de « La rédaction ».
 */
#[Fillable(['slug', 'title', 'rubric', 'byline', 'rp_date', 'excerpt', 'body', 'image_path', 'image_credit', 'location_id', 'event_id', 'author_id', 'is_featured', 'status', 'published_at'])]
class GazetteArticle extends Model
{
    use HasManagedImage, HasSlug, LogsContentChanges;

    public const STATUS_DRAFT = 'brouillon';

    public const STATUS_PUBLISHED = 'publie';

    protected static function booted(): void
    {
        // La date de parution est fixée à la première publication.
        static::saving(function (GazetteArticle $article): void {
            if ($article->status === self::STATUS_PUBLISHED && $article->published_at === null) {
                $article->published_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rubric' => GazetteRubric::class,
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    protected static function slugSourceAttribute(): string
    {
        return 'title';
    }

    public static function contentLabel(): string
    {
        return 'Article de la Gazette';
    }

    protected static function contentTitleAttribute(): string
    {
        return 'title';
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
