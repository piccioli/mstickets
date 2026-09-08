<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\CaiRuntsRegistrationFieldMapper;

test('mapRegistration maps a row to CaiRuntsRegistration attributes', function (): void {
    $row = (object) [
        'codice_fiscale' => '01234567890',
        'denominazione' => 'Sezione di Como',
        'forma_giuridica' => 'Associazione',
        'natura_giuridica' => null,
        'sede_indirizzo' => 'Via Roma',
        'sede_civico' => '1',
        'sede_comune' => 'Como',
        'sede_provincia' => 'CO',
        'sede_regione' => 'LOMBARDIA',
        'sede_cap' => '22100',
        'data_iscrizione' => 'Iscritto il 24/02/2023',
        'sezione_registro' => 'APS',
        'settori_attivita' => null,
        'rappresentante_legale' => 'Mario Rossi',
        'sito_web' => 'https://caicomo.it',
        'pec' => 'como@pec.cai.it',
        'url_dettaglio' => 'https://servizi.lavoro.gov.it/detail/12345',
    ];

    $attributes = CaiRuntsRegistrationFieldMapper::mapRegistration($row, '9216049');

    expect($attributes)->toBe([
        'cai_section_id' => '9216049',
        'tax_code' => '01234567890',
        'name' => 'Sezione di Como',
        'legal_form' => 'Associazione',
        'legal_nature' => null,
        'address' => 'Via Roma',
        'street_number' => '1',
        'municipality' => 'Como',
        'province' => 'CO',
        'region' => 'LOMBARDIA',
        'postal_code' => '22100',
        'latitude' => null,
        'longitude' => null,
        'registration_date' => '2023-02-24',
        'register_section' => 'APS',
        'activity_sectors' => null,
        'legal_representative' => 'Mario Rossi',
        'website' => 'https://caicomo.it',
        'pec' => 'como@pec.cai.it',
        'official_page_url' => 'https://servizi.lavoro.gov.it/detail/12345',
    ]);
});

test('mapRegistration reads lat/lon when present (datapack source)', function (): void {
    $row = (object) [
        'codice_fiscale' => null, 'denominazione' => 'X', 'forma_giuridica' => null,
        'natura_giuridica' => null, 'sede_indirizzo' => null, 'sede_civico' => null,
        'sede_comune' => null, 'sede_provincia' => null, 'sede_regione' => null, 'sede_cap' => null,
        'data_iscrizione' => null, 'sezione_registro' => null, 'settori_attivita' => null,
        'rappresentante_legale' => null, 'sito_web' => null, 'pec' => null, 'url_dettaglio' => null,
        'lat' => '45.81', 'lon' => '9.08',
    ];

    $attributes = CaiRuntsRegistrationFieldMapper::mapRegistration($row, '9216049');

    expect($attributes['latitude'])->toBe(45.81);
    expect($attributes['longitude'])->toBe(9.08);
});

test('mapBoardMember concatenates nome+cognome into full_name and parses dates', function (): void {
    $row = (object) [
        'ruolo' => 'presidente',
        'nome' => 'Mario',
        'cognome' => 'Rossi',
        'codice_fiscale' => 'RSSMRA80A01H501X',
        'valid_from' => '24/02/2023',
        'valid_to' => null,
    ];

    $attributes = CaiRuntsRegistrationFieldMapper::mapBoardMember($row, '12345');

    expect($attributes)->toBe([
        'cai_runts_registration_id' => '12345',
        'role' => 'presidente',
        'full_name' => 'Mario Rossi',
        'tax_code' => 'RSSMRA80A01H501X',
        'valid_from' => '2023-02-24',
        'valid_to' => null,
    ]);
});

test('mapBoardMember tolerates missing nome/cognome and produces a null full_name', function (): void {
    $row = (object) ['ruolo' => 'consigliere', 'nome' => null, 'cognome' => null, 'codice_fiscale' => null, 'valid_from' => null, 'valid_to' => null];

    $attributes = CaiRuntsRegistrationFieldMapper::mapBoardMember($row, '12345');

    expect($attributes['full_name'])->toBeNull();
});
