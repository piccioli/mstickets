<?php

declare(strict_types=1);

namespace App\Domain\Mail\Listeners;

use App\Domain\Mail\Actions\SendNewCustomerTicketStaffMail;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Events\TicketCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * E3 (§7.5.2 del PRD, US-312): trigger "ticket aperto dal pannello web".
 * `TicketCreated` è emesso anche dalla pipeline email (US-307), che forza il
 * canale a `TicketMessageChannel::Email`: quel caso genera questa stessa
 * notifica via {@see NotifyStaffOfNewCustomerTicketFromEmail}, agganciato a
 * `InboundEmailApplied`, mai da qui.
 */
final class NotifyStaffOfNewCustomerTicketFromWeb implements ShouldQueue
{
    public function handle(TicketCreated $event): void
    {
        if ($event->channel !== TicketMessageChannel::Web) {
            return;
        }

        SendNewCustomerTicketStaffMail::run($event->ticket);
    }
}
