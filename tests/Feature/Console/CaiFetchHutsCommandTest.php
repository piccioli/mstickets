<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function (): void {
    config([
        'cai_directory.huts_api.base_url' => 'https://rifugi.test/api/v1',
        'cai_directory.huts_api.per_page' => 2,
        'cai_directory.huts_api.retry_pause_ms' => 0,
    ]);

    $this->datapack = makeHutsDatapack([hutApiDetail(1, 'Rifugio Vecchio')]);
    $pdo = new PDO('sqlite:'.$this->datapack);
    $pdo->exec("INSERT INTO sezioni_cai (codice_cai) VALUES ('9218001'), ('9226005')");
});

/**
 * Fake dell'API: elenco paginato di `$details` (per_page dalla query) e dettaglio per id; `$detailFailures`
 * id_cai → numero di risposte 500 prima del successo (PHP_INT_MAX = sempre).
 *
 * @param  list<array<string, mixed>>  $details
 * @param  array<int, int>  $detailFailures
 */
function fakeHutsApi(array $details, array $detailFailures = [], ?int $retryAfter = null): void
{
    $byId = collect($details)->keyBy('id_cai');

    Http::fake(function (Request $request) use ($details, $byId, &$detailFailures, $retryAfter) {
        $path = parse_url($request->url(), PHP_URL_PATH);

        if ($path === '/api/v1/shelters') {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $perPage = (int) $query['per_page'];
            $page = (int) $query['page'];
            $items = array_slice($details, ($page - 1) * $perPage, $perPage);

            return Http::response([
                'current_page' => $page,
                'last_page' => (int) ceil(count($details) / $perPage),
                'total' => count($details),
                'data' => array_map(fn (array $d): array => ['id' => $d['id'], 'id_cai' => $d['id_cai'], 'title' => $d['title']], $items),
            ]);
        }

        $idCai = (int) basename((string) $path);

        if (($detailFailures[$idCai] ?? 0) > 0) {
            $detailFailures[$idCai]--;

            return $retryAfter === null
                ? Http::response(['message' => 'Server Error'], 500)
                : Http::response(['message' => 'Too Many Attempts.'], 429, ['Retry-After' => (string) $retryAfter]);
        }

        return Http::response($byId[$idCai]);
    });
}

function snapHutIds(string $datapack): array
{
    return (new PDO('sqlite:'.$datapack))->query('SELECT id_cai FROM snap_cai_huts ORDER BY id_cai')->fetchAll(PDO::FETCH_COLUMN);
}

test('downloads every page and every detail into snap_cai_huts with a summary', function (): void {
    $details = [
        hutApiDetail(10, 'Rifugio Tosa'),
        hutApiDetail(11, 'Bivacco Uno', ['branch_id' => '9226005']),
        hutApiDetail(12, 'Rifugio Sede', ['branch_id' => '9300100']),
    ];
    $details[1]['media'] = [];
    fakeHutsApi($details);

    $this->artisan('cai:fetch-huts', ['--datapack' => $this->datapack])
        ->expectsOutputToContain('Rifugi scaricati: 3')
        ->expectsOutputToContain('Con foto: 2')
        ->expectsOutputToContain('Collegati a una Sezione esistente: 2')
        ->expectsOutputToContain('Non collegati: 1')
        ->expectsOutputToContain('branch_id 9300100: 1')
        ->assertSuccessful();

    expect(snapHutIds($this->datapack))->toBe([10, 11, 12]);

    $payload = (new PDO('sqlite:'.$this->datapack))->query('SELECT payload FROM snap_cai_huts WHERE id_cai = 11')->fetchColumn();
    expect(json_decode((string) $payload, true))->toBe($details[1]);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'page=2') && $request->hasHeader('Accept', 'application/json'));
});

test('retries a failed detail and succeeds within the attempts', function (): void {
    fakeHutsApi([hutApiDetail(10, 'Rifugio Tosa'), hutApiDetail(11, 'Bivacco Uno')], [11 => 2]);

    $this->artisan('cai:fetch-huts', ['--datapack' => $this->datapack])->assertSuccessful();

    expect(snapHutIds($this->datapack))->toBe([10, 11]);
});

test('a rate-limited detail waits for Retry-After, capped by the config', function (): void {
    Sleep::fake();
    config(['cai_directory.huts_api.max_retry_after_seconds' => 60]);
    fakeHutsApi([hutApiDetail(10, 'Rifugio Tosa')], [10 => 2], retryAfter: 600);

    $this->artisan('cai:fetch-huts', ['--datapack' => $this->datapack])->assertSuccessful();

    expect(snapHutIds($this->datapack))->toBe([10]);
    Sleep::assertSequence([Sleep::for(60_000)->milliseconds(), Sleep::for(60_000)->milliseconds()]);
});

test('a detail failing after every attempt exits with an error and leaves the previous table intact', function (): void {
    fakeHutsApi([hutApiDetail(10, 'Rifugio Tosa'), hutApiDetail(11, 'Bivacco Uno')], [11 => PHP_INT_MAX]);

    $this->artisan('cai:fetch-huts', ['--datapack' => $this->datapack])
        ->expectsOutputToContain('non scaricato dopo 3 tentativi')
        ->assertFailed();

    expect(snapHutIds($this->datapack))->toBe([1]);
    Http::assertSentCount(1 + 2 + 2); // elenco + 2 dettagli al primo giro + 2 nuovi tentativi per id 11
});

test('dry-run downloads and summarizes without touching the datapack', function (): void {
    fakeHutsApi([hutApiDetail(10, 'Rifugio Tosa')]);

    $this->artisan('cai:fetch-huts', ['--datapack' => $this->datapack, '--dry-run' => true])
        ->expectsOutputToContain('dry-run')
        ->expectsOutputToContain('Rifugi scaricati: 1')
        ->assertSuccessful();

    expect(snapHutIds($this->datapack))->toBe([1]);
});

test('a missing datapack fails without calling the API', function (): void {
    Http::fake();

    $this->artisan('cai:fetch-huts', ['--datapack' => '/tmp/non-esiste-'.uniqid().'.sqlite'])->assertFailed();

    Http::assertNothingSent();
});
