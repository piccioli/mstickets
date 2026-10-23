<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Import\CaiOsm2caiExtractWriter;
use Illuminate\Console\Command;
use PDO;
use Throwable;

/**
 * Scrive nel datapack le tabelle `snap_osm2cai_*` dai CSV estratti dal dump osm2cai (US-1051). Lo chiama
 * `bin/update-osm2cai-datapack`, solo in locale: mai durante il deploy. Comando sottile: opzioni, verifica dei file,
 * delega a {@see CaiOsm2caiExtractWriter}, riepilogo.
 */
final class CaiImportOsm2caiExtractCommand extends Command
{
    protected $signature = 'cai:import-osm2cai-extract
        {directory : Cartella con trails.csv e trail_sections.csv (relativa alla root del progetto, o assoluta)}
        {--datapack=cai-datapack/runts-cai.sqlite : Percorso del file SQLite del datapack (relativo alla root del progetto, o assoluto)}';

    protected $description = 'Scrive le tabelle snap_osm2cai_* del datapack RUNTS-CAI dai CSV estratti dal dump osm2cai.';

    public function handle(CaiOsm2caiExtractWriter $writer): int
    {
        $directory = $this->absolute((string) $this->argument('directory'));
        $datapackPath = $this->absolute((string) $this->option('datapack'));

        if (! is_dir($directory)) {
            $this->error("Cartella dei CSV non trovata: {$directory}");

            return self::FAILURE;
        }

        if (! is_file($datapackPath)) {
            $this->error("File datapack non trovato: {$datapackPath}");

            return self::FAILURE;
        }

        try {
            $pdo = new PDO('sqlite:'.$datapackPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $summary = $writer->write($pdo, $directory);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Tabelle '.CaiOsm2caiExtractWriter::TRAILS_TABLE.' e '.CaiOsm2caiExtractWriter::TRAIL_SECTIONS_TABLE.' scritte nel datapack.');
        $this->line("Sentieri estratti: {$summary->trails}");
        $this->line("Collegati ad almeno una Sezione: {$summary->linkedTrails}");
        $this->line('Codici di Sezione non presenti in sezioni_cai: '.count($summary->unknownSectionCodes));

        foreach ($summary->unknownSectionCodes as $code => $count) {
            $this->line("  - {$code}: {$count} sentieri");
        }

        return self::SUCCESS;
    }

    private function absolute(string $path): string
    {
        return str_starts_with($path, '/') ? $path : base_path($path);
    }
}
