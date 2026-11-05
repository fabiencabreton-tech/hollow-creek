<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un compte suspendu est déconnecté immédiatement, sur tous ses appareils.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->isSuspended()) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                abort(403, 'Ce compte est suspendu.');
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Ce compte est suspendu. Pour toute question, contactez l’équipe de Tsukimori.',
            ]);
        }

        return $next($request);
    }
}
