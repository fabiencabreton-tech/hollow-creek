<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksPreview;
use App\Models\PredefinedCharacter;
use App\Services\CharacterWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Personnages prédéfinis proposés par l'équipe.
 */
class PredefinedController extends Controller
{
    use ChecksPreview;

    public function index(): View
    {
        return view('predefined.index', [
            'predefined' => PredefinedCharacter::query()
                ->published()
                ->with(['species', 'family'])
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get(),
        ]);
    }

    public function show(Request $request, PredefinedCharacter $predefined): View
    {
        $isPreview = $this->ensureVisible($request, $predefined->is_published, 'predefinis.gerer');

        $predefined->load(['species', 'family']);

        return view('predefined.show', [
            'predefined' => $predefined,
            'isPreview' => $isPreview,
            'remaining' => $request->user() ? app(CharacterWorkflow::class)->remainingSlots($request->user()) : null,
        ]);
    }

    public function claim(Request $request, PredefinedCharacter $predefined, CharacterWorkflow $workflow): RedirectResponse
    {
        $character = $workflow->start($request->user(), $predefined);

        return redirect()->route('characters.edit', [$character, 1])
            ->with('status', 'Fiche commencée à partir du rôle « '.$predefined->title.' ». Le rôle sera réservé pour vous à l’envoi de la fiche.');
    }
}
