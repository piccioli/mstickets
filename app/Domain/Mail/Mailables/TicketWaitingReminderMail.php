<?php

declare(strict_types=1);

namespace App\Domain\Mail\Mailables;

use App\Filament\Resources\Tickets\TicketResource;
use Illuminate\Mail\Mailables\Content;

/**
 * E7 (§7.5.2 del PRD, US-316): promemoria al richiedente quando il suo
 * ticket `status=waiting` non ha attività rilevante da almeno N giorni
 * lavorativi (comando `tickets:remind-waiting`, mai inviata da un
 * evento/listener come il resto del catalogo).
 */
final class TicketWaitingReminderMail extends TicketOutboundMailable
{
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-waiting-reminder',
            text: 'emails.ticket-waiting-reminder-text',
            with: [
                'ticket' => $this->ticket,
                'portalUrl' => TicketResource::getUrl('view', ['record' => $this->ticket]),
            ],
        );
    }
}
