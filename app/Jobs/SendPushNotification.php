<?php

namespace App\Jobs;

use App\Models\UserNotification;
use App\Services\PushSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Prévient les appareils du joueur d'une nouvelle notification du site.
 */
class SendPushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $notificationId) {}

    public function handle(PushSender $sender): void
    {
        $notification = UserNotification::query()->with('user')->find($this->notificationId);
        $user = $notification?->user;

        if ($user === null || $notification->read_at !== null || $user->isSuspended() || ! $user->hasVerifiedEmail() || ! $user->wantsPush($notification->type)) {
            return;
        }

        $sender->sendToUser($user, [
            'title' => $notification->title,
            'url' => route('notifications.open', $notification, false),
            'tag' => 'notification-'.$notification->getKey(),
        ]);
    }
}
