<?php

namespace App\Filament\Resources\PredefinedCharacters;

use App\Enums\PredefinedStatus;
use App\Filament\Resources\PredefinedCharacters\Pages\CreatePredefinedCharacter;
use App\Filament\Resources\PredefinedCharacters\Pages\EditPredefinedCharacter;
use App\Filament\Resources\PredefinedCharacters\Pages\ListPredefinedCharacters;
use App\Filament\Support\ContentFields;
use App\Models\PredefinedCharacter;
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

/**
 * Rôles prédéfinis proposés par l'équipe. Le statut (ouvert, en examen, pris)
 * suit automatiquement les fiches des joueurs.
 */
class PredefinedCharacterResource extends Resource
{
    protected static ?string $model = PredefinedCharacter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Personnages';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'prédéfini';

    protected static ?string $pluralModelLabel = 'prédéfinis';

    protected static ?string $navigationLabel = 'Prédéfinis';

    protected static ?string $slug = 'predefinis';

    protected static ?string $recordTitleAttribute = 'title';

    public static function publicUrl(PredefinedCharacter $record): string
    {
        return route('predefined.show', $record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Le rôle')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Titre du rôle')
                        ->helperText('Par exemple « La sœur cadette des Ashford ».')
                        ->required()
                        ->maxLength(120)
                        ->columnSpanFull(),
                    TextInput::make('first_name')->label('Prénom imposé')->helperText('Laissez vide si le joueur le choisit.')->maxLength(60),
                    TextInput::make('last_name')->label('Nom imposé')->maxLength(60),
                    Select::make('species_id')->label('Espèce')->relationship('species', 'name')->preload(),
                    Select::make('family_id')->label('Famille')->relationship('family', 'name')->preload(),
                    TextInput::make('age_range')->label('Âge')->placeholder('Entre 25 et 32 ans')->maxLength(60),
                    TextInput::make('suggested_faceclaims')->label('Avatars suggérés')->helperText('Des noms de personnalités, à titre indicatif.')->maxLength(255),
                    Textarea::make('summary')
                        ->label('Résumé')
                        ->helperText('Deux ou trois lignes, affichées dans la liste.')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(),
                ]),
            Section::make('Contenu')
                ->columnSpanFull()
                ->schema([
                    ContentFields::markdown('identity', 'Identité'),
                    ContentFields::markdown('history', 'Histoire', 'Ce que le joueur doit savoir. Les secrets de l’intrigue ne figurent jamais ici : cette page est publique.'),
                    ContentFields::markdown('personality', 'Personnalité'),
                    ContentFields::markdown('relations', 'Relations'),
                    ContentFields::markdown('story_hooks', 'Pistes narratives'),
                ]),
            ContentFields::publication()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')->label('Rôle')->searchable(),
                TextColumn::make('species.name')->label('Espèce')->placeholder('—'),
                TextColumn::make('family.name')->label('Famille')->placeholder('—'),
                TextColumn::make('status')->label('Statut')->badge(),
                IconColumn::make('is_published')->label('Publié')->boolean(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(PredefinedStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPredefinedCharacters::route('/'),
            'create' => CreatePredefinedCharacter::route('/nouveau'),
            'edit' => EditPredefinedCharacter::route('/{record}/modifier'),
        ];
    }
}
