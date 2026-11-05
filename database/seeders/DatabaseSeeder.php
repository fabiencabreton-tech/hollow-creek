<?php

namespace Database\Seeders;

use App\Support\RolesAndPermissions;
use Illuminate\Database\Seeder;

/**
 * Données de départ indispensables : rôles, permissions et pages officielles.
 * Sans danger à relancer (`php artisan db:seed --force`) : rien n'est écrasé.
 * Aucun compte de démonstration n'est créé ; le premier administrateur se crée
 * avec `php artisan hollow:admin`.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        RolesAndPermissions::sync();

        $this->call(OfficialPagesSeeder::class);
    }
}
