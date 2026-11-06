<?php

namespace App\Filament\Resources\FamilyPrelinks;

use App\Enums\PrelinkStatus;
use App\Filament\Resources\FamilyPrelinks\Pages\ListFamilyPrelinks;
use App\Models\FamilyPrelink;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * Préliens familiaux publiés par les joueurs : consultation et retrait.
 */
class FamilyPrelinkResource extends Resource
{
    protected static ?string $model = FamilyPrelink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = 'Univers';

    protected static ?int $navigationSort = 22;

    protected static ?string $modelLabel = 'prélien';

    protected static ?string $pluralModelLabel = 'préliens familiaux';

    protected static ?string $navigationLabel = 'Préliens familiaux';

    protected static ?string $slug = 'preliens';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([SoftDeletingScope::class])->with(['family', 'character', 'author']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Prélien')->searchable()->limit(50),
                TextColumn::make('relation')->label('Lien'),
                TextColumn::make('family.name')->label('Famille'),
                TextColumn::make('character.first_name')->label('Proposé par')->formatStateUsing(fn (FamilyPrelink $record): string => $record->character?->fullName() ?? '—'),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('created_at')->label('Publié le')->dateTime('d/m/Y')->sortable(),
                TextColumn::make('deleted_at')->label('Retiré le')->dateTime('d/m/Y')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('family_id')->label('Famille')->relationship('family', 'name'),
                SelectFilter::make('status')->label('Statut')->options(PrelinkStatus::class),
                TrashedFilter::make()->label('Préliens retirés'),
            ])
            ->recordActions([
                DeleteAction::make()->label('Retirer'),
                RestoreAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFamilyPrelinks::route('/'),
        ];
    }
}
