<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\UserNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * La lanterne : notifications du site, compteurs rafraîchis toutes les 60 secondes.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => UserNotification::query()
                ->where('user_id', $request->user()->getKey())
                ->latest('id')
                ->paginate(30),
        ]);
    }

    /**
     * Ouvre une notification : marquée lue, puis redirection vers son adresse sur le site.
     */
    public function open(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->getKey(), 404);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        $url = str_starts_with($notification->url, '/') && ! str_starts_with($notification->url, '//') ? $notification->url : '/';

        return redirect()->to($url);
    }

    public function readAll(Request $request): RedirectResponse
    {
        UserNotification::query()
            ->where('user_id', $request->user()->getKey())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()->route('notifications.index')->with('status', 'Toutes les notifications sont marquées comme lues.');
    }

    public function counts(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'notifications' => UserNotification::query()->where('user_id', $user->getKey())->whereNull('read_at')->count(),
            'messages' => Conversation::unreadCountFor($user),
        ]);
    }
}
