<?php

namespace App\Models;

use App\Enums\CharacterStatus;
use App\Enums\ResidenceKind;
use App\Models\Concerns\HasManagedImage;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsContentChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Habitation de Tsukimori. Les personnages acceptés y emménagent dans la limite
 * de sa capacité ; une habitation « fermée » ne se choisit qu'avec l'équipe.
 */
#[Fillable(['slug', 'name', 'kind', 'district_id', 'capacity', 'is_open', 'summary', 'description', 'image_path', 'image_credit', 'sort_order', 'is_published'])]
class Residence extends Model
{
    use HasManagedImage, HasSlug, LogsContentChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ResidenceKind::class,
            'capacity' => 'integer',
            'is_open' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
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
     * Tous les habitants, y compris les fiches archivées ou en attente.
     *
     * @return HasMany<Character, $this>
     */
    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    /**
     * Habitants affichés : personnages acceptés.
     *
     * @return HasMany<Character, $this>
     */
    public function residents(): HasMany
    {
        return $this->hasMany(Character::class)->where('status', CharacterStatus::Accepted->value)->orderBy('first_name');
    }

    public function freePlaces(): int
    {
        return max(0, $this->capacity - $this->characters()->count());
    }

    public static function contentLabel(): string
    {
        return 'Habitation';
    }
}
