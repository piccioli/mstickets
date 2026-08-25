<?php

declare(strict_types=1);

namespace App\Domain\Mail\Mailables;

use App\Filament\Resources\Tickets\TicketResource;
use Illuminate\Mail\Mailables\Content;

/**
 * E2 (§7.5.2 del PRD, US-311, **nuovo**: il v1 non la manda): conferma al
 * richiedente che il ticket aperto dal pannello web è stato registrato.
 * Inviata solo quando il ticket è stato creato con `TicketMessageChannel::Web`
 * (§7.3.7/US-307): quello creato dalla pipeline email genera E1, non questa.
 */
final class TicketOpenedFromWebMail extends TicketOutboundMailable
{
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-opened-from-web',
            text: 'emails.ticket-opened-from-web-text',
            with: [
                'ticket' => $this->ticket,
                'portalUrl' => TicketResource::getUrl('view', ['record' => $this->ticket]),
            ],
        );
    }
}
