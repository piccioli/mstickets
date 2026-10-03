<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Queries;

use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use App\Domain\CaiDirectory\Models\CaiDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Documenti dell'anno (conto economico o stato patrimoniale, vedi `CaiFinancialDocumentKind`) candidati
 * all'analisi con {@see AnalyzeCaiFinancialStatementDocument}.
 * Un documento senza anno non è mai candidato (il job lo ignorerebbe comunque).
 */
final class CaiFinancialDocumentsToAnalyzeQuery
{
    /**
     * Tutti i documenti CE/SP dell'anno, analizzati o no, opzionalmente di una sola sezione.
     *
     * @return Builder<CaiDocument>
     */
    public static function forYear(int $year, ?string $sectionCode = null): Builder
    {
        $query = CaiDocument::query()
            ->forYear($year)
            ->where(function (Builder $q): void {
                $q->incomeStatement()->orWhere(fn (Builder $inner) => $inner->balanceSheet());
            });

        if ($sectionCode !== null) {
            $query->where(function (Builder $q) use ($sectionCode): void {
                $q->where('cai_documents.cai_section_id', $sectionCode)
                    ->orWhereIn('cai_documents.cai_runts_registration_id', function (QueryBuilder $sub) use ($sectionCode): void {
                        $sub->select('cai_runts_registrations.id_runts')
                            ->from('cai_runts_registrations')
                            ->where('cai_runts_registrations.cai_section_id', $sectionCode);
                    });
            });
        }

        return $query;
    }

    /**
     * @param  Builder<CaiDocument>  $query
     * @return Builder<CaiDocument>
     */
    public static function neverAnalyzed(Builder $query): Builder
    {
        return $query->whereNull('cai_documents.financial_analysis_status');
    }

    /**
     * Conteggio per regione (sezione collegata direttamente o via registrazione RUNTS; "N/D" se ignota).
     *
     * @param  Builder<CaiDocument>  $query
     * @return array<string, int>
     */
    public static function countByRegion(Builder $query): array
    {
        $region = "COALESCE(
            (select s.region from cai_sections s where s.codice_cai = cai_documents.cai_section_id),
            (select s.region from cai_runts_registrations r join cai_sections s on s.codice_cai = r.cai_section_id
                where r.id_runts = cai_documents.cai_runts_registration_id),
            (select r.region from cai_runts_registrations r where r.id_runts = cai_documents.cai_runts_registration_id),
            'N/D'
        )";

        $counts = [];

        foreach ($query->reorder()->selectRaw($region.' as doc_region, count(*) as total')->groupByRaw($region)->toBase()->get() as $row) {
            $counts[(string) $row->doc_region] = (int) $row->total;
        }

        ksort($counts);

        return $counts;
    }
}
