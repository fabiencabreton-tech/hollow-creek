<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Blocage entre deux comptes : plus de message, d'invitation de scène ni de proposition de lien.
 */
class UserBlock extends Model
{
    public const UPDATED_AT = null;

    /**
     * Un blocage dans un sens ou dans l'autre.
     */
    public static function between(User|int $a, User|int $b): bool
    {
        $first = $a instanceof User ? $a->getKey() : $a;
        $second = $b instanceof User ? $b->getKey() : $b;

        return static::query()
            ->where(fn ($query) => $query->where('user_id', $first)->where('blocked_user_id', $second))
            ->orWhere(fn ($query) => $query->where('user_id', $second)->where('blocked_user_id', $first))
            ->exists();
    }

    /**
     * Identifiants des comptes avec lesquels ce joueur a un blocage, dans un sens ou dans l'autre.
     *
     * @return array<int, int>
     */
    public static function idsFor(User $user): array
    {
        return static::query()->where('user_id', $user->getKey())->pluck('blocked_user_id')
            ->merge(static::query()->where('blocked_user_id', $user->getKey())->pluck('user_id'))
            ->unique()->values()->all();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function blocked(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_user_id');
    }
}
