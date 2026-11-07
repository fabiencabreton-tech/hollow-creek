<?php

namespace App\Http\Controllers;

use App\Enums\CharacterStatus;
use App\Enums\PredefinedStatus;
use App\Enums\SpeciesKind;
use App\Models\Character;
use App\Models\Event;
use App\Models\Family;
use App\Models\GazetteArticle;
use App\Models\PredefinedCharacter;
use App\Models\Scene;
use App\Models\Species;
use App\Support\CityMap;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Accueil des visiteurs : une vitrine de Tsukimori. Un membre connecté arrive
 * directement sur son tableau de bord.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user() && ! $request->boolean('vitrine')) {
            return redirect()->route('dashboard');
        }

        $headline = GazetteArticle::query()->published()->where('is_featured', true)->latest('published_at')->first()
            ?? GazetteArticle::query()->published()->latest('published_at')->first();

        // Derniers articles, sous « À la une » (comme une première page de journal).
        $latest = GazetteArticle::query()
            ->published()
            ->when($headline, fn ($query) => $query->whereKeyNot($headline->getKey()))
            ->latest('published_at')
            ->limit(3)
            ->get();

        $species = Species::query()->where('is_published', true)->orderBy('sort_order')->orderBy('name')->get();

        return view('home', [
            'headline' => $headline,
            'latest' => $latest,
            'event' => Event::query()->active()->latest('published_at')->first(),
            'scenes' => Scene::query()
                ->listed()
                ->whereNotNull('last_post_at')
                ->with('location')
                ->latest('last_post_at')
                ->limit(3)
                ->get(),
            'natures' => $species->filter(fn (Species $item) => $item->kind === SpeciesKind::Nature)->values(),
            'traits' => $species->reject(fn (Species $item) => $item->kind === SpeciesKind::Nature)->values(),
            'families' => Family::query()->published()->where('is_major', true)->ordered()->with('district')->get(),
            'roles' => PredefinedCharacter::query()->published()->where('status', PredefinedStatus::Open->value)->with(['species', 'family'])->orderBy('sort_order')->limit(4)->get(),
            'rolesCount' => PredefinedCharacter::query()->published()->where('status', PredefinedStatus::Open->value)->count(),
            'charactersCount' => Character::query()->where('status', CharacterStatus::Accepted->value)->count(),
        ] + CityMap::build());
    }
}
