<x-layout title="Le registre de Kirisawa" description="Les quarante-trois victimes de l’incendie de l’hôpital psychiatrique de Kirisawa, le 31 octobre 1996.">
    <x-page-hero image="kirisawa" title="Le registre de Kirisawa" kanji="霧沢精神病院" eyebrow="31 octobre 1996 · 2 h 17"
        lead="Quarante-trois personnes décèdent dans l’incendie de l’hôpital psychiatrique de Kirisawa, « l’hôpital des Brumes », parmi lesquelles des patients et des membres du personnel. Seules elles peuvent revenir en Revenus."
        :breadcrumbs="['Le contexte' => route('universe.index'), 'Le registre de Kirisawa' => null]" />
    <div class="container">

        <div class="stack page-body stack--loose">
            <p class="muted">
                Certaines victimes sont des personnages de l’équipe ; d’autres peuvent être incarnées par les joueurs, avec validation de l’équipe.
                @if ($openCount > 0)
                    <strong>{{ $openCount }} {{ $openCount > 1 ? 'victimes peuvent' : 'victime peut' }} actuellement être incarnée{{ $openCount > 1 ? 's' : '' }}.</strong>
                @endif
                <a href="{{ route('species.show', 'revenus') }}">Lire la fiche des Revenus</a>.
            </p>

            @if ($victims->isEmpty())
                <p class="empty-note">Le registre sera publié par l’équipe très bientôt.</p>
            @else
                <ol class="registry plain-list">
                    @foreach ($victims as $victim)
                        <li id="victime-{{ $victim->number }}" @class(['registry__entry', 'registry__entry--open' => $victim->status === \App\Enums\VictimStatus::Open])>
                            <span class="registry__number" aria-hidden="true">{{ str_pad((string) $victim->number, 2, '0', STR_PAD_LEFT) }}</span>
                            <div class="stack stack--tight">
                                <p class="registry__name"><span class="visually-hidden">N° {{ $victim->number }} : </span>{{ $victim->fullName() }}</p>
                                <p class="registry__meta">{{ $victim->role->getLabel() }}@if ($victim->age_at_death) · {{ $victim->age_at_death }} ans @endif</p>
                                @if ($victim->summary)
                                    <p class="registry__summary">{{ $victim->summary }}</p>
                                @endif
                                <p>
                                    <x-status-badge :status="$victim->status" />
                                    @if ($victim->status === \App\Enums\VictimStatus::Played && $victim->character?->status === \App\Enums\CharacterStatus::Accepted)
                                        <a href="{{ route('characters.show', $victim->character) }}">Voir le personnage</a>
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ol>
                @if ($victims->count() < $total)
                    <p class="muted">{{ $total - $victims->count() }} {{ $total - $victims->count() > 1 ? 'noms ne sont pas encore publiés' : 'nom n’est pas encore publié' }}.</p>
                @endif
            @endif
        </div>
    </div>
</x-layout>
