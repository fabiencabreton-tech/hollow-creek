<x-layout title="Mes indices" robots="noindex">
    <div class="container container--narrow">
        <header class="page-header">
            <x-breadcrumbs :items="['Enquêtes' => route('investigations.index'), 'Mes indices' => null]" />
            <h1>Mes indices</h1>
            <p class="lede">Ce que vos personnages ont découvert ou reçu. Personne d’autre ne voit cette page.</p>
        </header>

        <div class="stack stack--loose page-body">
            @forelse ($groups as $clues)
                @php $investigation = $clues->first()->investigation; @endphp
                <section aria-labelledby="enquete-{{ $investigation->id }}">
                    <h2 id="enquete-{{ $investigation->id }}"><a href="{{ route('investigations.show', $investigation) }}">{{ $investigation->title }}</a></h2>
                    @include('investigations.partials.clue-list', [
                        'clues' => $clues,
                        'note' => fn ($clue) => $clue->characters->map(fn ($character) => $character->fullName())->join(', ', ' et '),
                    ])
                </section>
            @empty
                <p class="empty-note">Vos personnages ne détiennent encore aucun indice.</p>
            @endforelse
        </div>
    </div>
</x-layout>
