<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function (): void {
    config([
        'cai_directory.huts_api.base_url' => 'https://rifugi.test/api/v1',
        'cai_directory.huts_api.retry_pause_ms' => 0,
    ]);

    $this->datapack = makeHutsDatapack([hutApiDetail(1, 'Rifugio Vecchio')]);
    writeSicaiRestPointsSnapshot($this->datapack, [sicaiRestPointApiDetail(1, 'Punto vecchio')]);
});

/**
 * Fake dell'API dei punti di appoggio: elenco a pagine da 2 (come la fonte, `per_page` non si passa) e dettaglio per
 * id; `$detailFailures` id → numero di risposte 500 (o 429 con `$retryAfter`) prima del successo.
 *
 * @param  list<array<string, mixed>>  $details
 * @param  array<int, int>  $detailFailures
 */
function fakeSicaiRestPointsApi(array $details, array $detailFailures = [], ?int $retryAfter = null): void
{
    $byId = collect($details)->keyBy('id');

    Http::fake(function (Request $request) use ($details, $byId, &$detailFailures, $retryAfter) {
        $path = parse_url($request->url(), PHP_URL_PATH);

        if ($path === '/api/v1/sicaiRestPoints') {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $page = (int) $query['page'];

            return Http::response([
                'current_page' => $page,
                'last_page' => (int) ceil(count($details) / 2),
                'per_page' => 2,
                'total' => count($details),
                'data' => array_map(
                    fn (array $d): array => ['id' => $d['id'], 'title' => $d['title'], 'shelter_id' => $d['shelter_id']],
                    array_slice($details, ($page - 1) * 2, 2),
                ),
            ]);
        }

        $id = (int) basename((string) $path);

        if (($detailFailures[$id] ?? 0) > 0) {
            $detailFailures[$id]--;

            return $retryAfter === null
                ? Http::response(['message' => 'Server Error'], 500)
                : Http::response(['message' => 'Too Many Attempts.'], 429, ['Retry-After' => (string) $retryAfter]);
        }

        return Http::response($byId[$id]);
    });
}

function snapRestPointIds(string $datapack): array
{
    return (new PDO('sqlite:'.$datapack))->query('SELECT id FROM snap_cai_sicai_rest_points ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
}

test('downloads every page and every detail into snap_cai_sicai_rest_points with a summary', function (): void {
    $details = [
        sicaiRestPointApiDetail(29, "Agriturismo d'Apostolo", ['type' => 'Agriturismo']),
        sicaiRestPointApiDetail(4, 'Rifugio Bozzi', shelterId: 163),
        sicaiRestPointApiDetail(19, 'Hotel Uno', ['type' => 'Hotel']),
    ];
    fakeSicaiRestPointsApi($details);

    $this->artisan('cai:fetch-sicai-rest-points', ['--datapack' => $this->datapack])
        ->expectsOutputToContain('Punti di appoggio scaricati: 3')
        ->expectsOutputToContain('Collegati a un rifugio (shelter_id): 1')
        ->expectsOutputToContain('Hotel: 1')
        ->assertSuccessful();

    expect(snapRestPointIds($this->datapack))->toBe([4, 19, 29]);

    $payload = (new PDO('sqlite:'.$this->datapack))->query('SELECT payload FROM snap_cai_sicai_rest_points WHERE id = 4')->fetchColumn();
    expect(json_decode((string) $payload, true))->toBe($details[1]);

    // La tabella dei rifugi non si tocca.
    expect(snapHutIdsOf($this->datapack))->toBe([1]);
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'sicaiRestPoints?page=2') && $request->hasHeader('Accept', 'application/json'));
});

test('a rate-limited detail waits for Retry-After and then succeeds', function (): void {
    Sleep::fake();
    config(['cai_directory.huts_api.max_retry_after_seconds' => 60]);
    fakeSicaiRestPointsApi([sicaiRestPointApiDetail(10, 'Ostello')], [10 => 1], retryAfter: 30);

    $this->artisan('cai:fetch-sicai-rest-points', ['--datapack' => $this->datapack])->assertSuccessful();

    expect(snapRestPointIds($this->datapack))->toBe([10]);
    Sleep::assertSequence([Sleep::for(30_000)->milliseconds()]);
});

test('a detail failing after every attempt exits with an error and leaves the previous table intact', function (): void {
    fakeSicaiRestPointsApi([sicaiRestPointApiDetail(10, 'Ostello'), sicaiRestPointApiDetail(11, 'Camping')], [11 => PHP_INT_MAX]);

    $this->artisan('cai:fetch-sicai-rest-points', ['--datapack' => $this->datapack])
        ->expectsOutputToContain('punti di appoggio SICAI non scaricato dopo 3 tentativi')
        ->assertFailed();

    expect(snapRestPointIds($this->datapack))->toBe([1]);
});

test('dry-run downloads and summarizes without touching the datapack', function (): void {
    fakeSicaiRestPointsApi([sicaiRestPointApiDetail(10, 'Ostello')]);

    $this->artisan('cai:fetch-sicai-rest-points', ['--datapack' => $this->datapack, '--dry-run' => true])
        ->expectsOutputToContain('dry-run')
        ->expectsOutputToContain('Punti di appoggio scaricati: 1')
        ->assertSuccessful();

    expect(snapRestPointIds($this->datapack))->toBe([1]);
});

test('a missing datapack fails without calling the API', function (): void {
    Http::fake();

    $this->artisan('cai:fetch-sicai-rest-points', ['--datapack' => '/tmp/non-esiste-'.uniqid().'.sqlite'])->assertFailed();

    Http::assertNothingSent();
});

function snapHutIdsOf(string $datapack): array
{
    return (new PDO('sqlite:'.$datapack))->query('SELECT id_cai FROM snap_cai_huts ORDER BY id_cai')->fetchAll(PDO::FETCH_COLUMN);
}
