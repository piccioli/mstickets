<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use Illuminate\Support\Facades\Http;

/**
 * Client verso il servizio Python `cai-runts-scraper` (Fase 9, storia 3, design doc §4.2). A differenza di
 * {@see CaiApiClient} (Storia 1, API pubblica CAI), NON implementa retry proprio: il servizio Python già
 * ritenta internamente lo scrape (3 tentativi, backoff esponenziale — design doc §1), un secondo livello di
 * retry qui raddoppierebbe inutilmente il tempo di attesa in caso di fallimento reale.
 */
final class CaiRuntsScraperClient
{
    /**
     * @return array<string, mixed>
     */
    public function scrapeEntity(string $codiceFiscale): array
    {
        $response = Http::timeout((int) config('cai_directory.runts_scraper.scrape_timeout_seconds'))
            ->withOptions(['query' => ['codice_fiscale' => $codiceFiscale]])
            ->post($this->baseUrl().'/scrape/runts-entity');

        $response->throw();

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function analyzeBilancio(string $pdfContent): array
    {
        $response = Http::timeout((int) config('cai_directory.runts_scraper.analyze_timeout_seconds'))
            ->attach('file', $pdfContent, 'bilancio.pdf')
            ->post($this->baseUrl().'/analyze/bilancio');

        $response->throw();

        return $response->json();
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('cai_directory.runts_scraper.base_url'), '/');
    }
}
