<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\ImportContext;
use App\Import\Stages\ImportRunner;
use App\Import\Stages\ImportRunnerException;
use App\Import\Stages\ImportStageRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDOException;
use Throwable;

/**
 * Esegue l'ETL v1→v2 (§11 del PRD) sugli stage registrati in
 * config('import.stages'), nell'ordine risolto da ImportRunner dalle
 * dipendenze dichiarate da ciascuno stage. Ogni esecuzione produce/aggiorna
 * una riga import_runs (§5.2) con i conteggi per stage.
 */
final class V1ImportCommand extends Command
{
    protected $signature = 'v1:import
        {--dry-run : Non scrive alcuna riga nelle tabelle di destinazione}
        {--stage= : Esegue solo lo stage indicato}
        {--from-stage= : Esegue lo stage indicato e tutti quelli successivi nell\'ordine di dipendenza}
        {--limit= : Limita il numero di righe lette da ciascuno stage (debug/dev)}
        {--truncate : Tronca le tabelle di destinazione prima di importare (richiede conferma, non consentito in produzione)}
        {--anonymize : Sostituisce dati identificativi con dati fittizi deterministici (§11.8)}';

    protected $description = 'Importa i dati del dump v1 (db_legacy) nello schema v2.';

    private const CONNECTION = 'legacy';

    public function __construct(private readonly ImportStageRegistry $registry)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('stage') !== null && $this->option('from-stage') !== null) {
            $this->error('Le opzioni --stage e --from-stage non possono essere usate insieme.');

            return self::FAILURE;
        }

        if ($this->option('truncate') && ! $this->truncateIsAllowed()) {
            return self::FAILURE;
        }

        if (! $this->legacyConnectionIsReachable()) {
            $this->error('Impossibile connettersi alla connessione "legacy" (db_legacy).');
            $this->line('Assicurati che il servizio sia avviato con `make etl-up` e che il dump sia caricato con `bin/load-v1-dump path/to/dump.sql`.');

            return self::FAILURE;
        }

        $runner = new ImportRunner($this->registry);

        try {
            $stages = $runner->plan($this->option('stage'), $this->option('from-stage'));
        } catch (ImportRunnerException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $importRun = ImportRun::create([
            'started_at' => now(),
            'dump_label' => (string) config('database.connections.legacy.database', 'legacy'),
            'stages' => [],
            'status' => ImportRunStatus::Running,
            'is_dry_run' => $this->option('dry-run'),
        ]);

        $context = new ImportContext(
            importRun: $importRun,
            dryRun: (bool) $this->option('dry-run'),
            limit: $this->option('limit') !== null ? (int) $this->option('limit') : null,
            truncate: (bool) $this->option('truncate'),
            anonymize: (bool) $this->option('anonymize'),
        );

        try {
            $importRun = $runner->run($stages, $context);
        } catch (Throwable $exception) {
            $this->error("Import interrotto: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $this->reportSummary($importRun);

        return self::SUCCESS;
    }

    private function legacyConnectionIsReachable(): bool
    {
        try {
            DB::connection(self::CONNECTION)->getPdo();
        } catch (PDOException) {
            return false;
        }

        return true;
    }

    private function truncateIsAllowed(): bool
    {
        if (app()->environment('production')) {
            $this->error('--truncate non è consentito in ambiente di produzione.');

            return false;
        }

        if (! $this->confirm('Sei sicuro di voler troncare le tabelle di destinazione prima di importare?')) {
            $this->line('Import annullato.');

            return false;
        }

        return true;
    }

    private function reportSummary(ImportRun $importRun): void
    {
        $this->info("Import {$importRun->status->value}.");

        /** @var array<string, array{read: int, created: int, updated: int, skipped: int, warnings: array<int, string>}> $stages */
        $stages = $importRun->stages ?? [];

        foreach ($stages as $name => $result) {
            $this->line("- {$name}: letti {$result['read']}, creati {$result['created']}, aggiornati {$result['updated']}, saltati {$result['skipped']}");
        }
    }
}
