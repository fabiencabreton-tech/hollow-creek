<?php

namespace App\Http\Controllers;

use App\Enums\SceneStatus;
use App\Models\Scene;
use App\Http\Controllers\Concerns\ChecksPreview;
use App\Models\District;
use App\Models\GazetteArticle;
use App\Models\Location;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * La ville : quartiers et lieux où les scènes se dérouleront.
 */
class CityController extends Controller
{
    use ChecksPreview;

    public function index(): View
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

        return view('city.index', [
            'page' => Page::query()->where('slug', 'ville')->where('is_published', true)->first(),
            'districts' => $districts,
            'others' => $others,
            'places' => $places,
            'kinds' => $places->pluck('kind')->filter()->unique()->sort()->values(),
            'mapData' => $this->mapData($places, $districts),
        ]);
    }

    /**
     * Données de la carte interactive : uniquement du contenu publié et des scènes publiques en cours.
     *
     * @param  Collection<int, Location>  $places
     * @param  Collection<int, District>  $districts
     * @return array<string, array<string, mixed>>
     */
    private function mapData(Collection $places, Collection $districts): array
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

    public function district(Request $request, District $district): View
    {
        $isPreview = $this->ensureVisible($request, $district->is_published, 'univers.gerer');

        return view('city.district', [
            'district' => $district,
            'locations' => $district->locations()->published()->ordered()->get(),
            'isPreview' => $isPreview,
        ]);
    }

    public function location(Request $request, Location $location): View
    {
        $isPreview = $this->ensureVisible($request, $location->is_published, 'univers.gerer');

        $location->load('district');

        return view('city.location', [
            'location' => $location,
            'district' => $location->district?->is_published ? $location->district : null,
            'articles' => GazetteArticle::query()
                ->published()
                ->where('location_id', $location->getKey())
                ->latest('published_at')
                ->limit(5)
                ->get(),
            'isPreview' => $isPreview,
            'scenes' => Scene::query()
                ->listed()
                ->where('location_id', $location->getKey())
                ->where('status', SceneStatus::Ongoing->value)
                ->latest('last_post_at')
                ->limit(6)
                ->get(),
        ]);
    }
}
