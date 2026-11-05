<section class="card stack" aria-labelledby="titre-envoi">
    <h2 id="titre-envoi" class="card__title">5. Envoi à l’équipe</h2>

    <dl class="facts">
        <dt>Nom</dt><dd>{{ $character->fullName() }}</dd>
        <dt>Âge</dt><dd>{{ $character->age ? $character->age.' ans' : '—' }}@if ($character->apparent_age) (en paraît {{ $character->apparent_age }})@endif</dd>
        <dt>Espèce</dt><dd>{{ $character->species?->name ?? '—' }}</dd>
        <dt>Famille</dt><dd>{{ $character->family?->name ?? 'Indépendant' }}</dd>
        <dt>Profession</dt><dd>{{ $character->profession ?: '—' }}</dd>
        <dt>Avatar</dt><dd>{{ $character->faceclaim?->name ?? '—' }}</dd>
    </dl>

    @if ($missing !== [])
        <div class="alert alert--warning" role="status">
            <div>
                <p>Avant l’envoi, il manque :</p>
                <ul>
                    @foreach ($missing as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @else
        <form class="form" method="POST" action="{{ route('characters.submit', $character) }}">
            @csrf
            <div class="field">
                <label class="checkbox">
                    <input type="checkbox" name="attestation" value="1" required>
                    <span>J’atteste que cette fiche est entièrement écrite par moi, sans recours à une intelligence artificielle, et que le personnage est majeur.</span>
                </label>
                @error('attestation')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn--large">Envoyer ma fiche</button>
                <a href="{{ route('characters.show', $character) }}" target="_blank" rel="noopener">Prévisualiser le profil</a>
            </div>
        </form>
    @endif
</section>
