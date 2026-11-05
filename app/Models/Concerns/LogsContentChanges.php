<?php

namespace App\Models\Concerns;

use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Inscrit au journal de l'équipe chaque création, modification ou suppression
 * faite par une personne connectée (pas les imports automatiques).
 *
 * @mixin Model
 */
trait LogsContentChanges
{
    public static function bootLogsContentChanges(): void
    {
        foreach (['created' => 'contenu.cree', 'updated' => 'contenu.modifie', 'deleted' => 'contenu.supprime'] as $event => $action) {
            static::$event(function (Model $model) use ($action): void {
                if (! Auth::check()) {
                    return;
                }

                ActivityLogger::record($action, $model, static::contentLabel().' : '.$model->getAttribute(static::contentTitleAttribute()));
            });
        }
    }

    abstract public static function contentLabel(): string;

    protected static function contentTitleAttribute(): string
    {
        return 'name';
    }
}
