<?php

declare(strict_types=1);

namespace App\Domain\Mail\Support;

use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use Carbon\CarbonInterface;

/**
 * Metriche essenziali del sottosistema email (§7.7, US-323): messaggi
 * elaborati/scartati/falliti nelle ultime 24h, tempo medio di elaborazione
 * (fetch → applied), bounce rate. "Elaborati/scartati" contano i messaggi
 * inbound il cui STATO ATTUALE è `applied`/`discarded` e il cui `updated_at`
 * (ultima scrittura di stato, nessuna colonna dedicata "processed_at" nello
 * schema di Fase 0) ricade nella finestra; "falliti" non è ristretto alla
 * direzione, `failed` è uno stato condiviso da inbound e outbound (§7.3.2).
 *
 * Il tempo di elaborazione usa `received_at` (US-302, valorizzato al fetch)
 * come istante di partenza e `updated_at` come istante di arrivo allo stato
 * `applied` — nessuna colonna `applied_at` dedicata esiste nello schema.
 *
 * Il bounce rate NON usa `EmailStatus::Sent`: nessun listener di questa fase
 * transita mai un outbound da `queued` a `sent` (nota lasciata in US-311, "la
 * riga outbound resta status=Queued dopo l'accodamento") — il denominatore è
 * quindi `bounced + queued` (i soli due stati terminali/quasi-terminali
 * raggiungibili da un invio andato a buon fine), mai `sent`.
 */
final class EmailPipelineMetrics
{
    public static function snapshot(): EmailPipelineMetricsSnapshot
    {
        $since = now()->subDay();

        $processed = EmailMessage::query()
            ->where('direction', EmailDirection::Inbound)
            ->where('status', EmailStatus::Applied)
            ->where('updated_at', '>=', $since)
            ->count();

        $discarded = EmailMessage::query()
            ->where('direction', EmailDirection::Inbound)
            ->where('status', EmailStatus::Discarded)
            ->where('updated_at', '>=', $since)
            ->count();

        $failed = EmailMessage::query()
            ->where('status', EmailStatus::Failed)
            ->where('updated_at', '>=', $since)
            ->count();

        return new EmailPipelineMetricsSnapshot(
            processedLast24h: $processed,
            discardedLast24h: $discarded,
            failedLast24h: $failed,
            avgProcessingSeconds: self::avgProcessingSeconds($since),
            bounceRate: self::bounceRate(),
        );
    }

    private static function avgProcessingSeconds(CarbonInterface $since): ?float
    {
        $seconds = EmailMessage::query()
            ->where('direction', EmailDirection::Inbound)
            ->where('status', EmailStatus::Applied)
            ->where('updated_at', '>=', $since)
            ->whereNotNull('received_at')
            ->get(['received_at', 'updated_at'])
            ->map(fn (EmailMessage $message): float => $message->received_at->diffInSeconds($message->updated_at))
            ->all();

        return $seconds === [] ? null : array_sum($seconds) / count($seconds);
    }

    private static function bounceRate(): ?float
    {
        $attempted = EmailMessage::query()
            ->where('direction', EmailDirection::Outbound)
            ->whereIn('status', [EmailStatus::Bounced, EmailStatus::Queued])
            ->count();

        if ($attempted === 0) {
            return null;
        }

        $bounced = EmailMessage::query()
            ->where('direction', EmailDirection::Outbound)
            ->where('status', EmailStatus::Bounced)
            ->count();

        return $bounced / $attempted;
    }
}
