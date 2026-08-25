<?php

declare(strict_types=1);

namespace App\Domain\Mail\Support;

use App\Domain\Ticketing\Enums\TicketStatus;
use App\Support\DesignTokens;

/**
 * Colori del badge di stato per le email (US-310, §7.5.4): riusa la STESSA
 * categorizzazione semantica di {@see TicketStatus::getColor()} (info/gray/warning/
 * success/danger), mai una seconda palette. I nomi Filament non sono utilizzabili
 * direttamente in un'email (nessun tema Filament nel client di posta): ogni categoria
 * è quindi mappata alla coppia [background, border] più vicina fra quelle già estratte
 * nei token `--ms-status-*` di resources/css/theme.css (docs/design-system.md), non a
 * un hex reinventato qui.
 */
final class EmailStatusBadgePalette
{
    /**
     * @return array{background: string, border: string, text: string}
     */
    public static function colors(TicketStatus $status): array
    {
        [$background, $border] = match ($status->getColor()) {
            'info' => [DesignTokens::get('ms-status-nuovo-bg'), DesignTokens::get('ms-status-nuovo-border')],
            'gray' => [DesignTokens::get('ms-status-backlog-bg'), DesignTokens::get('ms-status-backlog-border')],
            'warning' => [DesignTokens::get('ms-status-todo-bg'), DesignTokens::get('ms-status-todo-border')],
            'success' => [DesignTokens::get('ms-status-completato-bg'), DesignTokens::get('ms-status-completato-border')],
            'danger' => [DesignTokens::get('ms-status-non-finanziato-bg'), DesignTokens::get('ms-status-non-finanziato-border')],
            default => [DesignTokens::get('ms-status-backlog-bg'), DesignTokens::get('ms-status-backlog-border')],
        };

        return [
            'background' => $background,
            'border' => $border,
            'text' => DesignTokens::get('ms-text-badge'),
        ];
    }
}
