<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Support\CaiSectionRuntsComparator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $attributes
 */
function caiSectionForComparator(array $attributes = []): CaiSection
{
    return CaiSection::create(array_merge([
        'codice_cai' => 'CAI-CMP',
        'name' => 'Sezione Comparata',
        'region' => 'LOMBARDIA',
    ], $attributes));
}

/**
 * @param  array<string, mixed>  $attributes
 */
function caiRuntsRegistrationForComparator(CaiSection $section, array $attributes = []): CaiRuntsRegistration
{
    return CaiRuntsRegistration::create(array_merge([
        'id_runts' => 'RUNTS-CMP',
        'cai_section_id' => $section->codice_cai,
        'tax_code' => $section->tax_code,
        'name' => $section->name,
    ], $attributes));
}

test('returns no rows when the section has no linked runts registration', function (): void {
    $section = caiSectionForComparator();

    expect(CaiSectionRuntsComparator::compare($section))->toBe([]);
});

test('flags a field as equal when both sides carry the same trimmed value', function (): void {
    $section = caiSectionForComparator(['pec' => 'sezione@pec.example.com']);
    caiRuntsRegistrationForComparator($section, ['pec' => ' sezione@pec.example.com ']);

    $rows = CaiSectionRuntsComparator::compare($section);
    $pecRow = collect($rows)->firstWhere('field', 'PEC');

    expect($pecRow)
        ->not->toBeNull()
        ->and($pecRow['cai_value'])->toBe('sezione@pec.example.com')
        ->and($pecRow['runts_value'])->toBe('sezione@pec.example.com')
        ->and($pecRow['status'])->toBe('Uguale');
});

test('flags a field as different when the two sides genuinely differ', function (): void {
    $section = caiSectionForComparator(['name' => 'Sezione Nome CAI']);
    caiRuntsRegistrationForComparator($section, ['name' => 'Denominazione RUNTS diversa']);

    $rows = CaiSectionRuntsComparator::compare($section);
    $nameRow = collect($rows)->firstWhere('field', 'Denominazione');

    expect($nameRow['cai_value'])->toBe('Sezione Nome CAI')
        ->and($nameRow['runts_value'])->toBe('Denominazione RUNTS diversa')
        ->and($nameRow['status'])->toBe('Diverso');
});

test('flags a field as not applicable when both sides are empty', function (): void {
    $section = caiSectionForComparator(['website' => null]);
    caiRuntsRegistrationForComparator($section, ['website' => null]);

    $rows = CaiSectionRuntsComparator::compare($section);
    $websiteRow = collect($rows)->firstWhere('field', 'Sito web');

    expect($websiteRow['status'])->toBe('N/D');
});

test('produces one block of rows per linked registration, labelled by registration name', function (): void {
    $section = caiSectionForComparator();
    CaiRuntsRegistration::create([
        'id_runts' => 'RUNTS-A',
        'cai_section_id' => $section->codice_cai,
        'name' => 'Prima registrazione',
    ]);
    CaiRuntsRegistration::create([
        'id_runts' => 'RUNTS-B',
        'cai_section_id' => $section->codice_cai,
        'name' => 'Seconda registrazione',
    ]);

    $labels = collect(CaiSectionRuntsComparator::compare($section))
        ->pluck('registration_label')
        ->unique()
        ->values()
        ->all();

    expect($labels)->toBe(['Prima registrazione', 'Seconda registrazione']);
});

test('composes the RUNTS structured address into a single comparable string', function (): void {
    $section = caiSectionForComparator(['address' => 'Via Roma 1, 20100 Milano (MI)']);
    caiRuntsRegistrationForComparator($section, [
        'address' => 'Via Roma',
        'street_number' => '1',
        'postal_code' => '20100',
        'municipality' => 'Milano',
        'province' => 'MI',
    ]);

    $rows = CaiSectionRuntsComparator::compare($section);
    $addressRow = collect($rows)->firstWhere('field', 'Indirizzo');

    expect($addressRow['runts_value'])->toBe('Via Roma 1, 20100 Milano (MI)')
        ->and($addressRow['status'])->toBe('Uguale');
});
