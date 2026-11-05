<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Crée les pages officielles manquantes. Une page déjà présente n'est jamais écrasée :
 * les modifications faites dans l'administration sont préservées.
 */
class OfficialPagesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ((array) config('hollow.pages') as $slug => $title) {
            if (Page::query()->where('slug', $slug)->exists()) {
                continue;
            }

            $file = database_path("seeders/pages/{$slug}.md");

            Page::create([
                'slug' => $slug,
                'title' => $title,
                'body' => is_file($file) ? (string) file_get_contents($file) : '',
                'is_published' => true,
            ]);
        }
    }
}
