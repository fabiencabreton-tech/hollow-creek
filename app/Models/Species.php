<?php

namespace App\Models;

use App\Enums\SpeciesTreatment;
use App\Models\Concerns\HasManagedImage;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\IsPublishable;
use App\Models\Concerns\LogsContentChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Espèce jouable : humains, revenants, fantômes, médiums, sorcières et sorciers, chasseurs de spectres.
 */
#[Table('species')]
#[Fillable(['slug', 'name', 'treatment', 'summary', 'description', 'abilities', 'limits', 'story_hooks', 'image_path', 'image_credit', 'sort_order', 'is_published'])]
class Species extends Model
{
    use HasManagedImage, HasSlug, IsPublishable, LogsContentChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'treatment' => SpeciesTreatment::class,
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function contentLabel(): string
    {
        return 'Espèce';
    }
}
