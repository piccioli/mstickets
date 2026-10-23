<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupDashboardMetrics;
use App\Domain\Identity\Enums\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('the regional group code is the most frequent prefix of its region followed by 000', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9213001', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9216001', 'region' => 'LOMBARDIA']);

    $data = CaiRegionalGroupDashboardMetrics::forRegion(Region::Toscana);

    expect($data->codiceCai)->toBe('9226000')
        ->and($data->sectionsCount)->toBe(2);
});

test('on a tie the numerically highest prefix wins', function (): void {
    caiSection(['codice_cai' => '9218001', 'region' => 'TRENTINO-ALTO ADIGE']);
    caiSection(['codice_cai' => '9219001', 'region' => 'TRENTINO-ALTO ADIGE']);

    expect(CaiRegionalGroupDashboardMetrics::forRegion(Region::TrentinoAltoAdige)->codiceCai)->toBe('9219000');
});

test('the sections of the group are picked by code prefix, whatever their region', function (): void {
    caiSection(['codice_cai' => '9218001', 'region' => 'TRENTINO-ALTO ADIGE']);
    caiSection(['codice_cai' => '9219001', 'region' => 'TRENTINO-ALTO ADIGE']);
    caiSection(['codice_cai' => '9219002', 'region' => 'TRENTINO-ALTO ADIGE']);
    caiSection(['codice_cai' => '9220001', 'region' => 'VENETO']);
    caiSection(['codice_cai' => '9220002', 'region' => 'VENETO']);
    caiSection(['codice_cai' => '9220003', 'region' => 'FRIULI-VENEZIA GIULIA', 'members_count' => 300]);
    caiSection(['codice_cai' => '9222001', 'region' => 'FRIULI-VENEZIA GIULIA']);
    caiSection(['codice_cai' => '9222002', 'region' => 'FRIULI-VENEZIA GIULIA']);

    $trentino = CaiRegionalGroupDashboardMetrics::forRegion(Region::TrentinoAltoAdige);
    $veneto = CaiRegionalGroupDashboardMetrics::forRegion(Region::Veneto);
    $fvg = CaiRegionalGroupDashboardMetrics::forRegion(Region::FriuliVeneziaGiulia);

    expect($trentino->sectionsCount)->toBe(2)
        ->and($veneto->codiceCai)->toBe('9220000')
        ->and($veneto->sectionsCount)->toBe(3)
        ->and($veneto->members)->toBe(300)
        ->and($fvg->codiceCai)->toBe('9222000')
        ->and($fvg->sectionsCount)->toBe(2);
});

test('a region without sections has everything null', function (): void {
    caiSection(['codice_cai' => '9216001', 'region' => 'LOMBARDIA', 'members_count' => 100]);

    $data = CaiRegionalGroupDashboardMetrics::forRegion(Region::Molise);

    expect($data->codiceCai)->toBeNull()
        ->and($data->sectionsCount)->toBeNull()
        ->and($data->members)->toBeNull()
        ->and($data->membersSectionsCount)->toBeNull()
        ->and($data->consolidatedIncomeStatement)->toBeNull()
        ->and($data->consolidatedBalanceSheet)->toBeNull()
        ->and($data->incomeStatement)->toBeNull()
        ->and($data->balanceSheet)->toBeNull()
        ->and($data->huts)->toBeNull()
        ->and($data->hutsSectionsCount)->toBeNull()
        ->and($data->trailsKm)->toBeNull()
        ->and($data->sicaiStages)->toBeNull();
});

test('huts are those of the sections of the group plus those owned by the GR', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226003', 'region' => 'TOSCANA']);
    caiHut(['branch_code' => '9226001']);
    caiHut(['branch_code' => '9226001']);
    caiHut(['branch_code' => '9226003', 'status' => null]);
    caiHut(['branch_code' => '9500010', 'branch_name' => 'gr toscana']);
    caiHut(['branch_code' => '9500046', 'branch_name' => 'GR SICILIA']);
    caiHut(['branch_code' => '9216001']);
    caiHut(['branch_code' => null]);

    $data = CaiRegionalGroupDashboardMetrics::forRegion(Region::Toscana);

    expect($data->huts)->toBe(4)
        ->and($data->hutsSectionsCount)->toBe(2);
});

test('a group without huts has 0 huts and 0 sections with huts, never null', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    caiHut(['branch_code' => '9216001']);

    $data = CaiRegionalGroupDashboardMetrics::forRegion(Region::Toscana);

    expect($data->huts)->toBe(0)
        ->and($data->hutsSectionsCount)->toBe(0);
});

