<?php

namespace App\Models;

use App\Enums\CharacterStatus;
use App\Enums\ClaimStatus;
use App\Models\Concerns\HasSlug;
use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Personnage d'un joueur. Un compte peut en avoir plusieurs ; chaque fiche est validée par l'équipe.
 */
#[Fillable([
    'first_name', 'last_name', 'age', 'apparent_age', 'species_id', 'family_id', 'profession', 'quote', 'dialogue_color',
    'history', 'personality', 'avatar_credit', 'death_date', 'death_circumstances', 'abilities', 'weaknesses', 'anchor',
    'emotion', 'occult_specialties', 'curse_origin', 'curse_effects', 'curse_resolution',
])]
class Character extends Model
{
    use HasSlug, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (Character $character): void {
            foreach (['avatar_path', 'banner_path'] as $column) {
                if ($character->isDirty($column) && filled($character->{$column}) && ! ImageStore::isManagedPath($character->{$column})) {
                    $character->{$column} = $character->getOriginal($column);
                }
            }
        });

        static::updated(function (Character $character): void {
            foreach (['avatar_path', 'banner_path'] as $column) {
                if ($character->wasChanged($column)) {
                    app(ImageStore::class)->delete($character->getOriginal($column));
                }
            }
        });

        static::forceDeleted(function (Character $character): void {
            app(ImageStore::class)->delete($character->avatar_path);
            app(ImageStore::class)->delete($character->banner_path);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CharacterStatus::class,
            'age' => 'integer',
            'apparent_age' => 'integer',
            'submitted_at' => 'datetime',
            'accepted_at' => 'datetime',
            'absence_until' => 'date',
            'death_date' => 'date',
            'occult_specialties' => 'array',
        ];
    }

    protected static function slugSourceAttribute(): string
    {
        return 'full_name_for_slug';
    }

    public function getFullNameForSlugAttribute(): string
    {
        return $this->fullName();
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', CharacterStatus::Accepted->value);
    }

    /**
     * Fiches qui comptent dans la limite de personnages par compte.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCountingTowardsLimit(Builder $query): Builder
    {
        return $query->whereNotIn('status', [CharacterStatus::Refused->value, CharacterStatus::Archived->value]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Species, $this>
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return BelongsTo<Faceclaim, $this>
     */
    public function faceclaim(): BelongsTo
    {
        return $this->belongsTo(Faceclaim::class);
    }

    /**
     * @return BelongsTo<PredefinedCharacter, $this>
     */
    public function predefined(): BelongsTo
    {
        return $this->belongsTo(PredefinedCharacter::class, 'predefined_character_id');
    }

    /**
     * @return HasMany<CharacterReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(CharacterReview::class)->latest('created_at')->latest('id');
    }

    /**
     * Demande active sur la célébrité choisie comme avatar.
     *
     * @return HasOne<FaceclaimClaim, $this>
     */
    public function activeClaim(): HasOne
    {
        return $this->hasOne(FaceclaimClaim::class)->whereIn('status', ClaimStatus::ACTIVE);
    }

    public function bannerUrl(): ?string
    {
        return ImageStore::url($this->banner_path);
    }

    /**
     * Absence annoncée sur le profil, tant que la date de retour n'est pas passée.
     */
    public function isAbsent(): bool
    {
        return filled($this->absence_note) && ($this->absence_until === null || $this->absence_until->endOfDay()->isFuture());
    }

    public function avatarUrl(): ?string
    {
        return ImageStore::url($this->avatar_path);
    }

    public function isEditableByOwner(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Ce qui manque encore avant l'envoi à l'équipe (vide : la fiche peut partir).
     *
     * @return array<int, string>
     */
    public function missingForSubmission(): array
    {
        $missing = [];

        if (blank($this->first_name) || $this->age === null || blank($this->profession)) {
            $missing[] = 'Identité : prénom, âge et profession';
        }

        $missing = array_merge($missing, $this->missingNatureDetails());

        if ($this->faceclaim_id === null || ! $this->activeClaim()->exists()) {
            $missing[] = 'Avatar : une célébrité disponible ou réservée à votre nom';
        }

        if ($this->avatar_path === null || blank($this->avatar_credit)) {
            $missing[] = 'Avatar : l’image et son crédit';
        }

        if (mb_strlen(trim(strip_tags((string) $this->history))) < 200) {
            $missing[] = 'Histoire : au moins quelques paragraphes (200 caractères)';
        }

        if (mb_strlen(trim(strip_tags((string) $this->personality))) < 100) {
            $missing[] = 'Personnalité : au moins un paragraphe (100 caractères)';
        }

        return $missing;
    }

    /**
     * @return BelongsToMany<Species, $this>
     */
    public function traits(): BelongsToMany
    {
        return $this->belongsToMany(Species::class, 'character_traits')->orderBy('sort_order');
    }

    /**
     * @return BelongsTo<KirisawaVictim, $this>
     */
    public function kirisawaVictim(): BelongsTo
    {
        return $this->belongsTo(KirisawaVictim::class);
    }

    public function hasTrait(string $slug): bool
    {
        return $this->traits->contains('slug', $slug);
    }

    /**
     * Personnage surnaturel : nature autre qu'humaine, ou spécialisation, ou condition.
     */
    /**
     * Libellés des spécialités occultes choisies.
     *
     * @return list<string>
     */
    public function occultSpecialtyLabels(): array
    {
        $labels = config('hollow.characters.occult_specialties', []);

        return array_values(array_filter(array_map(
            fn (string $key): ?string => $labels[$key] ?? null,
            $this->occult_specialties ?? [],
        )));
    }

    /**
     * Détails de nature affichés sur le profil public, sans les champs vides.
     *
     * @return array<string, string>
     */
    public function natureDetails(): array
    {
        return array_filter([
            'Circonstances de la mort' => $this->death_circumstances,
            'Attache' => $this->anchor,
            'Émotion dominante' => $this->emotion,
            'Spécialités occultes' => implode(', ', $this->occultSpecialtyLabels()),
            'Origine de la malédiction' => $this->curse_origin,
            'Effets de la malédiction' => $this->curse_effects,
            'Capacités' => $this->abilities,
            'Faiblesses et limites' => $this->weaknesses,
        ], fn (?string $value): bool => filled($value));
    }

    public function isSupernatural(): bool
    {
        return ($this->species && $this->species->isSupernatural()) || $this->traits->isNotEmpty();
    }

    /**
     * Ce que demande la nature choisie (étape « Nature »).
     *
     * @return array<int, string>
     */
    public function missingNatureDetails(): array
    {
        $this->loadMissing(['species', 'traits']);
        $nature = $this->species;
        $missing = [];

        if ($nature === null || ! $nature->isNature()) {
            return ['Nature : choisir la nature du personnage'];
        }

        if ($nature->needs_death_date && $this->death_date === null) {
            $missing[] = 'Nature : la date de décès';
        }

        if ($nature->needs_registry && $this->kirisawa_victim_id === null) {
            $missing[] = 'Nature : la victime du registre de Kirisawa que vous incarnez';
        }

        if ($nature->slug === 'onryo' && blank($this->emotion)) {
            $missing[] = 'Nature : l’émotion qui retient l’Onryō';
        }

        if ($this->hasTrait('praticiens-de-l-occulte') && count($this->occult_specialties ?? []) === 0) {
            $missing[] = 'Nature : au moins une spécialisation occulte';
        }

        if ($this->hasTrait('maudits') && (blank($this->curse_origin) || blank($this->curse_effects))) {
            $missing[] = 'Nature : l’origine et les effets de la malédiction';
        }

        if ($this->isSupernatural() && (blank($this->abilities) || blank($this->weaknesses))) {
            $missing[] = 'Nature : les capacités et les faiblesses du personnage';
        }

        return $missing;
    }
}
