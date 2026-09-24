<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiRuntsPresenceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('the --timeout option defaults to 10 seconds', function (): void {
    $definition = Artisan::all()['cai:check-runts-presence']->getDefinition();

    expect($definition->hasOption('timeout'))->toBeTrue();
    expect($definition->getOption('timeout')->getDefault())->toBe('10');
});

test('a custom --timeout is accepted without breaking a normal run', function (): void {
    $section = caiSection(['tax_code' => '01234567890', 'runts_presence_status' => null]);

    Http::fake([
        'http://cai-runts-scraper:8000/search/runts-entity*' => Http::response(['found' => true]),
    ]);

    $this->artisan('cai:check-runts-presence', ['--timeout' => 5])->assertExitCode(0);

    expect($section->fresh()->runts_presence_status)->toBe(CaiRuntsPresenceStatus::Registered);
});

test('cai:check-runts-presence writes runts_presence_status and runts_presence_checked_at for every section with a tax_code', function (): void {
    $registered = caiSection(['tax_code' => '01234567890', 'runts_presence_status' => null]);
    $notRegistered = caiSection(['tax_code' => '09876543210', 'runts_presence_status' => null]);
    caiSection(['tax_code' => null]);

    Http::fake([
        'http://cai-runts-scraper:8000/search/runts-entity*' => function ($request) {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return Http::response(['found' => $query['codice_fiscale'] === '01234567890']);
        },
    ]);

    $this->artisan('cai:check-runts-presence')->assertExitCode(0);

    expect($registered->fresh()->runts_presence_status)->toBe(CaiRuntsPresenceStatus::Registered);
    expect($registered->fresh()->runts_presence_checked_at)->not->toBeNull();

    expect($notRegistered->fresh()->runts_presence_status)->toBe(CaiRuntsPresenceStatus::NotRegistered);
    expect($notRegistered->fresh()->runts_presence_checked_at)->not->toBeNull();
});

test('cai:check-runts-presence writes a Timeout status (not null) when the check times out, instead of leaving it unwritten', function (): void {
    $section = caiSection(['tax_code' => '01234567890', 'runts_presence_status' => null]);

    Http::fake([
        'http://cai-runts-scraper:8000/search/runts-entity*' => fn () => throw new ConnectionException('Operation timed out after 10004 milliseconds'),
    ]);

    $this->artisan('cai:check-runts-presence')->assertExitCode(0);

    expect($section->fresh()->runts_presence_status)->toBe(CaiRuntsPresenceStatus::Timeout);
    expect($section->fresh()->runts_presence_checked_at)->not->toBeNull();
});

test('cai:check-runts-presence --dry-run does not write anything, not even on timeout', function (): void {
    $section = caiSection(['tax_code' => '01234567890', 'runts_presence_status' => null]);

    Http::fake([
        'http://cai-runts-scraper:8000/search/runts-entity*' => fn () => throw new ConnectionException('timed out'),
    ]);

    $this->artisan('cai:check-runts-presence', ['--dry-run' => true])->assertExitCode(0);

    expect($section->fresh()->runts_presence_status)->toBeNull();
    expect($section->fresh()->runts_presence_checked_at)->toBeNull();
});

test('cai:check-runts-presence continues past a section whose check fails with a non-timeout error, leaving its status untouched', function (): void {
    $failing = caiSection(['tax_code' => '01234567890', 'runts_presence_status' => null]);
    $ok = caiSection(['tax_code' => '09876543210', 'runts_presence_status' => null]);

    Http::fake([
        'http://cai-runts-scraper:8000/search/runts-entity*' => function ($request) {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            if ($query['codice_fiscale'] === '01234567890') {
                return Http::response(['detail' => 'Internal error'], 502);
            }

            return Http::response(['found' => true]);
        },
    ]);

    $this->artisan('cai:check-runts-presence')->assertExitCode(0);

    expect($failing->fresh()->runts_presence_status)->toBeNull();
    expect($ok->fresh()->runts_presence_status)->toBe(CaiRuntsPresenceStatus::Registered);
});

test('cai:check-runts-presence skips sections with neither tax_code nor vat_number, never calling the service for them', function (): void {
    caiSection(['tax_code' => null, 'vat_number' => null]);

    Http::fake();

    $this->artisan('cai:check-runts-presence')->assertExitCode(0);

    Http::assertNothingSent();
});

test('cai:check-runts-presence falls back to vat_number when tax_code is missing', function (): void {
    $section = caiSection(['tax_code' => null, 'vat_number' => '09876543210', 'runts_presence_status' => null]);

    Http::fake([
        'http://cai-runts-scraper:8000/search/runts-entity*' => function ($request) {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return Http::response(['found' => $query['codice_fiscale'] === '09876543210']);
        },
    ]);

    $this->artisan('cai:check-runts-presence')->assertExitCode(0);

    expect($section->fresh()->runts_presence_status)->toBe(CaiRuntsPresenceStatus::Registered);
});

test('cai:check-runts-presence prefers tax_code over vat_number when both are present', function (): void {
    $section = caiSection(['tax_code' => '01234567890', 'vat_number' => '09876543210', 'runts_presence_status' => null]);

    Http::fake([
        'http://cai-runts-scraper:8000/search/runts-entity*' => Http::response(['found' => true]),
    ]);

    $this->artisan('cai:check-runts-presence')->assertExitCode(0);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'codice_fiscale=01234567890'));
});
