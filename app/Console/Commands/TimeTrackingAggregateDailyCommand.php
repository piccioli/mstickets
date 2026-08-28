<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use App\Domain\TimeTracking\Actions\RecalculateWorkedTime;
use App\Domain\TimeTracking\WorkedTimeCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * §10.2 del PRD, US-613: colma un gap esplicito del v1 ("il job esiste ma non
 * ha alcuna cadenza schedulata") consolidando ogni sera `ticket_work_logs` per
 * i ticket che hanno avuto attività (un `ticket_log`) nella giornata — dietro
 * `config('orchestrator.features.timetracking_aggregate')`. Nessuna nuova
 * logica di calcolo: delega interamente a {@see RecalculateWorkedTime}, la
 * stessa azione già usata da `timetracking:recalculate` (Fase 1), che ricalcola
 * per intero `tickets.worked_minutes`/`ticket_work_logs` del ticket a partire
 * da {@see WorkedTimeCalculator} — idempotente per
 * costruzione (ricrea da zero le righe del ticket ad ogni esecuzione, mai un
 * duplicato per via del vincolo unique su `work_date`/`user_id`/`ticket_id`).
 * Rispetta §10.1: `--dry-run` non scrive nulla, log strutturato.
 */
final class TimeTrackingAggregateDailyCommand extends Command
{
    protected $signature = 'timetracking:aggregate-daily
        {--dry-run : Esamina i ticket con attività nella giornata senza consolidarli}';

    protected $description = 'Consolida ticket_work_logs per la giornata odierna (§10.2 del PRD).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $startedAt = now();
        $today = CarbonImmutable::today();

        Log::info('timetracking.aggregate_daily.started', [
            'dry_run' => $dryRun,
            'date' => $today->toDateString(),
        ]);

        $ticketIds = TicketLog::query()
            ->whereBetween('occurred_at', [$today->startOfDay(), $today->endOfDay()])
            ->distinct()
            ->pluck('ticket_id');

        $examined = 0;
        $aggregated = 0;

        Ticket::query()
            ->whereIn('id', $ticketIds)
            ->orderBy('id')
            ->chunkById(100, function ($tickets) use (&$examined, &$aggregated, $dryRun): void {
                /** @var Ticket $ticket */
                foreach ($tickets as $ticket) {
                    $examined++;

                    if ($dryRun) {
                        $this->line("[dry-run] ticket #{$ticket->id}: da consolidare.");

                        continue;
                    }

                    RecalculateWorkedTime::run($ticket);
                    $aggregated++;
                }
            });

        $durationMs = $startedAt->diffInMilliseconds(now());

        Log::info('timetracking.aggregate_daily.finished', [
            'dry_run' => $dryRun,
            'date' => $today->toDateString(),
            'examined' => $examined,
            'aggregated' => $aggregated,
            'duration_ms' => $durationMs,
        ]);

        $this->info(sprintf(
            'Ticket con attività oggi esaminati: %d. Consolidati: %d.',
            $examined,
            $aggregated,
        ));

        return self::SUCCESS;
    }
}
