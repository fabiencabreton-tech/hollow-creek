<?php

namespace App\Support;

use App\Enums\SceneStatus;
use App\Models\District;
use App\Models\Location;
use App\Models\Scene;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Données de la ville et de sa carte interactive, partagées par la page « La ville »
 * et l'accueil des visiteurs. Uniquement du contenu publié.
 */
class CityMap
{
    /**
     * @return array{districts: Collection<int, District>, others: Collection<int, Location>, places: Collection<int, Location>, kinds: Collection<int, string>, mapData: array<string, array<string, mixed>>}
     */
    public static function build(): array
    {
        $districts = District::query()
            ->published()
            ->ordered()
            ->with(['locations' => fn ($query) => $query->published()->ordered()])
            ->get();

        // Lieux sans quartier, ou dont le quartier n'est pas encore publié.
        $others = Location::query()
            ->published()
            ->ordered()
            ->where(fn (Builder $query) => $query
                ->whereNull('district_id')
                ->orWhereHas('district', fn (Builder $district) => $district->where('is_published', false)))
            ->get();

        $places = $districts->flatMap->locations->concat($others)->sortBy('name')->values();

        return [
            'districts' => $districts,
            'others' => $others,
            'places' => $places,
            'kinds' => $places->pluck('kind')->filter()->unique()->sort()->values(),
            'mapData' => self::mapData($places, $districts),
        ];
    }

    /**
     * Données de la carte interactive : uniquement du contenu publié et des scènes publiques en cours.
     *
     * @param  Collection<int, Location>  $places
     * @param  Collection<int, District>  $districts
     * @return array<string, array<string, mixed>>
     */
    private static function mapData(Collection $places, Collection $districts): array
    {
        $scenes = Scene::query()
            ->public()
            ->where('status', SceneStatus::Ongoing->value)
            ->whereIn('location_id', $places->pluck('id')->all())
            ->latest('last_post_at')
            ->get(['id', 'title', 'location_id'])
            ->groupBy('location_id');

        $districtNames = $districts->pluck('name', 'id');

        return $places->mapWithKeys(fn (Location $location): array => [$location->slug => [
            'name' => $location->name,
            'kind' => $location->kind,
            'district' => $districtNames[$location->district_id] ?? null,
            'summary' => $location->summary,
            'image' => $location->imageUrl(),
            'imageCredit' => $location->image_credit,
            'url' => route('locations.show', $location),
            'start' => route('scenes.create', ['lieu' => $location->slug]),
            'scenes' => ($scenes[$location->getKey()] ?? collect())
                ->take(4)
                ->map(fn (Scene $scene): array => ['title' => $scene->title, 'url' => route('scenes.show', $scene)])
                ->values()
                ->all(),
        ]])->all();
    }
}
