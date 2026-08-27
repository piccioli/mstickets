<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentationPages\Pages;

use App\Domain\Documentation\Actions\CreateDocumentationPage as CreateDocumentationPageAction;
use App\Filament\Resources\DocumentationPages\DocumentationPageResource;
use App\Filament\Resources\DocumentationPages\Schemas\DocumentationPageForm;
use App\Filament\Resources\DocumentationPages\Support\AttachesDocumentationMedia;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Non usa il flusso di default `Model::create()` di Filament: la creazione
 * passa SEMPRE da {@see CreateDocumentationPageAction} (§6.4.2, US-405), che
 * genera lo slug ed emette `DocumentationPageCreated` (mai un hook Eloquent).
 * `documents`/`images` non sono attributi del model (`->storeFiles(false)`
 * nel form, {@see DocumentationPageForm}):
 * vanno rimossi dai dati passati all'Action e salvati separatamente sulle
 * media collection, stesso idioma di `AddTicketAttachment` (US-107).
 */
class CreateDocumentationPage extends CreateRecord
{
    use AttachesDocumentationMedia;

    protected static string $resource = DocumentationPageResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $documents = $data['documents'] ?? [];
        $images = $data['images'] ?? [];
        unset($data['documents'], $data['images']);

        $page = CreateDocumentationPageAction::run($data);

        self::attachDocumentationMedia($page, $documents, 'documents');
        self::attachDocumentationMedia($page, $images, 'images');

        return $page;
    }
}
