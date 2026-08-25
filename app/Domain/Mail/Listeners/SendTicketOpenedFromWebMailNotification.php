<?php

declare(strict_types=1);

namespace App\Domain\Mail\Listeners;

use App\Domain\Mail\Actions\SendOutboundTicketMail;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Mailables\TicketOpenedFromWebMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Events\TicketCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * E2 (§7.5.2 del PRD, US-311, **nuovo**: il v1 non la manda): conferma al
 * richiedente quando apre un ticket dal pannello web. `TicketCreated` è
 * emesso anche dalla pipeline email (US-307), che forza il canale a
 * `TicketMessageChannel::Email`: quel caso genera E1
 * ({@see SendTicketReceivedByEmailNotification}, agganciato a
 * `InboundEmailApplied`), mai questa notifica.
 */
final class SendTicketOpenedFromWebMailNotification implements ShouldQueue
{
    public function handle(TicketCreated $event): void
    {
        if ($event->channel !== TicketMessageChannel::Web) {
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
            notificationType: NotificationType::TicketOpenedFromWeb,
            subject: "[#{$ticket->id}] {$ticket->title}",
            mailableClass: TicketOpenedFromWebMail::class,
            mailableFactory: fn (EmailMessage $outbound): TicketOpenedFromWebMail => new TicketOpenedFromWebMail($ticket, $outbound),
        );
    }
}
