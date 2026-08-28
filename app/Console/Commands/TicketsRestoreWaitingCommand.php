<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * T6 (§6.1.5/§10.2 del PRD): fa uscire automaticamente dallo stato `waiting` un
 * ticket rimasto lì da almeno `config('ticketing.restore_waiting.threshold_days')`
 * giorni DI CALENDARIO (esplicito nel PRD, a differenza di T3/T4 che usano giorni
 * lavorativi) — schedulato giornalmente, dietro
 * `config('orchestrator.features.tickets_restore_waiting')`. La soglia si misura su
 * `status_changed_at`, valorizzato da {@see ChangeTicketStatus} nel momento stesso in
 * cui il ticket è entrato in `waiting`.
 * Delega interamente a {@see ChangeTicketStatus} — mai un update diretto sulla
 * colonna `status` — con {@see User::system()} come attore: la riga `waiting → null`
 * di `TicketStateMachine` risolve già il target su `previous_status` del singolo
 * ticket ed è già `System`-abilitata (nessuna modifica alla state machine richiesta
 * da questa story). Il ripristino risulta annotato nella conversazione del ticket
 * "gratis" tramite il `ticket_log` che `ChangeTicketStatus` scrive comunque (mostrato
 * nella timeline di `TicketInfolist`), mai nella `description` (correzione esplicita
 * rispetto al v1, §10.2).
 * Rispetta §10.1: `--dry-run` non scrive nulla, log strutturato, idempotente per
 * costruzione (un ticket già ripristinato non è più `waiting`, quindi non più
 * selezionato dalla query alla ri-esecuzione).
 */
final class TicketsRestoreWaitingCommand extends Command
{
    protected $signature = 'tickets:restore-waiting
        {--dry-run : Esamina i ticket "waiting" ripristinabili senza ripristinarli}';

    protected $description = 'Ripristina allo stato precedente i ticket "waiting" da troppi giorni (§10.2 del PRD).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $startedAt = now();
        $actor = User::system();
        $thresholdDays = (int) config('ticketing.restore_waiting.threshold_days');

        Log::info('tickets.restore_waiting.started', [
            'dry_run' => $dryRun,
            'threshold_days' => $thresholdDays,
        ]);

        $tickets = Ticket::query()
            ->where('status', TicketStatus::Waiting)
            ->whereNotNull('previous_status')
            ->where('status_changed_at', '<=', now()->subDays($thresholdDays))
            ->get();

        $examined = 0;
        $restored = 0;
        $errors = 0;

        foreach ($tickets as $ticket) {
            $examined++;
            $previousStatus = $ticket->previous_status;

            if ($dryRun) {
                $restored++;
                $this->line("[dry-run] ticket #{$ticket->id} \"{$ticket->title}\": da ripristinare a \"{$previousStatus->getLabel()}\".");

                continue;
            }

            try {
                ChangeTicketStatus::run($ticket, $previousStatus, $actor);
                $restored++;
                $this->info("ticket #{$ticket->id}: ripristinato a \"{$previousStatus->getLabel()}\".");
            } catch (ValidationException $exception) {
                $errors++;
                Log::warning('tickets.restore_waiting.item_failed', [
                    'ticket_id' => $ticket->id,
                    'error' => $exception->getMessage(),
                ]);
                $this->warn("ticket #{$ticket->id}: transizione fallita — {$exception->getMessage()}");
            }
        }

        $durationMs = $startedAt->diffInMilliseconds(now());

        Log::info('tickets.restore_waiting.finished', [
            'dry_run' => $dryRun,
            'examined' => $examined,
            'restored' => $restored,
            'errors' => $errors,
            'duration_ms' => $durationMs,
        ]);

        $this->info(sprintf(
            'Ticket "waiting" esaminati: %d. Ripristinati: %d. Errori: %d.',
            $examined,
            $restored,
            $errors,
        ));

        return self::SUCCESS;
    }
}
