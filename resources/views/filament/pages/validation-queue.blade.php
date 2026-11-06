<x-filament-panels::page>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Les plus anciennes demandes en premier. « Examiner » ouvre l’aperçu complet et la décision dans la même fenêtre ;
        chaque décision prévient le membre et rejoint l’<a class="underline" href="{{ \App\Filament\Pages\ValidationHistory::getUrl() }}">historique</a>.
    </p>
    {{ $this->table }}
</x-filament-panels::page>
