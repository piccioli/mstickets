<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Account IMAP inbound (§7.4 del PRD, US-301)
    |--------------------------------------------------------------------------
    |
    | Configurazione della casella email da cui il sistema legge le email in
    | ingresso, passata a `Webklex\PHPIMAP\ClientManager::make()` da
    | App\Domain\Mail\Transports\WebklexImapTransport. Distinto da
    | config/mail.php, che resta la configurazione Laravel nativa di invio
    | (SMTP outbound). Nessuna chiamata env() fuori da questo file (§13.3 del
    | PRD).
    |
    */

    'imap' => [
        'host' => env('IMAP_HOST', 'localhost'),
        'port' => (int) env('IMAP_PORT', 993),
        'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
        'validate_cert' => (bool) env('IMAP_VALIDATE_CERT', true),
        'username' => env('IMAP_USERNAME', ''),
        'password' => env('IMAP_PASSWORD', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cartelle IMAP (§7.3.3, §7.4 del PRD)
    |--------------------------------------------------------------------------
    |
    | Nomi reali delle cartelle sul server IMAP, mappati ai ruoli usati dalla
    | pipeline (App\Domain\Mail\Enums\ImapFolderRole): la cartella di arrivo
    | (inbox) e le destinazioni possibili dopo l'elaborazione di un messaggio.
    |
    */

    'folders' => [
        'inbox' => env('IMAP_FOLDER_INBOX', 'INBOX'),
        'processed' => env('IMAP_FOLDER_PROCESSED', 'Processed'),
        'errors' => env('IMAP_FOLDER_ERRORS', 'Errors'),
        'quarantine' => env('IMAP_FOLDER_QUARANTINE', 'Quarantine'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fetch — limite di default (§7.3.3 del PRD)
    |--------------------------------------------------------------------------
    |
    | `InboundMailTransport::fetch()` richiede sempre un limit esplicito (mai
    | "tutti gli unseen"): questo è solo il valore di default che il comando
    | `mail:fetch-inbound` (US-302) usa quando non lo si sovrascrive da CLI.
    |
    */

    'fetch' => [
        'default_limit' => (int) env('IMAP_FETCH_DEFAULT_LIMIT', 50),

        // Cadenza dello scheduler (routes/console.php, §10.2 del PRD: "ogni 5
        // min" di default, ma la story chiede una cadenza configurabile).
        'schedule_cron' => env('MAIL_FETCH_SCHEDULE_CRON', '*/5 * * * *'),

        // Tempo massimo di esecuzione (secondi) passato a
        // Schedule::command()->timeout() — evita che una sessione IMAP bloccata
        // impedisca per sempre l'esecuzione successiva.
        'timeout' => (int) env('MAIL_FETCH_TIMEOUT', 300),

        // Numero di tentativi (con backoff, via il helper retry()) per la sola
        // chiamata di rete InboundMailTransport::fetch(): un fallimento
        // transitorio di connessione IMAP non deve far fallire l'intera
        // esecuzione schedulata.
        'tries' => (int) env('MAIL_FETCH_TRIES', 3),

        // Durata (secondi) del lock applicativo che impedisce due esecuzioni
        // concorrenti di mail:fetch-inbound (WithoutOverlapping, §10.1/§10.2
        // del PRD): valido sia per due trigger dello scheduler sia per
        // un'esecuzione manuale in parallelo a quella schedulata.
        'lock_seconds' => (int) env('MAIL_FETCH_LOCK_SECONDS', 280),
    ],

    /*
    |--------------------------------------------------------------------------
    | Archiviazione grezza (§7.3.3 del PRD, US-302)
    |--------------------------------------------------------------------------
    |
    | Disco Storage dedicato su cui `mail:fetch-inbound` scrive ogni `.eml`
    | grezzo PRIMA di qualunque parsing (config('filesystems.disks'), stesso
    | principio "disco nominato, mai un ADT" di ticket-attachments/import-reports/
    | legacy-media — così è intercettabile da Storage::fake() nei test).
    |
    */

    'storage' => [
        'raw_disk' => env('MAIL_RAW_STORAGE_DISK', 'raw-emails'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Anti-loop — rate limit (§7.3.4 del PRD, US-304)
    |--------------------------------------------------------------------------
    |
    | Soglie oltre le quali un mittente smette di ricevere auto-reply e va in
    | quarantena (email_suppressions, reason = loop_protection).
    |
    */

    'rate_limit' => [
        'max_per_hour' => (int) env('MAIL_RATE_LIMIT_PER_HOUR', 3),
        'max_per_day' => (int) env('MAIL_RATE_LIMIT_PER_DAY', 10),

        // Durata (ore) della soppressione applicata a un mittente che supera le
        // soglie sopra: oltre questo tempo l'indirizzo torna a poter ricevere
        // auto-reply (App\Domain\Mail\Models\EmailSuppression::scopeActive()).
        'suppression_hours' => (int) env('MAIL_RATE_LIMIT_SUPPRESSION_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifiche staff e supporto (§7 del PRD)
    |--------------------------------------------------------------------------
    |
    | Gruppo di notifica staff per i nuovi ticket cliente/mittente sconosciuto
    | (E3/E9, US-312): comma-separated, come config('orchestrator.anonymization
    | .mail_test_domains'). Indirizzo di supporto usato come mittente degli
    | auto-reply generati dalla pipeline.
    |
    */

    'staff_notification_group' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('MAIL_STAFF_NOTIFICATION_GROUP', '')),
    ))),

    'support_address' => env('MAIL_SUPPORT_ADDRESS', ''),

];
