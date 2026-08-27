<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use App\Domain\Ticketing\StateMachine\TicketStateMachine;
use Illuminate\Support\Facades\DB;

/**
 * Unico punto di ingresso per valorizzare `archived_at` (US-611, §10.2, comando
 * `tickets:archive-scrum`): non è un cambio di `status` (non passa da
 * {@see TicketStateMachine}), solo un flag
 * additivo — nessuna cancellazione, nessuna mutazione dello stato del ticket.
 * Scrive sempre un `ticket_log` (v1 gotcha esplicito nel PRD §10.1: il comando
 * v1 equivalente mutava senza lasciare traccia, falsando ore lavorate e storico —
 * qui non si ripete).
 */
final class ArchiveTicket
{
    public static function run(Ticket $ticket, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $actor): Ticket {
            $ticket->archived_at = now();
            $ticket->save();

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $actor->id,
                'event' => TicketLogEvent::Archived,
                'is_system' => $actor->isSystem(),
                'occurred_at' => now(),
            ]);

            return $ticket;
        });
    }
}
