<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Enums\CaiRuntsPresenceStatus;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\CaiDirectory\Queries\CaiSectionDashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a complete section exposes identity, members and both statements', function (): void {
    $section = caiSection(['codice_cai' => '9213', 'name' => 'Sezione di Pisa', 'region' => 'TOSCANA', 'members_count' => 1240]);
    CaiFinancialStatement::create(['cai_section_id' => $section->codice_cai, 'year' => 2025,
        'total_revenues' => 180000.50, 'total_expenses' => 175000.25, 'net_result' => 5000.25,
        'net_equity' => 90000, 'total_assets' => 250000]);

    $data = CaiSectionDashboardMetrics::forSection($section);

    expect($data->name)->toBe('Sezione di Pisa')
        ->and($data->codiceCai)->toBe('9213')
        ->and($data->region)->toBe('Toscana')
        ->and($data->members)->toBe(1240)
        ->and($data->incomeStatement?->year)->toBe(2025)
        ->and($data->incomeStatement?->revenues)->toBe(180000.5)
        ->and($data->incomeStatement?->expenses)->toBe(175000.25)
        ->and($data->incomeStatement?->netResult)->toBe(5000.25)
        ->and($data->balanceSheet?->year)->toBe(2025)
        ->and($data->balanceSheet?->netEquity)->toBe(90000.0)
        ->and($data->balanceSheet?->totalAssets)->toBe(250000.0)
        ->and($data->huts)->toBe(0)
        ->and($data->trailsKm)->toBe(0.0)
        ->and($data->sicaiStages)->toBeNull();
});

test('a section without statements has null statements and members', function (): void {
    $section = caiSection(['region' => 'EXTRA REGIONE', 'members_count' => null]);

    $data = CaiSectionDashboardMetrics::forSection($section);

    expect($data->incomeStatement)->toBeNull()
        ->and($data->balanceSheet)->toBeNull()
        ->and($data->members)->toBeNull()
        ->and($data->region)->toBe('Extra regione');
});

test('a statement with a single value keeps the others null and never recomputes the net result', function (): void {
    $section = caiSection();
    CaiFinancialStatement::create(['cai_section_id' => $section->codice_cai, 'year' => 2025,
        'total_revenues' => 1000, 'total_expenses' => 400]);

    $data = CaiSectionDashboardMetrics::forSection($section);

    expect($data->incomeStatement?->revenues)->toBe(1000.0)
        ->and($data->incomeStatement?->expenses)->toBe(400.0)
        ->and($data->incomeStatement?->netResult)->toBeNull()
        ->and($data->balanceSheet)->toBeNull();
});

test('income statement and balance sheet are picked independently on different years', function (): void {
    $section = caiSection();
    CaiFinancialStatement::create(['cai_section_id' => $section->codice_cai, 'year' => 2023,
        'net_result' => 10, 'net_equity' => 500, 'total_assets' => 800]);
    CaiFinancialStatement::create(['cai_section_id' => $section->codice_cai, 'year' => 2024, 'net_equity' => 600]);
    CaiFinancialStatement::create(['cai_section_id' => $section->codice_cai, 'year' => 2025, 'net_result' => -20]);

    $data = CaiSectionDashboardMetrics::forSection($section);

    expect($data->incomeStatement?->year)->toBe(2025)
        ->and($data->incomeStatement?->netResult)->toBe(-20.0)
        ->and($data->balanceSheet?->year)->toBe(2024)
        ->and($data->balanceSheet?->netEquity)->toBe(600.0)
        ->and($data->balanceSheet?->totalAssets)->toBeNull();
});

test('on the same year the statement linked to the RUNTS registration wins', function (): void {
    $section = caiSection();
    $registration = caiRuntsRegistration(['cai_section_id' => $section->codice_cai]);
    CaiFinancialStatement::create(['cai_section_id' => $section->codice_cai, 'year' => 2025, 'net_result' => 1]);
    CaiFinancialStatement::create(['cai_runts_registration_id' => $registration->id_runts, 'year' => 2025, 'net_result' => 2]);

    expect(CaiSectionDashboardMetrics::forSection($section)->incomeStatement?->netResult)->toBe(2.0);
});

test('a statement linked only through the RUNTS registration belongs to the section', function (): void {
    $section = caiSection();
    $registration = caiRuntsRegistration(['cai_section_id' => $section->codice_cai]);
    CaiFinancialStatement::create(['cai_runts_registration_id' => $registration->id_runts, 'year' => 2024, 'total_assets' => 77]);

    $other = caiSection();
    CaiFinancialStatement::create(['cai_section_id' => $other->codice_cai, 'year' => 2025, 'total_assets' => 999, 'net_result' => 5]);

    $data = CaiSectionDashboardMetrics::forSection($section);

    expect($data->balanceSheet?->year)->toBe(2024)
        ->and($data->balanceSheet?->totalAssets)->toBe(77.0)
        ->and($data->incomeStatement)->toBeNull();
});

