<?php

declare(strict_types=1);

namespace App\Domain\Mail\Mailables;

use App\Domain\Mail\Actions\SendOutboundTicketMail;
use App\Domain\Mail\Models\EmailMessage;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Base comune per ogni Mailable del catalogo E1-E9 (§7.5.1, requisiti
 * trasversali, US-311): retry/backoff, Message-ID esplicito e Reply-To VERP
 * letti dalla riga `email_messages` outbound già decisa da
 * {@see SendOutboundTicketMail} prima della costruzione del Mailable —
 * nessuna generazione qui, solo lettura.
 *
 * Estratta da {@see TicketOutboundMailable} in US-312 quando è comparsa la
 * prima comunicazione del catalogo senza un `Ticket` associato (E9,
 * mittente non identificato: nessun ticket esiste ancora). Un Mailable
 * legato a un ticket estende {@see TicketOutboundMailable}, uno che non lo è
 * (es. E9) estende direttamente questa classe.
 */
abstract class OutboundMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly EmailMessage $outbound,
    ) {}

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDay();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: (string) $this->outbound->subject,
            replyTo: [$this->outbound->reply_to],
        );
    }

    public function headers(): Headers
    {
        return new Headers(messageId: $this->outbound->message_id);
    }
}
