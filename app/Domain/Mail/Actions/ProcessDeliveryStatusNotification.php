<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Enums\SuppressionReason;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailSuppression;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Webklex\PHPIMAP\Attachment;
use Webklex\PHPIMAP\Message;

/**
 * Elabora un DSN (`multipart/report; report-type=delivery-status`) già
 * riconosciuto e scartato da {@see ClassifyInboundEmail} (§7.5.5 del PRD,
 * US-319): opera su un `EmailMessage` con `status = discarded` e
 * `failure_reason = EmailDiscardReason::DeliveryStatusNotification->value`,
 * filtrabile così senza reinterpretare uno scarto generico (nota lasciata da
 * US-304). Non è (ancora) invocata automaticamente da nessuna pipeline: il
 * wiring end-to-end è compito di US-326, stesso principio già seguito da
 * ResolveEmailSender/ResolveEmailThread prima di US-307.
 *
 * webklex tratta le parti `message/delivery-status` e `message/rfc822` di un
 * DSN come "allegati" (§Part::isAttachment(), verificato empiricamente: un
 * subtype diverso da plain/html senza filename/name ricade nel ramo
 * "è un allegato" di default) — nessuna API dedicata ai report di consegna
 * nella libreria, si estraggono quindi da `Message::getAttachments()` per
 * content-type.
 *
 * Correlazione con l'email originale (AC1): il Message-ID citato nella parte
 * `message/rfc822`/`text/rfc822-headers` (mai negli header del DSN stesso,
 * che referenziano solo il nuovo messaggio di notifica) è confrontato con
 * `email_messages.message_id` lato outbound — stesso formato senza `<`/`>`
 * usato da {@see SendOutboundTicketMail} (webklex normalizza allo stesso modo
 * gli header letti dalla libreria, mai un confronto con brackets).
 *
 * Hard bounce (`Action: failed` o `Status: 5.x.x`): sospensione immediata e
 * permanente (`email_suppressions.expires_at = null`), rimovibile solo da
 * amministrazione (US-323). Soft bounce (`Action: delayed` o `Status:
 * 4.x.x`): incrementa `bounce_count` sulla riga esistente (o la crea), ma la
 * sospensione vera e propria (quella verificata da
 * `EmailSuppression::scopeActive()`) scatta solo al raggiungimento della
 * soglia configurata — sotto soglia `expires_at` è impostato a `now()`
 * (già scaduto per costruzione), cosicché la riga esista per il conteggio
 * senza bloccare nel frattempo l'invio.
 */
final class ProcessDeliveryStatusNotification
{
    public static function run(EmailMessage $emailMessage): EmailMessage
    {
        try {
            $message = self::readMessage($emailMessage);
        } catch (Throwable $exception) {
            Log::warning('mail.dsn.read_failed', [
                'email_message_id' => $emailMessage->id,
                'error' => $exception->getMessage(),
            ]);

            return $emailMessage;
        }

        $statusPart = self::findAttachment($message, 'message/delivery-status');
        $originalPart = self::findAttachment($message, 'message/rfc822')
            ?? self::findAttachment($message, 'text/rfc822-headers');

        $action = $statusPart !== null ? self::matchField($statusPart->getContent(), 'Action') : null;
        $status = $statusPart !== null ? self::matchField($statusPart->getContent(), 'Status') : null;
        $reportedRecipient = $statusPart !== null ? self::matchRecipient($statusPart->getContent()) : null;
        $originalMessageId = $originalPart !== null ? self::extractMessageId($originalPart->getContent()) : null;

        $originalMessage = $originalMessageId !== null
            ? EmailMessage::query()
                ->where('direction', EmailDirection::Outbound)
                ->where('message_id', $originalMessageId)
                ->first()
            : null;

        self::correlateTicket($emailMessage, $originalMessage);

        $bouncedAddress = $reportedRecipient ?? self::firstRecipient($originalMessage);

        if ($bouncedAddress === null) {
            return $emailMessage;
        }

        if (self::isHardBounce($action, $status)) {
            self::registerHardBounce($bouncedAddress);
            $originalMessage?->forceFill(['status' => EmailStatus::Bounced])->save();
        } elseif (self::isSoftBounce($action, $status)) {
            self::registerSoftBounce($bouncedAddress);
        }

        return $emailMessage;
    }

