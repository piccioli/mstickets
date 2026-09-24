<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\CaiSectionFieldMapper;

test('mapSection maps a normalized cai_* row to CaiSection attributes', function (): void {
    $row = (object) [
        'codice_cai' => '9216049',
        'cai_denominazione' => 'Sezione di Como',
        'cai_codice_fiscale' => '01234567890',
        'cai_partita_iva' => '09876543210',
        'cai_email' => 'Como@Cai.It',
        'cai_pec' => 'como@pec.cai.it',
        'cai_telefono_sede' => '031 111111',
        'cai_telefono' => '031 222222',
        'cai_fax' => '031 333333',
        'cai_indirizzo_sede' => '{"address1":"Via Roma","number":"1","zip":"22100","city":"Como","province":"CO","nation":"Italia"}',
        'cai_indirizzo_postale' => null,
        'cai_sito_web' => 'https://caicomo.it',
        'cai_orari' => 'Lun-Ven 18-19',
        'cai_avvisi' => 'Chiuso ad agosto',
        'cai_anno_fondazione' => '1891',
        'cai_soci_ultimo_anno' => '450',
        'cai_lat' => '45.81',
        'cai_lon' => '9.08',
        'cai_regione' => 'LOMBARDIA',
    ];

    $attributes = CaiSectionFieldMapper::mapSection($row, ['como@cai.it' => 42]);

    expect($attributes)->toBe([
        'name' => 'Sezione di Como',
        'tax_code' => '01234567890',
        'vat_number' => '09876543210',
        'email' => 'Como@Cai.It',
        'pec' => 'como@pec.cai.it',
        'phone_office' => '031 111111',
        'phone' => '031 222222',
        'fax' => '031 333333',
        'address' => 'Via Roma 1, 22100 Como (CO), Italia',
        'postal_address' => null,
        'website' => 'https://caicomo.it',
        'office_hours' => 'Lun-Ven 18-19',
        'notices' => 'Chiuso ad agosto',
        'founded_year' => 1891,
        'members_count' => 450,
        'latitude' => 45.81,
        'longitude' => 9.08,
        'region' => 'LOMBARDIA',
        'user_id' => 42,
    ]);
});

test('mapSubsection maps a normalized cai_* row to CaiSubsection attributes', function (): void {
    $row = (object) [
        'cai_codice' => 'SUB-1',
        'cai_sezione_codice' => '9216049',
        'cai_nome' => 'Sottosezione Erba',
        'cai_email' => null,
        'cai_telefono_sede' => null,
        'cai_telefono' => null,
        'cai_indirizzo_sede' => null,
        'cai_sito_web' => null,
        'cai_orari' => null,
        'cai_avvisi' => null,
        'cai_anno_fondazione' => null,
        'cai_soci' => '30',
        'cai_lat' => null,
        'cai_lon' => null,
    ];

    $attributes = CaiSectionFieldMapper::mapSubsection($row, []);

    expect($attributes)->toBe([
        'cai_section_id' => '9216049',
        'name' => 'Sottosezione Erba',
        'email' => null,
        'phone_office' => null,
        'phone' => null,
        'address' => null,
        'website' => null,
        'office_hours' => null,
        'notices' => null,
        'founded_year' => null,
        'members_count' => 30,
        'latitude' => null,
        'longitude' => null,
        'user_id' => null,
    ]);
});

test('toCoordinate discards implausible values (|x| >= 1000)', function (): void {
    expect(CaiSectionFieldMapper::toCoordinate('25614'))->toBeNull();
    expect(CaiSectionFieldMapper::toCoordinate('45.81'))->toBe(45.81);
    expect(CaiSectionFieldMapper::toCoordinate(null))->toBeNull();
});

test('matchUserId is a case-insensitive, trimmed email lookup', function (): void {
    $usersByLowerEmail = ['como@cai.it' => 42];

    expect(CaiSectionFieldMapper::matchUserId('  Como@Cai.It  ', $usersByLowerEmail))->toBe(42);
    expect(CaiSectionFieldMapper::matchUserId(null, $usersByLowerEmail))->toBeNull();
    expect(CaiSectionFieldMapper::matchUserId('', $usersByLowerEmail))->toBeNull();
    expect(CaiSectionFieldMapper::matchUserId('nobody@example.test', $usersByLowerEmail))->toBeNull();
});
