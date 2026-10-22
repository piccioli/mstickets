<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Queries;

use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Enums\CaiSectionStatementStatus;
use App\Domain\CaiDirectory\Enums\CaiStatementSource;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Support\CaiConsolidatedSectionRow;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupConsolidatedData;
use App\Domain\Identity\Enums\Region;
use Illuminate\Support\Collection;

/**
 * Bilancio consolidato delle Sezioni di un Gruppo Regionale per un esercizio (Fase 10.3), di sola lettura.
 * Sezioni e codice del GR seguono le regole di prefisso di {@see CaiRegionalGroupDashboardMetrics}.
 *
 * - **Esercizi disponibili**: i 5 anni che finiscono con l'anno più alto fra bilanci e documenti; **default** = il
 *   più recente con almeno una Sezione in "Dati presenti", altrimenti il più recente dei 5. Un anno fuori dai 5 →
 *   default.
 * - **Stato**: "Dati presenti" se la Sezione ha un bilancio dell'anno con cifre di conto economico o stato
 *   patrimoniale; i due si scelgono indipendentemente come nella 10.1 (ordine di {@see CaiSectionStatementsQuery}, a
 *   parità vince RUNTS); altrimenti "Solo PDF" se la Sezione ha un
 *   file di bilancio dell'anno ({@see CaiSectionFinancialYearQuery::financialDocumentsOf()}); altrimenti "Nessun bilancio".
 * - **Da verificare**: ricavi, costi, patrimonio netto e totale attivo (mai il risultato) il cui valore assoluto
 *   supera 10× la mediana dei valori assoluti non nulli della voce fra le Sezioni con dati, se questi sono almeno 3.
 *
 * Query costanti nel numero di Sezioni: anni, prefisso, Sezioni, bilanci, documenti.
 */
final class CaiRegionalGroupConsolidatedStatements
{
    public const YEARS = 5;

    private const OUTLIER_FACTOR = 10;

    private const OUTLIER_MIN_VALUES = 3;

    /** Le voci che possono essere "da verificare", con la card che le include. */
    private const FLAGGABLE = [
        CaiConsolidatedSectionRow::REVENUES => 'income',
        CaiConsolidatedSectionRow::EXPENSES => 'income',
        CaiConsolidatedSectionRow::NET_EQUITY => 'balance',
        CaiConsolidatedSectionRow::TOTAL_ASSETS => 'balance',
    ];

