<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksPreview;
use App\Models\Species;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SpeciesController extends Controller
{
    use ChecksPreview;

    public function index(): View
    {
        return view('universe.species-index', [
            'species' => Species::query()->published()->ordered()->get(),
        ]);
    }

    public function show(Request $request, Species $species): View
    {
        $isPreview = $this->ensureVisible($request, $species->is_published, 'univers.gerer');

        return view('universe.species-show', [
            'species' => $species,
            'others' => Species::query()->published()->ordered()->whereKeyNot($species->getKey())->get(),
            'isPreview' => $isPreview,
        ]);
    }
}
