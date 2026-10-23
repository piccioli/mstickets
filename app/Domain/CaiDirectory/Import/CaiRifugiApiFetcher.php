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
 * Download di una risorsa dell'API pubblica rifugi.cai.it in una tabella `snap_*` del datapack: elenco paginato
 * (`{listPath}?page=`) + dettaglio di ogni elemento (`{listPath}/{id}`) con concorrenza, timeout e tentativi da
 * `config('cai_directory.huts_api')`. Tutto o niente: se anche un solo dettaglio non si scarica dopo i tentativi lancia
 * un'eccezione prima di toccare il datapack. Usato per i rifugi ({@see CaiHutsFetcher}, US-1032) e i punti di appoggio
 * SICAI ({@see CaiSicaiRestPointsFetcher}, US-1092).
 *
 * L'API ha un rate limit (1000 richieste per finestra, poi `429` con `Retry-After`): fra un tentativo e l'altro la
 * pausa è il `Retry-After` più alto ricevuto, entro `max_retry_after_seconds`.
 */
abstract class CaiRifugiApiFetcher
{
    /** Dettagli per giro di `Http::pool()`: le risposte di un blocco si liberano prima del successivo. */
    private const POOL_CHUNK = 50;

    /** Tabella `snap_*` del datapack scritta da {@see write()}. */
    abstract public function table(): string;

    /** Percorso dell'elenco, relativo a `base_url` (es. `/shelters`); il dettaglio è `{listPath}/{id}`. */
    abstract protected function listPath(): string;

    /** Chiave dell'identificativo negli elementi dell'elenco e nel dettaglio; anche nome della colonna PK della tabella. */
    abstract protected function idKey(): string;

    /** Nome al plurale degli elementi, per i messaggi d'errore ("rifugi"). */
    abstract protected function label(): string;

    /**
     * Parametri dell'elenco oltre a `page`.
     *
     * @return array<string, mixed>
     */
    protected function listQuery(): array
    {
        return [];
    }

    /**
     * Dettagli di tutti gli elementi dell'elenco, id → JSON grezzo della fonte, in ordine di id. Restano stringhe:
     * decodificati tutti insieme (~12 MB per 764 rifugi) non starebbero nel `memory_limit` CLI di 128M.
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
                foreach ($this->fetchDetails($chunk, $retryAfter) as $id => $payload) {
                    if ($payload === null) {
                        $failed[] = $id;

                        continue;
                    }

                    $details[$id] = $payload;
                }
            }

            $pending = $failed;
        }

        if ($pending !== []) {
            throw new RuntimeException(sprintf(
                'Dettaglio di %d %s non scaricato dopo %d tentativi (%s %s): %s non modificata.',
                count($pending),
                $this->label(),
                $attempts,
                $this->idKey(),
                implode(', ', array_slice($pending, 0, 20)).(count($pending) > 20 ? ', …' : ''),
                $this->table(),
            ));
        }

        ksort($details);

        return $details;
    }

    /**
     * Riscrive la tabella `snap_*` (DROP+CREATE+INSERT) in una sola transazione: in caso di errore resta la tabella
     * precedente.
     *
     * @param  array<int, string>  $details  id → JSON grezzo, come restituito da {@see fetch()}
     */
    public function write(PDO $datapack, array $details): void
    {
        $table = $this->table();
        $idKey = $this->idKey();

        $datapack->beginTransaction();

        try {
            $datapack->exec("DROP TABLE IF EXISTS {$table}");
            $datapack->exec("CREATE TABLE {$table} ({$idKey} INTEGER PRIMARY KEY, payload TEXT NOT NULL)");
            $insert = $datapack->prepare("INSERT INTO {$table} ({$idKey}, payload) VALUES (?, ?)");

            foreach ($details as $id => $payload) {
                $insert->execute([$id, $payload]);
            }

            $datapack->commit();
        } catch (Throwable $e) {
            $datapack->rollBack();

            throw $e;
        }
    }

    /**
     * Id di tutti gli elementi dell'elenco paginato.
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
                if (is_array($item) && isset($item[$this->idKey()])) {
                    $ids[(int) $item[$this->idKey()]] = (int) $item[$this->idKey()];
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
        $url = $this->baseUrl().$this->listPath();
        $lastError = null;
        $retryAfter = 0;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            if ($attempt > 1) {
                $this->pause($retryAfter);
            }

            try {
                $response = $this->request()->get($url, [...$this->listQuery(), 'page' => $page]);

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

        throw new RuntimeException("Pagina {$page} dell'elenco {$this->label()} non scaricata dopo {$attempts} tentativi ({$lastError}): {$this->table()} non modificata.");
    }

    /**
     * Un blocco di dettagli in parallelo: id → JSON grezzo, o null se la richiesta è fallita o la risposta non è il
     * dettaglio richiesto. `$retryAfter` riceve il `Retry-After` più alto fra le risposte.
     *
     * @param  list<int>  $ids
     * @return array<int, string|null>
     */
    private function fetchDetails(array $ids, int &$retryAfter): array
    {
        $responses = Http::pool(
            fn (Pool $pool): array => array_map(
                fn (int $id) => $pool->as((string) $id)
                    ->acceptJson()
                    ->timeout((int) config('cai_directory.huts_api.timeout_seconds'))
                    ->get($this->baseUrl().$this->listPath().'/'.$id),
                $ids,
            ),
            concurrency: (int) config('cai_directory.huts_api.concurrency'),
        );

        $details = [];

        foreach ($ids as $id) {
            $response = $responses[(string) $id] ?? null;
            $details[$id] = null;

            if (! $response instanceof Response) {
                continue;
            }

            $retryAfter = max($retryAfter, $this->retryAfter($response));
            $body = $response->successful() ? json_decode($response->body(), true) : null;

            if (is_array($body) && (int) ($body[$this->idKey()] ?? 0) === $id) {
                $details[$id] = $response->body();
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
