{{-- Carte interactive de Tsukimori. Sans JavaScript, chaque repère reste un lien vers la page du lieu. --}}
@php
    $mapped = $places->filter(fn ($place) => $place->map_x !== null && $place->map_y !== null);
    $kindClass = fn (?string $kind): string => 'map__marker--'.(\Illuminate\Support\Str::slug((string) $kind) ?: 'lieu');
@endphp
<section class="city-map" aria-labelledby="titre-carte" data-city-map>
    <div class="city-map__header">
        <h2 id="titre-carte">La carte de Tsukimori</h2>
        <p class="muted">Choisissez un lieu pour lire sa description, voir les scènes publiques en cours et ouvrir une scène sur place.</p>
    </div>

    <form class="map-filters js-only" role="search" aria-label="Filtrer les lieux" data-map-filters>
        <div class="field">
            <label for="carte-recherche">Rechercher un lieu</label>
            <input id="carte-recherche" type="search" autocomplete="off" data-map-search>
        </div>
        <div class="field">
            <label for="carte-quartier">Quartier</label>
            <select id="carte-quartier" data-map-district>
                <option value="">Tous les quartiers</option>
                @foreach ($districts as $district)
                    <option value="{{ $district->name }}">{{ $district->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="carte-type">Type de lieu</label>
            <select id="carte-type" data-map-kind>
                <option value="">Tous les types</option>
                @foreach ($kinds as $kind)
                    <option value="{{ $kind }}">{{ $kind }}</option>
                @endforeach
            </select>
        </div>
        <p class="map-filters__count" role="status" data-map-count></p>
    </form>

    <div class="city-map__layout">
        <div class="city-map__canvas">
            <svg class="map" viewBox="0 0 1000 700" role="group" aria-label="Plan de Tsukimori : {{ $mapped->count() }} lieux">
                <rect class="map__ground" x="0" y="0" width="1000" height="700" rx="12" />
                {{-- Montagnes et forêts de Kitayama --}}
                <path class="map__mountains" d="M0 150 L60 70 L110 120 L170 40 L240 115 L300 55 L360 120 L430 30 L500 105 L560 50 L630 110 L700 35 L770 100 L840 45 L910 110 L1000 60 L1000 0 L0 0 Z" />
                <path class="map__forest" d="M120 170 Q200 90 320 150 Q380 190 330 230 Q220 260 150 230 Q90 210 120 170 Z" />
                <path class="map__forest" d="M560 70 Q660 30 740 80 Q760 120 700 140 Q610 150 570 120 Z" />
                {{-- Lac Tsukikage et rivière Kirisawa --}}
                <ellipse class="map__water" cx="150" cy="420" rx="125" ry="105" />
                <path class="map__river" d="M640 30 C580 110 470 170 440 210 C415 250 385 300 370 330 C350 370 300 395 268 408" />
                {{-- Routes et voie ferrée --}}
                <path class="map__road" d="M275 380 C400 360 520 330 640 320 C760 310 860 300 990 280" />
                <path class="map__road" d="M520 330 C530 420 560 520 610 690" />
                <path class="map__road" d="M640 320 C700 260 760 210 860 180" />
                <path class="map__road map__road--small" d="M520 330 C500 250 520 170 600 90" />
                <path class="map__rail" d="M300 490 L1000 430" />
                {{-- Noms des quartiers --}}
                @foreach ($districts as $district)
                    @if ($district->map_x !== null && $district->map_y !== null)
                        <a href="{{ route('districts.show', $district) }}" class="map__district" data-map-district-label="{{ $district->name }}">
                            <text x="{{ $district->map_x * 10 }}" y="{{ $district->map_y * 7 }}" text-anchor="middle">{{ $district->name }}</text>
                        </a>
                    @endif
                @endforeach
                {{-- Repères des lieux --}}
                @foreach ($mapped as $place)
                    <a href="{{ route('locations.show', $place) }}" class="map__marker {{ $kindClass($place->kind) }}" data-place="{{ $place->slug }}" aria-label="{{ $place->name }}@if ($place->kind) ({{ $place->kind }})@endif">
                        <title>{{ $place->name }}</title>
                        <circle class="map__halo" cx="{{ $place->map_x * 10 }}" cy="{{ $place->map_y * 7 }}" r="18" />
                        <circle class="map__dot" cx="{{ $place->map_x * 10 }}" cy="{{ $place->map_y * 7 }}" r="8" />
                    </a>
                @endforeach
            </svg>
        </div>

        <aside class="map-panel card" aria-live="polite" data-map-panel>
            <div class="map-panel__empty" data-map-empty>
                <p class="eyebrow">Tsukimori · <span lang="ja">月森町</span></p>
                <p>Sélectionnez un repère sur la carte ou un lieu dans la liste.</p>
            </div>
            <div class="map-panel__content" hidden data-map-content>
                <figure class="map-panel__media" hidden data-map-media>
                    <img src="" alt="" data-map-image>
                    <figcaption data-map-credit></figcaption>
                </figure>
                <p class="eyebrow" data-map-meta></p>
                <h3 class="map-panel__title" data-map-title></h3>
                <p data-map-summary></p>
                <h4 class="map-panel__subtitle">Scènes publiques en cours</h4>
                <ul class="plain-list map-panel__scenes" data-map-scenes></ul>
                <p class="muted" data-map-no-scenes>Aucune scène publique en cours ici.</p>
                <div class="map-panel__actions">
                    <a class="btn" href="#" data-map-start>Commencer un RP ici</a>
                    <a class="btn btn--ghost" href="#" data-map-link>Voir le lieu</a>
                </div>
            </div>
        </aside>
    </div>

    <ul class="map-list plain-list" aria-label="Tous les lieux">
        @foreach ($places as $place)
            <li data-map-item="{{ $place->slug }}">
                <a href="{{ route('locations.show', $place) }}" data-place="{{ $place->slug }}">{{ $place->name }}</a>
                <span class="map-list__meta">{{ collect([$place->kind, $districts->firstWhere('id', $place->district_id)?->name])->filter()->join(' · ') }}</span>
            </li>
        @endforeach
    </ul>

    <script type="application/json" data-map-data>@json($mapData)</script>
</section>
