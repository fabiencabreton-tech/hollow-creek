<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Auth\Events\Login;

/**
 * Chaque connexion d'un membre de l'équipe est inscrite au journal.
 */
class RecordStaffLogin
{
    public function handle(Login $event): void
    {
        if ($event->user instanceof User && $event->user->isStaff()) {
            ActivityLogger::record('connexion.equipe', $event->user, causer: $event->user);
        }
    }
}
