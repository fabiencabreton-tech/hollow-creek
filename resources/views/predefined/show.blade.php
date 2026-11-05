<x-layout :title="$predefined->title" :description="$predefined->summary" :robots="$isPreview ? 'noindex' : null">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Les prédéfinis' => route('predefined.index'), $predefined->title => null]" />
            <x-preview-banner :show="$isPreview" />
            <div class="cluster">
                <p class="eyebrow">Rôle prédéfini</p>
                <x-status-badge :status="$predefined->status" />
            </div>
            <h1>{{ $predefined->title }}</h1>
            @if ($predefined->summary)
                <p class="lede">{{ $predefined->summary }}</p>
            @endif
        </header>

        <div class="entry">
            <div>
                @foreach (['identity' => 'Identité', 'history' => 'Histoire', 'personality' => 'Personnalité', 'relations' => 'Relations', 'story_hooks' => 'Pistes narratives'] as $field => $heading)
                    @if (filled($predefined->{$field}))
                        <section class="entry__section">
                            <h2>{{ $heading }}</h2>
                            <div class="prose">{{ \App\Support\Markdown::render($predefined->{$field}) }}</div>
                        </section>
                    @endif
                @endforeach
            </div>

            <aside class="entry__aside" aria-label="Reprendre ce rôle">
                <div class="card stack">
                    <dl class="facts">
                        @if ($predefined->first_name || $predefined->last_name)<dt>Nom</dt><dd>{{ trim($predefined->first_name.' '.$predefined->last_name) }}</dd>@endif
                        @if ($predefined->species)<dt>Nature</dt><dd>{{ $predefined->species->name }}</dd>@endif
                        @if ($predefined->family)<dt>Famille</dt><dd>{{ $predefined->family->name }}</dd>@endif
                        @if ($predefined->age_range)<dt>Âge</dt><dd>{{ $predefined->age_range }}</dd>@endif
                        @if ($predefined->suggested_faceclaims)<dt>Avatars suggérés</dt><dd>{{ $predefined->suggested_faceclaims }}</dd>@endif
                    </dl>

                    @if (! $predefined->isOpen())
                        <p class="muted">{{ $predefined->status === \App\Enums\PredefinedStatus::Taken ? 'Ce rôle est déjà incarné.' : 'Une fiche est en cours d’examen pour ce rôle.' }}</p>
                    @elseif (! auth()->check())
                        <p class="muted"><a href="{{ route('login') }}">Connectez-vous</a> pour reprendre ce rôle.</p>
                    @elseif (! auth()->user()->hasVerifiedEmail())
                        <p class="muted">Vérifiez votre adresse e-mail pour reprendre ce rôle.</p>
                    @elseif ($remaining < 1)
                        <p class="muted">Vous avez atteint le nombre maximum de personnages.</p>
                    @else
                        <form method="POST" action="{{ route('predefined.claim', $predefined) }}">
                            @csrf
                            <button type="submit" class="btn">Je veux ce rôle</button>
                        </form>
                        <p class="hint muted">La fiche est pré-remplie ; le rôle est réservé pour vous dès son envoi à l’équipe.</p>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</x-layout>
