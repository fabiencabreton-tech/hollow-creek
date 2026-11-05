<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Journal de l'équipe : qui a fait quoi, et quand. En lecture seule.
 */
class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Site';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'entrée du journal';

    protected static ?string $pluralModelLabel = 'journal';

    protected static ?string $navigationLabel = 'Journal';

    protected static ?string $slug = 'journal';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('user.pseudo')
                    ->label('Par')
                    ->placeholder('Système')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('Action')
                    ->formatStateUsing(fn (string $state): string => ActivityLog::actionLabels()[$state] ?? $state)
                    ->badge(),
                TextColumn::make('description')
                    ->label('Détail')
                    ->placeholder('—')
                    ->limit(80)
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Action')
                    ->options(ActivityLog::actionLabels()),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }
}
