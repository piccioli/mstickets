<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\ArchiveTicket;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * §10.2 del PRD (Q9), US-611: archivia i ticket `type = scrum` già `done` da almeno
 * `config('ticketing.archive_scrum.threshold_days')` giorni di calendario
 * (schedulato 05:00, dietro `config('orchestrator.features.tickets_archive_scrum')`).
 *
 * Comportamento v1 non recuperabile con certezza (v1dumps/orchestrator-v1-backup-
 * 20260726.tar.gz non contiene alcun comando/colonna di archiviazione — solo viste
 * Nova "Archived*" in sola lettura filtrate per `status`, nessuna mutazione): lettura
 * conservativa adottata, da confermare col committente al checkpoint US-618. Delega a
 * {@see ArchiveTicket} — mai una cancellazione, mai un cambio di `status`, solo il
 * flag `archived_at` — con {@see User::system()} come attore. Rispetta §10.1:
 * `--dry-run` non scrive nulla, log strutturato, idempotente per costruzione (un
 * ticket già archiviato non è più selezionato dalla query alla ri-esecuzione).
 */
final class TicketsArchiveScrumCommand extends Command
{
    protected $signature = 'tickets:archive-scrum
        {--dry-run : Esamina i ticket scrum archiviabili senza archiviarli}';

    protected $description = 'Archivia i ticket "scrum" chiusi da abbastanza giorni (§10.2 del PRD, Q9).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $startedAt = now();
        $actor = User::system();
        $thresholdDays = (int) config('ticketing.archive_scrum.threshold_days');

        Log::info('tickets.archive_scrum.started', [
            'dry_run' => $dryRun,
            'threshold_days' => $thresholdDays,
        ]);

        $tickets = Ticket::query()
            ->where('type', TicketType::Scrum)
            ->where('status', TicketStatus::Done)
            ->whereNull('archived_at')
            ->where('done_at', '<=', now()->subDays($thresholdDays))
            ->get();

        $examined = 0;
        $archived = 0;

        foreach ($tickets as $ticket) {
            $examined++;

            if ($dryRun) {
                $archived++;
                $this->line("[dry-run] ticket #{$ticket->id} \"{$ticket->title}\": da archiviare.");

                continue;
            }

            ArchiveTicket::run($ticket, $actor);
            $archived++;
            $this->info("ticket #{$ticket->id}: archiviato.");
        }

        $durationMs = $startedAt->diffInMilliseconds(now());

        Log::info('tickets.archive_scrum.finished', [
            'dry_run' => $dryRun,
            'examined' => $examined,
            'archived' => $archived,
            'duration_ms' => $durationMs,
        ]);

        $this->info(sprintf(
            'Ticket "scrum" chiusi esaminati: %d. Archiviati: %d.',
            $examined,
            $archived,
        ));

        return self::SUCCESS;
    }
}
