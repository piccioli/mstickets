<?php

declare(strict_types=1);

namespace App\Domain\Mail\Mailables;

use App\Domain\Mail\Actions\SendNewTicketMessageMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Models\Ticket;
use App\Filament\Resources\Tickets\TicketResource;
use DateTimeInterface;
use Illuminate\Mail\Mailables\Content;

/**
 * E5 (§7.5.2 del PRD, US-314): un nuovo messaggio PUBBLICO è stato pubblicato sul
 * ticket. Un'istanza è costruita per singolo destinatario da
 * {@see SendNewTicketMessageMail}, che si occupa già di
 * non generare MAI questo Mailable per un messaggio `visibility = internal`
 * (il guard vive nell'Action, non qui: questa classe si limita a mostrare il
 * contenuto di un messaggio che è già stato deciso pubblico).
 */
final class NewTicketMessageMail extends TicketOutboundMailable
{
    public function __construct(
        Ticket $ticket,
        public readonly string $authorName,
        public readonly string $bodyHtml,
        public readonly DateTimeInterface $occurredAt,
        EmailMessage $outbound,
    ) {
        parent::__construct($ticket, $outbound);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-ticket-message',
            text: 'emails.new-ticket-message-text',
            with: [
                'ticket' => $this->ticket,
                'authorName' => $this->authorName,
                'bodyHtml' => $this->bodyHtml,
                'occurredAt' => $this->occurredAt,
                'portalUrl' => TicketResource::getUrl('view', ['record' => $this->ticket]),
            ],
        );
    }
}
