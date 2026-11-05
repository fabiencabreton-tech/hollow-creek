<x-layout title="Ouvrir une scène" robots="noindex">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Les scènes' => route('scenes.index'), 'Ouvrir une scène' => null]" />
            <h1>Ouvrir une scène</h1>
            <p class="lede">Posez le décor, choisissez vos partenaires, écrivez l’introduction. Chaque partenaire accepte l’invitation avec le personnage de son choix.</p>
        </header>

        <div class="stack page-body stack--loose">
            <x-errors-summary />

            <form class="form card scene-form" method="POST" action="{{ route('scenes.store') }}" novalidate>
                @csrf

                <div class="form-grid">
                    <div class="field">
                        <label for="champ-personnage">Votre personnage</label>
                        <select id="champ-personnage" name="character_id" required @error('character_id') aria-invalid="true" @enderror>
                            @foreach ($characters as $character)
                                <option value="{{ $character->id }}" @selected((int) old('character_id') === $character->id)>{{ $character->fullName() }}</option>
                            @endforeach
                        </select>
                        @error('character_id')<p class="error">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="champ-lieu">Lieu (facultatif)</label>
                        <select id="champ-lieu" name="location_id">
                            <option value="">Ailleurs à Alderwick</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected((int) old('location_id', $selectedLocation) === $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                        @error('location_id')<p class="error">{{ $message }}</p>@enderror
                    </div>
                </div>

                @if ($events->isNotEmpty())
                    <div class="field">
                        <label for="champ-evenement">Événement (facultatif)</label>
                        <select id="champ-evenement" name="event_id">
                            <option value="">Aucun</option>
                            @foreach ($events as $event)
                                <option value="{{ $event->id }}" @selected((int) old('event_id', $selectedEvent) === $event->id)>{{ $event->title }}</option>
                            @endforeach
                        </select>
                        @error('event_id')<p class="error">{{ $message }}</p>@enderror
                    </div>
                @endif

                <x-field name="title" label="Titre de la scène" required maxlength="150" :value="old('title')" />
                <x-field name="rp_date" label="Date RP" required maxlength="80" :value="old('rp_date', $rpDate)" hint="Par exemple « mardi 3 novembre 2026, 22 h »." />

                <div class="form-grid">
                    <fieldset class="field">
                        <legend>Visibilité</legend>
                        <label class="checkbox"><input type="radio" name="visibility" value="publique" @checked(old('visibility', 'publique') === 'publique')> <span>Publique : tout le monde peut la lire</span></label>
                        <label class="checkbox"><input type="radio" name="visibility" value="privee" @checked(old('visibility') === 'privee')> <span>Privée : seulement ses participants</span></label>
                        <label class="checkbox"><input type="checkbox" name="is_open" value="1" @checked(old('is_open'))> <span>Ouverte : qui veut peut la rejoindre (scènes publiques)</span></label>
                        @error('visibility')<p class="error">{{ $message }}</p>@enderror
                    </fieldset>

                    <fieldset class="field">
                        <legend>Ordre de passage</legend>
                        <label class="checkbox"><input type="radio" name="turn_mode" value="libre" @checked(old('turn_mode', 'libre') === 'libre')> <span>Libre : chacun répond quand il le souhaite</span></label>
                        <label class="checkbox"><input type="radio" name="turn_mode" value="tour" @checked(old('turn_mode') === 'tour')> <span>Tour par tour, dans l’ordre d’arrivée</span></label>
                        @error('turn_mode')<p class="error">{{ $message }}</p>@enderror
                    </fieldset>
                </div>

                <fieldset class="field">
                    <legend>Avertissements de contenu (facultatif)</legend>
                    <div class="checkbox-grid">
                        @foreach ($warnings as $key => $label)
                            <label class="checkbox"><input type="checkbox" name="content_warnings[]" value="{{ $key }}" @checked(in_array($key, old('content_warnings', []), true))> <span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="field">
                    <label for="champ-partenaires">Partenaires invités (facultatif, 10 au plus)</label>
                    <select id="champ-partenaires" name="partners[]" multiple size="8">
                        @foreach ($partners as $partner)
                            <option value="{{ $partner->id }}" @selected(in_array($partner->id, array_map('intval', old('partners', [])), true))>{{ $partner->fullName() }} — {{ $partner->user?->pseudo }}</option>
                        @endforeach
                    </select>
                    <p class="hint">Ctrl ou ⌘ + clic pour en choisir plusieurs. Un seul personnage par joueur ; le joueur pourra rejoindre la scène avec un autre de ses personnages.</p>
                    @error('partners')<p class="error">{{ $message }}</p>@enderror
                </div>

                <x-scene-editor name="introduction" id="champ-introduction" label="Introduction" :value="old('introduction', '')" local-key="hollow-creek-nouvelle-scene" :rows="16" />

                <div class="form-actions">
                    <button type="submit" class="btn btn--large">Ouvrir la scène</button>
                    <button type="button" class="btn btn--ghost js-only" data-editor-preview>Aperçu</button>
                </div>
            </form>
        </div>
    </div>
</x-layout>
