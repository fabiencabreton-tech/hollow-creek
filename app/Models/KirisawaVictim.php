<?php

namespace App\Models;

use App\Enums\VictimRole;
use App\Enums\VictimStatus;
use App\Models\Concerns\LogsContentChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registre des quarante-trois victimes de l'incendie de Kirisawa (31 octobre 1996).
 * Certaines sont des personnages de l'équipe ; d'autres peuvent être incarnées en Revenus.
 * Le statut « réservé » et « incarné » suit les fiches (App\Services\CharacterWorkflow).
 */
#[Fillable(['number', 'first_name', 'last_name', 'role', 'age_at_death', 'summary', 'admin_notes', 'status', 'is_published'])]
class KirisawaVictim extends Model
{
    use LogsContentChanges;

    public const TOTAL = 43;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => VictimRole::class,
            'status' => VictimStatus::class,
            'is_published' => 'boolean',
            'number' => 'integer',
            'age_at_death' => 'integer',
        ];
    }

    public static function contentLabel(): string
    {
        return 'Victime de Kirisawa';
    }

    protected static function contentTitleAttribute(): string
    {
        return 'last_name';
    }

    /**
     * @param  Builder<KirisawaVictim>  $query
     * @return Builder<KirisawaVictim>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<KirisawaVictim>  $query
     * @return Builder<KirisawaVictim>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->published()->where('status', VictimStatus::Open->value);
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function fullName(): string
    {
        $name = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $name !== '' ? $name : 'Victime n°'.$this->number;
    }
}