test('the huts owned by the GR do not count as a section with huts', function (): void {
    caiSection(['codice_cai' => '9233001', 'region' => 'SICILIA']);
    caiHut(['branch_code' => '9500046', 'branch_name' => 'GR SICILIA']);

    $data = CaiRegionalGroupDashboardMetrics::forRegion(Region::Sicilia);

    expect($data->huts)->toBe(1)
        ->and($data->hutsSectionsCount)->toBe(0);
});

test('the huts of the group are counted with a single query', function (): void {
    foreach (range(1, 5) as $i) {
        caiSection(['codice_cai' => '922600'.$i, 'region' => 'TOSCANA']);
        caiHut(['branch_code' => '922600'.$i]);
    }

    DB::enableQueryLog();
    CaiRegionalGroupDashboardMetrics::forRegion(Region::Toscana);
    $hutQueries = collect(DB::getQueryLog())->filter(fn (array $q): bool => str_contains($q['query'], 'cai_huts'));

    expect($hutQueries)->toHaveCount(1);
});

test('members are the sum of the sections that have them', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA', 'members_count' => 1200]);
    caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA', 'members_count' => 300]);
    caiSection(['codice_cai' => '9226003', 'region' => 'TOSCANA', 'members_count' => null]);

    $data = CaiRegionalGroupDashboardMetrics::forRegion(Region::Toscana);

    expect($data->members)->toBe(1500)
        ->and($data->membersSectionsCount)->toBe(2)
        ->and($data->sectionsCount)->toBe(3);
});

test('members are null when no section has them', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA', 'members_count' => null]);

    $data = CaiRegionalGroupDashboardMetrics::forRegion(Region::Toscana);

    expect($data->members)->toBeNull()
        ->and($data->membersSectionsCount)->toBe(0);
});

test('the consolidated income statement sums only the latest year and the sections with data in it', function (): void {
    $a = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    $b = caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    $c = caiSection(['codice_cai' => '9226003', 'region' => 'TOSCANA']);
    CaiFinancialStatement::create(['cai_section_id' => $a->codice_cai, 'year' => 2025,
        'total_revenues' => 1000.10, 'total_expenses' => 900, 'net_result' => 100.10]);
    CaiFinancialStatement::create(['cai_section_id' => $b->codice_cai, 'year' => 2025,
        'total_revenues' => 500.20, 'total_expenses' => 600, 'net_result' => -99.80]);
    CaiFinancialStatement::create(['cai_section_id' => $b->codice_cai, 'year' => 2024, 'total_revenues' => 77777]);
    CaiFinancialStatement::create(['cai_section_id' => $c->codice_cai, 'year' => 2024,
        'total_revenues' => 99999, 'net_result' => 99999]);

    $income = CaiRegionalGroupDashboardMetrics::forRegion(Region::Toscana)->consolidatedIncomeStatement;

    expect($income?->year)->toBe(2025)
        ->and($income?->sectionsCount)->toBe(2)
        ->and($income?->revenues)->toBe(1500.3)
        ->and($income?->expenses)->toBe(1500.0)
        ->and($income?->netResult)->toBe(0.3);
});

test('a consolidated total without any value stays null, never 0', function (): void {
    $a = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    $b = caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    CaiFinancialStatement::create(['cai_section_id' => $a->codice_cai, 'year' => 2025, 'total_revenues' => 1000]);
    CaiFinancialStatement::create(['cai_section_id' => $b->codice_cai, 'year' => 2025, 'total_revenues' => 200, 'net_equity' => 50]);

    $data = CaiRegionalGroupDashboardMetrics::forRegion(Region::Toscana);

    expect($data->consolidatedIncomeStatement?->revenues)->toBe(1200.0)
        ->and($data->consolidatedIncomeStatement?->expenses)->toBeNull()
        ->and($data->consolidatedIncomeStatement?->netResult)->toBeNull()
        ->and($data->consolidatedBalanceSheet?->sectionsCount)->toBe(1)
        ->and($data->consolidatedBalanceSheet?->netEquity)->toBe(50.0)
        ->and($data->consolidatedBalanceSheet?->totalAssets)->toBeNull();
});

test('on the same year of a section the statement linked to the RUNTS registration wins', function (): void {
    $section = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    $registration = caiRuntsRegistration(['cai_section_id' => $section->codice_cai]);
    CaiFinancialStatement::create(['cai_section_id' => $section->codice_cai, 'year' => 2025, 'net_result' => 1]);
    CaiFinancialStatement::create(['cai_runts_registration_id' => $registration->id_runts, 'year' => 2025, 'net_result' => 2]);

    $income = CaiRegionalGroupDashboardMetrics::forRegion(Region::Toscana)->consolidatedIncomeStatement;

    expect($income?->netResult)->toBe(2.0)
        ->and($income?->sectionsCount)->toBe(1);
});

