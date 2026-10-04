<?php

declare(strict_types=1);

// Manifest di tracciabilità per il collaudo (UAT) di Fase 9 (Sincronizzazione live CAI/RUNTS +
// rifiniture ticketing/email). Stessa disciplina delle fasi precedenti (vedi docs/collaudo/fase-8.php):
// topic raggruppati per user story del PRD/prd.json quando esiste un numero US-9xx, altrimenti per
// commit "Fase 9 - ..."/"Fase 9, Storia N" quando la story non ha un numero formale (il ramo
// ralph/orchestrator-v2-fase-9 contiene tre filoni distinti: US-901..US-928 con storia Ralph
// dedicata, una serie di rifiniture UX ticket/email non numerate all'inizio del ramo, e il lavoro di
// fallback CF/PIVA + sorgente documento/upload manuale + menu Gruppo Regionale alla fine). Ogni voce
// collega un criterio di accettazione a un test automatico REALMENTE esistente in tests/ (PHP, Pest)
// o in cai-runts-scraper/tests/ (Python, pytest) — entrambi verificati da `collaudo:verify-manifest 9`.

return [
    'fase' => '9',
    'titolo' => 'Fase 9 (Sincronizzazione live CAI/RUNTS, fallback CF/PIVA, upload manuale documenti, menu Gruppo Regionale)',
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
            'titolo' => 'Campo "Richiesta" obbligatorio alla creazione ticket, rimozione del campo "Ticket padre"',
            'test' => [
                ['id' => 'F9-01', 'descrizione' => 'Creare un ticket richiede sempre una Richiesta', 'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php::creating a ticket requires a richiesta'],
                ['id' => 'F9-02', 'descrizione' => 'La Richiesta diventa il primo messaggio pubblico del ticket, con i suoi allegati', 'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php::the richiesta becomes the first public message of the ticket, with its attachments'],
                ['id' => 'F9-03', 'descrizione' => 'Anche lo staff che crea un ticket fornisce la Richiesta come primo messaggio pubblico', 'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php::staff creating a ticket also provides the richiesta as the first public message'],
                ['id' => 'F9-04', 'descrizione' => 'Il campo "Ticket padre" è assente dal form di creazione ma resta presente in modifica', 'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php::the parent ticket field is absent from the create form but still present on edit'],
                ['id' => 'F9-05', 'descrizione' => 'Il campo nascosto "Ticket padre" in creazione non è impostabile manipolando la fillForm', 'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php::the hidden parent ticket field on create cannot be set via a manipulated fillForm'],
            ],
        ],
        [
            'titolo' => 'Email di conferma apertura ticket (E1/E2) mostra titolo e Richiesta',
            'test' => [
                ['id' => 'F9-06', 'descrizione' => 'L\'email mostra il titolo e il corpo del primo messaggio pubblico (la Richiesta)', 'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/TicketRequestBodyInEmailTest.php::the email shows the title and the body of the first public message (the richiesta)'],
                ['id' => 'F9-07', 'descrizione' => 'Un ticket senza alcun messaggio rende l\'email senza andare in errore', 'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/TicketRequestBodyInEmailTest.php::a ticket without any message renders the email without crashing'],
            ],
        ],
        [
            'titolo' => 'Nuovo indirizzo e telefono Montagna Servizi nel footer delle email',
            'test' => [
                ['id' => 'F9-08', 'descrizione' => 'Il footer mostra il nuovo indirizzo/telefono aziendale, mai il vecchio indirizzo', 'test_automatico' => 'tests/Feature/Domain/Mail/EmailLayoutTest.php::the footer shows the current company address and phone number, not the old address'],
            ],
        ],
        [
            'titolo' => 'cai_last_synced_at su sezioni/sottosezioni (US-901/US-902)',
            'test' => [
                ['id' => 'F9-09', 'descrizione' => 'cai_sections ha una colonna cai_last_synced_at nullable', 'test_automatico' => 'tests/Feature/Database/CaiLastSyncedAtColumnsTest.php::cai_sections has a nullable cai_last_synced_at column'],
                ['id' => 'F9-10', 'descrizione' => 'cai_subsections ha una colonna cai_last_synced_at nullable', 'test_automatico' => 'tests/Feature/Database/CaiLastSyncedAtColumnsTest.php::cai_subsections has a nullable cai_last_synced_at column'],
                ['id' => 'F9-11', 'descrizione' => 'cai_last_synced_at è mass-assignable e castato a datetime su CaiSection', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Models/CaiLastSyncedAtFillableTest.php::cai_last_synced_at is mass-assignable and cast to a datetime on CaiSection'],
                ['id' => 'F9-12', 'descrizione' => 'cai_last_synced_at è mass-assignable e castato a datetime su CaiSubsection', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Models/CaiLastSyncedAtFillableTest.php::cai_last_synced_at is mass-assignable and cast to a datetime on CaiSubsection'],
            ],
        ],
        [
            'titolo' => 'CaiSectionFieldMapper condiviso fra import datapack e sync live (US-903)',
            'test' => [
                ['id' => 'F9-13', 'descrizione' => 'mapSection mappa una riga cai_* normalizzata sugli attributi di CaiSection', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php::mapSection maps a normalized cai_* row to CaiSection attributes'],
                ['id' => 'F9-14', 'descrizione' => 'mapSubsection mappa una riga cai_* normalizzata sugli attributi di CaiSubsection', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php::mapSubsection maps a normalized cai_* row to CaiSubsection attributes'],
                ['id' => 'F9-15', 'descrizione' => 'toCoordinate scarta valori non plausibili (|x| >= 1000)', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php::toCoordinate discards implausible values (|x| >= 1000)'],
                ['id' => 'F9-16', 'descrizione' => 'matchUserId cerca l\'email in modo case-insensitive e con trim', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php::matchUserId is a case-insensitive, trimmed email lookup'],
            ],
        ],
        [
            'titolo' => 'Normalizzatore JSON API CAI pubblica (US-904)',
            'test' => [
                ['id' => 'F9-17', 'descrizione' => 'normalizeSection converte i campi grezzi dell\'API CAI nella forma cai_* del datapack', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php::normalizeSection converts raw CAI API fields to the cai_* row shape'],
                ['id' => 'F9-18', 'descrizione' => 'normalizeSection ripiega su office_address/postal_address in snake_case', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php::normalizeSection falls back to office_address/postal_address snake_case keys'],
                ['id' => 'F9-19', 'descrizione' => 'normalizeSubsection converte i campi grezzi dell\'API CAI, associati al codice della sezione madre', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php::normalizeSubsection converts raw CAI API fields to the cai_* row shape, scoped to the parent section code'],
                ['id' => 'F9-20', 'descrizione' => 'normalizeSubsection ripiega su lastyearMembershipsCount se currentMemberships è assente', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php::normalizeSubsection falls back to lastyearMembershipsCount when currentMemberships is absent'],
            ],
        ],
        [
            'titolo' => 'CaiApiClient con retry per l\'API pubblica CAI (US-905)',
            'test' => [
                ['id' => 'F9-21', 'descrizione' => 'fetchNationalSections restituisce l\'array JSON decodificato dall\'endpoint sections-list-simple', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php::fetchNationalSections returns the decoded JSON array from the sections-list-simple endpoint'],
                ['id' => 'F9-22', 'descrizione' => 'fetchSubsections restituisce l\'array JSON decodificato dall\'endpoint per-sezione', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php::fetchSubsections returns the decoded JSON array from the per-section endpoint'],
                ['id' => 'F9-23', 'descrizione' => 'fetchNationalSections ritenta fino a 3 volte su un fallimento di connessione, poi lancia un\'eccezione', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php::fetchNationalSections retries up to 3 times on connection failure then throws'],
                ['id' => 'F9-24', 'descrizione' => 'fetchNationalSections ha successo se un tentativo successivo recupera', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php::fetchNationalSections succeeds if a later attempt recovers'],
            ],
        ],
        [
            'titolo' => 'SyncCaiSectionAndSubsections — sincronizzazione live condivisa sezione+sottosezioni (US-906)',
            'test' => [
                ['id' => 'F9-25', 'descrizione' => 'run crea una nuova CaiSection con cai_last_synced_at valorizzato', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php::run creates a new CaiSection with cai_last_synced_at set'],
                ['id' => 'F9-26', 'descrizione' => 'run aggiorna una CaiSection esistente quando un campo è davvero cambiato, e aggiorna sempre cai_last_synced_at', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php::run updates an existing CaiSection when a field actually changed, and always bumps cai_last_synced_at'],
                ['id' => 'F9-27', 'descrizione' => 'run conta una sezione come invariata quando nulla è davvero cambiato, ma aggiorna comunque cai_last_synced_at', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php::run counts a section as skipped when nothing actually changed, but still bumps cai_last_synced_at'],
                ['id' => 'F9-28', 'descrizione' => 'run crea le sottosezioni recuperate dall\'endpoint per-sezione', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php::run creates subsections fetched from the per-section API endpoint'],
                ['id' => 'F9-29', 'descrizione' => 'run non scrive nulla in modalità dry-run', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php::run does not write anything in dry-run mode'],
                ['id' => 'F9-30', 'descrizione' => 'run associa l\'email di una sezione a un utente esistente, senza distinguere maiuscole/minuscole', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php::run matches a section email to an existing user, case-insensitively'],
            ],
        ],
        [
            'titolo' => 'ScrapeCaiSection — sincronizzazione live di una sola sezione (US-907)',
            'test' => [
                ['id' => 'F9-31', 'descrizione' => 'run recupera l\'elenco nazionale, isola la sezione richiesta e la sincronizza', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/ScrapeCaiSectionTest.php::run fetches the national list, isolates the requested section and syncs it'],
                ['id' => 'F9-32', 'descrizione' => 'run recupera le sottosezioni solo della sezione richiesta, mai delle altre sezioni dell\'elenco nazionale', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/ScrapeCaiSectionTest.php::run only fetches subsections for the requested section, never for other sections in the national list'],
                ['id' => 'F9-33', 'descrizione' => 'run lancia un\'eccezione se il codice sezione richiesto non è nell\'elenco nazionale', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/ScrapeCaiSectionTest.php::run throws when the requested section code is not present in the national list'],
            ],
        ],
        [
            'titolo' => 'Bottone "Sincronizza dati CAI" sulla dashboard cliente Sezione (US-908, Storia 4)',
            'test' => [
                ['id' => 'F9-34', 'descrizione' => 'Il bottone è visibile solo per un cliente Sezione con una CaiSection collegata', 'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the sync cai data action is visible only for a sezione customer with a linked cai section'],
                ['id' => 'F9-35', 'descrizione' => 'Il bottone sincronizza dal vivo solo la sezione del cliente autenticato, tramite l\'API pubblica CAI', 'test_automatico' => "tests/Feature/Filament/Pages/CustomerDashboardTest.php::the sync cai data action live-scrapes only the current customer\\'s own section from the CAI API"],
            ],
        ],
        [
            'titolo' => 'Data ultimo aggiornamento CAI/RUNTS visibile nell\'Infolist condiviso (US-909/US-927)',
            'test' => [
                ['id' => 'F9-36', 'descrizione' => 'La pagina sezione mostra la data dell\'ultimo aggiornamento dal vivo (CAI), o un segnaposto "mai sincronizzato"', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::the section detail page shows the last live-sync timestamp, or a "never synced" placeholder'],
                ['id' => 'F9-37', 'descrizione' => 'La pagina sezione mostra la data dell\'ultimo aggiornamento dal vivo RUNTS, o un segnaposto "mai sincronizzato"', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::the section detail page shows the last RUNTS live-sync timestamp, or a "never synced" placeholder'],
                ['id' => 'F9-38', 'descrizione' => 'cai_runts_registrations ha una colonna runts_last_synced_at nullable, mass-assignable e castata a datetime', 'test_automatico' => 'tests/Feature/Database/CaiRuntsRegistrationLastSyncedAtTest.php::cai_runts_registrations has a nullable runts_last_synced_at column, mass-assignable and cast to datetime'],
            ],
        ],
        [
            'titolo' => 'cai:sync-national — refresh mensile schedulato dietro feature flag (US-910)',
            'test' => [
                ['id' => 'F9-39', 'descrizione' => 'cai:sync-national sincronizza ogni sezione e sottosezione restituita dall\'API nazionale', 'test_automatico' => 'tests/Feature/Console/CaiSyncNationalCommandTest.php::cai:sync-national syncs every section and subsection returned by the national API'],
                ['id' => 'F9-40', 'descrizione' => 'cai:sync-national --dry-run non scrive nulla', 'test_automatico' => 'tests/Feature/Console/CaiSyncNationalCommandTest.php::cai:sync-national --dry-run does not write anything'],
                ['id' => 'F9-41', 'descrizione' => 'cai:sync-national prosegue oltre una sezione che fallisce la sincronizzazione', 'test_automatico' => 'tests/Feature/Console/CaiSyncNationalCommandTest.php::cai:sync-national continues past a section that fails to sync'],
            ],
        ],
        [
            'titolo' => 'Servizio Python cai-runts-scraper — scaffold, endpoint /health, porting scraper/analyzer (US-912/US-913)',
            'test' => [
                ['id' => 'F9-42', 'descrizione' => 'L\'endpoint /health del servizio risponde ok', 'test_automatico' => 'cai-runts-scraper/tests/test_main.py::def test_health_returns_ok'],
                ['id' => 'F9-43', 'descrizione' => 'run_scraper è una funzione asincrona con i parametri attesi (porting dal prototipo)', 'test_automatico' => 'cai-runts-scraper/tests/test_ported_modules.py::def test_run_scraper_is_an_async_function_with_expected_parameters'],
                ['id' => 'F9-44', 'descrizione' => 'classify_codice_pratica mappa correttamente i codici pratica noti', 'test_automatico' => 'cai-runts-scraper/tests/test_ported_modules.py::def test_classify_codice_pratica_maps_known_codes'],
                ['id' => 'F9-45', 'descrizione' => 'extract_bilancio_pdf è una funzione sincrona con i parametri attesi', 'test_automatico' => 'cai-runts-scraper/tests/test_ported_modules.py::def test_extract_bilancio_pdf_is_a_sync_function_with_expected_parameters'],
            ],
        ],
        [
            'titolo' => 'POST /scrape/runts-entity — ricerca completa di un ente su RUNTS (US-914/US-915/US-916)',
            'test' => [
                ['id' => 'F9-46', 'descrizione' => 'Restituisce found:false quando la ricerca non produce risultati', 'test_automatico' => 'cai-runts-scraper/tests/test_scrape_runts_entity.py::def test_scrape_runts_entity_returns_found_false_when_no_results'],
                ['id' => 'F9-47', 'descrizione' => 'Restituisce metadati, cariche sociali e documenti quando l\'ente è trovato', 'test_automatico' => 'cai-runts-scraper/tests/test_scrape_runts_entity.py::def test_scrape_runts_entity_returns_metadata_board_members_and_documents'],
                ['id' => 'F9-48', 'descrizione' => 'Un errore dello scraper diventa un 502 esplicito, mai un errore generico', 'test_automatico' => 'cai-runts-scraper/tests/test_scrape_runts_entity.py::def test_scrape_runts_entity_returns_502_when_scraper_raises'],
            ],
        ],
        [
            'titolo' => 'POST /analyze/bilancio — estrazione delle cifre finanziarie da un PDF (US-917)',
            'test' => [
                ['id' => 'F9-49', 'descrizione' => 'Restituisce i campi finanziari estratti da un bilancio leggibile', 'test_automatico' => 'cai-runts-scraper/tests/test_analyze_bilancio.py::def test_analyze_bilancio_returns_financial_fields'],
                ['id' => 'F9-50', 'descrizione' => 'Non fallisce mai, nemmeno su un PDF illeggibile (campi null invece di un errore)', 'test_automatico' => 'cai-runts-scraper/tests/test_analyze_bilancio.py::def test_analyze_bilancio_never_fails_even_on_unreadable_pdf'],
            ],
        ],
        [
            'titolo' => 'Analizzatore bilanci — correzioni dei pattern su layout reali RUNTS',
            'test' => [
                ['id' => 'F9-51', 'descrizione' => 'Il totale oneri combacia anche a cavallo di un a-capo, su un layout a due colonne', 'test_automatico' => 'cai-runts-scraper/tests/test_analyzer_patterns.py::def test_totale_oneri_matches_across_the_line_break_in_a_two_column_layout'],
                ['id' => 'F9-52', 'descrizione' => 'Il totale proventi combacia sul terzo numero della riga valore', 'test_automatico' => 'cai-runts-scraper/tests/test_analyzer_patterns.py::def test_totale_proventi_matches_the_third_number_on_the_value_line'],
                ['id' => 'F9-53', 'descrizione' => 'Le imposte combaciano quando un simbolo di euro separa l\'etichetta dal valore', 'test_automatico' => 'cai-runts-scraper/tests/test_analyzer_patterns.py::def test_imposte_matches_when_a_euro_sign_separates_the_label_from_the_value'],
                ['id' => 'F9-54', 'descrizione' => 'I campi già riconosciuti prima di questo fix continuano a combaciare (nessuna regressione)', 'test_automatico' => 'cai-runts-scraper/tests/test_analyzer_patterns.py::def test_risultato_fields_already_matched_before_this_fix_and_still_do'],
                ['id' => 'F9-55', 'descrizione' => 'Il risultato d\'esercizio riconosce un trattino come valore zero dell\'anno corrente, invece di saltare all\'anno successivo', 'test_automatico' => 'cai-runts-scraper/tests/test_analyzer_patterns.py::def test_risultato_esercizio_recognizes_a_dash_as_the_current_year_value_instead_of_skipping_to_the_next_year'],
                ['id' => 'F9-56', 'descrizione' => 'parse_italian_number tratta un trattino isolato come zero', 'test_automatico' => 'cai-runts-scraper/tests/test_analyzer_patterns.py::def test_parse_italian_number_treats_a_lone_dash_as_zero'],
            ],
        ],
        [
            'titolo' => 'Wiring docker-compose + verifica reale contro RUNTS (US-918)',
            'test' => [
                ['id' => 'F9-57', 'descrizione' => 'Il servizio cai-runts-scraper è raggiungibile via rete Docker Compose interna (verifica manuale già eseguita in sviluppo, vedi progress.txt)', 'test_automatico' => 'cai-runts-scraper/tests/test_main.py::def test_health_returns_ok'],
            ],
        ],
        [
            'titolo' => 'CaiRuntsRegistrationFieldMapper / CaiFinancialStatementFieldMapper condivisi (US-920/US-921)',
            'test' => [
                ['id' => 'F9-58', 'descrizione' => 'mapRegistration mappa una riga sugli attributi di CaiRuntsRegistration', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php::mapRegistration maps a row to CaiRuntsRegistration attributes'],
                ['id' => 'F9-59', 'descrizione' => 'mapRegistration legge lat/lon quando presenti (fonte datapack)', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php::mapRegistration reads lat/lon when present (datapack source)'],
                ['id' => 'F9-60', 'descrizione' => 'mapBoardMember concatena nome+cognome in full_name e interpreta le date', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php::mapBoardMember concatenates nome+cognome into full_name and parses dates'],
                ['id' => 'F9-61', 'descrizione' => 'mapBoardMember tollera nome/cognome mancanti, producendo un full_name null', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php::mapBoardMember tolerates missing nome/cognome and produces a null full_name'],
                ['id' => 'F9-62', 'descrizione' => 'mapFinancialStatement mappa i campi dell\'analizzatore (nomi italiani) sulle colonne di CaiFinancialStatement', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiFinancialStatementFieldMapperTest.php::mapFinancialStatement maps the Italian-named analyzer fields to CaiFinancialStatement columns'],
            ],
        ],
        [
            'titolo' => 'CaiRuntsScraperClient + configurazione (US-922)',
            'test' => [
                ['id' => 'F9-63', 'descrizione' => 'scrapeEntity invia codice_fiscale come query parameter e restituisce il JSON decodificato', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php::scrapeEntity sends codice_fiscale as a query parameter and returns the decoded JSON'],
                ['id' => 'F9-64', 'descrizione' => 'analyzeBilancio invia il PDF come file multipart e restituisce il JSON decodificato', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php::analyzeBilancio sends the PDF as a multipart file upload and returns the decoded JSON'],
                ['id' => 'F9-65', 'descrizione' => 'checkEntityExists invia codice_fiscale come query parameter e restituisce il flag found', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php::checkEntityExists sends codice_fiscale as a query parameter and returns the found flag'],
                ['id' => 'F9-66', 'descrizione' => 'checkEntityExists restituisce false quando il servizio riporta found:false', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php::checkEntityExists returns false when the service reports found: false'],
                ['id' => 'F9-67', 'descrizione' => 'checkEntityExists accetta un timeout esplicito, altrimenti usa quello di configurazione', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php::checkEntityExists accepts an explicit timeout override, falling back to config when omitted'],
            ],
        ],
        [
            'titolo' => 'SyncCaiRuntsRegistration — metadati, cariche sociali e documenti di bilancio (US-923/US-924/US-925)',
            'test' => [
                ['id' => 'F9-68', 'descrizione' => 'run restituisce un esito "non trovato" e non scrive nulla quando lo scraper riporta found:false', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php::run returns a not-found result and writes nothing when the scraper reports found: false'],
                ['id' => 'F9-69', 'descrizione' => 'run restituisce "non trovato" senza chiamare lo scraper quando la sezione non ha un codice fiscale', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php::run returns a not-found result without calling the scraper when the section has no tax_code'],
                ['id' => 'F9-70', 'descrizione' => 'run crea una nuova CaiRuntsRegistration e le sue cariche sociali, aggiornando runts_last_synced_at', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php::run creates a new CaiRuntsRegistration and its board members, and bumps runts_last_synced_at'],
                ['id' => 'F9-71', 'descrizione' => 'run aggiorna una CaiRuntsRegistration esistente, aggiornando sempre runts_last_synced_at', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php::run updates an existing CaiRuntsRegistration and always bumps runts_last_synced_at'],
                ['id' => 'F9-72', 'descrizione' => 'run scarica e salva un nuovo documento, inviando all\'analisi solo i bilanci di esercizio', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php::run downloads and stores a new document, and dispatches analysis only for bilancio_esercizio'],
                ['id' => 'F9-73', 'descrizione' => 'run non riscarica né rimette in coda un documento già esistente', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php::run does not re-download or re-queue a document that already exists'],
            ],
        ],
        [
            'titolo' => 'AnalyzeCaiFinancialStatementDocument — job asincrono di analisi bilanci su coda Horizon dedicata (US-924)',
            'test' => [
                ['id' => 'F9-74', 'descrizione' => 'handle scarica il PDF salvato, lo analizza e crea un CaiFinancialStatement', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php::handle downloads the stored PDF, analyzes it, and creates a CaiFinancialStatement'],
                ['id' => 'F9-75', 'descrizione' => 'handle aggiorna un CaiFinancialStatement esistente per la stessa (registrazione, anno)', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php::handle updates an existing CaiFinancialStatement for the same registration+year'],
                ['id' => 'F9-76', 'descrizione' => 'handle non sovrascrive mai un campo già valorizzato con null (un secondo documento con struttura diversa non deve cancellare dati buoni)', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php::handle never overwrites an already-populated field with null (a second, structurally different document for the same year must not clobber good data)'],
                ['id' => 'F9-77', 'descrizione' => 'handle non fa nulla quando il CaiDocument non esiste più', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php::handle does nothing when the CaiDocument no longer exists'],
                ['id' => 'F9-78', 'descrizione' => 'handle non fa nulla quando il documento non ha un anno estraibile', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php::handle does nothing when the document has no extractable year'],
                ['id' => 'F9-79', 'descrizione' => 'handle marca il documento come Extracted, con estratto testo ed esito OCR, quando trova almeno un campo', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php::handle marks the document itself as Extracted with the raw text excerpt and OCR flag when at least one field is found'],
                ['id' => 'F9-80', 'descrizione' => 'handle marca il documento come NoDataExtracted quando nessun campo finanziario viene estratto', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php::handle marks the document as NoDataExtracted when every financial field comes back null'],
            ],
        ],
        [
            'titolo' => 'Bottone "Sincronizza dati RUNTS", separato da "Sincronizza dati CAI" (US-926, Storie 6/8)',
            'test' => [
                ['id' => 'F9-81', 'descrizione' => 'Il bottone è visibile solo per un cliente Sezione con una CaiSection collegata', 'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the sync runts data action is visible only for a sezione customer with a linked cai section'],
                ['id' => 'F9-82', 'descrizione' => 'Il bottone sincronizza dal vivo la sezione del cliente tramite il servizio cai-runts-scraper', 'test_automatico' => "tests/Feature/Filament/Pages/CustomerDashboardTest.php::the sync runts data action live-scrapes the current customer\\'s section via the cai-runts-scraper service"],
                ['id' => 'F9-83', 'descrizione' => 'Il bottone mostra una notifica informativa quando non viene trovata alcuna registrazione RUNTS', 'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the sync runts data action shows an informative notification when no RUNTS registration is found'],
            ],
        ],
        [
            'titolo' => 'Tab "Differenze" — confronto fra dati CaiSection e registrazione RUNTS (Storia 7)',
            'test' => [
                ['id' => 'F9-84', 'descrizione' => 'Nessuna riga quando la sezione non ha una registrazione RUNTS collegata', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php::returns no rows when the section has no linked runts registration'],
                ['id' => 'F9-85', 'descrizione' => 'Un campo è marcato "Uguale" quando i due lati coincidono (a meno di spazi)', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php::flags a field as equal when both sides carry the same trimmed value'],
                ['id' => 'F9-86', 'descrizione' => 'Un campo è marcato "Diverso" quando i due lati differiscono davvero', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php::flags a field as different when the two sides genuinely differ'],
                ['id' => 'F9-87', 'descrizione' => 'Un campo è marcato "non applicabile" quando entrambi i lati sono vuoti', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php::flags a field as not applicable when both sides are empty'],
                ['id' => 'F9-88', 'descrizione' => 'Un blocco di righe per ogni registrazione collegata, etichettato col nome della registrazione', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php::produces one block of rows per linked registration, labelled by registration name'],
                ['id' => 'F9-89', 'descrizione' => 'L\'indirizzo strutturato RUNTS viene composto in un\'unica stringa comparabile', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php::composes the RUNTS structured address into a single comparable string'],
                ['id' => 'F9-90', 'descrizione' => 'Il tab Differenze mostra il confronto fra CaiSection e la sua registrazione RUNTS', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::the differences tab shows the comparison between CaiSection and its RUNTS registration'],
                ['id' => 'F9-91', 'descrizione' => 'Il tab Differenze mostra uno stato vuoto esplicito quando non c\'è alcuna registrazione RUNTS collegata', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::the differences tab shows an explicit empty state when no RUNTS registration is linked'],
            ],
        ],
        [
            'titolo' => 'Link diretto alla scheda ufficiale CAI, indipendente dal campo website (Storia 5)',
            'test' => [
                ['id' => 'F9-92', 'descrizione' => 'Il tab Anagrafica CAI collega alla scheda ufficiale su cai.it, costruita dal codice_cai, a prescindere dal campo website', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::the CAI directory tab links to the official cai.it section page, built from codice_cai, regardless of the website field'],
            ],
        ],
        [
            'titolo' => 'Orari di apertura e avvisi mostrati come testo formattato, mai HTML grezzo (fix, Storia 3)',
            'test' => [
                ['id' => 'F9-93', 'descrizione' => 'Lo stile inline viene rimosso mantenendo il testo di uno span', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Support/CaiRichTextSanitizerTest.php::strips inline styling but keeps the text content of a span'],
                ['id' => 'F9-94', 'descrizione' => 'Un tag script e il suo contenuto vengono rimossi interamente', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Support/CaiRichTextSanitizerTest.php::strips a script tag and its content entirely'],
                ['id' => 'F9-95', 'descrizione' => 'Gli attributi event handler vengono rimossi', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Support/CaiRichTextSanitizerTest.php::strips event handler attributes'],
                ['id' => 'F9-96', 'descrizione' => 'Orari di apertura e avvisi sono mostrati come testo formattato, mai come sorgente HTML grezzo', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::office hours and notices are shown as formatted text, not raw HTML source'],
            ],
        ],
        [
            'titolo' => 'Bilanci e allegati ordinati dal più recente al più antico (Storie 10/11)',
            'test' => [
                ['id' => 'F9-97', 'descrizione' => 'Il tab Bilanci elenca gli anni dal più recente al più antico', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::the financial statements tab lists years from most recent to oldest'],
                ['id' => 'F9-98', 'descrizione' => 'Il tab Allegati elenca i documenti dall\'anno più recente al più antico', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::the documents tab lists attachments from most recent to oldest year'],
            ],
        ],
        [
            'titolo' => 'Verifica leggera di presenza RUNTS — cai:check-runts-presence dietro feature flag',
            'test' => [
                ['id' => 'F9-99', 'descrizione' => 'L\'opzione --timeout ha un default di 10 secondi', 'test_automatico' => 'tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php::the --timeout option defaults to 10 seconds'],
                ['id' => 'F9-100', 'descrizione' => 'Un --timeout personalizzato è accettato senza rompere un giro normale', 'test_automatico' => 'tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php::a custom --timeout is accepted without breaking a normal run'],
                ['id' => 'F9-101', 'descrizione' => 'Il comando scrive runts_presence_status e runts_presence_checked_at per ogni sezione con codice fiscale', 'test_automatico' => 'tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php::cai:check-runts-presence writes runts_presence_status and runts_presence_checked_at for every section with a tax_code'],
                ['id' => 'F9-102', 'descrizione' => 'Un timeout della verifica scrive lo stato Timeout (non null), invece di lasciarlo non scritto', 'test_automatico' => 'tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php::cai:check-runts-presence writes a Timeout status (not null) when the check times out, instead of leaving it unwritten'],
                ['id' => 'F9-103', 'descrizione' => '--dry-run non scrive nulla, nemmeno in caso di timeout', 'test_automatico' => 'tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php::cai:check-runts-presence --dry-run does not write anything, not even on timeout'],
                ['id' => 'F9-104', 'descrizione' => 'Il comando prosegue oltre una sezione la cui verifica fallisce con un errore diverso dal timeout, lasciandone invariato lo stato', 'test_automatico' => 'tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php::cai:check-runts-presence continues past a section whose check fails with a non-timeout error, leaving its status untouched'],
                ['id' => 'F9-105', 'descrizione' => 'Il comando salta le sezioni senza codice fiscale né partita IVA, senza mai chiamare il servizio per loro', 'test_automatico' => 'tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php::cai:check-runts-presence skips sections with neither tax_code nor vat_number, never calling the service for them'],
                ['id' => 'F9-106', 'descrizione' => 'Il comando usa la partita IVA quando il codice fiscale manca', 'test_automatico' => 'tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php::cai:check-runts-presence falls back to vat_number when tax_code is missing'],
                ['id' => 'F9-107', 'descrizione' => 'Il comando preferisce il codice fiscale alla partita IVA quando sono presenti entrambi', 'test_automatico' => 'tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php::cai:check-runts-presence prefers tax_code over vat_number when both are present'],
                ['id' => 'F9-108', 'descrizione' => 'L\'endpoint /search/runts-entity restituisce found:true quando l\'ente è trovato', 'test_automatico' => 'cai-runts-scraper/tests/test_search_runts_entity.py::def test_search_runts_entity_returns_found_true'],
                ['id' => 'F9-109', 'descrizione' => 'L\'endpoint /search/runts-entity restituisce found:false quando l\'ente non è trovato', 'test_automatico' => 'cai-runts-scraper/tests/test_search_runts_entity.py::def test_search_runts_entity_returns_found_false'],
                ['id' => 'F9-110', 'descrizione' => 'L\'endpoint /search/runts-entity restituisce 502 quando la verifica lancia un\'eccezione', 'test_automatico' => 'cai-runts-scraper/tests/test_search_runts_entity.py::def test_search_runts_entity_returns_502_when_check_raises'],
            ],
        ],
        [
            'titolo' => 'Colonna e filtro presenza RUNTS nell\'Anagrafica Sezioni, stato a 3 valori',
            'test' => [
                ['id' => 'F9-111', 'descrizione' => 'La tabella è filtrabile per stato di presenza RUNTS', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::the table is filterable by RUNTS presence status'],
                ['id' => 'F9-112', 'descrizione' => 'Ogni caso dell\'enum stato presenza RUNTS ha un\'etichetta e un colore', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Enums/CaiRuntsPresenceStatusTest.php::every case has a label and a color'],
                ['id' => 'F9-113', 'descrizione' => 'I tre casi attesi esistono con i valori stringa attesi', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Enums/CaiRuntsPresenceStatusTest.php::the three expected cases exist with the expected string values'],
                ['id' => 'F9-114', 'descrizione' => 'cai_sections ha le colonne runts_presence_status e runts_presence_checked_at, entrambe nullable, mass-assignable e castate', 'test_automatico' => 'tests/Feature/Database/CaiSectionRuntsPresenceColumnsTest.php::cai_sections has runts_presence_status and runts_presence_checked_at columns, both nullable, mass-assignable and cast'],
            ],
        ],
        [
            'titolo' => 'cai:sync-runts-section — sincronizzare una singola sezione da riga di comando',
            'test' => [
                ['id' => 'F9-115', 'descrizione' => 'Il comando sincronizza una singola sezione per codice_cai e ne riporta il successo', 'test_automatico' => 'tests/Feature/Console/CaiSyncRuntsSectionCommandTest.php::cai:sync-runts-section syncs a single section by codice_cai and reports success'],
                ['id' => 'F9-116', 'descrizione' => 'Il comando segnala quando non viene trovata alcuna registrazione RUNTS', 'test_automatico' => 'tests/Feature/Console/CaiSyncRuntsSectionCommandTest.php::cai:sync-runts-section reports when no RUNTS registration is found'],
                ['id' => 'F9-117', 'descrizione' => 'Il comando fallisce esplicitamente quando il codice_cai non esiste', 'test_automatico' => 'tests/Feature/Console/CaiSyncRuntsSectionCommandTest.php::cai:sync-runts-section fails explicitly when the codice_cai does not exist'],
                ['id' => 'F9-118', 'descrizione' => 'Il comando segnala esplicitamente un errore quando lo scrape fallisce, senza un\'eccezione non gestita', 'test_automatico' => 'tests/Feature/Console/CaiSyncRuntsSectionCommandTest.php::cai:sync-runts-section reports an error explicitly when the scrape fails, without an unhandled exception'],
            ],
        ],
        [
            'titolo' => 'cai:sync-runts-all — sincronizzare tutte le sezioni con retry, --limit e --codes',
            'test' => [
                ['id' => 'F9-119', 'descrizione' => 'Il comando sincronizza ogni sezione con codice fiscale e riporta un riepilogo', 'test_automatico' => 'tests/Feature/Console/CaiSyncRuntsAllCommandTest.php::cai:sync-runts-all syncs every section with a tax_code and reports a summary'],
                ['id' => 'F9-120', 'descrizione' => 'Il comando salta le sezioni senza codice fiscale, senza mai chiamare lo scraper per loro', 'test_automatico' => 'tests/Feature/Console/CaiSyncRuntsAllCommandTest.php::cai:sync-runts-all skips sections without a tax_code, never calling the scraper for them'],
                ['id' => 'F9-121', 'descrizione' => 'Il comando salta una sezione col codice fiscale palesemente non valido (es. "0" o "."), senza chiamare lo scraper', 'test_automatico' => 'tests/Feature/Console/CaiSyncRuntsAllCommandTest.php::cai:sync-runts-all skips a section whose tax_code is obviously invalid (e.g. "0" or "."), never calling the scraper'],
                ['id' => 'F9-122', 'descrizione' => 'Il comando prosegue con la sezione successiva quando una fallisce, senza fermare il giro', 'test_automatico' => 'tests/Feature/Console/CaiSyncRuntsAllCommandTest.php::cai:sync-runts-all continues with the next section when one fails, without stopping the batch'],
                ['id' => 'F9-123', 'descrizione' => 'Il comando ritenta automaticamente una sezione fallita una volta, e la conta come sincronizzata se il retry recupera', 'test_automatico' => 'tests/Feature/Console/CaiSyncRuntsAllCommandTest.php::cai:sync-runts-all automatically retries a section that failed once, and counts it as synced if the retry recovers'],
                ['id' => 'F9-124', 'descrizione' => 'Il comando conta una sezione "non trovata" separatamente da una sincronizzata', 'test_automatico' => 'tests/Feature/Console/CaiSyncRuntsAllCommandTest.php::cai:sync-runts-all counts a not-found section separately from a synced one'],
                ['id' => 'F9-125', 'descrizione' => 'L\'opzione --limit elabora solo le prime N sezioni', 'test_automatico' => 'tests/Feature/Console/CaiSyncRuntsAllCommandTest.php::cai:sync-runts-all --limit processes only the first N sections'],
                ['id' => 'F9-126', 'descrizione' => 'L\'opzione --codes limita la sincronizzazione all\'elenco di codice_cai indicato, separati da virgola', 'test_automatico' => 'tests/Feature/Console/CaiSyncRuntsAllCommandTest.php::cai:sync-runts-all --codes restricts the sync to the given comma-separated codice_cai'],
            ],
        ],
        [
            'titolo' => 'Pagina admin "Bilanci non interpretati" per rivedere il parser',
            'test' => [
                ['id' => 'F9-127', 'descrizione' => 'Un utente senza cai-directory.review-unparsed-documents non accede alla pagina', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiUnparsedFinancialStatementsTest.php::a user without cai-directory.review-unparsed-documents is denied access to the page'],
                ['id' => 'F9-128', 'descrizione' => 'Un utente con cai-directory.review-unparsed-documents accede alla pagina', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiUnparsedFinancialStatementsTest.php::a user with cai-directory.review-unparsed-documents can access the page'],
                ['id' => 'F9-129', 'descrizione' => 'La tabella elenca solo i bilanci di esercizio senza dati estratti, mostrando sezione, anno e fonte RUNTS fissa', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiUnparsedFinancialStatementsTest.php::the table lists only bilancio_esercizio documents with no data extracted, showing section, year and a fixed RUNTS source'],
                ['id' => 'F9-130', 'descrizione' => 'Le azioni di download e visualizzazione testo grezzo sono visibili per un documento non interpretato', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiUnparsedFinancialStatementsTest.php::the download and view-raw-text actions are visible for an unparsed document'],
            ],
        ],
        [
            'titolo' => 'Import datapack — estensioni: scope su una sola sezione, skip campi RUNTS',
            'test' => [
                ['id' => 'F9-131', 'descrizione' => 'Un import scoped (onlyCaiSectionCode) importa solo la sezione richiesta, le sue sottosezioni e i suoi dati RUNTS', 'test_automatico' => 'tests/Feature/Console/CaiImportDatapackCommandTest.php::a scoped import (onlyCaiSectionCode) imports only the requested section, its subsections and its runts data'],
                ['id' => 'F9-132', 'descrizione' => 'skipSectionFields importa solo le tabelle di fonte RUNTS, lasciando invariata una CaiSection/CaiSubsection già importata', 'test_automatico' => 'tests/Feature/Console/CaiImportDatapackCommandTest.php::skipSectionFields imports only the RUNTS-sourced tables, leaving an already-imported CaiSection/CaiSubsection untouched'],
            ],
        ],
        [
            'titolo' => 'Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato',
            'test' => [
                ['id' => 'F9-133', 'descrizione' => 'La generazione trova una sezione nonostante differenze di prefisso/forma giuridica/spaziatura, riportando entrambi i codici', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiTaxCodeFallbackGeneratorTest.php::generate matches a section despite prefix/legal-form/spacing differences and reports both codes'],
                ['id' => 'F9-134', 'descrizione' => 'Le righe senza corrispondenza sono riportate, mai scartate in silenzio', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiTaxCodeFallbackGeneratorTest.php::generate reports rows without a matching section instead of dropping them silently'],
                ['id' => 'F9-135', 'descrizione' => 'Una riga senza CF né PIVA viene saltata', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiTaxCodeFallbackGeneratorTest.php::generate skips a row with neither CF nor PIVA'],
                ['id' => 'F9-136', 'descrizione' => 'Due sezioni che normalizzano allo stesso nome non vengono mai fatte corrispondere', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiTaxCodeFallbackGeneratorTest.php::generate never matches two sections that normalize to the same name'],
                ['id' => 'F9-137', 'descrizione' => 'Il comando cai:generate-tax-code-fallback scrive le corrispondenze trovate in JSON e riporta le righe senza corrispondenza', 'test_automatico' => 'tests/Feature/Console/CaiGenerateTaxCodeFallbackCommandTest.php::cai:generate-tax-code-fallback writes the matched entries as JSON and reports unmatched rows'],
                ['id' => 'F9-138', 'descrizione' => 'Il comando fallisce esplicitamente quando il file Excel sorgente non esiste', 'test_automatico' => 'tests/Feature/Console/CaiGenerateTaxCodeFallbackCommandTest.php::cai:generate-tax-code-fallback fails when the source Excel file does not exist'],
                ['id' => 'F9-139', 'descrizione' => 'Il riempimento completa codice fiscale e partita IVA mancanti dal fallback', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/FillCaiSectionFiscalCodesFromFallbackTest.php::run fills a missing tax_code and vat_number from the fallback'],
                ['id' => 'F9-140', 'descrizione' => 'Il riempimento non sovrascrive mai un valore già presente, anche se il fallback riporta un valore diverso', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/FillCaiSectionFiscalCodesFromFallbackTest.php::run never overwrites a value already present, even if the fallback disagrees'],
                ['id' => 'F9-141', 'descrizione' => 'Una sezione senza dati e senza una corrispondenza nel fallback viene saltata', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/FillCaiSectionFiscalCodesFromFallbackTest.php::run skips a section missing data with no corresponding fallback entry'],
                ['id' => 'F9-142', 'descrizione' => 'In modalità dry-run il riempimento riporta cosa cambierebbe, senza scrivere', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/FillCaiSectionFiscalCodesFromFallbackTest.php::run in dry-run mode reports what would change without writing'],
                ['id' => 'F9-143', 'descrizione' => 'Una sezione che ha già sia codice fiscale sia partita IVA non viene mai selezionata per il riempimento', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/FillCaiSectionFiscalCodesFromFallbackTest.php::run never selects a section that already has both tax_code and vat_number'],
                ['id' => 'F9-144', 'descrizione' => 'Il comando cai:fill-tax-codes-from-fallback completa i campi mancanti dal file configurato e riporta un riepilogo', 'test_automatico' => 'tests/Feature/Console/CaiFillTaxCodesFromFallbackCommandTest.php::cai:fill-tax-codes-from-fallback fills missing fields from the configured fallback file and reports a summary'],
                ['id' => 'F9-145', 'descrizione' => 'cai:sync-runts-all completa un codice fiscale mancante dal fallback prima di selezionare le sezioni da sincronizzare', 'test_automatico' => 'tests/Feature/Console/CaiSyncRuntsAllCommandTest.php::cai:sync-runts-all fills a missing tax_code from the fallback before selecting sections to sync'],
                ['id' => 'F9-146', 'descrizione' => 'La tabella dell\'Anagrafica CAI è filtrabile per "CF mancante"', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::the table is filterable by missing tax_code'],
            ],
        ],
        [
            'titolo' => 'Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS',
            'test' => [
                ['id' => 'F9-147', 'descrizione' => 'Ogni caso dell\'enum sorgente documento ha un\'etichetta e un colore', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Enums/CaiDocumentSourceTest.php::every case has a label and a color'],
                ['id' => 'F9-148', 'descrizione' => 'I tre casi attesi (runts/manual/veryfico) esistono con i valori stringa attesi', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Enums/CaiDocumentSourceTest.php::the three expected cases exist with the expected string values'],
                ['id' => 'F9-149', 'descrizione' => 'Ogni caso del vocabolario tipo documento ha un\'etichetta non vuota', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Enums/CaiDocumentTypeTest.php::every case has a non-empty label'],
                ['id' => 'F9-150', 'descrizione' => 'Solo Mod A/B/D e le combinazioni che li contengono attivano l\'analisi finanziaria automatica', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Enums/CaiDocumentTypeTest.php::only Mod A/B/D and the combinations that contain one trigger financial analysis'],
                ['id' => 'F9-151', 'descrizione' => 'Il caso di fallback "Altro" esiste col valore atteso', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Enums/CaiDocumentTypeTest.php::the fallback case exists with the expected value'],
                ['id' => 'F9-152', 'descrizione' => 'Un documento caricato a mano si collega sempre direttamente alla sezione, mai a una registrazione RUNTS', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/UploadCaiDocumentManuallyTest.php::run always attaches the document directly to the section, never to a registration'],
                ['id' => 'F9-153', 'descrizione' => 'Un tipo Mod A/B/D (o una combinazione che li contiene) invia il documento all\'analisi finanziaria automatica', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/UploadCaiDocumentManuallyTest.php::run dispatches the financial-statement analysis job for a Mod A/B/D type or a combination that contains one'],
                ['id' => 'F9-154', 'descrizione' => 'Un tipo narrativo (relazioni, verbali, bilancio sociale, altro) non invia mai all\'analisi automatica', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Actions/UploadCaiDocumentManuallyTest.php::run never dispatches the analysis job for a narrative type (relazioni, verbali, bilancio sociale, altro)'],
                ['id' => 'F9-155', 'descrizione' => 'Una sezione può avere documenti e bilanci collegati direttamente, senza alcuna registrazione RUNTS', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php::a section has many documents and financial statements attached directly, without any runts registration'],
                ['id' => 'F9-156', 'descrizione' => 'L\'analisi crea un CaiFinancialStatement chiave-sezione per un documento senza registrazione', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php::handle creates a CaiFinancialStatement keyed by section for a document with no registration'],
                ['id' => 'F9-157', 'descrizione' => 'L\'analisi aggiorna un CaiFinancialStatement chiave-sezione esistente, senza toccare uno chiave-registrazione per lo stesso anno', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php::handle updates an existing section-keyed CaiFinancialStatement without touching a registration-keyed one for the same year'],
                ['id' => 'F9-158', 'descrizione' => 'L\'azione "Carica documento" è visibile solo con il permesso cai-directory.upload-document', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::the upload document action is only visible with cai-directory.upload-document'],
                ['id' => 'F9-159', 'descrizione' => 'Caricare un documento lo collega sempre direttamente alla sezione, senza un selettore di registrazione nel form', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::uploading a document always attaches it directly to the section, with no registration picker in the form'],
                ['id' => 'F9-160', 'descrizione' => 'Caricare un documento di tipo Mod A invia il job di analisi finanziaria', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::uploading a Mod A document dispatches the financial-statement analysis job'],
                ['id' => 'F9-161', 'descrizione' => 'Un cliente può scaricare un documento collegato direttamente alla propria sezione, senza alcuna registrazione RUNTS', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::a customer can download a document attached directly to their own section, without any runts registration'],
                ['id' => 'F9-162', 'descrizione' => 'Un cliente non può scaricare un documento collegato direttamente a un\'altra sezione', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::a customer cannot download a document attached directly to another cai section'],
                ['id' => 'F9-163', 'descrizione' => 'I tab Bilanci/Allegati uniscono i documenti collegati direttamente alla sezione con quelli collegati via registrazione RUNTS', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php::the documents and financial statements tabs merge documents/bilanci attached directly to the section with those attached via a runts registration'],
            ],
        ],
        [
            'titolo' => 'Menu Gruppo Regionale — voci "GR"/"Sezioni" separate dalla Dashboard cliente',
            'test' => [
                ['id' => 'F9-164', 'descrizione' => 'La card "Sezioni del gruppo regionale" non compare più su questa pagina, nemmeno per un cliente Gruppo Regionale (si è spostata su una voce di navigazione propria)', 'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the regional group sections card is never shown on this page, not even for a gruppo regionale customer (it moved to its own navigation entry)'],
                ['id' => 'F9-165', 'descrizione' => 'Il gruppo di navigazione è "GR" per un cliente Gruppo Regionale, "Area cliente" per qualunque altro tipo cliente', 'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the navigation group is "GR" for a gruppo regionale customer and "Area cliente" for any other customer type'],
                ['id' => 'F9-166', 'descrizione' => 'Un cliente Gruppo Regionale può accedere alla nuova pagina "Sezioni"', 'test_automatico' => 'tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php::a gruppo regionale customer can access the page'],
                ['id' => 'F9-167', 'descrizione' => 'Un cliente Sezione non può accedere alla pagina "Sezioni"', 'test_automatico' => 'tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php::a sezione customer cannot access the page'],
                ['id' => 'F9-168', 'descrizione' => 'Un utente non-cliente non può accedere alla pagina "Sezioni"', 'test_automatico' => 'tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php::a non-customer cannot access the page'],
                ['id' => 'F9-169', 'descrizione' => 'Il gruppo di navigazione della nuova pagina è "Sezioni"', 'test_automatico' => 'tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php::the navigation group is "Sezioni"'],
                ['id' => 'F9-170', 'descrizione' => 'La pagina elenca solo le sezioni della stessa regione, col relativo conteggio di ticket aperti', 'test_automatico' => 'tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php::it lists only sections in the same region, with their open ticket count'],
                ['id' => 'F9-171', 'descrizione' => 'Stato vuoto esplicito quando la regione non ha ancora sezioni classificate', 'test_automatico' => 'tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php::it shows an explicit empty state when the region has no sections yet'],
                ['id' => 'F9-172', 'descrizione' => 'Stato vuoto esplicito quando il Gruppo Regionale non ha una regione valorizzata', 'test_automatico' => 'tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php::it shows an explicit empty state when the group has no region'],
                ['id' => 'F9-173', 'descrizione' => 'La pagina "Sezioni" collega alla pagina di dettaglio sezione', 'test_automatico' => 'tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php::the regional group sections page links to the section detail page'],
            ],
        ],
        [
            'titolo' => 'Checkpoint di fine fase — flusso end-to-end sui tre filoni della Fase 9',
            'test' => [
                ['id' => 'F9-174', 'descrizione' => 'Il flusso completo funziona end-to-end: creazione ticket con Richiesta, sync live CAI/RUNTS di una sezione con estrazione bilancio, fallback CF/PIVA, upload manuale di un documento e menu Gruppo Regionale scoped alla propria regione', 'test_automatico' => 'tests/Feature/EndToEnd/Fase9CheckpointEndToEndTest.php::the full Fase 9 flow works end-to-end: ticket richiesta, live CAI/RUNTS sync with bilancio extraction, CF/PIVA fallback, manual document upload and gruppo regionale menu'],
            ],
        ],
        [
            'titolo' => 'Bilanci Sezioni 2025 nel datapack CAI — documenti manuali importati da cai:import-datapack',
            'test' => [
                ['id' => 'F9-175', 'descrizione' => 'Il nome di un file normalizzato (codice - sezione - etichetta.estensione) viene scomposto nei suoi campi', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/ManualBilancio/ManualBilancioParsingTest.php::ManualBilancioFilenameParser parses a normalized file name'],
                ['id' => 'F9-176', 'descrizione' => 'Un nome file non conforme (.DS_Store, codice non a 7 cifre, etichetta mancante) restituisce null, mai un\'eccezione', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/ManualBilancio/ManualBilancioParsingTest.php::ManualBilancioFilenameParser returns null for non conforming names'],
                ['id' => 'F9-177', 'descrizione' => 'Un\'etichetta nota viene mappata sul tipo documento corretto; il titolo resta quello originale e l\'anno è 2025 (2026 se l\'etichetta lo contiene); ogni etichetta sconosciuta ricade su "Altro"', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/ManualBilancio/ManualBilancioParsingTest.php::ManualBilancioTypeMapper maps labels to type, title and year'],
                ['id' => 'F9-178', 'descrizione' => 'L\'indice Excel della campagna è letto per intestazione di colonna e il codice CAI numerico (9216157.0) è normalizzato a 7 cifre', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/ManualBilancio/CampagnaSezioniIndexReaderTest.php::CampagnaSezioniIndexReader reads columns by header and normalizes the code'],
                ['id' => 'F9-179', 'descrizione' => 'Un file Excel senza il foglio "Sezioni" produce un errore esplicito', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/ManualBilancio/CampagnaSezioniIndexReaderTest.php::CampagnaSezioniIndexReader throws when the Sezioni sheet is missing'],
                ['id' => 'F9-180', 'descrizione' => 'Un file Excel indice mancante produce un errore esplicito', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/ManualBilancio/CampagnaSezioniIndexReaderTest.php::CampagnaSezioniIndexReader throws when the file is missing'],
                ['id' => 'F9-181', 'descrizione' => 'La scansione delle cartelle normalizzate produce le righe attese (con hash sha256) e riporta ogni codice di anomalia di copertura', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/ManualBilancio/ManualBilancioDatapackBuilderTest.php::ManualBilancioDatapackBuilder builds rows and reports every anomaly code'],
                ['id' => 'F9-182', 'descrizione' => 'Senza elenco di sezioni del datapack il controllo di presenza è saltato; una cartella staging mancante è rifiutata', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/ManualBilancio/ManualBilancioDatapackBuilderTest.php::ManualBilancioDatapackBuilder skips the datapack check when codes are null and rejects a missing staging'],
                ['id' => 'F9-183', 'descrizione' => 'cai:build-manual-bilanci-datapack con datapack o cartella staging mancanti fallisce con un messaggio italiano esplicito', 'test_automatico' => 'tests/Feature/Console/CaiBuildManualBilanciDatapackCommandTest.php::missing datapack or staging folder fail with an explicit Italian message'],
                ['id' => 'F9-184', 'descrizione' => 'Il comando scrive la tabella bilanci_manuali nel datapack, è idempotente e non tocca le altre tabelle', 'test_automatico' => 'tests/Feature/Console/CaiBuildManualBilanciDatapackCommandTest.php::build writes bilanci_manuali, is idempotent and leaves other tables untouched'],
                ['id' => 'F9-185', 'descrizione' => 'Con --dry-run il file del datapack non viene modificato', 'test_automatico' => 'tests/Feature/Console/CaiBuildManualBilanciDatapackCommandTest.php::--dry-run does not modify the datapack file'],
                ['id' => 'F9-186', 'descrizione' => 'cai:import-datapack crea i documenti manuali collegati alla sezione e una seconda esecuzione non crea duplicati', 'test_automatico' => 'tests/Feature/Console/CaiManualBilanciImportTest.php::creates manual documents linked to the section, idempotent on the second run'],
                ['id' => 'F9-187', 'descrizione' => 'Una sezione sconosciuta o un file mancante sono saltati (con avviso) senza fermare gli altri documenti', 'test_automatico' => 'tests/Feature/Console/CaiManualBilanciImportTest.php::unknown section is skipped and a missing file is skipped with a warning without stopping the others'],
                ['id' => 'F9-188', 'descrizione' => 'In --dry-run l\'import non scrive nulla ma riporta gli stessi conteggi', 'test_automatico' => 'tests/Feature/Console/CaiManualBilanciImportTest.php::dry-run writes nothing but reports the same counts'],
                ['id' => 'F9-189', 'descrizione' => 'I documenti RUNTS già presenti non vengono toccati dall\'import dei documenti manuali', 'test_automatico' => 'tests/Feature/Console/CaiManualBilanciImportTest.php::pre-existing RUNTS documents are left intact'],
                ['id' => 'F9-190', 'descrizione' => 'Senza --analyze-manual nessuna analisi dei bilanci viene accodata', 'test_automatico' => 'tests/Feature/Console/CaiManualBilanciImportTest.php::without --analyze-manual nothing is queued'],
                ['id' => 'F9-191', 'descrizione' => 'Con --analyze-manual l\'analisi è accodata solo per i documenti appena creati di un tipo analizzabile', 'test_automatico' => 'tests/Feature/Console/CaiManualBilanciImportTest.php::--analyze-manual queues analysis only for newly created documents of an analyzable type, ignoring invalid types'],
            ],
        ],
        [
            'titolo' => 'Menu Anagrafica CAI (sotto-menu ad espansione, Bilanci annidato in Sezioni), Bilancio 2025 e Gruppi regionali (US-940..US-947)',
            'test' => [
                ['id' => 'F9-192', 'descrizione' => 'Un admin vede il gruppo Anagrafica CAI organizzato in sotto-menu ordinati: Sezioni (con Anagrafica sezioni, Mappa sezioni e il sotto-menu annidato Bilanci con Bilanci non interpretati e Bilancio 2025) e Gruppi regionali', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiNavigationTest.php::an admin sees the Anagrafica CAI group organised in ordered sub-menus'],
                ['id' => 'F9-193', 'descrizione' => 'Un utente con solo cai-directory.view vede Sezioni e, nel sotto-menu annidato Bilanci, solo Bilancio 2025', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiNavigationTest.php::a user with only cai-directory.view sees Sezioni and only Bilancio 2025 under the nested Bilanci'],
                ['id' => 'F9-194', 'descrizione' => 'Un utente senza permessi cai-directory non vede il gruppo Anagrafica CAI', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiNavigationTest.php::a user without cai-directory permissions does not see the Anagrafica CAI group'],
                ['id' => 'F9-195', 'descrizione' => 'I documenti sono classificati come conto economico/stato patrimoniale per tipo e titolo', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Support/CaiFinancialDocumentKindTest.php::instance methods classify a document'],
                ['id' => 'F9-196', 'descrizione' => 'Gli scope SQL concordano coi metodi d\'istanza sulle stesse righe', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Support/CaiFinancialDocumentKindTest.php::scopes agree with the instance methods on the same rows'],
                ['id' => 'F9-197', 'descrizione' => 'Il filtro per anno agisce sulla colonna year', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Support/CaiFinancialDocumentKindTest.php::forYear filters on the year column'],
                ['id' => 'F9-198', 'descrizione' => 'I flag per sezione riflettono documenti diretti, via registrazione e bilanci interpretati', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiSectionFinancialYearQueryTest.php::flags reflect direct documents, registration documents and parsed statements'],
                ['id' => 'F9-199', 'descrizione' => 'L\'elenco sezioni per anno è una singola query', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiSectionFinancialYearQueryTest.php::the list is a single query'],
                ['id' => 'F9-200', 'descrizione' => 'Un utente senza cai-directory.view non accede a "Bilancio 2025" (403)', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiFinancialYear2025Test.php::a user without cai-directory.view is denied (403)'],
                ['id' => 'F9-201', 'descrizione' => 'La pagina "Bilancio 2025" elenca tutte le sezioni ordinate per regione e nome', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiFinancialYear2025Test.php::a user with cai-directory.view sees all sections ordered by region then name'],
                ['id' => 'F9-202', 'descrizione' => 'Ogni filtro ternario restringe l\'elenco', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiFinancialYear2025Test.php::each ternary filter narrows the list'],
                ['id' => 'F9-203', 'descrizione' => 'I filtri Regione e "conto economico interpretato" si combinano in AND', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiFinancialYear2025Test.php::region and income-parsed filters combine with AND'],
                ['id' => 'F9-204', 'descrizione' => 'Il comando accoda solo i documenti CE/SP dell\'anno mai analizzati', 'test_automatico' => 'tests/Feature/Console/CaiAnalyzeFinancialDocumentsCommandTest.php::queues only never-analyzed income statement and balance sheet documents of the year'],
                ['id' => 'F9-205', 'descrizione' => 'Con --dry-run il comando non accoda nulla e stampa il dettaglio per regione', 'test_automatico' => 'tests/Feature/Console/CaiAnalyzeFinancialDocumentsCommandTest.php::dry run queues nothing and prints the per-region breakdown'],
                ['id' => 'F9-206', 'descrizione' => 'Un documento col solo stato patrimoniale è Extracted e valorizza le colonne dello stato patrimoniale', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php::a document with only balance sheet data is Extracted and fills the balance sheet columns'],
                ['id' => 'F9-207', 'descrizione' => 'Il mapper riporta i totali dello stato patrimoniale quando presenti', 'test_automatico' => 'tests/Unit/Domain/CaiDirectory/Import/CaiFinancialStatementFieldMapperTest.php::mapFinancialStatement maps the balance sheet totals when present'],
                ['id' => 'F9-208', 'descrizione' => 'Il servizio di analisi restituisce i campi finanziari', 'test_automatico' => 'cai-runts-scraper/tests/test_analyze_bilancio.py::test_analyze_bilancio_returns_financial_fields'],
                ['id' => 'F9-209', 'descrizione' => 'Gli utenti senza cai-directory.view non accedono a "Elenco gruppi regionali" (403)', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiRegionalGroupsTest.php::a user without cai-directory.view is denied (403)'],
                ['id' => 'F9-210', 'descrizione' => 'I conteggi per gruppo regionale sono calcolati per regione; le sezioni extra-regione non appartengono a nessun gruppo', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiRegionalGroupsTest.php::counts are computed per region and extra-region sections belong to no group'],
                ['id' => 'F9-211', 'descrizione' => 'Il numero di query non cresce col numero di gruppi regionali', 'test_automatico' => 'tests/Feature/Filament/CaiDirectory/CaiRegionalGroupsTest.php::the number of queries does not grow with the number of groups'],
            ],
        ],
        [
            'titolo' => 'Snapshot CAI/RUNTS nel datapack (export e import) (US-950..US-954)',
            'test' => [
                ['id' => 'F9-212', 'descrizione' => 'Senza datapack il comando di export fallisce con un messaggio italiano esplicito', 'test_automatico' => 'tests/Feature/Console/CaiExportDatapackSnapshotCommandTest.php::missing datapack fails with an explicit Italian message'],
                ['id' => 'F9-213', 'descrizione' => 'L\'export scrive tutte le sei tabelle snap_* con conteggi uguali a quelli del database', 'test_automatico' => 'tests/Feature/Console/CaiExportDatapackSnapshotCommandTest.php::exports every table with counts matching the database'],
                ['id' => 'F9-214', 'descrizione' => 'I valori sono scritti in forma grezza di colonna, senza user_id e senza id autoincrementale', 'test_automatico' => 'tests/Feature/Console/CaiExportDatapackSnapshotCommandTest.php::values are raw column values, never user_id, never an autoincrement id'],
                ['id' => 'F9-215', 'descrizione' => 'L\'export è idempotente: due esecuzioni producono lo stesso contenuto nello stesso ordine', 'test_automatico' => 'tests/Feature/Console/CaiExportDatapackSnapshotCommandTest.php::is idempotent: two runs produce the same content in the same order'],
                ['id' => 'F9-216', 'descrizione' => 'Con --dry-run l\'export non scrive nulla e il file del datapack resta invariato', 'test_automatico' => 'tests/Feature/Console/CaiExportDatapackSnapshotCommandTest.php::--dry-run writes nothing and the datapack file is unchanged'],
                ['id' => 'F9-217', 'descrizione' => 'Le altre tabelle del datapack (sezioni_cai, enti, bilanci, allegati...) non vengono toccate dall\'export', 'test_automatico' => 'tests/Feature/Console/CaiExportDatapackSnapshotCommandTest.php::other datapack tables are left untouched'],
                ['id' => 'F9-218', 'descrizione' => 'Un contenuto identico è copiato una sola volta, col nome uguale allo sha256 ricalcolato', 'test_automatico' => 'tests/Feature/Console/CaiExportDatapackSnapshotFilesTest.php::identical content is copied once, named by recomputed sha256'],
                ['id' => 'F9-219', 'descrizione' => 'Un file mancante esporta comunque la riga con file_in_datapack 0 e un avviso', 'test_automatico' => 'tests/Feature/Console/CaiExportDatapackSnapshotFilesTest.php::a missing file exports the row with file_in_datapack 0 and a warning'],
                ['id' => 'F9-220', 'descrizione' => 'I documenti manuali non vengono mai copiati nel datapack', 'test_automatico' => 'tests/Feature/Console/CaiExportDatapackSnapshotFilesTest.php::manual documents are never copied'],
                ['id' => 'F9-221', 'descrizione' => 'Una seconda esecuzione non ricopia i file già presenti', 'test_automatico' => 'tests/Feature/Console/CaiExportDatapackSnapshotFilesTest.php::a second run does not recopy files already present'],
                ['id' => 'F9-222', 'descrizione' => 'Con spazio libero insufficiente l\'export si ferma con un errore italiano prima di scrivere', 'test_automatico' => 'tests/Feature/Console/CaiExportDatapackSnapshotFilesTest.php::insufficient free space aborts with an Italian error before writing anything'],
                ['id' => 'F9-223', 'descrizione' => 'Con --dry-run nessun file viene copiato', 'test_automatico' => 'tests/Feature/Console/CaiExportDatapackSnapshotFilesTest.php::--dry-run copies no file'],
                ['id' => 'F9-224', 'descrizione' => 'L\'import ripristina tutte le tabelle snapshot su un database vuoto con parità di conteggi', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotImportTest.php::restores every snapshot table on an empty database with count parity'],
                ['id' => 'F9-225', 'descrizione' => 'I bilanci sono ripristinati per registrazione o per sezione, con esattamente un genitore', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotImportTest.php::statements are restored by registration and by section parent, exactly one parent each'],
                ['id' => 'F9-226', 'descrizione' => 'Una seconda esecuzione non crea né aggiorna nulla', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotImportTest.php::a second run creates and updates nothing'],
                ['id' => 'F9-227', 'descrizione' => 'I valori cambiati sono aggiornati e user_id non viene mai sovrascritto', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotImportTest.php::changed snapshot values are updated, user_id is never overwritten'],
                ['id' => 'F9-228', 'descrizione' => 'Le sezioni nuove ottengono user_id ricostruito per email', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotImportTest.php::new sections get user_id rebuilt by email'],
                ['id' => 'F9-229', 'descrizione' => 'Le righe con genitore inesistente sono saltate con avviso, senza violare chiavi esterne', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotImportTest.php::rows whose parent does not exist are skipped with a warning and never violate a foreign key'],
                ['id' => 'F9-230', 'descrizione' => 'Con --dry-run l\'import non scrive e riporta gli stessi conteggi dell\'esecuzione reale', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotImportTest.php::dry-run writes nothing and reports the same counts as the real run'],
                ['id' => 'F9-231', 'descrizione' => 'Un datapack senza tabelle snap_* non è influenzato e un import limitato a una sezione salta lo snapshot', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotImportTest.php::a datapack without snap tables is unaffected and a scoped import skips the snapshot'],
                ['id' => 'F9-232', 'descrizione' => 'Il comando di import stampa le righe di riepilogo dello snapshot e non accoda nulla', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotImportTest.php::the import command prints the snapshot summary rows and queues nothing'],
                ['id' => 'F9-233', 'descrizione' => 'I documenti RUNTS sono creati copiando i file sul disco documenti, mai come manuali', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotDocumentsImportTest.php::creates RUNTS documents copying files into the documents disk, never as manual'],
                ['id' => 'F9-234', 'descrizione' => 'Un documento già importato dal legacy (stesso genitore, hash e fonte) non è duplicato ma ne è allineata l\'analisi', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotDocumentsImportTest.php::does not duplicate a legacy document with same parent, hash and source but aligns the analysis'],
                ['id' => 'F9-235', 'descrizione' => 'Una seconda esecuzione dell\'import documenti non crea né aggiorna nulla', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotDocumentsImportTest.php::second run creates and updates nothing'],
                ['id' => 'F9-236', 'descrizione' => 'Un file sorgente mancante è saltato con avviso senza fermare gli altri', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotDocumentsImportTest.php::a missing source file is skipped with a warning without stopping the others'],
                ['id' => 'F9-237', 'descrizione' => 'Una riga il cui genitore non esiste è saltata e conteggiata', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotDocumentsImportTest.php::a row whose parent does not exist is skipped and counted'],
                ['id' => 'F9-238', 'descrizione' => 'Con spazio insufficiente non viene copiato nulla e l\'errore italiano è esplicito', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotDocumentsImportTest.php::insufficient disk space copies nothing and reports an explicit Italian error'],
                ['id' => 'F9-239', 'descrizione' => 'Con spazio insufficiente il comando di import termina con esito di fallimento', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotDocumentsImportTest.php::the command fails with the space error'],
                ['id' => 'F9-240', 'descrizione' => 'Con --dry-run l\'import documenti non scrive né copia ma riporta conteggi e byte previsti', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotDocumentsImportTest.php::dry-run writes and copies nothing but reports counts and bytes'],
                ['id' => 'F9-241', 'descrizione' => 'La riga di riepilogo riporta i megabyte copiati', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotDocumentsImportTest.php::the summary line reports the megabytes copied'],
                ['id' => 'F9-242', 'descrizione' => 'Il file scaricato di un documento importato ha lo stesso sha256 del sorgente', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotDocumentsImportTest.php::the downloaded file of an imported document has the sha256 of the source'],
                ['id' => 'F9-243', 'descrizione' => 'L\'import dei documenti non accoda alcun job di analisi', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotDocumentsImportTest.php::no analysis job is queued by the documents import'],
                ['id' => 'F9-244', 'descrizione' => 'L\'esito di analisi dei documenti manuali è ripristinato per sezione e hash, senza creare né accodare nulla', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotManualAnalysisImportTest.php::restores the analysis of manual documents matched by section and hash, without creating or queuing'],
                ['id' => 'F9-245', 'descrizione' => 'Una riga manuale senza documento corrispondente è saltata e conteggiata', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotManualAnalysisImportTest.php::a snapshot manual row without a matching document is skipped and counted'],
                ['id' => 'F9-246', 'descrizione' => 'Una seconda esecuzione non aggiorna nulla e --dry-run non scrive', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotManualAnalysisImportTest.php::second run updates nothing and dry-run writes nothing'],
                ['id' => 'F9-247', 'descrizione' => 'Dopo l\'import i flag della pagina "Bilancio 2025" sono gli stessi del database locale', 'test_automatico' => 'tests/Feature/Console/CaiSnapshotManualAnalysisImportTest.php::the Bilancio 2025 flags are the same as in the local database after the import'],
            ],
        ],
    ],
];
