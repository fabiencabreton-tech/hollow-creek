<?php

namespace App\Filament\Support;

use App\Support\ImageStore;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Champs partagés par les fiches de l'univers et la Gazette.
 */
class ContentFields
{
    public static function markdown(string $name, string $label, ?string $helper = null): MarkdownEditor
    {
        return MarkdownEditor::make($name)
            ->label($label)
            ->helperText($helper ?? 'Markdown : **gras**, *italique*, ## intertitre, - liste, > citation. Le HTML n’est pas interprété.')
            ->fileAttachments(false)
            ->toolbarButtons([
                ['bold', 'italic', 'link'],
                ['heading', 'blockquote', 'bulletList', 'orderedList'],
                ['undo', 'redo'],
            ]);
    }

    public static function imageSection(string $directory): Section
    {
        return Section::make('Illustration')
            ->description('Photographie sous licence ou illustration commandée à un artiste. Aucune image générée par intelligence artificielle.')
            ->collapsible()
            ->schema([
                FileUpload::make('image_path')
                    ->label('Image')
                    ->image()
                    ->disk(ImageStore::DISK)
                    ->directory($directory)
                    ->visibility('public')
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                    ->helperText('JPEG, PNG, WebP ou GIF, 2 Mo maximum. L’image est ré-encodée : ses métadonnées (lieu, appareil) sont supprimées.')
                    ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => app(ImageStore::class)->store($file, $directory)),
                TextInput::make('image_credit')
                    ->label('Crédit de l’image')
                    ->helperText('Auteur ou autrice, source et licence. Obligatoire avec une image.')
                    ->requiredWith('image_path')
                    ->maxLength(255),
            ]);
    }

    public static function publication(): Section
    {
        return Section::make('Publication')
            ->schema([
                Toggle::make('is_published')
                    ->label('Publiée sur le site')
                    ->helperText('En brouillon, la fiche reste invisible des visiteurs ; l’équipe peut la prévisualiser.'),
                TextInput::make('slug')
                    ->label('Adresse')
                    ->helperText('Laissée vide, elle est créée à partir du nom.')
                    ->maxLength(70)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->unique(ignoreRecord: true),
            ]);
    }
}
