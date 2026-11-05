<?php

namespace App\Filament\Resources\Districts;

use App\Filament\Resources\Districts\Pages\CreateDistrict;
use App\Filament\Resources\Districts\Pages\EditDistrict;
use App\Filament\Resources\Districts\Pages\ListDistricts;
use App\Filament\Support\ContentFields;
use App\Models\District;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Univers';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'quartier';

    protected static ?string $pluralModelLabel = 'quartiers';

    protected static ?string $navigationLabel = 'Quartiers';

    protected static ?string $slug = 'quartiers';

    protected static ?string $recordTitleAttribute = 'name';

    public static function publicUrl(District $record): string
    {
        return route('districts.show', $record);
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
                        ->maxLength(100),
                    Textarea::make('summary')
                        ->label('Résumé')
                        ->rows(3)
                        ->maxLength(600),
                    ContentFields::markdown('description', 'Description'),
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
                TextColumn::make('name')->label('Quartier')->searchable(),
                TextColumn::make('locations_count')->label('Lieux')->counts('locations'),
                IconColumn::make('is_published')->label('Publié')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDistricts::route('/'),
            'create' => CreateDistrict::route('/nouveau'),
            'edit' => EditDistrict::route('/{record}/modifier'),
        ];
    }
}
