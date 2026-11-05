<x-layout title="Notifications" robots="noindex">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Tableau de bord' => route('dashboard'), 'Notifications' => null]" />
            <h1>Notifications</h1>
            <p class="lede">Tout ce qui vous concerne à Hollow Creek. Les e-mails se règlent dans <a href="{{ route('account.edit') }}#preferences">Mon compte</a>.</p>
        </header>

        <div class="stack page-body stack--loose">
            <x-flash />

            @if ($notifications->isEmpty())
                <p class="empty-note">Aucune notification pour le moment.</p>
            @else
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn--ghost">Tout marquer comme lu</button>
                </form>

                <ul class="plain-list notification-list">
                    @foreach ($notifications as $notification)
                        <li @class(['notification', 'notification--unread' => $notification->read_at === null])>
                            <a class="notification__link" href="{{ route('notifications.open', $notification) }}">
                                <span class="notification__title">{{ $notification->title }}</span>
                                @if ($notification->body)
                                    <span class="notification__body">{{ \Illuminate\Support\Str::limit($notification->body, 160) }}</span>
                                @endif
                                <span class="notification__meta">{{ $notification->typeLabel() }} · {{ $notification->created_at?->locale('fr')->diffForHumans() }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{ $notifications->links('partials.pagination') }}
            @endif
        </div>
    </div>
</x-layout>
