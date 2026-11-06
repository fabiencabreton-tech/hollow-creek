{{-- Aperçu complet d'une demande dans la file de validation (administration). --}}
@php
    use App\Models\Character;
    use App\Models\FaceclaimClaim;
    use App\Models\FamilyRequest;
    use App\Models\User;
    use App\Support\Markdown;

    $box = 'border:1px solid rgba(127,127,127,.25);border-radius:10px;padding:14px 16px;margin-bottom:14px';
    $label = 'font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.7;margin:0 0 4px';
    $grid = 'display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px 18px';
@endphp

<div style="font-size:14px;line-height:1.55">
    @if ($subject === null)
        <p>Cet élément n’existe plus.</p>
    @elseif ($subject instanceof Character)
        <div style="display:flex;gap:18px;align-items:flex-start;flex-wrap:wrap;{{ $box }}">
            @if ($subject->avatarUrl())
                <img src="{{ $subject->avatarUrl() }}" alt="" style="width:120px;height:150px;object-fit:cover;border-radius:8px">
            @endif
            <div style="flex:1;min-width:220px;{{ $grid }}">
                <div><p style="{{ $label }}">Joueur</p>{{ $subject->user?->pseudo ?? '—' }}</div>
                <div><p style="{{ $label }}">Âge · profession</p>{{ $subject->age ?? '—' }} ans · {{ $subject->profession ?: '—' }}</div>
                <div><p style="{{ $label }}">Nature</p>{{ $subject->species?->name ?? '—' }}</div>
                <div><p style="{{ $label }}">Spécialisations, conditions</p>{{ $subject->traits->pluck('name')->join(', ') ?: '—' }}</div>
                <div><p style="{{ $label }}">Famille</p>{{ $subject->family?->name ?? 'Aucune' }}</div>
                <div><p style="{{ $label }}">Avatar</p>{{ $subject->faceclaim?->name ?? '—' }}<br><span style="opacity:.7">Crédit : {{ $subject->avatar_credit ?: 'non indiqué' }}</span></div>
                @if ($subject->kirisawaVictim)
                    <div><p style="{{ $label }}">Registre de Kirisawa</p>N°{{ $subject->kirisawaVictim->number }} · {{ $subject->kirisawaVictim->fullName() }}</div>
                @endif
                @if ($subject->death_date)
                    <div><p style="{{ $label }}">Date de mort</p>{{ $subject->death_date->format('d/m/Y') }}</div>
                @endif
                @if ($subject->hasTrait('mediums') && $subject->hasTrait('praticiens-de-l-occulte'))
                    <div style="color:#b45309"><p style="{{ $label }}">À vérifier</p>Médium et praticien de l’occulte : cumul à justifier.</div>
                @endif
            </div>
        </div>

        @foreach (array_filter(['Histoire' => $subject->history, 'Personnalité' => $subject->personality]) as $heading => $text)
            <div style="{{ $box }}">
                <p style="{{ $label }}">{{ $heading }}</p>
                <div style="max-height:320px;overflow:auto">{{ Markdown::render($text) }}</div>
            </div>
        @endforeach

        @if ($subject->natureDetails() !== [] || $subject->curse_resolution)
            <div style="{{ $box }}">
                <p style="{{ $label }}">Nature et capacités</p>
                <dl style="margin:0;{{ $grid }}">
                    @foreach ($subject->natureDetails() as $heading => $text)
                        <div><dt style="font-weight:600">{{ $heading }}</dt><dd style="margin:0;white-space:pre-line">{{ $text }}</dd></div>
                    @endforeach
                    @if ($subject->curse_resolution)
                        <div><dt style="font-weight:600">Rupture possible (non publique)</dt><dd style="margin:0;white-space:pre-line">{{ $subject->curse_resolution }}</dd></div>
                    @endif
                </dl>
            </div>
        @endif

        @if ($subject->reviews->isNotEmpty())
            <div style="{{ $box }}">
                <p style="{{ $label }}">Décisions précédentes</p>
                <ul style="margin:0;padding-left:18px">
                    @foreach ($subject->reviews as $review)
                        <li>{{ $review->created_at?->format('d/m/Y') }} · {{ $review->decision->getLabel() }} · {{ $review->reviewer?->pseudo }}@if ($review->comment) — « {{ $review->comment }} »@endif</li>
                    @endforeach
                </ul>
            </div>
        @endif
    @elseif ($subject instanceof FaceclaimClaim)
        <div style="{{ $box }}{{ $grid }}">
            <div><p style="{{ $label }}">Célébrité demandée</p><strong>{{ $subject->faceclaim?->name }}</strong></div>
            <div><p style="{{ $label }}">Demandée par</p>{{ $subject->user?->pseudo ?? ($subject->guest_pseudo ? $subject->guest_pseudo.' (visiteur)' : '—') }}</div>
            <div><p style="{{ $label }}">Le</p>{{ $subject->created_at?->format('d/m/Y H:i') }}</div>
            @if ($subject->note)
                <div style="grid-column:1/-1"><p style="{{ $label }}">Note</p>{{ $subject->note }}</div>
            @endif
        </div>
    @elseif ($subject instanceof FamilyRequest)
        <div style="{{ $box }}{{ $grid }}">
            <div><p style="{{ $label }}">Demande</p><strong>{{ $subject->label() }}</strong></div>
            <div><p style="{{ $label }}">Personnage</p>{{ $subject->character?->fullName() }} ({{ $subject->user?->pseudo }})</div>
            <div><p style="{{ $label }}">Famille actuelle</p>{{ $subject->character?->family?->name ?? 'Aucune' }}</div>
        </div>
        @if ($subject->isProposal())
            <div style="{{ $box }}">
                <p style="{{ $label }}">Famille proposée</p>
                <p style="margin:0 0 6px"><strong>{{ $subject->proposed_name }}</strong> {{ $subject->proposed_japanese_name }}</p>
                <p style="margin:0;white-space:pre-line">{{ $subject->proposed_summary }}</p>
            </div>
        @endif
        @if ($subject->message)
            <div style="{{ $box }}"><p style="{{ $label }}">Message du joueur</p><p style="margin:0;white-space:pre-line">{{ $subject->message }}</p></div>
        @endif
    @elseif ($subject instanceof User)
        <div style="{{ $box }}{{ $grid }}">
            <div><p style="{{ $label }}">Pseudo</p><strong>{{ $subject->pseudo }}</strong></div>
            <div><p style="{{ $label }}">Adresse e-mail</p>{{ $subject->email }}</div>
            <div><p style="{{ $label }}">Inscrit le</p>{{ $subject->created_at?->format('d/m/Y H:i') }}</div>
            <div><p style="{{ $label }}">Majorité et conditions</p>{{ $subject->age_confirmed_at ? 'Majorité confirmée' : 'Majorité non confirmée' }} · {{ $subject->terms_accepted_at ? 'conditions acceptées' : 'conditions non acceptées' }}</div>
        </div>
        <p style="opacity:.75">L’adresse n’a pas encore été confirmée par le lien envoyé. Valider l’inscription à la main seulement si vous savez que l’adresse appartient bien à cette personne ; refuser supprime le compte.</p>
    @endif
</div>
