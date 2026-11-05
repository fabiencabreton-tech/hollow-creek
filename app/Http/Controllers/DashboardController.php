<?php

namespace App\Http\Controllers;

use App\Services\CharacterWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'user' => $user,
            'isStaff' => $user->isStaff(),
            'hasTwoFactor' => $user->hasConfirmedTwoFactor(),
            'greeting' => $this->greeting(),
            'characters' => $user->characters()->with('species')->whereNot('status', \App\Enums\CharacterStatus::Archived->value)->orderBy('first_name')->get(),
            'remaining' => app(CharacterWorkflow::class)->remainingSlots($user),
        ]);
    }

    protected function greeting(): string
    {
        $hour = (int) now()->format('G');

        return ($hour >= 18 || $hour < 5) ? 'Bonsoir' : 'Bonjour';
    }
}
