<?php

namespace App\Models;

use App\Enums\SpeciesKind;
use App\Enums\SpeciesTreatment;
use App\Models\Concerns\HasManagedImage;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\IsPublishable;
use App\Models\Concerns\LogsContentChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catégorie du bestiaire de Tsukimori.
 * - nature : Humain, Yūrei, Onryō (Yūrei particulier), Revenu, Rappelé — une seule par personnage ;
 * - specialisation : Médium, Praticien de l'occulte — des humains, cumulables sous réserve ;
 * - condition : Maudit — peut toucher n'importe quelle nature.
 */
#[Table('species')]
#[Fillable([
    'slug', 'name', 'kind', 'parent_id', 'allowed_natures', 'needs_death_date', 'needs_registry', 'is_rare',
    'treatment', 'summary', 'description', 'origin', 'physical', 'longevity', 'abilities', 'weaknesses',
    'limits', 'rp_notes', 'creation_rules', 'story_hooks', 'image_path', 'image_credit', 'sort_order', 'is_published',
])]
class Species extends Model
{
    use HasManagedImage, HasSlug, IsPublishable, LogsContentChanges;

    /**
     * Rubriques d'une fiche de bestiaire, dans l'ordre d'affichage.
     */
    public const SECTIONS = [
        'description' => 'Description générale',
        'origin' => 'Origine',
        'physical' => 'Caractéristiques physiques',
        'longevity' => 'Longévité',
        'abilities' => 'Capacités',
        'weaknesses' => 'Faiblesses',
        'limits' => 'Restrictions',
        'rp_notes' => 'Particularités pour les RP',
        'creation_rules' => 'Conditions de création',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => SpeciesKind::class,
            'treatment' => SpeciesTreatment::class,
            'allowed_natures' => 'array',
            'needs_death_date' => 'boolean',
            'needs_registry' => 'boolean',
            'is_rare' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function contentLabel(): string
    {
        return 'Catégorie';
    }

    /**
     * @param  Builder<Species>  $query
     * @return Builder<Species>
     */
    public function scopeNatures(Builder $query): Builder
    {
        return $query->where('kind', SpeciesKind::Nature->value);
    }

    /**
     * @param  Builder<Species>  $query
     * @return Builder<Species>
     */
    public function scopeTraits(Builder $query): Builder
    {
        return $query->whereIn('kind', [SpeciesKind::Specialisation->value, SpeciesKind::Condition->value]);
    }

    /**
     * @return BelongsTo<Species, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Species::class, 'parent_id');
    }

    public function isNature(): bool
    {
        return $this->kind === SpeciesKind::Nature;
    }

    /**
     * Une spécialisation ou une condition peut-elle s'ajouter à cette nature ?
     */
    public function allowsNature(?Species $nature): bool
    {
        if ($nature === null) {
            return false;
        }

        $allowed = $this->allowed_natures;

        return empty($allowed) || in_array($nature->slug, $allowed, true);
    }

    /**
     * Les morts (Yūrei, Onryō, Revenus, Rappelés) n'ont pas de vie humaine à raconter au présent.
     */
    public function isSupernatural(): bool
    {
        return $this->kind !== SpeciesKind::Nature || $this->treatment !== SpeciesTreatment::Humain;
    }
}
