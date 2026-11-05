<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * « Mon compte » : pseudo, e-mail, mot de passe, double authentification et appareils connectés.
 * Les enregistrements eux-mêmes passent par les routes sécurisées de Fortify.
 */
class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.edit', [
            'user' => $request->user(),
            'sessions' => $this->sessions($request),
            'passwordRecentlyConfirmed' => (time() - (int) $request->session()->get('auth.password_confirmed_at', 0))
                < (int) config('auth.password_timeout', 10800),
        ]);
    }

    /**
     * Préférences : e-mails envoyés par les scènes.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'scene_mail' => ['required', 'in:toutes,tour,aucune'],
        ]);

        $request->user()->forceFill(['scene_mail' => $data['scene_mail']])->save();

        return redirect()->to(route('account.edit').'#preferences')->with('status', 'Préférences enregistrées.');
    }

    /**
     * Le mot de passe vient d'être confirmé : retour à la section « double authentification ».
     */
    public function security(): RedirectResponse
    {
        return redirect()->to(route('account.edit').'#double-authentification');
    }

    /**
     * Déconnecte tous les autres appareils (mot de passe actuel requis).
     */
    public function destroyOtherSessions(Request $request): RedirectResponse
    {
        $request->validateWithBag('logoutOtherSessions', [
            'password' => ['required', 'string', 'current_password:web'],
        ], [
            'password.current_password' => 'Le mot de passe est incorrect.',
        ]);

        $user = $request->user();

        // Les jetons « se souvenir de moi » des autres appareils deviennent invalides.
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $user->getAuthIdentifier())
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        return redirect()->to(route('account.edit').'#appareils')
            ->with('status', 'other-sessions-logged-out');
    }

    /**
     * @return Collection<int, object>
     */
    protected function sessions(Request $request): Collection
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn (object $session): object => (object) [
                'device' => $this->describeAgent((string) $session->user_agent),
                'ip_address' => $session->ip_address,
                'is_current' => $session->id === $request->session()->getId(),
                'last_active' => Carbon::createFromTimestamp((int) $session->last_activity)->locale('fr')->diffForHumans(),
            ]);
    }

    protected function describeAgent(string $agent): string
    {
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Navigateur inconnu',
        };

        $system = match (true) {
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS X') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'système inconnu',
        };

        return "{$browser} sur {$system}";
    }
}
