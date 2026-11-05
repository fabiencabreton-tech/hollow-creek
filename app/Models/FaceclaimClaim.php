<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande, réservation ou attribution d'une célébrité.
 */
#[Fillable(['faceclaim_id', 'user_id', 'character_id', 'status', 'expires_at', 'extended_at', 'reminded_at', 'decided_by', 'decided_at', 'note'])]
class FaceclaimClaim extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ClaimStatus::class,
            'expires_at' => 'datetime',
            'extended_at' => 'datetime',
            'reminded_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ClaimStatus::ACTIVE);
    }

    /**
     * @return BelongsTo<Faceclaim, $this>
     */
    public function faceclaim(): BelongsTo
    {
        return $this->belongsTo(Faceclaim::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function canBeExtended(): bool
    {
        return $this->status === ClaimStatus::Reserved && $this->extended_at === null && $this->expires_at !== null;
    }
}
