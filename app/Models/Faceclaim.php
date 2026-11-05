<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Personnalité réelle du bottin des avatars. Seul le nom est conservé : aucune photo.
 */
#[Fillable(['name', 'category'])]
class Faceclaim extends Model
{
    protected static function booted(): void
    {
        static::saving(function (Faceclaim $faceclaim): void {
            $faceclaim->name = static::cleanName((string) $faceclaim->name);
            $faceclaim->name_key = static::key($faceclaim->name);
        });
    }

    public static function cleanName(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }

    /**
     * Forme normalisée, insensible aux accents, majuscules et espaces : « Élise  Dupont » → « elise dupont ».
     */
    public static function key(string $name): string
    {
        $ascii = Str::lower(Str::ascii(static::cleanName($name)));

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $ascii));
    }

    /**
     * @return HasMany<FaceclaimClaim, $this>
     */
    public function claims(): HasMany
    {
        return $this->hasMany(FaceclaimClaim::class);
    }

    /**
     * La demande active (en cours, réservation ou attribution), s'il y en a une.
     *
     * @return HasOne<FaceclaimClaim, $this>
     */
    public function activeClaim(): HasOne
    {
        return $this->hasOne(FaceclaimClaim::class)->whereIn('status', ClaimStatus::ACTIVE);
    }

    /**
     * Statut public : Disponible, Demande en cours, Réservé ou Pris.
     */
    public function publicStatus(): string
    {
        return $this->activeClaim?->status->getLabel() ?? 'Disponible';
    }
}
