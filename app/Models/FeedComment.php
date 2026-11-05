<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;

/**
 * Commentaire d'une publication, signé par un personnage.
 */
class FeedComment extends Model
{
    use SoftDeletes;

    /**
     * @return BelongsTo<FeedPost, $this>
     */
    public function feedPost(): BelongsTo
    {
        return $this->belongsTo(FeedPost::class)->withTrashed();
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

    public function html(): HtmlString
    {
        return new HtmlString(nl2br(e($this->body), false));
    }
}
