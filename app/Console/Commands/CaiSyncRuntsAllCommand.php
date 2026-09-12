<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Actions\SyncCaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sincronizza dal vivo i dati RUNTS (+ cariche sociali + documenti + analisi bilanci, Fase 9 storia 3) di
 * TUTTE le sezioni CAI con un codice fiscale in anagrafica: stesso identico percorso di
 * {@see CaiSyncRuntsSectionCommand} (una sezione sola) applicato in loop, stesso principio di
 * `CaiSyncNationalCommand` (un errore su una singola sezione è loggato e non interrompe le altre).
 */
class CaiSyncRuntsAllCommand extends Command
{
    protected $signature = 'cai:sync-runts-all {--limit= : Numero massimo di sezioni da processare}';

    protected $description = 'Sincronizza dal vivo i dati RUNTS e i bilanci per tutte le sezioni CAI con codice fiscale';

    public function __construct(private readonly SyncCaiRuntsRegistration $syncer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $startedAt = now();

        $sections = CaiSection::query()
            ->whereNotNull('tax_code')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get();

        Log::info('cai.sync_runts_all.started', ['total' => $sections->count()]);

        $examined = 0;
        $synced = 0;
        $notFound = 0;
        $errors = 0;

        foreach ($sections as $section) {
            $examined++;

            try {
                $result = $this->syncer->run($section);

                if ($result->found) {
                    $synced++;
                    $this->line(sprintf(
                        '- %s: sincronizzata (%d bilancio/i in analisi)',
                        $section->codice_cai,
                        $result->queuedAnalysisCount,
                    ));
                } else {
                    $notFound++;
                    $this->line("- {$section->codice_cai}: nessuna registrazione RUNTS trovata");
                }
            } catch (Throwable $exception) {
                $errors++;
                Log::warning('cai.sync_runts_all.item_failed', [
                    'codice_cai' => $section->codice_cai,
                    'error' => $exception->getMessage(),
                ]);
                $this->warn("- {$section->codice_cai}: sincronizzazione fallita — {$exception->getMessage()}");
            }
        }

        Log::info('cai.sync_runts_all.finished', [
            'examined' => $examined,
            'synced' => $synced,
            'not_found' => $notFound,
            'errors' => $errors,
            'duration_ms' => $startedAt->diffInMilliseconds(now()),
        ]);

        $this->info(sprintf(
            '%d sezioni esaminate, %d sincronizzate, %d non trovate, %d errori.',
            $examined,
            $synced,
            $notFound,
            $errors,
        ));

        return self::SUCCESS;
    }
}
