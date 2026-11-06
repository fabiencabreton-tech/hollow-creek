<?php

namespace App\Filament\Resources\Families\RelationManagers;

use App\Models\Family;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Liens narratifs avec les autres familles (affichés sur les deux fiches).
 */
class RelationsRelationManager extends RelationManager
{
    protected static string $relationship = 'outgoingRelations';

    protected static ?string $title = 'Liens avec les autres familles';

    protected static ?string $modelLabel = 'lien';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        $ownerId = $this->getOwnerRecord()->getKey();

        return $schema->components([
            Select::make('other_family_id')
                ->label('Autre famille')
                ->options(fn (): array => Family::query()->whereKeyNot($ownerId)->ordered()->pluck('name', 'id')->all())
                ->required(),
            Textarea::make('summary')->label('Nature du lien')->rows(3)->required()->maxLength(1000)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('otherFamily.name')->label('Famille'),
                TextColumn::make('summary')->label('Lien')->limit(90),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
