<?php

namespace App\Models;

use App\Models\Concerns\HasManagedImage;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\IsPublishable;
use App\Models\Concerns\LogsContentChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Famille fondatrice : Whitmore, Blackwell, Ashford, Holloway.
 */
#[Fillable(['slug', 'name', 'summary', 'history', 'open_roles', 'image_path', 'image_credit', 'sort_order', 'is_published'])]
class Family extends Model
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
     * Initiale du nom de famille, pour le sceau (« Les Whitmore » → « W »).
     */
    public function initial(): string
    {
        $name = (string) preg_replace('/^(les|la|le|l’|l\')\s*/iu', '', trim((string) $this->name));

        return mb_strtoupper(mb_substr($name, 0, 1)) ?: '·';
    }

    public static function contentLabel(): string
    {
        return 'Famille';
    }
}
