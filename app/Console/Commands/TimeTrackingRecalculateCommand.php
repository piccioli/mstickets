<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\TimeTracking\Actions\RecalculateWorkedTime;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Ribuild massivo delle ore lavorate (§6.2.2 del PRD): ricalcola
 * `tickets.worked_minutes` e l'aggregato `ticket_work_logs` con la stessa
 * politica unificata di {@see RecalculateWorkedTime} (DECISIONE Q15), usata anche
 * dal listener in coda che gira ad ogni cambio di stato. `--ticket` ricalcola un
 * solo ticket e ignora `--from`/`--to`; senza `--ticket`, `--from`/`--to` filtrano
 * su `created_at` (entrambi opzionali, componibili).
 */
final class TimeTrackingRecalculateCommand extends Command
{
    protected $signature = 'timetracking:recalculate
        {--from= : Ricalcola solo i ticket con created_at >= questa data (Y-m-d)}
        {--to= : Ricalcola solo i ticket con created_at <= questa data (Y-m-d)}
        {--ticket= : Ricalcola solo il ticket con questo ID (ignora --from/--to)}';

    protected $description = 'Ricalcola tickets.worked_minutes e ticket_work_logs (§6.2.2 del PRD, politica unica, decisione Q15).';

    public function handle(): int
    {
        $query = Ticket::query();

        $ticketId = $this->option('ticket');

        if ($ticketId !== null) {
            $query->whereKey($ticketId);
        } else {
            if ($from = $this->option('from')) {
                $query->where('created_at', '>=', CarbonImmutable::parse($from)->startOfDay());
            }

            if ($to = $this->option('to')) {
                $query->where('created_at', '<=', CarbonImmutable::parse($to)->endOfDay());
            }
        }

        $count = 0;

        $query->orderBy('id')->chunkById(100, function ($tickets) use (&$count): void {
            /** @var Ticket $ticket */
            foreach ($tickets as $ticket) {
                RecalculateWorkedTime::run($ticket);
                $count++;
            }
        });

        $this->info("Ricalcolate le ore lavorate per {$count} ticket.");

        return self::SUCCESS;
    }
}
