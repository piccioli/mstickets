<?php

declare(strict_types=1);

namespace App\Domain\Mail\Listeners;

use App\Domain\Mail\Actions\SendTicketAssignedMail;
use App\Domain\Ticketing\Events\TicketTesterAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * E6 (§7.5.2 del PRD, US-315): trigger "tester del ticket cambiato", per
 * qualunque via `tester_id` sia stato valorizzato (US-110).
 */
final class SendTicketTesterAssignedNotification implements ShouldQueue
{
    public function handle(TicketTesterAssigned $event): void
    {
        SendTicketAssignedMail::run($event->ticket, $event->testerId, asTester: true, actor: $event->actor);
    }
}
