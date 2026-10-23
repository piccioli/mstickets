<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Import\CaiHutsFetcher;
use App\Domain\CaiDirectory\Import\CaiHutsFetchSummary;
use Illuminate\Console\Command;
use PDO;
use Throwable;

/**
 * Scarica i rifugi da rifugi.cai.it nella tabella `snap_cai_huts` del datapack (US-1032). Da eseguire SOLO in locale,
 * prima di `cai:import-datapack` e `bin/push-cai-datapack`: mai durante il deploy. Comando sottile: opzioni, verifica
 * del file, delega a {@see CaiHutsFetcher}, riepilogo.
 */
final class CaiFetchHutsCommand extends Command
{
    protected $signature = 'cai:fetch-huts
        {--datapack=cai-datapack/runts-cai.sqlite : Percorso del file SQLite del datapack (relativo alla root del progetto, o assoluto)}
        {--dry-run : Scarica e stampa il riepilogo senza modificare il datapack}';

    protected $description = 'Scarica i rifugi da rifugi.cai.it nella tabella snap_cai_huts del datapack RUNTS-CAI.';

    public function handle(CaiHutsFetcher $fetcher): int
    {
        $rawPath = (string) $this->option('datapack');
        $datapackPath = str_starts_with($rawPath, '/') ? $rawPath : base_path($rawPath);
        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($datapackPath)) {
            $this->error("File datapack non trovato: {$datapackPath}");

            return self::FAILURE;
        }

        try {
            $pdo = new PDO('sqlite:'.$datapackPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sectionCodes = array_map('strval', $pdo->query('SELECT codice_cai FROM sezioni_cai')->fetchAll(PDO::FETCH_COLUMN));

            $this->line('Scarico elenco e dettagli da '.config('cai_directory.huts_api.base_url').' …');
            $details = $fetcher->fetch();

            if (! $dryRun) {
                $fetcher->write($pdo, $details);
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $summary = CaiHutsFetchSummary::of($details, $sectionCodes);

        $this->info($dryRun
            ? 'Download (dry-run) completato: il datapack non è stato modificato.'
            : 'Download completato: tabella snap_cai_huts scritta nel datapack.');
        $this->line("Rifugi scaricati: {$summary->total}");
        $this->line("Con foto: {$summary->withPhotos}");
        $this->line("Collegati a una Sezione esistente: {$summary->linked}");
        $this->line("Non collegati: {$summary->unlinked()}");

        foreach ($summary->unlinkedByBranch as $branch => $count) {
            $this->line("  - branch_id {$branch}: {$count}");
        }

        return self::SUCCESS;
    }
}
