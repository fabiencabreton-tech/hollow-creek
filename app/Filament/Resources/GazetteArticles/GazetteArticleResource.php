<?php

namespace App\Filament\Resources\GazetteArticles;

use App\Enums\GazetteRubric;
use App\Filament\Resources\GazetteArticles\Pages\CreateGazetteArticle;
use App\Filament\Resources\GazetteArticles\Pages\EditGazetteArticle;
use App\Filament\Resources\GazetteArticles\Pages\ListGazetteArticles;
use App\Filament\Support\ContentFields;
use App\Models\GazetteArticle;
use App\Support\Settings;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class GazetteArticleResource extends Resource
{
    protected static ?string $model = GazetteArticle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Gazette';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'article';

    protected static ?string $pluralModelLabel = 'articles';

    protected static ?string $navigationLabel = 'Articles';

    protected static ?string $slug = 'gazette';

    protected static ?string $recordTitleAttribute = 'title';

    public static function publicUrl(GazetteArticle $record): string
    {
        return route('gazette.show', $record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Article')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('title')
                        ->label('Titre')
                        ->required()
                        ->maxLength(180),
                    Select::make('rubric')
                        ->label('Rubrique')
                        ->options(GazetteRubric::class)
                        ->default(GazetteRubric::VieLocale)
                        ->required()
                        ->native(false),
                    TextInput::make('byline')
                        ->label('Signature')
                        ->helperText('Un nom de journaliste fictif, ou « La rédaction ».')
                        ->default('La rédaction')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('rp_date')
                        ->label('Date RP de parution')
                        ->default(fn (): string => app(Settings::class)->rpDate())
                        ->maxLength(80),
                    Textarea::make('excerpt')
                        ->label('Chapeau')
                        ->helperText('Quelques lignes d’accroche, affichées dans la liste et sur l’accueil.')
                        ->rows(3)
                        ->maxLength(600),
                    ContentFields::markdown('body', 'Texte')->required(),
                    Select::make('location_id')
                        ->label('Lieu concerné')
                        ->relationship('location', 'name')
                        ->searchable()
                        ->preload(),
                ]),
            ContentFields::imageSection('gazette')->columnSpanFull(),
            Section::make('Publication')
                ->columnSpanFull()
                ->schema([
                    Select::make('status')
                        ->label('Statut')
                        ->options([
                            GazetteArticle::STATUS_DRAFT => 'Brouillon',
                            GazetteArticle::STATUS_PUBLISHED => 'Publié',
                        ])
                        ->default(GazetteArticle::STATUS_DRAFT)
                        ->required()
                        ->native(false),
                    DateTimePicker::make('published_at')
                        ->label('Date de parution')
                        ->helperText('Laissée vide, elle est fixée au moment de la publication. Une date future programme la parution.')
                        ->seconds(false),
                    Toggle::make('is_featured')
                        ->label('À la une de l’accueil'),
                    TextInput::make('slug')
                        ->label('Adresse')
                        ->helperText('Laissée vide, elle est créée à partir du titre.')
                        ->maxLength(70)
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->unique(ignoreRecord: true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable()->limit(60),
                TextColumn::make('rubric')
                    ->label('Rubrique')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === GazetteArticle::STATUS_PUBLISHED ? 'Publié' : 'Brouillon')
                    ->color(fn (string $state): string => $state === GazetteArticle::STATUS_PUBLISHED ? 'success' : 'gray'),
                IconColumn::make('is_featured')->label('À la une')->boolean(),
                TextColumn::make('published_at')->label('Parution')->dateTime('d/m/Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('author.pseudo')->label('Saisi par')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('rubric')->label('Rubrique')->options(GazetteRubric::class),
                SelectFilter::make('status')->label('Statut')->options([
                    GazetteArticle::STATUS_DRAFT => 'Brouillon',
                    GazetteArticle::STATUS_PUBLISHED => 'Publié',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGazetteArticles::route('/'),
            'create' => CreateGazetteArticle::route('/nouveau'),
            'edit' => EditGazetteArticle::route('/{record}/modifier'),
        ];
    }
}
