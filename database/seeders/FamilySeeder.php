<?php

namespace Database\Seeders;

use App\Models\Character;
use App\Models\District;
use App\Models\Family;
use App\Models\FamilyRelation;
use App\Services\FamilyService;
use App\Support\Settings;
use Illuminate\Database\Seeder;

/**
 * Importe une seule fois les quatre grandes familles. Une famille déjà présente
 * (même identifiant) n'est jamais écrasée : seuls ses champs vides sont complétés.
 */
class FamilySeeder extends Seeder
{
    public const DONE_KEY = 'contenu_familles';

    public function run(Settings $settings, FamilyService $families): void
    {
        if ($settings->get(self::DONE_KEY)) {
            return;
        }

        $content = require database_path('seeders/content/familles.php');
        $districts = District::query()->pluck('id', 'name');

        foreach ($content['families'] as $index => $data) {
            $family = Family::query()->firstOrNew(['slug' => $data['slug']]);
            $attributes = [
                'name' => $data['name'],
                'japanese_name' => $data['japanese_name'],
                'motto' => $data['motto'],
                'district_id' => $districts[$data['district']] ?? null,
                'district_note' => $data['district_note'],
                'activities' => $data['activities'],
                'reputation' => $data['reputation'],
                'summary' => $data['summary'],
                'history' => $data['history'],
                'kirisawa_link' => $data['kirisawa_link'],
                'rp_notes' => $data['rp_notes'],
            ];

            foreach ($attributes as $key => $value) {
                if (blank($family->getAttribute($key))) {
                    $family->setAttribute($key, $value);
                }
            }

            $family->forceFill([
                'slug' => $data['slug'],
                'is_major' => true,
                'is_open' => $family->exists ? $family->is_open : true,
                'is_published' => $family->exists ? $family->is_published : true,
                'sort_order' => $family->exists ? $family->sort_order : $index + 1,
            ])->save();
        }

        $ids = Family::query()->pluck('id', 'slug');
        foreach ($content['relations'] as [$a, $b, $summary]) {
            if (! isset($ids[$a], $ids[$b])) {
                continue;
            }
            $exists = FamilyRelation::query()
                ->where(fn ($query) => $query->where('family_id', $ids[$a])->where('other_family_id', $ids[$b]))
                ->orWhere(fn ($query) => $query->where('family_id', $ids[$b])->where('other_family_id', $ids[$a]))
                ->exists();
            if (! $exists) {
                FamilyRelation::query()->create(['family_id' => $ids[$a], 'other_family_id' => $ids[$b], 'summary' => $summary]);
            }
        }

        // Les personnages déjà acceptés dans une famille entrent dans son arbre.
        Character::query()->accepted()->whereNotNull('family_id')->doesntHave('familyMember')->get()
            ->each(fn (Character $character) => $families->ensureMember($character));

        $settings->set(self::DONE_KEY, true);
    }
}