test('a statement linked only through the RUNTS registration is consolidated', function (): void {
    $a = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    $b = caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    $registration = caiRuntsRegistration(['cai_section_id' => $a->codice_cai]);
    CaiFinancialStatement::create(['cai_runts_registration_id' => $registration->id_runts, 'year' => 2025, 'total_assets' => 70]);
    CaiFinancialStatement::create(['cai_section_id' => $b->codice_cai, 'year' => 2025, 'total_assets' => 30]);

    $balance = CaiRegionalGroupDashboardMetrics::forRegion(Region::Toscana)->consolidatedBalanceSheet;

    expect($balance?->totalAssets)->toBe(100.0)
        ->and($balance?->sectionsCount)->toBe(2)
        ->and($balance?->totalSections)->toBe(2);
});

test('income statement and balance sheet pick their year independently', function (): void {
    $a = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    $b = caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226003', 'region' => 'TOSCANA']);
    CaiFinancialStatement::create(['cai_section_id' => $a->codice_cai, 'year' => 2025, 'net_result' => 10]);
    CaiFinancialStatement::create(['cai_section_id' => $a->codice_cai, 'year' => 2024, 'net_equity' => 400, 'total_assets' => 900]);
    CaiFinancialStatement::create(['cai_section_id' => $b->codice_cai, 'year' => 2024, 'net_equity' => 100]);

    $data = CaiRegionalGroupDashboardMetrics::forRegion(Region::Toscana);

    expect($data->consolidatedIncomeStatement?->year)->toBe(2025)
        ->and($data->consolidatedIncomeStatement?->sectionsCount)->toBe(1)
        ->and($data->consolidatedBalanceSheet?->year)->toBe(2024)
        ->and($data->consolidatedBalanceSheet?->sectionsCount)->toBe(2)
        ->and($data->consolidatedBalanceSheet?->totalSections)->toBe(3)
        ->and($data->consolidatedBalanceSheet?->netEquity)->toBe(500.0)
        ->and($data->consolidatedBalanceSheet?->totalAssets)->toBe(900.0);
});

test('subsections never count as sections of the group', function (): void {
    $section = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA', 'members_count' => 500]);
    CaiSubsection::create(['cai_codice' => '9226001001', 'cai_section_id' => $section->codice_cai,
        'name' => 'Sottosezione', 'members_count' => 80]);

    $data = CaiRegionalGroupDashboardMetrics::forRegion(Region::Toscana);

    expect($data->sectionsCount)->toBe(1)
        ->and($data->members)->toBe(500);
});

test('the statements of all the sections are loaded with a single query', function (): void {
    foreach (range(1, 5) as $i) {
        $section = caiSection(['codice_cai' => '922600'.$i, 'region' => 'TOSCANA']);
        CaiFinancialStatement::create(['cai_section_id' => $section->codice_cai, 'year' => 2025, 'net_result' => $i]);
    }

    DB::enableQueryLog();
    CaiRegionalGroupDashboardMetrics::forRegion(Region::Toscana);
    $statementQueries = collect(DB::getQueryLog())
        ->filter(fn (array $q): bool => str_contains($q['query'], 'cai_financial_statements'));

    expect($statementQueries)->toHaveCount(1);
});

test('trails are the km in the regional territory, including trails without a section', function (): void {
    grTrailsFixture();

    expect(CaiRegionalGroupDashboardMetrics::forRegion(Region::Lombardia)->trailsKm)->toBe(30.7)
        ->and(CaiRegionalGroupDashboardMetrics::forRegion(Region::Molise)->trailsKm)->toBe(7.0);
});

test('a region missing from the trails data has 0 km, never null', function (): void {
    grTrailsFixture();

    expect(CaiRegionalGroupDashboardMetrics::forRegion(Region::Piemonte)->trailsKm)->toBe(0.0);
});

test('a region known to the trails data but without km has 0 km', function (): void {
    caiSection(['codice_cai' => '9300001', 'region' => 'LOMBARDIA']);
    DB::table('cai_trail_regions')->insert(['id' => 1, 'name' => 'Lombardia', 'region_key' => 'lombardia']);

    expect(CaiRegionalGroupDashboardMetrics::forRegion(Region::Lombardia)->trailsKm)->toBe(0.0);
});
