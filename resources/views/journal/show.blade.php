<x-layout :title="$entry->heading().' — '.$character->fullName()" :robots="$entry->is_private ? 'noindex' : null">
    <div class="container container--narrow">
        <article>
            <header class="page-header">
                <x-breadcrumbs :items="[$character->fullName() => route('characters.show', $character), 'Journal' => route('journal.index', $character), $entry->heading() => null]" />
                <p class="eyebrow">
                    Journal de {{ $character->fullName() }}
                    @if ($entry->rp_date) · {{ $entry->rp_date }}@endif
                    @if ($entry->is_private) · <span class="badge">Privée</span>@endif
                </p>
                <h1>{{ $entry->heading() }}</h1>
            </header>

            <div class="stack stack--loose">
                <x-flash />
                @if ($entry->is_private)
                    <p class="alert">Entrée privée : vous seul la lisez (la modération peut y accéder en cas de besoin).</p>
                @endif
                <div class="prose journal-entry__body">{{ $entry->html() }}</div>

                <footer class="cluster">
                    <a href="{{ route('journal.index', $character) }}">← Tout le journal</a>
                    @if ($isOwner)
                        <a href="{{ route('journal.edit', [$character, $entry]) }}">Modifier</a>
                        <form method="POST" action="{{ route('journal.destroy', [$character, $entry]) }}" data-confirm="Supprimer cette entrée ?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="link-button">Supprimer</button>
                        </form>
                    @else
                        <x-report-link type="journal" :id="$entry->id" :owner="$entry->user_id" />
                    @endif
                </footer>
            </div>
        </article>
    </div>
</x-layout>
