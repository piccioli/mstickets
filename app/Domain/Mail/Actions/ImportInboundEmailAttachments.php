<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Mail\Enums\EmailAttachmentStatus;
use App\Domain\Mail\Models\EmailAttachment;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Support\EmailAttachmentTypes;
use App\Domain\Ticketing\Models\TicketMessage;
use App\Domain\Ticketing\Support\TicketAttachmentTypes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use Webklex\PHPIMAP\Attachment;
use Webklex\PHPIMAP\Message;

/**
 * Importa gli allegati di un'email inbound sul `TicketMessage` appena creato/
 * aggiornato da {@see ApplyInboundEmail} (§7.3.9 del PRD, US-309, problema 15
 * del v1). Rilegge il `.eml` grezzo (stesso pattern I/O di
 * {@see ParseInboundEmail}/{@see ClassifyInboundEmail}): nessun dato di
 * allegato viene mai fatto transitare per `RawInboundEmail`.
 *
 * Ogni allegato è elaborato nel proprio try/catch: un errore su un singolo
 * allegato (decoding, salvataggio) produce una riga `email_attachments` con
 * `status = failed` e non ferma né gli altri allegati né l'elaborazione del
 * messaggio. Un allegato scartato per tipo/dimensione produce comunque una
 * riga con `status` che inizia per `rejected_` — mai uno scarto silenzioso.
 *
 * Il nome file originale NON è MAI usato come path su disco (path traversal,
 * problema 15 del v1): il file fisico è nominato da un ULID
 * (`usingFileName()`), il nome originale sanitizzato resta solo come
 * metadato (`filename` su `email_attachments`, `usingName()` su Media). Il
 * MIME validato è quello di `Attachment::getMimeType()` (sniffing reale del
 * contenuto via `finfo`, mai il Content-Type dichiarato nell'header).
 *
 * Gotcha verificato empiricamente: `Attachment::getSize()` restituisce la
 * dimensione della parte MIME ancora codificata (es. base64), non i byte
 * decodificati — la dimensione reale da confrontare con i limiti è sempre
 * `strlen($attachment->getContent())`.
 */
final class ImportInboundEmailAttachments
{
    public static function run(EmailMessage $emailMessage, TicketMessage $ticketMessage): void
    {
        $message = self::readMessage($emailMessage);

        if ($message === null || ! $message->hasAttachments()) {
            return;
        }

        $totalBytes = 0;
        $count = 0;

        foreach ($message->getAttachments() as $attachment) {
            if (! EmailAttachmentTypes::includeInline() && self::isInline($attachment)) {
                continue;
            }

            self::importOne($emailMessage, $ticketMessage, $attachment, $totalBytes, $count);
        }
    }

    private static function readMessage(EmailMessage $emailMessage): ?Message
    {
        $rawPath = (string) $emailMessage->raw_path;

        if ($rawPath === '') {
            return null;
        }

        try {
            $raw = Storage::disk((string) config('mail_pipeline.storage.raw_disk'))->get($rawPath);

            return $raw === null ? null : Message::fromString($raw);
        } catch (Throwable $exception) {
            Log::warning('mail.attachments.read_failed', [
                'email_message_id' => $emailMessage->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private static function isInline(Attachment $attachment): bool
    {
        return strtolower((string) $attachment->getDisposition()) === 'inline';
    }

    private static function importOne(
        EmailMessage $emailMessage,
        TicketMessage $ticketMessage,
        Attachment $attachment,
        int &$totalBytes,
        int &$count,
    ): void {
        $originalName = self::originalName($attachment);
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $filename = self::sanitizeFilename($originalName, $extension);
        $mimeType = (string) $attachment->getMimeType();

        try {
            $content = $attachment->getContent();
            $size = strlen($content);

            if ($count >= EmailAttachmentTypes::maxCount()) {
                self::reject($emailMessage, $filename, $mimeType, $size, EmailAttachmentStatus::RejectedSize, 'Numero massimo di allegati per messaggio superato');

                return;
            }

            if ($size > EmailAttachmentTypes::maxFileSize()) {
                self::reject($emailMessage, $filename, $mimeType, $size, EmailAttachmentStatus::RejectedSize, 'Dimensione allegato superiore al limite consentito');

                return;
            }

            if (($totalBytes + $size) > EmailAttachmentTypes::maxTotalSize()) {
                self::reject($emailMessage, $filename, $mimeType, $size, EmailAttachmentStatus::RejectedSize, 'Dimensione totale allegati superiore al limite consentito');

                return;
            }

            if (! EmailAttachmentTypes::isAllowed($extension, $mimeType)) {
                self::reject($emailMessage, $filename, $mimeType, $size, EmailAttachmentStatus::RejectedMime, "Tipo file non consentito: {$mimeType}");

                return;
            }

            $media = $ticketMessage->addMediaFromString($content)
                ->usingFileName(self::storageFilename($extension))
                ->usingName($filename)
                ->toMediaCollection('attachments');

            EmailAttachment::create([
                'email_message_id' => $emailMessage->id,
                'filename' => $filename,
                'mime_type' => $mimeType,
                'size_bytes' => $size,
                'disk' => $media->disk,
                'path' => sprintf('%d/%s', $media->id, $media->file_name),
                'media_id' => $media->id,
                'status' => EmailAttachmentStatus::Stored,
            ]);

            $totalBytes += $size;
            $count++;
        } catch (Throwable $exception) {
            Log::warning('mail.attachments.item_failed', [
                'email_message_id' => $emailMessage->id,
                'filename' => $filename,
                'error' => $exception->getMessage(),
            ]);

            self::reject($emailMessage, $filename, $mimeType, 0, EmailAttachmentStatus::Failed, $exception->getMessage());
        }
    }

    private static function reject(
        EmailMessage $emailMessage,
        string $filename,
        string $mimeType,
        int $size,
        EmailAttachmentStatus $status,
        string $reason,
    ): void {
        EmailAttachment::create([
            'email_message_id' => $emailMessage->id,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size_bytes' => $size,
            'disk' => TicketAttachmentTypes::disk(),
            'path' => '',
            'status' => $status,
            'rejection_reason' => $reason,
        ]);
    }

    private static function originalName(Attachment $attachment): string
    {
        $name = (string) ($attachment->getName() ?: $attachment->filename);

        return $name !== '' ? $name : 'allegato';
    }

    private static function sanitizeFilename(string $original, string $extension): string
    {
        $base = Str::slug((string) pathinfo($original, PATHINFO_FILENAME));

        if ($base === '') {
            $base = 'allegato';
        }

        return $extension !== '' ? "{$base}.{$extension}" : $base;
    }

    private static function storageFilename(string $extension): string
    {
        $ulid = (string) Str::ulid();

        return $extension !== '' ? "{$ulid}.{$extension}" : $ulid;
    }
}
