<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\DTO\TicketLogChanges;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Models\TicketLog;
use App\Domain\Ticketing\Models\TicketMessage;
use App\Domain\Ticketing\Support\TicketAttachmentTypes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Unico punto di ingresso per allegare un file a un messaggio del ticket (§9.6 del
 * PRD, US-107): valida tipo/dimensione contro l'unica lista condivisa
 * {@see TicketAttachmentTypes} PRIMA di scrivere sul disco (errore di validazione
 * localizzato, mai un'eccezione generica del vendor, A2), salva sulla collection
 * medialibrary `attachments` (disco privato, nome file già sanitizzato dal path
 * generator di medialibrary) e scrive il proprio `ticket_log` `attachment_added`.
 */
final class AddTicketAttachment
{
    public static function run(TicketMessage $ticketMessage, UploadedFile $file, User $user): Media
    {
        self::guardAgainstDisallowedFile($file);

        return DB::transaction(function () use ($ticketMessage, $file, $user): Media {
            $media = $ticketMessage
                ->addMedia($file)
                ->usingName($file->getClientOriginalName())
                ->toMediaCollection('attachments');

            TicketLog::create([
                'ticket_id' => $ticketMessage->ticket_id,
                'user_id' => $user->id,
                'event' => TicketLogEvent::AttachmentAdded,
                'changes' => TicketLogChanges::attachmentAdded($media->file_name)->toArray(),
                'is_system' => $user->isSystem(),
                'occurred_at' => now(),
            ]);

            return $media;
        });
    }

    private static function guardAgainstDisallowedFile(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = strtolower($file->getMimeType() ?? $file->getClientMimeType());

        if (! TicketAttachmentTypes::isAllowed($extension, $mimeType)) {
            throw ValidationException::withMessages([
                'file' => ["Il tipo di file '{$extension}' non è ammesso come allegato."],
            ]);
        }

        if ($file->getSize() > TicketAttachmentTypes::maxFileSize()) {
            throw ValidationException::withMessages([
                'file' => ['Il file supera la dimensione massima consentita per gli allegati.'],
            ]);
        }
    }
}
