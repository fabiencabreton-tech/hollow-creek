<x-layout title="Enquêtes" description="Les mystères de Tsukimori que les habitants tentent d’éclaircir, et les indices déjà réunis.">
    <x-page-hero image="foret-brume" title="Enquêtes" eyebrow="Ce que Tsukimori se demande"
        lead="Certains indices sont connus de tous ; d’autres ne sont confiés qu’à quelques personnages, par l’équipe ou de la main à la main."
        :breadcrumbs="['Accueil' => route('home'), 'Enquêtes' => null]">
        @auth
            <div class="cluster page-actions">
                <a class="btn btn--ghost" href="{{ route('clues.mine') }}">Mes indices</a>
            </div>
        @endauth
    </x-page-hero>
    <div class="container">

        @if ($investigations->isEmpty())
            <p class="empty-note">Aucune enquête ouverte pour le moment.</p>
        @else
            <div class="grid-cards page-body">
                @foreach ($investigations as $investigation)
                    @php $mine = $held->get($investigation->id, collect())->count(); @endphp
                    <a class="tile" href="{{ route('investigations.show', $investigation) }}">
                        <span class="tile__eyebrow">{{ $investigation->status->getLabel() }}</span>
                        <span class="tile__title">{{ $investigation->title }}</span>
                        @if ($investigation->summary)
                            <span class="tile__text">{{ $investigation->summary }}</span>
                        @endif
                        <span class="tile__more">{{ $investigation->public_clues_count }} {{ $investigation->public_clues_count > 1 ? 'indices publics' : 'indice public' }}@if ($mine > 0) · {{ $mine }} {{ $mine > 1 ? 'indices détenus' : 'indice détenu' }} par vos personnages @endif</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
