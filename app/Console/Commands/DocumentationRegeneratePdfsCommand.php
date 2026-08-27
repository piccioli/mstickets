<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Documentation\Actions\GenerateDocumentationPagePdf;
use App\Domain\Documentation\Models\DocumentationPage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Rigenera in blocco tutti i PDF di documentazione (manuale, §10.2/§6.4.3 del
 * PRD, US-406): serve dopo un cambio di logo/layout, quando i PDF già generati
 * non riflettono più il design corrente. Rispetta le regole comuni §10.1:
 * `--dry-run` non scrive nulla, log strutturato di inizio/fine/durata/conteggi,
 * idempotente (sovrascrive sempre lo stesso pdf_path), un errore su una singola
 * pagina non interrompe il batch — stesso principio già applicato da
 * {@see MailRetryFailedCommand} per i messaggi email.
 */
final class DocumentationRegeneratePdfsCommand extends Command
{
    protected $signature = 'documentation:regenerate-pdfs
        {--dry-run : Esamina e conta le pagine senza generare o scrivere alcun PDF}';

    protected $description = 'Rigenera in blocco i PDF di tutte le pagine di documentazione (§6.4.3 del PRD).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $startedAt = now();

        Log::info('documentation.regenerate_pdfs.started', ['dry_run' => $dryRun]);

        $examined = 0;
        $regenerated = 0;
        $skipped = 0;
        $errors = 0;

        DocumentationPage::query()->orderBy('id')->chunkById(50, function ($pages) use (&$examined, &$regenerated, &$skipped, &$errors, $dryRun): void {
            foreach ($pages as $page) {
                $examined++;

                if ($dryRun) {
                    $skipped++;
                    $this->line("[dry-run] pagina #{$page->id} \"{$page->title}\": PDF da rigenerare.");

                    continue;
                }

                try {
                    GenerateDocumentationPagePdf::run($page);
                    $regenerated++;
                    $this->info("Pagina #{$page->id} \"{$page->title}\": PDF rigenerato.");
                } catch (Throwable $exception) {
                    $errors++;
                    Log::warning('documentation.regenerate_pdfs.item_failed', [
                        'documentation_page_id' => $page->id,
                        'error' => $exception->getMessage(),
                    ]);
                    $this->warn("Pagina #{$page->id} \"{$page->title}\": rigenerazione fallita — {$exception->getMessage()}");
                }
            }
        });

        $durationMs = $startedAt->diffInMilliseconds(now());

        Log::info('documentation.regenerate_pdfs.finished', [
            'dry_run' => $dryRun,
            'examined' => $examined,
            'regenerated' => $regenerated,
            'skipped' => $skipped,
            'errors' => $errors,
            'duration_ms' => $durationMs,
        ]);

        $this->info(sprintf(
            'Pagine esaminate: %d. Rigenerate: %d. Saltate: %d. Errori: %d.',
            $examined,
            $regenerated,
            $skipped,
            $errors,
        ));

        return self::SUCCESS;
    }
}
