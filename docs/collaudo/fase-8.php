<?php

declare(strict_types=1);

// Manifest di tracciabilità per il collaudo (UAT) di Fase 8 (Integrazione dati RUNTS-CAI —
// Sezioni/Sottosezioni, US-801..US-808). Stessa disciplina delle fasi precedenti (vedi
// docs/collaudo/fase-7.php): topic raggruppati per user story del PRD (scripts/ralph/prd.json,
// Fase 8), in ordine di priorità US-801 -> US-808, più un topic finale dedicato al checkpoint di
// fine fase (US-808). Ogni voce collega un criterio di accettazione a un test automatico
// REALMENTE esistente in tests/ (verificato da `collaudo:verify-manifest 8`). Scope confermato
// col committente durante il design (orchestrator/docs/superpowers/specs/
// 2026-08-28-integrazione-runts-cai-design.md): limitato a Sezioni/Sottosezioni — Gruppi
// Regionali RUNTS, report PDF per singola sezione, refresh automatico del datapack e scraper
// Python sono esplicitamente fuori scope. Il test "the regional group sections card is absent
// for sezione, organo tecnico/struttura operativa, and generico customers" (CustomerDashboardTest,
// introdotto in Fase 7) resta tracciato solo in fase-7.php (F7-35): non ridondato qui.

