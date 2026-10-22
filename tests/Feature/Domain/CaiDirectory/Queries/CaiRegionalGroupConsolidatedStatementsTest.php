<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Enums\CaiSectionStatementStatus;
use App\Domain\CaiDirectory\Enums\CaiStatementSource;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupConsolidatedStatements;
use App\Domain\CaiDirectory\Support\CaiConsolidatedSectionRow;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupConsolidatedData;
use App\Domain\Identity\Enums\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function consolidatedRow(CaiRegionalGroupConsolidatedData $data, string $code): CaiConsolidatedSectionRow
{
    $row = collect($data->rows)->first(fn (CaiConsolidatedSectionRow $r): bool => $r->codiceCai === $code);

    expect($row)->not->toBeNull();

    return $row;
}

/**
 * Un file di bilancio riconosciuto da `CaiFinancialDocumentKind` (il titolo di default di `caiDocument()` non lo è).
 *
 * @param  array<string, mixed>  $attributes
 */
function consolidatedDocument(array $attributes = []): CaiDocument
{
    return caiDocument(array_merge(['title' => 'Rendiconto gestionale'], $attributes));
}

test('the available years are the five ending with the highest year of statements or documents', function (): void {
    $a = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    CaiFinancialStatement::create(['cai_section_id' => $a->codice_cai, 'year' => 2024, 'total_revenues' => 10]);
    consolidatedDocument(['cai_runts_registration_id' => null, 'cai_section_id' => $a->codice_cai, 'year' => 2025]);

    expect(CaiRegionalGroupConsolidatedStatements::availableYears())->toBe([2021, 2022, 2023, 2024, 2025]);
});

test('every section of the group has a row with its status, also those without anything', function (): void {
    $data = caiSection(['codice_cai' => '9226001', 'name' => 'SEZ. A', 'region' => 'TOSCANA']);
    $pdf = caiSection(['codice_cai' => '9226002', 'name' => 'SEZ. B', 'region' => 'TOSCANA']);
    $none = caiSection(['codice_cai' => '9226003', 'name' => 'SEZ. C', 'region' => 'TOSCANA']);
    CaiFinancialStatement::create(['cai_section_id' => $data->codice_cai, 'year' => 2025,
        'total_revenues' => 1000, 'total_expenses' => 900, 'net_result' => 100, 'net_equity' => 50, 'total_assets' => 70]);
    consolidatedDocument(['cai_runts_registration_id' => null, 'cai_section_id' => $pdf->codice_cai, 'year' => 2025,
        'source' => CaiDocumentSource::Manual, 'file_path' => 'cai/b.pdf']);

    $result = CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana, 2025);

    expect($result->sectionsCount)->toBe(3)
        ->and($result->codiceCai)->toBe('9226000')
        ->and(collect($result->rows)->pluck('name')->all())->toBe(['SEZ. A', 'SEZ. B', 'SEZ. C']);

    $a = consolidatedRow($result, '9226001');
    expect($a->status)->toBe(CaiSectionStatementStatus::Data)
        ->and($a->source)->toBe(CaiStatementSource::Manual)
        ->and($a->revenues)->toBe(1000.0)
        ->and($a->expenses)->toBe(900.0)
        ->and($a->netResult)->toBe(100.0)
        ->and($a->netEquity)->toBe(50.0)
        ->and($a->totalAssets)->toBe(70.0);

    $b = consolidatedRow($result, '9226002');
    expect($b->status)->toBe(CaiSectionStatementStatus::PdfOnly)
        ->and($b->source)->toBe(CaiStatementSource::Manual)
        ->and($b->revenues)->toBeNull()
        ->and($b->documentId)->not->toBeNull();

    $c = consolidatedRow($result, '9226003');
    expect($c->status)->toBe(CaiSectionStatementStatus::None)
        ->and($c->source)->toBeNull()
        ->and($c->documentId)->toBeNull();

    expect($result->dataCount)->toBe(1)
        ->and($result->pdfOnlyCount)->toBe(1)
        ->and($result->noneCount)->toBe(1);
});

