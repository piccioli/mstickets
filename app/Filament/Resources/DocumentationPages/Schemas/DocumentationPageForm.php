<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentationPages\Schemas;

use App\Domain\Documentation\Actions\CreateDocumentationPage;
use App\Domain\Documentation\Enums\DocumentationCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Form condiviso da CreateDocumentationPage/EditDocumentationPage (US-405).
 * `slug` non è un campo qui (generato da `title` in
 * {@see CreateDocumentationPage}): l'editor
 * gestisce solo i contenuti, mai l'identificatore tecnico della pagina.
 *
 * `documents`/`images` usano `->storeFiles(false)` (stesso idioma di
 * `TicketForm`/`ViewTicket` per gli allegati ticket, US-107): i file grezzi
 * arrivano come `UploadedFile` nei dati del form e vengono salvati sulle media
 * collection dedicate dalla pagina Filament (`CreateDocumentationPage`/
 * `EditDocumentationPage`), non da un cast automatico del form.
 */
class DocumentationPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Documentazione')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Titolo')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('category')
                            ->label('Categoria')
                            ->options(collect(DocumentationCategory::cases())->mapWithKeys(
                                fn (DocumentationCategory $category): array => [$category->value => $category->getLabel()],
                            ))
                            ->default(DocumentationCategory::Customer->value)
                            ->required(),
                        RichEditor::make('body')
                            ->label('Contenuto')
                            ->required()
                            ->columnSpanFull(),
                        FileUpload::make('documents')
                            ->label('Documenti allegati')
                            ->multiple()
                            ->storeFiles(false)
                            ->dehydrated(fn (?array $state): bool => filled($state)),
                        FileUpload::make('images')
                            ->label('Immagini allegate')
                            ->image()
                            ->multiple()
                            ->storeFiles(false)
                            ->dehydrated(fn (?array $state): bool => filled($state)),
                    ]),
            ]);
    }
}
