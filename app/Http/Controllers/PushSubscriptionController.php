<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\PushSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Abonnement des appareils du joueur aux notifications push.
 */
class PushSubscriptionController extends Controller
{
    public function __construct(private readonly PushSender $sender) {}

    public function store(Request $request): JsonResponse
    {
        if (! $this->sender->isConfigured()) {
            return response()->json(['message' => 'Les notifications sur appareil ne sont pas activées sur le site.'], 503);
        }

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:1000', 'url'],
            'keys.p256dh' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9_-]+$/'],
            'keys.auth' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        if (! $this->sender->allows($data['endpoint'])) {
            return response()->json(['message' => 'Service de notification inconnu.'], 422);
        }

        // Un appareil appartient au dernier compte qui s'y est abonné.
        $hash = PushSubscription::hashEndpoint($data['endpoint']);
        $subscription = PushSubscription::query()->where('endpoint_hash', $hash)->first() ?? new PushSubscription;
        $subscription->forceFill([
            'endpoint_hash' => $hash,
            'user_id' => $request->user()->getKey(),
            'endpoint' => $data['endpoint'],
            'public_key' => $data['keys']['p256dh'],
            'auth_token' => $data['keys']['auth'],
            'device' => $this->deviceLabel((string) $request->userAgent()),
        ])->save();

        // Simple remise à jour au chargement d'une page : pas de nouvelle alerte de bienvenue.
        if ($request->boolean('silencieux')) {
            return response()->json(['ok' => true]);
        }

        $delivered = $this->sender->send($subscription, [
            'title' => 'Notifications activées',
            'body' => 'Cet appareil vous préviendra des réponses, des messages et des décisions de l’équipe.',
            'url' => route('dashboard', absolute: false),
            'tag' => 'bienvenue',
        ]);

        session()->flash('status', $delivered
            ? 'Notifications activées sur cet appareil : une alerte de bienvenue vient d’y être envoyée.'
            : 'Cet appareil est abonné, mais l’alerte de bienvenue n’a pas pu partir. Réessayez avec « Envoyer une notification d’essai ».');

        return response()->json(['ok' => true, 'delivered' => $delivered]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:1000']]);

        PushSubscription::query()
            ->where('user_id', $request->user()->getKey())
            ->where('endpoint_hash', PushSubscription::hashEndpoint($data['endpoint']))
            ->delete();

        session()->flash('status', 'Cet appareil ne recevra plus de notifications.');

        return response()->json(['ok' => true]);
    }

    public function forget(Request $request, PushSubscription $subscription): RedirectResponse
    {
        abort_unless($subscription->user_id === $request->user()->getKey(), 404);

        $subscription->delete();

        return redirect()->to(route('account.edit').'#notifications-appareils')->with('status', 'Cet appareil ne recevra plus de notifications.');
    }

    public function test(Request $request): RedirectResponse
    {
        $reached = $this->sender->sendToUser($request->user(), [
            'title' => 'Une lanterne s’allume sur le lac',
            'body' => 'Notification d’essai : tout fonctionne.',
            'url' => route('account.edit', absolute: false).'#notifications-appareils',
            'tag' => 'essai',
        ]);

        $message = $reached > 0
            ? 'Notification d’essai envoyée à '.$reached.' appareil'.($reached > 1 ? 's' : '').'.'
            : 'Aucun appareil n’a pu être prévenu : réactivez les notifications sur l’appareil voulu.';

        return redirect()->to(route('account.edit').'#notifications-appareils')->with('status', $message);
    }

    private function deviceLabel(string $agent): string
    {
        $system = match (true) {
            str_contains($agent, 'iPhone') => 'iPhone',
            str_contains($agent, 'iPad') => 'iPad',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') => 'Mac',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Appareil',
        };

        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($agent, 'Firefox') => 'Firefox',
            str_contains($agent, 'Chrome') => 'Chrome',
            str_contains($agent, 'Safari') => 'Safari',
            default => null,
        };

        return $browser ? "{$system} · {$browser}" : $system;
    }
}
