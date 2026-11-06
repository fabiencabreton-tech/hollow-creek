<?php

namespace App\Models;

use App\Enums\ArchiveCategory;
use App\Models\Concerns\HasManagedImage;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsContentChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Document des archives municipales de Tsukimori, rédigé et publié par l'équipe.
 */
#[Fillable(['slug', 'title', 'category', 'reference', 'document_date', 'excerpt', 'body', 'image_path', 'image_credit', 'district_id', 'location_id', 'is_published'])]
class MunicipalRecord extends Model
{
    use HasManagedImage, HasSlug, LogsContentChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ArchiveCategory::class,
            'document_date' => 'date',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @return BelongsTo<District, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    protected static function slugSourceAttribute(): string
    {
        return 'title';
    }

    public static function contentLabel(): string
    {
        return 'Document des archives municipales';
    }

    protected static function contentTitleAttribute(): string
    {
        return 'title';
    }
}
