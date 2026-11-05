<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Fiches publiées ou en brouillon, classées dans l'ordre choisi par l'équipe.
 *
 * @mixin Model
 */
trait IsPublishable
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_published'), true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy($this->qualifyColumn('sort_order'))->orderBy($this->qualifyColumn('name'));
    }
}
