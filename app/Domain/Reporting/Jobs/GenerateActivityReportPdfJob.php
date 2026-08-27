<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Jobs;

use App\Domain\Documentation\Jobs\GenerateDocumentationPagePdfJob;
use App\Domain\Reporting\Actions\GenerateActivityReportPdf;
use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job in coda che genera il PDF di un report di attività (§6.5.3 del PRD,
 * US-409), dispatchato da `reports:generate-monthly` (US-410). Rilegge il
 * report per id (mai il model serializzato, stesso motivo di
 * {@see GenerateDocumentationPagePdfJob}) cosi'
 * produce sempre il PDF con i dati piu' recenti anche se piu' job si accodano
 * prima che il worker giri.
 */
final class GenerateActivityReportPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $activityReportId) {}

    public function handle(): void
    {
        $report = ActivityReport::query()->find($this->activityReportId);

        if ($report === null) {
            return;
        }

        GenerateActivityReportPdf::run($report);
    }
}
