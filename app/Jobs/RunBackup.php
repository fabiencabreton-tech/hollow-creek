<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/**
 * Sauvegarde demandée depuis l'administration. Elle passe par la file d'attente
 * pour ne pas bloquer la page (sur l'offre gratuite, la tâche planifiée la traite
 * dans la minute).
 */
class RunBackup implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public int $uniqueFor = 900;

    public function handle(): void
    {
        $exitCode = Artisan::call('backup:run');

        if ($exitCode !== 0) {
            throw new RuntimeException('La sauvegarde a échoué : '.trim(Artisan::output()));
        }
    }
}
