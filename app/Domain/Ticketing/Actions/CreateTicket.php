<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Events\TicketCreated;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use Illuminate\Support\Facades\DB;

/**
 * Unico punto di ingresso per la creazione di un ticket (A1 del PRD): mai un hook
 * Eloquent. Forza lo stato iniziale `new` indipendentemente da cosa contiene
 * `$attributes`, scrive il `ticket_log` `created` ed emette `TicketCreated`.
 */
final class CreateTicket
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function run(array $attributes, User $user): Ticket
    {
        return DB::transaction(function () use ($attributes, $user): Ticket {
            $ticket = Ticket::create([
                ...$attributes,
                'status' => TicketStatus::New,
                'status_changed_at' => now(),
            ]);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'event' => TicketLogEvent::Created,
                'is_system' => $user->isSystem(),
                'occurred_at' => now(),
            ]);

            event(new TicketCreated($ticket));

            return $ticket;
        });
    }
}
