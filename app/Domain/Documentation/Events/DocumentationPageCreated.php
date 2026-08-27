<?php

declare(strict_types=1);

namespace App\Domain\Documentation\Events;

use App\Domain\Documentation\Actions\CreateDocumentationPage;
use App\Domain\Documentation\Models\DocumentationPage;

/**
 * Emesso da {@see CreateDocumentationPage} (mai
 * da un hook Eloquent `booted()`/`creating()` sul model, correzione esplicita
 * rispetto al v1, §6.4.2, US-405): innesca la creazione automatica del Tag
 * "Documentation: <titolo>" collegato alla pagina.
 */
final readonly class DocumentationPageCreated
{
    public function __construct(
        public DocumentationPage $page,
    ) {}
}
