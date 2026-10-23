<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Scarica i rifugi dall'API pubblica rifugi.cai.it (US-1032) e li scrive nella tabella `snap_cai_huts` del datapack,
 * poi letta da {@see CaiHutsImporter}. Elenco paginato (`/shelters?per_page=&page=`) + dettaglio di ogni rifugio
 * (`/shelters/{id_cai}`) con concorrenza, timeout e tentativi da `config('cai_directory.huts_api')`. Tutto o niente:
 * se anche un solo rifugio non si scarica dopo i tentativi lancia un'eccezione prima di toccare il datapack.
 *
 * L'API ha un rate limit (1000 richieste per finestra, poi `429` con `Retry-After`): fra un tentativo e l'altro la
 * pausa è il `Retry-After` più alto ricevuto, entro `max_retry_after_seconds`. Un run completo (~770 richieste) sta
 * nel limite; due run di fila no.
 */
final class CaiHutsFetcher
{
    /** Dettagli per giro di `Http::pool()`: le risposte di un blocco si liberano prima del successivo. */
    private const POOL_CHUNK = 50;

    /**
     * Dettagli di tutti i rifugi dell'elenco, `id_cai` → JSON grezzo della fonte, in ordine di `id_cai`. Restano
     * stringhe (~12 MB per 764 rifugi): decodificati tutti insieme non starebbero nel `memory_limit` CLI di 128M.
     *
     * @return array<int, string>
     *
     * @throws RuntimeException se l'elenco o un dettaglio non si scaricano dopo i tentativi
     */
    public function fetch(): array
    {
        $ids = $this->fetchIds();
        $details = [];
        $pending = $ids;
        $attempts = (int) config('cai_directory.huts_api.attempts');

        $retryAfter = 0;

        for ($attempt = 1; $attempt <= $attempts && $pending !== []; $attempt++) {
            if ($attempt > 1) {
                $this->pause($retryAfter);
            }

            $failed = [];
            $retryAfter = 0;

            foreach (array_chunk($pending, self::POOL_CHUNK) as $chunk) {
                foreach ($this->fetchDetails($chunk, $retryAfter) as $idCai => $payload) {
                    if ($payload === null) {
                        $failed[] = $idCai;

                        continue;
                    }

                    $details[$idCai] = $payload;
                }
            }

            $pending = $failed;
        }

        if ($pending !== []) {
            throw new RuntimeException(sprintf(
                'Dettaglio di %d rifugi non scaricato dopo %d tentativi (id_cai %s): snap_cai_huts non modificata.',
                count($pending),
                $attempts,
                implode(', ', array_slice($pending, 0, 20)).(count($pending) > 20 ? ', …' : ''),
            ));
        }

        ksort($details);

        return $details;
    }

    /**
     * Riscrive `snap_cai_huts` (DROP+CREATE+INSERT) in una sola transazione: in caso di errore resta la tabella
     * precedente.
     *
     * @param  array<int, string>  $details  `id_cai` → JSON grezzo, come restituito da {@see fetch()}
     */
    public function write(PDO $datapack, array $details): void
    {
        $datapack->beginTransaction();

        try {
            $datapack->exec('DROP TABLE IF EXISTS '.CaiHutsImporter::TABLE);
            $datapack->exec('CREATE TABLE '.CaiHutsImporter::TABLE.' (id_cai INTEGER PRIMARY KEY, payload TEXT NOT NULL)');
            $insert = $datapack->prepare('INSERT INTO '.CaiHutsImporter::TABLE.' (id_cai, payload) VALUES (?, ?)');

            foreach ($details as $idCai => $payload) {
                $insert->execute([$idCai, $payload]);
            }

            $datapack->commit();
        } catch (Throwable $e) {
            $datapack->rollBack();

            throw $e;
        }
    }

    /**
     * `id_cai` di tutti i rifugi dell'elenco paginato.
     *
     * @return list<int>
     */
    private function fetchIds(): array
    {
        $ids = [];
        $page = 1;

        do {
            $body = $this->getListPage($page);

            foreach (is_array($body['data'] ?? null) ? $body['data'] : [] as $item) {
                if (is_array($item) && isset($item['id_cai'])) {
                    $ids[(int) $item['id_cai']] = (int) $item['id_cai'];
                }
            }

            $lastPage = (int) ($body['last_page'] ?? $page);
            $page++;
        } while ($page <= $lastPage);

        return array_values($ids);
    }

    /**
     * @return array<string, mixed>
     */
    private function getListPage(int $page): array
    {
        $attempts = (int) config('cai_directory.huts_api.attempts');
        $url = $this->baseUrl().'/shelters';
        $lastError = null;
        $retryAfter = 0;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            if ($attempt > 1) {
                $this->pause($retryAfter);
            }

            try {
                $response = $this->request()->get($url, ['per_page' => config('cai_directory.huts_api.per_page'), 'page' => $page]);

                if ($response->successful() && is_array($response->json('data'))) {
                    /** @var array<string, mixed> */
                    return $response->json();
                }

                $lastError = "HTTP {$response->status()}";
                $retryAfter = $this->retryAfter($response);
            } catch (Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        throw new RuntimeException("Pagina {$page} dell'elenco rifugi non scaricata dopo {$attempts} tentativi ({$lastError}): snap_cai_huts non modificata.");
    }

    /**
     * Un blocco di dettagli in parallelo: `id_cai` → JSON grezzo, o null se la richiesta è fallita o la risposta non è
     * il dettaglio di un rifugio. `$retryAfter` riceve il `Retry-After` più alto fra le risposte.
     *
     * @param  list<int>  $ids
     * @return array<int, string|null>
     */
    private function fetchDetails(array $ids, int &$retryAfter): array
    {
        $responses = Http::pool(
            fn (Pool $pool): array => array_map(
                fn (int $idCai) => $pool->as((string) $idCai)
                    ->acceptJson()
                    ->timeout((int) config('cai_directory.huts_api.timeout_seconds'))
                    ->get($this->baseUrl().'/shelters/'.$idCai),
                $ids,
            ),
            concurrency: (int) config('cai_directory.huts_api.concurrency'),
        );

        $details = [];

        foreach ($ids as $idCai) {
            $response = $responses[(string) $idCai] ?? null;
            $details[$idCai] = null;

            if (! $response instanceof Response) {
                continue;
            }

            $retryAfter = max($retryAfter, $this->retryAfter($response));
            $body = $response->successful() ? json_decode($response->body(), true) : null;

            if (is_array($body) && (int) ($body['id_cai'] ?? 0) === $idCai) {
                $details[$idCai] = $response->body();
            }
        }

        return $details;
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()->timeout((int) config('cai_directory.huts_api.timeout_seconds'));
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('cai_directory.huts_api.base_url'), '/');
    }

    private function retryAfter(Response $response): int
    {
        $header = $response->header('Retry-After');

        return ctype_digit($header) ? (int) $header : 0;
    }

    private function pause(int $retryAfterSeconds): void
    {
        $retryAfterMs = min($retryAfterSeconds, (int) config('cai_directory.huts_api.max_retry_after_seconds')) * 1000;

        Sleep::for(max(0, (int) config('cai_directory.huts_api.retry_pause_ms'), $retryAfterMs))->milliseconds();
    }
}
