<?php

namespace App\Http\Controllers;

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
        ]);
    }

    protected function greeting(): string
    {
        $hour = (int) now()->format('G');

        return ($hour >= 18 || $hour < 5) ? 'Bonsoir' : 'Bonjour';
    }
}
