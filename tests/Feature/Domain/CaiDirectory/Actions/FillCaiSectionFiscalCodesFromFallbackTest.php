<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Actions\FillCaiSectionFiscalCodesFromFallback;
use App\Domain\CaiDirectory\Support\CaiTaxCodeFallbackRepository;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<string, array{name: string, tax_code: ?string, vat_number: ?string}>  $entries
 */
function fillActionWithFallback(array $entries): FillCaiSectionFiscalCodesFromFallback
{
    $path = tempnam(sys_get_temp_dir(), 'cai-tax-code-fallback-');
    file_put_contents($path, json_encode($entries));

    return new FillCaiSectionFiscalCodesFromFallback(new CaiTaxCodeFallbackRepository($path));
}

test('run fills a missing tax_code and vat_number from the fallback', function (): void {
    $section = caiSection(['codice_cai' => '9216049', 'tax_code' => null, 'vat_number' => null]);

    $action = fillActionWithFallback([
        '9216049' => ['name' => 'Sezione di Como', 'tax_code' => '90000340159', 'vat_number' => '01234567890'],
    ]);

    $result = $action->run(User::system());

    expect($result->read)->toBe(1)
        ->and($result->updated)->toBe(1)
        ->and($result->skipped)->toBe(0);

    $section->refresh();
    expect($section->tax_code)->toBe('90000340159')
        ->and($section->vat_number)->toBe('01234567890');
});

test('run never overwrites a value already present, even if the fallback disagrees', function (): void {
    $section = caiSection(['codice_cai' => '9216049', 'tax_code' => '11111111111', 'vat_number' => null]);

    $action = fillActionWithFallback([
        '9216049' => ['name' => 'Sezione di Como', 'tax_code' => '90000340159', 'vat_number' => '01234567890'],
    ]);

    $action->run(User::system());

    $section->refresh();
    expect($section->tax_code)->toBe('11111111111')
        ->and($section->vat_number)->toBe('01234567890');
});

test('run skips a section missing data with no corresponding fallback entry', function (): void {
    caiSection(['codice_cai' => '9216049', 'tax_code' => null, 'vat_number' => null]);

    $action = fillActionWithFallback([]);

    $result = $action->run(User::system());

    expect($result->read)->toBe(1)
        ->and($result->updated)->toBe(0)
        ->and($result->skipped)->toBe(1);
});

test('run in dry-run mode reports what would change without writing', function (): void {
    $section = caiSection(['codice_cai' => '9216049', 'tax_code' => null, 'vat_number' => null]);

    $action = fillActionWithFallback([
        '9216049' => ['name' => 'Sezione di Como', 'tax_code' => '90000340159', 'vat_number' => null],
    ]);

    $result = $action->run(User::system(), dryRun: true);

    expect($result->updated)->toBe(1);

    $section->refresh();
    expect($section->tax_code)->toBeNull();
});

test('run never selects a section that already has both tax_code and vat_number', function (): void {
    caiSection(['codice_cai' => '9216049', 'tax_code' => '90000340159', 'vat_number' => '01234567890']);

    $action = fillActionWithFallback([
        '9216049' => ['name' => 'Sezione di Como', 'tax_code' => '99999999999', 'vat_number' => '99999999999'],
    ]);

    $result = $action->run(User::system());

    expect($result->read)->toBe(0);
});
