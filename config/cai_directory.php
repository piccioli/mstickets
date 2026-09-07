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
];
