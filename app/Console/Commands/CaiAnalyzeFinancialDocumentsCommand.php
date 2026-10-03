<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use App\Domain\CaiDirectory\Queries\CaiFinancialDocumentsToAnalyzeQuery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Accoda l'analisi dei documenti conto economico / stato patrimoniale di un anno (Fase 9): l'import dei
 * documenti non accoda mai l'analisi da solo. Idempotente: dopo l'analisi `financial_analysis_status` non è
 * più `null`, quindi una seconda esecuzione senza `--force` non accoda nulla.
 */
class CaiAnalyzeFinancialDocumentsCommand extends Command
{
    protected $signature = 'cai:analyze-financial-documents
        {--year= : Anno dei documenti da analizzare (obbligatorio)}
        {--section= : Limita a una sezione (codice CAI)}
        {--force : Rianalizza anche i documenti già analizzati}
        {--dry-run : Non accoda nulla, mostra solo cosa verrebbe accodato}';

    protected $description = 'Accoda l\'analisi dei conti economici/stati patrimoniali di un anno (coda cai-runts-analysis)';

    public function handle(): int
    {
        $year = $this->option('year');

        if ($year === null || ! ctype_digit((string) $year) || (int) $year < 1900) {
            $this->error('Specificare un anno valido con --year=AAAA.');

            return self::FAILURE;
        }

        $year = (int) $year;
        $section = $this->option('section') !== null ? (string) $this->option('section') : null;
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        Log::info('cai.analyze_financial_documents.started', [
            'year' => $year, 'section' => $section, 'force' => $force, 'dry_run' => $dryRun,
        ]);

        $all = CaiFinancialDocumentsToAnalyzeQuery::forYear($year, $section);
        $candidates = $force ? $all : CaiFinancialDocumentsToAnalyzeQuery::neverAnalyzed($all->clone());

        $total = $all->clone()->count();
        $toQueue = $candidates->clone()->count();
        $skipped = $total - $toQueue;

        if ($dryRun) {
            $this->info("Dry run: {$toQueue} documenti verrebbero accodati ({$skipped} già analizzati saltati).");

            foreach (CaiFinancialDocumentsToAnalyzeQuery::countByRegion($candidates->clone()) as $region => $count) {
                $this->line("  {$region}: {$count}");
            }
        } else {
            $candidates->clone()->orderBy('cai_documents.id')->pluck('cai_documents.id')->each(
                fn (int $id) => AnalyzeCaiFinancialStatementDocument::dispatch($id)->onQueue('cai-runts-analysis'),
            );

            $this->info("{$toQueue} documenti accodati, {$skipped} saltati (già analizzati).");
            $this->line('Controlla Horizon / la coda "cai-runts-analysis" per l\'avanzamento.');
        }

        Log::info('cai.analyze_financial_documents.finished', [
            'year' => $year, 'queued' => $dryRun ? 0 : $toQueue, 'would_queue' => $toQueue, 'skipped' => $skipped, 'dry_run' => $dryRun,
        ]);

        return self::SUCCESS;
    }
}
