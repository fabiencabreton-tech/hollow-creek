<x-layout :title="'Demande '.$ticket->reference" robots="noindex">
    <div class="container">
        <header class="page-header">
            @auth
                <x-breadcrumbs :items="['Mes demandes' => route('contact.mine'), $ticket->reference => null]" />
            @endauth
            <div class="cluster">
                <p class="eyebrow">{{ $ticket->reference }} · {{ $ticket->motifLabel() }}</p>
                <span class="badge">{{ \App\Models\ContactTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}</span>
            </div>
            <h1>{{ $ticket->subject }}</h1>
        </header>

        <div class="stack page-body stack--loose conversation">
            <x-flash />
            <x-errors-summary />

            <div class="message-list">
                @foreach ($ticket->publicMessages as $message)
                    <article @class(['message', 'message--team' => $message->author === 'equipe'])>
                        <p class="message__meta"><strong>{{ $message->author === 'equipe' ? 'L’équipe de Hollow Creek' : $ticket->name }}</strong> · {{ $message->created_at?->locale('fr')->isoFormat('LLL') }}</p>
                        <div class="message__body">{{ $message->html() }}</div>
                    </article>
                @endforeach
            </div>

            <form class="form card" method="POST" action="{{ $replyUrl }}" novalidate>
                @csrf
                <x-honeypot />
                <div class="field">
                    <label for="champ-suivi">Répondre</label>
                    <textarea id="champ-suivi" name="body" rows="5" maxlength="5000" required>{{ old('body') }}</textarea>
                    @error('body')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div class="form-actions"><button type="submit" class="btn">Envoyer</button></div>
                @if ($ticket->status === \App\Models\ContactTicket::STATUS_CLOSED)
                    <p class="hint">La demande est close ; répondre la rouvre.</p>
                @endif
            </form>
        </div>
    </div>
</x-layout>
