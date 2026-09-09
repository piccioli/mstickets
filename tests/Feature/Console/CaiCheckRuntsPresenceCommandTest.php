<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('cai:check-runts-presence writes runts_registered and runts_presence_checked_at for every section with a tax_code', function (): void {
    $registered = caiSection(['tax_code' => '01234567890', 'runts_registered' => null]);
    $notRegistered = caiSection(['tax_code' => '09876543210', 'runts_registered' => null]);
    caiSection(['tax_code' => null]);

    Http::fake([
        'http://cai-runts-scraper:8000/search/runts-entity*' => function ($request) {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return Http::response(['found' => $query['codice_fiscale'] === '01234567890']);
        },
    ]);

    $this->artisan('cai:check-runts-presence')->assertExitCode(0);

    expect($registered->fresh()->runts_registered)->toBeTrue();
    expect($registered->fresh()->runts_presence_checked_at)->not->toBeNull();

    expect($notRegistered->fresh()->runts_registered)->toBeFalse();
    expect($notRegistered->fresh()->runts_presence_checked_at)->not->toBeNull();
});

test('cai:check-runts-presence --dry-run does not write anything', function (): void {
    $section = caiSection(['tax_code' => '01234567890', 'runts_registered' => null]);

    Http::fake([
        'http://cai-runts-scraper:8000/search/runts-entity*' => Http::response(['found' => true]),
    ]);

    $this->artisan('cai:check-runts-presence', ['--dry-run' => true])->assertExitCode(0);

    expect($section->fresh()->runts_registered)->toBeNull();
    expect($section->fresh()->runts_presence_checked_at)->toBeNull();
});

test('cai:check-runts-presence continues past a section whose check fails', function (): void {
    $failing = caiSection(['tax_code' => '01234567890', 'runts_registered' => null]);
    $ok = caiSection(['tax_code' => '09876543210', 'runts_registered' => null]);

    Http::fake([
        'http://cai-runts-scraper:8000/search/runts-entity*' => function ($request) {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            if ($query['codice_fiscale'] === '01234567890') {
                return Http::response(['detail' => 'Playwright timeout'], 502);
            }

            return Http::response(['found' => true]);
        },
    ]);

    $this->artisan('cai:check-runts-presence')->assertExitCode(0);

    expect($failing->fresh()->runts_registered)->toBeNull();
    expect($ok->fresh()->runts_registered)->toBeTrue();
});

test('cai:check-runts-presence skips sections without a tax_code, never calling the service for them', function (): void {
    caiSection(['tax_code' => null]);

    Http::fake();

    $this->artisan('cai:check-runts-presence')->assertExitCode(0);

    Http::assertNothingSent();
});
