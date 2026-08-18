<?php

declare(strict_types=1);

namespace App\Domain\Mail\Listeners;

use App\Domain\Mail\Actions\SendTicketStatusChangedMail;
use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Events\TicketStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * E4 (§7.5.2 del PRD, US-313): trigger "cambio di stato del ticket", per
 * qualunque transizione applicata da {@see ChangeTicketStatus}.
 * Implementa ShouldQueue perché l'evento è dispatchato sincronamente
 * (`ChangeTicketStatus::run()`): l'asincronia della notifica sta qui, non nel
 * dispatch, stesso principio già applicato agli altri listener E1-E3/E9.
 */
final class SendTicketStatusChangedNotification implements ShouldQueue
{
    public function handle(TicketStatusChanged $event): void
    {
        SendTicketStatusChangedMail::run($event);
    }
}
