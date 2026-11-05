@php
    $traitIds = old('traits', $character->traits->pluck('id')->all());
    $traitIds = array_map('intval', (array) $traitIds);
    $natureId = (int) old('species_id', $character->species_id);
    $specialties = (array) old('occult_specialties', $character->occult_specialties ?? []);
@endphp
<form class="form card nature-form" method="POST" action="{{ route('characters.update', [$character, 2]) }}" novalidate data-nature-form data-supernatural-natures="{{ $natures->filter(fn ($nature) => $nature->isSupernatural())->pluck('slug')->implode(',') }}">
    @csrf
    @method('PUT')
    <h2 class="card__title">2. Nature</h2>
    <p class="muted">Humain ou surnaturel : vous choisissez librement, sans obligation de participer à l’intrigue principale. Le <a href="{{ route('species.index') }}" target="_blank" rel="noopener">bestiaire</a> détaille chaque catégorie, ses capacités et ses limites.</p>

    <fieldset class="field">
        <legend>Nature (une seule)</legend>
        <div class="choice-list">
            @foreach ($natures as $nature)
                <label @class(['choice', 'choice--sub' => $nature->parent_id])>
                    <input type="radio" name="species_id" value="{{ $nature->id }}" data-nature="{{ $nature->slug }}" @checked($natureId === $nature->id) required>
                    <span>
                        <strong>{{ $nature->name }}</strong>@if ($nature->is_rare) <span class="badge">Rare · validation renforcée</span>@endif
                        <span class="choice__text">{{ $nature->summary }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('species_id')<p class="error">{{ $message }}</p>@enderror
    </fieldset>

    <fieldset class="field">
        <legend>Spécialisations et conditions (facultatif)</legend>
        <div class="choice-list">
            @foreach ($traits as $trait)
                <label class="choice" data-allowed-natures="{{ implode(',', $trait->allowed_natures ?? []) }}">
                    <input type="checkbox" name="traits[]" value="{{ $trait->id }}" data-trait="{{ $trait->slug }}" @checked(in_array($trait->id, $traitIds, true))>
                    <span>
                        <strong>{{ $trait->name }}</strong> <span class="badge">{{ $trait->kind->getLabel() }}</span>
                        <span class="choice__text">{{ $trait->summary }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        <p class="hint">Médium et praticien de l’occulte sont réservés aux humains ; le cumul des deux est examiné avec attention par l’équipe. Une malédiction peut toucher n’importe quelle nature.</p>
        @error('traits')<p class="error">{{ $message }}</p>@enderror
    </fieldset>

    <fieldset class="field nature-group" data-show-for-natures="{{ $natures->where('needs_death_date', true)->pluck('slug')->implode(',') }}">
        <legend>Décès</legend>
        @if ($victims->isNotEmpty() || $character->kirisawa_victim_id)
            <div class="field" data-show-for-natures="{{ $natures->where('needs_registry', true)->pluck('slug')->implode(',') }}">
                <label for="champ-victime">Victime du registre de Kirisawa incarnée (Revenus)</label>
                <select id="champ-victime" name="kirisawa_victim_id">
                    <option value="">Choisir…</option>
                    @foreach ($victims as $victim)
                        <option value="{{ $victim->id }}" @selected((int) old('kirisawa_victim_id', $character->kirisawa_victim_id) === $victim->id)>N° {{ $victim->number }} · {{ $victim->fullName() }}@if ($victim->age_at_death), {{ $victim->age_at_death }} ans @endif</option>
                    @endforeach
                </select>
                <p class="hint">Seules les 43 victimes de l’incendie peuvent revenir. Le nom du personnage devient celui de la victime ; la date de décès est le 31 octobre 1996. <a href="{{ route('kirisawa.index') }}" target="_blank" rel="noopener">Consulter le registre</a></p>
                @error('kirisawa_victim_id')<p class="error">{{ $message }}</p>@enderror
            </div>
        @else
            <p class="muted" data-show-for-natures="{{ $natures->where('needs_registry', true)->pluck('slug')->implode(',') }}">Aucune victime du registre de Kirisawa n’est ouverte aux joueurs pour le moment.</p>
        @endif
        <x-field name="death_date" label="Date de décès" type="date" :value="old('death_date', $character->death_date?->format('Y-m-d'))" hint="Pour les Yūrei, les Onryō et les Rappelés." />
        <div class="field">
            <label for="champ-deces">Circonstances du décès (facultatif)</label>
            <textarea id="champ-deces" name="death_circumstances" rows="3" maxlength="3000">{{ old('death_circumstances', $character->death_circumstances) }}</textarea>
        </div>
    </fieldset>

    <fieldset class="field nature-group" data-show-for-natures="yurei,onryo">
        <legend>Esprit</legend>
        <div class="field">
            <label for="champ-ancrage">Attache (facultatif)</label>
            <textarea id="champ-ancrage" name="anchor" rows="2" maxlength="2000" aria-describedby="aide-ancrage">{{ old('anchor', $character->anchor) }}</textarea>
            <p id="aide-ancrage" class="hint">Un lieu ou une personne auquel l’esprit reste attaché, s’il y en a un.</p>
        </div>
        <div class="field" data-show-for-natures="onryo">
            <label for="champ-emotion">Émotion qui retient l’Onryō</label>
            <textarea id="champ-emotion" name="emotion" rows="2" maxlength="2000" aria-describedby="aide-emotion">{{ old('emotion', $character->emotion) }}</textarea>
            <p id="aide-emotion" class="hint">Rancœur, colère, tristesse, sentiment d’injustice, désir de vengeance…</p>
        </div>
    </fieldset>

    <fieldset class="field nature-group" data-show-for-traits="praticiens-de-l-occulte">
        <legend>Spécialisations occultes (deux au plus)</legend>
        <div class="checkbox-grid">
            @foreach (config('hollow.characters.occult_specialties') as $key => $label)
                <label class="checkbox"><input type="checkbox" name="occult_specialties[]" value="{{ $key }}" @checked(in_array($key, $specialties, true))> <span>{{ $label }}</span></label>
            @endforeach
        </div>
        @error('occult_specialties')<p class="error">{{ $message }}</p>@enderror
    </fieldset>

    <fieldset class="field nature-group" data-show-for-traits="maudits">
        <legend>Malédiction</legend>
        <div class="field">
            <label for="champ-origine-malediction">Origine</label>
            <textarea id="champ-origine-malediction" name="curse_origin" rows="2" maxlength="3000">{{ old('curse_origin', $character->curse_origin) }}</textarea>
        </div>
        <div class="field">
            <label for="champ-effets-malediction">Effets et conséquences</label>
            <textarea id="champ-effets-malediction" name="curse_effects" rows="3" maxlength="3000">{{ old('curse_effects', $character->curse_effects) }}</textarea>
        </div>
        <div class="field">
            <label for="champ-resolution-malediction">Possibilité de résolution (facultatif)</label>
            <textarea id="champ-resolution-malediction" name="curse_resolution" rows="2" maxlength="3000">{{ old('curse_resolution', $character->curse_resolution) }}</textarea>
        </div>
    </fieldset>

    <fieldset class="field nature-group" data-supernatural>
        <legend>Capacités et faiblesses</legend>
        <p class="hint">Pour un personnage surnaturel : ce qu’il peut faire, dans les limites du bestiaire, et ce qui le fragilise. L’équipe veille à ce qu’aucun personnage n’accumule trop de capacités.</p>
        <div class="field">
            <label for="champ-capacites">Capacités</label>
            <textarea id="champ-capacites" name="abilities" rows="4" maxlength="5000">{{ old('abilities', $character->abilities) }}</textarea>
        </div>
        <div class="field">
            <label for="champ-faiblesses">Faiblesses et limites</label>
            <textarea id="champ-faiblesses" name="weaknesses" rows="4" maxlength="5000">{{ old('weaknesses', $character->weaknesses) }}</textarea>
        </div>
    </fieldset>

    @include('characters.steps.actions')
</form>
