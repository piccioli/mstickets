<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Import\ManualBilancio\ManualBilancioBuildResult;
use App\Domain\CaiDirectory\Import\ManualBilancio\ManualBilancioDatapackBuilder;
use Illuminate\Console\Command;
use InvalidArgumentException;
use PDO;
use Throwable;

/**
 * Aggiunge al datapack RUNTS-CAI la tabella `bilanci_manuali` (bilanci delle Sezioni caricati a mano,
 * già normalizzati in `<staging>/normalized/`). Comando volutamente sottile: opzioni + verifica file +
 * delega a {@see ManualBilancioDatapackBuilder} + scrittura in una sola transazione + report.
 * Nessun'altra tabella del datapack viene toccata.
 */
final class CaiBuildManualBilanciDatapackCommand extends Command
{
    protected $signature = 'cai:build-manual-bilanci-datapack
        {--datapack=cai-datapack/runts-cai.sqlite : Percorso del file SQLite del datapack (relativo alla root del progetto, o assoluto)}
        {--staging=cai-datapack/bilanci-sezioni-2026 : Cartella con normalized/ e 2026_Campagna_Sezioni.xlsx (relativa alla root del progetto, o assoluta)}
        {--dry-run : Non modifica il datapack, stampa solo il report}';

    protected $description = 'Aggiunge al datapack RUNTS-CAI la tabella bilanci_manuali con i bilanci delle Sezioni normalizzati.';

    public function handle(): int
    {
        $datapackPath = $this->resolveAbsolutePath((string) $this->option('datapack'));
        $stagingDir = $this->resolveAbsolutePath((string) $this->option('staging'));
        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($datapackPath)) {
            $this->error("File datapack non trovato: {$datapackPath}");
            $this->line('Copia il datapack RUNTS-CAI (runts-cai.sqlite) nella cartella cai-datapack/ prima di eseguire questo comando.');

            return self::FAILURE;
        }

        if (! is_dir($stagingDir)) {
            $this->error("Cartella dei bilanci non trovata: {$stagingDir}");

            return self::FAILURE;
        }

        try {
            $pdo = new PDO('sqlite:'.$datapackPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $codes = $pdo->query('SELECT codice_cai FROM sezioni_cai')->fetchAll(PDO::FETCH_COLUMN);
            $result = ManualBilancioDatapackBuilder::build($stagingDir, array_map('strval', $codes));

            if (! $dryRun) {
                $this->write($pdo, $result);
            }
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error('Errore durante la costruzione di bilanci_manuali: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->report($result, $dryRun);

        return self::SUCCESS;
    }

    private function write(PDO $pdo, ManualBilancioBuildResult $result): void
    {
        $pdo->beginTransaction();

        try {
            $pdo->exec('DROP TABLE IF EXISTS bilanci_manuali');
            $pdo->exec('CREATE TABLE bilanci_manuali (
                id INTEGER PRIMARY KEY,
                codice_cai TEXT NOT NULL REFERENCES sezioni_cai(codice_cai),
                regione TEXT NOT NULL,
                anno INTEGER NOT NULL,
                tipo TEXT NOT NULL,
                titolo TEXT NOT NULL,
                filename TEXT NOT NULL,
                path TEXT NOT NULL,
                mime TEXT,
                size INTEGER,
                hash_sha256 TEXT NOT NULL
            )');

            $insert = $pdo->prepare('INSERT INTO bilanci_manuali
                (codice_cai, regione, anno, tipo, titolo, filename, path, mime, size, hash_sha256)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

            foreach ($result->rows as $row) {
                $insert->execute([
                    $row->codiceCai, $row->regione, $row->anno, $row->tipo, $row->titolo,
                    $row->fileName, $row->path, $row->mimeType, $row->size, $row->hashSha256,
                ]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }
    }

    private function report(ManualBilancioBuildResult $result, bool $dryRun): void
    {
        $this->info($dryRun ? 'Build (dry-run) completata: il datapack non è stato modificato.' : 'Build completata: tabella bilanci_manuali scritta nel datapack.');

        /** @var array<string, array{sections: array<string, true>, files: int}> $perRegion */
        $perRegion = [];
        foreach ($result->rows as $row) {
            $perRegion[$row->regione] ??= ['sections' => [], 'files' => 0];
            $perRegion[$row->regione]['sections'][$row->codiceCai] = true;
            $perRegion[$row->regione]['files']++;
        }

        $this->table(
            ['Regione', 'Sezioni', 'File'],
            array_map(
                static fn (string $regione, array $data): array => [$regione, count($data['sections']), $data['files']],
                array_keys($perRegion),
                $perRegion,
            ),
        );

        $sizeMb = number_format($result->totalSize() / 1048576, 1, ',', '.');
        $this->line("Totali: {$result->filesCount()} file, {$result->sectionsCount()} sezioni, {$sizeMb} MB.");

        if ($result->anomalies === []) {
            return;
        }

        $this->newLine();
        $this->warn('Anomalie:');
        foreach ($result->anomalyCounts() as $code => $count) {
            $this->line("- {$code}: {$count}");
            foreach ($result->anomaliesOf($code) as $anomaly) {
                $this->line("    {$anomaly->message}");
            }
        }
    }

    private function resolveAbsolutePath(string $rawPath): string
    {
        return str_starts_with($rawPath, '/') ? $rawPath : base_path($rawPath);
    }
}
