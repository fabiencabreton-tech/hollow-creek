<x-layout title="Les scènes" description="Les scènes publiques de Tsukimori, écrites à plusieurs mains.">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Accueil' => route('home'), 'Les scènes' => null]" />
            <h1>Les scènes</h1>
            <p class="lede">Les histoires publiques de Tsukimori, écrites à plusieurs mains. Les scènes privées ne sont visibles que de leurs participants.</p>
        </header>

        <div class="cluster page-actions">
            @auth
                <a class="btn" href="{{ route('scenes.create') }}">Ouvrir une scène</a>
                <a class="btn btn--ghost" href="{{ route('scenes.mine') }}">Mes scènes</a>
            @endauth
        </div>

        <form class="filters" method="GET" action="{{ route('scenes.index') }}">
            <div class="field">
                <label for="filtre-lieu">Lieu</label>
                <select id="filtre-lieu" name="lieu">
                    <option value="">Tous</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->slug }}" @selected($locationFilter?->is($location))>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="filtre-statut">Statut</label>
                <select id="filtre-statut" name="statut">
                    <option value="">Tous</option>
                    @foreach ([\App\Enums\SceneStatus::Ongoing, \App\Enums\SceneStatus::Paused, \App\Enums\SceneStatus::Finished] as $status)
                        <option value="{{ $status->value }}" @selected($statusFilter === $status)>{{ $status->getLabel() }}</option>
                    @endforeach
                </select>
            </div>
            @if ($events->isNotEmpty())
                <div class="field">
                    <label for="filtre-evenement">Événement</label>
                    <select id="filtre-evenement" name="evenement">
                        <option value="">Tous</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->slug }}" @selected($eventFilter?->is($event))>{{ $event->title }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="form-actions">
                <button type="submit" class="btn btn--ghost">Filtrer</button>
            </div>
        </form>

        @if ($scenes->isEmpty())
            <p class="empty-note">{{ $locationFilter || $statusFilter || $eventFilter ? 'Aucune scène ne correspond à ces critères.' : 'Aucune scène publique pour le moment : la première attend sa plume.' }}</p>
        @else
            <div class="scene-list page-body">
                @foreach ($scenes as $scene)
                    @include('scenes.partials.card', ['scene' => $scene])
                @endforeach
            </div>
            {{ $scenes->links('partials.pagination') }}
        @endif
    </div>
</x-layout>
