<?php

namespace App\Models;

use App\Enums\PrelinkRelation;
use App\Enums\PrelinkStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Prélien familial : un membre d'une famille cherche un frère, une cousine, un
 * parent… que quelqu'un d'autre viendra incarner.
 */
class FamilyPrelink extends Model
{
    use SoftDeletes;

    public const MAX_OPEN_PER_CHARACTER = 5;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'relation' => PrelinkRelation::class,
            'status' => PrelinkStatus::class,
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', PrelinkStatus::Open->value);
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
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
    public function takenBy(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'taken_character_id');
    }

    public function html(): \Illuminate\Support\HtmlString
    {
        return \App\Support\SceneText::render($this->description);
    }
}
