<?php

declare(strict_types=1);

namespace App\Domain\Mail\Mailables;

use App\Domain\Mail\Actions\SendTicketAssignedMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Actions\AssignTicket;
use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Filament\Resources\Tickets\TicketResource;
use Illuminate\Mail\Mailables\Content;

/**
 * E6 (§7.5.2 del PRD, US-315): notifica al nuovo assegnatario (developer) o
 * tester quando {@see AssignTicket} o il
 * context di {@see ChangeTicketStatus} valorizza
 * `assignee_id`/`tester_id`. Un'istanza è costruita da
 * {@see SendTicketAssignedMail}, che decide `$asTester`
 * in base a QUALE campo è cambiato, non al ruolo dell'utente.
 */
final class TicketAssignedMail extends TicketOutboundMailable
{
    public function __construct(
        Ticket $ticket,
        public readonly bool $asTester,
        EmailMessage $outbound,
    ) {
        parent::__construct($ticket, $outbound);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-assigned',
            text: 'emails.ticket-assigned-text',
            with: [
                'ticket' => $this->ticket,
                'asTester' => $this->asTester,
                'portalUrl' => TicketResource::getUrl('view', ['record' => $this->ticket]),
            ],
        );
    }
}
