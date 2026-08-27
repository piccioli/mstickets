<?php

declare(strict_types=1);

namespace App\Domain\Documentation\Events;

use App\Domain\Documentation\Actions\UpdateDocumentationPage;
use App\Domain\Documentation\Models\DocumentationPage;

/**
 * Emesso da {@see UpdateDocumentationPage} solo
 * quando `title` è effettivamente cambiato rispetto al valore persistito (§6.4.2,
 * US-405): innesca la rinomina del Tag collegato mantenendo la stessa riga,
 * senza crearne uno nuovo.
 */
final readonly class DocumentationPageRenamed
{
    public function __construct(
        public DocumentationPage $page,
        public string $oldTitle,
        public string $newTitle,
    ) {}
}
