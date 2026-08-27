<?php

declare(strict_types=1);

namespace App\Domain\Documentation\Jobs;

use App\Domain\Documentation\Actions\GenerateDocumentationPagePdf;
use App\Domain\Documentation\Listeners\GenerateDocumentationPagePdfOnChange;
use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\TimeTracking\Jobs\RecalculateTicketWorkedTimeJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job in coda dispatchato da {@see GenerateDocumentationPagePdfOnChange}
 * (§6.4.3 del PRD, US-406): rilegge la pagina per id (mai il model serializzato,
 * stesso motivo di {@see RecalculateTicketWorkedTimeJob})
 * cosi' produce sempre il PDF con i dati piu' recenti anche se piu' modifiche si
 * accodano prima che il worker giri.
 */
final class GenerateDocumentationPagePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $documentationPageId) {}

    public function handle(): void
    {
        $page = DocumentationPage::query()->find($this->documentationPageId);

        if ($page === null) {
            return;
        }

        GenerateDocumentationPagePdf::run($page);
    }
}
