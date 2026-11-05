<?php

namespace App\Models;

use App\Models\Concerns\HasManagedImage;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\IsPublishable;
use App\Models\Concerns\LogsContentChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Quartier de Hollow Creek.
 */
#[Fillable(['slug', 'name', 'summary', 'description', 'image_path', 'image_credit', 'sort_order', 'is_published'])]
class District extends Model
{
    use HasManagedImage, HasSlug, IsPublishable, LogsContentChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Location, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public static function contentLabel(): string
    {
        return 'Quartier';
    }
}
