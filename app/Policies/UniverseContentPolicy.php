<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Règles communes aux fiches de l'univers (espèces, familles, quartiers, lieux) :
 * réservées aux personnes qui ont la permission « univers.gerer ».
 */
abstract class UniverseContentPolicy
{
    protected string $permission = 'univers.gerer';

    public function viewAny(User $user): bool
    {
        return $user->can($this->permission);
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can($this->permission);
    }

    public function create(User $user): bool
    {
        return $user->can($this->permission);
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->permission);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can($this->permission);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can($this->permission);
    }

    public function reorder(User $user): bool
    {
        return $user->can($this->permission);
    }
}
