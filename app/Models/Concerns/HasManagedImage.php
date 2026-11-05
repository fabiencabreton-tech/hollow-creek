<?php

namespace App\Models\Concerns;

use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Model;

/**
 * Image d'illustration (colonne image_path) produite par ImageStore.
 * - un chemin qui ne vient pas d'ImageStore est refusé ;
 * - l'ancien fichier est supprimé quand l'image est remplacée ou la fiche supprimée.
 *
 * @mixin Model
 */
trait HasManagedImage
{
    public static function bootHasManagedImage(): void
    {
        static::saving(function (Model $model): void {
            $path = $model->getAttribute('image_path');

            if ($model->isDirty('image_path') && filled($path) && ! ImageStore::isManagedPath($path)) {
                $model->setAttribute('image_path', $model->getOriginal('image_path'));
            }

            if (blank($model->getAttribute('image_path'))) {
                $model->setAttribute('image_path', null);
            }
        });

        static::updated(function (Model $model): void {
            if ($model->wasChanged('image_path')) {
                app(ImageStore::class)->delete($model->getOriginal('image_path'));
            }
        });

        static::deleted(function (Model $model): void {
            app(ImageStore::class)->delete($model->getAttribute('image_path'));
        });
    }

    public function imageUrl(): ?string
    {
        return ImageStore::url($this->getAttribute('image_path'));
    }
}
