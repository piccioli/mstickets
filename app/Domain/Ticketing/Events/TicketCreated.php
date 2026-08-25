<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Events;

use App\Domain\Mail\Actions\ApplyInboundEmail;
use App\Domain\Ticketing\Actions\CreateTicket;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Models\Ticket;

/**
 * `$channel` distingue un ticket aperto dal pannello web (default, E2 —
 * US-311) da uno creato dalla pipeline email (`TicketMessageChannel::Email`,
 * forzato da {@see ApplyInboundEmail}, che genera E1
 * invece): entrambi i percorsi passano da {@see CreateTicket},
 * quindi emettono lo stesso evento, e serve un modo esplicito — non basato
 * sull'ordine/timing di scrittura del primo ticket_message — per capire quale
 * notifica inviare.
 */
final readonly class TicketCreated
{
    public function __construct(
        public Ticket $ticket,
        public TicketMessageChannel $channel = TicketMessageChannel::Web,
    ) {}
}
