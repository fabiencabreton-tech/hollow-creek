<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Archives secrètes de l'équipe : l'intrigue cachée, jamais affichée sur le site public.
 */
#[Fillable(['title', 'category', 'body'])]
class LoreEntry extends Model
{
    protected static function booted(): void
    {
        static::saving(function (LoreEntry $entry): void {
            if (auth()->check()) {
                $entry->updated_by = auth()->id();
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
