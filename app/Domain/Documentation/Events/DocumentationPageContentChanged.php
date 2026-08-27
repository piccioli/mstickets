<?php

declare(strict_types=1);

namespace App\Domain\Documentation\Events;

use App\Domain\Documentation\Actions\UpdateDocumentationPage;
use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Tags\Listeners\RenameTagForDocumentationPage;

/**
 * Emesso da {@see UpdateDocumentationPage} quando `title` O `body` sono
 * effettivamente cambiati rispetto al valore persistito (§6.4.3 del PRD,
 * US-406): innesca la rigenerazione del PDF. Deliberatamente distinto da
 * {@see DocumentationPageRenamed} (solo title, consumato da
 * {@see RenameTagForDocumentationPage} per rinominare
 * il Tag collegato): un cambio del solo `body` deve rigenerare il PDF ma non
 * toccare il Tag, quindi le due semantiche restano separate.
 */
final readonly class DocumentationPageContentChanged
{
    public function __construct(
        public DocumentationPage $page,
    ) {}
}
