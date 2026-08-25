<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Mailables\TicketStatusChangedMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Support\NotificationRecipientResolver;
use App\Domain\Mail\Support\RecipientLocale;
use App\Domain\Ticketing\Events\TicketStatusChanged;
use App\Domain\Ticketing\Models\Ticket;

/**
 * E4 (§7.5.2 del PRD, US-313, destinatari generalizzati da US-318): notifica
 * SOLO i destinatari previsti dalla tabella esplicita "attore × transizione →
 * destinatari" di {@see NotificationRecipientResolver} (§6.1.3, colonna
 * "Effetti") — non più l'intero {@see Ticket::messageRecipients()}
 * per ogni transizione (comportamento provvisorio di US-313, causa dello
 * stesso bug v1 di email spurie su ogni salvataggio, problema 12). Una
 * transizione senza "notifica X" in tabella non invia nessuna email.
 * L'esclusione di chi ha eseguito l'azione è già applicata dal resolver.
 */
final class SendTicketStatusChangedMail
{
    public static function run(TicketStatusChanged $event): void
    {
        $ticket = $event->ticket;

        $recipients = NotificationRecipientResolver::resolve($ticket, $event->from, $event->to, $event->actor);

        foreach ($recipients as $recipient) {
            $recipientIsCustomer = $recipient->hasRole(UserRole::Customer->value);

            SendOutboundTicketMail::run(
                ticket: $ticket,
                recipient: $recipient,
                notificationType: NotificationType::TicketStatusChanged,
                subject: __('[#:id] Status updated: :status', ['id' => $ticket->id, 'status' => $event->to->getLabel()], RecipientLocale::resolve($recipient)),
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
