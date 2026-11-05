<form class="form card" method="POST" action="{{ route('characters.update', [$character, 5]) }}" novalidate>
    @csrf
    @method('PUT')
    <h2 class="card__title">5. Personnalité</h2>
    <p class="muted">Caractère, qualités, failles, habitudes, peurs.</p>

    <div class="field">
        <label for="champ-personality">Personnalité</label>
        <textarea id="champ-personality" name="personality" rows="14" maxlength="20000" data-word-count="compteur-personnalite">{{ old('personality', $character->personality) }}</textarea>
        <p class="hint" id="compteur-personnalite" aria-live="polite"></p>
        @error('personality')<p class="error">{{ $message }}</p>@enderror
    </div>

    @include('characters.steps.actions')
</form>
