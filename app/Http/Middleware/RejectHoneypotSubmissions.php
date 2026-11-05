<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Champ piège : invisible pour les personnes, il n'est rempli que par les robots.
 */
class RejectHoneypotSubmissions
{
    public const FIELD = 'site_web';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') && filled($request->input(self::FIELD))) {
            throw ValidationException::withMessages([
                'email' => 'Le formulaire n’a pas pu être envoyé. Réessayez dans un instant.',
            ]);
        }

        return $next($request);
    }
}
