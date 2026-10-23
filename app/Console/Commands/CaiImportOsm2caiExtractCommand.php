<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Import\CaiOsm2caiExtractWriter;
use App\Support\Formatting\ItalianNumberFormatter;
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
        {directory : Cartella con i CSV di database/osm2cai/extract.sql (relativa alla root del progetto, o assoluta)}
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

        $this->info('Tabelle snap_osm2cai_* scritte nel datapack.');
        $this->line("Sentieri estratti: {$summary->trails}");
        $this->line("Collegati ad almeno una Sezione: {$summary->linkedTrails}");
        $this->line('Codici di Sezione non presenti in sezioni_cai: '.count($summary->unknownSectionCodes));

        foreach ($summary->unknownSectionCodes as $code => $count) {
            $this->line("  - {$code}: {$count} sentieri");
        }

        $this->line("Province: {$summary->provinces} · settori: {$summary->sectors} · legami sentiero–territorio: {$summary->trailTerritories}");
        $this->line('Regioni estere scartate: '.(count($summary->foreignRegions) > 0 ? implode(', ', $summary->foreignRegions) : 'nessuna'));

        foreach ($summary->missingRegions() as $region) {
            $this->warn("Regione assente dalla fonte: {$region->label()}");
        }

        $this->line('Per regione (km nel territorio · sentieri presenti, almeno il 50% nella regione):');

        foreach ($summary->regions as $row) {
            $this->line(sprintf('  - %s: %s · %d sentieri', $row['region']->label(), ItalianNumberFormatter::kilometers($row['km']), $row['presentRoutes']));
        }

        $this->line(sprintf(
            'Tappe SICAI: %d (senza numero nel nome: %d, senza lettera: %d)',
            $summary->sicaiStages['total'],
            $summary->sicaiStages['withoutNumber'],
            $summary->sicaiStages['withoutLetter'],
        ));

        if ($summary->sicaiStages['withoutLetter'] > 0) {
            $this->warn('Tappe SICAI senza lettera: l\'itinerario non finisce con "<lettera>00"; non compariranno in nessun GR.');
        }

        return self::SUCCESS;
    }

    private function absolute(string $path): string
    {
        return str_starts_with($path, '/') ? $path : base_path($path);
    }
}
