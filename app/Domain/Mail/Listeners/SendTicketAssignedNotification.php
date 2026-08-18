<?php

declare(strict_types=1);

namespace App\Domain\Mail\Listeners;

use App\Domain\Mail\Actions\SendTicketAssignedMail;
use App\Domain\Ticketing\Events\TicketAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * E6 (§7.5.2 del PRD, US-315): trigger "assegnatario del ticket cambiato",
 * per qualunque via `assignee_id` sia stato valorizzato (US-110). Implementa
 * ShouldQueue perché l'evento è dispatchato sincronamente, stesso principio
 * già applicato agli altri listener del catalogo E1-E5/E9.
 */
final class SendTicketAssignedNotification implements ShouldQueue
{
    public function handle(TicketAssigned $event): void
    {
        SendTicketAssignedMail::run($event->ticket, $event->assigneeId, asTester: false, actor: $event->actor);
    }
}
