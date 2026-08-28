<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Calcolo delle ore lavorate (§6.2.2 del PRD)
    |--------------------------------------------------------------------------
    |
    | Finestra oraria, giorni lavorativi e granularità usati da
    | App\Domain\TimeTracking\WorkedTimeCalculator per derivare sia
    | `tickets.worked_minutes` sia l'aggregato `ticket_work_logs`, a partire da
    | `ticket_logs.from_status`/`to_status`/`occurred_at`.
    |
    | DECISIONE Q15 del PRD: una sola politica di calcolo (non le due divergenti
    | del v1, "totale ticket" e "aggregato giornaliero") — i numeri storici
    | confrontati col v1 potranno divergere per questa scelta, documentato in
    | CLAUDE.md e da misurare nel report di importazione della Fase 2.
    |
    */

    'workday_start' => (int) env('TIMETRACKING_WORKDAY_START', 9),
    'workday_end' => (int) env('TIMETRACKING_WORKDAY_END', 18),
    'granularity_minutes' => (int) env('TIMETRACKING_GRANULARITY_MINUTES', 10),

    // Tetto (non un forfait, `min($minuti, tetto)`) applicato SOLO all'intervallo
    // ancora aperto di un ticket tuttora in `progress` (nessun log di chiusura
    // `from_status = 'progress'` ancora scritto): evita che le ore calcolate
    // crescano indefinitamente finché la transizione di chiusura non arriva
    // davvero (§6.2.2, §17.1 del PRD).
    'non_status_change_cap_minutes' => (int) env('TIMETRACKING_NON_STATUS_CHANGE_CAP_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Consolidamento schedulato (§10.2 del PRD, US-613)
    |--------------------------------------------------------------------------
    |
    | Cadenza di `timetracking:aggregate-daily`, dietro
    | config('orchestrator.features.timetracking_aggregate').
    |
    */

    'aggregate_daily' => [
        'schedule_cron' => env('TIMETRACKING_AGGREGATE_SCHEDULE_CRON', '30 23 * * *'),
    ],

];
