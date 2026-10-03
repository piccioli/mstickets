<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Export\CaiSnapshotExporter;
use Illuminate\Console\Command;
use Throwable;

/**
 * Fotografa nel datapack RUNTS-CAI tutte le righe CAI del DB corrente (tabelle `snap_*`). Comando
 * volutamente sottile: opzioni + verifica file + delega a {@see CaiSnapshotExporter} + riepilogo.
 * Nessun'altra tabella del datapack viene toccata.
 */
final class CaiExportDatapackSnapshotCommand extends Command
{
    protected $signature = 'cai:export-datapack-snapshot
        {--datapack=cai-datapack/runts-cai.sqlite : Percorso del file SQLite del datapack (relativo alla root del progetto, o assoluto)}
        {--dry-run : Non modifica il datapack, stampa solo il riepilogo}';

    protected $description = 'Esporta nel datapack RUNTS-CAI tutte le righe CAI del database corrente (tabelle snap_*).';

    public function handle(CaiSnapshotExporter $exporter): int
    {
        $rawPath = (string) $this->option('datapack');
        $datapackPath = str_starts_with($rawPath, '/') ? $rawPath : base_path($rawPath);
        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($datapackPath)) {
            $this->error("File datapack non trovato: {$datapackPath}");
            $this->line('Copia il datapack RUNTS-CAI (runts-cai.sqlite) nella cartella cai-datapack/ prima di eseguire questo comando.');

            return self::FAILURE;
        }

        try {
            $result = $exporter->export($datapackPath, $dryRun);
        } catch (Throwable $e) {
            $this->error('Errore durante l\'esportazione dello snapshot: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info($dryRun ? 'Esportazione (dry-run) completata: il datapack non è stato modificato.' : 'Esportazione completata: tabelle snap_* scritte nel datapack.');

        $mismatch = false;
        $rows = [];
        foreach ($result['tables'] as $table => $counts) {
            $match = $counts['exported'] === $counts['database'];
            $mismatch = $mismatch || ! $match;
            $rows[] = [$table, $counts['exported'], $counts['database'], $match ? 'ok' : 'DIVERSO'];
        }
        $this->table(['Tabella', 'Esportate', 'Nel database', 'Esito'], $rows);

        $files = $result['files'];
        $this->line(sprintf(
            'File documenti RUNTS: %d %s, %d già presenti, %d mancanti; %.1f MB %s; %d hash distinti.',
            $files['copied'],
            $dryRun ? 'da copiare' : 'copiati',
            $files['present'],
            $files['missing'],
            $files['bytes'] / 1048576,
            $dryRun ? 'da copiare' : 'copiati',
            $files['distinct'],
        ));
        foreach ($files['missing_paths'] as $path) {
            $this->warn("File mancante sul disco cai-documents: {$path}");
        }

        if ($mismatch) {
            $this->error('I conteggi esportati non coincidono con il database.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
