<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Mailables\TicketStatusChangedMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Events\TicketStatusChanged;
use App\Domain\Ticketing\Models\Ticket;

/**
 * E4 (§7.5.2 del PRD, US-313): notifica ogni destinatario rilevante del ticket
 * (richiedente, assegnatario, tester, partecipanti) di un cambio di stato,
 * ESCLUSO chi ha eseguito l'azione — {@see Ticket::messageRecipients()}
 * (US-106) applica già questa esclusione, sostituto inline della tabella
 * attore×transizione generale di US-318 (non ancora implementata: questa
 * story usa solo la regola "nessuno riceve la notifica di un'azione che ha
 * eseguito lui stesso", US-318 la generalizzerà per il resto del catalogo).
 */
final class SendTicketStatusChangedMail
{
    public static function run(TicketStatusChanged $event): void
    {
        $ticket = $event->ticket;

        foreach ($ticket->messageRecipients($event->actor) as $recipient) {
            $recipientIsCustomer = $recipient->hasRole(UserRole::Customer->value);

            SendOutboundTicketMail::run(
                ticket: $ticket,
                recipient: $recipient,
                notificationType: NotificationType::TicketStatusChanged,
                subject: "[#{$ticket->id}] Stato aggiornato: {$event->to->getLabel()}",
                mailableClass: TicketStatusChangedMail::class,
                mailableFactory: fn (EmailMessage $outbound): TicketStatusChangedMail => new TicketStatusChangedMail(
                    ticket: $ticket,
                    previousStatus: $event->from,
                    newStatus: $event->to,
                    recipientIsCustomer: $recipientIsCustomer,
                    outbound: $outbound,
                ),
            );
        }
    }
}
