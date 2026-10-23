<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Queries;

use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Support\CaiConsolidatedBalanceSheetSummary;
use App\Domain\CaiDirectory\Support\CaiConsolidatedIncomeStatementSummary;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupDashboardData;
use App\Domain\Identity\Enums\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Metriche di sola lettura della Dashboard V2 del Gruppo Regionale (Fase 10.2). Il GR non esiste come ente:
 *
 * - **prefisso del GR** = il prefisso di 4 caratteri di `codice_cai` più frequente fra le Sezioni la cui `region`
 *   è quella del GR (a parità vince il numericamente più alto); **codice CAI del GR** = prefisso + `000`;
 * - **Sezioni del GR** = tutte le `CaiSection` con quel prefisso, qualunque sia la loro `region` (la SAT `9218…`
 *   resta fuori dal GR `9219`, Sappada `9220…` in FVG entra nel Veneto). Le Sottosezioni non contano mai;
 * - **consolidato** = un solo esercizio, il più recente in cui almeno una Sezione ha dati, sommando solo le
 *   Sezioni con dati in quell'anno e solo i valori non nulli (un totale senza valori resta `null`, mai 0). Conto
 *   economico e stato patrimoniale scelgono l'esercizio indipendentemente.
 *
 * - **rifugi** (10.5) = quelli di {@see CaiRegionalGroupHuts} (Sezioni del GR + intestati al GR), contati con una sola
 *   query; la nota conta le Sezioni con almeno un rifugio. Zero rifugi è un dato noto (0), non `null`.
 * - **sentieri** (10.7) = km nel territorio regionale di {@see CaiRegionalGroupTrails::territoryKm()} (km intersecati
 *   con la regione osm2cai, anche dei sentieri senza Sezione); regione assente dal datapack o senza km → 0, non `null`.
 * - **tappe SICAI** (10.9) = numero di tappe e km delle principali di {@see CaiRegionalGroupSicai::forRegion()} (una
 *   sola query); nessuna tappa → 0, non `null`.
 *
 * Il bilancio di una Sezione per un anno segue le regole della 10.1 ({@see CaiSectionStatementsQuery}, a parità
 * di anno vince quello RUNTS), con una sola query per tutti i bilanci del GR.
 */
final class CaiRegionalGroupDashboardMetrics
{
    public static function forRegion(Region $region): CaiRegionalGroupDashboardData
    {
        $prefix = self::prefixOf($region);

        if ($prefix === null) {
            return new CaiRegionalGroupDashboardData(
                codiceCai: null,
                sectionsCount: null,
                members: null,
                membersSectionsCount: null,
                consolidatedIncomeStatement: null,
                consolidatedBalanceSheet: null,
            );
        }

        $sections = self::sectionsOf($prefix)->get(['codice_cai', 'members_count']);

        $withMembers = $sections->filter(fn (CaiSection $s): bool => $s->members_count !== null);

        /** @var list<string> $codes */
        $codes = $sections->pluck('codice_cai')->values()->all();
        $statements = CaiSectionStatementsQuery::forSections($codes)->toBase();

        /** @var array<string, int> $hutsBySection `branch_code` → numero di rifugi */
        $hutsBySection = CaiRegionalGroupHuts::hutsQuery($codes, CaiRegionalGroupHuts::groupName($region))
            ->toBase()
            ->selectRaw('branch_code, COUNT(*) AS huts_count')
            ->groupBy('branch_code')
            ->pluck('huts_count', 'branch_code')
            ->mapWithKeys(fn (mixed $count, mixed $code): array => [(string) $code => (int) $count])
            ->all();

        $sicaiRows = CaiRegionalGroupSicai::stageRows($region);

        return new CaiRegionalGroupDashboardData(
            codiceCai: $prefix.'000',
            sectionsCount: $sections->count(),
            members: $withMembers->isEmpty() ? null : (int) $withMembers->sum('members_count'),
            membersSectionsCount: $withMembers->count(),
            consolidatedIncomeStatement: self::consolidatedIncomeStatement($statements),
            consolidatedBalanceSheet: self::consolidatedBalanceSheet($statements, $sections->count()),
            huts: array_sum($hutsBySection),
            hutsSectionsCount: count(array_intersect_key($hutsBySection, array_flip($codes))),
            trailsKm: CaiRegionalGroupTrails::territoryKm($region),
            sicaiStages: count($sicaiRows),
            sicaiStagesKm: CaiRegionalGroupSicai::mainKm($sicaiRows),
        );
    }

