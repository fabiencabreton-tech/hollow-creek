<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserBlock;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Bloquer ou débloquer un joueur.
 */
class BlockController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['pseudo' => ['required', 'string', 'max:30']]);
        $user = $request->user();
        $target = User::query()->where('pseudo', $data['pseudo'])->first();

        if ($target === null || $target->is($user)) {
            return back()->withErrors(['pseudo' => 'Ce joueur est introuvable.']);
        }

        if ($target->isStaff()) {
            return back()->withErrors(['pseudo' => 'Un membre de l’équipe ne peut pas être bloqué ; écrivez-nous si quelque chose ne va pas.']);
        }

        $block = UserBlock::query()->firstOrNew(['user_id' => $user->getKey(), 'blocked_user_id' => $target->getKey()]);

        if (! $block->exists) {
            $block->forceFill(['user_id' => $user->getKey(), 'blocked_user_id' => $target->getKey()])->save();
            ActivityLogger::record('compte.blocage', $target, $target->pseudo, causer: $user);
        }

        return back()->with('status', $target->pseudo.' est bloqué : plus de message, d’invitation ni de proposition de lien entre vous.');
    }

    public function destroy(Request $request, UserBlock $block): RedirectResponse
    {
        abort_unless($block->user_id === $request->user()->getKey(), 404);

        $block->delete();

        return redirect()->to(route('account.edit').'#bloques')->with('status', 'Blocage levé.');
    }
}
