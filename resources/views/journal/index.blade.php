<x-layout :title="'Journal de '.$character->fullName()" :description="'Les pages que '.$character->fullName().' a bien voulu laisser lire.'">
    <div class="container container--narrow">
        <header class="page-header">
            <x-breadcrumbs :items="['Personnages' => route('characters.index'), $character->fullName() => route('characters.show', $character), 'Journal' => null]" />
            <p class="eyebrow">Journal personnel</p>
            <h1>{{ $character->fullName() }}</h1>
            @if ($isOwner)
                <div class="cluster page-actions">
                    <a class="btn" href="{{ route('journal.create', $character) }}">Écrire une entrée</a>
                </div>
            @endif
        </header>

        <div class="stack stack--loose page-body">
            <x-flash />

            @forelse ($entries as $entry)
                <article class="journal-entry card">
                    <header>
                        <p class="eyebrow">
                            @if ($entry->rp_date){{ $entry->rp_date }} · @endif
                            <time datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->locale('fr')->isoFormat('LL') }}</time>
                            @if ($entry->is_private) · <span class="badge">Privée</span>@endif
                        </p>
                        <h2 class="journal-entry__title"><a href="{{ route('journal.show', [$character, $entry]) }}">{{ $entry->heading() }}</a></h2>
                    </header>
                    <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $entry->html()), 400) }}</p>
                    <p><a href="{{ route('journal.show', [$character, $entry]) }}">Lire<span class="visually-hidden"> : {{ $entry->heading() }}</span></a></p>
                </article>
            @empty
                <p class="empty-note">{{ $isOwner ? 'Le journal est encore vierge.' : 'Aucune page de ce journal n’est lisible pour le moment.' }}</p>
            @endforelse

            {{ $entries->links('partials.pagination') }}
        </div>
    </div>
</x-layout>
