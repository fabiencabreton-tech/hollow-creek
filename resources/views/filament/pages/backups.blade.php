<x-filament-panels::page>
    <div class="grid gap-6">
        @unless ($passwordConfigured)
            <x-filament::callout color="danger" heading="Archives non chiffrées">
                <x-slot name="description">
                    Indiquez un mot de passe dans BACKUP_ARCHIVE_PASSWORD (fichier .env) pour chiffrer les archives, et conservez-le hors du serveur.
                </x-slot>
            </x-filament::callout>
        @endunless

        @unless ($externalConfigured)
            <x-filament::callout color="warning" heading="Pas encore de copie hors d’Alwaysdata">
                <x-slot name="description">
                    Les archives restent sur le serveur. Configurez un stockage externe (voir le guide d’installation) ou téléchargez la dernière archive chaque semaine.
                </x-slot>
            </x-filament::callout>
        @endunless

        <x-filament::section heading="Archives disponibles" description="Une sauvegarde automatique a lieu chaque dimanche à 3 h. Les plus anciennes sont supprimées automatiquement.">
            @if (count($backups) === 0)
                <x-filament::empty-state heading="Aucune sauvegarde pour le moment" description="Lancez une première sauvegarde avec le bouton « Sauvegarder maintenant »." />
            @else
                <div class="fi-ta-ctn" style="overflow-x: auto">
                    <table class="w-full text-sm" style="width: 100%; border-collapse: collapse">
                        <thead>
                            <tr style="text-align: left">
                                <th style="padding: 8px">Date</th>
                                <th style="padding: 8px">Emplacement</th>
                                <th style="padding: 8px">Taille</th>
                                <th style="padding: 8px"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($backups as $backup)
                                <tr wire:key="{{ $backup['disk'] }}-{{ $backup['file'] }}" style="border-top: 1px solid rgba(127, 127, 127, 0.2)">
                                    <td style="padding: 8px">{{ $backup['date'] }}</td>
                                    <td style="padding: 8px">{{ $backup['disk_label'] }}</td>
                                    <td style="padding: 8px">{{ $backup['size'] }}</td>
                                    <td style="padding: 8px; text-align: right; white-space: nowrap">
                                        <x-filament::button
                                            tag="a"
                                            size="sm"
                                            color="gray"
                                            icon="heroicon-o-arrow-down-tray"
                                            :href="route('admin.backups.download', ['disk' => $backup['disk'], 'file' => $backup['file']])"
                                        >
                                            Télécharger
                                        </x-filament::button>
                                        <x-filament::button
                                            size="sm"
                                            color="danger"
                                            icon="heroicon-o-trash"
                                            wire:click="deleteBackup('{{ $backup['disk'] }}', '{{ $backup['file'] }}')"
                                            wire:confirm="Supprimer définitivement cette archive ?"
                                        >
                                            Supprimer
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section heading="Restaurer" description="En cas de besoin">
            <p class="text-sm">
                Les étapes de restauration (décompresser l’archive avec son mot de passe, importer database.sql dans phpMyAdmin, recopier le dossier public) sont décrites dans le guide docs/sauvegardes-et-restauration.md. Alwaysdata garde aussi ses propres sauvegardes quotidiennes, restaurables depuis son interface.
            </p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
