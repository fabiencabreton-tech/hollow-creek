<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Annonce d'un nouvel événement à tous les joueurs, selon leurs préférences.
 */
class AnnounceEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Event $event) {}

    public function handle(Notifier $notifier): void
    {
        if (! $this->event->is_published) {
            return;
        }

        User::query()
            ->whereNotNull('email_verified_at')
            ->whereNull('suspended_at')
            ->chunkById(200, function ($users) use ($notifier): void {
                foreach ($users as $user) {
                    $notifier->send(
                        $user,
                        'evenement',
                        'Nouvel événement : '.$this->event->title,
                        route('events.show', $this->event, absolute: false),
                        $this->event->summary,
                    );
                }
            });
    }
}
