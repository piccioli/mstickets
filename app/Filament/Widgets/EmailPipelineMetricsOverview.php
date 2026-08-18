<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Mail\Support\EmailPipelineMetrics;
use App\Filament\Pages\EmailSuppressions;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * Metriche essenziali del sottosistema email (§7.7, US-323), mostrate in
 * testa alla pagina "Soppressioni" ({@see EmailSuppressions}).
 * Gate identico a `email.view` (sola lettura, stesso permesso della
 * Resource Registro, US-321): un utente senza permesso email non vede il
 * widget nemmeno se una futura pagina lo includesse altrove.
 */
class EmailPipelineMetricsOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return (bool) Auth::user()?->can(Permission::EmailView);
    }

    protected function getStats(): array
    {
        $snapshot = EmailPipelineMetrics::snapshot();

        return [
            Stat::make('Elaborati (24h)', (string) $snapshot->processedLast24h),
            Stat::make('Scartati (24h)', (string) $snapshot->discardedLast24h),
            Stat::make('Falliti (24h)', (string) $snapshot->failedLast24h),
            Stat::make('Tempo medio di elaborazione', self::formatDuration($snapshot->avgProcessingSeconds)),
            Stat::make('Bounce rate', self::formatPercentage($snapshot->bounceRate)),
        ];
    }

    private static function formatDuration(?float $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        return $seconds < 60
            ? sprintf('%ds', (int) round($seconds))
            : sprintf('%dm %ds', (int) ($seconds / 60), (int) round($seconds) % 60);
    }

    private static function formatPercentage(?float $ratio): string
    {
        return $ratio === null ? '—' : sprintf('%.1f%%', $ratio * 100);
    }
}
