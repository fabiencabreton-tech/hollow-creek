<?php

namespace App\Models;

use App\Enums\FeedKind;
use App\Models\Concerns\HasManagedImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;

/**
 * Publication du fil « Murmures », signée par un personnage.
 */
class FeedPost extends Model
{
    use HasManagedImage, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => FeedKind::class,
        ];
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

    /**
     * @return HasMany<FeedComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(FeedComment::class)->orderBy('id');
    }

    public function html(): HtmlString
    {
        return \App\Support\SceneText::render($this->body);
    }
}
