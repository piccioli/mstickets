<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client per l'API pubblica CAI (Fase 9, storia 1, design doc §3.1/§6): HTTP GET
 * JSON, nessuna automazione browser necessaria (a differenza di RUNTS, §2 del
 * design doc). Retry con backoff esponenziale (1 secondo di base, 3 tentativi),
 * stesso schema del prototipo Python `RUNTS/scraper/cai_scraper.py::_with_retry`.
 */
final class CaiApiClient
{
    private const MAX_ATTEMPTS = 3;

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchNationalSections(): array
    {
        return $this->getJsonArrayWithRetry((string) config('cai_directory.api.sections_list_url'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchSubsections(string $sectionCode): array
    {
        $url = sprintf((string) config('cai_directory.api.subsections_list_url_template'), $sectionCode);

        return $this->getJsonArrayWithRetry($url);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getJsonArrayWithRetry(string $url): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'Origin' => 'https://www.cai.it',
                    'Referer' => 'https://www.cai.it/',
                ])
                    ->timeout((int) config('cai_directory.api.timeout_seconds'))
                    ->get($url);

                $response->throw();

                /** @var list<array<string, mixed>>|null $decoded */
                $decoded = $response->json();

                return $decoded ?? [];
            } catch (ConnectionException|RequestException $exception) {
                $lastException = $exception;

                if ($attempt < self::MAX_ATTEMPTS) {
                    sleep(2 ** $attempt);
                }
            }
        }

        throw new RuntimeException(
            "Chiamata all'API CAI fallita dopo ".self::MAX_ATTEMPTS." tentativi: {$url}",
            previous: $lastException,
        );
    }
}
