<?php

namespace App\Http\Controllers;

use App\Models\Census;
use App\Models\CensusResponse;
use App\Models\Character;
use App\Services\CensusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Recensement d'activité côté joueur.
 */
class CensusController extends Controller
{
    public function show(Request $request): View
    {
        $census = Census::query()->open()->latest('id')->first();
        $characters = Character::query()->where('user_id', $request->user()->getKey())->accepted()->orderBy('first_name')->get();

        return view('census.show', [
            'census' => $census,
            'characters' => $characters,
            'confirmed' => $census
                ? CensusResponse::query()->where('census_id', $census->getKey())->whereIn('character_id', $characters->modelKeys())->pluck('character_id')->all()
                : [],
        ]);
    }

    public function store(Request $request, CensusService $service): RedirectResponse
    {
        $census = Census::query()->open()->latest('id')->firstOrFail();

        $data = $request->validate([
            'characters' => ['required', 'array'],
            'characters.*' => ['integer'],
        ], ['characters.required' => 'Cochez au moins un personnage.']);

        $count = $service->confirm($census, $request->user(), $data['characters']);

        return redirect()->route('census.show')->with('status', $count > 1 ? $count.' personnages recensés. Merci !' : 'Personnage recensé. Merci !');
    }
}
