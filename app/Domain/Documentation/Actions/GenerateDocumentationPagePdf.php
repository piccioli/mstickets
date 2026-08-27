<?php

declare(strict_types=1);

namespace App\Domain\Documentation\Actions;

use App\Domain\Documentation\Jobs\GenerateDocumentationPagePdfJob;
use App\Domain\Documentation\Models\DocumentationPage;
use App\Support\Pdf\LogoDataUri;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * Unico punto che genera davvero il PDF di una pagina di documentazione
 * (§6.4.3 del PRD, US-406): riusato sia dal job dispatchato in automatico alla
 * creazione/modifica ({@see GenerateDocumentationPagePdfJob})
 * sia dal comando `documentation:regenerate-pdfs` — mai duplicata la chiamata a
 * spatie/laravel-pdf. Il percorso è derivato dall'id (mai dallo slug: uno slug
 * potrebbe in teoria cambiare in futuro, l'id no) e sempre sovrascritto, quindi
 * idempotente per costruzione.
 */
final class GenerateDocumentationPagePdf
{
    public static function run(DocumentationPage $page): void
    {
        $path = "documentation-pages/{$page->id}.pdf";

        Pdf::view('pdfs.documentation-page', [
            'page' => $page,
            'logoDataUri' => LogoDataUri::resolve(config('documentation.pdf.logo_path')),
            'footer' => (string) config('documentation.pdf.footer'),
        ])
            ->format('a4')
            ->disk(config('documentation.pdf.disk'))
            ->save($path);

        $page->update([
            'pdf_path' => $path,
            'pdf_generated_at' => now(),
        ]);
    }
}
