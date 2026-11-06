<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use App\Support\WebPush\WebPushCrypto;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Envoi des notifications push aux appareils d'un joueur.
 *
 * Le contenu est chiffré pour l'appareil (seul lui peut le lire, pas le service push) et
 * ne contient qu'un titre et une adresse du site : jamais le texte d'un RP ou d'un message.
 */
class PushSender
{
    public function isConfigured(): bool
    {
        return filled(config('hollow.push.public_key')) && filled(config('hollow.push.private_key'));
    }

    public function publicKey(): ?string
    {
        return $this->isConfigured() ? (string) config('hollow.push.public_key') : null;
    }

    /**
     * Adresse d'abonnement acceptée : celle d'un service push de navigateur connu.
     */
    public function allows(string $endpoint): bool
    {
        $parts = parse_url($endpoint);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $scheme = $parts['scheme'] ?? '';

        if ($host === '' || strlen($endpoint) > 1000) {
            return false;
        }

        $hostWithPort = $host.(isset($parts['port']) ? ':'.$parts['port'] : '');
        if (in_array($hostWithPort, (array) config('hollow.push.extra_hosts'), true)) {
            return in_array($scheme, ['http', 'https'], true);
        }

        if ($scheme !== 'https' || isset($parts['port'])) {
            return false;
        }

        foreach ((array) config('hollow.push.hosts') as $pattern) {
            if (Str::is($pattern, $host)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{title: string, body?: string|null, url: string}  $payload
     * @return int nombre d'appareils atteints
     */
    public function sendToUser(User $user, array $payload): int
    {
        $reached = 0;

        foreach ($user->pushSubscriptions()->get() as $subscription) {
            if ($this->send($subscription, $payload)) {
                $reached++;
            }
        }

        return $reached;
    }

    /**
     * @param  array{title: string, body?: string|null, url: string}  $payload
     */
    public function send(PushSubscription $subscription, array $payload): bool
    {
        if (! $this->isConfigured() || ! $this->allows($subscription->endpoint)) {
            return false;
        }

        try {
            $json = (string) json_encode(array_filter($payload, fn ($value) => $value !== null), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $body = WebPushCrypto::encrypt($json, $subscription->public_key, $subscription->auth_token);
            $publicKey = (string) config('hollow.push.public_key');
            $token = WebPushCrypto::vapidToken($subscription->endpoint, (string) config('hollow.push.subject'), $publicKey, (string) config('hollow.push.private_key'));

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => "vapid t={$token}, k={$publicKey}",
                    'Content-Encoding' => 'aes128gcm',
                    'TTL' => '86400',
                    'Urgency' => 'normal',
                ])
                ->withBody($body, 'application/octet-stream')
                ->post($subscription->endpoint);
        } catch (Throwable $exception) {
            Log::warning('Notification push non envoyée : '.$exception->getMessage(), ['abonnement' => $subscription->getKey()]);

            return false;
        }

        if ($response->successful()) {
            $subscription->forceFill(['last_used_at' => now()])->save();

            return true;
        }

        // Abonnement expiré ou retiré par le navigateur : on l'oublie.
        if (in_array($response->status(), [404, 410], true)) {
            $subscription->delete();

            return false;
        }

        Log::warning('Service push : réponse '.$response->status(), ['abonnement' => $subscription->getKey(), 'reponse' => Str::limit($response->body(), 300)]);

        return false;
    }
}
