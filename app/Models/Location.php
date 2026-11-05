<?php

namespace App\Models;

use App\Models\Concerns\HasManagedImage;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\IsPublishable;
use App\Models\Concerns\LogsContentChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lieu de Hollow Creek : commerce, institution, nature… Les scènes RP s'y ouvriront (phase 3).
 */
#[Fillable(['district_id', 'slug', 'name', 'kind', 'summary', 'description', 'image_path', 'image_credit', 'sort_order', 'is_published'])]
class Location extends Model
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
     * @return BelongsTo<District, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * @return HasMany<GazetteArticle, $this>
     */
    public function gazetteArticles(): HasMany
    {
        return $this->hasMany(GazetteArticle::class);
    }

    public static function contentLabel(): string
    {
        return 'Lieu';
    }
}
