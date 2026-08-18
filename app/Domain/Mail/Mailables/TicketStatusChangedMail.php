<?php

declare(strict_types=1);

namespace App\Domain\Mail\Mailables;

use App\Domain\Mail\Actions\SendTicketStatusChangedMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Filament\Resources\Tickets\TicketResource;
use Illuminate\Mail\Mailables\Content;

/**
 * E4 (§7.5.2 del PRD, US-313, corregge il problema 11: nel v1 `$recipient->role`
 * non esiste e viene sempre mandato lo stesso template). Un'istanza è costruita
 * per singolo destinatario da {@see SendTicketStatusChangedMail},
 * che decide `$recipientIsCustomer` in base al ruolo REALE del destinatario
 * (`hasRole()`), mai un attributo inesistente sull'utente.
 */
final class TicketStatusChangedMail extends TicketOutboundMailable
{
    public function __construct(
        Ticket $ticket,
        public readonly TicketStatus $previousStatus,
        public readonly TicketStatus $newStatus,
        public readonly bool $recipientIsCustomer,
        EmailMessage $outbound,
    ) {
        parent::__construct($ticket, $outbound);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-status-changed',
            text: 'emails.ticket-status-changed-text',
            with: [
                'ticket' => $this->ticket,
                'previousStatus' => $this->previousStatus,
                'newStatus' => $this->newStatus,
                'recipientIsCustomer' => $this->recipientIsCustomer,
                'portalUrl' => TicketResource::getUrl('view', ['record' => $this->ticket]),
            ],
        );
    }
}
