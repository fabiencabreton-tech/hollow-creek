<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chez Alwaysdata, un proxy frontal reçoit les visiteurs, gère le HTTPS puis transmet
 * la requête au serveur PHP en HTTP, en ajoutant X-Real-IP (adresse du visiteur)
 * et X-Forwarded-Proto (http ou https).
 *
 * Ces en-têtes ne sont crus que si la requête vient réellement d'une adresse interne
 * (le proxy) : un visiteur ne peut pas usurper une autre adresse IP en les envoyant lui-même.
 * Sans cela, toutes les limites anti-abus par adresse IP s'appliqueraient au proxy entier.
 */
class TrustHostingProxy
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('hollow.security.trust_hosting_proxy')) {
            return $next($request);
        }

        $remote = (string) $request->server->get('REMOTE_ADDR', '');
        $ranges = (array) config('hollow.security.trusted_proxy_ranges', []);

        if ($remote === '' || $ranges === [] || ! IpUtils::checkIp($remote, $ranges)) {
            return $next($request);
        }

        $realIp = trim((string) $request->headers->get('X-Real-IP', ''));

        if ($realIp !== '' && filter_var($realIp, FILTER_VALIDATE_IP) !== false) {
            $request->server->set('REMOTE_ADDR', $realIp);
        }

        if (strtolower((string) $request->headers->get('X-Forwarded-Proto', '')) === 'https') {
            $request->server->set('HTTPS', 'on');
            $request->server->set('SERVER_PORT', 443);
        }

        return $next($request);
    }
}
