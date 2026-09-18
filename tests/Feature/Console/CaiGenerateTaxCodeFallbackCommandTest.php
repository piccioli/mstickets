<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('cai:generate-tax-code-fallback writes the matched entries as JSON and reports unmatched rows', function (): void {
    caiSection(['codice_cai' => '9216049', 'name' => 'SEZ. ABBIATEGRASSO']);

    $xlsxPath = writeCaiTaxCodeFallbackFixtureXlsx([
        ['C.A.I. SEZIONE DI ABBIATEGRASSO', '90000340159', ''],
        ['GR LOMBARDIA', '12345678901', ''],
    ]);
    $outputPath = tempnam(sys_get_temp_dir(), 'cai-tax-code-fallback-output-').'.json';

    $this->artisan('cai:generate-tax-code-fallback', ['--path' => $xlsxPath, '--output' => $outputPath])
        ->expectsOutputToContain('1 sezioni CF/PIVA scritte')
        ->expectsOutputToContain('1 righe del foglio senza corrispondenza')
        ->expectsOutputToContain('GR LOMBARDIA')
        ->assertExitCode(0);

    $written = json_decode((string) file_get_contents($outputPath), true);
    unlink($xlsxPath);
    unlink($outputPath);

    expect($written)->toBe([
        '9216049' => ['name' => 'C.A.I. SEZIONE DI ABBIATEGRASSO', 'tax_code' => '90000340159', 'vat_number' => null],
    ]);
});

test('cai:generate-tax-code-fallback fails when the source Excel file does not exist', function (): void {
    $this->artisan('cai:generate-tax-code-fallback', ['--path' => '/nonexistent/foglio.xlsx'])
        ->expectsOutputToContain('File Excel non trovato')
        ->assertExitCode(1);
});
