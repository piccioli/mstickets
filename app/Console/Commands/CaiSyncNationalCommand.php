<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Actions\ScrapeCaiSection;
use App\Domain\CaiDirectory\Actions\SyncCaiSectionAndSubsections;
use App\Domain\CaiDirectory\Import\CaiApiSectionNormalizer;
use App\Domain\CaiDirectory\Support\CaiApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Refresh mensile nazionale CAI (Fase 9, storia 1, design doc §3.5): chiama l'API CAI
 * per l'elenco nazionale completo, poi sincronizza ogni sezione e le sue sottosezioni
 * riusando {@see SyncCaiSectionAndSubsections} (stessa logica del bottone dashboard,
 * {@see ScrapeCaiSection}, applicata in loop). Un errore su una singola sezione (es.
 * l'endpoint sottosezioni di quella sezione fallisce dopo i retry) è loggato e non
 * interrompe le altre — stesso principio già in uso da
 * `TicketsAutoCloseReleasedCommand`/`ApplyStatusToChildren`.
 */
class CaiSyncNationalCommand extends Command
{
    protected $signature = 'cai:sync-national {--dry-run : Calcola le modifiche senza scriverle}';

    protected $description = "Sincronizza dal vivo tutte le sezioni/sottosezioni CAI dall'API ufficiale (refresh mensile, Fase 9 storia 1)";

    public function __construct(
        private readonly CaiApiClient $apiClient,
        private readonly SyncCaiSectionAndSubsections $syncer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $startedAt = now();

        Log::info('cai.sync_national.started', ['dry_run' => $dryRun]);

        $rawSections = $this->apiClient->fetchNationalSections();

        $examined = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($rawSections as $raw) {
            $examined++;
            $codiceCai = (string) ($raw['code'] ?? '(sconosciuto)');

            try {
                $normalized = CaiApiSectionNormalizer::normalizeSection($raw);
                $results = $this->syncer->run($normalized, $dryRun);

                $created += $results['cai_sections']->created + $results['cai_subsections']->created;
                $updated += $results['cai_sections']->updated + $results['cai_subsections']->updated;
                $skipped += $results['cai_sections']->skipped + $results['cai_subsections']->skipped;

                $this->line(sprintf(
                    '- %s: sezione creati %d, aggiornati %d, saltati %d; sottosezioni creati %d, aggiornati %d, saltati %d',
                    $codiceCai,
                    $results['cai_sections']->created,
                    $results['cai_sections']->updated,
                    $results['cai_sections']->skipped,
                    $results['cai_subsections']->created,
                    $results['cai_subsections']->updated,
                    $results['cai_subsections']->skipped,
                ));
            } catch (Throwable $exception) {
                $errors++;
                Log::warning('cai.sync_national.item_failed', ['codice_cai' => $codiceCai, 'error' => $exception->getMessage()]);
                $this->warn("- {$codiceCai}: sincronizzazione fallita — {$exception->getMessage()}");
            }
        }

        Log::info('cai.sync_national.finished', [
            'dry_run' => $dryRun,
            'examined' => $examined,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'duration_ms' => $startedAt->diffInMilliseconds(now()),
        ]);

        $this->info(sprintf(
            'Sincronizzazione nazionale CAI completata: %d sezioni esaminate, %d create, %d aggiornate, %d invariate, %d errori.',
            $examined,
            $created,
            $updated,
            $skipped,
            $errors,
        ));

        return self::SUCCESS;
    }
}
