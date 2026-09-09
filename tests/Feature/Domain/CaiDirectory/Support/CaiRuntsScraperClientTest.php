<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Support\CaiRuntsScraperClient;
use Illuminate\Support\Facades\Http;

test('scrapeEntity sends codice_fiscale as a query parameter and returns the decoded JSON', function (): void {
    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(['found' => false]),
    ]);

    $result = app(CaiRuntsScraperClient::class)->scrapeEntity('01234567890');

    expect($result)->toBe(['found' => false]);
    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'codice_fiscale=01234567890'));
});

test('analyzeBilancio sends the PDF as a multipart file upload and returns the decoded JSON', function (): void {
    Http::fake([
        'http://cai-runts-scraper:8000/analyze/bilancio' => Http::response(['totale_oneri' => 1200.0]),
    ]);

    $result = app(CaiRuntsScraperClient::class)->analyzeBilancio('%PDF-1.4 fixture');

    // Nota: `Http::response(['totale_oneri' => 1200.0])` viene serializzato da json_encode() come `1200`
    // (nessuna parte frazionaria, comportamento standard di serialize_precision=-1), quindi il round-trip
    // json_decode() restituisce un int, non un float — verificato empiricamente su questo ambiente PHP.
    expect($result)->toBe(['totale_oneri' => 1200]);
    Http::assertSent(fn ($request): bool => $request->hasFile('file'));
});

test('checkEntityExists sends codice_fiscale as a query parameter and returns the found flag', function (): void {
    Http::fake([
        'http://cai-runts-scraper:8000/search/runts-entity*' => Http::response(['found' => true]),
    ]);

    $result = app(CaiRuntsScraperClient::class)->checkEntityExists('01234567890');

    expect($result)->toBeTrue();
    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'codice_fiscale=01234567890'));
});

test('checkEntityExists returns false when the service reports found: false', function (): void {
    Http::fake([
        'http://cai-runts-scraper:8000/search/runts-entity*' => Http::response(['found' => false]),
    ]);

    expect(app(CaiRuntsScraperClient::class)->checkEntityExists('00000000000'))->toBeFalse();
});
