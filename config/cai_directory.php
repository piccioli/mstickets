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
];
