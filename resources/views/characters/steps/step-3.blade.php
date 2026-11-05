<form class="form card" method="POST" action="{{ route('characters.update', [$character, 3]) }}" novalidate>
    @csrf
    @method('PUT')
    <h2 class="card__title">3. Histoire</h2>
    <p class="muted">Son passé, ses liens avec Hollow Creek, ce qui l’a mené(e) là. Mise en forme : **gras**, *italique*, une ligne vide entre les paragraphes.</p>

    <div class="field">
        <label for="champ-history">Histoire</label>
        <textarea id="champ-history" name="history" rows="18" maxlength="30000" data-word-count="compteur-histoire">{{ old('history', $character->history) }}</textarea>
        <p class="hint" id="compteur-histoire" aria-live="polite"></p>
        @error('history')<p class="error">{{ $message }}</p>@enderror
    </div>

    @include('characters.steps.actions')
</form>
