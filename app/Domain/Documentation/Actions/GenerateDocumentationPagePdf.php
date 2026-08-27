<?php

declare(strict_types=1);

namespace App\Domain\Documentation\Actions;

use App\Domain\Documentation\Jobs\GenerateDocumentationPagePdfJob;
use App\Domain\Documentation\Models\DocumentationPage;
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
            'logoDataUri' => self::logoDataUri(),
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

    /**
     * Incorpora il logo come data URI (mai un URL: il job gira in coda, non può
     * dipendere dalla raggiungibilità HTTP dell'app verso se stessa) — nessun
     * errore se il file configurato manca, il PDF si genera comunque senza logo.
     */
    private static function logoDataUri(): ?string
    {
        $path = config('documentation.pdf.logo_path');

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            return null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
