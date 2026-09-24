<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function runtsEntityFoundPayload(string $idRunts, string $codiceFiscale, string $name): array
{
    return [
        'found' => true,
        'entity' => [
            'id_runts' => $idRunts, 'codice_fiscale' => $codiceFiscale,
            'denominazione' => $name, 'forma_giuridica' => null,
            'natura_giuridica' => null, 'sede_indirizzo' => null, 'sede_civico' => null,
            'sede_comune' => null, 'sede_provincia' => null, 'sede_regione' => null,
            'sede_cap' => null, 'data_iscrizione' => null, 'sezione_registro' => null,
            'settori_attivita' => null, 'rappresentante_legale' => null, 'sito_web' => null,
            'pec' => null, 'url_dettaglio' => null,
        ],
        'board_members' => [],
        'documents' => [],
    ];
}

test('cai:sync-runts-all syncs every section with a tax_code and reports a summary', function (): void {
    caiSection(['codice_cai' => '9226003', 'tax_code' => '01234567890']);
    caiSection(['codice_cai' => '9226004', 'tax_code' => '09876543210']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::sequence()
            ->push(runtsEntityFoundPayload('111', '01234567890', 'Sezione Uno'))
            ->push(runtsEntityFoundPayload('222', '09876543210', 'Sezione Due')),
    ]);

    $this->artisan('cai:sync-runts-all', ['--delay-ms' => 0])
        ->expectsOutputToContain('2 sezioni esaminate, 2 sincronizzate, 0 non trovate, 0 errori')
        ->assertExitCode(0);

    expect(CaiRuntsRegistration::query()->findOrFail('111')->name)->toBe('Sezione Uno');
    expect(CaiRuntsRegistration::query()->findOrFail('222')->name)->toBe('Sezione Due');
});

test('cai:sync-runts-all skips sections without a tax_code, never calling the scraper for them', function (): void {
    caiSection(['codice_cai' => '9226005', 'tax_code' => null]);

    Http::fake();

    $this->artisan('cai:sync-runts-all', ['--delay-ms' => 0])
        ->expectsOutputToContain('0 sezioni esaminate, 0 sincronizzate, 0 non trovate, 0 errori')
        ->assertExitCode(0);

    Http::assertNothingSent();
});

test('cai:sync-runts-all skips a section whose tax_code is obviously invalid (e.g. "0" or "."), never calling the scraper', function (): void {
    caiSection(['codice_cai' => '9212004', 'tax_code' => '0']);
    caiSection(['codice_cai' => '9212053', 'tax_code' => '.']);

    Http::fake();

    $this->artisan('cai:sync-runts-all', ['--delay-ms' => 0])
        ->expectsOutputToContain('0 sezioni esaminate, 0 sincronizzate, 0 non trovate, 0 errori, 2 codice fiscale non validi')
        ->assertExitCode(0);

    Http::assertNothingSent();
});

test('cai:sync-runts-all continues with the next section when one fails, without stopping the batch', function (): void {
    caiSection(['codice_cai' => '9226006', 'tax_code' => '01234567890']);
    caiSection(['codice_cai' => '9226007', 'tax_code' => '09876543210']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::sequence()
            ->push(['detail' => 'Playwright timeout'], 502) // 9226006, main pass
            ->push(runtsEntityFoundPayload('333', '09876543210', 'Sezione Tre')) // 9226007, main pass
            ->push(['detail' => 'Playwright timeout'], 502), // 9226006, retry pass — still failing
    ]);

    $this->artisan('cai:sync-runts-all', ['--delay-ms' => 0])
        ->expectsOutputToContain('2 sezioni esaminate, 1 sincronizzate, 0 non trovate, 1 errori')
        ->assertExitCode(0);

    expect(CaiRuntsRegistration::query()->findOrFail('333')->name)->toBe('Sezione Tre');
});

test('cai:sync-runts-all automatically retries a section that failed once, and counts it as synced if the retry recovers', function (): void {
    caiSection(['codice_cai' => '9226011', 'tax_code' => '01234567890']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::sequence()
            ->push(['detail' => 'Playwright timeout'], 502) // main pass — transient site issue
            ->push(runtsEntityFoundPayload('444', '01234567890', 'Sezione Recuperata')), // retry — site recovered
    ]);

    $this->artisan('cai:sync-runts-all', ['--delay-ms' => 0])
        ->expectsOutputToContain('1 sezioni esaminate, 1 sincronizzate, 0 non trovate, 0 errori')
        ->assertExitCode(0);

    expect(CaiRuntsRegistration::query()->findOrFail('444')->name)->toBe('Sezione Recuperata');
});

test('cai:sync-runts-all counts a not-found section separately from a synced one', function (): void {
    caiSection(['codice_cai' => '9226008', 'tax_code' => '01234567890']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(['found' => false]),
    ]);

    $this->artisan('cai:sync-runts-all', ['--delay-ms' => 0])
        ->expectsOutputToContain('1 sezioni esaminate, 0 sincronizzate, 1 non trovate, 0 errori')
        ->assertExitCode(0);
});

test('cai:sync-runts-all fills a missing tax_code from the fallback before selecting sections to sync', function (): void {
    $section = caiSection(['codice_cai' => '9226012', 'tax_code' => null]);

    $fallbackPath = tempnam(sys_get_temp_dir(), 'cai-tax-code-fallback-');
    file_put_contents($fallbackPath, json_encode([
        '9226012' => ['name' => 'Sezione di test', 'tax_code' => '01234567890', 'vat_number' => null],
    ]));
    Config::set('cai_directory.tax_code_fallback_path', $fallbackPath);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(runtsEntityFoundPayload('555', '01234567890', 'Sezione Test')),
    ]);

    $this->artisan('cai:sync-runts-all', ['--delay-ms' => 0])
        ->expectsOutputToContain('Fallback CF/PIVA: 1 sezione/i completata/e dal foglio manuale.')
        ->expectsOutputToContain('1 sezioni esaminate, 1 sincronizzate, 0 non trovate, 0 errori')
        ->assertExitCode(0);

    unlink($fallbackPath);

    expect($section->fresh()->tax_code)->toBe('01234567890');
});

test('cai:sync-runts-all --codes restricts the sync to the given comma-separated codice_cai', function (): void {
    caiSection(['codice_cai' => '9226020', 'tax_code' => '01234567890']);
    caiSection(['codice_cai' => '9226021', 'tax_code' => '09876543210']);
    caiSection(['codice_cai' => '9226022', 'tax_code' => '01111111111']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(['found' => false]),
    ]);

    $this->artisan('cai:sync-runts-all', ['--codes' => '9226020, 9226022', '--delay-ms' => 0])
        ->expectsOutputToContain('2 sezioni esaminate, 0 sincronizzate, 2 non trovate, 0 errori')
        ->assertExitCode(0);

    Http::assertSentCount(2);
});

test('cai:sync-runts-all --limit processes only the first N sections', function (): void {
    caiSection(['codice_cai' => '9226009', 'tax_code' => '01234567890']);
    caiSection(['codice_cai' => '9226010', 'tax_code' => '09876543210']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(['found' => false]),
    ]);

    $this->artisan('cai:sync-runts-all', ['--limit' => 1, '--delay-ms' => 0])
        ->expectsOutputToContain('1 sezioni esaminate, 0 sincronizzate, 1 non trovate, 0 errori')
        ->assertExitCode(0);

    Http::assertSentCount(1);
});
