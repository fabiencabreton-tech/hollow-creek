<?php

namespace App\Policies;

use App\Models\FaceclaimClaim;
use App\Models\User;

class FaceclaimClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bottin.gerer');
    }

    public function view(User $user, FaceclaimClaim $claim): bool
    {
        return $user->can('bottin.gerer') || $user->getKey() === $claim->user_id;
    }

    /**
     * Le joueur annule ou prolonge ses propres demandes (hors fiche).
     */
    public function manageOwn(User $user, FaceclaimClaim $claim): bool
    {
        return $user->getKey() === $claim->user_id && $claim->character_id === null;
    }
}
