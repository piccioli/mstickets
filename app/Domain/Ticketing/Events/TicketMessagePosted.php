<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Events;

use App\Domain\Ticketing\Actions\PostTicketMessage;
use App\Domain\Ticketing\Listeners\RestoreTicketStatusOnRequesterMessage;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketMessage;

/**
 * Emesso da {@see PostTicketMessage} (§6.1.7). Nessun
 * listener in questa fase invia realmente un'email (arriva in Fase 3): l'unico
 * listener registrato è {@see RestoreTicketStatusOnRequesterMessage}
 * (regola T7, decisione Q14).
 */
final readonly class TicketMessagePosted
{
    public function __construct(
        public Ticket $ticket,
        public TicketMessage $message,
    ) {}
}
