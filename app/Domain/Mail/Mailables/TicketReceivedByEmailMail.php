<?php

declare(strict_types=1);

namespace App\Domain\Mail\Mailables;

use App\Filament\Resources\Tickets\TicketResource;
use Illuminate\Mail\Mailables\Content;

/**
 * E1 (§7.5.2 del PRD, US-311): conferma al mittente che la sua email ha
 * aperto un nuovo ticket. Inviata SOLO per un ticket appena creato dalla
 * pipeline inbound (US-307), mai per un messaggio che si aggancia a un
 * ticket esistente — quel caso genera solo E5 (US-314), non questa.
 */
final class TicketReceivedByEmailMail extends TicketOutboundMailable
{
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-received-by-email',
            text: 'emails.ticket-received-by-email-text',
            with: [
                'ticket' => $this->ticket,
                'portalUrl' => TicketResource::getUrl('view', ['record' => $this->ticket]),
            ],
        );
    }
}
