<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Services;

use App\Domain\Reporting\Actions\CreateActivityReport;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Models\ActivityReport;
use App\Domain\Ticketing\Models\Ticket;

/**
 * Unico punto che determina quali ticket appartengono a un ActivityReport per
 * il suo periodo (US-408, §6.5.2): service esplicito, mai un Observer su
 * created/updated — va invocato dall'azione di creazione/aggiornamento
 * ({@see CreateActivityReport}) e dal comando di generazione (US-410).
 * `sync()` rende l'operazione idempotente: invocare `syncTickets()` più volte
 * di seguito sullo stesso report produce sempre lo stesso insieme di ticket
 * collegati, senza duplicati né side-effect aggiuntivi.
 */
final class ActivityReportSyncService
{
    public function syncTickets(ActivityReport $report): void
    {
        $owner = match ($report->owner_kind) {
            ActivityReportOwnerKind::User => $report->ownerUser,
            ActivityReportOwnerKind::Organization => $report->ownerOrganization,
        };

        if ($owner === null) {
            $report->tickets()->detach();

            return;
        }

        $start = $report->periodStart();
        $end = $report->periodEnd();

        $ticketIds = match ($report->owner_kind) {
            ActivityReportOwnerKind::User => Ticket::query()
                ->where('requester_id', $owner->id)
                ->whereBetween('done_at', [$start, $end])
                ->pluck('id'),
            ActivityReportOwnerKind::Organization => Ticket::query()
                ->whereHas(
                    'requester.organizations',
                    fn ($query) => $query->where('organizations.id', $owner->id)
                )
                ->whereBetween('done_at', [$start, $end])
                ->pluck('id'),
        };

        $report->tickets()->sync($ticketIds);
    }
}
