<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Mailables\TicketAssignedMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Support\RecipientLocale;
use App\Domain\Ticketing\Models\Ticket;

/**
 * E6 (§7.5.2 del PRD, US-315): notifica il nuovo assegnatario/tester quando
 * `assignee_id`/`tester_id` cambia, SOLO SE l'assegnatario è diverso da chi
 * ha eseguito l'azione — nessuna auto-notifica per un'auto-assegnazione.
 */
final class SendTicketAssignedMail
{
    public static function run(Ticket $ticket, int $newUserId, bool $asTester, User $actor): void
    {
        if ($newUserId === $actor->id) {
            return;
        }

        $recipient = User::find($newUserId);

        if ($recipient === null) {
            return;
        }

        SendOutboundTicketMail::run(
            ticket: $ticket,
            recipient: $recipient,
            notificationType: NotificationType::TicketAssigned,
            subject: __('[#:id] Ticket assigned: :title', ['id' => $ticket->id, 'title' => $ticket->title], RecipientLocale::resolve($recipient)),
            mailableClass: TicketAssignedMail::class,
            mailableFactory: fn (EmailMessage $outbound): TicketAssignedMail => new TicketAssignedMail(
                ticket: $ticket,
                asTester: $asTester,
                outbound: $outbound,
            ),
        );
    }
}
