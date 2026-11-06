<x-layout :title="$clue->title.' — '.$investigation->title" :robots="$clue->is_public ? null : 'noindex'">
    <div class="container container--narrow">
        <article>
            <header class="page-header">
                <x-breadcrumbs :items="['Enquêtes' => route('investigations.index'), $investigation->title => route('investigations.show', $investigation), $clue->title => null]" />
                <p class="eyebrow">Indice{{ $clue->is_public ? ' public' : '' }} · {{ $investigation->title }}</p>
                <h1>{{ $clue->title }}</h1>
                @if ($holders->isNotEmpty())
                    <p class="muted">Détenu par {{ $holders->map(fn ($character) => $character->fullName())->join(', ', ' et ') }}.</p>
                @endif
            </header>

            <div class="stack stack--loose">
                <x-flash />
                <x-errors-summary />

                @if ($clue->imageUrl())
                    <x-figure :url="$clue->imageUrl()" :credit="$clue->image_credit" :alt="''" />
                @endif
                @if ($clue->body)
                    <div class="prose">{{ \App\Support\Markdown::render($clue->body) }}</div>
                @endif

                @if ($canShare && $recipients->isNotEmpty())
                    <form class="form card" method="POST" action="{{ route('clues.share', [$investigation, $clue]) }}" novalidate>
                        @csrf
                        <h2 class="card__title">Confier cet indice</h2>
                        <p class="hint">Quand, en jeu, votre personnage révèle ce qu’il sait. Le destinataire pourra le lire et son joueur sera prévenu.</p>
                        <div class="form-grid">
                            <div class="field">
                                <label for="champ-indice-de">De la part de</label>
                                <select id="champ-indice-de" name="from_character_id">
                                    @foreach ($holders as $character)
                                        <option value="{{ $character->id }}">{{ $character->fullName() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label for="champ-indice-a">À</label>
                                <select id="champ-indice-a" name="to_character_id" @error('to_character_id') aria-invalid="true" @enderror>
                                    @foreach ($recipients as $character)
                                        <option value="{{ $character->id }}">{{ $character->fullName() }}</option>
                                    @endforeach
                                </select>
                                @error('to_character_id')<p class="error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="form-actions"><button type="submit" class="btn">Confier l’indice</button></div>
                    </form>
                @endif

                <p><a href="{{ route('investigations.show', $investigation) }}">← Retour à l’enquête</a></p>
            </div>
        </article>
    </div>
</x-layout>