    public static function forRegion(Region $region, ?int $year = null): CaiRegionalGroupConsolidatedData
    {
        $availableYears = self::availableYears();
        $prefix = CaiRegionalGroupDashboardMetrics::prefixOf($region);

        /** @var Collection<int, CaiSection> $sections */
        $sections = $prefix === null
            ? collect()
            : CaiRegionalGroupDashboardMetrics::sectionsOf($prefix)->orderBy('name')->orderBy('codice_cai')->get(['codice_cai', 'name']);

        /** @var list<string> $codes */
        $codes = $sections->pluck('codice_cai')->values()->all();

        $statements = CaiSectionStatementsQuery::forSections($codes)
            ->toBase()
            ->filter(fn (CaiFinancialStatement $s): bool => in_array($s->year, $availableYears, true)
                && ($s->hasIncomeStatementData() || $s->hasBalanceSheetData()));

        if ($year === null || ! in_array($year, $availableYears, true)) {
            $year = (int) ($statements->max('year') ?? max($availableYears));
        }

        // Come nella 10.1/10.2 conto economico e stato patrimoniale si scelgono ciascuno col primo bilancio dell'anno
        // che ne ha i dati: l'ordine della query fa vincere quello RUNTS.
        $ofYear = $statements->filter(fn (CaiFinancialStatement $s): bool => $s->year === $year);
        $incomeBySection = self::firstBySection($ofYear->filter(fn (CaiFinancialStatement $s): bool => $s->hasIncomeStatementData()));
        $balanceBySection = self::firstBySection($ofYear->filter(fn (CaiFinancialStatement $s): bool => $s->hasBalanceSheetData()));
        $anyBySection = self::firstBySection($ofYear);

        $documentsBySection = ($codes === [] ? collect() : CaiSectionFinancialYearQuery::financialDocumentsOf($codes, $year)->get())
            ->groupBy(fn (CaiDocument $d): string => (string) $d->getAttribute('owner_section_code'));

        $rows = $sections->map(fn (CaiSection $section): CaiConsolidatedSectionRow => self::row(
            $section,
            $anyBySection->get($section->codice_cai),
            $incomeBySection->get($section->codice_cai),
            $balanceBySection->get($section->codice_cai),
            $documentsBySection->get($section->codice_cai, collect()),
        ));

        $rows = self::flagOutliers($rows);
        $withData = $rows->filter(fn (CaiConsolidatedSectionRow $r): bool => $r->status === CaiSectionStatementStatus::Data);

        return new CaiRegionalGroupConsolidatedData(
            codiceCai: $prefix === null ? null : $prefix.'000',
            year: $year,
            availableYears: $availableYears,
            sectionsCount: $rows->count(),
            rows: $rows->values()->all(),
            revenues: self::sum($withData, 'revenues'),
            expenses: self::sum($withData, 'expenses'),
            netResult: self::sum($withData, 'netResult'),
            netEquity: self::sum($withData, 'netEquity'),
            totalAssets: self::sum($withData, 'totalAssets'),
            incomeStatementSectionsCount: $withData->filter(fn (CaiConsolidatedSectionRow $r): bool => $r->hasIncomeStatementValues())->count(),
            balanceSheetSectionsCount: $withData->filter(fn (CaiConsolidatedSectionRow $r): bool => $r->hasBalanceSheetValues())->count(),
            incomeStatementFlaggedCount: self::flaggedCount($rows, 'income'),
            balanceSheetFlaggedCount: self::flaggedCount($rows, 'balance'),
            dataCount: $withData->count(),
            pdfOnlyCount: $rows->filter(fn (CaiConsolidatedSectionRow $r): bool => $r->status === CaiSectionStatementStatus::PdfOnly)->count(),
            noneCount: $rows->filter(fn (CaiConsolidatedSectionRow $r): bool => $r->status === CaiSectionStatementStatus::None)->count(),
        );
    }

    /**
     * I 5 esercizi che finiscono con l'anno più alto fra bilanci e documenti (l'anno corrente se non ce ne sono),
     * in ordine crescente.
     *
     * @return list<int>
     */
    public static function availableYears(): array
    {
        $latest = max(
            (int) CaiFinancialStatement::query()->max('year'),
            (int) CaiDocument::query()->max('year'),
        );

        if ($latest === 0) {
            $latest = (int) now()->year;
        }

        return range($latest - self::YEARS + 1, $latest);
    }

    /**
     * @param  Collection<int, CaiFinancialStatement>  $statements
     * @return Collection<string, CaiFinancialStatement>
     */
    private static function firstBySection(Collection $statements): Collection
    {
        return $statements
            ->unique(fn (CaiFinancialStatement $s): string => (string) $s->getAttribute('owner_section_code'))
            ->keyBy(fn (CaiFinancialStatement $s): string => (string) $s->getAttribute('owner_section_code'));
    }

