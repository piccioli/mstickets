<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\CaiApiSectionNormalizer;

test('normalizeSection converts raw CAI API fields to the cai_* row shape', function (): void {
    $raw = [
        'code' => '9216049',
        'name' => 'Sezione di Como',
        'cf' => '01234567890',
        'vat' => '09876543210',
        'email' => 'como@cai.it',
        'pec' => 'como@pec.cai.it',
        'officePhone' => '031 111111',
        'phone' => '031 222222',
        'fax' => '031 333333',
        'officeAddress' => ['address1' => 'Via Roma', 'number' => '1', 'zip' => '22100', 'city' => 'Como', 'province' => 'CO', 'nation' => 'Italia'],
        'postalAddress' => null,
        'website' => 'https://caicomo.it',
        'timetable' => 'Lun-Ven 18-19',
        'notice' => 'Chiuso ad agosto',
        'foundationYear' => 1891,
        'lastyearMembershipsCount' => 450,
        'latitude' => 45.81,
        'longitude' => 9.08,
        'region' => 'lombardia',
    ];

    $row = CaiApiSectionNormalizer::normalizeSection($raw);

    expect($row->codice_cai)->toBe('9216049');
    expect($row->cai_denominazione)->toBe('Sezione di Como');
    expect($row->cai_codice_fiscale)->toBe('01234567890');
    expect($row->cai_partita_iva)->toBe('09876543210');
    expect($row->cai_email)->toBe('como@cai.it');
    expect($row->cai_pec)->toBe('como@pec.cai.it');
    expect($row->cai_telefono_sede)->toBe('031 111111');
    expect($row->cai_telefono)->toBe('031 222222');
    expect($row->cai_fax)->toBe('031 333333');
    expect(json_decode($row->cai_indirizzo_sede, true))->toBe(['address1' => 'Via Roma', 'number' => '1', 'zip' => '22100', 'city' => 'Como', 'province' => 'CO', 'nation' => 'Italia']);
    expect($row->cai_indirizzo_postale)->toBeNull();
    expect($row->cai_sito_web)->toBe('https://caicomo.it');
    expect($row->cai_orari)->toBe('Lun-Ven 18-19');
    expect($row->cai_avvisi)->toBe('Chiuso ad agosto');
    expect($row->cai_anno_fondazione)->toBe(1891);
    expect($row->cai_soci_ultimo_anno)->toBe(450);
    expect($row->cai_lat)->toBe(45.81);
    expect($row->cai_lon)->toBe(9.08);
    expect($row->cai_regione)->toBe('LOMBARDIA');
});

test('normalizeSection falls back to office_address/postal_address snake_case keys', function (): void {
    $raw = [
        'code' => '9216050',
        'name' => 'Sezione Alternativa',
        'office_address' => ['rawAddress' => 'Via Test 5, Milano'],
        'postal_address' => null,
        'region' => 'lombardia',
    ];

    $row = CaiApiSectionNormalizer::normalizeSection($raw);

    expect(json_decode($row->cai_indirizzo_sede, true))->toBe(['rawAddress' => 'Via Test 5, Milano']);
});

test('normalizeSubsection converts raw CAI API fields to the cai_* row shape, scoped to the parent section code', function (): void {
    $raw = [
        'code' => 'SUB-1',
        'name' => 'Sottosezione Erba',
        'email' => null,
        'officePhone' => null,
        'phone' => null,
        'officeAddress' => null,
        'website' => null,
        'timetable' => null,
        'notice' => null,
        'foundationYear' => null,
        'currentMemberships' => 30,
        'latitude' => null,
        'longitude' => null,
    ];

    $row = CaiApiSectionNormalizer::normalizeSubsection($raw, '9216049');

    expect($row->cai_codice)->toBe('SUB-1');
    expect($row->cai_sezione_codice)->toBe('9216049');
    expect($row->cai_nome)->toBe('Sottosezione Erba');
    expect($row->cai_soci)->toBe(30);
    expect($row->cai_indirizzo_sede)->toBeNull();
});

test('normalizeSubsection falls back to lastyearMembershipsCount when currentMemberships is absent', function (): void {
    $raw = ['code' => 'SUB-2', 'name' => 'Sottosezione B', 'lastyearMembershipsCount' => 12];

    $row = CaiApiSectionNormalizer::normalizeSubsection($raw, '9216049');

    expect($row->cai_soci)->toBe(12);
});
