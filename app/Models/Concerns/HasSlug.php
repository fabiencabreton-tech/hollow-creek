<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Adresse lisible (« the-willow-cafe ») générée depuis le nom quand elle est laissée vide,
 * et rendue unique au besoin (« -2 », « -3 »…).
 *
 * @mixin Model
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function (Model $model): void {
            $source = (string) $model->getAttribute(static::slugSourceAttribute());
            $slug = Str::slug((string) ($model->getAttribute('slug') ?: $source), language: 'fr');

            if ($slug === '') {
                $slug = Str::lower(Str::random(8));
            }

            $slug = Str::limit($slug, 70, '');
            $candidate = $slug;
            $suffix = 2;

            while (static::query()
                ->where('slug', $candidate)
                ->when($model->exists, fn ($query) => $query->whereKeyNot($model->getKey()))
                ->exists()) {
                $candidate = $slug.'-'.$suffix++;
            }

            $model->setAttribute('slug', $candidate);
        });
    }

    protected static function slugSourceAttribute(): string
    {
        return 'name';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
