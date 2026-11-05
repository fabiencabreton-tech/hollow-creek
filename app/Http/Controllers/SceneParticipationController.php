<?php

namespace App\Http\Controllers;

use App\Enums\ParticipantStatus;
use App\Models\Character;
use App\Models\Scene;
use App\Models\SceneParticipant;
use App\Services\SceneService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Invitations, arrivées et départs dans une scène.
 */
class SceneParticipationController extends Controller
{
    public function __construct(private readonly SceneService $scenes) {}

    public function invite(Request $request, Scene $scene): RedirectResponse
    {
        Gate::authorize('invite', $scene);

        $data = $request->validate(['character_id' => ['required', 'integer']], [], ['character_id' => 'personnage']);

        $this->scenes->invite($scene, $request->user(), Character::query()->findOrFail($data['character_id']));

        return back()->with('status', 'Invitation envoyée.');
    }

    public function accept(Request $request, Scene $scene, SceneParticipant $participant): RedirectResponse
    {
        $this->ensureOwnInvitation($request, $scene, $participant);

        $data = $request->validate(['character_id' => ['required', 'integer']], [], ['character_id' => 'personnage']);

        $this->scenes->acceptInvitation($participant, Character::query()->findOrFail($data['character_id']));

        return redirect()->route('scenes.show', $scene)->with('status', 'Vous participez à la scène.');
    }

    public function decline(Request $request, Scene $scene, SceneParticipant $participant): RedirectResponse
    {
        $this->ensureOwnInvitation($request, $scene, $participant);

        $this->scenes->declineInvitation($participant);

        return redirect()->route('scenes.mine')->with('status', 'Invitation déclinée.');
    }

    public function cancel(Request $request, Scene $scene, SceneParticipant $participant): RedirectResponse
    {
        Gate::authorize('manage', $scene);
        abort_unless($participant->scene_id === $scene->getKey(), 404);

        $this->scenes->cancelInvitation($participant);

        return back()->with('status', 'Invitation retirée.');
    }

    public function join(Request $request, Scene $scene): RedirectResponse
    {
        Gate::authorize('join', $scene);

        $data = $request->validate(['character_id' => ['required', 'integer']], [], ['character_id' => 'personnage']);

        $this->scenes->join($scene, $request->user(), Character::query()->findOrFail($data['character_id']));

        return redirect()->route('scenes.show', $scene)->with('status', 'Vous avez rejoint la scène.');
    }

    public function leave(Request $request, Scene $scene): RedirectResponse
    {
        Gate::authorize('leave', $scene);

        $this->scenes->leave($scene, $request->user());

        return redirect()->route('scenes.mine')->with('status', 'Vous avez quitté la scène.');
    }

    private function ensureOwnInvitation(Request $request, Scene $scene, SceneParticipant $participant): void
    {
        abort_unless(
            $participant->scene_id === $scene->getKey()
                && $participant->user_id === $request->user()->getKey()
                && $participant->status === ParticipantStatus::Invited,
            404,
        );
    }
}
