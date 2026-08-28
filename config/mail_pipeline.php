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
    | Bounce/DSN — soglia soft bounce (§7.5.5 del PRD, US-319)
    |--------------------------------------------------------------------------
    |
    | Un hard bounce sospende l'indirizzo subito (email_suppressions,
    | reason=hard_bounce, permanente finché non rimossa da amministrazione,
    | US-323). Un soft bounce si limita a incrementare
    | email_suppressions.bounce_count: la sospensione vera e propria (usata da
    | EmailSuppression::scopeActive()) scatta solo quando il conteggio
    | raggiunge questa soglia, non al primo soft bounce.
    |
    */

    'bounce' => [
        'soft_bounce_threshold' => (int) env('MAIL_BOUNCE_SOFT_THRESHOLD', 3),
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

    /*
    |--------------------------------------------------------------------------
    | Risoluzione del thread — euristica di ultimo livello (§7.3.6 del PRD, US-306)
    |--------------------------------------------------------------------------
    |
    | Finestra (giorni) entro cui un `email_threads.last_message_at` è ancora
    | considerato "aperto" dal livello 4 (euristica: stesso mittente + subject
    | normalizzato identico), usata SOLO quando nessuno dei tre livelli più
    | affidabili (VERP, In-Reply-To/References, token subject) produce un match.
    |
    */

    'threading' => [
        'heuristic_window_days' => (int) env('MAIL_THREAD_HEURISTIC_WINDOW_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Allegati inbound (§7.3.9 del PRD, US-309)
    |--------------------------------------------------------------------------
    |
    | Configurazione propria e distinta da config/ticketing.php
    | (App\Domain\Ticketing\Support\TicketAttachmentTypes, US-107): il contesto
    | email è deliberatamente più permissivo (mittenti esterni possono allegare
    | tipi di file che un upload dal portale non ammetterebbe). Gli allegati
    | inline (loghi/firme, riconosciuti da Content-Disposition: inline) sono
    | esclusi per default.
    |
    */

    'attachments' => [
        'max_file_size' => (int) env('MAIL_ATTACHMENT_MAX_FILE_SIZE', 26214400), // 25 MB
        'max_total_size' => (int) env('MAIL_ATTACHMENT_MAX_TOTAL_SIZE', 52428800), // 50 MB
        'max_count' => (int) env('MAIL_ATTACHMENT_MAX_COUNT', 20),
        'include_inline' => (bool) env('MAIL_ATTACHMENT_INCLUDE_INLINE', false),

        'allowed_extensions' => array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) env(
                'MAIL_ATTACHMENT_ALLOWED_EXTENSIONS',
                'pdf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp,txt,csv,rtf,eml,msg,'
                .'jpg,jpeg,png,gif,bmp,webp,heic,tif,tiff,'
                .'zip,rar,7z,mp3,wav,mp4,mov',
            )),
        ))),

        'allowed_mimes' => array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) env(
                'MAIL_ATTACHMENT_ALLOWED_MIMES',
                'application/pdf,application/msword,'
                .'application/vnd.openxmlformats-officedocument.wordprocessingml.document,'
                .'application/vnd.ms-excel,'
                .'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,'
                .'application/vnd.ms-powerpoint,'
                .'application/vnd.openxmlformats-officedocument.presentationml.presentation,'
                .'application/vnd.oasis.opendocument.text,application/vnd.oasis.opendocument.spreadsheet,'
                .'application/vnd.oasis.opendocument.presentation,'
                .'text/plain,text/csv,application/rtf,message/rfc822,application/vnd.ms-outlook,'
                .'image/jpeg,image/png,image/gif,image/bmp,image/webp,image/heic,image/tiff,'
                .'application/zip,application/x-rar-compressed,application/vnd.rar,application/x-7z-compressed,'
                .'audio/mpeg,audio/wav,audio/x-wav,video/mp4,video/quicktime',
            )),
        ))),
    ],

    'staff_notification_group' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('MAIL_STAFF_NOTIFICATION_GROUP', '')),
    ))),

    'support_address' => env('MAIL_SUPPORT_ADDRESS', ''),

    /*
    |--------------------------------------------------------------------------
    | Quarantena — link diretto dalla notifica E9 (§7.3.8/§7.7 del PRD, US-312)
    |--------------------------------------------------------------------------
    |
    | Base URL della pagina di amministrazione "Quarantena" (US-322, non ancora
    | costruita in questa fase): finché resta vuota, E9 (UnknownSenderStaffMail)
    | non mostra nessun link — mai un link rotto verso una pagina che non
    | esiste ancora (stesso principio già applicato a
    | notification_preferences_url/US-317 e alla voce di menu Mailpit/US-324).
    | Quando valorizzata, l'ulid del messaggio in quarantena è appeso come
    | segmento di path.
    |
    */

    'quarantine_review_url' => env('MAIL_QUARANTINE_REVIEW_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Layout email — link footer (§7.5.4 del PRD, US-310)
    |--------------------------------------------------------------------------
    |
    | URL della pagina "gestisci le tue preferenze di notifica" linkata dal
    | footer condiviso (resources/views/emails/layouts/base.blade.php). La UI
    | per gestirle è fuori scope in questa fase (assegnata alla Fase 6, vedi
    | prd.json): finché non esiste una pagina reale, il link resta vuoto e il
    | componente footer lo nasconde — mai un link rotto (stesso principio già
    | applicato alla voce di menu Mailpit, US-324).
    |
    */

    'notification_preferences_url' => env('MAIL_NOTIFICATION_PREFERENCES_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Voce di menu "Mailpit" (§7.7 del PRD, US-324)
    |--------------------------------------------------------------------------
    |
    | URL della UI Mailpit, mostrato come prima sotto-voce del gruppo di
    | navigazione "Email" SOLO in ambiente locale/staging (mai in produzione)
    | e SOLO se questa variabile è valorizzata — vuota di default, mai un link
    | rotto verso un Mailpit che non esiste in quell'ambiente (vedi
    | App\Filament\Navigation\MailpitNavigationItem).
    |
    */

    'mailpit_url' => env('MAILPIT_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Reinvio outbound falliti (§7.3.3 del PRD, US-325)
    |--------------------------------------------------------------------------
    |
    | Valore di default usato da `mail:retry-failed` quando non si sovrascrive
    | `--limit` da CLI, e cadenza dello scheduler quando il feature flag
    | config('orchestrator.features.mail_retry_failed') (già presente da
    | Fase 0) è attivo.
    |
    */

    'retry' => [
        'default_limit' => (int) env('MAIL_RETRY_DEFAULT_LIMIT', 50),
        'schedule_cron' => env('MAIL_RETRY_SCHEDULE_CRON', '0 * * * *'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Digest periodico (E8, §7.5.2/§10.2 del PRD, US-614)
    |--------------------------------------------------------------------------
    |
    | Cadenza dello scheduler per `mail:send-digest`, dietro il feature flag
    | già presente da Fase 0 (config('orchestrator.features.mail_digest')).
    | Nessuna soglia di finestra qui: l'AC della story fissa esplicitamente
    | "24h precedenti", non un valore configurabile come le soglie in giorni
    | lavorativi di config/ticketing.php.
    |
    */

    'digest' => [
        'schedule_cron' => env('MAIL_DIGEST_SCHEDULE_CRON', '0 7 * * *'),
    ],

];
