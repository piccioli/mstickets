<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\Identity\Enums\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rotta di download dedicata per un documento (`cai_documents`) importato dal datapack
 * RUNTS-CAI (US-804): disco privato `cai-documents`, mai un URL diretto. A differenza di
 * `ActivityReportPdfDownloadController`/`DocumentationPagePdfDownloadController` non esiste
 * una Policy dedicata al modello (`CaiDocument` è un modello di sola lettura per lo staff,
 * senza owner/proprietario da confrontare): l'autorizzazione è quindi lo stesso permesso
 * unico che gate l'intera CaiSectionResource (`Permission::CaiDirectoryView`), verificato
 * qui direttamente invece che tramite `AuthorizesRequests`/una Policy.
 */
class CaiDocumentDownloadController extends Controller
{
    public function __invoke(Request $request, CaiDocument $caiDocument): StreamedResponse
    {
        abort_unless((bool) Auth::user()?->can(Permission::CaiDirectoryView), 403);

        abort_unless(Storage::disk('cai-documents')->exists((string) $caiDocument->file_path), 404);

        return Storage::disk('cai-documents')->download(
            (string) $caiDocument->file_path,
            $caiDocument->file_name,
        );
    }
}
