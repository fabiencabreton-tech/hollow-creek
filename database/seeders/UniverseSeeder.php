<?php

namespace Database\Seeders;

use App\Enums\SpeciesKind;
use App\Models\Character;
use App\Models\District;
use App\Models\Family;
use App\Models\Location;
use App\Models\LoreEntry;
use App\Models\Page;
use App\Models\PredefinedCharacter;
use App\Models\Species;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Univers de Tsukimori : bestiaire, quartiers, lieux, pages et archives secrètes.
 *
 * Sur une base créée avec l'univers de Hollow Creek, le contenu est remplacé une seule
 * fois : catégories renommées (les fiches suivent), familles et lieux d'Alderwick retirés,
 * pages de présentation réécrites. Ensuite, rien n'est plus jamais écrasé : l'équipe
 * gère tout dans l'administration.
 */
class UniverseSeeder extends Seeder
{
    public const DONE_KEY = 'contenu_tsukimori';

    public function run(): void
    {
        $settings = app(Settings::class);

        if ($settings->get(self::DONE_KEY)) {
            return;
        }

        $content = require database_path('seeders/content/univers.php');

        DB::transaction(function () use ($content): void {
            $this->retireHollowCreek($content['hollow_creek']);
            $this->seedSpecies($content['species']);
            $this->moveTraitsOutOfNatures();
            $this->seedPlaces($content['districts'], $content['locations']);
            $this->seedPages($content['hollow_creek']['pages']);

            foreach ($content['lore'] as $entry) {
                LoreEntry::query()->firstOrCreate(['title' => $entry['title']], $entry);
            }
        });

        $settings->set('contenu_initial_univers', true);
        $settings->set(self::DONE_KEY, true);
    }

    /**
     * @param  array<string, mixed>  $old
     */
    private function retireHollowCreek(array $old): void
    {
        // Les catégories de Hollow Creek deviennent celles de Tsukimori ; les fiches suivent.
        foreach ($old['species'] as $oldSlug => $newSlug) {
            $previous = Species::query()->where('slug', $oldSlug)->first();

            if ($previous === null || $oldSlug === $newSlug) {
                continue;
            }

            $target = Species::query()->where('slug', $newSlug)->first();

            if ($target) {
                Character::withTrashed()->where('species_id', $previous->getKey())->update(['species_id' => $target->getKey()]);
                PredefinedCharacter::query()->where('species_id', $previous->getKey())->update(['species_id' => $target->getKey()]);
                $previous->delete();
            } else {
                $previous->forceFill(['slug' => $newSlug])->saveQuietly();
            }
        }

        $families = Family::query()->whereIn('name', $old['families'])->get();
        Character::withTrashed()->whereIn('family_id', $families->modelKeys())->update(['family_id' => null]);
        PredefinedCharacter::query()->whereIn('family_id', $families->modelKeys())->update(['family_id' => null]);
        $families->each->delete();

        Location::query()->whereIn('name', $old['locations'])->get()->each->delete();
        District::query()->whereIn('name', $old['districts'])->get()->each->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function seedSpecies(array $items): void
    {
        $fields = ['name', 'kind', 'allowed_natures', 'needs_death_date', 'needs_registry', 'is_rare', 'treatment', 'summary',
            'description', 'origin', 'physical', 'longevity', 'abilities', 'weaknesses', 'limits', 'rp_notes', 'creation_rules'];

        foreach ($items as $order => $item) {
            $species = Species::query()->firstOrNew(['slug' => $item['slug']]);
            $values = ['slug' => $item['slug'], 'sort_order' => $order + 1, 'is_published' => true];

            foreach ($fields as $field) {
                $values[$field] = $item[$field] ?? match ($field) {
                    'needs_death_date', 'needs_registry', 'is_rare' => false,
                    default => null,
                };
            }

            $species->forceFill($values)->save();
        }

        foreach ($items as $item) {
            if (isset($item['parent'])) {
                Species::query()->where('slug', $item['slug'])->update([
                    'parent_id' => Species::query()->where('slug', $item['parent'])->value('id'),
                ]);
            }
        }
    }

    /**
     * Une fiche dont la catégorie est devenue une spécialisation (ancienne « sorcière »)
     * redevient humaine, avec cette spécialisation en plus.
     */
    private function moveTraitsOutOfNatures(): void
    {
        $human = Species::query()->where('slug', 'humains')->value('id');
        $traits = Species::query()->where('kind', '!=', SpeciesKind::Nature->value)->pluck('id');

        Character::withTrashed()->whereIn('species_id', $traits)->get()->each(function (Character $character) use ($human): void {
            DB::table('character_traits')->insertOrIgnore([
                'character_id' => $character->getKey(),
                'species_id' => $character->species_id,
            ]);
            $character->forceFill(['species_id' => $human])->saveQuietly();
        });

        PredefinedCharacter::query()->whereIn('species_id', $traits)->update(['species_id' => $human]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $districts
     * @param  array<int, array<string, mixed>>  $locations
     */
    private function seedPlaces(array $districts, array $locations): void
    {
        $ids = [];

        foreach ($districts as $order => $item) {
            $district = District::query()->firstOrNew(['name' => $item['name']]);
            $district->forceFill([
                'summary' => $item['summary'],
                'map_x' => $item['map'][0],
                'map_y' => $item['map'][1],
                'sort_order' => $order + 1,
                'is_published' => true,
            ])->save();
            $ids[$item['name']] = $district->getKey();
        }

        foreach ($locations as $order => $item) {
            $location = Location::query()->firstOrNew(['name' => $item['name']]);
            $location->forceFill([
                'kind' => $item['kind'],
                'district_id' => $item['district'] ? $ids[$item['district']] : null,
                'summary' => $item['summary'],
                'map_x' => $item['map'][0],
                'map_y' => $item['map'][1],
                'sort_order' => $order + 1,
                'is_published' => true,
            ])->save();
        }
    }

    /**
     * Pages de présentation réécrites ; les autres pages gardent leur texte, avec le nouveau nom.
     *
     * @param  array<int, string>  $rewritten
     */
    private function seedPages(array $rewritten): void
    {
        foreach ((array) config('hollow.pages') as $slug => $title) {
            $page = Page::query()->where('slug', $slug)->first();
            $file = database_path("seeders/pages/{$slug}.md");

            if ($page === null) {
                continue;
            }

            if (in_array($slug, $rewritten, true) && is_file($file)) {
                $page->forceFill(['title' => $title, 'body' => (string) file_get_contents($file)])->save();

                continue;
            }

            $page->forceFill([
                'title' => $title,
                'body' => str_replace(['Hollow Creek', 'revenants'], ['Tsukimori', 'Revenus'], (string) $page->body),
            ])->save();
        }
    }
}
