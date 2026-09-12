<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('cai:sync-runts-all syncs every section with a tax_code and reports a summary', function (): void {
    caiSection(['codice_cai' => '9226003', 'tax_code' => '01234567890']);
    caiSection(['codice_cai' => '9226004', 'tax_code' => '09876543210']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::sequence()
            ->push([
                'found' => true,
                'entity' => [
                    'id_runts' => '111', 'codice_fiscale' => '01234567890',
                    'denominazione' => 'Sezione Uno', 'forma_giuridica' => null,
                    'natura_giuridica' => null, 'sede_indirizzo' => null, 'sede_civico' => null,
                    'sede_comune' => null, 'sede_provincia' => null, 'sede_regione' => null,
                    'sede_cap' => null, 'data_iscrizione' => null, 'sezione_registro' => null,
                    'settori_attivita' => null, 'rappresentante_legale' => null, 'sito_web' => null,
                    'pec' => null, 'url_dettaglio' => null,
                ],
                'board_members' => [],
                'documents' => [],
            ])
            ->push([
                'found' => true,
                'entity' => [
                    'id_runts' => '222', 'codice_fiscale' => '09876543210',
                    'denominazione' => 'Sezione Due', 'forma_giuridica' => null,
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

    $this->artisan('cai:sync-runts-all')
        ->expectsOutputToContain('2 sezioni esaminate, 2 sincronizzate, 0 non trovate, 0 errori')
        ->assertExitCode(0);

    expect(CaiRuntsRegistration::query()->findOrFail('111')->name)->toBe('Sezione Uno');
    expect(CaiRuntsRegistration::query()->findOrFail('222')->name)->toBe('Sezione Due');
});

test('cai:sync-runts-all skips sections without a tax_code, never calling the scraper for them', function (): void {
    caiSection(['codice_cai' => '9226005', 'tax_code' => null]);

    Http::fake();

    $this->artisan('cai:sync-runts-all')
        ->expectsOutputToContain('0 sezioni esaminate, 0 sincronizzate, 0 non trovate, 0 errori')
        ->assertExitCode(0);

    Http::assertNothingSent();
});

test('cai:sync-runts-all continues with the next section when one fails, without stopping the batch', function (): void {
    caiSection(['codice_cai' => '9226006', 'tax_code' => '01234567890']);
    caiSection(['codice_cai' => '9226007', 'tax_code' => '09876543210']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::sequence()
            ->push(['detail' => 'Playwright timeout'], 502)
            ->push([
                'found' => true,
                'entity' => [
                    'id_runts' => '333', 'codice_fiscale' => '09876543210',
                    'denominazione' => 'Sezione Tre', 'forma_giuridica' => null,
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

    $this->artisan('cai:sync-runts-all')
        ->expectsOutputToContain('2 sezioni esaminate, 1 sincronizzate, 0 non trovate, 1 errori')
        ->assertExitCode(0);

    expect(CaiRuntsRegistration::query()->findOrFail('333')->name)->toBe('Sezione Tre');
});

test('cai:sync-runts-all counts a not-found section separately from a synced one', function (): void {
    caiSection(['codice_cai' => '9226008', 'tax_code' => '01234567890']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(['found' => false]),
    ]);

    $this->artisan('cai:sync-runts-all')
        ->expectsOutputToContain('1 sezioni esaminate, 0 sincronizzate, 1 non trovate, 0 errori')
        ->assertExitCode(0);
});

test('cai:sync-runts-all --limit processes only the first N sections', function (): void {
    caiSection(['codice_cai' => '9226009', 'tax_code' => '01234567890']);
    caiSection(['codice_cai' => '9226010', 'tax_code' => '09876543210']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(['found' => false]),
    ]);

    $this->artisan('cai:sync-runts-all', ['--limit' => 1])
        ->expectsOutputToContain('1 sezioni esaminate, 0 sincronizzate, 1 non trovate, 0 errori')
        ->assertExitCode(0);

    Http::assertSentCount(1);
});
