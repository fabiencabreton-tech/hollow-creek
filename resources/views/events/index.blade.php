<x-layout title="Événements" description="Les événements qui secouent Tsukimori, organisés par l’équipe.">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Accueil' => route('home'), 'Événements' => null]" />
            <h1>Événements</h1>
            <p class="lede">Ce qui secoue Tsukimori : les personnages s’y inscrivent, les scènes et les articles de la Gazette s’y rattachent.</p>
        </header>

        <div class="stack page-body stack--loose">
            <section aria-labelledby="titre-a-venir">
                <h2 id="titre-a-venir" class="section__title">Annoncés et en cours</h2>
                @if ($current->isEmpty())
                    <p class="muted">Aucun événement pour le moment.</p>
                @else
                    <div class="grid-cards">
                        @foreach ($current as $event)
                            @include('events.partials.tile', ['event' => $event])
                        @endforeach
                    </div>
                @endif
            </section>

            @if ($past->isNotEmpty())
                <section aria-labelledby="titre-passes">
                    <h2 id="titre-passes" class="section__title">Terminés</h2>
                    <div class="grid-cards">
                        @foreach ($past as $event)
                            @include('events.partials.tile', ['event' => $event])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-layout>
