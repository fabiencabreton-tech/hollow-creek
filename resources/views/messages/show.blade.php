@php($viewer = auth()->user())
<x-layout :title="$conversation->title($viewer)" robots="noindex">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Messages' => route('messages.index'), $conversation->title($viewer) => null]" />
            <h1>{{ $conversation->title($viewer) }}</h1>
            <p class="muted">Avec {{ $conversation->others($viewer)->pluck('pseudo')->join(', ', ' et ') ?: 'personne' }}</p>
        </header>

        <div class="stack page-body stack--loose conversation">
            <x-flash />
            <x-errors-summary />

            <div class="message-list" data-poll-url="{{ route('messages.poll', $conversation) }}" data-last-id="{{ $messages->max('id') ?? 0 }}" aria-live="polite">
                @foreach ($messages as $message)
                    @include('messages.partials.message', ['message' => $message, 'viewer' => $viewer])
                @endforeach
            </div>

            @if ($canSend)
                <form class="form card" method="POST" action="{{ route('messages.send', $conversation) }}" novalidate>
                    @csrf
                    <div class="field">
                        <label for="champ-reponse-message">Votre message</label>
                        <textarea id="champ-reponse-message" name="body" rows="4" maxlength="5000" required>{{ old('body') }}</textarea>
                        @error('body')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-actions"><button type="submit" class="btn">Envoyer</button></div>
                </form>
            @else
                <p class="muted">Cette conversation est fermée : il n’y a plus d’autre participant, ou un blocage existe.</p>
            @endif

            <div class="cluster">
                <form method="POST" action="{{ route('messages.archive', $conversation) }}">
                    @csrf
                    <button type="submit" class="link-button">{{ $participant?->archived_at ? 'Sortir des archives' : 'Archiver' }}</button>
                </form>
                @if ($conversation->participants->count() > 2)
                    <form method="POST" action="{{ route('messages.leave', $conversation) }}" data-confirm="Quitter cette conversation de groupe ?">
                        @csrf
                        <button type="submit" class="link-button">Quitter la conversation</button>
                    </form>
                @endif
                @foreach ($conversation->others($viewer) as $other)
                    @unless ($other->isStaff())
                        <form method="POST" action="{{ route('blocks.store') }}" data-confirm="Bloquer {{ $other->pseudo }} ? Plus aucun message, invitation ni proposition de lien entre vous.">
                            @csrf
                            <input type="hidden" name="pseudo" value="{{ $other->pseudo }}">
                            <button type="submit" class="link-button">Bloquer {{ $other->pseudo }}</button>
                        </form>
                    @endunless
                @endforeach
            </div>
        </div>
    </div>
</x-layout>
