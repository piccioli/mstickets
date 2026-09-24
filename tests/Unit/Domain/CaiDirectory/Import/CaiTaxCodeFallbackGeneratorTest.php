<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\CaiTaxCodeFallbackGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('generate matches a section despite prefix/legal-form/spacing differences and reports both codes', function (): void {
    caiSection(['codice_cai' => '9216049', 'name' => 'SEZ. ABBIATEGRASSO']);
    caiSection(['codice_cai' => '9216050', 'name' => 'SEZ. CARPI']);

    $path = writeCaiTaxCodeFallbackFixtureXlsx([
        ['C.A.I. SEZIONE DI ABBIATEGRASSO', '90000340159', ''],
        ['C.A.I. SEZIONE DI CARPI APS', '', '01234567890'],
    ]);

    $result = app(CaiTaxCodeFallbackGenerator::class)->generate($path);
    unlink($path);

    expect($result->entries)->toBe([
        '9216049' => ['name' => 'C.A.I. SEZIONE DI ABBIATEGRASSO', 'tax_code' => '90000340159', 'vat_number' => null],
        '9216050' => ['name' => 'C.A.I. SEZIONE DI CARPI APS', 'tax_code' => null, 'vat_number' => '01234567890'],
    ]);
    expect($result->unmatchedNames)->toBe([]);
});

test('generate reports rows without a matching section instead of dropping them silently', function (): void {
    caiSection(['codice_cai' => '9216049', 'name' => 'SEZ. ABBIATEGRASSO']);

    $path = writeCaiTaxCodeFallbackFixtureXlsx([
        ['GR LOMBARDIA', '12345678901', ''],
        ['C.A.I. SEZIONE DI UN PAESE INESISTENTE', '99999999999', ''],
    ]);

    $result = app(CaiTaxCodeFallbackGenerator::class)->generate($path);
    unlink($path);

    expect($result->entries)->toBe([]);
    expect($result->unmatchedNames)->toBe(['GR LOMBARDIA', 'C.A.I. SEZIONE DI UN PAESE INESISTENTE']);
});

test('generate skips a row with neither CF nor PIVA', function (): void {
    caiSection(['codice_cai' => '9216049', 'name' => 'SEZ. ABBIATEGRASSO']);

    $path = writeCaiTaxCodeFallbackFixtureXlsx([
        ['C.A.I. SEZIONE DI ABBIATEGRASSO', '', ''],
    ]);

    $result = app(CaiTaxCodeFallbackGenerator::class)->generate($path);
    unlink($path);

    expect($result->entries)->toBe([]);
    expect($result->unmatchedNames)->toBe([]);
});

test('generate never matches two sections that normalize to the same name', function (): void {
    caiSection(['codice_cai' => '9216049', 'name' => 'SEZ. ABBIATEGRASSO APS']);
    caiSection(['codice_cai' => '9216050', 'name' => 'SEZ. ABBIATEGRASSO ETS']);

    $path = writeCaiTaxCodeFallbackFixtureXlsx([
        ['C.A.I. SEZIONE DI ABBIATEGRASSO', '90000340159', ''],
    ]);

    $result = app(CaiTaxCodeFallbackGenerator::class)->generate($path);
    unlink($path);

    expect($result->entries)->toBe([]);
    expect($result->unmatchedNames)->toBe(['C.A.I. SEZIONE DI ABBIATEGRASSO']);
});
