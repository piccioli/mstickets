<?php

declare(strict_types=1);

namespace App\Domain\Mail\Listeners;

use App\Domain\Mail\Actions\SendOutboundTicketMail;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Events\InboundEmailApplied;
use App\Domain\Mail\Mailables\TicketReceivedByEmailMail;
use App\Domain\Mail\Models\EmailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * E1 (§7.5.2 del PRD, US-311): conferma al mittente SOLO quando l'email
 * inbound applicata ha creato un nuovo ticket (`isNewTicket`) — un messaggio
 * che si aggancia a un ticket esistente non genera questa notifica.
 */
final class SendTicketReceivedByEmailNotification implements ShouldQueue
{
    public function handle(InboundEmailApplied $event): void
    {
        if (! $event->isNewTicket) {
            return;
        }

        $ticket = $event->ticket;
        $recipient = $ticket->requester;

        if ($recipient === null) {
            return;
        }

        SendOutboundTicketMail::run(
            ticket: $ticket,
            recipient: $recipient,
            notificationType: NotificationType::TicketReceivedByEmail,
            subject: "[#{$ticket->id}] {$ticket->title}",
            mailableClass: TicketReceivedByEmailMail::class,
            mailableFactory: fn (EmailMessage $outbound): TicketReceivedByEmailMail => new TicketReceivedByEmailMail($ticket, $outbound),
        );
    }
}
