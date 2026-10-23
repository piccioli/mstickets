<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\CaiHutFieldMapper;

test('parses the contract fee in italian format', function (mixed $raw, ?float $expected): void {
    expect(CaiHutFieldMapper::amount($raw))->toBe($expected);
})->with([
    ['48564,05', 48564.05],
    ['17.080,00', 17080.0],
    ['22.000', 22000.0],
    ['552,22 €', 552.22],
    ['7000€', 7000.0],
    ['6000', 6000.0],
    ['gratuita', null],
    ['7500 + IVA', null],
    ['None', null],
    ['', null],
    [null, null],
]);

test('accepts only Y-m-d dates', function (mixed $raw, ?string $expected): void {
    expect(CaiHutFieldMapper::date($raw))->toBe($expected);
})->with([
    ['2027-03-31', '2027-03-31'],
    ['2027-02-30', null],
    ['31/03/2027', null],
    ['None', null],
    ['', null],
    [null, null],
]);

test('maps service values to 1, 0 or null', function (mixed $raw, ?int $expected): void {
    expect(CaiHutFieldMapper::flag($raw))->toBe($expected);
})->with([
    ['1', 1], ['0', 0], ['2', 1], ['Segnale GSM', 1], ['Assente', 0], ['Nessuna', 0], [' ', null], [null, null],
]);

test('maps a raw API detail to the cai_huts attributes', function (): void {
    $attributes = CaiHutFieldMapper::map([
        'id_cai' => 921200201,
        'title' => 'GIORDANO FELICE',
        'slug' => 'giordano-felice',
        'geo' => ['type' => 'Point', 'coordinates' => [7.858085, 45.91364]],
        'updated_at' => '2023-09-29T15:49:56.000000Z',
        'fields' => [
            ['name' => 'branch_id', 'value' => '9212002', 'data' => null],
            ['name' => 'branch', 'value' => 'Varallo Sesia', 'data' => null],
            ['name' => 'type', 'value' => 'Bivacco', 'data' => null],
            ['name' => 'status', 'value' => 'In attività', 'data' => null],
            ['name' => 'alias', 'value' => '', 'data' => null],
            ['name' => 'altitude_geo', 'value' => 4167, 'data' => null],
            ['name' => 'posti_totali_service', 'value' => '6', 'data' => null],
            ['name' => 'posti_letto_invernali_service', 'value' => '6', 'data' => null],
            ['name' => 'fixedPhone_contact', 'value' => null, 'data' => null],
            ['name' => 'webAddress_contact', 'value' => 'https://www.caivarallo.com', 'data' => null],
            ['name' => 'name_property', 'value' => 'CAI sezione di Varallo Sesia', 'data' => null],
            ['name' => 'contract_fee_property', 'value' => '157,77', 'data' => null],
            ['name' => 'photovoltaic_certification', 'value' => '1', 'data' => null],
            ['name' => 'heating_type_certification', 'value' => 'Assente', 'data' => null],
            ['name' => 'openingTime_contact', 'value' => null, 'data' => [
                ['key' => 'x', 'layout' => 'aperture', 'attributes' => ['type' => '["annuale"]', 'startDate' => null, 'endDate' => null]],
            ]],
        ],
        'media' => [
            ['original_url' => 'http://rifugi.cai.it/media/images/2/b.jpg', 'order_column' => 3],
            ['original_url' => 'http://rifugi.cai.it/media/images/2/a.jpg', 'order_column' => 2],
        ],
    ]);

    expect($attributes)->toMatchArray([
        'branch_code' => '9212002',
        'branch_name' => 'Varallo Sesia',
        'name' => 'GIORDANO FELICE',
        'alias' => null,
        'type' => 'Bivacco',
        'status' => 'In attività',
        'latitude' => 45.91364,
        'longitude' => 7.858085,
        'altitude' => 4167,
        'beds' => 6,
        'winter_beds' => 6,
        'photos' => ['http://rifugi.cai.it/media/images/2/a.jpg', 'http://rifugi.cai.it/media/images/2/b.jpg'],
        'opening_times' => [['types' => ['annuale'], 'start_date' => null, 'end_date' => null]],
        'source_updated_at' => '2023-09-29 15:49:56',
    ])
        ->and($attributes['contacts']['phone'])->toBeNull()
        ->and($attributes['contacts']['website'])->toBe('https://www.caivarallo.com')
        ->and($attributes['management']['property']['name'])->toBe('CAI sezione di Varallo Sesia')
        ->and($attributes['management']['contract']['fee'])->toBe(157.77)
        ->and($attributes['management']['certifications'])->toBe(['heating_type' => 'Assente', 'photovoltaic' => 1])
        ->and($attributes['fields']['branch_id'])->toBe('9212002');
});
