<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Mailables\TicketWaitingReminderMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Models\Ticket;

/**
 * E7 (§7.5.2 del PRD, US-316): invia il promemoria al richiedente di un
 * ticket `status=waiting` inattivo. Punto unico invocato da
 * `tickets:remind-waiting` — nessun evento/listener, il trigger è lo
 * scheduler, non un'azione di dominio (stesso motivo per cui esiste come
 * Action dedicata invece che codice inline nel comando: testabile in
 * isolamento senza dover passare dalla query di selezione dei ticket).
 */
final class SendTicketWaitingReminderMail
{
    public static function run(Ticket $ticket): void
    {
        $recipient = $ticket->requester;

        if ($recipient === null) {
            return;
        }

        SendOutboundTicketMail::run(
            ticket: $ticket,
            recipient: $recipient,
            notificationType: NotificationType::TicketWaitingReminder,
            subject: "[#{$ticket->id}] Promemoria: ticket in attesa - {$ticket->title}",
            mailableClass: TicketWaitingReminderMail::class,
            mailableFactory: fn (EmailMessage $outbound): TicketWaitingReminderMail => new TicketWaitingReminderMail($ticket, $outbound),
        );
    }
}
