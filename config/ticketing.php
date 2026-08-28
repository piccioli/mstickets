<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Allegati sui messaggi del ticket (§9.6, §17.2 del PRD)
    |--------------------------------------------------------------------------
    |
    | Unica lista di tipi/dimensione ammessi per gli allegati: condivisa tra
    | l'upload da UI (questa fase, US-107) e un futuro parser email inbound
    | (Fase 3) tramite App\Domain\Ticketing\Support\TicketAttachmentTypes, mai
    | duplicata altrove. Il disco è sempre privato (mai `public`): il download
    | passa da una rotta dedicata autorizzata dalla TicketPolicy (US-105), mai
    | da un URL medialibrary diretto (§9.6, decisione Q10 del PRD: nessuna
    | compatibilità richiesta con le URL pubbliche del v1).
    |
    */

    'attachments' => [
        'disk' => env('TICKET_ATTACHMENTS_DISK', 'ticket-attachments'),

        // Byte, non KB: letto direttamente da UploadedFile::getSize()/Media::$size.
        'max_file_size' => (int) env('TICKET_MAX_FILE_SIZE', 10 * 1024 * 1024),

        'documents' => [
            'extensions' => array_filter(explode(',', (string) env(
                'TICKET_ALLOWED_DOCUMENT_TYPES',
                'pdf,doc,docx,xls,xlsx,ppt,pptx,json,geojson,txt,csv,zip'
            ))),
            'mimes' => array_filter(explode(',', (string) env(
                'TICKET_ALLOWED_DOCUMENT_MIMES',
                'application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,'.
                'application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,'.
                'application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,'.
                'application/json,application/geo+json,text/plain,text/csv,application/zip'
            ))),
        ],

        'images' => [
            'extensions' => array_filter(explode(',', (string) env(
                'TICKET_ALLOWED_IMAGE_TYPES',
                'jpg,jpeg,png,gif,bmp,webp,svg,tiff,heic'
            ))),
            'mimes' => array_filter(explode(',', (string) env(
                'TICKET_ALLOWED_IMAGE_MIMES',
                'image/jpeg,image/jpg,image/png,image/gif,image/bmp,image/webp,image/svg+xml,image/tiff,image/heic'
            ))),
        ],

        // `mp4`/`video/mp4` compare tra gli audio (non un errore di categoria): i
        // messaggi vocali di alcuni client mobili arrivano con quel contenitore
        // (§17.2 nota del PRD).
        'audio' => [
            'extensions' => array_filter(explode(',', (string) env(
                'TICKET_ALLOWED_AUDIO_TYPES',
                'mp3,m4a,wav,ogg,aac,flac,wma,mp4'
            ))),
            'mimes' => array_filter(explode(',', (string) env(
                'TICKET_ALLOWED_AUDIO_MIMES',
                'audio/mpeg,audio/mp4,audio/x-m4a,audio/wav,audio/ogg,audio/aac,audio/flac,audio/x-ms-wma,video/mp4'
            ))),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracciamento visualizzazioni (§6.2.3 del PRD)
    |--------------------------------------------------------------------------
    |
    | Soglia di throttling per l'aggiornamento di `ticket_views.last_viewed_at`/
    | `view_count`: una visualizzazione entro questa finestra dall'ultima
    | registrata per lo stesso (ticket, utente, giorno) non tocca la riga
    | esistente (US-108).
    |
    */

    'views' => [
        'throttle_minutes' => (int) env('TICKET_VIEW_THROTTLE_MINUTES', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reminder ticket in attesa (§7.5.2 E7 del PRD, US-316)
    |--------------------------------------------------------------------------
    |
    | Soglia (giorni lavorativi, lun-ven, App\Domain\Ticketing\Support\
    | WorkingDaysCalculator) di inattività su un ticket `status=waiting` oltre
    | la quale il richiedente riceve un promemoria via `tickets:remind-waiting`,
    | e finestra minima (giorni di calendario) tra due promemoria consecutivi
    | sullo stesso ticket per non duplicarli.
    |
    */

    'waiting_reminder' => [
        'threshold_working_days' => (int) env('TICKET_WAITING_REMINDER_THRESHOLD_DAYS', 3),
        'cooldown_days' => (int) env('TICKET_WAITING_REMINDER_COOLDOWN_DAYS', 7),
        'schedule_cron' => env('TICKET_WAITING_REMINDER_SCHEDULE_CRON', '0 6 * * *'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Badge di navigazione (§8.4 del PRD, US-604)
    |--------------------------------------------------------------------------
    |
    | TTL della cache dei conteggi "in attesa"/"problemi"/"da testare" mostrati
    | sulla voce di menu Ticket: evita una query sincrona a ogni caricamento di
    | pagina, per utente autenticato (chiave di cache scoped su user id).
    |
    */

    'navigation_badges' => [
        'cache_ttl_seconds' => (int) env('TICKET_NAVIGATION_BADGE_TTL', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Automazioni schedulate T3/T4 (§6.1.5, §10.2 del PRD, US-610)
    |--------------------------------------------------------------------------
    |
    | Cadenza di `tickets:progress-to-todo` (18:00, tutti i ticket `progress` →
    | `todo`) e di `tickets:auto-close-released` (07:45, ticket `released` da
    | almeno `threshold_working_days` giorni lavorativi — App\Domain\Ticketing\
    | Support\WorkingDaysCalculator, stesso calcolo del reminder E7 — → `done`).
    | Dietro i feature flag già presenti da Fase 0
    | (config('orchestrator.features.tickets_progress_to_todo')/
    | tickets_auto_close_released), disattivati di default.
    |
    */

    'progress_to_todo' => [
        'schedule_cron' => env('TICKET_PROGRESS_TO_TODO_SCHEDULE_CRON', '0 18 * * *'),
    ],

    'auto_close_released' => [
        'threshold_working_days' => (int) env('TICKET_AUTO_CLOSE_RELEASED_THRESHOLD_DAYS', 3),
        'schedule_cron' => env('TICKET_AUTO_CLOSE_RELEASED_SCHEDULE_CRON', '45 7 * * *'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Automazione schedulata T5 (§6.1.5, §10.2 del PRD, US-611)
    |--------------------------------------------------------------------------
    |
    | Cadenza di `tickets:close-scrum` (16:00, ticket `type = scrum` creati/aggiornati
    | oggi → `done` tramite la riga T5 dedicata di
    | App\Domain\Ticketing\StateMachine\TicketStateMachine). Dietro il feature flag
    | già presente da Fase 0 (config('orchestrator.features.tickets_close_scrum')),
    | disattivato di default.
    |
    */

    'close_scrum' => [
        'schedule_cron' => env('TICKET_CLOSE_SCRUM_SCHEDULE_CRON', '0 16 * * *'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Comando tickets:archive-scrum (§10.2 del PRD, Q9, US-611)
    |--------------------------------------------------------------------------
    |
    | Comportamento v1 non recuperabile con certezza dal dump disponibile
    | (v1dumps/orchestrator-v1-backup-20260726.tar.gz): nessun comando né colonna di
    | archiviazione nel codice applicativo v1, solo viste Nova "Archived*" in sola
    | lettura filtrate per `status` — nessuna mutazione reale da riprodurre. Lettura
    | conservativa adottata qui (da confermare col committente al checkpoint
    | US-618): archivia (`archived_at`, mai una cancellazione né un cambio di
    | `status`) i ticket `type = scrum` già `done` da almeno `threshold_days` giorni
    | di calendario.
    |
    */

    'archive_scrum' => [
        'threshold_days' => (int) env('TICKET_ARCHIVE_SCRUM_THRESHOLD_DAYS', 30),
        'schedule_cron' => env('TICKET_ARCHIVE_SCRUM_SCHEDULE_CRON', '0 5 * * *'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Automazione schedulata T6 (§6.1.5, §10.2 del PRD, US-612)
    |--------------------------------------------------------------------------
    |
    | Cadenza di `tickets:restore-waiting`: ticket `status = waiting` da almeno
    | `threshold_days` giorni DI CALENDARIO (esplicito nel PRD, a differenza di
    | T3/T4 che usano giorni lavorativi) → `previous_status`. Il PRD (§10.2)
    | specifica solo "daily" senza un orario preciso: 06:30 è un valore assunto
    | (tra `waiting_reminder` alle 06:00 e `auto_close_released` alle 07:45),
    | comunque configurabile via env. Dietro il feature flag già presente da
    | Fase 0 (config('orchestrator.features.tickets_restore_waiting')),
    | disattivato di default.
    |
    */

    'restore_waiting' => [
        'threshold_days' => (int) env('TICKET_RESTORE_WAITING_DAYS', 7),
        'schedule_cron' => env('TICKET_RESTORE_WAITING_SCHEDULE_CRON', '30 6 * * *'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Comando tickets:notify-idle-developers — E11 (§7.5.2/§10.2 del PRD, US-616)
    |--------------------------------------------------------------------------
    |
    | Nel v1 il promemoria era un job ritardato di 30 minuti lanciato da un
    | observer, attivo solo prima delle 15:30 (correzione esplicita del PRD
    | principale, §10.2: qui è un comando schedulato, non un job ritardato).
    | `window_start`/`window_end` riproducono lo stesso vincolo orario a
    | livello applicativo (oltre alla cadenza del cron in routes/console.php,
    | difesa in profondità utile anche per un'esecuzione manuale da CLI fuori
    | orario). Dietro il feature flag già presente da Fase 0
    | (config('orchestrator.features.tickets_idle_developer_notice')),
    | disattivato di default.
    |
    */

    'idle_developer_notice' => [
        'window_start' => env('TICKET_IDLE_DEVELOPER_NOTICE_WINDOW_START', '09:00'),
        'window_end' => env('TICKET_IDLE_DEVELOPER_NOTICE_WINDOW_END', '15:30'),
        'schedule_cron' => env('TICKET_IDLE_DEVELOPER_NOTICE_SCHEDULE_CRON', '*/30 9-15 * * *'),
    ],

];
