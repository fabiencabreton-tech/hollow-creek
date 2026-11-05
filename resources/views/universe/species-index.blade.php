<x-layout title="Les espèces" description="Les six espèces jouables de Hollow Creek.">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['L’univers' => route('universe.index'), 'Les espèces' => null]" />
            <h1>Les espèces</h1>
            <p class="lede">Chaque personnage appartient à l’une de ces espèces, avec ses capacités et ses limites. Tous restent intéressants indépendamment de leur nature.</p>
        </header>

        @if ($species->isEmpty())
            <p class="empty-note">Les espèces seront présentées ici très bientôt.</p>
        @else
            <div class="grid-cards page-body">
                @foreach ($species as $item)
                    <a class="tile tile--{{ $item->treatment->value }}" href="{{ route('species.show', $item) }}">
                        @if ($item->imageUrl())
                            <span class="tile__media"><img src="{{ $item->imageUrl() }}" alt="" loading="lazy"></span>
                        @endif
                        <span class="tile__eyebrow">Espèce</span>
                        <span class="tile__title">{{ $item->name }}</span>
                        @if ($item->summary)
                            <span class="tile__text">{{ $item->summary }}</span>
                        @endif
                        <span class="tile__more">Lire la fiche</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
