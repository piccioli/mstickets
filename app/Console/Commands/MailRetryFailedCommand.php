<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\RetryOutboundEmailMessage;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Riaccoda i messaggi outbound `failed` senza doverli reinviare uno per uno
 * da UI (§7.3.3 del PRD, US-325): delega a {@see RetryOutboundEmailMessage},
 * la stessa azione già usata dal pulsante "Reinvia" dell'amministrazione
 * (US-322), quindi rispetta allo stesso modo `email_suppressions`/
 * `notification_preferences` e l'elenco dei Mailable ricostruibili. Un
 * messaggio bloccato (soppressione attiva) o non ricostruibile viene
 * segnalato e il comando passa al successivo, non si ferma mai a metà lista.
 * L'attore registrato in `email_message_logs` è {@see User::system()}: nessun
 * utente reale è collegato a un'esecuzione da CLI/scheduler.
 */
final class MailRetryFailedCommand extends Command
{
    protected $signature = 'mail:retry-failed
        {--limit= : Numero massimo di messaggi da reinviare (default config(\'mail_pipeline.retry.default_limit\')), ignorato con --email-message}
        {--email-message= : Ulid di un singolo email_messages da reinviare}';

    protected $description = 'Riaccoda i messaggi email outbound falliti, rispettando soppressioni e preferenze di notifica (§7.3.3 del PRD).';

    public function handle(): int
    {
        $actor = User::system();

        $emailMessageUlid = $this->option('email-message');

        if (is_string($emailMessageUlid) && $emailMessageUlid !== '') {
            return $this->retrySingle($emailMessageUlid, $actor);
        }

        return $this->retryBatch($actor);
    }

    private function retrySingle(string $ulid, User $actor): int
    {
        $emailMessage = EmailMessage::query()->whereRaw('lower(ulid) = ?', [mb_strtolower($ulid)])->first();

        if ($emailMessage === null) {
            $this->error("Nessun messaggio trovato con ulid {$ulid}.");

            return self::FAILURE;
        }

        return $this->retry($emailMessage, $actor) ? self::SUCCESS : self::FAILURE;
    }

    private function retryBatch(User $actor): int
    {
        $limit = $this->option('limit') !== null
            ? (int) $this->option('limit')
            : (int) config('mail_pipeline.retry.default_limit');

        $messages = EmailMessage::query()
            ->where('direction', EmailDirection::Outbound)
            ->where('status', EmailStatus::Failed)
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($messages->isEmpty()) {
            $this->info('Nessun messaggio outbound fallito da reinviare.');

            return self::SUCCESS;
        }

        $resent = 0;
        $blocked = 0;

        foreach ($messages as $emailMessage) {
            if ($this->retry($emailMessage, $actor)) {
                $resent++;
            } else {
                $blocked++;
            }
        }

        $this->info(sprintf(
            'Messaggi falliti esaminati: %d. Riaccodati: %d. Non reinviati: %d.',
            $messages->count(),
            $resent,
            $blocked,
        ));

        return self::SUCCESS;
    }

    private function retry(EmailMessage $emailMessage, User $actor): bool
    {
        try {
            $result = RetryOutboundEmailMessage::run($emailMessage, $actor);
        } catch (RuntimeException $exception) {
            $this->warn("Messaggio {$emailMessage->ulid}: {$exception->getMessage()}");

            return false;
        }

        if ($result->status !== EmailStatus::Queued) {
            $lastNote = $result->logs()->latest('occurred_at')->first()?->notes;

            $this->warn("Messaggio {$emailMessage->ulid} non reinviato: ".($lastNote ?? 'destinatario in soppressione attiva.'));

            return false;
        }

        $this->info("Messaggio {$emailMessage->ulid} riaccodato.");

        return true;
    }
}
