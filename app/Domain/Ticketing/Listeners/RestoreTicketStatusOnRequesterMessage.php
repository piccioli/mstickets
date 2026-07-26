<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Listeners;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Actions\PostTicketMessage;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Events\TicketMessagePosted;

/**
 * Regola T7 (§6.1.5, decisione Q14, semplificata rispetto al v1): quando il
 * RICHIEDENTE posta un messaggio, se lo stato corrente è `waiting` il ticket torna a
 * `previous_status`; altrimenti, se il ticket era in uno stato che indica attesa di
 * risposta del cliente (`assigned`/`progress`), passa a `todo`. Implementato come
 * listener dell'evento `TicketMessagePosted`, non come logica dentro
 * {@see PostTicketMessage} (A1 del PRD): la transizione
 * passa comunque da {@see ChangeTicketStatus}, mai una scrittura diretta di `status`,
 * attribuita all'utente di sistema (§6.2.1).
 */
final class RestoreTicketStatusOnRequesterMessage
{
    public function handle(TicketMessagePosted $event): void
    {
        $ticket = $event->ticket;
        $message = $event->message;

        if ($ticket->requester_id === null || $message->author_id !== $ticket->requester_id) {
            return;
        }

        $system = User::system();

        if ($ticket->status === TicketStatus::Waiting && $ticket->previous_status !== null) {
            ChangeTicketStatus::run($ticket, $ticket->previous_status, $system);

            return;
        }

        if (in_array($ticket->status, [TicketStatus::Assigned, TicketStatus::Progress], strict: true)) {
            ChangeTicketStatus::run($ticket, TicketStatus::Todo, $system);
        }
    }
}
