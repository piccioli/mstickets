<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Queries;

use App\Domain\CaiDirectory\Enums\CaiRuntsPresenceStatus;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\CaiDirectory\Support\CaiBalanceSheetSummary;
use App\Domain\CaiDirectory\Support\CaiIncomeStatementSummary;
use App\Domain\CaiDirectory\Support\CaiSectionDashboardData;
use App\Domain\Identity\Enums\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Metriche di sola lettura della Dashboard V2 (Fase 10.1). Un bilancio è "della Sezione" sia se collegato
 * direttamente (`cai_section_id`) sia se collegato a una sua `CaiRuntsRegistration` — stessa regola di
 * {@see CaiSectionFinancialYearQuery}. Una sola query sui bilanci per Sezione.
 *
 * Soci = `members_count` della Sezione così com'è: i soci delle Sottosezioni non vengono mai sommati.
 *
 * Terzo Settore: iscritta se ha una `CaiRuntsRegistration` collegata o se la verifica di presenza l'ha trovata
 * (`Registered`); in ogni altro caso (mai verificata, timeout, non presente) "non iscritta" — scelta del committente.
 */
final class CaiSectionDashboardMetrics
{
    public static function forSection(CaiSection $section): CaiSectionDashboardData
    {
        $statements = self::statementsOf($section);

        $income = $statements->first(fn (CaiFinancialStatement $s): bool => $s->hasIncomeStatementData());
        $balance = $statements->first(fn (CaiFinancialStatement $s): bool => $s->hasBalanceSheetData());

        return new CaiSectionDashboardData(
            name: $section->name,
            codiceCai: $section->codice_cai,
            region: self::regionLabel($section->region),
            members: $section->members_count,
            incomeStatement: $income === null ? null : new CaiIncomeStatementSummary(
                year: $income->year,
                revenues: self::amount($income->total_revenues),
                expenses: self::amount($income->total_expenses),
                netResult: self::amount($income->net_result),
            ),
            balanceSheet: $balance === null ? null : new CaiBalanceSheetSummary(
                year: $balance->year,
                netEquity: self::amount($balance->net_equity),
                totalAssets: self::amount($balance->total_assets),
            ),
            thirdSectorRegistered: $section->runts_presence_status === CaiRuntsPresenceStatus::Registered
                || $section->runtsRegistrations()->exists(),
        );
    }

    /**
     * Le iscrizioni RUNTS sono collegate solo alle Sezioni: una Sottosezione non ha mai bilanci.
     * La regione si legge dalla Sezione padre.
     */
    public static function forSubsection(CaiSubsection $subsection): CaiSectionDashboardData
    {
        return new CaiSectionDashboardData(
            name: $subsection->name,
            codiceCai: $subsection->cai_codice,
            region: self::regionLabel($subsection->section?->region),
            members: $subsection->members_count,
            incomeStatement: null,
            balanceSheet: null,
        );
    }

    /**
     * Bilanci della Sezione dal più recente; a parità di anno prima quello collegato all'iscrizione RUNTS.
     *
     * @return Collection<int, CaiFinancialStatement>
     */
    private static function statementsOf(CaiSection $section): Collection
    {
        return CaiFinancialStatement::query()
            ->where(function (Builder $q) use ($section): void {
                $q->where('cai_section_id', $section->codice_cai)
                    ->orWhereIn('cai_runts_registration_id', function (QueryBuilder $sub) use ($section): void {
                        $sub->select('id_runts')
                            ->from('cai_runts_registrations')
                            ->where('cai_section_id', $section->codice_cai);
                    });
            })
            ->orderByDesc('year')
            ->orderByRaw('CASE WHEN cai_runts_registration_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('id')
            ->get();
    }

    /**
     * `cai_sections.region` è testo libero in maiuscolo ("EMILIA-ROMAGNA", "EXTRA REGIONE"): le 20 regioni
     * ufficiali prendono l'etichetta dell'enum {@see Region}, il resto solo la maiuscola iniziale.
     */
    private static function regionLabel(?string $region): ?string
    {
        $region = trim((string) $region);

        if ($region === '') {
            return null;
        }

        $upper = mb_strtoupper($region);

        foreach (Region::cases() as $case) {
            if (mb_strtoupper($case->label()) === $upper) {
                return $case->label();
            }
        }

        $lower = mb_strtolower($region);

        return mb_strtoupper(mb_substr($lower, 0, 1)).mb_substr($lower, 1);
    }

    private static function amount(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
