<x-layout :title="'Fiche : '.$character->fullName()" robots="noindex">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Mes personnages' => route('characters.mine'), $character->fullName() => null]" />
            <div class="cluster">
                <h1>{{ $character->fullName() }}</h1>
                <x-status-badge :status="$character->status" />
            </div>
            @if ($character->predefined)
                <p class="muted">À partir du rôle prédéfini « <a href="{{ route('predefined.show', $character->predefined) }}">{{ $character->predefined->title }}</a> ».</p>
            @endif
        </header>

        <nav aria-label="Étapes de la fiche">
            <ol class="steps">
                @foreach ($steps as $number => $label)
                    <li>
                        <a href="{{ route('characters.edit', [$character, $number]) }}" @if ($number === $step) aria-current="step" @endif>
                            <span class="steps__number">{{ $number }}</span> {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ol>
        </nav>

        <div class="stack page-body stack--loose wizard">
            <x-flash />
            <x-errors-summary />

            @if ($lastReview && $character->status === \App\Enums\CharacterStatus::ChangesRequested)
                <div class="alert alert--warning" role="status">
                    <div class="stack stack--tight">
                        <p><strong>Modifications demandées par l’équipe</strong> le {{ $lastReview->created_at?->locale('fr')->isoFormat('LL') }} :</p>
                        <p>« {{ $lastReview->comment }} »</p>
                    </div>
                </div>
            @endif

            @include('characters.steps.step-'.$step)
        </div>
    </div>
</x-layout>
