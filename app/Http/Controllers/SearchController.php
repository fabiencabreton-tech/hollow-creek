<?php

namespace App\Http\Controllers;

use App\Enums\ParticipantStatus;
use App\Enums\SceneStatus;
use App\Models\Character;
use App\Models\Faceclaim;
use App\Models\GazetteArticle;
use App\Models\Location;
use App\Models\Scene;
use App\Support\Search;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Recherche dans le site. Une scène privée n'apparaît que pour ses participants.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $term = mb_substr(trim((string) $request->query('q', '')), 0, 80);
        $user = $request->user();
        $results = null;

        if (mb_strlen($term) >= 2) {
            $results = [
                'characters' => Search::apply(Character::query()->accepted()->with('species'), ['first_name', 'last_name', 'profession'], $term, indexed: true)
                    ->orderBy('first_name')->limit(20)->get(),
                'scenes' => Search::apply(Scene::query()->with('location'), ['title'], $term, indexed: true)
                    ->whereIn('status', [SceneStatus::Ongoing->value, SceneStatus::Paused->value, SceneStatus::Finished->value])
                    ->where(function ($query) use ($user): void {
                        $query->public();

                        if ($user) {
                            $query->orWhereHas('participants', fn ($participants) => $participants
                                ->where('user_id', $user->getKey())
                                ->whereIn('status', [ParticipantStatus::Invited->value, ParticipantStatus::Accepted->value, ParticipantStatus::Left->value]));
                        }
                    })
                    ->latest('last_post_at')->limit(20)->get(),
                'faceclaims' => Search::apply(Faceclaim::query()->with('activeClaim'), ['name'], $term, indexed: true)->orderBy('name')->limit(20)->get(),
                'locations' => Search::apply(Location::query()->published(), ['name', 'summary'], $term)->orderBy('name')->limit(10)->get(),
                'articles' => Search::apply(GazetteArticle::query()->published(), ['title', 'excerpt'], $term, indexed: true)->latest('published_at')->limit(10)->get(),
            ];
        }

        return view('search.index', [
            'term' => $term,
            'results' => $results,
        ]);
    }
}
