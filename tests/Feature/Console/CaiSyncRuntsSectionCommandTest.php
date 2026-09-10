<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('cai:sync-runts-section syncs a single section by codice_cai and reports success', function (): void {
    $section = caiSection(['codice_cai' => '9226003', 'tax_code' => '01234567890']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response([
            'found' => true,
            'entity' => [
                'id_runts' => '12345', 'codice_fiscale' => '01234567890',
                'denominazione' => 'Sezione di Pisa', 'forma_giuridica' => null,
                'natura_giuridica' => null, 'sede_indirizzo' => null, 'sede_civico' => null,
                'sede_comune' => null, 'sede_provincia' => null, 'sede_regione' => null,
                'sede_cap' => null, 'data_iscrizione' => null, 'sezione_registro' => null,
                'settori_attivita' => null, 'rappresentante_legale' => null, 'sito_web' => null,
                'pec' => null, 'url_dettaglio' => null,
            ],
            'board_members' => [],
            'documents' => [],
        ]),
    ]);

    $this->artisan('cai:sync-runts-section', ['codice_cai' => '9226003'])->assertExitCode(0);

    expect(CaiRuntsRegistration::query()->findOrFail('12345')->name)->toBe('Sezione di Pisa');
});

test('cai:sync-runts-section reports when no RUNTS registration is found', function (): void {
    $section = caiSection(['codice_cai' => '9226003', 'tax_code' => '01234567890']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(['found' => false]),
    ]);

    $this->artisan('cai:sync-runts-section', ['codice_cai' => '9226003'])
        ->expectsOutputToContain('Nessuna registrazione RUNTS trovata')
        ->assertExitCode(0);
});

test('cai:sync-runts-section fails explicitly when the codice_cai does not exist', function (): void {
    $this->artisan('cai:sync-runts-section', ['codice_cai' => 'DOES-NOT-EXIST'])
        ->expectsOutputToContain('Nessuna sezione CAI con codice')
        ->assertExitCode(1);
});

test('cai:sync-runts-section reports an error explicitly when the scrape fails, without an unhandled exception', function (): void {
    caiSection(['codice_cai' => '9226003', 'tax_code' => '01234567890']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(['detail' => 'Playwright timeout'], 502),
    ]);

    $this->artisan('cai:sync-runts-section', ['codice_cai' => '9226003'])
        ->expectsOutputToContain('Sincronizzazione fallita')
        ->assertExitCode(1);
});
