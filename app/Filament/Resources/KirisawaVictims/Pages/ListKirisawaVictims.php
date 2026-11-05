<?php

namespace App\Filament\Resources\KirisawaVictims\Pages;

use App\Enums\VictimRole;
use App\Enums\VictimStatus;
use App\Filament\Resources\KirisawaVictims\KirisawaVictimResource;
use App\Models\KirisawaVictim;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListKirisawaVictims extends ListRecords
{
    protected static string $resource = KirisawaVictimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('prepare')
                ->label('Préparer les 43 emplacements')
                ->icon(Heroicon::OutlinedQueueList)
                ->color('gray')
                ->visible(fn (): bool => KirisawaVictim::query()->count() < KirisawaVictim::TOTAL)
                ->requiresConfirmation()
                ->modalDescription('Crée les numéros manquants, sans nom et non publiés : l’équipe les complète un à un.')
                ->action(function (): void {
                    abort_unless(auth()->user()?->can('create', KirisawaVictim::class), 403);
                    $existing = KirisawaVictim::query()->pluck('number')->all();
                    $created = 0;

                    foreach (range(1, KirisawaVictim::TOTAL) as $number) {
                        if (! in_array($number, $existing, true)) {
                            KirisawaVictim::query()->create([
                                'number' => $number,
                                'role' => VictimRole::Patient,
                                'status' => VictimStatus::Administrative,
                                'is_published' => false,
                            ]);
                            $created++;
                        }
                    }

                    Notification::make()->title($created.' emplacement(s) créé(s)')->success()->send();
                }),
            CreateAction::make(),
        ];
    }
}
