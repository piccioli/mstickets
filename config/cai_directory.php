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
        'search_timeout_seconds' => (int) env('CAI_RUNTS_SCRAPER_SEARCH_TIMEOUT_SECONDS', 60),
    ],

    /*
     * Verifica leggera di presenza RUNTS (Fase 9): cadenza cron di
     * `cai:check-runts-presence`, dietro il feature flag
     * `config('orchestrator.features.cai_check_runts_presence')` (disattivo di default).
     */
    'runts_presence_check' => [
        'schedule_cron' => env('CAI_CHECK_RUNTS_PRESENCE_SCHEDULE_CRON', '0 5 1 * *'),
    ],
];
