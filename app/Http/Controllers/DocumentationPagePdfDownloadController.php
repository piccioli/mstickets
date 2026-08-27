<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Documentation\Models\DocumentationPage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rotta di download dedicata per il PDF generato di una pagina di documentazione
 * (§6.4.3 del PRD, §6.1.8, US-406): disco privato, mai un URL diretto.
 * L'autorizzazione delega SEMPRE a `DocumentationPagePolicy::view()` (chi può
 * vedere la pagina può scaricarne il PDF, nessun altro) — stesso pattern già
 * in uso da {@see TicketAttachmentDownloadController} per gli allegati ticket.
 */
class DocumentationPagePdfDownloadController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request, DocumentationPage $documentationPage): StreamedResponse
    {
        $this->authorize('view', $documentationPage);

        abort_if($documentationPage->pdf_path === null, 404);

        return Storage::disk(config('documentation.pdf.disk'))->download(
            $documentationPage->pdf_path,
            "{$documentationPage->slug}.pdf",
        );
    }
}