return [
    'fase' => '8',
    'titolo' => 'Fase 8 (Integrazione dati RUNTS-CAI — Sezioni/Sottosezioni)',
    'parte_1' => [
        'app_url' => 'https://ticket-uat.montagnaservizi.com',
        'mailpit_url' => 'https://mailpit-ticket-uat.montagnaservizi.com',
        'credenziali' => [
            ['ruolo' => 'Admin', 'email' => 'info@montagnaservizi.com', 'password' => 'uat'],
            ['ruolo' => 'Developer', 'email' => 'lorena.sava@montagnaservizi.com', 'password' => 'uat'],
            ['ruolo' => 'Manager', 'email' => 'manager@oc.test', 'password' => 'uat'],
            ['ruolo' => 'Customer', 'email' => 'infosentieroitalia@cai.it', 'password' => 'uat'],
        ],
    ],
    'topics' => [
        [
            'titolo' => 'Schema dati App\\Domain\\CaiDirectory — tabelle e relazioni (US-801)',
            'test' => [
                [
                    'id' => 'F8-01',
                    'descrizione' => 'La tabella cai_sections ha le colonne richieste da US-801',
                    'test_automatico' => 'tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php::cai_sections table has the columns required by US-801',
                ],
                [
                    'id' => 'F8-02',
                    'descrizione' => 'La tabella cai_subsections ha le colonne richieste da US-801',
                    'test_automatico' => 'tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php::cai_subsections table has the columns required by US-801',
                ],
                [
                    'id' => 'F8-03',
                    'descrizione' => 'La tabella cai_runts_registrations ha le colonne richieste da US-801',
                    'test_automatico' => 'tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php::cai_runts_registrations table has the columns required by US-801',
                ],
                [
                    'id' => 'F8-04',
                    'descrizione' => 'La tabella cai_financial_statements ha le colonne richieste da US-801',
                    'test_automatico' => 'tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php::cai_financial_statements table has the columns required by US-801',
                ],
                [
                    'id' => 'F8-05',
                    'descrizione' => 'La tabella cai_board_members ha le colonne richieste da US-801 (tabella vuota all\'origine, struttura pronta)',
                    'test_automatico' => 'tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php::cai_board_members table has the columns required by US-801',
                ],
                [
                    'id' => 'F8-06',
                    'descrizione' => 'La tabella cai_documents ha le colonne richieste da US-801',
                    'test_automatico' => 'tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php::cai_documents table has the columns required by US-801',
                ],
                [
                    'id' => 'F8-07',
                    'descrizione' => 'cai_sections usa codice_cai come chiave primaria naturale, non incrementale',
                    'test_automatico' => 'tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php::cai_sections uses codice_cai as a natural, non-incrementing primary key',
                ],
                [
                    'id' => 'F8-08',
                    'descrizione' => 'Una sezione ha molte sottosezioni e appartiene a un utente (relazioni Eloquent)',
                    'test_automatico' => 'tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php::a section has many subsections and belongs to a user',
                ],
                [
                    'id' => 'F8-09',
                    'descrizione' => 'Eliminare l\'utente collegato lascia user_id della sezione a null (FK nullable, mai un errore)',
                    'test_automatico' => 'tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php::deleting the linked user leaves the section user_id null',
                ],
                [
                    'id' => 'F8-10',
                    'descrizione' => 'Una registrazione RUNTS appartiene a una sezione e ha molti bilanci, cariche sociali e documenti',
                    'test_automatico' => 'tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php::a runts registration belongs to a section and has many statements, board members and documents',
                ],
            ],
        ],
        [
            'titolo' => 'Comando cai:import-datapack — import e matching per email (US-802)',
            'test' => [
                [
                    'id' => 'F8-11',
                    'descrizione' => 'Il file datapack mancante al percorso indicato stampa un messaggio esplicito e fallisce, mai un errore criptico',
                    'test_automatico' => 'tests/Feature/Console/CaiImportDatapackCommandTest.php::missing datapack file prints an explicit message and fails, no cryptic error',
                ],
                [
                    'id' => 'F8-12',
                    'descrizione' => 'L\'opzione --dry-run non scrive alcuna riga né alcun file',
                    'test_automatico' => 'tests/Feature/Console/CaiImportDatapackCommandTest.php::--dry-run writes nothing',
                ],
                [
                    'id' => 'F8-13',
                    'descrizione' => 'L\'import completo popola le sei tabelle con i campi mappati correttamente, collega gli utenti per email case-insensitive, salta gli enti senza match e copia i file degli allegati',
                    'test_automatico' => 'tests/Feature/Console/CaiImportDatapackCommandTest.php::full import populates all six tables with correctly mapped fields, matches users by email case-insensitively, skips unmatched enti and copies allegati files',
                ],
                [
                    'id' => 'F8-14',
                    'descrizione' => 'Eseguire l\'import due volte sulla stessa fixture è idempotente (nessun duplicato, righe invariate non riscritte)',
                    'test_automatico' => 'tests/Feature/Console/CaiImportDatapackCommandTest.php::running the import twice against the same fixture is idempotent (no duplicates, unchanged rows not re-updated)',
                ],
            ],
        ],
        [
            'titolo' => 'Wiring dell\'import in make setup e nel deploy UAT (US-803)',
            'test' => [
                [
                    'id' => 'F8-15',
                    'descrizione' => 'make setup esegue cai:import-datapack best-effort, dopo v1:import',
                    'test_automatico' => 'tests/Feature/Deploy/CaiDatapackWiringTest.php::runs cai:import-datapack best-effort in make setup, after v1:import',
                ],
                [
                    'id' => 'F8-16',
                    'descrizione' => 'CAI_DATAPACK_HOST_PATH è dichiarata in .env.uat.example, coerente col percorso remoto di default di bin/push-cai-datapack',
                    'test_automatico' => 'tests/Feature/Deploy/CaiDatapackWiringTest.php::declares CAI_DATAPACK_HOST_PATH in .env.uat.example, matching bin/push-cai-datapack default remote path',
                ],
                [
                    'id' => 'F8-17',
                    'descrizione' => 'CAI_DATAPACK_HOST_PATH è montata in sola lettura nel servizio app, stesso pattern di LEGACY_MEDIA_HOST_PATH',
                    'test_automatico' => 'tests/Feature/Deploy/CaiDatapackWiringTest.php::bind-mounts CAI_DATAPACK_HOST_PATH read-only into the app service, same pattern as LEGACY_MEDIA_HOST_PATH',
                ],
                [
                    'id' => 'F8-18',
                    'descrizione' => 'remote-deploy.sh esegue cai:import-datapack in modo incondizionato, dopo v1:import --anonymize, con il commento esplicito sulla ricopiatura manuale',
                    'test_automatico' => 'tests/Feature/Deploy/CaiDatapackWiringTest.php::runs cai:import-datapack unconditionally in remote-deploy.sh, after v1:import --anonymize',
                ],
            ],
        ],
        [
            'titolo' => 'Filament Resource staff — consultazione Sezioni/Sottosezioni CAI (US-804)',
            'test' => [
                [
                    'id' => 'F8-19',
                    'descrizione' => 'Un utente senza cai-directory.view non accede alla lista né al dettaglio',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::a user without cai-directory.view is denied access to the list and detail pages',
                ],
                [
                    'id' => 'F8-20',
                    'descrizione' => 'Un utente con cai-directory.view accede alla lista e vede le colonne attese',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::a user with cai-directory.view can access the list page and sees the expected columns',
                ],
                [
                    'id' => 'F8-21',
                    'descrizione' => 'La risorsa è di sola consultazione: nessuna funzione di creazione, modifica o cancellazione',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::the resource has no create, edit or delete function',
                ],
                [
                    'id' => 'F8-22',
                    'descrizione' => 'La tabella è filtrabile per regione',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::the table is filterable by region',
                ],
                [
                    'id' => 'F8-23',
                    'descrizione' => 'La tabella è filtrabile per presenza di un utente collegato',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::the table is filterable by presence of a linked user',
                ],
                [
                    'id' => 'F8-24',
                    'descrizione' => 'Il dettaglio di una sezione con dati RUNTS, bilanci e allegati mostra i dati attesi',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::viewing a section with runts data, statements and attachments shows the expected data',
                ],
                [
                    'id' => 'F8-25',
                    'descrizione' => 'Il dettaglio di una sezione senza dati RUNTS, bilanci o allegati non genera errori e mostra stati vuoti',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::viewing a section without runts data, statements or attachments does not crash and shows empty states',
                ],
                [
                    'id' => 'F8-26',
                    'descrizione' => 'Un utente autorizzato può scaricare un documento CAI',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::an authorized user can download a cai document',
                ],
                [
                    'id' => 'F8-27',
                    'descrizione' => 'Un utente senza cai-directory.view non può scaricare un documento CAI',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::a user without cai-directory.view is denied downloading a cai document',
                ],
                [
                    'id' => 'F8-28',
                    'descrizione' => 'Un cliente può scaricare un documento della propria sezione CAI',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::a customer can download a document belonging to their own cai section',
                ],
                [
                    'id' => 'F8-29',
                    'descrizione' => 'Un cliente non può scaricare un documento di un\'altra sezione CAI',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::a customer cannot download a document belonging to another cai section',
                ],
                [
                    'id' => 'F8-30',
                    'descrizione' => 'Un cliente Gruppo Regionale può scaricare un documento di una sezione della propria regione',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::a gruppo regionale customer can download a document belonging to a section in their own region',
                ],
                [
                    'id' => 'F8-31',
                    'descrizione' => 'Un cliente Gruppo Regionale non può scaricare un documento di una sezione di un\'altra regione',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::a gruppo regionale customer cannot download a document belonging to a section in another region',
                ],
            ],
        ],
        [
            'titolo' => 'Mappa e export (staff, US-805)',
            'test' => [
                [
                    'id' => 'F8-32',
                    'descrizione' => 'Un utente senza cai-directory.view non accede alla pagina mappa',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionsMapTest.php::a user without cai-directory.view is denied access to the map page',
                ],
                [
                    'id' => 'F8-33',
                    'descrizione' => 'Un utente con cai-directory.view vede sulla mappa solo le sezioni geolocalizzate',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionsMapTest.php::a user with cai-directory.view sees only geolocated sections on the map',
                ],
                [
                    'id' => 'F8-34',
                    'descrizione' => 'Un utente con cai-directory.view può esportare le sezioni correntemente filtrate in CSV',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionsExportTest.php::a user with cai-directory.view can export the currently filtered sections as csv',
                ],
                [
                    'id' => 'F8-35',
                    'descrizione' => 'Un utente con cai-directory.view può esportare le sezioni correntemente filtrate in GeoJSON',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionsExportTest.php::a user with cai-directory.view can export the currently filtered sections as geojson',
                ],
                [
                    'id' => 'F8-36',
                    'descrizione' => 'Un utente con cai-directory.view può esportare le sezioni correntemente filtrate in XLSX',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionsExportTest.php::a user with cai-directory.view can export the currently filtered sections as xlsx',
                ],
                [
                    'id' => 'F8-37',
                    'descrizione' => 'Un utente senza cai-directory.view non vede le azioni di export',
                    'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionsExportTest.php::a user without cai-directory.view cannot see the export actions',
                ],
            ],
        ],
        [
            'titolo' => 'Dati CAI sulla dashboard del cliente Sezione (US-806)',
            'test' => [
                [
                    'id' => 'F8-38',
                    'descrizione' => 'La card CAI mostra i dati della sezione collegata per un cliente Sezione',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the cai directory card shows the linked cai section data for a sezione customer',
                ],
                [
                    'id' => 'F8-39',
                    'descrizione' => 'La card CAI non mostra mai i dati di un\'altra sezione',
                    'test_automatico' => "tests/Feature/Filament/Pages/CustomerDashboardTest.php::the cai directory card never leaks another sezione\\'s data",
                ],
                [
                    'id' => 'F8-40',
                    'descrizione' => 'La card CAI mostra i dati della sottosezione collegata quando nessuna sezione è collegata',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the cai directory card shows the linked cai subsection data when no cai section is linked',
                ],
                [
                    'id' => 'F8-41',
                    'descrizione' => 'La card CAI mostra uno stato vuoto esplicito per un cliente Sezione senza sezione o sottosezione collegata',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the cai directory card shows an explicit empty state for a sezione customer without a linked cai section or subsection',
                ],
                [
                    'id' => 'F8-42',
                    'descrizione' => 'La card CAI è assente per i clienti non-Sezione',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the cai directory card is absent for non-sezione customers',
                ],
            ],
        ],
        [
            'titolo' => 'Dettaglio sezione dalla dashboard del Gruppo Regionale (US-807)',
            'test' => [
                [
                    'id' => 'F8-43',
                    'descrizione' => 'Un cliente Gruppo Regionale può aprire il dettaglio di una sezione della propria regione',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php::a gruppo regionale customer can open the detail of a section in their own region',
                ],
                [
                    'id' => 'F8-44',
                    'descrizione' => 'Un tentativo diretto di aprire una sezione di un\'altra regione è respinto (403)',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php::a direct attempt to open a section of another region is forbidden',
                ],
                [
                    'id' => 'F8-45',
                    'descrizione' => 'Un cliente Gruppo Regionale senza regione valorizzata non può aprire alcun dettaglio sezione',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php::a gruppo regionale customer without a region cannot open any section detail',
                ],
                [
                    'id' => 'F8-46',
                    'descrizione' => 'Un cliente Sezione non può accedere alla pagina di dettaglio del Gruppo Regionale',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php::a sezione customer cannot access the regional group detail page',
                ],
                [
                    'id' => 'F8-47',
                    'descrizione' => 'Un cliente non-customer non può accedere alla pagina di dettaglio del Gruppo Regionale',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php::a non-customer cannot access the regional group detail page',
                ],
                [
                    'id' => 'F8-48',
                    'descrizione' => 'Aprire il dettaglio per un utente che non è una Sezione risulta non trovato (404)',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php::opening the detail for a user that is not a sezione is not found',
                ],
                [
                    'id' => 'F8-49',
                    'descrizione' => 'La pagina di dettaglio mostra lo stesso contenuto della dashboard del cliente Sezione, riusando lo stesso Infolist',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php::the detail page shows the same cai section data as the customer own dashboard, reusing the same infolist',
                ],
                [
                    'id' => 'F8-50',
                    'descrizione' => 'La pagina di dettaglio mostra uno stato vuoto esplicito per una sezione senza dati CAI collegati',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php::the detail page shows an explicit empty state for a section without linked cai data',
                ],
                [
                    'id' => 'F8-51',
                    'descrizione' => 'La card "Sezioni del gruppo regionale" sulla dashboard cliente collega alla pagina di dettaglio sezione',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php::the regional group sections card on the customer dashboard links to the section detail page',
                ],
            ],
        ],
        [
            'titolo' => 'Checkpoint di fine fase — flusso end-to-end import, consultazione staff, dashboard cliente Sezione e Gruppo Regionale (US-808)',
            'test' => [
                [
                    'id' => 'F8-52',
                    'descrizione' => 'Il flusso completo RUNTS-CAI funziona end-to-end: import, matching per email, consultazione staff, dashboard cliente Sezione e dettaglio scoped del cliente Gruppo Regionale',
                    'test_automatico' => 'tests/Feature/EndToEnd/Fase8CheckpointEndToEndTest.php::the full RUNTS-CAI flow works end-to-end: import, email matching, staff consultation, sezione dashboard and regional group scoped detail',
                ],
            ],
        ],
    ],
];
