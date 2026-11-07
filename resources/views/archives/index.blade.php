<x-layout title="Archives municipales" description="Délibérations, état civil, cadastre, coupures de presse et histoire de Tsukimori, conservés à la mairie.">
    <x-page-hero image="kirisawa" title="Archives municipales" eyebrow="Mairie de Tsukimori"
        lead="Les documents que la mairie conserve et laisse consulter : délibérations, actes, plans, coupures de presse. Tout le monde peut les lire ; ce qu’ils taisent reste à découvrir."
        :breadcrumbs="['Accueil' => route('home'), 'La carte' => route('city.index'), 'Archives municipales' => null]" />
    <div class="container">

        <form class="filters" method="GET" action="{{ route('archives.index') }}" role="search">
            <div class="field">
                <label for="filtre-archives-recherche">Rechercher</label>
                <input id="filtre-archives-recherche" type="search" name="q" value="{{ $search }}" maxlength="100">
            </div>
            <div class="field">
                <label for="filtre-archives-categorie">Série</label>
                <select id="filtre-archives-categorie" name="categorie">
                    <option value="">Toutes les séries</option>
                    @foreach ($categories as $item)
                        <option value="{{ $item->value }}" @selected($category === $item)>{{ $item->getLabel() }}</option>
                    @endforeach
                </select>
            </div>
            @if ($years->isNotEmpty())
                <div class="field">
                    <label for="filtre-archives-annee">Année</label>
                    <select id="filtre-archives-annee" name="annee">
                        <option value="">Toutes les années</option>
                        @foreach ($years as $item)
                            <option value="{{ $item }}" @selected($year === $item)>{{ $item }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="form-actions">
                <button type="submit" class="btn btn--ghost">Consulter</button>
            </div>
        </form>

        @if ($records->isEmpty())
            <p class="empty-note">{{ $category || $year || $search !== '' ? 'Aucun document ne correspond à cette recherche.' : 'Les archives sont en cours de classement.' }}</p>
        @else
            <ol class="archive-list plain-list page-body">
                @foreach ($records as $record)
                    <li class="archive-item">
                        <p class="archive-item__meta">
                            <span>{{ $record->category->getLabel() }}</span>
                            @if ($record->reference)<span>Cote {{ $record->reference }}</span>@endif
                            @if ($record->document_date)<time datetime="{{ $record->document_date->toDateString() }}">{{ $record->document_date->locale('fr')->isoFormat('LL') }}</time>@endif
                        </p>
                        <h2 class="archive-item__title"><a href="{{ route('archives.show', $record) }}">{{ $record->title }}</a></h2>
                        @if ($record->excerpt)
                            <p>{{ $record->excerpt }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>

            {{ $records->links('partials.pagination') }}
        @endif
    </div>
</x-layout>
