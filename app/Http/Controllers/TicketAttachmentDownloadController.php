<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Ticketing\Models\TicketMessage;
use App\Domain\Ticketing\Support\TicketAttachmentSvgSanitizer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rotta di download dedicata per gli allegati dei messaggi (§9.6 del PRD, US-107):
 * disco privato, mai un URL medialibrary pubblico. L'autorizzazione delega SEMPRE
 * a `TicketPolicy::view()` (chi può vedere il ticket può scaricare l'allegato,
 * nessun altro, §9.5) — mai un controllo duplicato qui. Un SVG viene sanitizzato
 * al volo prima di essere servito (§17.2 nota: può contenere script).
 */
class TicketAttachmentDownloadController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request, Media $media): StreamedResponse|Response
    {
        abort_unless(
            $media->collection_name === 'attachments' && $media->model_type === TicketMessage::class,
            404
        );

        $ticketMessage = TicketMessage::query()->findOrFail($media->model_id);

        $this->authorize('view', $ticketMessage->ticket);

        if ($media->mime_type === 'image/svg+xml') {
            $sanitized = TicketAttachmentSvgSanitizer::sanitize(
                Storage::disk($media->disk)->get($media->getPathRelativeToRoot())
            );

            return response($sanitized, 200, [
                'Content-Type' => 'image/svg+xml',
                'Content-Disposition' => 'inline; filename="'.addslashes($media->file_name).'"',
            ]);
        }

        return $media->toResponse($request);
    }
}
