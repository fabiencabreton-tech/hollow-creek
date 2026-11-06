<x-layout title="Habitations" description="Maisons, appartements et chambres de Tsukimori, et les personnages qui y vivent.">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Accueil' => route('home'), 'La ville' => route('city.index'), 'Habitations' => null]" />
            <h1>Habitations</h1>
            <p class="lede">Où vivent les habitants de Tsukimori. Un personnage accepté peut emménager dans une habitation libre qui a encore de la place.</p>
        </header>

        <div class="page-body stack stack--loose">
            @if ($groups->isEmpty() && $others->isEmpty())
                <p class="empty-note">Le cadastre des habitations est en préparation.</p>
            @endif

            @foreach ($groups as $group)
                <section aria-labelledby="quartier-{{ $group['district']->slug }}">
                    <h2 id="quartier-{{ $group['district']->slug }}"><a href="{{ route('districts.show', $group['district']) }}">{{ $group['district']->name }}</a></h2>
                    @include('residences.partials.list', ['residences' => $group['residences']])
                </section>
            @endforeach

            @if ($others->isNotEmpty())
                <section aria-labelledby="quartier-autres">
                    <h2 id="quartier-autres">Ailleurs</h2>
                    @include('residences.partials.list', ['residences' => $others])
                </section>
            @endif
        </div>
    </div>
</x-layout>
