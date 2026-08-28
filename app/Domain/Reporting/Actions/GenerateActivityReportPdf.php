<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Actions;

use App\Domain\Documentation\Actions\GenerateDocumentationPagePdf;
use App\Domain\Reporting\Events\ActivityReportPdfGenerated;
use App\Domain\Reporting\Models\ActivityReport;
use App\Http\Controllers\ActivityReportPdfDownloadController;
use App\Support\Pdf\LogoDataUri;
use Illuminate\Support\Facades\App;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * Unico punto che genera davvero il PDF di un report di attività (§6.5.3 del
 * PRD, US-409), riusato sia dal job dispatchato dal comando
 * `reports:generate-monthly` (US-410) sia da qualunque futura rigenerazione
 * manuale — mai duplicata la chiamata a spatie/laravel-pdf, stesso pattern di
 * {@see GenerateDocumentationPagePdf}
 * (US-406). Il percorso su disco è derivato dall'id (mai da un nome basato su
 * owner/periodo: quello serve solo al nome del file scaricato, vedi
 * {@see ActivityReportPdfDownloadController}) e sempre
 * sovrascritto, quindi idempotente per costruzione.
 */
final class GenerateActivityReportPdf
{
    public static function run(ActivityReport $report): void
    {
        $isFirstGeneration = $report->pdf_generated_at === null;
        $path = "activity-reports/{$report->id}.pdf";

        $tickets = $report->tickets()->orderBy('done_at')->get();
        $totalWorkedMinutes = (int) $tickets->sum('worked_minutes');

        $previousLocale = App::getLocale();
        App::setLocale($report->locale);

        try {
            Pdf::view('pdfs.activity-report', [
                'report' => $report,
                'tickets' => $tickets,
                'totalWorkedMinutes' => $totalWorkedMinutes,
                'logoDataUri' => LogoDataUri::resolve(config('reporting.pdf.logo_path')),
                'footer' => (string) config('reporting.pdf.footer'),
            ])
                ->format('a4')
                ->disk(config('reporting.pdf.disk'))
                ->save($path);
        } finally {
            App::setLocale($previousLocale);
        }

        $report->update([
            'pdf_path' => $path,
            'pdf_generated_at' => now(),
        ]);

        if ($isFirstGeneration) {
            event(new ActivityReportPdfGenerated($report));
        }
    }
}
