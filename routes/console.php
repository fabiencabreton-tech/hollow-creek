<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tâches planifiées
|--------------------------------------------------------------------------
| Une seule tâche planifiée Alwaysdata suffit, chaque minute :
|     php ~/hollowcreek/artisan schedule:run
| Elle déclenche tout ce qui suit au bon moment.
*/

// Signe de vie, affiché dans l'administration (« État de l'installation »).
Schedule::call(fn () => Cache::forever('hollow.scheduler.heartbeat', now()->getTimestamp()))
    ->name('hollow:heartbeat')
    ->everyMinute();

// Offre gratuite : pas de processus permanent, la file d'attente (e-mails, sauvegardes
// demandées depuis l'administration) est vidée à chaque passage de la tâche planifiée.
if (config('hollow.queue_via_scheduler')) {
    Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3 --sleep=1')
        ->name('hollow:file-attente')
        ->everyMinute()
        ->withoutOverlapping(15);
}

// Sauvegarde hebdomadaire chiffrée, puis nettoyage des plus anciennes.
Schedule::command('backup:clean')->weeklyOn(0, '02:50');
Schedule::command('backup:run')->weeklyOn(0, '03:00');
Schedule::command('backup:monitor')->dailyAt('09:05');

// Ménage : liens de mot de passe expirés et tâches en échec de plus de 30 jours.
Schedule::command('auth:clear-resets')->dailyAt('04:10');
Schedule::command('queue:prune-failed --hours=720')->weeklyOn(1, '04:20');

// Bottin des avatars : rappels 48 h avant échéance, puis libération des réservations expirées.
Schedule::call(function (): void {
    $registry = app(\App\Services\FaceclaimRegistry::class);
    $registry->remindExpiringSoon();
    $registry->expireOverdue();
})->name('hollow:reservations')->everyFifteenMinutes()->withoutOverlapping(30);
