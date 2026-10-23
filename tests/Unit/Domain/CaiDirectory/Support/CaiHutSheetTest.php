<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiHutContractStatus;
use App\Domain\CaiDirectory\Models\CaiHut;
use App\Domain\CaiDirectory\Support\CaiHutSheet;
use Carbon\CarbonImmutable;

/** @param  array<string, mixed>  $attributes */
function hutSheet(array $attributes = []): CaiHutSheet
{
    return new CaiHutSheet((new CaiHut)->forceFill(array_merge(['id_cai' => 7, 'name' => 'Rifugio'], $attributes)));
}

function hutSheetWithContractEnd(?string $end): CaiHutSheet
{
    return hutSheet(['management' => ['contract' => ['end_date' => $end]]]);
}

test('the contract status follows the thresholds of the design', function (?string $end, ?CaiHutContractStatus $status, ?string $note): void {
    $today = CarbonImmutable::parse('2026-10-23');
    $sheet = hutSheetWithContractEnd($end);

    expect($sheet->contractStatus($today))->toBe($status)
        ->and($sheet->contractNote($today))->toBe($note);
})->with([
    'no end date' => [null, null, null],
    'expired four months ago' => ['2026-06-30', CaiHutContractStatus::Expired, 'Scaduto da 4 mesi'],
    'expired yesterday' => ['2026-10-22', CaiHutContractStatus::Expired, 'Scaduto da 1 giorno'],
    'expired twelve days ago' => ['2026-10-11', CaiHutContractStatus::Expired, 'Scaduto da 12 giorni'],
    'expires today' => ['2026-10-23', CaiHutContractStatus::Expiring, 'Scade oggi'],
    'expires in five months' => ['2027-03-31', CaiHutContractStatus::Expiring, 'Scade tra 5 mesi'],
    'last expiring day (183)' => ['2027-04-24', CaiHutContractStatus::Expiring, 'Scade tra 6 mesi'],
    'first valid day (184)' => ['2027-04-25', CaiHutContractStatus::Valid, null],
]);

test('contract dates use the dd/mm/yyyy format of the design', function (): void {
    $sheet = hutSheet(['management' => ['contract' => ['start_date' => '2021-04-01', 'end_date' => '2027-03-31']]]);

    expect($sheet->contractStart())->toBe('01/04/2021')
        ->and($sheet->contractEnd())->toBe('31/03/2027');
});

test('the fee is shown in euro, with cents only when present, or as the source text', function (array $contract, ?string $expected): void {
    expect(hutSheet(['management' => ['contract' => $contract]])->fee())->toBe($expected);
})->with([
    'whole' => [['fee' => 48500.0, 'fee_text' => '48500'], '€ 48.500'],
    'with cents' => [['fee' => 48564.05, 'fee_text' => '48564,05'], '€ 48.564,05'],
    'free text' => [['fee' => null, 'fee_text' => '7500 + IVA'], '7500 + IVA'],
    'missing' => [['fee' => null, 'fee_text' => null], null],
]);

test('coordinates are decimal degrees with a comma', function (): void {
    expect(hutSheet(['latitude' => 46.1697, 'longitude' => 10.8764])->coordinates())->toBe('46,16970° N · 10,87640° E')
        ->and(hutSheet()->coordinates())->toBeNull();
});

test('opening periods are joined with their dates', function (): void {
    $sheet = hutSheet(['opening_times' => [
        ['types' => ['estiva', 'scialpinismo primaverile'], 'start_date' => '2026-05-23', 'end_date' => '2026-11-02'],
        ['types' => ['fine settimana'], 'start_date' => null, 'end_date' => null],
        ['types' => ['invernale'], 'start_date' => '2025-12-20', 'end_date' => '2026-04-06'],
        ['types' => [], 'start_date' => null, 'end_date' => null],
    ]]);

    expect($sheet->openingTimes())->toBe('Estiva e scialpinismo primaverile: 23 maggio – 2 novembre 2026 · Fine settimana · Invernale: 20 dicembre 2025 – 6 aprile 2026')
        ->and(hutSheet()->openingTimes())->toBeNull()
        ->and(hutSheet(['opening_times' => [['types' => [], 'start_date' => null, 'end_date' => null]]])->openingTimes())->toBeNull();
});

test('contacts become clickable links', function (): void {
    $contacts = collect(hutSheet(['contacts' => [
        'mobile_phone' => '+39 333 1234567',
        'email' => 'info@rifugio.it',
        'website' => 'www.rifugio.it',
        'facebook' => 'rifugiorossi',
        'instagram' => 'https://www.instagram.com/rifugio_rossi/',
    ]])->contacts())->keyBy('label');

    expect($contacts['Telefono'])->toBe(['label' => 'Telefono', 'value' => '+39 333 1234567', 'href' => 'tel:+393331234567'])
        ->and($contacts['Email']['href'])->toBe('mailto:info@rifugio.it')
        ->and($contacts['Sito web'])->toBe(['label' => 'Sito web', 'value' => 'rifugio.it', 'href' => 'https://www.rifugio.it'])
        ->and($contacts['Facebook']['href'])->toBe('https://facebook.com/rifugiorossi')
        ->and($contacts['Instagram'])->toBe(['label' => 'Instagram', 'value' => 'instagram.com/rifugio_rossi', 'href' => 'https://www.instagram.com/rifugio_rossi/']);
});

test('services and certifications are yes, no or unknown', function (): void {
    $sheet = hutSheet([
        'services' => ['wifi' => 1, 'docce' => 0, 'ristorante' => null],
        'management' => ['certifications' => ['photovoltaic' => 1, 'iso14001' => 0, 'buildYear' => '1908']],
    ]);
    $services = collect($sheet->services())->pluck('value', 'label');
    $certifications = collect($sheet->certifications())->pluck('value', 'label');

    expect($services)->toHaveCount(15)
        ->and($services['Wi-Fi'])->toBe(1)
        ->and($services['Docce'])->toBe(0)
        ->and($services['Ristorante'])->toBeNull()
        ->and($services['Mountain bike'])->toBeNull()
        ->and($certifications)->toHaveCount(8)
        ->and($certifications['Fotovoltaico'])->toBe(1)
        ->and($certifications['ISO 14001'])->toBe(0)
        ->and($certifications['Generatore'])->toBeNull()
        ->and(array_map(CaiHutSheet::flagLabel(...), [1, 0, null]))->toBe(['Sì', 'No', '—']);
});

test('the manager and the possession type fall back on the other source fields', function (): void {
    $sheet = hutSheet(['management' => [
        'manager' => ['name' => 'Mario', 'surname' => 'Casanova', 'phone' => '+39 0463 758585'],
        'possession_type' => null,
        'property' => ['possession_type' => 'Affitto a gestore'],
    ]]);
    $manager = collect($sheet->manager())->pluck('value', 'label');

    expect($sheet->hasManager())->toBeTrue()
        ->and($manager['Nome e cognome'])->toBe('Mario Casanova')
        ->and($manager['Telefono'])->toBe('+39 0463 758585')
        ->and($manager['Società'])->toBeNull()
        ->and($sheet->possessionType())->toBe('Affitto a gestore')
        ->and(hutSheet()->hasManager())->toBeFalse();
});

test('an unknown status of the source is shown as raw text', function (): void {
    expect(hutSheet(['fields' => ['status' => 'Rudere']])->statusLabel())->toBe('Rudere')
        ->and(hutSheet()->sourceUrl())->toBe('https://rifugi.cai.it/shelters/7');
});
