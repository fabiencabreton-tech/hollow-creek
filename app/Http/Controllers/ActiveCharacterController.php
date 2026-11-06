<?php

namespace App\Http\Controllers;

use App\Enums\CharacterStatus;
use App\Models\Character;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Change le personnage actif : il est proposé en premier dans tous les formulaires.
 */
class ActiveCharacterController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate(['character_id' => ['required', 'integer']], [], ['character_id' => 'personnage']);
        $character = Character::query()->find((int) $data['character_id']);

        if ($character === null || $character->user_id !== $request->user()->getKey() || $character->status !== CharacterStatus::Accepted) {
            throw ValidationException::withMessages(['character_id' => 'Choisissez l’un de vos personnages acceptés.']);
        }

        $request->user()->forceFill(['active_character_id' => $character->getKey()])->save();

        return back()->with('status', 'Vous jouez maintenant '.$character->fullName().'.');
    }
}
