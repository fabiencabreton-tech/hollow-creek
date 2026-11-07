<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Species;
use App\Support\Settings;
use Illuminate\Database\Seeder;

/**
 * Petites corrections de contenu appliquées une seule fois aux sites déjà installés.
 */
class ContentUpdatesSeeder extends Seeder
{
    public function run(Settings $settings): void
    {
        if (! $settings->get('maj_credits_facultatifs')) {
            $page = Page::query()->where('slug', 'reglement')->first();
            if ($page) {
                $page->forceFill(['body' => str_replace(
                    "- Chaque image d'avatar indique son crédit (photographe ou source).",
                    "- Le crédit d'une image (photographe, source, licence) est facultatif, sauf quand la licence de l'image l'exige : il faut alors l'indiquer.",
                    (string) $page->body,
                )])->save();
            }
            $settings->set('maj_credits_facultatifs', true);
        }

        // Refonte « Brume » : écriture japonaise des deux espèces qui portent un nom japonais.
        if (! $settings->get('maj_especes_kanji')) {
            foreach (['yurei' => '幽霊', 'onryo' => '怨霊'] as $slug => $kanji) {
                Species::query()->where('slug', $slug)->whereNull('japanese_name')->update(['japanese_name' => $kanji]);
            }
            $settings->set('maj_especes_kanji', true);
        }
    }
}
