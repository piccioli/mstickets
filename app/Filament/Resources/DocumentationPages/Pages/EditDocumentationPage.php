<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentationPages\Pages;

use App\Domain\Documentation\Actions\UpdateDocumentationPage as UpdateDocumentationPageAction;
use App\Domain\Documentation\Models\DocumentationPage;
use App\Filament\Resources\DocumentationPages\DocumentationPageResource;
use App\Filament\Resources\DocumentationPages\Support\AttachesDocumentationMedia;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * L'aggiornamento passa SEMPRE da {@see UpdateDocumentationPageAction}
 * (§6.4.2, US-405), che emette `DocumentationPageRenamed` solo quando `title`
 * è effettivamente cambiato — mai un update Eloquent diretto sul record, a
 * differenza di `EditTicket` (lì nessun evento di dominio dipende dal cambio
 * di un singolo campo). Il form non pre-carica i documenti/immagini già
 * allegati (`->storeFiles(false)`, US-107 style): qui si possono solo
 * aggiungere nuovi allegati, mai rimuovere quelli esistenti.
 */
class EditDocumentationPage extends EditRecord
{
    use AttachesDocumentationMedia;

    protected static string $resource = DocumentationPageResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        abort_unless($record instanceof DocumentationPage, 404);

        $documents = $data['documents'] ?? [];
        $images = $data['images'] ?? [];
        unset($data['documents'], $data['images']);

        $page = UpdateDocumentationPageAction::run($record, $data);

        self::attachDocumentationMedia($page, $documents, 'documents');
        self::attachDocumentationMedia($page, $images, 'images');

        return $page;
    }
}