test('a subsection uses its own identity and members, the parent region and no statements', function (): void {
    $section = caiSection(['region' => "VALLE D'AOSTA", 'members_count' => 900]);
    CaiFinancialStatement::create(['cai_section_id' => $section->codice_cai, 'year' => 2025, 'net_result' => 10]);
    $subsection = CaiSubsection::create(['cai_codice' => '9213001', 'cai_section_id' => $section->codice_cai,
        'name' => 'Sottosezione di Cascina', 'members_count' => 80]);

    $data = CaiSectionDashboardMetrics::forSubsection($subsection);

    expect($data->name)->toBe('Sottosezione di Cascina')
        ->and($data->codiceCai)->toBe('9213001')
        ->and($data->region)->toBe("Valle d'Aosta")
        ->and($data->members)->toBe(80)
        ->and($data->incomeStatement)->toBeNull()
        ->and($data->balanceSheet)->toBeNull();
});

test('section members are never summed with the subsections members', function (): void {
    $section = caiSection(['members_count' => 500]);
    CaiSubsection::create(['cai_codice' => 'SUB-1', 'cai_section_id' => $section->codice_cai, 'name' => 'Sub 1', 'members_count' => 40]);
    CaiSubsection::create(['cai_codice' => 'SUB-2', 'cai_section_id' => $section->codice_cai, 'name' => 'Sub 2', 'members_count' => 60]);

    expect(CaiSectionDashboardMetrics::forSection($section)->members)->toBe(500);
});

test('a section with a RUNTS registration is registered in the third sector', function (): void {
    $section = caiSection();
    caiRuntsRegistration(['cai_section_id' => $section->codice_cai]);

    expect(CaiSectionDashboardMetrics::forSection($section)->thirdSectorRegistered)->toBeTrue();
});

test('a section found by the RUNTS presence check is registered even without a linked registration', function (): void {
    $section = caiSection(['runts_presence_status' => CaiRuntsPresenceStatus::Registered]);

    expect(CaiSectionDashboardMetrics::forSection($section)->thirdSectorRegistered)->toBeTrue();
});

test('a section that is not found as registered is not registered, whatever the check outcome', function (?CaiRuntsPresenceStatus $status): void {
    $section = caiSection(['runts_presence_status' => $status]);

    expect(CaiSectionDashboardMetrics::forSection($section)->thirdSectorRegistered)->toBeFalse();
})->with([
    'mai verificata' => [null],
    'timeout' => [CaiRuntsPresenceStatus::Timeout],
    'non presente' => [CaiRuntsPresenceStatus::NotRegistered],
]);

test('a subsection has no third sector information', function (): void {
    $section = caiSection(['runts_presence_status' => CaiRuntsPresenceStatus::Registered]);
    $subsection = CaiSubsection::create(['cai_codice' => 'SUB-ETS', 'cai_section_id' => $section->codice_cai, 'name' => 'Sub']);

    expect(CaiSectionDashboardMetrics::forSubsection($subsection)->thirdSectorRegistered)->toBeNull();
});

test('huts counts every hut of the section whatever its status, never the other sections ones', function (): void {
    $section = caiSection();
    caiHut(['branch_code' => $section->codice_cai, 'status' => CaiHutStatus::Active]);
    caiHut(['branch_code' => $section->codice_cai, 'status' => CaiHutStatus::TemporarilyClosed]);
    caiHut(['branch_code' => $section->codice_cai, 'status' => CaiHutStatus::Sold]);
    caiHut(['branch_code' => caiSection()->codice_cai]);

    expect(CaiSectionDashboardMetrics::forSection($section)->huts)->toBe(3);
});

test('a section without huts has 0 huts, a known value', function (): void {
    expect(CaiSectionDashboardMetrics::forSection(caiSection())->huts)->toBe(0);
});

test('a subsection has no huts information', function (): void {
    $section = caiSection();
    caiHut(['branch_code' => $section->codice_cai]);
    $subsection = CaiSubsection::create(['cai_codice' => 'SUB-HUT', 'cai_section_id' => $section->codice_cai, 'name' => 'Sub']);

    expect(CaiSectionDashboardMetrics::forSubsection($subsection)->huts)->toBeNull();
});

test('trails km sums the length of every trail of the section, a shared trail counted in full', function (): void {
    $section = caiSection();
    $other = caiSection();
    caiTrail(['length_km' => 5.4], [$section->codice_cai]);
    caiTrail(['length_km' => 12.25], [$section->codice_cai, $other->codice_cai]);
    caiTrail(['length_km' => null], [$section->codice_cai]);
    caiTrail(['length_km' => 100], [$other->codice_cai]);

    expect(CaiSectionDashboardMetrics::forSection($section)->trailsKm)->toBe(17.65)
        ->and(CaiSectionDashboardMetrics::forSection($other)->trailsKm)->toBe(112.25);
});

test('a section without trails has 0 trail km, a known value', function (): void {
    expect(CaiSectionDashboardMetrics::forSection(caiSection())->trailsKm)->toBe(0.0);
});

test('a subsection has no trails information', function (): void {
    $section = caiSection();
    caiTrail([], [$section->codice_cai]);
    $subsection = CaiSubsection::create(['cai_codice' => 'SUB-TRL', 'cai_section_id' => $section->codice_cai, 'name' => 'Sub']);

    expect(CaiSectionDashboardMetrics::forSubsection($subsection)->trailsKm)->toBeNull();
});