test('a statement or a document from the RUNTS registration has source RUNTS and RUNTS wins on a tie', function (): void {
    $a = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    $b = caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    $ra = caiRuntsRegistration(['cai_section_id' => $a->codice_cai]);
    $rb = caiRuntsRegistration(['cai_section_id' => $b->codice_cai]);
    CaiFinancialStatement::create(['cai_section_id' => $a->codice_cai, 'year' => 2025, 'total_revenues' => 111]);
    CaiFinancialStatement::create(['cai_runts_registration_id' => $ra->id_runts, 'year' => 2025, 'total_revenues' => 222]);
    consolidatedDocument(['cai_runts_registration_id' => $rb->id_runts, 'year' => 2025, 'file_path' => 'cai/rb.pdf']);

    $result = CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana, 2025);

    expect(consolidatedRow($result, '9226001')->source)->toBe(CaiStatementSource::Runts)
        ->and(consolidatedRow($result, '9226001')->revenues)->toBe(222.0)
        ->and(consolidatedRow($result, '9226002')->status)->toBe(CaiSectionStatementStatus::PdfOnly)
        ->and(consolidatedRow($result, '9226002')->source)->toBe(CaiStatementSource::Runts);
});

test('a document of the RUNTS registration with source manual counts as uploaded by hand', function (): void {
    $a = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    $ra = caiRuntsRegistration(['cai_section_id' => $a->codice_cai]);
    consolidatedDocument(['cai_runts_registration_id' => $ra->id_runts, 'year' => 2025, 'source' => CaiDocumentSource::Manual]);

    $row = consolidatedRow(CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana, 2025), '9226001');

    expect($row->status)->toBe(CaiSectionStatementStatus::PdfOnly)
        ->and($row->source)->toBe(CaiStatementSource::Manual)
        ->and($row->documentId)->not->toBeNull();
});

test('a statement without any figure and a document that is not a financial statement do not count', function (): void {
    $a = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    CaiFinancialStatement::create(['cai_section_id' => $a->codice_cai, 'year' => 2025, 'total_liabilities' => 5]);
    caiDocument(['cai_runts_registration_id' => null, 'cai_section_id' => $a->codice_cai, 'year' => 2025,
        'title' => "BILANCIO D'ESERCIZIO"]);

    $row = consolidatedRow(CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana, 2025), '9226001');

    expect($row->status)->toBe(CaiSectionStatementStatus::None);
});

test('the PDF is the financial document of the year with a file, RUNTS first then the lowest id', function (): void {
    $a = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    $ra = caiRuntsRegistration(['cai_section_id' => $a->codice_cai]);
    CaiFinancialStatement::create(['cai_section_id' => $a->codice_cai, 'year' => 2025, 'total_revenues' => 10]);
    consolidatedDocument(['cai_runts_registration_id' => null, 'cai_section_id' => $a->codice_cai, 'year' => 2025,
        'source' => CaiDocumentSource::Manual, 'file_path' => 'cai/manual.pdf']);
    consolidatedDocument(['cai_runts_registration_id' => $ra->id_runts, 'year' => 2025, 'file_path' => null]);
    consolidatedDocument(['cai_runts_registration_id' => $ra->id_runts, 'year' => 2024, 'file_path' => 'cai/2024.pdf']);
    consolidatedDocument(['cai_runts_registration_id' => $ra->id_runts, 'year' => 2025, 'title' => 'Verbale assemblea',
        'file_path' => 'cai/verbale.pdf']);
    $first = consolidatedDocument(['cai_runts_registration_id' => $ra->id_runts, 'year' => 2025, 'file_path' => 'cai/runts-1.pdf']);
    consolidatedDocument(['cai_runts_registration_id' => $ra->id_runts, 'year' => 2025, 'file_path' => 'cai/runts-2.pdf']);

    $row = consolidatedRow(CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana, 2025), '9226001');

    expect($row->documentId)->toBe($first->id);
});

