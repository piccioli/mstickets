<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\DTO\TicketLogChanges;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Events\TicketAssigned;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use Illuminate\Support\Facades\DB;

/**
 * Unico punto di ingresso per valorizzare `assignee_id` (A1 del PRD): scrive il
 * `ticket_log` `assigned` con il DTO tipizzato {@see TicketLogChanges} ed emette
 * `TicketAssigned`. Non impone qui alcun vincolo di permesso: quello è compito della
 * `TicketPolicy` (US-105)/del chiamante, questa Action orchestra solo la mutazione.
 */
final class AssignTicket
{
    public static function run(Ticket $ticket, User $assignee, User $user): Ticket
    {
        return DB::transaction(function () use ($ticket, $assignee, $user): Ticket {
            $previousAssigneeId = $ticket->assignee_id;

            $ticket->assignee_id = $assignee->id;
            $ticket->save();

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'event' => TicketLogEvent::Assigned,
                'changes' => TicketLogChanges::assigneeChanged($previousAssigneeId, $assignee->id)->toArray(),
                'is_system' => $user->isSystem(),
                'occurred_at' => now(),
            ]);

            event(new TicketAssigned($ticket, $previousAssigneeId, $assignee->id, $user));

            return $ticket;
        });
    }
}
