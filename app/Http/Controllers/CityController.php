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

        return view('city.index', [
            'page' => Page::query()->where('slug', 'ville')->where('is_published', true)->first(),
            'districts' => $districts,
            'others' => $others,
        ]);
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
