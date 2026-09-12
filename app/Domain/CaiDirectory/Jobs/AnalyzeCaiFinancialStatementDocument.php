<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Jobs;

use App\Domain\CaiDirectory\Import\CaiFinancialStatementFieldMapper;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Support\CaiRuntsScraperClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
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

        $this->upsertMerging($document, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertMerging(CaiDocument $document, array $attributes): void
    {
        // DB::transaction()+lockForUpdate() (mai una semplice read-then-write): la coda dedicata
        // 'cai-runts-analysis' ha più worker paralleli (config/horizon.php), e per lo stesso
        // (registrazione, anno) possono esistere più documenti "bilancio_esercizio" con struttura
        // diversa (es. RUNTS CAI Como: sia "Mod. B - Rendiconto Gestionale" sia "Mod. A - Stato
        // Patrimoniale", quest'ultimo non interpretabile dalle stesse regex e che quindi torna quasi
        // sempre null). Senza il lock, due job concorrenti per lo stesso anno possono entrambi
        // leggere la riga PRIMA che l'altro scriva: il merge campo-per-campo (§ sotto, "il valore
        // non-null vince") da solo NON basta a prevenire questa race — bug reale riprodotto
        // verificando l'estrazione su dati veri (CAI Como, 2 documenti per l'anno 2025 processati in
        // parallelo, il secondo sovrascriveva con null i valori appena scritti dal primo). Stesso
        // principio già in uso da `RecordTicketView` (Fase 1, US-108) per lo stesso tipo di race.
        DB::transaction(function () use ($document, $attributes): void {
            $existing = CaiFinancialStatement::query()
                ->where('cai_runts_registration_id', $document->cai_runts_registration_id)
                ->where('year', $document->year)
                ->lockForUpdate()
                ->first();

            if ($existing === null) {
                $this->createOrFallBackToMerge($document, $attributes);

                return;
            }

            $merged = [];

            foreach ($attributes as $field => $newValue) {
                $merged[$field] = $newValue ?? $existing->getAttribute($field);
            }

            $existing->update($merged);
        });
    }

    /**
     * Anche con il lock sopra, una riga che non esiste ancora non può essere "bloccata" in anticipo:
     * se due job per lo stesso (registrazione, anno) risultano ENTRAMBI al loro primissimo giro
     * (nessuna riga da trovare) e tentano una create() quasi simultanea, il vincolo unique
     * (cai_runts_registration_id, year) fa fallire il secondo con una QueryException — qui
     * catturata esplicitamente e ritrattata come un merge sulla riga ormai creata dall'altro job,
     * mai lasciata propagare come un fallimento del job.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createOrFallBackToMerge(CaiDocument $document, array $attributes): void
    {
        try {
            CaiFinancialStatement::create([
                'cai_runts_registration_id' => $document->cai_runts_registration_id,
                'year' => $document->year,
                ...$attributes,
            ]);
        } catch (QueryException) {
            $existing = CaiFinancialStatement::query()
                ->where('cai_runts_registration_id', $document->cai_runts_registration_id)
                ->where('year', $document->year)
                ->lockForUpdate()
                ->firstOrFail();

            $merged = [];

            foreach ($attributes as $field => $newValue) {
                $merged[$field] = $newValue ?? $existing->getAttribute($field);
            }

            $existing->update($merged);
        }
    }
}
