<?php

namespace App\Filament\Pages;

use App\Jobs\RunBackup;
use App\Support\ActivityLogger;
use App\Support\BackupInventory;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Sauvegardes : liste, sauvegarde immédiate, téléchargement et suppression.
 */
class ManageBackups extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Site';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Sauvegardes';

    protected static ?string $title = 'Sauvegardes';

    protected static ?string $slug = 'sauvegardes';

    protected string $view = 'filament.pages.backups';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('sauvegardes.gerer') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runBackup')
                ->label('Sauvegarder maintenant')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->requiresConfirmation()
                ->modalDescription('L’archive (base de données et images) est préparée en arrière-plan ; elle apparaît ici quelques minutes plus tard et un e-mail confirme le résultat.')
                ->action(function (): void {
                    abort_unless(static::canAccess(), 403);

                    RunBackup::dispatch();
                    ActivityLogger::record('sauvegarde.demandee');

                    Notification::make()
                        ->title('Sauvegarde demandée')
                        ->body('Rechargez cette page dans quelques minutes pour voir la nouvelle archive.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function deleteBackup(string $disk, string $file): void
    {
        abort_unless(static::canAccess(), 403);

        if (app(BackupInventory::class)->delete($disk, $file)) {
            ActivityLogger::record('sauvegarde.supprimee', description: basename($file), properties: ['disque' => $disk]);
            Notification::make()->title('Archive supprimée')->success()->send();

            return;
        }

        Notification::make()->title('Archive introuvable')->danger()->send();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $inventory = app(BackupInventory::class);

        return [
            'backups' => $inventory->all(),
            'externalConfigured' => in_array('sauvegardes-externes', $inventory->disks(), true),
            'passwordConfigured' => filled(config('backup.backup.password')),
        ];
    }
}
