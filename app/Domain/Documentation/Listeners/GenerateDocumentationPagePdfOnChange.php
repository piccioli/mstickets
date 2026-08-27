<?php

declare(strict_types=1);

namespace App\Domain\Documentation\Listeners;

use App\Domain\Documentation\Events\DocumentationPageContentChanged;
use App\Domain\Documentation\Events\DocumentationPageCreated;
use App\Domain\Documentation\Jobs\GenerateDocumentationPagePdfJob;

/**
 * Reagisce alla creazione o alla modifica di title/body di una pagina di
 * documentazione dispatchando il job di generazione PDF in coda (§6.4.3 del
 * PRD, US-406) — stesso pattern già in uso per il ricalcolo ore lavorate
 * (listener leggero che si limita a dispatchare, il lavoro vero vive nel Job).
 */
final class GenerateDocumentationPagePdfOnChange
{
    public function handle(DocumentationPageCreated|DocumentationPageContentChanged $event): void
    {
        GenerateDocumentationPagePdfJob::dispatch($event->page->id);
    }
}
