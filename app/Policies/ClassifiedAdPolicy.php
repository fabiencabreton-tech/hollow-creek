<?php

namespace App\Policies;

use App\Models\ClassifiedAd;
use App\Models\User;

/**
 * Petites annonces : leur auteur les gère sur le site ; la modération peut les
 * consulter et les retirer depuis l'administration.
 */
class ClassifiedAdPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('signalements.traiter');
    }

    public function view(User $user, ClassifiedAd $ad): bool
    {
        return $user->can('signalements.traiter');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ClassifiedAd $ad): bool
    {
        return $user->getKey() === $ad->user_id && ! $user->isSuspended();
    }

    public function delete(User $user, ClassifiedAd $ad): bool
    {
        return $user->getKey() === $ad->user_id || $user->can('signalements.traiter');
    }

    public function restore(User $user, ClassifiedAd $ad): bool
    {
        return $user->can('signalements.traiter');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('signalements.traiter');
    }
}
