<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\StateMachine\TicketStateMachine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * T3 (§6.1.5/§10.2 del PRD, US-610): riporta in `todo` tutti i ticket rimasti
 * `progress` a fine giornata (schedulato 18:00, dietro
 * `config('orchestrator.features.tickets_progress_to_todo')`). Delega interamente
 * a {@see ChangeTicketStatus} — mai un update diretto sulla colonna `status` — con
 * {@see User::system()} come attore: la transizione `progress → todo` ammette già
 * `TransitionActor::System` in {@see TicketStateMachine}.
 * Rispetta §10.1: `--dry-run` non scrive nulla, log strutturato, idempotente per
 * costruzione (un ticket già `todo` non è più selezionato dalla query alla
 * ri-esecuzione).
 */
final class TicketsProgressToTodoCommand extends Command
{
    protected $signature = 'tickets:progress-to-todo
        {--dry-run : Esamina i ticket "progress" senza transitarli a "todo"}';

    protected $description = 'Riporta in "todo" tutti i ticket rimasti "progress" a fine giornata (§10.2 del PRD).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $startedAt = now();
        $actor = User::system();

        Log::info('tickets.progress_to_todo.started', ['dry_run' => $dryRun]);

        $tickets = Ticket::query()->where('status', TicketStatus::Progress)->get();

        $transitioned = 0;
        $errors = 0;

        foreach ($tickets as $ticket) {
            if ($dryRun) {
                $transitioned++;
                $this->line("[dry-run] ticket #{$ticket->id} \"{$ticket->title}\": da transitare a \"todo\".");

                continue;
            }

            try {
                ChangeTicketStatus::run($ticket, TicketStatus::Todo, $actor);
                $transitioned++;
                $this->info("ticket #{$ticket->id}: transitato a \"todo\".");
            } catch (ValidationException $exception) {
                $errors++;
                Log::warning('tickets.progress_to_todo.item_failed', [
                    'ticket_id' => $ticket->id,
                    'error' => $exception->getMessage(),
                ]);
                $this->warn("ticket #{$ticket->id}: transizione fallita — {$exception->getMessage()}");
            }
        }

        $durationMs = $startedAt->diffInMilliseconds(now());

        Log::info('tickets.progress_to_todo.finished', [
            'dry_run' => $dryRun,
            'examined' => $tickets->count(),
            'transitioned' => $transitioned,
            'errors' => $errors,
            'duration_ms' => $durationMs,
        ]);

        $this->info(sprintf(
            'Ticket "progress" esaminati: %d. Transitati a "todo": %d. Errori: %d.',
            $tickets->count(),
            $transitioned,
            $errors,
        ));

        return self::SUCCESS;
    }
}
