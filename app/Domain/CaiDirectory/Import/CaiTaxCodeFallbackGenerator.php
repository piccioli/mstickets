<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use App\Domain\CaiDirectory\Models\CaiSection;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader;

/**
 * Genera la mappa `codice_cai` => nome/CF/PIVA a partire dal foglio Excel "Sezioni CAI con
 * CF e P.IVA" (fonte manuale, mai nel datapack RUNTS-CAI) — usata da
 * `cai:generate-tax-code-fallback` per scrivere `resources/data/cai/tax-code-fallback.json`
 * (US fallback CF/PIVA).
 *
 * **Il foglio non contiene `codice_cai`**, solo la denominazione della sezione in un
 * formato testuale diverso da `cai_sections.name` (es. "C.A.I. SEZIONE DI ABBIATEGRASSO"
 * nel foglio vs "SEZ. ABBIATEGRASSO" nel datapack RUNTS-CAI): il match avviene per nome
 * normalizzato (prefissi "C.A.I. SEZIONE (DI)"/"SEZ.", forme giuridiche APS/ETS/ONLUS/ODV,
 * accenti, spazi multipli). Verificato contro il dataset reale: ~88% delle righe del
 * foglio trova un match univoco: le righe "GR <regione>" (Gruppi Regionali, non sezioni)
 * restano correttamente senza match, insieme a un residuo di nomi troppo abbreviati/diversi
 * per il confronto — riportate come `unmatchedNames`, mai scartate in silenzio.
 */
final class CaiTaxCodeFallbackGenerator
{
    private const HEADER_NAME_CELL = 'Sezione / Gruppo Regionale';

    public function generate(string $xlsxPath): CaiTaxCodeFallbackGenerationResult
    {
        $codeByNormalizedName = $this->buildNormalizedNameIndex();

        $entries = [];
        $unmatchedNames = [];

        $reader = new Reader;
        $reader->open($xlsxPath);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->toArray();
                $name = trim((string) ($cells[0] ?? ''));
                $taxCode = trim((string) ($cells[1] ?? ''));
                $vatNumber = trim((string) ($cells[2] ?? ''));

                // Il foglio reale ripete l'intestazione una seconda volta a metà file
                // (l'intera lista appare due volte di seguito): confrontare il testo
                // dell'intestazione, non solo saltare la prima riga, evita di trattarla
                // come una sezione "senza corrispondenza".
                if ($name === self::HEADER_NAME_CELL) {
                    continue;
                }

                if ($name === '' || ($taxCode === '' && $vatNumber === '')) {
                    continue;
                }

                $normalized = $this->normalizeName($name);
                $codiceCai = $codeByNormalizedName[$normalized] ?? null;

                if ($codiceCai === null) {
                    $unmatchedNames[] = $name;

                    continue;
                }

                $entries[$codiceCai] = [
                    'name' => $name,
                    'tax_code' => $taxCode !== '' ? $taxCode : null,
                    'vat_number' => $vatNumber !== '' ? $vatNumber : null,
                ];
            }
        }

        $reader->close();

        return new CaiTaxCodeFallbackGenerationResult($entries, array_values(array_unique($unmatchedNames)));
    }

    /**
     * @return array<string, string>
     */
    private function buildNormalizedNameIndex(): array
    {
        $index = [];
        $ambiguous = [];

        CaiSection::query()->pluck('name', 'codice_cai')->each(function (string $name, string $codiceCai) use (&$index, &$ambiguous): void {
            $normalized = $this->normalizeName($name);

            if (array_key_exists($normalized, $index)) {
                $ambiguous[$normalized] = true;

                return;
            }

            $index[$normalized] = $codiceCai;
        });

        // Due sezioni che normalizzano allo stesso nome non possono essere distinte dal
        // solo testo: meglio nessun match (riportato come "unmatched" a valle) che uno
        // sbagliato assegnato alla prima trovata.
        foreach (array_keys($ambiguous) as $normalized) {
            unset($index[$normalized]);
        }

        return $index;
    }

    private function normalizeName(string $name): string
    {
        $ascii = mb_strtoupper(Str::ascii($name));
        $withoutPrefix = preg_replace('/^C\.?A\.?I\.?\s+SEZIONE\s+(DI\s+)?/', '', $ascii) ?? $ascii;
        $withoutPrefix = preg_replace('/^SEZ\.?\s+/', '', $withoutPrefix) ?? $withoutPrefix;
        $withoutLegalForm = preg_replace('/[\s\-]*\b(APS|ETS|ODV|ONLUS)\b[\s\-]*/', ' ', $withoutPrefix) ?? $withoutPrefix;
        $lettersAndDigitsOnly = preg_replace('/[^A-Z0-9]+/', ' ', $withoutLegalForm) ?? $withoutLegalForm;

        return trim(preg_replace('/\s+/', ' ', $lettersAndDigitsOnly) ?? $lettersAndDigitsOnly);
    }
}
