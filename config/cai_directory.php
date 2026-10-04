<?php

declare(strict_types=1);

return [
    /*
     * Percorso del file SQLite del datapack RUNTS-CAI (relativo alla root del
     * progetto, o assoluto), letto dal bottone "Sincronizza dati CAI" della
     * dashboard cliente (Fase 9): stesso default del comando `cai:import-datapack`
     * (US-802), qui esposto via config per poterlo sovrascrivere nei test.
     */
    'datapack_path' => env('CAI_DATAPACK_PATH', 'cai-datapack/runts-cai.sqlite'),

    /*
     * JSON di fallback CF/PIVA (percorso assoluto), generato da
     * `cai:generate-tax-code-fallback` a partire dal foglio Excel manuale del
     * committente e committato nel repo — letto da
     * `App\Domain\CaiDirectory\Support\CaiTaxCodeFallbackRepository`. Esposto via
     * config (non un default hardcoded nella classe) per poterlo puntare altrove nei
     * test: i fixture di `CaiSyncRuntsAllCommandTest`/simili riusano `codice_cai`
     * REALI del dataset RUNTS-CAI (es. "9226005" = SEZ. CARRARA) — se quella classe
     * leggesse di default il file reale committato, il fallback riempirebbe a sorpresa
     * il `tax_code` di sezioni di test pensate per restarne prive, cambiando il
     * comportamento atteso di `cai:sync-runts-all` in test che non parlano affatto di
     * fallback. `phpunit.xml` punta questa env a un percorso inesistente.
     */
    'tax_code_fallback_path' => env('CAI_TAX_CODE_FALLBACK_PATH', resource_path('data/cai/tax-code-fallback.json')),

    /*
     * API pubblica CAI (Fase 9, storia 1, design doc §3.1): stessi endpoint già
     * usati dal prototipo Python `RUNTS/scraper/cai_scraper.py`. Il template della
     * URL delle sottosezioni contiene un solo `%s` (il codice sezione), risolto con
     * `sprintf()` da CaiApiClient.
     */
    'api' => [
        'sections_list_url' => env('CAI_API_SECTIONS_LIST_URL', 'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple'),
        'subsections_list_url_template' => env('CAI_API_SUBSECTIONS_URL_TEMPLATE', 'https://www.cai.it/wp-json/cai-section/v2/sections/%s/sub-sections-list'),
        'timeout_seconds' => (int) env('CAI_API_TIMEOUT_SECONDS', 30),
    ],

    /*
     * Refresh mensile nazionale (Fase 9, storia 1, design doc §3.5): cadenza cron di
     * `cai:sync-national`, dietro il feature flag
     * `config('orchestrator.features.cai_sync_national')` (disattivo di default).
     */
    'sync_national' => [
        'schedule_cron' => env('CAI_SYNC_NATIONAL_SCHEDULE_CRON', '0 6 1 * *'),
    ],

    /*
     * Servizio Python cai-runts-scraper (Fase 9, storia 2/3, design doc
     * `2026-09-07-cai-runts-scraper-service-design.md`): raggiunto via la rete Docker Compose interna dal
     * nome del servizio, mai una porta pubblicata verso l'host in produzione/UAT.
     */
    'runts_scraper' => [
        'base_url' => env('CAI_RUNTS_SCRAPER_BASE_URL', 'http://cai-runts-scraper:8000'),
        'scrape_timeout_seconds' => (int) env('CAI_RUNTS_SCRAPER_SCRAPE_TIMEOUT_SECONDS', 150),
        'analyze_timeout_seconds' => (int) env('CAI_RUNTS_SCRAPER_ANALYZE_TIMEOUT_SECONDS', 60),
        // 120s, non 60: verificato con una run reale contro RUNTS (cai:check-runts-presence)
        // che 60s è troppo stretto — i 3 tentativi interni del servizio Python (fino a ~30s di
        // attesa selettore ciascuno + backoff 1s/2s) possono superare abbondantemente i 60s nel
        // caso peggiore, causando un timeout lato client PHP prima che il servizio risponda.
        'search_timeout_seconds' => (int) env('CAI_RUNTS_SCRAPER_SEARCH_TIMEOUT_SECONDS', 120),
    ],

    /*
     * Verifica leggera di presenza RUNTS (Fase 9): cadenza cron di
     * `cai:check-runts-presence`, dietro il feature flag
     * `config('orchestrator.features.cai_check_runts_presence')` (disattivo di default).
     */
    'runts_presence_check' => [
        'schedule_cron' => env('CAI_CHECK_RUNTS_PRESENCE_SCHEDULE_CRON', '0 5 1 * *'),
    ],

    /*
     * Import dello snapshot nel datapack (`cai:import-datapack`): margine di spazio libero richiesto sullo storage
     * dei documenti, in percentuale dei byte da copiare, prima di copiare i file dei documenti RUNTS.
     */
    'snapshot' => [
        'disk_margin_percent' => (float) env('CAI_SNAPSHOT_DISK_MARGIN_PERCENT', 10),
    ],
];
