<?php

namespace App\Models;

use App\Enums\CharacterStatus;
use App\Models\Concerns\HasManagedImage;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\IsPublishable;
use App\Models\Concerns\LogsContentChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Famille de Tsukimori : l'une des grandes familles historiques (Minase, Fujisawa,
 * Tachibana, Katsuragi) ou une famille proposée par des joueurs. Les notes secrètes
 * ne sont jamais affichées sur le site public.
 */
#[Fillable(['slug', 'name', 'japanese_name', 'motto', 'district_id', 'district_note', 'summary', 'history', 'activities', 'reputation', 'kirisawa_link', 'rp_notes', 'open_roles', 'secret_notes', 'is_major', 'is_open', 'image_path', 'image_credit', 'sort_order', 'is_published'])]
class Family extends Model
{
    use HasManagedImage, HasSlug, IsPublishable, LogsContentChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_major' => 'boolean',
            'is_open' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Nom sans article : « Famille Minase » → « Minase ».
     */
    public function shortName(): string
    {
        return (string) preg_replace('/^((la\s+)?famille|les|la|le|l’|l\')\s*/iu', '', trim((string) $this->name));
    }

    /**
     * @return BelongsTo<District, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Personnages de la famille (toutes fiches confondues).
     *
     * @return HasMany<Character, $this>
     */
    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    /**
     * Membres jouables affichés : personnages acceptés.
     *
     * @return HasMany<Character, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Character::class)->where('status', CharacterStatus::Accepted->value)->orderBy('first_name');
    }

    /**
     * Nœuds de l'arbre généalogique.
     *
     * @return HasMany<FamilyMember, $this>
     */
    public function treeMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<FamilyPrelink, $this>
     */
    public function prelinks(): HasMany
    {
        return $this->hasMany(FamilyPrelink::class);
    }

    /**
     * @return HasMany<PredefinedCharacter, $this>
     */
    public function predefinedCharacters(): HasMany
    {
        return $this->hasMany(PredefinedCharacter::class);
    }

    /**
     * Liens saisis depuis cette famille (l'autre famille les affiche aussi).
     *
     * @return HasMany<FamilyRelation, $this>
     */
    public function outgoingRelations(): HasMany
    {
        return $this->hasMany(FamilyRelation::class);
    }

    /**
     * Liens avec les autres familles, dans les deux sens.
     *
     * @return Collection<int, array{family: Family, summary: string}>
     */
    public function relatedFamilies(): Collection
    {
        return FamilyRelation::query()
            ->where('family_id', $this->getKey())
            ->orWhere('other_family_id', $this->getKey())
            ->with(['family', 'otherFamily'])
            ->get()
            ->map(fn (FamilyRelation $relation): array => [
                'family' => $relation->family_id === $this->getKey() ? $relation->otherFamily : $relation->family,
                'summary' => $relation->summary,
            ])
            ->filter(fn (array $row): bool => $row['family'] !== null && $row['family']->is_published)
            ->sortBy(fn (array $row): int => $row['family']->sort_order)
            ->values();
    }

    /**
     * Sceau de la famille : son nom japonais, sinon l'initiale (« Minase » → « M »).
     */
    public function initial(): string
    {
        if (filled($this->japanese_name)) {
            return (string) preg_replace('/家$/u', '', (string) $this->japanese_name);
        }

        return mb_strtoupper(mb_substr($this->shortName(), 0, 1)) ?: '·';
    }

    public static function contentLabel(): string
    {
        return 'Famille';
    }
}
