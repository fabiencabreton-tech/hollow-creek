<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Family;
use App\Models\Location;
use App\Models\Species;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Contenu de départ de l'univers (textes du cahier des charges).
 * Ne s'exécute qu'une seule fois : ensuite, l'équipe gère tout dans l'administration,
 * et une fiche supprimée volontairement ne revient jamais.
 */
class UniverseSeeder extends Seeder
{
    public const DONE_KEY = 'contenu_initial_univers';

    public function run(): void
    {
        $settings = app(Settings::class);

        if ($settings->get(self::DONE_KEY)) {
            return;
        }

        $content = require database_path('seeders/content/univers.php');

        DB::transaction(function () use ($content): void {
            foreach ($content['species'] as $order => $item) {
                Species::query()->firstOrCreate(['name' => $item['name']], [
                    'treatment' => $item['treatment'],
                    'summary' => $item['summary'],
                    'sort_order' => $order + 1,
                    'is_published' => true,
                ]);
            }

            foreach ($content['families'] as $order => $item) {
                Family::query()->firstOrCreate(['name' => $item['name']], [
                    'summary' => $item['summary'],
                    'sort_order' => $order + 1,
                    'is_published' => true,
                ]);
            }

            $districts = [];

            foreach ($content['districts'] as $order => $item) {
                $districts[$item['name']] = District::query()->firstOrCreate(['name' => $item['name']], [
                    'summary' => $item['summary'],
                    'description' => $item['description'],
                    'sort_order' => $order + 1,
                    'is_published' => true,
                ]);
            }

            foreach ($content['locations'] as $order => $item) {
                Location::query()->firstOrCreate(['name' => $item['name']], [
                    'kind' => $item['kind'],
                    'district_id' => $item['district'] ? $districts[$item['district']]->getKey() : null,
                    'summary' => $item['summary'],
                    'description' => $item['description'],
                    'sort_order' => $order + 1,
                    'is_published' => true,
                ]);
            }

            foreach ($content['draft_locations'] as $order => $item) {
                Location::query()->firstOrCreate(['name' => $item['name']], [
                    'kind' => $item['kind'],
                    'sort_order' => 100 + $order,
                    'is_published' => false,
                ]);
            }
        });

        $settings->set(self::DONE_KEY, true);
    }
}
