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

];
