<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Enregistre une action dans le journal de l'équipe.
 * Un échec d'écriture du journal n'empêche jamais l'action elle-même.
 */
class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function record(string $action, ?Model $subject = null, ?string $description = null, array $properties = [], ?User $causer = null): ?ActivityLog
    {
        try {
            $causer ??= Auth::user();

            return ActivityLog::create([
                'user_id' => $causer?->getKey(),
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'description' => $description !== null ? mb_substr($description, 0, 500) : null,
                'properties' => $properties === [] ? null : $properties,
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
