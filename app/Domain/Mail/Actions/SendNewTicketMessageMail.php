<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Mailables\NewTicketMessageMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Support\RecipientLocale;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use App\Domain\Ticketing\Events\TicketMessagePosted;
use App\Domain\Ticketing\Models\Ticket;

/**
 * E5 (§7.5.2 del PRD, US-314): notifica ogni destinatario rilevante del ticket
 * (richiedente, assegnatario, tester, partecipanti) di un nuovo messaggio
 * PUBBLICO, ESCLUSO chi lo ha scritto — stesso schema di
 * {@see SendTicketStatusChangedMail} (E4, US-313): riusa
 * {@see Ticket::messageRecipients()} (US-106) per
 * l'esclusione dell'autore.
 *
 * Il guard "mai per un messaggio interno, nemmeno verso lo staff" vive QUI (early
 * return), non in `messageRecipients()`, che non ha nulla a che vedere con la
 * visibilità del messaggio: un messaggio `visibility = internal` non genera mai
 * questo Mailable, indipendentemente da chi sarebbe altrimenti un destinatario.
 */
final class SendNewTicketMessageMail
{
    public static function run(TicketMessagePosted $event): void
    {
        $message = $event->message;

        if ($message->visibility !== TicketMessageVisibility::Public) {
            return;
        }

        $author = $message->author;

        if ($author === null) {
            return;
        }

        $ticket = $event->ticket;

        foreach ($ticket->messageRecipients($author) as $recipient) {
            SendOutboundTicketMail::run(
                ticket: $ticket,
                recipient: $recipient,
                notificationType: NotificationType::NewTicketMessage,
                subject: __('[#:id] New message: :title', ['id' => $ticket->id, 'title' => $ticket->title], RecipientLocale::resolve($recipient)),
                mailableClass: NewTicketMessageMail::class,
                mailableFactory: fn (EmailMessage $outbound): NewTicketMessageMail => new NewTicketMessageMail(
                    ticket: $ticket,
                    authorName: $author->name,
                    bodyHtml: (string) $message->body_html,
                    occurredAt: $message->posted_at ?? now(),
                    outbound: $outbound,
                ),
            );
        }
    }
}
