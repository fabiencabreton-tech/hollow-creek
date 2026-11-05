<x-layout title="Recensement" robots="noindex">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Tableau de bord' => route('dashboard'), 'Recensement' => null]" />
            <h1>Recensement</h1>
            @if ($census)
                <p class="lede">{{ $census->title }} — à confirmer avant le {{ $census->ends_at->locale('fr')->isoFormat('LL') }}.</p>
            @endif
        </header>

        <div class="stack page-body stack--loose">
            <x-flash />
            <x-errors-summary />

            @if (! $census)
                <p class="empty-note">Aucun recensement en cours.</p>
            @else
                @if ($census->message)
                    <div class="card"><p>{{ $census->message }}</p></div>
                @endif

                @if ($characters->isEmpty())
                    <p class="muted">Vous n’avez pas de personnage accepté à recenser.</p>
                @else
                    <form class="form card" method="POST" action="{{ route('census.store') }}">
                        @csrf
                        <fieldset class="field">
                            <legend>Les personnages que vous jouez toujours</legend>
                            @foreach ($characters as $character)
                                <label class="checkbox">
                                    <input type="checkbox" name="characters[]" value="{{ $character->id }}" @checked(in_array($character->id, $confirmed, true)) @disabled(in_array($character->id, $confirmed, true))>
                                    <span>{{ $character->fullName() }}@if (in_array($character->id, $confirmed, true)) · déjà recensé @elseif ($character->isAbsent()) · absence annoncée @endif</span>
                                </label>
                            @endforeach
                        </fieldset>
                        <p class="hint">Un personnage non recensé à la fin peut être archivé par l’équipe ; son avatar redevient alors disponible. Une absence annoncée sur le profil le protège.</p>
                        <div class="form-actions"><button type="submit" class="btn">Confirmer</button></div>
                    </form>
                @endif
            @endif
        </div>
    </div>
</x-layout>
