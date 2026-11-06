<?php

namespace App\Services;

use App\Jobs\SendPushNotification;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\DailyDigest;
use App\Notifications\SiteNotificationMail;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Point de passage unique des notifications : une ligne sur le site, l'e-mail selon la
 * préférence du joueur pour ce type (immédiat, résumé quotidien ou aucun), et une
 * notification push sur ses appareils abonnés.
 */
class Notifier
{
    /**
     * @param  string  $url  adresse relative au site (« /scenes/12 »)
     * @param  Notification|null  $mail  e-mail détaillé ; à défaut, un e-mail simple reprend le titre
     */
    public function send(User $user, string $type, string $title, string $url, ?string $body = null, ?Notification $mail = null): UserNotification
    {
        $mode = $user->notificationMode($type);

        $notification = new UserNotification;
        $notification->forceFill([
            'user_id' => $user->getKey(),
            'type' => $type,
            'title' => mb_substr($title, 0, 255),
            'body' => $body !== null ? mb_substr($body, 0, 500) : null,
            'url' => mb_substr($this->relative($url), 0, 500),
            'created_at' => now(),
            // Rien à envoyer plus tard si l'e-mail est immédiat ou désactivé.
            'mailed_at' => $mode === 'quotidien' ? null : now(),
        ])->save();

        if ($mode === 'immediat' && ! $user->isSuspended() && $user->hasVerifiedEmail()) {
            $user->notify($mail ?? new SiteNotificationMail($notification->title, $notification->body, $notification->url));
        }

        // Téléphones et ordinateurs abonnés : envoi en file d'attente, après l'enregistrement.
        if ($user->wantsPush($type) && ! $user->isSuspended() && app(PushSender::class)->isConfigured() && $user->pushSubscriptions()->exists()) {
            SendPushNotification::dispatch($notification->getKey())->afterCommit();
        }

        return $notification;
    }

    /**
     * Résumé quotidien des notifications non encore envoyées par e-mail.
     */
    public function sendDigests(): int
    {
        $sent = 0;

        $userIds = UserNotification::query()->whereNull('mailed_at')->distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            $user = User::query()->find($userId);
            $pending = UserNotification::query()->where('user_id', $userId)->whereNull('mailed_at')->orderBy('id')->get();

            if ($user === null || $pending->isEmpty()) {
                continue;
            }

            $wanted = $pending->filter(fn (UserNotification $notification): bool => $user->notificationMode($notification->type) === 'quotidien'
                && $notification->read_at === null);

            DB::transaction(function () use ($pending): void {
                UserNotification::query()->whereKey($pending->modelKeys())->update(['mailed_at' => now()]);
            });

            if ($wanted->isNotEmpty() && ! $user->isSuspended() && $user->hasVerifiedEmail()) {
                $user->notify(new DailyDigest($wanted->take(30)->values()->all(), $wanted->count()));
                $sent++;
            }
        }

        return $sent;
    }

    public function unreadCount(User $user): int
    {
        return UserNotification::query()->where('user_id', $user->getKey())->whereNull('read_at')->count();
    }

    private function relative(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);
        $fragment = parse_url($url, PHP_URL_FRAGMENT);

        return '/'.ltrim($path, '/').($query ? '?'.$query : '').($fragment ? '#'.$fragment : '');
    }
}
