<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Queries;

use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * "Stato bilancio per sezione e anno": arricchisce le `CaiSection` con quattro flag booleani calcolati con
 * sub-query `EXISTS` correlate (una sola query per pagina di lista, nessun `having` su alias):
 * `has_income_statement_file`, `has_balance_sheet_file`, `income_statement_parsed`, `balance_sheet_parsed`.
 *
 * Un documento/bilancio è "della sezione" sia se collegato direttamente (`cai_section_id`) sia se collegato a una
 * sua `CaiRuntsRegistration`. Le condizioni vivono in un solo posto (i metodi `*Exists()`): sia la colonna
 * booleana sia i filtri `with*()` le riusano, quindi non possono divergere.
 */
final class CaiSectionFinancialYearQuery
{
    private const FLAGS = [
        'has_income_statement_file' => 'incomeStatementFileExists',
        'has_balance_sheet_file' => 'balanceSheetFileExists',
        'income_statement_parsed' => 'incomeStatementParsedExists',
        'balance_sheet_parsed' => 'balanceSheetParsedExists',
    ];

    /**
     * @return Builder<CaiSection>
     */
    public static function forYear(int $year): Builder
    {
        $query = CaiSection::query()->select('cai_sections.*');

        foreach (self::FLAGS as $alias => $method) {
            $exists = self::{$method}($year)->toBase();

            $query->selectRaw('EXISTS ('.$exists->toSql().') as '.$alias, $exists->getBindings());
        }

        return $query;
    }

    /**
     * @param  Builder<CaiSection>  $query
     * @return Builder<CaiSection>
     */
    public static function withIncomeStatementFile(Builder $query, int $year, bool $value = true): Builder
    {
        return self::filter($query, self::incomeStatementFileExists($year), $value);
    }

    /**
     * @param  Builder<CaiSection>  $query
     * @return Builder<CaiSection>
     */
    public static function withBalanceSheetFile(Builder $query, int $year, bool $value = true): Builder
    {
        return self::filter($query, self::balanceSheetFileExists($year), $value);
    }

    /**
     * @param  Builder<CaiSection>  $query
     * @return Builder<CaiSection>
     */
    public static function withIncomeStatementParsed(Builder $query, int $year, bool $value = true): Builder
    {
        return self::filter($query, self::incomeStatementParsedExists($year), $value);
    }

    /**
     * @param  Builder<CaiSection>  $query
     * @return Builder<CaiSection>
     */
    public static function withBalanceSheetParsed(Builder $query, int $year, bool $value = true): Builder
    {
        return self::filter($query, self::balanceSheetParsedExists($year), $value);
    }

    /**
     * @param  Builder<CaiSection>  $query
     * @param  Builder<CaiDocument>|Builder<CaiFinancialStatement>  $exists
     * @return Builder<CaiSection>
     */
    private static function filter(Builder $query, Builder $exists, bool $value): Builder
    {
        $sql = 'EXISTS ('.$exists->toBase()->toSql().')';

        return $query->whereRaw(($value ? '' : 'NOT ').$sql, $exists->toBase()->getBindings());
    }

    /**
     * @return Builder<CaiDocument>
     */
    private static function incomeStatementFileExists(int $year): Builder
    {
        return self::documentsOfSection($year)->incomeStatement();
    }

    /**
     * @return Builder<CaiDocument>
     */
    private static function balanceSheetFileExists(int $year): Builder
    {
        return self::documentsOfSection($year)->balanceSheet();
    }

    /**
     * @return Builder<CaiFinancialStatement>
     */
    private static function incomeStatementParsedExists(int $year): Builder
    {
        return self::statementsOfSection($year)->where(function (Builder $q): void {
            $q->whereNotNull('total_expenses')->orWhereNotNull('total_revenues')->orWhereNotNull('net_result');
        });
    }

    /**
     * @return Builder<CaiFinancialStatement>
     */
    private static function balanceSheetParsedExists(int $year): Builder
    {
        return self::statementsOfSection($year)->where(function (Builder $q): void {
            $q->whereNotNull('total_assets')->orWhereNotNull('net_equity');
        });
    }

    /**
     * Documenti dell'anno collegati alla sezione corrente (`cai_sections.codice_cai` della query esterna).
     *
     * @return Builder<CaiDocument>
     */
    private static function documentsOfSection(int $year): Builder
    {
        return CaiDocument::query()
            ->select(DB::raw('1'))
            ->forYear($year)
            ->where(fn (Builder $q) => self::linkedToSection($q, 'cai_documents'));
    }

    /**
     * @return Builder<CaiFinancialStatement>
     */
    private static function statementsOfSection(int $year): Builder
    {
        return CaiFinancialStatement::query()
            ->select(DB::raw('1'))
            ->where('cai_financial_statements.year', $year)
            ->where(fn (Builder $q) => self::linkedToSection($q, 'cai_financial_statements'));
    }

    /**
     * @param  Builder<CaiDocument>|Builder<CaiFinancialStatement>  $query
     */
    private static function linkedToSection(Builder $query, string $table): void
    {
        $query->whereColumn($table.'.cai_section_id', 'cai_sections.codice_cai')
            ->orWhereIn($table.'.cai_runts_registration_id', function (QueryBuilder $sub): void {
                $sub->select('cai_runts_registrations.id_runts')
                    ->from('cai_runts_registrations')
                    ->whereColumn('cai_runts_registrations.cai_section_id', 'cai_sections.codice_cai');
            });
    }
}
