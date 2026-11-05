<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Jobs\AnnounceEvent;
use App\Models\Concerns\HasManagedImage;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsContentChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Événement de l'équipe : les personnages s'y inscrivent, les scènes et articles s'y rattachent.
 */
#[Fillable(['slug', 'title', 'summary', 'body', 'rp_date', 'location_id', 'status', 'registrations_open', 'image_path', 'image_credit', 'is_published', 'sort_order'])]
class Event extends Model
{
    use HasManagedImage, HasSlug, LogsContentChanges;

    protected static function booted(): void
    {
        static::saving(function (Event $event): void {
            if ($event->is_published && $event->published_at === null) {
                $event->published_at = now();
            }
        });

        // Première publication : tous les joueurs sont prévenus.
        static::saved(function (Event $event): void {
            if ($event->wasChanged('published_at') || ($event->wasRecentlyCreated && $event->published_at !== null)) {
                if ($event->getOriginal('published_at') === null && $event->published_at !== null) {
                    AnnounceEvent::dispatch($event)->afterCommit();
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'registrations_open' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function slugSourceAttribute(): string
    {
        return 'title';
    }

    public static function contentLabel(): string
    {
        return 'Événement';
    }

    protected static function contentTitleAttribute(): string
    {
        return 'title';
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->published()->whereIn('status', [EventStatus::Announced->value, EventStatus::Ongoing->value]);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return HasMany<EventParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class)->orderBy('id');
    }

    /**
     * @return HasMany<Scene, $this>
     */
    public function scenes(): HasMany
    {
        return $this->hasMany(Scene::class);
    }

    /**
     * @return HasMany<GazetteArticle, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(GazetteArticle::class);
    }

    public function acceptsRegistrations(): bool
    {
        return $this->is_published && $this->registrations_open && $this->status !== EventStatus::Finished;
    }
}
