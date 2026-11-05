<?php

namespace App\Models;

use App\Enums\ParticipantStatus;
use App\Enums\SceneStatus;
use App\Enums\SceneVisibility;
use App\Enums\TurnMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Scène RP : un lieu, une date RP, des participants et leurs réponses.
 */
#[Fillable(['title', 'rp_date', 'location_id', 'event_id', 'visibility', 'is_open', 'content_warnings', 'turn_mode'])]
class Scene extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => SceneVisibility::class,
            'turn_mode' => TurnMode::class,
            'status' => SceneStatus::class,
            'is_open' => 'boolean',
            'content_warnings' => 'array',
            'last_post_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Scene>  $query
     * @return Builder<Scene>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', SceneVisibility::Public->value);
    }

    /**
     * @param  Builder<Scene>  $query
     * @return Builder<Scene>
     */
    public function scopeListed(Builder $query): Builder
    {
        return $query->public()->whereIn('status', [SceneStatus::Ongoing->value, SceneStatus::Paused->value, SceneStatus::Finished->value]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function nextUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'next_user_id');
    }

    /**
     * @return HasMany<SceneParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(SceneParticipant::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<SceneParticipant, $this>
     */
    public function activeParticipants(): HasMany
    {
        return $this->participants()->where('status', ParticipantStatus::Accepted->value);
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class)->orderBy('id');
    }

    public function isPrivate(): bool
    {
        return $this->visibility === SceneVisibility::Private;
    }

    public function participantFor(?User $user): ?SceneParticipant
    {
        if ($user === null) {
            return null;
        }

        if ($this->relationLoaded('participants')) {
            return $this->participants->firstWhere('user_id', $user->getKey());
        }

        return $this->participants()->where('user_id', $user->getKey())->first();
    }

    public function isActiveParticipant(?User $user): bool
    {
        return $this->participantFor($user)?->status === ParticipantStatus::Accepted;
    }

    /**
     * Avertissements de contenu, avec leur libellé.
     *
     * @return array<string, string>
     */
    public function warningLabels(): array
    {
        $labels = config('hollow.scenes.content_warnings', []);

        return collect($this->content_warnings ?? [])
            ->filter(fn ($key): bool => is_string($key) && isset($labels[$key]))
            ->mapWithKeys(fn (string $key): array => [$key => $labels[$key]])
            ->all();
    }
}