    /**
     * Le Sezioni del GR con quel prefisso, qualunque sia la loro `region` (regola condivisa con il consolidato 10.3).
     *
     * @return Builder<CaiSection>
     */
    public static function sectionsOf(string $prefix): Builder
    {
        return CaiSection::query()->where('codice_cai', 'like', $prefix.'%');
    }

    /**
     * Il prefisso di 4 caratteri del GR della regione, `null` se la regione non ha Sezioni.
     */
    public static function prefixOf(Region $region): ?string
    {
        $codes = CaiSection::query()
            ->whereRaw('UPPER(region) = ?', [mb_strtoupper($region->label())])
            ->pluck('codice_cai');

        $best = null;
        $bestCount = 0;

        foreach ($codes->countBy(fn (string $code): string => mb_substr($code, 0, 4)) as $prefix => $count) {
            $prefix = (string) $prefix;

            if ($count > $bestCount || ($count === $bestCount && (int) $prefix > (int) $best)) {
                $best = $prefix;
                $bestCount = $count;
            }
        }

        return $best;
    }

    /**
     * @param  Collection<int, CaiFinancialStatement>  $statements
     */
    private static function consolidatedIncomeStatement(Collection $statements): ?CaiConsolidatedIncomeStatementSummary
    {
        $picked = self::pickLatestYear($statements, fn (CaiFinancialStatement $s): bool => $s->hasIncomeStatementData());

        if ($picked === null) {
            return null;
        }

        return new CaiConsolidatedIncomeStatementSummary(
            year: $picked['year'],
            sectionsCount: $picked['statements']->count(),
            revenues: self::sum($picked['statements'], 'total_revenues'),
            expenses: self::sum($picked['statements'], 'total_expenses'),
            netResult: self::sum($picked['statements'], 'net_result'),
        );
    }

    /**
     * @param  Collection<int, CaiFinancialStatement>  $statements
     */
    private static function consolidatedBalanceSheet(Collection $statements, int $totalSections): ?CaiConsolidatedBalanceSheetSummary
    {
        $picked = self::pickLatestYear($statements, fn (CaiFinancialStatement $s): bool => $s->hasBalanceSheetData());

        if ($picked === null) {
            return null;
        }

        return new CaiConsolidatedBalanceSheetSummary(
            year: $picked['year'],
            sectionsCount: $picked['statements']->count(),
            totalSections: $totalSections,
            netEquity: self::sum($picked['statements'], 'net_equity'),
            totalAssets: self::sum($picked['statements'], 'total_assets'),
        );
    }

    /**
     * L'esercizio più recente con almeno un bilancio che soddisfa `$hasData` e, per ogni Sezione, il suo primo
     * bilancio di quell'anno che lo soddisfa (l'ordine di {@see CaiSectionStatementsQuery} fa vincere quello
     * RUNTS, come nella 10.1).
     *
     * @param  Collection<int, CaiFinancialStatement>  $statements
     * @param  callable(CaiFinancialStatement): bool  $hasData
     * @return array{year: int, statements: Collection<int, CaiFinancialStatement>}|null
     */
    private static function pickLatestYear(Collection $statements, callable $hasData): ?array
    {
        $withData = $statements->filter($hasData);
        $year = $withData->max('year');

        if ($year === null) {
            return null;
        }

        $picked = $withData
            ->filter(fn (CaiFinancialStatement $s): bool => $s->year === $year)
            ->unique(fn (CaiFinancialStatement $s): string => (string) $s->getAttribute('owner_section_code'))
            ->values();

        return ['year' => (int) $year, 'statements' => $picked];
    }

    /**
     * @param  Collection<int, CaiFinancialStatement>  $statements
     */
    private static function sum(Collection $statements, string $attribute): ?float
    {
        $values = $statements
            ->map(fn (CaiFinancialStatement $s): mixed => $s->getAttribute($attribute))
            ->reject(fn (mixed $value): bool => $value === null);

        return $values->isEmpty() ? null : round($values->sum(fn (mixed $value): float => (float) $value), 2);
    }
}
