<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rotta di download dedicata per un documento (`cai_documents`) importato dal datapack
 * RUNTS-CAI (US-804): disco privato `cai-documents`, mai un URL diretto. A differenza di
 * `ActivityReportPdfDownloadController`/`DocumentationPagePdfDownloadController` non esiste
 * una Policy dedicata al modello (`CaiDocument` è un modello di sola lettura, senza owner
 * applicativo diretto): l'autorizzazione è verificata qui direttamente invece che tramite
 * `AuthorizesRequests`/una Policy, con due vie d'accesso (US-804, US-806):
 * - lo staff con il permesso di catalogo `Permission::CaiDirectoryView` (intera anagrafica);
 * - il cliente Sezione proprietario della `CaiSection` a cui il documento appartiene (via
 *   `CaiRuntsRegistration::section()`), per i propri allegati sulla `CustomerDashboard`.
 */
class CaiDocumentDownloadController extends Controller
{
    public function __invoke(Request $request, CaiDocument $caiDocument): StreamedResponse
    {
        abort_unless($this->authorized($caiDocument), 403);

        abort_unless(Storage::disk('cai-documents')->exists((string) $caiDocument->file_path), 404);

        return Storage::disk('cai-documents')->download(
            (string) $caiDocument->file_path,
            $caiDocument->file_name,
        );
    }

    private function authorized(CaiDocument $caiDocument): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->can(Permission::CaiDirectoryView)) {
            return true;
        }

        return $caiDocument->runtsRegistration?->section?->user_id === $user->id;
    }
}
