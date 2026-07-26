<?php

declare(strict_types=1);

namespace App\Domain\TimeTracking\Actions;

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketWorkLog;
use App\Domain\TimeTracking\WorkedTimeCalculator;
use App\Domain\TimeTracking\WorkedTimeSegment;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Unico punto di scrittura per le ore lavorate derivate di un ticket (§6.2.2 del PRD,
 * DECISIONE Q15): ricalcola SIA `tickets.worked_minutes` SIA l'aggregato
 * `ticket_work_logs` dalla stessa {@see WorkedTimeCalculator}, in un'unica transazione.
 * Le righe di `ticket_work_logs` del ticket vengono ricreate da zero ad ogni
 * esecuzione (mai un upsert differenziale): rende il ricalcolo idempotente, che sia
 * innescato dal listener in coda o dal comando `timetracking:recalculate`.
 */
final class RecalculateWorkedTime
{
    public static function run(Ticket $ticket, ?CarbonInterface $asOf = null): void
    {
        $logs = $ticket->logs()->orderBy('occurred_at')->orderBy('id')->get();

        $segments = WorkedTimeCalculator::fromConfig()->segmentsFor($logs, $asOf);

        DB::transaction(function () use ($ticket, $segments): void {
            $ticket->forceFill([
                'worked_minutes' => array_sum(array_map(
                    static fn (WorkedTimeSegment $segment): int => $segment->minutes,
                    $segments,
                )),
            ])->save();

            TicketWorkLog::query()->where('ticket_id', $ticket->id)->delete();

            foreach ($segments as $segment) {
                TicketWorkLog::create([
                    'work_date' => $segment->workDate->toDateString(),
                    'user_id' => $segment->userId,
                    'ticket_id' => $ticket->id,
                    'minutes' => $segment->minutes,
                ]);
            }
        });
    }
}
