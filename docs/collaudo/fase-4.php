<?php

declare(strict_types=1);

// Manifest di tracciabilità per il collaudo (UAT) di Fase 4 (Tag/commesse con SAL,
// Documentation con generazione PDF, Activity Report/Organizations — US-401..US-411).
// A differenza di Fase 3 (un topic per story), qui i topic sono raggruppati per area
// funzionale del PRD (§6.3 Tag/commesse, §6.4 Documentation, §6.5 Activity
// Report/Organizations), più un topic finale dedicato al checkpoint di fine fase
// (US-411). Ogni voce collega un criterio di accettazione a un test automatico
// REALMENTE esistente in tests/ (verificato da `collaudo:verify-manifest 4`). Fonte
// delle story: scripts/ralph/prd.json (Fase 4). Questo file è puro dato: nessuna
// logica.

return [
    'fase' => '4',
    'titolo' => 'Fase 4 (Tag/commesse, Documentation, Activity Report/Organizations)',
    'parte_1' => [
        'app_url' => 'https://ticket-uat.montagnaservizi.com',
        'mailpit_url' => 'https://mailpit-ticket-uat.montagnaservizi.com',
        'credenziali' => [
            ['ruolo' => 'Admin', 'email' => 'info@montagnaservizi.com', 'password' => 'uat'],
            ['ruolo' => 'Developer', 'email' => 'lorena.sava@montagnaservizi.com', 'password' => 'uat'],
            ['ruolo' => 'Manager', 'email' => 'manager@oc.test', 'password' => 'uat'],
            ['ruolo' => 'Customer', 'email' => 'infosentieroitalia@cai.it', 'password' => 'uat'],
            ['ruolo' => 'Fundraising', 'email' => 'sara.mariani@montagnaservizi.com', 'password' => 'uat'],
        ],
    ],
    'topics' => [
        [
            'titolo' => 'Tag / commesse — modello SAL, azione "crea commessa", vista elenco (§6.3, US-401..US-403)',
            'test' => [
                [
                    'id' => 'F4-01',
                    'descrizione' => 'sal() è null quando la commessa non ha ore stimate',
                    'test_automatico' => 'tests/Unit/Domain/Tags/TagSalTest.php::sal() is null when estimated_hours is null',
                ],
                [
                    'id' => 'F4-02',
                    'descrizione' => 'sal() somma i minuti lavorati di tutti i ticket collegati e arrotonda a 2 decimali',
                    'test_automatico' => 'tests/Unit/Domain/Tags/TagSalTest.php::sal() rounds to two decimal places',
                ],
                [
                    'id' => 'F4-03',
                    'descrizione' => 'La commessa risulta chiusa solo quando ogni ticket collegato è rilasciato o completato',
                    'test_automatico' => 'tests/Unit/Domain/Tags/TagSalTest.php::isClosed() is true when every linked ticket is released or done',
                ],
                [
                    'id' => 'F4-04',
                    'descrizione' => 'Creare una commessa da un ticket precompila le ore stimate dal ticket e li collega',
                    'test_automatico' => 'tests/Feature/Domain/Tags/Actions/CreateTagFromTicketTest.php::creating a tag from a ticket precompiles estimated_hours from the ticket and links it',
                ],
                [
                    'id' => 'F4-05',
                    'descrizione' => 'Lo slug generato riceve un suffisso numerico in caso di collisione, incluse le commesse soft-deleted',
                    'test_automatico' => 'tests/Feature/Domain/Tags/Actions/CreateTagFromTicketTest.php::the generated slug gets a numeric suffix when it collides with an existing tag, including soft-deleted ones',
                ],
                [
                    'id' => 'F4-06',
                    'descrizione' => 'Un utente con tag.create può trasformare un ticket in commessa dalla pagina di visualizzazione',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/CreateCommessaActionTest.php::a user with tag.create can turn a ticket into a commessa from the view page',
                ],
                [
                    'id' => 'F4-07',
                    'descrizione' => 'Un utente senza tag.create non vede l\'azione "crea commessa"',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/CreateCommessaActionTest.php::a user without tag.create cannot see the create commessa action',
                ],
                [
                    'id' => 'F4-08',
                    'descrizione' => 'L\'elenco commesse mostra ore stimate/lavorate, barra SAL e conteggio ticket aperti/chiusi',
                    'test_automatico' => 'tests/Feature/Filament/Tags/TagResourceTest.php::the list shows estimated/worked hours, the SAL bar and the open/closed ticket counts',
                ],
                [
                    'id' => 'F4-09',
                    'descrizione' => 'Una commessa senza ore stimate mostra un placeholder SAL invece di un errore di divisione',
                    'test_automatico' => 'tests/Feature/Filament/Tags/TagResourceTest.php::a tag with no estimated hours shows a SAL placeholder instead of a division error',
                ],
                [
                    'id' => 'F4-10',
                    'descrizione' => 'Un utente senza tag.view non accede all\'elenco commesse',
                    'test_automatico' => 'tests/Feature/Filament/Tags/TagResourceTest.php::a user without tag.view is denied access to the tags resource',
                ],
            ],
        ],
        [
            'titolo' => 'Documentation — modello, visibilità, Resource, generazione PDF (§6.4, US-404..US-406)',
            'test' => [
                [
                    'id' => 'F4-11',
                    'descrizione' => 'Lo scope di visibilità esclude le pagine interne per chi non ha documentation.view.internal',
                    'test_automatico' => 'tests/Feature/Domain/Documentation/DocumentationPageVisibilityTest.php::scopeVisibleTo excludes internal pages for a user without documentation.view.internal',
                ],
                [
                    'id' => 'F4-12',
                    'descrizione' => 'Un cliente non può visualizzare una pagina interna nemmeno richiedendone direttamente l\'id',
                    'test_automatico' => 'tests/Feature/Domain/Documentation/DocumentationPageVisibilityTest.php::a customer cannot view an internal page even by requesting its id directly',
                ],
                [
                    'id' => 'F4-13',
                    'descrizione' => 'Creare una pagina di documentazione crea un tag collegato "Documentation: <titolo>"',
                    'test_automatico' => 'tests/Feature/Domain/Documentation/DocumentationPageAutoTagTest.php::creating a documentation page creates a linked tag named "Documentation: <title>"',
                ],
                [
                    'id' => 'F4-14',
                    'descrizione' => 'Rinominare una pagina rinomina il tag collegato esistente senza crearne un duplicato',
                    'test_automatico' => 'tests/Feature/Domain/Documentation/DocumentationPageAutoTagTest.php::renaming a documentation page renames the existing linked tag without creating a duplicate',
                ],
                [
                    'id' => 'F4-15',
                    'descrizione' => 'Un utente con documentation.view.customer accede al registro e vede solo le pagine cliente',
                    'test_automatico' => 'tests/Feature/Filament/Documentation/DocumentationPageResourceTest.php::a user with documentation.view.customer can access the registry and see only customer pages',
                ],
                [
                    'id' => 'F4-16',
                    'descrizione' => 'La ricerca full-text trova una pagina da un termine presente solo nel corpo',
                    'test_automatico' => 'tests/Feature/Filament/Documentation/DocumentationPageResourceTest.php::full-text search finds a page by a term only present in the body',
                ],
                [
                    'id' => 'F4-17',
                    'descrizione' => 'Creare una pagina genera un PDF non vuoto e valorizza pdf_path/pdf_generated_at',
                    'test_automatico' => 'tests/Feature/Domain/Documentation/DocumentationPagePdfTest.php::creating a documentation page generates a non-empty PDF and stamps pdf_path/pdf_generated_at',
                ],
                [
                    'id' => 'F4-18',
                    'descrizione' => 'Modificare il titolo rigenera il PDF con un timestamp più recente',
                    'test_automatico' => 'tests/Feature/Domain/Documentation/DocumentationPagePdfTest.php::changing the title regenerates the PDF with a newer timestamp',
                ],
                [
                    'id' => 'F4-19',
                    'descrizione' => 'Il comando documentation:regenerate-pdfs rigenera il PDF di ogni pagina',
                    'test_automatico' => 'tests/Feature/Console/DocumentationRegeneratePdfsCommandTest.php::regenerates the pdf of every documentation page',
                ],
                [
                    'id' => 'F4-20',
                    'descrizione' => 'Un utente che può visualizzare la pagina può scaricarne il PDF',
                    'test_automatico' => 'tests/Feature/Http/DocumentationPagePdfDownloadControllerTest.php::a user who can view the documentation page can download its pdf',
                ],
                [
                    'id' => 'F4-21',
                    'descrizione' => 'Un utente senza il permesso di categoria corrispondente è negato, anche via accesso diretto per id',
                    'test_automatico' => 'tests/Feature/Http/DocumentationPagePdfDownloadControllerTest.php::a user without the matching category permission is denied, even by direct id access',
                ],
            ],
        ],
        [
            'titolo' => 'Activity Report e Organizations — modello, sync, PDF, comando mensile (§6.5, US-407..US-410)',
            'test' => [
                [
                    'id' => 'F4-22',
                    'descrizione' => 'Un utente con organization.view accede al registro organizzazioni',
                    'test_automatico' => 'tests/Feature/Filament/Organizations/OrganizationResourceTest.php::a user with organization.view can access the organizations registry',
                ],
                [
                    'id' => 'F4-23',
                    'descrizione' => 'Collegare un utente tramite il relation manager "Membri" lo collega all\'organizzazione',
                    'test_automatico' => 'tests/Feature/Filament/Organizations/OrganizationResourceTest.php::adding a user via the members relation manager attaches it to the organization',
                ],
                [
                    'id' => 'F4-24',
                    'descrizione' => 'periodStart/periodEnd coprono l\'intero mese per un report mensile',
                    'test_automatico' => 'tests/Unit/Domain/Reporting/ActivityReportPeriodTest.php::periodStart/periodEnd span the full month for a monthly report',
                ],
                [
                    'id' => 'F4-25',
                    'descrizione' => 'periodLabel è il nome del mese localizzato e capitalizzato più l\'anno per un report mensile',
                    'test_automatico' => 'tests/Unit/Domain/Reporting/ActivityReportPeriodTest.php::periodLabel is the localized capitalized month name and year for a monthly report',
                ],
                [
                    'id' => 'F4-26',
                    'descrizione' => 'syncTickets seleziona solo i ticket del proprietario utente completati nel periodo',
                    'test_automatico' => 'tests/Feature/Domain/Reporting/Services/ActivityReportSyncServiceTest.php::syncTickets selects only the owner user tickets done within the period',
                ],
                [
                    'id' => 'F4-27',
                    'descrizione' => 'syncTickets seleziona i ticket richiesti da ogni membro dell\'organizzazione proprietaria',
                    'test_automatico' => 'tests/Feature/Domain/Reporting/Services/ActivityReportSyncServiceTest.php::syncTickets selects tickets requested by any member of the owner organization',
                ],
                [
                    'id' => 'F4-28',
                    'descrizione' => 'syncTickets è idempotente se invocato due volte di seguito sullo stesso report',
                    'test_automatico' => 'tests/Feature/Domain/Reporting/Services/ActivityReportSyncServiceTest.php::syncTickets is idempotent when invoked twice in a row',
                ],
                [
                    'id' => 'F4-29',
                    'descrizione' => 'Creare il report sincronizza i suoi ticket in un\'unica chiamata',
                    'test_automatico' => 'tests/Feature/Domain/Reporting/Actions/CreateActivityReportTest.php::creates the report and syncs its tickets in one call',
                ],
                [
                    'id' => 'F4-30',
                    'descrizione' => 'Un duplicato proprietario/periodo viene rifiutato con un errore leggibile invece della QueryException grezza',
                    'test_automatico' => 'tests/Feature/Domain/Reporting/Actions/CreateActivityReportTest.php::rejects a duplicate owner/period with a readable error instead of the raw QueryException',
                ],
                [
                    'id' => 'F4-31',
                    'descrizione' => 'Generare il PDF del report produce un file non vuoto e valorizza pdf_path/pdf_generated_at',
                    'test_automatico' => 'tests/Feature/Domain/Reporting/ActivityReportPdfTest.php::generates a non-empty PDF and stamps pdf_path/pdf_generated_at',
                ],
                [
                    'id' => 'F4-32',
                    'descrizione' => 'Cancellare il report rimuove il PDF generato dallo storage, nessun file orfano',
                    'test_automatico' => 'tests/Feature/Domain/Reporting/ActivityReportPdfTest.php::deleting the report removes its generated PDF from storage',
                ],
                [
                    'id' => 'F4-33',
                    'descrizione' => 'activity-report.view.own autorizza un membro dell\'organizzazione proprietaria ma non un non-membro',
                    'test_automatico' => 'tests/Feature/Domain/Reporting/ActivityReportPolicyTest.php::activity-report.view.own authorizes a member of the owner organization but not a non-member (US-409)',
                ],
                [
                    'id' => 'F4-34',
                    'descrizione' => 'Un utente con solo activity-report.view.own può scaricare il proprio report',
                    'test_automatico' => 'tests/Feature/Http/ActivityReportPdfDownloadControllerTest.php::a user with only activity-report.view.own can download their own report',
                ],
                [
                    'id' => 'F4-35',
                    'descrizione' => 'Il comando reports:generate-monthly crea il report per un cliente con un ticket completato nel mese precedente e accoda il PDF',
                    'test_automatico' => 'tests/Feature/Console/ReportsGenerateMonthlyCommandTest.php::creates a monthly report for a customer with a ticket done in the previous month and queues its pdf',
                ],
                [
                    'id' => 'F4-36',
                    'descrizione' => 'Rieseguire il comando non duplica un report già creato per lo stesso proprietario e periodo',
                    'test_automatico' => 'tests/Feature/Console/ReportsGenerateMonthlyCommandTest.php::re-running the command does not duplicate a report already created for the same owner and period',
                ],
                [
                    'id' => 'F4-37',
                    'descrizione' => '--dry-run esamina i proprietari attivi senza creare report né accodare PDF',
                    'test_automatico' => 'tests/Feature/Console/ReportsGenerateMonthlyCommandTest.php::--dry-run examines active owners without creating any report or queuing any pdf',
                ],
                [
                    'id' => 'F4-38',
                    'descrizione' => 'view.own vede solo il proprio report come proprietario diretto, mai quello di un altro owner',
                    'test_automatico' => 'tests/Feature/Domain/Reporting/ActivityReportScopeVisibleToTest.php::view.own sees only its own report as a direct user owner, never another owner',
                ],
                [
                    'id' => 'F4-39',
                    'descrizione' => 'Un cliente con activity-report.view.own vede solo il proprio report nell\'elenco "Report Attività"',
                    'test_automatico' => 'tests/Feature/Filament/ActivityReports/ActivityReportResourceTest.php::a customer with activity-report.view.own sees only its own report in the list',
                ],
            ],
        ],
        [
            'titolo' => 'Checkpoint di fine fase — verifica end-to-end su dati reali (US-411)',
            'test' => [
                [
                    'id' => 'F4-40',
                    'descrizione' => 'Il SAL è calcolato correttamente su una commessa con ticket collegati (replica automatica della verifica manuale su dati reali v1:import)',
                    'test_automatico' => 'tests/Feature/EndToEnd/Fase4CheckpointEndToEndTest.php::SAL is computed correctly on a real commessa with linked tickets',
                ],
                [
                    'id' => 'F4-41',
                    'descrizione' => 'Una pagina di documentazione genera un PDF scaricabile con la carta intestata Montagna Servizi corretta',
                    'test_automatico' => 'tests/Feature/EndToEnd/Fase4CheckpointEndToEndTest.php::a documentation page pdf is generated and downloadable with the correct letterhead content',
                ],
                [
                    'id' => 'F4-42',
                    'descrizione' => 'Un report attività è generato per un proprietario reale con ticket e totali verificati contro i ticket sorgente',
                    'test_automatico' => 'tests/Feature/EndToEnd/Fase4CheckpointEndToEndTest.php::an activity report is generated for a real owner with tickets and totals verified against the source tickets',
                ],
            ],
        ],
    ],
];
