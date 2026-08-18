<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Mail\Actions\ApplyInboundEmail;
use App\Domain\Mail\Actions\ClassifyInboundEmail;
use App\Domain\Mail\Actions\ParseInboundEmail;
use App\Domain\Mail\Actions\ProcessDeliveryStatusNotification;
use App\Domain\Mail\Actions\StoreRawInboundEmail;
use App\Domain\Mail\Contracts\InboundMailTransport;
use App\Domain\Mail\Enums\EmailDiscardReason;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Scarica fino a `--limit` messaggi da INBOX, li archivia grezzi PRIMA di
 * qualunque parsing (§7.3.3 del PRD, US-302: mai "tutti gli unseen", rischio
 * OOM già noto del v1) e orchestra su ciascun messaggio appena archiviato
 * l'intera pipeline sincrona parse → classify → (DSN | apply) (US-326,
 * checkpoint di fine fase): `ParseInboundEmail` (US-303), `ClassifyInboundEmail`
 * (US-304), poi — a seconda dell'esito della classificazione —
 * `ProcessDeliveryStatusNotification` (US-319) per un DSN, oppure
 * `ApplyInboundEmail` (US-307, che a sua volta chiama `ResolveEmailSender`/
 * US-305 e `ResolveEmailThread`/US-306) per un messaggio classificato o
 * quarantenato. Un messaggio già presente (stesso `imap_folder`/`imap_uid`,
 * §US-302) è saltato PRIMA di questa pipeline: non viene mai riprocessato.
 * Ogni Action della pipeline gestisce già da sé i propri errori (mai
 * un'eccezione propagata fuori, `status = failed` in caso di fallimento):
 * un messaggio che fallisce non impedisce l'elaborazione dei successivi.
 */
final class MailFetchInboundCommand extends Command
{
    protected $signature = 'mail:fetch-inbound
        {--limit= : Numero massimo di messaggi da leggere (default config(\'mail_pipeline.fetch.default_limit\'))}
        {--since= : Legge solo i messaggi arrivati da questa data in poi (formato accettato da Carbon::parse)}';

    protected $description = 'Scarica e archivia grezze le nuove email da INBOX (§7.3.3 del PRD).';

    public function __construct(private readonly InboundMailTransport $transport)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        set_time_limit((int) config('mail_pipeline.fetch.timeout'));

        $lock = Cache::lock('mail:fetch-inbound', (int) config('mail_pipeline.fetch.lock_seconds'));

        if (! $lock->get()) {
            $this->warn('mail:fetch-inbound è già in esecuzione altrove: esecuzione saltata.');

            return self::SUCCESS;
        }

        try {
            return $this->fetchAndStore();
        } finally {
            $this->transport->disconnect();
            $lock->release();
        }
    }

    private function fetchAndStore(): int
    {
        $limit = $this->option('limit') !== null
            ? (int) $this->option('limit')
            : (int) config('mail_pipeline.fetch.default_limit');

        $since = $this->option('since') !== null
            ? Carbon::parse($this->option('since'))
            : null;

        try {
            $messages = retry(
                (int) config('mail_pipeline.fetch.tries'),
                fn () => $this->transport->fetch($limit, $since),
                fn (int $attempt): int => $attempt * 1000,
            );
        } catch (Throwable $exception) {
            $this->error("Impossibile leggere INBOX via IMAP: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $stored = 0;
        $skipped = 0;

        foreach ($messages as $raw) {
            $emailMessage = StoreRawInboundEmail::run($raw);

            if ($emailMessage === null) {
                $skipped++;

                continue;
            }

            $stored++;
            $this->processPipeline($emailMessage);
        }

        $this->info(sprintf(
            'Letti %d messaggi: %d archiviati, %d già presenti (saltati).',
            count($messages),
            $stored,
            $skipped,
        ));

        return self::SUCCESS;
    }

    private function processPipeline(EmailMessage $emailMessage): void
    {
        $emailMessage = ParseInboundEmail::run($emailMessage);

        if ($emailMessage->status !== EmailStatus::Parsed) {
            return;
        }

        $emailMessage = ClassifyInboundEmail::run($emailMessage);

        if ($emailMessage->status === EmailStatus::Discarded) {
            if ($emailMessage->failure_reason === EmailDiscardReason::DeliveryStatusNotification->value) {
                ProcessDeliveryStatusNotification::run($emailMessage);
            }

            return;
        }

        if (in_array($emailMessage->status, [EmailStatus::Classified, EmailStatus::Quarantined], true)) {
            ApplyInboundEmail::run($emailMessage);
        }
    }
}
