<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Actions\ScrapeCaiSection;
use App\Domain\CaiDirectory\Enums\CaiRuntsPresenceStatus;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Support\CaiRuntsScraperClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Verifica leggera di presenza RUNTS (Fase 9): per ogni `CaiSection` con `tax_code` O `vat_number`
 * valorizzato (in Italia i due numeri coincidono spesso per gli enti, `tax_code` preferito quando
 * entrambi sono presenti — nessuna sezione con entrambi nulli viene interrogata, non c'è nulla da
 * cercare su RUNTS), chiama {@see CaiRuntsScraperClient::checkEntityExists()} (SOLO ricerca, mai lo
 * scrape completo di {@see ScrapeCaiSection}/`cai:sync-national`)
 * e scrive `runts_presence_status`/`runts_presence_checked_at`. Un timeout della verifica
 * ({@see ConnectionException}, l'esito più comune con `--timeout` basso — una sezione "trovata"
 * risponde in pochi secondi, una non trovata/ambigua richiede molto di più) è un esito ESPLICITO
 * ({@see CaiRuntsPresenceStatus::Timeout}), MAI lasciato `null` come "mai verificata": la scrittura
 * avviene comunque (a meno di `--dry-run`), così una sezione in timeout è distinguibile da una mai
 * controllata. Un errore diverso dal timeout (es. un 502 non riconducibile a un timeout) resta
 * invece non scritto, loggato soltanto — stesso principio "un errore non blocca le altre" già in
 * uso da `cai:sync-national`.
 */
class CaiCheckRuntsPresenceCommand extends Command
{
    protected $signature = 'cai:check-runts-presence
        {--dry-run : Calcola gli esiti senza scriverli}
        {--timeout=10 : Timeout in secondi per ogni verifica (una sezione trovata risponde in pochi secondi; una non trovata/ambigua può richiedere fino al timeout stesso — un valore basso scambia completezza per velocità)}';

    protected $description = 'Verifica se ogni sezione CAI con codice fiscale o partita IVA risulta registrata su RUNTS (solo ricerca, nessuno scrape completo)';

    public function __construct(private readonly CaiRuntsScraperClient $client)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $timeoutSeconds = (int) $this->option('timeout');
        $startedAt = now();

        Log::info('cai.check_runts_presence.started', ['dry_run' => $dryRun, 'timeout_seconds' => $timeoutSeconds]);

        $sections = CaiSection::query()
            ->where(fn ($query) => $query->whereNotNull('tax_code')->orWhereNotNull('vat_number'))
            ->get();

        $examined = 0;
        $registered = 0;
        $notRegistered = 0;
        $timedOut = 0;
        $otherErrors = 0;

        foreach ($sections as $section) {
            $examined++;
            $codiceFiscale = (string) ($section->tax_code ?? $section->vat_number);

            try {
                $found = $this->client->checkEntityExists($codiceFiscale, $timeoutSeconds);
                $status = $found ? CaiRuntsPresenceStatus::Registered : CaiRuntsPresenceStatus::NotRegistered;
                $found ? $registered++ : $notRegistered++;

                $this->line("- {$section->codice_cai}: ".($found ? 'presente su RUNTS' : 'non presente su RUNTS'));

                $this->writeStatus($section, $status, $dryRun);
            } catch (ConnectionException $exception) {
                $timedOut++;
                Log::warning('cai.check_runts_presence.item_timed_out', ['codice_cai' => $section->codice_cai, 'error' => $exception->getMessage()]);
                $this->warn("- {$section->codice_cai}: verifica in timeout");

                $this->writeStatus($section, CaiRuntsPresenceStatus::Timeout, $dryRun);
            } catch (Throwable $exception) {
                $otherErrors++;
                Log::warning('cai.check_runts_presence.item_failed', ['codice_cai' => $section->codice_cai, 'error' => $exception->getMessage()]);
                $this->warn("- {$section->codice_cai}: verifica fallita — {$exception->getMessage()}");
            }
        }

        Log::info('cai.check_runts_presence.finished', [
            'dry_run' => $dryRun,
            'examined' => $examined,
            'registered' => $registered,
            'not_registered' => $notRegistered,
            'timed_out' => $timedOut,
            'other_errors' => $otherErrors,
            'duration_ms' => $startedAt->diffInMilliseconds(now()),
        ]);

        $this->info(sprintf(
            'Verifica presenza RUNTS completata: %d sezioni esaminate, %d presenti, %d non presenti, %d in timeout, %d altri errori.',
            $examined,
            $registered,
            $notRegistered,
            $timedOut,
            $otherErrors,
        ));

        return self::SUCCESS;
    }

    private function writeStatus(CaiSection $section, CaiRuntsPresenceStatus $status, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        $section->update([
            'runts_presence_status' => $status,
            'runts_presence_checked_at' => Carbon::now(),
        ]);
    }
}
