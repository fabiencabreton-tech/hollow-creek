<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Pages officielles : présentation de l'accueil, règlement et pages légales.
 * Le texte s'écrit en Markdown ; le HTML saisi est neutralisé à l'affichage.
 */
class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'page officielle';

    protected static ?string $pluralModelLabel = 'pages officielles';

    protected static ?string $navigationLabel = 'Pages officielles';

    protected static ?string $slug = 'pages';

    protected static ?string $recordTitleAttribute = 'title';

    public static function publicUrl(Page $page): string
    {
        return $page->slug === 'accueil' ? route('home') : url('/'.$page->slug);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    TextInput::make('title')
                        ->label('Titre')
                        ->required()
                        ->maxLength(150),
                    MarkdownEditor::make('body')
                        ->label('Texte')
                        ->helperText('Mise en forme Markdown : **gras**, *italique*, ## intertitre, - liste, [lien](https://…). Le HTML n’est pas interprété.')
                        ->required()
                        ->fileAttachments(false)
                        ->toolbarButtons([
                            ['bold', 'italic', 'link'],
                            ['heading', 'blockquote', 'bulletList', 'orderedList', 'table'],
                            ['undo', 'redo'],
                        ]),
                    Toggle::make('is_published')
                        ->label('Publiée')
                        ->helperText('Une page dépubliée renvoie « page introuvable » aux visiteurs.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->columns([
                TextColumn::make('title')
                    ->label('Page')
                    ->description(fn (Page $record): string => '/'.($record->slug === 'accueil' ? '' : $record->slug))
                    ->searchable(),
                IconColumn::make('is_published')
                    ->label('Publiée')
                    ->boolean(),
                TextColumn::make('editor.pseudo')
                    ->label('Modifiée par')
                    ->placeholder('—'),
                TextColumn::make('updated_at')
                    ->label('Dernière modification')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('voir')
                    ->label('Voir sur le site')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->url(fn (Page $record): string => static::publicUrl($record))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'edit' => EditPage::route('/{record}/modifier'),
        ];
    }
}