test('totals sum only the non-null values of the sections with data and say how many they sum', function (): void {
    $a = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    $b = caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    $c = caiSection(['codice_cai' => '9226003', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226004', 'region' => 'TOSCANA']);
    CaiFinancialStatement::create(['cai_section_id' => $a->codice_cai, 'year' => 2025,
        'total_revenues' => 1000.10, 'net_result' => 100.10]);
    CaiFinancialStatement::create(['cai_section_id' => $b->codice_cai, 'year' => 2025,
        'total_revenues' => 500.20, 'net_result' => -99.80, 'total_assets' => 300]);
    CaiFinancialStatement::create(['cai_section_id' => $c->codice_cai, 'year' => 2025, 'total_assets' => 200]);
    CaiFinancialStatement::create(['cai_section_id' => $a->codice_cai, 'year' => 2024, 'total_revenues' => 99999]);

    $result = CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana, 2025);

    expect($result->revenues)->toBe(1500.3)
        ->and($result->expenses)->toBeNull()
        ->and($result->netResult)->toBe(0.3)
        ->and($result->netEquity)->toBeNull()
        ->and($result->totalAssets)->toBe(500.0)
        ->and($result->incomeStatementSectionsCount)->toBe(2)
        ->and($result->balanceSheetSectionsCount)->toBe(2)
        ->and($result->dataCount)->toBe(3)
        ->and($result->noneCount)->toBe(1)
        ->and($result->dataCount + $result->pdfOnlyCount + $result->noneCount)->toBe($result->sectionsCount);
});

test('the default year is the latest with data, even when the latest available year has none', function (): void {
    $a = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    CaiFinancialStatement::create(['cai_section_id' => $a->codice_cai, 'year' => 2023, 'total_revenues' => 10]);
    CaiFinancialStatement::create(['cai_section_id' => $a->codice_cai, 'year' => 2024, 'total_liabilities' => 10]);
    consolidatedDocument(['cai_runts_registration_id' => null, 'cai_section_id' => $a->codice_cai, 'year' => 2025]);

    $default = CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana);
    $invalid = CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana, 2019);

    expect($default->year)->toBe(2023)
        ->and($default->availableYears)->toBe([2021, 2022, 2023, 2024, 2025])
        ->and($invalid->year)->toBe(2023)
        ->and(CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana, 2025)->year)->toBe(2025);
});

test('without any data the default year is the latest available', function (): void {
    $a = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    consolidatedDocument(['cai_runts_registration_id' => null, 'cai_section_id' => $a->codice_cai, 'year' => 2025]);
    caiSection(['codice_cai' => '9216001', 'region' => 'LOMBARDIA']);
    CaiFinancialStatement::create(['cai_section_id' => '9216001', 'year' => 2024, 'total_revenues' => 10]);

    expect(CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana)->year)->toBe(2025);
});

test('with fewer than 3 values of an item nothing is flagged', function (): void {
    $a = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    $b = caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    CaiFinancialStatement::create(['cai_section_id' => $a->codice_cai, 'year' => 2025, 'total_revenues' => 100]);
    CaiFinancialStatement::create(['cai_section_id' => $b->codice_cai, 'year' => 2025, 'total_revenues' => 1000000]);

    $result = CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana, 2025);

    expect(consolidatedRow($result, '9226002')->flagged)->toBe([])
        ->and($result->incomeStatementFlaggedCount)->toBe(0);
});

test('a value over 10 times the median is flagged, the net result never is', function (): void {
    foreach (['9226001' => 100, '9226002' => 120, '9226003' => 80] as $code => $value) {
        caiSection(['codice_cai' => $code, 'region' => 'TOSCANA']);
        CaiFinancialStatement::create(['cai_section_id' => $code, 'year' => 2025, 'total_revenues' => $value,
            'total_expenses' => $value, 'net_result' => 1, 'net_equity' => $value, 'total_assets' => $value]);
    }
    caiSection(['codice_cai' => '9226004', 'region' => 'TOSCANA']);
    CaiFinancialStatement::create(['cai_section_id' => '9226004', 'year' => 2025, 'total_revenues' => 202500000,
        'total_expenses' => -202400000, 'net_result' => 100000, 'net_equity' => 1100, 'total_assets' => 1000]);

    $result = CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana, 2025);
    $outlier = consolidatedRow($result, '9226004');

    // Mediana dei valori assoluti di ricavi/costi = (100+120)/2 = 110: 202,5 M€ e |−202,4 M€| sono oltre 10×.
    // Patrimonio netto: mediana 110, 1100 = 10× esatto → non segnalato. Totale attivo: mediana 110, 1000 < 1100.
    expect($outlier->flagged)->toBe([CaiConsolidatedSectionRow::REVENUES, CaiConsolidatedSectionRow::EXPENSES])
        ->and($outlier->isFlagged(CaiConsolidatedSectionRow::REVENUES))->toBeTrue()
        ->and($outlier->isFlagged('netResult'))->toBeFalse()
        ->and($outlier->netResult)->toBe(100000.0)
        ->and($result->incomeStatementFlaggedCount)->toBe(2)
        ->and($result->balanceSheetFlaggedCount)->toBe(0)
        ->and(consolidatedRow($result, '9226001')->flagged)->toBe([])
        ->and($result->revenues)->toBe(202500300.0);
});

