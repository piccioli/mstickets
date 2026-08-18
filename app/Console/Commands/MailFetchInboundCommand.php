<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Mail\Actions\StoreRawInboundEmail;
use App\Domain\Mail\Contracts\InboundMailTransport;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Scarica fino a `--limit` messaggi da INBOX e li archivia grezzi PRIMA di
 * qualunque parsing (§7.3.3 del PRD, US-302): mai "tutti gli unseen", rischio
 * OOM già noto del v1. Si ferma alla riga `email_messages` con
 * `status=received`: parsing (US-303), classificazione anti-loop (US-304) e
 * applicazione sul ticket (US-307) arrivano nelle story successive.
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
            if (StoreRawInboundEmail::run($raw) === null) {
                $skipped++;

                continue;
            }

            $stored++;
        }

        $this->info(sprintf(
            'Letti %d messaggi: %d archiviati, %d già presenti (saltati).',
            count($messages),
            $stored,
            $skipped,
        ));

        return self::SUCCESS;
    }
}
