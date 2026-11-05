<?php

namespace App\Filament\Resources\Species;

use App\Enums\SpeciesKind;
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
use Filament\Forms\Components\Toggle;
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

    protected static ?string $modelLabel = 'catégorie';

    protected static ?string $pluralModelLabel = 'catégories';

    protected static ?string $navigationLabel = 'Bestiaire';

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
                    Select::make('kind')
                        ->label('Type')
                        ->helperText('Nature : une seule par personnage. Spécialisation et condition : s’ajoutent à une nature.')
                        ->options(SpeciesKind::class)
                        ->default(SpeciesKind::Nature->value)
                        ->required()
                        ->native(false),
                    Select::make('parent_id')
                        ->label('Catégorie particulière de… (facultatif)')
                        ->helperText('Par exemple : les Onryō sont des Yūrei particuliers.')
                        ->relationship('parent', 'name', fn ($query) => $query->where('kind', SpeciesKind::Nature->value))
                        ->preload(),
                    Select::make('allowed_natures')
                        ->label('Natures compatibles (spécialisations et conditions)')
                        ->helperText('Vide : compatible avec toutes les natures. Les médiums et les praticiens restent humains.')
                        ->multiple()
                        ->options(fn (): array => Species::query()->where('kind', SpeciesKind::Nature->value)->orderBy('sort_order')->pluck('name', 'slug')->all()),
                    Toggle::make('needs_death_date')->label('Personnage mort : date de décès demandée'),
                    Toggle::make('needs_registry')->label('Réservé aux victimes du registre de Kirisawa'),
                    Toggle::make('is_rare')->label('Catégorie rare, validation renforcée'),
                    Select::make('treatment')
                        ->label('Traitement visuel des portraits')
                        ->options(SpeciesTreatment::class)
                        ->required()
                        ->native(false),
                    Textarea::make('summary')
                        ->label('Résumé')
                        ->helperText('Une ou deux phrases, affichées dans le bestiaire.')
                        ->rows(3)
                        ->maxLength(600),
                    ContentFields::markdown('description', 'Description générale'),
                ]),
            Section::make('Fiche de bestiaire')
                ->description('Rubriques affichées seulement si elles sont remplies.')
                ->columnSpanFull()
                ->schema([
                    ContentFields::markdown('origin', 'Origine'),
                    ContentFields::markdown('physical', 'Caractéristiques physiques'),
                    ContentFields::markdown('longevity', 'Longévité'),
                    ContentFields::markdown('abilities', 'Capacités'),
                    ContentFields::markdown('weaknesses', 'Faiblesses'),
                    ContentFields::markdown('limits', 'Restrictions'),
                    ContentFields::markdown('rp_notes', 'Particularités pour les RP'),
                    ContentFields::markdown('creation_rules', 'Conditions de création'),
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
                TextColumn::make('name')->label('Catégorie')->searchable(),
                TextColumn::make('kind')->label('Type')->badge(),
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
