<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Jobs;

use App\Domain\CaiDirectory\Import\CaiFinancialStatementFieldMapper;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Support\CaiRuntsScraperClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Estrae le cifre finanziarie da un `CaiDocument` di bilancio già scaricato/allegato (Fase 9, storia 3,
 * design doc §4.4) chiamando il servizio Python `cai-runts-scraper`. Prende un id (mai il modello
 * serializzato) e ri-legge tutto a runtime, stesso pattern già in uso da
 * `App\Domain\Documentation\Jobs\GenerateDocumentationPagePdfJob`. `->onQueue('cai-runts-analysis')` è
 * applicato al sito di dispatch (Task 7), non su questa classe.
 */
final class AnalyzeCaiFinancialStatementDocument implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $caiDocumentId) {}

    public function handle(CaiRuntsScraperClient $client): void
    {
        $document = CaiDocument::query()->find($this->caiDocumentId);

        if ($document === null) {
            return;
        }

        // cai_documents.year è nullable (un bilancio reale su RUNTS può non avere un anno estraibile dai
        // metadati), ma cai_financial_statements.year è NOT NULL e parte della chiave composita: nessun
        // valore sensato da scrivere in quel caso, no-op silenzioso invece di far fallire il job.
        if ($document->year === null) {
            return;
        }

        $pdfContent = Storage::disk('cai-documents')->get((string) $document->file_path);

        if ($pdfContent === null) {
            return;
        }

        $result = $client->analyzeBilancio($pdfContent);
        $attributes = CaiFinancialStatementFieldMapper::mapFinancialStatement((object) $result);

        $existing = CaiFinancialStatement::query()
            ->where('cai_runts_registration_id', $document->cai_runts_registration_id)
            ->where('year', $document->year)
            ->first();

        if ($existing === null) {
            CaiFinancialStatement::create([
                'cai_runts_registration_id' => $document->cai_runts_registration_id,
                'year' => $document->year,
                ...$attributes,
            ]);

            return;
        }

        // Merge campo-per-campo, mai un update() cieco: per lo stesso (registrazione, anno) possono
        // esistere più documenti "bilancio_esercizio" con struttura diversa (es. RUNTS CAI Como: sia
        // "Mod. B - Rendiconto Gestionale" sia "Mod. A - Stato Patrimoniale", quest'ultimo non
        // interpretabile dalle stesse regex e che quindi torna quasi sempre null) — un valore null
        // della nuova analisi non deve MAI cancellare un valore reale già presente da un'analisi
        // precedente. Stesso principio già documentato per `upsert_bilancio()` nel prototipo Python
        // ("il migliore-di-tutti-i-file vince per campo"), non portato qui quando questo job è stato
        // scritto la prima volta — bug reale trovato verificando l'estrazione su dati veri.
        $merged = [];

        foreach ($attributes as $field => $newValue) {
            $merged[$field] = $newValue ?? $existing->getAttribute($field);
        }

        $existing->update($merged);
    }
}
