<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.gerer');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.gerer');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.gerer');
    }

    /**
     * Le rôle « administrateur » garde toutes les permissions : il ne se modifie pas.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.gerer') && $role->name !== 'administrateur';
    }

    /**
     * Les trois rôles de base ne peuvent pas être supprimés.
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.gerer') && ! array_key_exists($role->name, (array) config('hollow.roles'));
    }
}