    /**
     * `$statement` è il primo bilancio dell'anno con dati (decide la fonte), `$income`/`$balance` quelli che danno
     * le cifre di conto economico e di stato patrimoniale.
     *
     * @param  Collection<int, CaiDocument>  $documents  file di bilancio dell'anno, prima quelli RUNTS
     */
    private static function row(
        CaiSection $section,
        ?CaiFinancialStatement $statement,
        ?CaiFinancialStatement $income,
        ?CaiFinancialStatement $balance,
        Collection $documents,
    ): CaiConsolidatedSectionRow {
        $pdf = $documents->first(fn (CaiDocument $d): bool => $d->file_path !== null);

        if ($statement !== null) {
            return new CaiConsolidatedSectionRow(
                name: $section->name,
                codiceCai: $section->codice_cai,
                status: CaiSectionStatementStatus::Data,
                source: $statement->cai_runts_registration_id !== null ? CaiStatementSource::Runts : CaiStatementSource::Manual,
                revenues: self::amount($income?->total_revenues),
                expenses: self::amount($income?->total_expenses),
                netResult: self::amount($income?->net_result),
                netEquity: self::amount($balance?->net_equity),
                totalAssets: self::amount($balance?->total_assets),
                documentId: $pdf?->id,
            );
        }

        /** @var CaiDocument|null $document */
        $document = $pdf ?? $documents->first();

        return new CaiConsolidatedSectionRow(
            name: $section->name,
            codiceCai: $section->codice_cai,
            status: $document === null ? CaiSectionStatementStatus::None : CaiSectionStatementStatus::PdfOnly,
            source: $document === null ? null : self::documentSource($document),
            revenues: null,
            expenses: null,
            netResult: null,
            netEquity: null,
            totalAssets: null,
            documentId: $pdf?->id,
        );
    }

    private static function documentSource(CaiDocument $document): CaiStatementSource
    {
        return $document->cai_runts_registration_id !== null && $document->source !== CaiDocumentSource::Manual
            ? CaiStatementSource::Runts
            : CaiStatementSource::Manual;
    }

    /**
     * @param  Collection<int, CaiConsolidatedSectionRow>  $rows
     * @return Collection<int, CaiConsolidatedSectionRow>
     */
    private static function flagOutliers(Collection $rows): Collection
    {
        $withData = $rows->filter(fn (CaiConsolidatedSectionRow $r): bool => $r->status === CaiSectionStatementStatus::Data);

        $thresholds = [];

        foreach (array_keys(self::FLAGGABLE) as $item) {
            $values = $withData
                ->map(fn (CaiConsolidatedSectionRow $r): ?float => $r->{$item})
                ->reject(fn (?float $v): bool => $v === null)
                ->map(fn (float $v): float => abs($v));

            if ($values->count() >= self::OUTLIER_MIN_VALUES) {
                $thresholds[$item] = (float) $values->median() * self::OUTLIER_FACTOR;
            }
        }

        if ($thresholds === []) {
            return $rows;
        }

        return $rows->map(function (CaiConsolidatedSectionRow $r) use ($thresholds): CaiConsolidatedSectionRow {
            $flagged = [];

            foreach ($thresholds as $item => $threshold) {
                $value = $r->{$item};

                if ($value !== null && abs($value) > $threshold) {
                    $flagged[] = $item;
                }
            }

            return $flagged === [] ? $r : new CaiConsolidatedSectionRow(
                name: $r->name,
                codiceCai: $r->codiceCai,
                status: $r->status,
                source: $r->source,
                revenues: $r->revenues,
                expenses: $r->expenses,
                netResult: $r->netResult,
                netEquity: $r->netEquity,
                totalAssets: $r->totalAssets,
                documentId: $r->documentId,
                flagged: $flagged,
            );
        });
    }

    /**
     * @param  Collection<int, CaiConsolidatedSectionRow>  $rows
     */
    private static function flaggedCount(Collection $rows, string $card): int
    {
        $items = array_keys(array_filter(self::FLAGGABLE, fn (string $c): bool => $c === $card));

        return $rows->sum(fn (CaiConsolidatedSectionRow $r): int => count(array_intersect($r->flagged, $items)));
    }

    /**
     * @param  Collection<int, CaiConsolidatedSectionRow>  $rows
     */
    private static function sum(Collection $rows, string $item): ?float
    {
        $values = $rows->map(fn (CaiConsolidatedSectionRow $r): ?float => $r->{$item})
            ->reject(fn (?float $v): bool => $v === null);

        return $values->isEmpty() ? null : round((float) $values->sum(), 2);
    }

    private static function amount(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
