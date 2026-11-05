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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Personnage d'un joueur. Un compte peut en avoir plusieurs ; chaque fiche est validée par l'équipe.
 */
#[Fillable(['first_name', 'last_name', 'age', 'apparent_age', 'species_id', 'family_id', 'profession', 'quote', 'dialogue_color', 'history', 'personality', 'avatar_credit'])]
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

        if (blank($this->first_name) || $this->age === null || $this->species_id === null || blank($this->profession)) {
            $missing[] = 'Identité : prénom, âge, espèce et profession';
        }

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
}
