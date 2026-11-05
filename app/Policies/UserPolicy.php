<?php

namespace App\Policies;

use App\Models\User;

/**
 * Accès de l'équipe aux comptes, dans l'administration.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('comptes.voir');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('comptes.voir');
    }

    /**
     * Suspendre, réactiver, valider une adresse, réinitialiser la double authentification.
     * Personne ne peut agir ainsi sur son propre compte.
     */
    public function moderate(User $user, User $model): bool
    {
        return $user->can('comptes.moderer') && ! $user->is($model);
    }

    /**
     * Seule une personne qui a elle-même tous les droits peut toucher aux rôles d'un administrateur.
     */
    public function manageRoles(User $user, User $model): bool
    {
        if (! $user->can('comptes.gerer-roles') || $user->is($model)) {
            return false;
        }

        return ! $model->hasRole('administrateur') || $user->hasRole('administrateur');
    }
}
