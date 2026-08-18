<?php

declare(strict_types=1);

namespace App\Domain\Mail\Events;

use App\Domain\Mail\Actions\ApplyInboundEmail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Models\Ticket;

/**
 * Emesso da {@see ApplyInboundEmail} (§7.3.7, US-307)
 * DOPO il commit della transazione che crea/aggiorna il ticket: è il punto
 * d'ingresso per le notifiche in coda, mai dentro la transazione né in modo
 * sincrono. Nessun listener è ancora registrato in questa story (stesso
 * pattern già usato per `TicketCreated` in Fase 1): la conferma al mittente
 * (E1, US-311) e la notifica allo staff (E3, US-312) si aggancieranno qui con
 * listener `ShouldQueue`, registrati in `AppServiceProvider::boot()`.
 */
final readonly class InboundEmailApplied
{
    public function __construct(
        public Ticket $ticket,
        public EmailMessage $emailMessage,
        public bool $isNewTicket,
    ) {}
}
