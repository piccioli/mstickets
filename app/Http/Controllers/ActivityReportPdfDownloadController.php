<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rotta di download dedicata per il PDF generato di un report di attività
 * (§6.5.3 del PRD, §6.1.8, US-409): disco privato, mai un URL diretto.
 * L'autorizzazione delega SEMPRE a `ActivityReportPolicy::view()` (chi può
 * vedere il report può scaricarne il PDF, nessun altro — un utente con solo
 * `activity-report.view.own` non può scaricare il report di un altro owner
 * nemmeno passando il suo id in URL), stesso pattern già in uso da
 * {@see DocumentationPagePdfDownloadController} per il PDF di documentazione.
 */
class ActivityReportPdfDownloadController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request, ActivityReport $activityReport): StreamedResponse
    {
        $this->authorize('view', $activityReport);

        abort_if($activityReport->pdf_path === null, 404);

        return Storage::disk(config('reporting.pdf.disk'))->download(
            $activityReport->pdf_path,
            $activityReport->pdfDownloadFilename(),
        );
    }
}
