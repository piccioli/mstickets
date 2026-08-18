<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Events\TicketStatusChanged;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use App\Domain\Ticketing\StateMachine\TicketStateMachine;
use App\Domain\Ticketing\StateMachine\TransitionEffect;
use Illuminate\Support\Facades\DB;

/**
 * Unico punto di ingresso per un cambio di stato del ticket (A1 del PRD): invoca
 * {@see TicketStateMachine::authorize()} (US-101) — che lancia già un errore di
 * validazione localizzato per una transizione vietata, senza scrivere nulla — poi
 * esegue gli `effects` dichiarati sulla riga di tabella risolta. Tutto avviene in
 * un'unica transazione (§6.1.4): se la demozione degli altri ticket `progress`
 * dello stesso assegnatario fallisce, l'intero cambio di stato viene annullato.
 */
final class ChangeTicketStatus
{
    /**
     * @param  array<string, mixed>  $context  Valori applicati contestualmente alla
     *                                         transizione (es. `assignee_id`,
     *                                         `tester_id`, `waiting_reason`,
     *                                         `problem_reason`), già verificati dai
     *                                         guard di `TicketStateMachine::authorize()`.
     */
    public static function run(Ticket $ticket, TicketStatus $to, User $user, array $context = []): Ticket
    {
        return DB::transaction(function () use ($ticket, $to, $user, $context): Ticket {
            $transition = TicketStateMachine::authorize($ticket, $to, $user, $context);

            $from = $ticket->status;

            $ticket->fill($context);
            $ticket->status = $to;
            $ticket->status_changed_at = now();

            foreach ($transition->effects as $effect) {
                match ($effect) {
                    TransitionEffect::SavePreviousStatus => $ticket->previous_status = $from,
                    TransitionEffect::RestorePreviousStatus => $ticket->previous_status = null,
                    TransitionEffect::SetReleasedAt => $ticket->released_at = now(),
                    TransitionEffect::SetDoneAt => $ticket->done_at = now(),
                    TransitionEffect::DemoteOtherProgressTickets => null,
                };
            }

            $ticket->save();

            self::writeStatusLog($ticket, $user, $from, $to);

            if (in_array(TransitionEffect::DemoteOtherProgressTickets, $transition->effects, true)) {
                self::demoteOtherProgressTickets($ticket, $user);
            }

            event(new TicketStatusChanged($ticket, $from, $to, $user));

            return $ticket;
        });
    }

    /**
     * REGOLA §6.1.4: un solo ticket `progress` per assegnatario. Ogni demozione produce
     * il proprio `ticket_log` indipendente ed emette il proprio `TicketStatusChanged`,
     * nella STESSA transazione del cambio di stato che l'ha innescata.
     */
    private static function demoteOtherProgressTickets(Ticket $ticket, User $user): void
    {
        if ($ticket->assignee_id === null) {
            return;
        }

        Ticket::query()
            ->where('assignee_id', $ticket->assignee_id)
            ->where('id', '!=', $ticket->id)
            ->where('status', TicketStatus::Progress)
            ->get()
            ->each(function (Ticket $other) use ($user): void {
                $otherFrom = $other->status;

                $other->status = TicketStatus::Todo;
                $other->status_changed_at = now();
                $other->save();

                self::writeStatusLog($other, $user, $otherFrom, TicketStatus::Todo);

                event(new TicketStatusChanged($other, $otherFrom, TicketStatus::Todo, $user));
            });
    }

    private static function writeStatusLog(Ticket $ticket, User $user, TicketStatus $from, TicketStatus $to): void
    {
        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'event' => TicketLogEvent::StatusChanged,
            'from_status' => $from,
            'to_status' => $to,
            'is_system' => $user->isSystem(),
            'occurred_at' => now(),
        ]);
    }
}
