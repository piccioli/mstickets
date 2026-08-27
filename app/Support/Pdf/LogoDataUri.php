<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Domain\Documentation\Actions\GenerateDocumentationPagePdf;
use App\Domain\Reporting\Actions\GenerateActivityReportPdf;

/**
 * Incorpora un'immagine di logo come data URI base64 per un PDF generato in coda
 * (mai un URL: il job gira in coda, non può dipendere dalla raggiungibilità HTTP
 * dell'app verso se stessa). Riusato da
 * {@see GenerateDocumentationPagePdf} (§6.4.3,
 * US-406) e {@see GenerateActivityReportPdf}
 * (§6.5.3, US-409) — nessun errore se il file configurato manca, il PDF si
 * genera comunque senza logo.
 */
final class LogoDataUri
{
    public static function resolve(mixed $path): ?string
    {
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
