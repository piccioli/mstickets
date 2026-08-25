<?php

declare(strict_types=1);

namespace App\Domain\Mail\Listeners;

use App\Domain\Mail\Actions\SendNewCustomerTicketStaffMail;
use App\Domain\Mail\Events\InboundEmailApplied;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * E3 (§7.5.2 del PRD, US-312): trigger "ticket aperto da un'email inbound".
 * Un'email che si aggancia a un ticket esistente (`isNewTicket = false`) non
 * genera questa notifica.
 */
final class NotifyStaffOfNewCustomerTicketFromEmail implements ShouldQueue
{
    public function handle(InboundEmailApplied $event): void
    {
        if (! $event->isNewTicket) {
            return;
        }

        SendNewCustomerTicketStaffMail::run($event->ticket);
    }
}
