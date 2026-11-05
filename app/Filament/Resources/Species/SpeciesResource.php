<?php

namespace App\Filament\Resources\Species;

use App\Enums\SpeciesTreatment;
use App\Filament\Resources\Species\Pages\CreateSpecies;
use App\Filament\Resources\Species\Pages\EditSpecies;
use App\Filament\Resources\Species\Pages\ListSpecies;
use App\Filament\Support\ContentFields;
use App\Models\Species;
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
use Filament\Tables\Table;
use UnitEnum;

class SpeciesResource extends Resource
{
    protected static ?string $model = Species::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Univers';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'espèce';

    protected static ?string $pluralModelLabel = 'espèces';

    protected static ?string $navigationLabel = 'Espèces';

    protected static ?string $slug = 'especes';

    protected static ?string $recordTitleAttribute = 'name';

    public static function publicUrl(Species $record): string
    {
        return route('species.show', $record);
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
                        ->maxLength(80),
                    Select::make('treatment')
                        ->label('Traitement visuel des portraits')
                        ->options(SpeciesTreatment::class)
                        ->required()
                        ->native(false),
                    Textarea::make('summary')
                        ->label('Résumé')
                        ->helperText('Une ou deux phrases, affichées dans la liste des espèces.')
                        ->rows(3)
                        ->maxLength(600),
                    ContentFields::markdown('description', 'Description'),
                ]),
            Section::make('Règles de jeu')
                ->description('Rubriques affichées seulement si elles sont remplies.')
                ->columnSpanFull()
                ->schema([
                    ContentFields::markdown('abilities', 'Capacités'),
                    ContentFields::markdown('limits', 'Limites'),
                    ContentFields::markdown('story_hooks', 'Pistes de jeu'),
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
                TextColumn::make('name')->label('Espèce')->searchable(),
                TextColumn::make('treatment')
                    ->label('Portraits'),
                IconColumn::make('is_published')->label('Publiée')->boolean(),
                TextColumn::make('updated_at')->label('Modifiée')->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpecies::route('/'),
            'create' => CreateSpecies::route('/nouvelle'),
            'edit' => EditSpecies::route('/{record}/modifier'),
        ];
    }
}
