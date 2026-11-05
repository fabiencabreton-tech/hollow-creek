<?php

namespace App\Http\Controllers;

use App\Enums\SpeciesKind;
use App\Http\Controllers\Concerns\ChecksPreview;
use App\Models\Species;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SpeciesController extends Controller
{
    use ChecksPreview;

    public function index(): View
    {
        $species = Species::query()->published()->ordered()->with('parent')->get();

        // Natures d'abord, puis spécialisations, puis conditions.
        $groups = collect(SpeciesKind::cases())
            ->mapWithKeys(fn (SpeciesKind $kind) => [$kind->value => $species->filter(fn (Species $item) => $item->kind === $kind)->values()])
            ->filter(fn ($items) => $items->isNotEmpty());

        return view('universe.species-index', [
            'groups' => $groups,
        ]);
    }

    public function show(Request $request, Species $species): View
    {
        $isPreview = $this->ensureVisible($request, $species->is_published, 'univers.gerer');

        return view('universe.species-show', [
            'species' => $species->load('parent'),
            'allowedNatures' => Species::query()->whereIn('slug', $species->allowed_natures ?? [])->get(),
            'others' => Species::query()->published()->ordered()->whereKeyNot($species->getKey())->get(),
            'isPreview' => $isPreview,
        ]);
    }
}
