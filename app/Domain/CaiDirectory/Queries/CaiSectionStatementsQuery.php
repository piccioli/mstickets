<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Queries;

use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use Illuminate\Database\Eloquent\Collection;

/**
 * Bilanci di un insieme di Sezioni con UNA sola query, condivisa dalla Dashboard V2 Sezione (10.1) e dal
 * consolidato del Gruppo Regionale (10.2). Un bilancio è "della Sezione" sia se collegato direttamente
 * (`cai_section_id`) sia se collegato a una sua `CaiRuntsRegistration` — stessa regola di
 * {@see CaiSectionFinancialYearQuery}. Ogni riga espone `owner_section_code`, il `codice_cai` della Sezione a
 * cui appartiene (i due genitori sono mutuamente esclusivi).
 *
 * Ordine: dal più recente; a parità di anno prima quello collegato all'iscrizione RUNTS, così il primo bilancio
 * di un anno che soddisfa un criterio è quello che "vince".
 */
final class CaiSectionStatementsQuery
{
    /**
     * @param  list<string>  $sectionCodes
     * @return Collection<int, CaiFinancialStatement>
     */
    public static function forSections(array $sectionCodes): Collection
    {
        if ($sectionCodes === []) {
            return new Collection;
        }

        return CaiFinancialStatement::query()
            ->select('cai_financial_statements.*')
            ->selectRaw('COALESCE(cai_financial_statements.cai_section_id, r.cai_section_id) as owner_section_code')
            ->leftJoin('cai_runts_registrations as r', 'r.id_runts', '=', 'cai_financial_statements.cai_runts_registration_id')
            ->where(function ($q) use ($sectionCodes): void {
                $q->whereIn('cai_financial_statements.cai_section_id', $sectionCodes)
                    ->orWhereIn('r.cai_section_id', $sectionCodes);
            })
            ->orderByDesc('cai_financial_statements.year')
            ->orderByRaw('CASE WHEN cai_financial_statements.cai_runts_registration_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('cai_financial_statements.id')
            ->get();
    }
}
