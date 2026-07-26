<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Support;

use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Models\TicketLog;

/**
 * Unica fonte del testo leggibile per un `TicketLog` (§13.4 "diff leggibile"):
 * estratto da `TicketInfolist` (US-110) per essere riusato anche dalla sezione
 * "attività recenti" della vista di lavoro (US-113), senza duplicare il match
 * degli eventi in due punti.
 */
final class TicketLogFormatter
{
    /**
     * Un cambio di stato legge sempre `from_status`/`to_status` (già enum castati),
     * mai il JSON `changes`: gli altri eventi leggono da `changes`
     * (`TicketLogChanges`, US-103) e/o dall'etichetta dell'evento stesso.
     */
    public static function describe(TicketLog $log): string
    {
        if ($log->event === TicketLogEvent::StatusChanged) {
            $from = $log->from_status?->getLabel() ?? '—';
            $to = $log->to_status?->getLabel() ?? '—';

            return "{$from} → {$to}";
        }

        /** @var array<string, mixed> $changes */
        $changes = $log->changes ?? [];

        return match ($log->event) {
            TicketLogEvent::Assigned => self::describeAssigneeChange($changes),
            TicketLogEvent::AttachmentAdded => 'Aggiunto: '.(string) ($changes['attachment']['file_name'] ?? ''),
            TicketLogEvent::AttachmentRemoved => 'Rimosso: '.(string) ($changes['attachment']['file_name'] ?? ''),
            default => $log->event->getLabel(),
        };
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private static function describeAssigneeChange(array $changes): string
    {
        $from = $changes['assignee_id']['from'] ?? null;
        $to = $changes['assignee_id']['to'] ?? null;

        return sprintf('Assegnatario: %s → %s', $from ?? '—', $to ?? '—');
    }
}
