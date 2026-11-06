<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Services\ValidationDesk;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Historique des décisions de validation : qui, quoi, quand, et avec quel motif.
 */
class ValidationHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Validation';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Décisions';

    protected static ?string $title = 'Historique des décisions';

    protected static ?string $slug = 'decisions';

    protected string $view = 'filament.pages.validation-history';

    public static function canAccess(): bool
    {
        return ValidationQueue::canAccess();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ActivityLog::query()->whereIn('action', array_keys(ValidationDesk::HISTORY_ACTIONS))->with('user'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('action')
                    ->label('Décision')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ValidationDesk::HISTORY_ACTIONS[$state] ?? $state)
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'refus') => 'danger',
                        str_contains($state, 'modifications') => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('description')->label('Concerne')->wrap()->searchable(),
                TextColumn::make('properties.motif')->label('Motif ou message')->placeholder('—')->wrap()->limit(160),
                TextColumn::make('user.pseudo')->label('Par')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('action')->label('Décision')->options(ValidationDesk::HISTORY_ACTIONS),
            ]);
    }
}
