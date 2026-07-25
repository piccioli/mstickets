<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Feature flag per le automazioni schedulate (§10.1, §10.2 del PRD)
    |--------------------------------------------------------------------------
    |
    | Ogni comando schedulato è attivabile/disattivabile da qui, letto da una
    | variabile d'ambiente dedicata (pattern del v1 da mantenere). Default di
    | tutti i flag: false — l'abilitazione è una scelta di deploy.
    |
    | In questa fase (Fase 0) nessuno di questi comandi esiste ancora: le
    | chiavi sono il punto di innesto che le fasi successive attiveranno,
    | senza dover ritoccare questo file oltre a implementare il comando.
    |
    */

    'features' => [
        'tickets_progress_to_todo' => (bool) env('ENABLE_TICKETS_PROGRESS_TO_TODO', false),
        'tickets_auto_close_released' => (bool) env('ENABLE_TICKETS_AUTO_CLOSE_RELEASED', false),
        'tickets_close_scrum' => (bool) env('ENABLE_TICKETS_CLOSE_SCRUM', false),
        'tickets_restore_waiting' => (bool) env('ENABLE_TICKETS_RESTORE_WAITING', false),
        'tickets_waiting_reminders' => (bool) env('ENABLE_TICKETS_WAITING_REMINDERS', false),
        'tickets_archive_scrum' => (bool) env('ENABLE_TICKETS_ARCHIVE_SCRUM', false),
        'mail_fetch_inbound' => (bool) env('ENABLE_MAIL_FETCH_INBOUND', false),
        'mail_retry_failed' => (bool) env('ENABLE_MAIL_RETRY_FAILED', false),
        'timetracking_aggregate' => (bool) env('ENABLE_TIMETRACKING_AGGREGATE', false),
        'reports_monthly' => (bool) env('ENABLE_REPORTS_MONTHLY', false),
        'mail_digest' => (bool) env('ENABLE_MAIL_DIGEST', false),
        'tickets_idle_developer_notice' => (bool) env('ENABLE_TICKETS_IDLE_DEVELOPER_NOTICE', false),
    ],

];
