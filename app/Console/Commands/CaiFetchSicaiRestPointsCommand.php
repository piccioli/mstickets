<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Import\CaiSicaiRestPointFieldMapper;
use App\Domain\CaiDirectory\Import\CaiSicaiRestPointsFetcher;
use Illuminate\Console\Command;
use PDO;
use Throwable;

/**
 * Scarica i punti di appoggio SICAI da rifugi.cai.it nella tabella `snap_cai_sicai_rest_points` del datapack
 * (US-1092), gemello di `cai:fetch-huts` con le stesse regole (tutto o niente, rate limit rispettato). Da eseguire SOLO
 * in locale, prima di `cai:import-datapack` e `bin/push-cai-datapack`: mai durante il deploy.
 */
final class CaiFetchSicaiRestPointsCommand extends Command
{
    protected $signature = 'cai:fetch-sicai-rest-points
        {--datapack=cai-datapack/runts-cai.sqlite : Percorso del file SQLite del datapack (relativo alla root del progetto, o assoluto)}
        {--dry-run : Scarica e stampa il riepilogo senza modificare il datapack}';

    protected $description = 'Scarica i punti di appoggio SICAI da rifugi.cai.it nella tabella snap_cai_sicai_rest_points del datapack RUNTS-CAI.';

    public function handle(CaiSicaiRestPointsFetcher $fetcher): int
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

            $this->line('Scarico elenco e dettagli da '.config('cai_directory.huts_api.base_url').' …');
            $details = $fetcher->fetch();

            if (! $dryRun) {
                $fetcher->write($pdo, $details);
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $withShelter = 0;
        $byType = [];

        foreach ($details as $payload) {
            $detail = json_decode($payload, true);
            $detail = is_array($detail) ? $detail : [];

            if (CaiSicaiRestPointFieldMapper::shelterId($detail) !== null) {
                $withShelter++;
            }

            $type = CaiSicaiRestPointFieldMapper::map($detail)['type'] ?? '(vuoto)';
            $byType[$type] = ($byType[$type] ?? 0) + 1;
        }

        arsort($byType);

        $this->info($dryRun
            ? 'Download (dry-run) completato: il datapack non è stato modificato.'
            : 'Download completato: tabella '.$fetcher->table().' scritta nel datapack.');
        $this->line('Punti di appoggio scaricati: '.count($details));
        $this->line("Collegati a un rifugio (shelter_id): {$withShelter}");

        foreach ($byType as $type => $count) {
            $this->line("  - {$type}: {$count}");
        }

        return self::SUCCESS;
    }
}
