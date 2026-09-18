<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

test('cai:fill-tax-codes-from-fallback fills missing fields from the configured fallback file and reports a summary', function (): void {
    $section = caiSection(['codice_cai' => '9216049', 'tax_code' => null, 'vat_number' => null]);
    caiSection(['codice_cai' => '9216050', 'tax_code' => '90000340159', 'vat_number' => '01234567890']);

    $fallbackPath = tempnam(sys_get_temp_dir(), 'cai-tax-code-fallback-');
    file_put_contents($fallbackPath, json_encode([
        '9216049' => ['name' => 'Sezione di Abbiategrasso', 'tax_code' => '90000340159', 'vat_number' => null],
    ]));
    Config::set('cai_directory.tax_code_fallback_path', $fallbackPath);

    $this->artisan('cai:fill-tax-codes-from-fallback')
        ->expectsOutputToContain('1 sezioni esaminate, 1 aggiornate, 0 senza corrispondenza nel fallback')
        ->assertExitCode(0);

    unlink($fallbackPath);

    expect($section->fresh()->tax_code)->toBe('90000340159');
});
