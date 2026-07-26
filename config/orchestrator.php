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

    /*
    |--------------------------------------------------------------------------
    | Variabili d'ambiente obbligatorie note in questa fase (§12, US-022)
    |--------------------------------------------------------------------------
    |
    | Snapshot letto qui, mai con env() fuori dai file di config/ (§13.3), e
    | usato da `php artisan orchestrator:doctor` per verificarne la presenza.
    | Nessun default: un valore null/vuoto qui riflette fedelmente una
    | variabile mancante nell'ambiente reale. Le fasi successive (IMAP/SMTP,
    | ecc.) aggiungono le proprie voci qui, senza toccare il comando.
    |
    */

    'required_env' => [
        'APP_KEY' => env('APP_KEY'),
        'APP_ENV' => env('APP_ENV'),
        'APP_URL' => env('APP_URL'),
        'DB_CONNECTION' => env('DB_CONNECTION'),
        'DB_HOST' => env('DB_HOST'),
        'DB_PORT' => env('DB_PORT'),
        'DB_DATABASE' => env('DB_DATABASE'),
        'DB_USERNAME' => env('DB_USERNAME'),
        'DB_PASSWORD' => env('DB_PASSWORD'),
        'REDIS_HOST' => env('REDIS_HOST'),
        'REDIS_PORT' => env('REDIS_PORT'),
        'FILESYSTEM_DISK' => env('FILESYSTEM_DISK'),
        'QUEUE_CONNECTION' => env('QUEUE_CONNECTION'),
        'CACHE_STORE' => env('CACHE_STORE'),
        'SESSION_DRIVER' => env('SESSION_DRIVER'),
        'MAIL_MAILER' => env('MAIL_MAILER'),
        'MAIL_HOST' => env('MAIL_HOST'),
        'MAIL_PORT' => env('MAIL_PORT'),
        'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Utente di sistema (§12, US-022)
    |--------------------------------------------------------------------------
    |
    | Fallback usato come autore dei log/eventi generati dal sistema (non da un
    | utente reale). Creato da `php artisan orchestrator:doctor` se manca:
    | nessun ruolo assegnato, nessuna password, così non può autenticarsi né
    | accedere al pannello (App\Domain\Identity\Models\User::canAccessPanel).
    |
    */

    'system_user' => [
        'email' => env('ORCHESTRATOR_SYSTEM_USER_EMAIL', 'system@orchestrator.local'),
        'name' => 'Sistema',
    ],

];
