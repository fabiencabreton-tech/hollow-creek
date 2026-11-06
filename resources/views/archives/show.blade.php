<x-layout :title="$record->title" :description="$record->excerpt" :robots="$isPreview ? 'noindex' : null">
    <div class="container">
        <article>
            <header class="page-header">
                <x-breadcrumbs :items="['La ville' => route('city.index'), 'Archives municipales' => route('archives.index'), $record->title => null]" />
                <x-preview-banner :show="$isPreview" />
                <p class="eyebrow"><a href="{{ route('archives.index', ['categorie' => $record->category->value]) }}">{{ $record->category->getLabel() }}</a></p>
                <h1>{{ $record->title }}</h1>
                <p class="archive-item__meta">
                    @if ($record->reference)<span>Cote {{ $record->reference }}</span>@endif
                    @if ($record->document_date)<time datetime="{{ $record->document_date->toDateString() }}">{{ $record->document_date->locale('fr')->isoFormat('LL') }}</time>@endif
                </p>
                @if ($record->excerpt)
                    <p class="lede">{{ $record->excerpt }}</p>
                @endif
            </header>

            <div class="archive-document stack stack--loose">
                @if ($record->imageUrl())
                    <x-figure :url="$record->imageUrl()" :credit="$record->image_credit" :alt="''" />
                @endif

                @if ($record->body)
                    <div class="prose">{{ \App\Support\Markdown::render($record->body) }}</div>
                @endif

                @if ($district || $location)
                    <p class="muted">
                        @if ($location)Lieu : <a href="{{ route('locations.show', $location) }}">{{ $location->name }}</a>@endif
                        @if ($district && $location) · @endif
                        @if ($district)Quartier : <a href="{{ route('districts.show', $district) }}">{{ $district->name }}</a>@endif
                    </p>
                @endif

                <p><a href="{{ route('archives.index') }}">← Toutes les archives</a></p>
            </div>
        </article>
    </div>
</x-layout>
