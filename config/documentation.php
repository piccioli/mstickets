<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | PDF della documentazione (§6.4.3 del PRD, US-406)
    |--------------------------------------------------------------------------
    |
    | Logo e dati societari mostrati nell'intestazione/piè di pagina del PDF
    | generato da App\Domain\Documentation\Actions\GenerateDocumentationPagePdf.
    | Il logo è letto da percorso file system (mai un URL: il job gira in coda,
    | non può dipendere dalla raggiungibilità HTTP dell'app verso se stessa) e
    | incorporato nel PDF come data URI base64.
    |
    */

    'pdf' => [
        'logo_path' => env('PDF_LOGO_PATH') ?: public_path('images/branding/montagna-servizi-logo.png'),

        'footer' => env('PDF_FOOTER', 'Montagna Servizi SCPA — Via Errico Petrella 19, 20124 Milano (MI) · P.IVA 11790660960 · SDI: M5UXCR1 · info@montagnaservizi.com'),

        // Disco privato dedicato (mai `public`, stesso ragionamento di
        // `documentation-attachments`, §6.4.1/§9.6): il download passa sempre
        // dalla rotta autorizzata da DocumentationPagePolicy::view().
        'disk' => env('DOCUMENTATION_PDF_DISK', 'documentation-pdfs'),
    ],

];
