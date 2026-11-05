<x-layout title="Les familles fondatrices" description="Les grandes familles de Hollow Creek.">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['L’univers' => route('universe.index'), 'Les familles fondatrices' => null]" />
            <h1>Les familles fondatrices</h1>
            <p class="lede">Chaque famille possède ses secrets, ses relations et ses conflits. Les joueurs peuvent aussi créer des personnages indépendants de ces lignées.</p>
        </header>

        @if ($families->isEmpty())
            <p class="empty-note">Les familles seront présentées ici très bientôt.</p>
        @else
            <div class="grid-cards page-body">
                @foreach ($families as $family)
                    <a class="tile" href="{{ route('families.show', $family) }}">
                        <span class="seal" aria-hidden="true">{{ $family->initial() }}</span>
                        <span class="tile__title">{{ $family->name }}</span>
                        @if ($family->summary)
                            <span class="tile__text">{{ $family->summary }}</span>
                        @endif
                        <span class="tile__more">Lire l’histoire</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
