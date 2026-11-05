<?php

namespace App\Filament\Resources\Locations;

use App\Filament\Resources\Locations\Pages\CreateLocation;
use App\Filament\Resources\Locations\Pages\EditLocation;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Filament\Support\ContentFields;
use App\Models\Location;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Univers';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'lieu';

    protected static ?string $pluralModelLabel = 'lieux';

    protected static ?string $navigationLabel = 'Lieux';

    protected static ?string $slug = 'lieux';

    protected static ?string $recordTitleAttribute = 'name';

    public static function publicUrl(Location $record): string
    {
        return route('locations.show', $record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Présentation')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('kind')
                        ->label('Type de lieu')
                        ->helperText('Par exemple : café, librairie, bar, forêt, ruines.')
                        ->maxLength(60),
                    Select::make('district_id')
                        ->label('Quartier')
                        ->relationship('district', 'name')
                        ->placeholder('Sans quartier')
                        ->searchable()
                        ->preload(),
                    Textarea::make('summary')
                        ->label('Résumé')
                        ->helperText('Une ou deux phrases, affichées sur la carte du lieu.')
                        ->rows(3)
                        ->maxLength(600),
                    ContentFields::markdown('description', 'Description immersive'),
                ]),
            ContentFields::imageSection('univers')->columnSpanFull(),
            ContentFields::publication()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Lieu')->searchable(),
                TextColumn::make('kind')->label('Type')->placeholder('—'),
                TextColumn::make('district.name')->label('Quartier')->placeholder('—'),
                IconColumn::make('is_published')->label('Publié')->boolean(),
            ])
            ->filters([
                SelectFilter::make('district_id')
                    ->label('Quartier')
                    ->relationship('district', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocations::route('/'),
            'create' => CreateLocation::route('/nouveau'),
            'edit' => EditLocation::route('/{record}/modifier'),
        ];
    }
}
