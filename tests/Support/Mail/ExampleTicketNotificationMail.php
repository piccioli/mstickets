<?php

declare(strict_types=1);

namespace Tests\Support\Mail;

use App\Domain\Ticketing\Models\Ticket;
use DateTimeInterface;
use Illuminate\Mail\Mailable;

/**
 * Mailable di riferimento (US-310), usata solo per dimostrare/testare che il
 * layout condiviso (resources/views/emails/layouts/base.blade.php) e i
 * componenti Blade riusabili funzionano insieme in un Mailable reale, non solo
 * in un rendering di vista isolato. Le comunicazioni reali del catalogo
 * (E1-E7/E9) arrivano da US-311 in poi e avranno una propria vista/Mailable
 * dedicata che segue lo stesso pattern di questa (vedi
 * resources/views/emails/examples/ticket-notification.blade.php).
 */
final class ExampleTicketNotificationMail extends Mailable
{
    public function __construct(
        private readonly Ticket $ticket,
        private readonly string $authorName,
        private readonly DateTimeInterface $occurredAt,
        private readonly string $bodyHtml,
        private readonly string $ctaLabel,
        private readonly string $ctaUrl,
    ) {}

    public function build(): self
    {
        return $this
            ->subject("[#{$this->ticket->id}] {$this->ticket->title}")
            ->view('emails.examples.ticket-notification')
            ->text('emails.examples.ticket-notification-text')
            ->with([
                'ticket' => $this->ticket,
                'authorName' => $this->authorName,
                'occurredAt' => $this->occurredAt,
                'bodyHtml' => $this->bodyHtml,
                'ctaLabel' => $this->ctaLabel,
                'ctaUrl' => $this->ctaUrl,
            ]);
    }
}
