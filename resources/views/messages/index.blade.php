<x-layout title="Messages" robots="noindex">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Tableau de bord' => route('dashboard'), 'Messages' => null]" />
            <h1>Messages</h1>
            <p class="lede">Entre joueurs, hors jeu : pour préparer une scène, se présenter, s’organiser. L’équipe ne lit pas vos conversations, sauf un message que vous lui signalez.</p>
        </header>

        <div class="stack page-body stack--loose">
            <x-flash />

            <div class="cluster">
                <a class="btn" href="{{ route('messages.create') }}">Nouvelle conversation</a>
                @if ($archived)
                    <a class="btn btn--ghost" href="{{ route('messages.index') }}">Conversations en cours</a>
                @else
                    <a class="btn btn--ghost" href="{{ route('messages.index', ['archives' => 1]) }}">Archives</a>
                @endif
            </div>

            <form class="filters" method="GET" action="{{ route('messages.index') }}" role="search">
                @if ($archived)<input type="hidden" name="archives" value="1">@endif
                <div class="field">
                    <label for="recherche-messages">Rechercher</label>
                    <input id="recherche-messages" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Pseudo, sujet ou mot">
                </div>
                <div class="form-actions"><button type="submit" class="btn btn--ghost">Rechercher</button></div>
            </form>

            @if ($participations->isEmpty())
                <p class="empty-note">{{ $search !== '' ? 'Aucune conversation ne correspond.' : ($archived ? 'Aucune conversation archivée.' : 'Aucune conversation pour le moment.') }}</p>
            @else
                <ul class="plain-list inbox">
                    @foreach ($participations as $participation)
                        @php
                            $conversation = $participation->conversation;
                            $unread = ($lastIds[$conversation->id] ?? 0) > ($participation->last_read_message_id ?? 0);
                        @endphp
                        <li @class(['inbox__item', 'inbox__item--turn' => $unread])>
                            <a class="inbox__title" href="{{ route('messages.show', $conversation) }}">{{ $conversation->title(auth()->user()) }}</a>
                            @if (filled($conversation->subject))
                                <span class="muted">avec {{ $conversation->others(auth()->user())->pluck('pseudo')->join(', ') }}</span>
                            @endif
                            @if ($unread)<span class="badge badge--wait">Non lu</span>@endif
                            <span class="muted">{{ $conversation->last_message_at?->locale('fr')->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layout>
