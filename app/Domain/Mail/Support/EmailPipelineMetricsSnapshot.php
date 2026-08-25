<?php

declare(strict_types=1);

namespace App\Domain\Mail\Support;

/**
 * Fotografia delle metriche essenziali del sottosistema email (§7.7, US-323),
 * calcolata da {@see EmailPipelineMetrics::snapshot()}.
 */
final readonly class EmailPipelineMetricsSnapshot
{
    public function __construct(
        public int $processedLast24h,
        public int $discardedLast24h,
        public int $failedLast24h,
        public ?float $avgProcessingSeconds,
        public ?float $bounceRate,
    ) {}
}