test('a balance sheet outlier counts on the balance sheet card', function (): void {
    foreach (['9226001' => 100, '9226002' => 100, '9226003' => 100, '9226004' => 5000] as $code => $value) {
        caiSection(['codice_cai' => $code, 'region' => 'TOSCANA']);
        CaiFinancialStatement::create(['cai_section_id' => $code, 'year' => 2025, 'net_equity' => $value, 'total_assets' => $value]);
    }

    $result = CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana, 2025);

    expect(consolidatedRow($result, '9226004')->flagged)->toBe([CaiConsolidatedSectionRow::NET_EQUITY, CaiConsolidatedSectionRow::TOTAL_ASSETS])
        ->and($result->balanceSheetFlaggedCount)->toBe(2)
        ->and($result->incomeStatementFlaggedCount)->toBe(0);
});

test('a section outside the prefix of the group is excluded, whatever its region', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9213001', 'region' => 'TOSCANA']);
    CaiFinancialStatement::create(['cai_section_id' => '9213001', 'year' => 2025, 'total_revenues' => 999]);

    $result = CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana, 2025);

    expect($result->sectionsCount)->toBe(2)
        ->and(collect($result->rows)->pluck('codiceCai')->all())->not->toContain('9213001')
        ->and($result->revenues)->toBeNull()
        ->and($result->dataCount)->toBe(0);
});

test('a region without sections has no rows and no group code', function (): void {
    $result = CaiRegionalGroupConsolidatedStatements::forRegion(Region::Molise);

    expect($result->codiceCai)->toBeNull()
        ->and($result->sectionsCount)->toBe(0)
        ->and($result->rows)->toBe([]);
});

test('the queries do not grow with the number of sections', function (): void {
    $count = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana, 2025);

        return count(DB::getQueryLog());
    };

    $a = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    CaiFinancialStatement::create(['cai_section_id' => $a->codice_cai, 'year' => 2025, 'total_revenues' => 10]);
    $few = $count();

    foreach (range(2, 8) as $i) {
        $s = caiSection(['codice_cai' => '922600'.$i, 'region' => 'TOSCANA']);
        $r = caiRuntsRegistration(['cai_section_id' => $s->codice_cai]);
        CaiFinancialStatement::create(['cai_runts_registration_id' => $r->id_runts, 'year' => 2025, 'total_revenues' => 10]);
        consolidatedDocument(['cai_runts_registration_id' => $r->id_runts, 'year' => 2025, 'file_path' => 'cai/x.pdf']);
    }

    expect($count())->toBe($few);
});

test('income statement and balance sheet of a section are picked independently in the same year', function (): void {
    $a = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    $ra = caiRuntsRegistration(['cai_section_id' => $a->codice_cai]);
    CaiFinancialStatement::create(['cai_runts_registration_id' => $ra->id_runts, 'year' => 2025, 'total_revenues' => 300]);
    CaiFinancialStatement::create(['cai_section_id' => $a->codice_cai, 'year' => 2025, 'total_revenues' => 999, 'net_equity' => 40]);

    $result = CaiRegionalGroupConsolidatedStatements::forRegion(Region::Toscana, 2025);
    $row = consolidatedRow($result, '9226001');

    expect($row->source)->toBe(CaiStatementSource::Runts)
        ->and($row->revenues)->toBe(300.0)
        ->and($row->netEquity)->toBe(40.0)
        ->and($result->balanceSheetSectionsCount)->toBe(1);
});
