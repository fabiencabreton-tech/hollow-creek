<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CensusResponse extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<Census, $this>
     */
    public function census(): BelongsTo
    {
        return $this->belongsTo(Census::class);
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class)->withTrashed();
    }
}
