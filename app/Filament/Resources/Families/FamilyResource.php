<?php

namespace App\Filament\Resources\Families;

use App\Filament\Resources\Families\Pages\CreateFamily;
use App\Filament\Resources\Families\Pages\EditFamily;
use App\Filament\Resources\Families\Pages\ListFamilies;
use App\Filament\Resources\Families\RelationManagers\MembersRelationManager;
use App\Filament\Resources\Families\RelationManagers\RelationsRelationManager;
use App\Filament\Support\ContentFields;
use App\Models\Family;
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

class FamilyResource extends Resource
{
    protected static ?string $model = Family::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static string|UnitEnum|null $navigationGroup = 'Univers';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'famille';

    protected static ?string $pluralModelLabel = 'familles';

    protected static ?string $navigationLabel = 'Familles';

    protected static ?string $slug = 'familles';

    protected static ?string $recordTitleAttribute = 'name';

    public static function publicUrl(Family $record): string
    {
        return route('families.show', $record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identité')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nom')
                        ->helperText('Par exemple « Famille Minase ».')
                        ->required()
                        ->maxLength(80),
                    TextInput::make('japanese_name')->label('Nom japonais')->helperText('Par exemple « 水瀬家 ».')->maxLength(40),
                    TextInput::make('motto')->label('Devise')->helperText('Sans guillemets : « Ceux qui connaissent les eaux du lac ».')->maxLength(160)->columnSpanFull(),
                    Select::make('district_id')->label('Quartier')->relationship('district', 'name')->searchable()->preload(),
                    TextInput::make('district_note')->label('Précision sur le lieu')->helperText('Par exemple « près du lac Tsukikage ».')->maxLength(160),
                    Textarea::make('summary')->label('Résumé')->rows(3)->maxLength(600)->columnSpanFull(),
                    Textarea::make('activities')->label('Activités principales')->rows(2)->maxLength(1000),
                    TextInput::make('reputation')->label('Réputation')->maxLength(255),
                    Toggle::make('is_major')->label('Grande famille historique')->helperText('Mise en avant en tête de la rubrique.'),
                    Toggle::make('is_open')->label('Accueille de nouveaux membres')->default(true),
                ]),
            Section::make('Textes publics')
                ->columnSpanFull()
                ->schema([
                    ContentFields::markdown('history', 'Histoire', 'Seulement ce que les habitants savent ou racontent.'),
                    ContentFields::markdown('kirisawa_link', 'Lien public avec Kirisawa', 'Ce qui est connu en ville. Les vérités cachées vont dans les notes réservées à l’équipe.'),
                    ContentFields::markdown('rp_notes', 'Jouer un membre de la famille', 'Possibilités de personnages, thématiques, conseils.'),
                    ContentFields::markdown('open_roles', 'Rôles ouverts (ancien champ, facultatif)'),
                ]),
            Section::make('Réservé à l’équipe')
                ->description('Jamais affiché sur le site : secrets familiaux, pistes d’intrigue.')
                ->columnSpanFull()
                ->schema([
                    ContentFields::markdown('secret_notes', 'Notes secrètes'),
                ]),
            ContentFields::imageSection('univers')->columnSpanFull(),
            ContentFields::publication()->columnSpanFull(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            MembersRelationManager::class,
            RelationsRelationManager::class,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Famille')->searchable(),
                TextColumn::make('japanese_name')->label('Japonais')->placeholder('—'),
                IconColumn::make('is_major')->label('Grande famille')->boolean(),
                TextColumn::make('members_count')->label('Membres joués')->counts('members'),
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
            'index' => ListFamilies::route('/'),
            'create' => CreateFamily::route('/nouvelle'),
            'edit' => EditFamily::route('/{record}/modifier'),
        ];
    }
}
