<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Import\CaiDatapackImporter;
use App\Domain\CaiDirectory\Import\CaiImportTableResult;
use Illuminate\Console\Command;

/**
 * Importa il datapack RUNTS-CAI (US-802) nelle tabelle del dominio
 * `App\Domain\CaiDirectory` (create in US-801). Comando volutamente sottile: parsing
 * opzioni + verifica esistenza file (stesso principio esplicito di `bin/load-v1-dump`,
 * mai un errore PDO criptico) + delega a {@see CaiDatapackImporter} + stampa del
 * riepilogo strutturato.
 */
final class CaiImportDatapackCommand extends Command
{
    protected $signature = 'cai:import-datapack
        {--path=cai-datapack/runts-cai.sqlite : Percorso del file SQLite del datapack RUNTS-CAI (relativo alla root del progetto, o assoluto)}
        {--dry-run : Non scrive alcuna riga né alcun file, solo conteggio di quanto verrebbe letto}';

    protected $description = 'Importa il datapack RUNTS-CAI (sezioni, sottosezioni, enti, bilanci, cariche sociali, allegati) in App\\Domain\\CaiDirectory.';

    public function __construct(private readonly CaiDatapackImporter $importer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $absolutePath = $this->resolveAbsolutePath((string) $this->option('path'));

        if (! is_file($absolutePath)) {
            $this->error("File datapack non trovato: {$absolutePath}");
            $this->line('Copia il datapack RUNTS-CAI (runts-cai.sqlite) nella cartella cai-datapack/ prima di eseguire questo comando.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $results = $this->importer->import($absolutePath, $dryRun);

        $this->reportSummary($results, $dryRun);

        return self::SUCCESS;
    }

    private function resolveAbsolutePath(string $rawPath): string
    {
        return str_starts_with($rawPath, '/') ? $rawPath : base_path($rawPath);
    }

    /**
     * @param  array<string, CaiImportTableResult>  $results
     */
    private function reportSummary(array $results, bool $dryRun): void
    {
        $this->info($dryRun ? 'Import (dry-run) completato.' : 'Import completato.');

        foreach ($results as $table => $result) {
            $this->line("- {$table}: letti {$result->read}, creati {$result->created}, aggiornati {$result->updated}, saltati {$result->skipped}");

            foreach ($result->warnings as $warning) {
                $this->warn("  {$warning}");
            }
        }
    }
}
