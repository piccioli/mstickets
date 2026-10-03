<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Queries;

use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * "Gruppi regionali con lo stato dei bilanci di un anno": gli utenti `GruppoRegionale` arricchiti, con UNA sola
 * aggregazione per regione (LEFT JOIN su una sub-query raggruppata, mai una query per riga), da
 * {@see CaiSectionFinancialYearQuery::forYear()}: `sections_count`, `with_financials_count` (sezioni con almeno
 * un file di conto economico o stato patrimoniale dell'anno), `income_parsed_count` e `coverage_percent`
 * (intero, `null` se la regione non ha sezioni).
 *
 * `cai_sections.region` è il nome della regione in maiuscolo ("EMILIA-ROMAGNA"): viene mappato sul valore
 * dell'enum {@see Region} con un `CASE` generato dall'enum, così il join con `users.region` non confronta mai
 * stringhe grezze scritte a mano.
 */
final class CaiRegionalGroupsQuery
{
    /**
     * @return Builder<User>
     */
    public static function forYear(int $year): Builder
    {
        $regionCase = 'CASE UPPER(s.region)';
        $bindings = [];

        foreach (Region::cases() as $region) {
            $regionCase .= ' WHEN ? THEN ?';
            $bindings[] = mb_strtoupper($region->label());
            $bindings[] = $region->value;
        }

        $regionCase .= ' END';

        // Due livelli: la CASE (con i suoi binding) sta solo nel livello interno, quello esterno raggruppa sulla
        // colonna `region_key` — su Postgres due CASE con binding posizionali non sono "la stessa espressione".
        $sections = DB::query()
            ->fromSub(CaiSectionFinancialYearQuery::forYear($year)->toBase(), 's')
            ->selectRaw($regionCase.' as region_key', $bindings)
            ->selectRaw('CASE WHEN s.has_income_statement_file OR s.has_balance_sheet_file THEN 1 ELSE 0 END as with_financials')
            ->selectRaw('CASE WHEN s.income_statement_parsed THEN 1 ELSE 0 END as income_parsed')
            ->whereNotNull('s.region');

        $aggregate = DB::query()
            ->fromSub($sections, 'r')
            ->select('r.region_key')
            ->selectRaw('COUNT(*) as sections_count')
            ->selectRaw('SUM(r.with_financials) as with_financials_count')
            ->selectRaw('SUM(r.income_parsed) as income_parsed_count')
            ->groupBy('r.region_key');

        return User::query()
            ->select('users.*')
            ->selectRaw('COALESCE(agg.sections_count, 0) as sections_count')
            ->selectRaw('COALESCE(agg.with_financials_count, 0) as with_financials_count')
            ->selectRaw('COALESCE(agg.income_parsed_count, 0) as income_parsed_count')
            ->selectRaw('CASE WHEN COALESCE(agg.sections_count, 0) > 0 THEN ROUND(100.0 * agg.with_financials_count / agg.sections_count) END as coverage_percent')
            ->leftJoinSub($aggregate, 'agg', 'agg.region_key', '=', 'users.region')
            ->where('users.customer_type', CustomerType::GruppoRegionale);
    }
}
