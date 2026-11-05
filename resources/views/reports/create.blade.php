<x-layout title="Signaler un contenu" robots="noindex">
    <div class="container">
        <header class="page-header">
            <h1>Signaler un contenu</h1>
            <p class="lede">Seule l’équipe de modération lira votre signalement, avec l’extrait ci-dessous et rien d’autre. La personne concernée ne saura pas qui l’a signalée.</p>
        </header>

        <div class="stack page-body stack--loose">
            <x-errors-summary />

            <blockquote class="card report-excerpt">
                <p class="eyebrow">{{ \App\Models\Report::TYPE_LABELS[$type] }}</p>
                <p>{!! nl2br(e($excerpt)) !!}</p>
            </blockquote>

            <form class="form card" method="POST" action="{{ route('reports.store') }}" novalidate>
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="id" value="{{ $target->getKey() }}">
                <input type="hidden" name="back" value="{{ $back }}">

                <fieldset class="field">
                    <legend>Motif</legend>
                    @foreach (config('hollow.reports.reasons') as $key => $label)
                        <label class="checkbox"><input type="radio" name="reason" value="{{ $key }}" @checked(old('reason') === $key) required> <span>{{ $label }}</span></label>
                    @endforeach
                    @error('reason')<p class="error">{{ $message }}</p>@enderror
                </fieldset>

                <div class="field">
                    <label for="champ-details">Précisions (facultatif)</label>
                    <textarea id="champ-details" name="details" rows="4" maxlength="2000">{{ old('details') }}</textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Envoyer le signalement</button>
                    <a href="{{ $back }}">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</x-layout>
