<?php

declare(strict_types=1);

namespace App\Domain\Mail\Mailables;

use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Models\Ticket;

/**
 * Base per ogni Mailable del catalogo E1-E9 legato a un ticket reale
 * (§7.5.1, US-311): aggiunge il `Ticket` a {@see OutboundMailable}. Un
 * Mailable senza ticket (es. E9, mittente non identificato) estende
 * direttamente `OutboundMailable`, non questa classe.
 */
abstract class TicketOutboundMailable extends OutboundMailable
{
    public function __construct(
        public readonly Ticket $ticket,
        EmailMessage $outbound,
    ) {
        parent::__construct($outbound);
    }
}
