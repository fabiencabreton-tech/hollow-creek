<?php

namespace App\Filament\Resources\ClassifiedAds;

use App\Filament\Resources\ClassifiedAds\Pages\ListClassifiedAds;
use App\Enums\AdCategory;
use App\Models\ClassifiedAd;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ClassifiedAdResource extends Resource
{
    protected static ?string $model = ClassifiedAd::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Immersion';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'annonce';

    protected static ?string $pluralModelLabel = 'petites annonces';

    protected static ?string $navigationLabel = 'Petites annonces';

    protected static ?string $slug = 'annonces';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('character_name')->label('Signée par')->disabled(),
            TextInput::make('title')->label('Titre')->disabled(),
            Textarea::make('body')->label('Texte')->disabled()->rows(6)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([SoftDeletingScope::class]))
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable()->limit(50),
                TextColumn::make('category')->label('Rubrique')->badge(),
                TextColumn::make('character_name')->label('Signée par')->searchable(),
                TextColumn::make('author.pseudo')->label('Compte')->placeholder('—'),
                TextColumn::make('expires_at')->label('Expire le')->dateTime('d/m/Y')->sortable(),
                TextColumn::make('deleted_at')->label('Retirée le')->dateTime('d/m/Y')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('category')->label('Rubrique')->options(AdCategory::class),
                TrashedFilter::make()->label('Annonces retirées'),
            ])
            ->recordActions([
                DeleteAction::make()->label('Retirer'),
                RestoreAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClassifiedAds::route('/'),
        ];
    }
}