    private static function readMessage(EmailMessage $emailMessage): ?Message
    {
        $rawPath = (string) $emailMessage->raw_path;

        if ($rawPath === '') {
            return null;
        }

        $raw = Storage::disk((string) config('mail_pipeline.storage.raw_disk'))->get($rawPath);

        return $raw === null ? null : Message::fromString($raw);
    }

    private static function findAttachment(?Message $message, string $contentType): ?Attachment
    {
        if ($message === null || ! $message->hasAttachments()) {
            return null;
        }

        foreach ($message->getAttachments() as $attachment) {
            if (strtolower((string) $attachment->getContentType()) === $contentType) {
                return $attachment;
            }
        }

        return null;
    }

    private static function matchField(string $content, string $name): ?string
    {
        if (preg_match('/^'.preg_quote($name, '/').':\s*(\S+)/im', $content, $matches) !== 1) {
            return null;
        }

        return trim($matches[1]);
    }

    private static function matchRecipient(string $content): ?string
    {
        if (preg_match('/^(?:Final|Original)-Recipient:\s*(?:rfc822;\s*)?(\S+)/im', $content, $matches) !== 1) {
            return null;
        }

        return trim($matches[1], " \t<>") ?: null;
    }

    private static function extractMessageId(string $content): ?string
    {
        if (preg_match('/^Message-ID:\s*(.+)$/im', $content, $matches) !== 1) {
            return null;
        }

        return trim($matches[1], " \t<>\r\n") ?: null;
    }

    private static function correlateTicket(EmailMessage $emailMessage, ?EmailMessage $originalMessage): void
    {
        if ($originalMessage === null || $originalMessage->ticket_id === null || $emailMessage->ticket_id !== null) {
            return;
        }

        $emailMessage->forceFill(['ticket_id' => $originalMessage->ticket_id])->save();
    }

    private static function firstRecipient(?EmailMessage $originalMessage): ?string
    {
        return $originalMessage?->to[0] ?? null;
    }

    private static function isHardBounce(?string $action, ?string $status): bool
    {
        return strtolower($action ?? '') === 'failed' || str_starts_with($status ?? '', '5');
    }

    private static function isSoftBounce(?string $action, ?string $status): bool
    {
        return strtolower($action ?? '') === 'delayed' || str_starts_with($status ?? '', '4');
    }

    private static function registerHardBounce(string $email): void
    {
        EmailSuppression::query()->updateOrCreate(
            ['email' => $email],
            [
                'reason' => SuppressionReason::HardBounce,
                'expires_at' => null,
            ],
        );
    }

    private static function registerSoftBounce(string $email): void
    {
        $existing = EmailSuppression::query()->where('email', $email)->first();

        if ($existing !== null && $existing->reason === SuppressionReason::HardBounce) {
            // Un hard bounce successivo può sospendere un mittente già in soft
            // bounce (registerHardBounce sopra), ma non vale il contrario: un
            // soft bounce arrivato dopo non deve mai "retrocedere" una
            // sospensione già permanente.
            return;
        }

        $bounceCount = ($existing->bounce_count ?? 0) + 1;
        $threshold = (int) config('mail_pipeline.bounce.soft_bounce_threshold');

        EmailSuppression::query()->updateOrCreate(
            ['email' => $email],
            [
                'reason' => SuppressionReason::SoftBounce,
                'bounce_count' => $bounceCount,
                'expires_at' => $bounceCount >= $threshold ? null : now(),
            ],
        );
    }
}
