<?php

namespace App\Models;

use App\Enums\AdCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Petite annonce passée par un personnage, visible jusqu'à sa date d'expiration.
 */
class ClassifiedAd extends Model
{
    use SoftDeletes;

    public const DURATIONS = [7, 14, 30];

    public const MAX_ACTIVE_PER_USER = 5;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => AdCategory::class,
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    public function isActive(): bool
    {
        return $this->expires_at->isFuture();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class)->withTrashed();
    }

    public function html(): \Illuminate\Support\HtmlString
    {
        return \App\Support\SceneText::render($this->body);
    }
}
