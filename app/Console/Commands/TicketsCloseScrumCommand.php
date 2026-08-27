<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\StateMachine\TicketStateMachine;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * T5 (§6.1.5/§10.2 del PRD, US-611): chiude in `done` i ticket `type = scrum`
 * creati o aggiornati oggi (schedulato 16:00, dietro
 * `config('orchestrator.features.tickets_close_scrum')`). Delega a
 * {@see ChangeTicketStatus} — mai un update diretto sulla colonna `status` — con
 * {@see User::system()} come attore: la riga T5 dedicata di
 * {@see TicketStateMachine} ammette `* → done`
 * solo per l'attore di sistema, guardata da `type = scrum`.
 * Rispetta §10.1: `--dry-run` non scrive nulla, log strutturato, idempotente per
 * costruzione (un ticket già `done` non è più selezionato dalla query alla
 * ri-esecuzione).
 */
final class TicketsCloseScrumCommand extends Command
{
    protected $signature = 'tickets:close-scrum
        {--dry-run : Esamina i ticket scrum creati/aggiornati oggi senza chiuderli}';

    protected $description = 'Chiude in "done" i ticket di tipo "scrum" creati/aggiornati oggi (§10.2 del PRD).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $startedAt = now();
        $actor = User::system();
        $today = now()->toDateString();

        Log::info('tickets.close_scrum.started', ['dry_run' => $dryRun]);

        $tickets = Ticket::query()
            ->where('type', TicketType::Scrum)
            ->where('status', '!=', TicketStatus::Done)
            ->where(function (Builder $query) use ($today): void {
                $query->whereDate('created_at', $today)
                    ->orWhereDate('updated_at', $today);
            })
            ->get();

        $examined = 0;
        $closed = 0;
        $errors = 0;

        foreach ($tickets as $ticket) {
            $examined++;

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
                Log::warning('tickets.close_scrum.item_failed', [
                    'ticket_id' => $ticket->id,
                    'error' => $exception->getMessage(),
                ]);
                $this->warn("ticket #{$ticket->id}: transizione fallita — {$exception->getMessage()}");
            }
        }

        $durationMs = $startedAt->diffInMilliseconds(now());

        Log::info('tickets.close_scrum.finished', [
            'dry_run' => $dryRun,
            'examined' => $examined,
            'closed' => $closed,
            'errors' => $errors,
            'duration_ms' => $durationMs,
        ]);

        $this->info(sprintf(
            'Ticket "scrum" creati/aggiornati oggi esaminati: %d. Chiusi in "done": %d. Errori: %d.',
            $examined,
            $closed,
            $errors,
        ));

        return self::SUCCESS;
    }
}
