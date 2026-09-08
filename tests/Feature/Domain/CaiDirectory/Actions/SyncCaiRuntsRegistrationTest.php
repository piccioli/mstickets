<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Actions\SyncCaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiBoardMember;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function fakeRuntsEntityFound(string $codiceFiscale, array $overrides = []): void
{
    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(array_merge([
            'found' => true,
            'entity' => [
                'id_runts' => '12345',
                'codice_fiscale' => $codiceFiscale,
                'denominazione' => 'Sezione di Como',
                'forma_giuridica' => null, 'natura_giuridica' => null,
                'sede_indirizzo' => 'Via Roma', 'sede_civico' => '1', 'sede_comune' => 'Como',
                'sede_provincia' => 'CO', 'sede_regione' => 'LOMBARDIA', 'sede_cap' => '22100',
                'data_iscrizione' => '24/02/2023', 'sezione_registro' => 'APS',
                'settori_attivita' => null, 'rappresentante_legale' => 'Mario Rossi',
                'sito_web' => 'https://caicomo.it', 'pec' => 'como@pec.cai.it',
                'url_dettaglio' => 'https://servizi.lavoro.gov.it/detail/12345',
            ],
            'board_members' => [
                ['ruolo' => 'presidente', 'nome' => 'Mario', 'cognome' => 'Rossi', 'codice_fiscale' => 'RSSMRA80A01H501X', 'valid_from' => '24/02/2023', 'valid_to' => null],
            ],
            'documents' => [],
        ], $overrides)),
    ]);
}

test('run returns a not-found result and writes nothing when the scraper reports found: false', function (): void {
    $section = caiSection(['tax_code' => '01234567890']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(['found' => false]),
    ]);

    $result = app(SyncCaiRuntsRegistration::class)->run($section);

    expect($result->found)->toBeFalse();
    expect($result->registration)->toBeNull();
    expect(CaiRuntsRegistration::query()->count())->toBe(0);
});

test('run returns a not-found result without calling the scraper when the section has no tax_code', function (): void {
    $section = caiSection(['tax_code' => null]);

    Http::fake();

    $result = app(SyncCaiRuntsRegistration::class)->run($section);

    expect($result->found)->toBeFalse();
    Http::assertNothingSent();
});

test('run creates a new CaiRuntsRegistration and its board members, and bumps runts_last_synced_at', function (): void {
    $section = caiSection(['tax_code' => '01234567890']);
    fakeRuntsEntityFound('01234567890');

    $result = app(SyncCaiRuntsRegistration::class)->run($section);

    expect($result->found)->toBeTrue();

    $registration = CaiRuntsRegistration::query()->findOrFail('12345');
    expect($registration->name)->toBe('Sezione di Como');
    expect($registration->cai_section_id)->toBe($section->codice_cai);
    expect($registration->runts_last_synced_at)->not->toBeNull();

    $boardMember = CaiBoardMember::query()->where('cai_runts_registration_id', '12345')->sole();
    expect($boardMember->full_name)->toBe('Mario Rossi');
});

test('run updates an existing CaiRuntsRegistration and always bumps runts_last_synced_at', function (): void {
    $section = caiSection(['tax_code' => '01234567890']);
    $existing = caiRuntsRegistration(['id_runts' => '12345', 'cai_section_id' => $section->codice_cai, 'name' => 'Vecchio nome', 'runts_last_synced_at' => null]);
    fakeRuntsEntityFound('01234567890');

    app(SyncCaiRuntsRegistration::class)->run($section);

    $registration = $existing->fresh();
    expect($registration->name)->toBe('Sezione di Como');
    expect($registration->runts_last_synced_at)->not->toBeNull();
});
