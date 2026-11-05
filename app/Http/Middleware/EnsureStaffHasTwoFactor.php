<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * L'administration n'est accessible qu'avec la double authentification activée et confirmée.
 * Un membre de l'équipe qui ne l'a pas encore activée est envoyé vers « Mon compte ».
 */
class EnsureStaffHasTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! config('hollow.security.staff_two_factor')) {
            return $next($request);
        }

        if ($user instanceof User && ! $user->hasConfirmedTwoFactor()) {
            if ($request->expectsJson()) {
                abort(403, 'Double authentification requise.');
            }

            return redirect()->to(route('account.edit').'#double-authentification')->with(
                'warning',
                'Pour ouvrir l’administration, activez d’abord la double authentification sur votre compte.'
            );
        }

        return $next($request);
    }
}
