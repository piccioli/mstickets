<?php

declare(strict_types=1);

namespace App\Domain\Mail\Mailables;

use App\Domain\Mail\Models\EmailMessage;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Support\Str;

/**
 * E9 (§7.3.8/§7.5.2 del PRD, US-308/US-312): notifica al gruppo staff quando
 * un messaggio va in quarantena (mittente non identificato). Nessun `Ticket`
 * esiste ancora a questo punto — estende {@see OutboundMailable} direttamente,
 * non {@see TicketOutboundMailable}. `$quarantinedMessage` è il messaggio
 * inbound originale (mittente/subject/corpo), distinto da `$outbound` (la
 * riga `email_messages` di QUESTA notifica staff).
 */
final class UnknownSenderStaffMail extends OutboundMailable
{
    public function __construct(
        public readonly EmailMessage $quarantinedMessage,
        EmailMessage $outbound,
    ) {
        parent::__construct($outbound);
    }

    public function content(): Content
    {
        $reviewUrl = self::reviewUrl($this->quarantinedMessage);

        return new Content(
            view: 'emails.unknown-sender-staff',
            text: 'emails.unknown-sender-staff-text',
            with: [
                'fromEmail' => $this->quarantinedMessage->from_email,
                'subject' => (string) $this->quarantinedMessage->subject,
                'bodyExcerpt' => Str::limit((string) $this->quarantinedMessage->body_text, 400),
                'reviewUrl' => $reviewUrl,
            ],
        );
    }

    public static function reviewUrl(EmailMessage $quarantinedMessage): ?string
    {
        $base = (string) config('mail_pipeline.quarantine_review_url');

        if ($base === '') {
            return null;
        }

        return rtrim($base, '/').'/'.$quarantinedMessage->ulid;
    }
}
