<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Parsers\EmailBodyParser;
use App\Domain\Mail\Parsers\SubjectNormalizer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use Webklex\PHPIMAP\Attribute;
use Webklex\PHPIMAP\Message;

/**
 * Parsa il `.eml` grezzo di un messaggio `email_messages` già archiviato da
 * US-302 (§7.3.5, US-303): rilegge il file da
 * `config('mail_pipeline.storage.raw_disk')`, lo passa a
 * `Webklex\PHPIMAP\Message::fromString()` (nessuna connessione IMAP
 * necessaria, stessa libreria di US-301), normalizza il subject e ne estrae
 * il corpo. Un fallimento QUALSIASI in questo processo (file mancante,
 * `.eml` malformato, decoding non gestibile) non lancia mai un'eccezione non
 * gestita fuori da questa Action: viene loggato e il messaggio passa a
 * `status = failed` con `failure_reason` valorizzato, cosicché un batch che
 * processa più messaggi non si fermi al primo che fallisce.
 */
final class ParseInboundEmail
{
    public static function run(EmailMessage $emailMessage): EmailMessage
    {
        try {
            $raw = Storage::disk((string) config('mail_pipeline.storage.raw_disk'))
                ->get((string) $emailMessage->raw_path);

            if ($raw === null) {
                throw new RuntimeException("File grezzo mancante: {$emailMessage->raw_path}");
            }

            $message = Message::fromString($raw);

            $normalizedSubject = SubjectNormalizer::normalize(
                self::attributeToNullableString($message->getSubject()) ?? $emailMessage->subject
            );

            $parsedBody = EmailBodyParser::parse(
                $message->hasTextBody() ? $message->getTextBody() : null,
                $message->hasHTMLBody() ? $message->getHTMLBody() : null,
            );

            $emailMessage->forceFill([
                'subject' => $normalizedSubject->subject,
                'body_text' => $parsedBody->bodyText,
                'body_html' => $parsedBody->bodyHtml,
                'status' => EmailStatus::Parsed,
            ])->save();
        } catch (Throwable $exception) {
            Log::warning('mail.parse.failed', [
                'email_message_id' => $emailMessage->id,
                'error' => $exception->getMessage(),
            ]);

            $emailMessage->forceFill([
                'status' => EmailStatus::Failed,
                'failure_reason' => $exception->getMessage(),
            ])->save();
        }

        return $emailMessage;
    }

    private static function attributeToNullableString(Attribute $attribute): ?string
    {
        if ($attribute->count() === 0) {
            return null;
        }

        return $attribute->toString() ?: null;
    }
}
