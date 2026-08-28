<?php

declare(strict_types=1);

namespace App\Domain\Mail\Mailables;

use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Support\TicketDigestEntry;
use App\Filament\Resources\Tickets\TicketResource;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Support\Collection;

/**
 * E8 (§7.5.2 del PRD, US-614): riepilogo giornaliero dell'attività su più
 * ticket dello stesso cliente. Nessun `Ticket` singolo associato (come E9,
 * {@see UnknownSenderStaffMail}) — estende {@see OutboundMailable}
 * direttamente, non {@see TicketOutboundMailable}: `$entries` copre più
 * ticket in una sola email, mai un digest con un solo ticket "travestito"
 * da mail singola.
 */
final class MailDigestMail extends OutboundMailable
{
    /**
     * @param  Collection<int, TicketDigestEntry>  $entries
     */
    public function __construct(
        public readonly Collection $entries,
        EmailMessage $outbound,
    ) {
        parent::__construct($outbound);
    }

    public function content(): Content
    {
        $rows = $this->entries->map(fn (TicketDigestEntry $entry): array => [
            'entry' => $entry,
            'url' => TicketResource::getUrl('view', ['record' => $entry->ticket]),
        ]);

        return new Content(
            view: 'emails.mail-digest',
            text: 'emails.mail-digest-text',
            with: [
                'rows' => $rows,
            ],
        );
    }
}
