<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailDiscardReason;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Enums\SuppressionReason;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailSuppression;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use Webklex\PHPIMAP\Header;
use Webklex\PHPIMAP\Message;

/**
 * Classificazione anti-loop di un'email inbound già parsata da US-303
 * (§7.3.4, US-304): decide se il messaggio prosegue verso l'identificazione
 * del mittente (US-305) o se va scartato prima di qualunque azione, per non
 * produrre mail loop, spam confermato o duplicati.
 *
 * Rilegge il `.eml` grezzo (stesso pattern di `ParseInboundEmail`) perché gli
 * header di controllo (Auto-Submitted, Precedence, List-Id, ecc.) non sono
 * mai stati estratti da nessuna story precedente: solo subject/corpo lo sono.
 *
 * Un DSN (`Content-Type: multipart/report; report-type=delivery-status`) non
 * ha oggi una gestione bounce dedicata (US-319, story futura): è comunque
 * marcato `status = discarded` con motivo `EmailDiscardReason::
 * DeliveryStatusNotification`, cosicché "mai al ticketing" sia vero fin da
 * subito e US-319 possa in futuro filtrare esattamente questi messaggi per
 * correlarli all'email originale invece di reinterpretare uno scarto generico.
 */
final class ClassifyInboundEmail
{
    public static function run(EmailMessage $emailMessage): EmailMessage
    {
        try {
            $header = self::readHeader($emailMessage);

            $reason = self::discardReason($header, $emailMessage->from_email);

            if ($reason !== null) {
                return self::discard($emailMessage, $reason);
            }

            if (self::exceedsRateLimit($emailMessage->from_email)) {
                self::suppressForLoopProtection($emailMessage->from_email);
            }

            $emailMessage->forceFill(['status' => EmailStatus::Classified])->save();
        } catch (Throwable $exception) {
            Log::warning('mail.classify.failed', [
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

    private static function discardReason(Header $header, string $fromEmail): ?EmailDiscardReason
    {
        if (self::isDeliveryStatusNotification($header)) {
            return EmailDiscardReason::DeliveryStatusNotification;
        }

        if (self::isEmptyOrSystemSender($fromEmail)) {
            return EmailDiscardReason::SystemSender;
        }

        if (self::isSuppressed($fromEmail)) {
            return EmailDiscardReason::Suppressed;
        }

        if (self::isSelfSender($fromEmail)) {
            return EmailDiscardReason::SelfSender;
        }

        if (self::isAutoSubmitted($header)) {
            return EmailDiscardReason::AutoSubmitted;
        }

        if (self::hasBulkPrecedence($header)) {
            return EmailDiscardReason::Precedence;
        }

        if (self::isMailingList($header)) {
            return EmailDiscardReason::MailingList;
        }

        if (self::hasAutoResponseSuppressed($header)) {
            return EmailDiscardReason::AutoResponseSuppressed;
        }

        return null;
    }

    private static function readHeader(EmailMessage $emailMessage): Header
    {
        $raw = Storage::disk((string) config('mail_pipeline.storage.raw_disk'))
            ->get((string) $emailMessage->raw_path);

        if ($raw === null) {
            throw new RuntimeException("File grezzo mancante: {$emailMessage->raw_path}");
        }

        return Message::fromString($raw)->getHeader();
    }

    private static function headerValue(Header $header, string $name): ?string
    {
        $value = $header->get($name)->first();

        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function isDeliveryStatusNotification(Header $header): bool
    {
        $contentType = strtolower(self::headerValue($header, 'content-type') ?? '');

        return str_starts_with($contentType, 'multipart/report')
            && str_contains($contentType, 'report-type=delivery-status');
    }

    private static function isAutoSubmitted(Header $header): bool
    {
        $value = self::headerValue($header, 'auto-submitted');

        return $value !== null && strtolower($value) !== 'no';
    }

    private static function hasBulkPrecedence(Header $header): bool
    {
        $value = strtolower(self::headerValue($header, 'precedence') ?? '');

        return in_array($value, ['bulk', 'list', 'junk'], true);
    }

    private static function isMailingList(Header $header): bool
    {
        return self::headerValue($header, 'list-id') !== null
            || self::headerValue($header, 'list-unsubscribe') !== null;
    }

    private static function hasAutoResponseSuppressed(Header $header): bool
    {
        return self::headerValue($header, 'x-auto-response-suppress') !== null;
    }

    private static function isEmptyOrSystemSender(string $fromEmail): bool
    {
        if ($fromEmail === '') {
            return true;
        }

        $localPart = strtolower(Str::before($fromEmail, '@'));

        return in_array($localPart, ['mailer-daemon', 'postmaster', 'no-reply', 'noreply'], true);
    }

    private static function isSuppressed(string $fromEmail): bool
    {
        if ($fromEmail === '') {
            return false;
        }

        return EmailSuppression::query()->active()->whereRaw('lower(email) = ?', [strtolower($fromEmail)])->exists();
    }

    private static function isSelfSender(string $fromEmail): bool
    {
        $platformAddress = (string) config('mail_pipeline.support_address');

        if ($platformAddress === '' || $fromEmail === '') {
            return false;
        }

        return strtolower($fromEmail) === strtolower($platformAddress);
    }

    private static function exceedsRateLimit(string $fromEmail): bool
    {
        if ($fromEmail === '') {
            return false;
        }

        $hourly = EmailMessage::query()
            ->where('direction', EmailDirection::Inbound)
            ->whereRaw('lower(from_email) = ?', [strtolower($fromEmail)])
            ->where('received_at', '>=', now()->subHour())
            ->count();

        if ($hourly > (int) config('mail_pipeline.rate_limit.max_per_hour')) {
            return true;
        }

        $daily = EmailMessage::query()
            ->where('direction', EmailDirection::Inbound)
            ->whereRaw('lower(from_email) = ?', [strtolower($fromEmail)])
            ->where('received_at', '>=', now()->subDay())
            ->count();

        return $daily > (int) config('mail_pipeline.rate_limit.max_per_day');
    }

    private static function suppressForLoopProtection(string $fromEmail): void
    {
        EmailSuppression::query()->updateOrCreate(
            ['email' => $fromEmail],
            [
                'reason' => SuppressionReason::LoopProtection,
                'expires_at' => now()->addHours((int) config('mail_pipeline.rate_limit.suppression_hours')),
            ],
        );
    }

    private static function discard(EmailMessage $emailMessage, EmailDiscardReason $reason): EmailMessage
    {
        $emailMessage->forceFill([
            'status' => EmailStatus::Discarded,
            'failure_reason' => $reason->value,
        ])->save();

        return $emailMessage;
    }
}
