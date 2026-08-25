<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Support\RawInboundEmail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Archivia un messaggio grezzo letto da IMAP (§7.3.3 del PRD, US-302): scrive
 * il `.eml` su `config('mail_pipeline.storage.raw_disk')` PRIMA di creare la
 * riga `email_messages` (status=received). Un messaggio già presente per
 * (imap_folder, imap_uid) — l'unique esiste già da Fase 0/US-016 — è saltato
 * senza nemmeno scrivere il file su disco: rieseguire `mail:fetch-inbound`
 * sullo stesso stato IMAP non produce mai duplicati.
 */
final class StoreRawInboundEmail
{
    public static function run(RawInboundEmail $raw): ?EmailMessage
    {
        $alreadyStored = EmailMessage::query()
            ->where('imap_folder', $raw->imapFolder)
            ->where('imap_uid', $raw->imapUid)
            ->exists();

        if ($alreadyStored) {
            return null;
        }

        $rawPath = Str::ulid()->toString().'.eml';

        Storage::disk((string) config('mail_pipeline.storage.raw_disk'))->put($rawPath, $raw->rawMessage);

        return EmailMessage::create([
            'direction' => EmailDirection::Inbound,
            'status' => EmailStatus::Received,
            'imap_folder' => $raw->imapFolder,
            'imap_uid' => $raw->imapUid,
            'raw_path' => $rawPath,
            'message_id' => $raw->messageId,
            'from_email' => $raw->fromEmail ?? '',
            'from_name' => $raw->fromName,
            'subject' => $raw->subject,
            'to' => $raw->to,
            'in_reply_to' => $raw->inReplyTo,
            'references' => $raw->references === [] ? null : implode(' ', $raw->references),
            'received_at' => now(),
        ]);
    }
}
