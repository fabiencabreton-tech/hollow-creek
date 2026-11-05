<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\FaceclaimRegistry;
use Illuminate\Auth\Events\Verified;

/**
 * Une adresse vérifiée récupère les demandes d'avatar faites avec elle sans compte.
 */
class AttachGuestClaims
{
    public function __construct(private readonly FaceclaimRegistry $registry) {}

    public function handle(Verified $event): void
    {
        if ($event->user instanceof User) {
            $this->registry->attachGuestClaims($event->user);
        }
    }
}
