<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Stage dell'ETL v1→v2 (§11.4 del PRD)
    |--------------------------------------------------------------------------
    |
    | Elenco delle classi che implementano App\Import\Stages\Contracts\ImportStage,
    | risolte via il container e registrate in App\Import\Stages\ImportStageRegistry
    | nell'ordine in cui compaiono qui sotto (l'ordine di REGISTRAZIONE non conta:
    | ImportRunner::plan() risolve l'ordine di ESECUZIONE dalle dipendenze
    | dichiarate da ciascuno stage).
    |
    | Nessuno stage reale esiste ancora in questa fase (US-201): le fasi
    | successive aggiungono qui la propria classe, senza toccare il comando
    | v1:import né il runner.
    |
    */

    'stages' => [
        // App\Import\Stages\UsersStage::class,
    ],

];
