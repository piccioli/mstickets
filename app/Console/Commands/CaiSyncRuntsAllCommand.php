<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Actions\SyncCaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sincronizza dal vivo i dati RUNTS (+ cariche sociali + documenti + analisi bilanci, Fase 9 storia 3) di
 * TUTTE le sezioni CAI con un codice fiscale plausibile in anagrafica: stesso identico percorso di
 * {@see CaiSyncRuntsSectionCommand} (una sezione sola) applicato in loop, stesso principio di
 * `CaiSyncNationalCommand` (un errore su una singola sezione è loggato e non interrompe le altre).
 *
 * Due protezioni aggiunte dopo un run reale su tutte le sezioni (verificato: il sito RUNTS può degradare
 * per una finestra di tempo, producendo una lunga serie di timeout Playwright consecutivi su enti
 * completamente diversi, poi recuperare da sé): (1) un ritardo configurabile fra una sezione e la
 * successiva (`--delay-ms`), per non contribuire a un eventuale sovraccarico del sito; (2) un secondo
 * giro di retry automatico, a fine batch, solo sulle sezioni fallite con un errore (mai su quelle
 * "non trovate", che sono un esito legittimo) — una sezione che fallisce nel giro principale ma va a
 * buon fine nel retry conta come sincronizzata, non come errore.
 */
class CaiSyncRuntsAllCommand extends Command
{
    /**
     * Un codice fiscale/partita IVA reale ha sempre almeno questa lunghezza (11 cifre CF pubblico, 11
     * partita IVA, 16 alfanumerico CF persona fisica). Valori più corti (osservati in anagrafica: "0",
     * ".") non possono mai corrispondere a un ente RUNTS reale — inviarli allo scraper produce solo un
     * timeout/502 sprecato, mai un risultato utile.
     */
    private const MIN_PLAUSIBLE_TAX_CODE_LENGTH = 5;

    protected $signature = 'cai:sync-runts-all
        {--limit= : Numero massimo di sezioni da processare}
        {--delay-ms=1500 : Millisecondi di pausa fra una sezione e la successiva (e fra i retry)}';

    protected $description = 'Sincronizza dal vivo i dati RUNTS e i bilanci per tutte le sezioni CAI con codice fiscale';

    public function __construct(private readonly SyncCaiRuntsRegistration $syncer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $delayMicroseconds = ((int) $this->option('delay-ms')) * 1000;
        $startedAt = now();

        $sections = CaiSection::query()
            ->whereNotNull('tax_code')
            ->orderBy('codice_cai')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get();

        $invalid = 0;
        $outcomes = [];

        foreach ($sections as $section) {
            if (! $this->hasPlausibleTaxCode($section)) {
                $invalid++;
                $this->line("- {$section->codice_cai}: codice fiscale non valido (\"{$section->tax_code}\"), saltata");

                continue;
            }

            $outcomes[$section->codice_cai] = [$section, $this->attemptSync($section)];
            $this->pause($delayMicroseconds);
        }

        $this->retryFailedSections($outcomes, $delayMicroseconds);

        $this->reportSummary($outcomes, $invalid, $startedAt);

        return self::SUCCESS;
    }

    private function hasPlausibleTaxCode(CaiSection $section): bool
    {
        return mb_strlen(trim((string) $section->tax_code)) >= self::MIN_PLAUSIBLE_TAX_CODE_LENGTH;
    }

    /**
     * @return 'synced'|'not_found'|'error'
     */
    private function attemptSync(CaiSection $section): string
    {
        try {
            $result = $this->syncer->run($section);

            if ($result->found) {
                $this->line(sprintf(
                    '- %s: sincronizzata (%d bilancio/i in analisi)',
                    $section->codice_cai,
                    $result->queuedAnalysisCount,
                ));

                return 'synced';
            }

            $this->line("- {$section->codice_cai}: nessuna registrazione RUNTS trovata");

            return 'not_found';
        } catch (Throwable $exception) {
            Log::warning('cai.sync_runts_all.item_failed', [
                'codice_cai' => $section->codice_cai,
                'error' => $exception->getMessage(),
            ]);
            $this->warn("- {$section->codice_cai}: sincronizzazione fallita — {$exception->getMessage()}");

            return 'error';
        }
    }

    /**
     * @param  array<string, array{0: CaiSection, 1: string}>  $outcomes
     */
    private function retryFailedSections(array &$outcomes, int $delayMicroseconds): void
    {
        $failed = array_filter($outcomes, fn (array $outcome): bool => $outcome[1] === 'error');

        if ($failed === []) {
            return;
        }

        $this->line(sprintf(
            'Ritento %d sezione/i fallita/e (possibile problema temporaneo del sito RUNTS)...',
            count($failed),
        ));

        foreach ($failed as $codiceCai => [$section]) {
            $outcomes[$codiceCai] = [$section, $this->attemptSync($section)];
            $this->pause($delayMicroseconds);
        }
    }

    private function pause(int $microseconds): void
    {
        if ($microseconds > 0) {
            usleep($microseconds);
        }
    }

    /**
     * @param  array<string, array{0: CaiSection, 1: string}>  $outcomes
     */
    private function reportSummary(array $outcomes, int $invalid, Carbon $startedAt): void
    {
        $examined = count($outcomes);
        $synced = count(array_filter($outcomes, fn (array $o): bool => $o[1] === 'synced'));
        $notFound = count(array_filter($outcomes, fn (array $o): bool => $o[1] === 'not_found'));
        $errors = count(array_filter($outcomes, fn (array $o): bool => $o[1] === 'error'));

        Log::info('cai.sync_runts_all.finished', [
            'examined' => $examined,
            'synced' => $synced,
            'not_found' => $notFound,
            'errors' => $errors,
            'invalid' => $invalid,
            'duration_ms' => $startedAt->diffInMilliseconds(now()),
        ]);

        $this->info(sprintf(
            '%d sezioni esaminate, %d sincronizzate, %d non trovate, %d errori, %d codice fiscale non validi.',
            $examined,
            $synced,
            $notFound,
            $errors,
            $invalid,
        ));
    }
}
