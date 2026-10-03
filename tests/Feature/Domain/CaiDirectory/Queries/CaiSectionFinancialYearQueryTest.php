<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Queries\CaiSectionFinancialYearQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * @return array<string, array<string, bool>>
 */
function caiFinancialYearFlags(int $year = 2025): array
{
    return CaiSectionFinancialYearQuery::forYear($year)->get()->mapWithKeys(fn ($s) => [
        $s->codice_cai => [
            'income_file' => (bool) $s->has_income_statement_file,
            'balance_file' => (bool) $s->has_balance_sheet_file,
            'income_parsed' => (bool) $s->income_statement_parsed,
            'balance_parsed' => (bool) $s->balance_sheet_parsed,
        ],
    ])->all();
}

function caiFinancialYearFixture(): void
{
    $direct = caiSection(['codice_cai' => 'DIRECT']);
    caiDocument(['cai_runts_registration_id' => null, 'cai_section_id' => $direct->codice_cai, 'year' => 2025,
        'document_type' => 'altro', 'title' => 'Conto economico']);

    $viaRegistration = caiSection(['codice_cai' => 'VIAREG']);
    $registration = caiRuntsRegistration(['cai_section_id' => $viaRegistration->codice_cai]);
    caiDocument(['cai_runts_registration_id' => $registration->id_runts, 'year' => 2025,
        'title' => 'Mod. A - Stato Patrimoniale']);

    $onlyIncome = caiSection(['codice_cai' => 'ONLYCE']);
    CaiFinancialStatement::create(['cai_section_id' => $onlyIncome->codice_cai, 'year' => 2025, 'net_result' => 10]);

    $onlyBalance = caiSection(['codice_cai' => 'ONLYSP']);
    $registrationSp = caiRuntsRegistration(['cai_section_id' => $onlyBalance->codice_cai]);
    CaiFinancialStatement::create(['cai_runts_registration_id' => $registrationSp->id_runts, 'year' => 2025, 'total_assets' => 99]);

    $otherYear = caiSection(['codice_cai' => 'OTHERYEAR']);
    caiDocument(['cai_runts_registration_id' => null, 'cai_section_id' => $otherYear->codice_cai, 'year' => 2024,
        'document_type' => 'altro', 'title' => 'Conto economico', 'hash' => 'oy']);
    CaiFinancialStatement::create(['cai_section_id' => $otherYear->codice_cai, 'year' => 2024, 'net_result' => 1, 'total_assets' => 1]);

    caiSection(['codice_cai' => 'EMPTY']);
}

test('flags reflect direct documents, registration documents and parsed statements', function (): void {
    caiFinancialYearFixture();

    $flags = caiFinancialYearFlags();

    expect($flags['DIRECT'])->toBe(['income_file' => true, 'balance_file' => false, 'income_parsed' => false, 'balance_parsed' => false])
        ->and($flags['VIAREG'])->toBe(['income_file' => false, 'balance_file' => true, 'income_parsed' => false, 'balance_parsed' => false])
        ->and($flags['ONLYCE'])->toBe(['income_file' => false, 'balance_file' => false, 'income_parsed' => true, 'balance_parsed' => false])
        ->and($flags['ONLYSP'])->toBe(['income_file' => false, 'balance_file' => false, 'income_parsed' => false, 'balance_parsed' => true])
        ->and($flags['OTHERYEAR'])->toBe(['income_file' => false, 'balance_file' => false, 'income_parsed' => false, 'balance_parsed' => false])
        ->and($flags['EMPTY'])->toBe(['income_file' => false, 'balance_file' => false, 'income_parsed' => false, 'balance_parsed' => false]);
});

test('the year is a parameter', function (): void {
    caiFinancialYearFixture();

    $flags = caiFinancialYearFlags(2024);

    expect($flags['OTHERYEAR'])->toBe(['income_file' => true, 'balance_file' => false, 'income_parsed' => true, 'balance_parsed' => true])
        ->and($flags['DIRECT']['income_file'])->toBeFalse();
});

test('filter scopes agree with the boolean columns', function (): void {
    caiFinancialYearFixture();

    $scopes = [
        'income_file' => 'withIncomeStatementFile',
        'balance_file' => 'withBalanceSheetFile',
        'income_parsed' => 'withIncomeStatementParsed',
        'balance_parsed' => 'withBalanceSheetParsed',
    ];
    $flags = caiFinancialYearFlags();

    foreach ($scopes as $flag => $method) {
        foreach ([true, false] as $value) {
            $ids = CaiSectionFinancialYearQuery::{$method}(CaiSectionFinancialYearQuery::forYear(2025), 2025, $value)
                ->pluck('codice_cai')->all();
            $expected = array_keys(array_filter($flags, fn (array $f) => $f[$flag] === $value));

            expect($ids)->toEqualCanonicalizing($expected, $method.($value ? ' true' : ' false'));
        }
    }
});

test('filters can be combined on a plain section query', function (): void {
    caiFinancialYearFixture();

    $ids = CaiSectionFinancialYearQuery::withBalanceSheetFile(
        CaiSectionFinancialYearQuery::withIncomeStatementFile(CaiSection::query(), 2025),
        2025,
        false,
    )->pluck('codice_cai')->all();

    expect($ids)->toBe(['DIRECT']);
});

test('the list is a single query', function (): void {
    caiFinancialYearFixture();

    DB::enableQueryLog();
    $rows = CaiSectionFinancialYearQuery::withIncomeStatementFile(CaiSectionFinancialYearQuery::forYear(2025), 2025)->get();
    foreach ($rows as $row) {
        $row->has_income_statement_file;
        $row->balance_sheet_parsed;
    }
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($queries)->toHaveCount(1);
});
