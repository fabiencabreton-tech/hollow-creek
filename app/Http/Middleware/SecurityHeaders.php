<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité envoyés avec chaque page.
 *
 * Site public et espace joueur : politique de contenu stricte (CSP) ; seuls les scripts
 * du site, porteurs d'un jeton à usage unique (nonce), peuvent s'exécuter.
 * Administration : Filament et Alpine.js exigent des scripts en ligne, la politique y est
 * donc assouplie, mais reste limitée au site lui-même.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::cspNonce() ?? Vite::useCspNonce();

        $response = $next($request);

        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('Permissions-Policy', 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=(), interest-cohort=()');

        if (config('hollow.security.hsts') && $request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', $this->policy($request, $nonce));
        }

        return $response;
    }

    protected function policy(Request $request, string $nonce): string
    {
        $isAdmin = $request->is('admin', 'admin/*');

        $script = $isAdmin
            ? ["'self'", "'unsafe-inline'", "'unsafe-eval'"]
            : ["'self'", "'nonce-{$nonce}'"];

        $style = $isAdmin
            ? ["'self'", "'unsafe-inline'"]
            : ["'self'", "'nonce-{$nonce}'"];

        $connect = ["'self'"];

        // Serveur de développement Vite (npm run dev) : uniquement en local.
        if (app()->isLocal() && is_file(public_path('hot'))) {
            $hot = rtrim((string) file_get_contents(public_path('hot')));
            $script[] = $hot;
            $style[] = $hot;
            $connect[] = $hot;
            $connect[] = preg_replace('/^http/', 'ws', $hot);
        }

        $directives = [
            "default-src 'self'",
            'script-src '.implode(' ', $script),
            'style-src '.implode(' ', $style),
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            'connect-src '.implode(' ', $connect),
            "media-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ];

        if ($request->isSecure()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}
