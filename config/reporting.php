<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | PDF del report di attività (§6.5.3 del PRD, US-409)
    |--------------------------------------------------------------------------
    |
    | Stesso renderer/branding del PDF di documentazione (§6.4.3, US-406): logo
    | e dati societari riusano le stesse variabili PDF_LOGO_PATH/PDF_FOOTER,
    | nessuna duplicazione di configurazione.
    |
    */

    'pdf' => [
        'logo_path' => env('PDF_LOGO_PATH') ?: public_path('images/branding/montagna-servizi-logo.png'),

        'footer' => env('PDF_FOOTER', 'Montagna Servizi SCPA — Via Errico Petrella 19, 20124 Milano (MI) · P.IVA 11790660960 · SDI: M5UXCR1 · info@montagnaservizi.com'),

        // Disco privato dedicato (mai `public`, stesso ragionamento di
        // `documentation-pdfs`, §6.4.3/§9.6): il download passa sempre dalla
        // rotta autorizzata da ActivityReportPolicy::view().
        'disk' => env('ACTIVITY_REPORT_PDF_DISK', 'activity-report-pdfs'),
    ],

    // Sigla usata per comporre il nome del file scaricato (mai il percorso su
    // disco, sempre basato sull'id — vedi GenerateActivityReportPdf): es.
    // "MS-cai-sezione-milano-2026-02.pdf".
    'platform_acronym' => env('PLATFORM_ACRONYM', 'MS'),

];
