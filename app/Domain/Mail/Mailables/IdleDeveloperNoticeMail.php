<?php

declare(strict_types=1);

namespace App\Domain\Mail\Mailables;

use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Models\Ticket;
use App\Filament\Resources\Tickets\TicketResource;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Support\Collection;

/**
 * E11 (§7.5.2 del PRD, US-616): promemoria interno per un developer con
 * ticket assegnati ma nessuno `status = progress`. Nessun `Ticket` singolo
 * associato (come E8/E9) — estende {@see OutboundMailable} direttamente,
 * mai {@see TicketOutboundMailable}: `$tickets` elenca tutti i ticket in
 * coda del destinatario in una sola email.
 */
final class IdleDeveloperNoticeMail extends OutboundMailable
{
    /**
     * @param  Collection<int, Ticket>  $tickets
     */
    public function __construct(
        public readonly Collection $tickets,
        EmailMessage $outbound,
    ) {
        parent::__construct($outbound);
    }

    public function content(): Content
    {
        $rows = $this->tickets->map(fn (Ticket $ticket): array => [
            'ticket' => $ticket,
            'url' => TicketResource::getUrl('view', ['record' => $ticket]),
        ]);

        return new Content(
            view: 'emails.idle-developer-notice',
            text: 'emails.idle-developer-notice-text',
            with: [
                'rows' => $rows,
            ],
        );
    }
}
