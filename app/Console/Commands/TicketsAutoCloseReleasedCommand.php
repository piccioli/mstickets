<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\StateMachine\TicketStateMachine;
use App\Domain\Ticketing\Support\WorkingDaysCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * T4 (§6.1.5/§10.2 del PRD, US-610): chiude automaticamente in `done` i ticket
 * `released` da almeno `config('ticketing.auto_close_released.threshold_working_days')`
 * giorni lavorativi (schedulato 07:45, dietro
 * `config('orchestrator.features.tickets_auto_close_released')`), stesso calcolo
 * giorni lavorativi di {@see WorkingDaysCalculator} già usato dal reminder E7
 * (US-316). Delega a {@see ChangeTicketStatus} — mai un update diretto sulla
 * colonna `status` — con {@see User::system()} come attore: la transizione
 * `released → done` valorizza `done_at` tramite l'effect `SetDoneAt` già
 * dichiarato in {@see TicketStateMachine}.
 * Rispetta §10.1: `--dry-run` non scrive nulla, log strutturato, idempotente per
 * costruzione (un ticket già `done` non è più selezionato dalla query alla
 * ri-esecuzione).
 */
final class TicketsAutoCloseReleasedCommand extends Command
{
    protected $signature = 'tickets:auto-close-released
        {--dry-run : Esamina i ticket "released" senza chiuderli}';

    protected $description = 'Chiude automaticamente i ticket "released" da abbastanza giorni lavorativi (§10.2 del PRD).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $startedAt = now();
        $actor = User::system();
        $thresholdWorkingDays = (int) config('ticketing.auto_close_released.threshold_working_days');

        Log::info('tickets.auto_close_released.started', [
            'dry_run' => $dryRun,
            'threshold_working_days' => $thresholdWorkingDays,
        ]);

        $tickets = Ticket::query()->where('status', TicketStatus::Released)->get();

        $examined = 0;
        $closed = 0;
        $skippedNotElapsed = 0;
        $errors = 0;

        foreach ($tickets as $ticket) {
            $examined++;

            if ($ticket->released_at === null || ! WorkingDaysCalculator::haveElapsed($ticket->released_at, $thresholdWorkingDays)) {
                $skippedNotElapsed++;

                continue;
            }

            if ($dryRun) {
                $closed++;
                $this->line("[dry-run] ticket #{$ticket->id} \"{$ticket->title}\": da chiudere in \"done\".");

                continue;
            }

            try {
                ChangeTicketStatus::run($ticket, TicketStatus::Done, $actor);
                $closed++;
                $this->info("ticket #{$ticket->id}: chiuso in \"done\".");
            } catch (ValidationException $exception) {
                $errors++;
                Log::warning('tickets.auto_close_released.item_failed', [
                    'ticket_id' => $ticket->id,
                    'error' => $exception->getMessage(),
                ]);
                $this->warn("ticket #{$ticket->id}: transizione fallita — {$exception->getMessage()}");
            }
        }

        $durationMs = $startedAt->diffInMilliseconds(now());

        Log::info('tickets.auto_close_released.finished', [
            'dry_run' => $dryRun,
            'examined' => $examined,
            'closed' => $closed,
            'skipped_not_elapsed' => $skippedNotElapsed,
            'errors' => $errors,
            'duration_ms' => $durationMs,
        ]);

        $this->info(sprintf(
            'Ticket "released" esaminati: %d. Chiusi in "done": %d. Non ancora scaduti: %d. Errori: %d.',
            $examined,
            $closed,
            $skippedNotElapsed,
            $errors,
        ));

        return self::SUCCESS;
    }
}
