<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // Disco privato dedicato agli allegati dei messaggi del ticket (§9.6 del PRD,
        // US-107): mai `public`, il download passa da una rotta autorizzata dalla
        // TicketPolicy. `serve` a false: nessuna route di framework che esponga il
        // file per path, solo il controller dedicato.
        'ticket-attachments' => [
            'driver' => 'local',
            'root' => storage_path('app/private/ticket-attachments'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        // Disco privato dedicato agli allegati (media collection "documents"/"images")
        // delle pagine di documentazione (§6.4.1 del PRD, US-404): mai `public`, altrimenti
        // un allegato di una pagina `category=internal` sarebbe raggiungibile via URL diretto
        // indipendentemente dalla DocumentationPagePolicy, stesso ragionamento già applicato
        // a `ticket-attachments` qui sopra.
        'documentation-attachments' => [
            'driver' => 'local',
            'root' => storage_path('app/private/documentation-attachments'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        // Disco privato dedicato ai PDF generati delle pagine di documentazione
        // (§6.4.3 del PRD, US-406): separato da `documentation-attachments` (quello
        // è per i documenti/immagini caricati dall'utente, non per l'output generato
        // dal job di rendering) — stesso motivo per cui `ticket-attachments` non
        // ospita anche gli allegati email in Fase 3.
        'documentation-pdfs' => [
            'driver' => 'local',
            'root' => storage_path('app/private/documentation-pdfs'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        // Disco privato dedicato ai PDF generati dei report di attività (§6.5.3
        // del PRD, US-409): stesso ragionamento di `documentation-pdfs`, disco
        // separato per tipo di output generato — il download passa sempre dalla
        // rotta autorizzata da ActivityReportPolicy::view().
        'activity-report-pdfs' => [
            'driver' => 'local',
            'root' => storage_path('app/private/activity-report-pdfs'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        // Disco nominato radicato su storage/app (non storage/app/private, il default
        // Laravel 11+ del disco "local"): usato dai report v1:inspect/v1:validate
        // (§11.2/§11.7 del PRD), che devono vivere in storage/app/import/ (path
        // letterale richiesto dal PRD). Nominato (non un Storage::build() ad-hoc)
        // proprio per essere intercettabile da Storage::fake('import-reports') nei
        // test, stesso motivo già documentato per "legacy-media" (US-211).
        'import-reports' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        // File fisici degli allegati v1 (`media.file_name`, §11.4 stage 14 del PRD),
        // forniti separatamente dal dump SQL: stessa convenzione piatta (nessuna
        // sotto-cartella per id/uuid) già attesa da `v1:inspect` (storage/app/v1-media).
        'legacy-media' => [
            'driver' => 'local',
            // `env('LEGACY_MEDIA_PATH', default)` non applica il default se la variabile
            // è definita ma vuota (`.env.example` la lascia intenzionalmente vuota,
            // "vuoto = default storage/app/v1-media"): `env()` sostituisce il default
            // solo quando la chiave è del tutto assente, non quando vale ''. Senza `?:`
            // una stringa vuota diventa la root del disco, e Flysystem fallisce con
            // "Unable to create a directory at ." (bug reale trovato nel job CI
            // etl-fixture, mai eseguito prima su questo branch).
            'root' => env('LEGACY_MEDIA_PATH') ?: storage_path('app/v1-media'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        // Disco privato dedicato ai `.eml` grezzi scaricati da IMAP prima di
        // qualunque parsing (§7.3.3 del PRD, US-302): `email_messages.raw_path`
        // punta sempre a un path su questo disco. Nominato (non un
        // `Storage::build()` ad-hoc) per essere intercettabile da
        // `Storage::fake('raw-emails')` nei test, stesso principio già
        // documentato per "import-reports"/"legacy-media".
        'raw-emails' => [
            'driver' => 'local',
            'root' => storage_path('app/private/raw-emails'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
