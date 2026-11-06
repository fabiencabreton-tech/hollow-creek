<?php

namespace Database\Seeders;

use App\Models\Page;
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
    }
}
