<?php

namespace App\Http\Controllers;

use App\Enums\VictimStatus;
use App\Models\KirisawaVictim;
use Illuminate\Contracts\View\View;

/**
 * Registre public des victimes de l'incendie de Kirisawa (31 octobre 1996).
 * Les notes de l'équipe n'y figurent jamais.
 */
class KirisawaController extends Controller
{
    public function __invoke(): View
    {
        $victims = KirisawaVictim::query()->published()->with('character')->orderBy('number')->get();

        return view('universe.kirisawa', [
            'victims' => $victims,
            'openCount' => $victims->where('status', VictimStatus::Open)->count(),
            'total' => KirisawaVictim::TOTAL,
        ]);
    }
}
