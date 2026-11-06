<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lien narratif entre deux familles, enregistré une seule fois pour la paire.
 */
#[Fillable(['family_id', 'other_family_id', 'summary'])]
class FamilyRelation extends Model
{
    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function otherFamily(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'other_family_id');
    }
}
