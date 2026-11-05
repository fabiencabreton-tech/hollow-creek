<x-layout title="Mes demandes" robots="noindex">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Tableau de bord' => route('dashboard'), 'Mes demandes' => null]" />
            <h1>Mes demandes</h1>
        </header>

        <div class="stack page-body stack--loose">
            <div><a class="btn" href="{{ route('contact.create') }}">Nouvelle demande</a></div>
            @if ($tickets->isEmpty())
                <p class="empty-note">Aucune demande envoyée à l’équipe.</p>
            @else
                <ul class="plain-list inbox">
                    @foreach ($tickets as $ticket)
                        <li @class(['inbox__item', 'inbox__item--turn' => $ticket->status === \App\Models\ContactTicket::STATUS_ANSWERED])>
                            <a class="inbox__title" href="{{ route('contact.mine.show', $ticket) }}">{{ $ticket->subject }}</a>
                            <span class="muted">{{ $ticket->reference }}</span>
                            <span class="badge">{{ \App\Models\ContactTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layout>
