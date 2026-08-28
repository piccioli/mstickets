<?php

declare(strict_types=1);

// Manifest di tracciabilità per il collaudo (UAT) di Fase 7 (Tipologia di cliente CAI:
// Sezione/Sottosezione, Gruppo Regionale, Organo Tecnico Centrale/Struttura Operativa,
// Cliente generico — US-701..US-706). Stessa disciplina delle fasi precedenti (vedi
// docs/collaudo/fase-6.php): topic raggruppati per user story del PRD (scripts/ralph/
// prd.json, Fase 7), in ordine di priorità US-701 -> US-706, più un topic finale
// dedicato al checkpoint di fine fase (US-706). Ogni voce collega un criterio di
// accettazione a un test automatico REALMENTE esistente in tests/ (verificato da
// `collaudo:verify-manifest 7`). Scope confermato col committente durante il design
// (orchestrator/docs/superpowers/specs/2026-08-28-tipologia-clienti-cai-design.md): la
// differenziazione per tipo si limita a (1) badge tipo/regione sulla dashboard cliente,
// (2) card "sezioni del gruppo regionale" per i clienti Gruppo Regionale — nessun altro
// comportamento differenziato per tipo in questa fase.

return [
    'fase' => '7',
    'titolo' => 'Fase 7 (Tipologia di cliente CAI)',
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
            'titolo' => 'Schema e cataloghi CustomerType/Region — persistenza e cast (§14, US-701)',
            'test' => [
                [
                    'id' => 'F7-01',
                    'descrizione' => 'Il catalogo CustomerType contiene esattamente i 4 tipi cliente CAI del PRD',
                    'test_automatico' => 'tests/Unit/Domain/Identity/CustomerTypeTest.php::contains exactly the 4 tipi cliente CAI di PRD §14 (Fase 7)',
                ],
                [
                    'id' => 'F7-02',
                    'descrizione' => 'Il catalogo Region contiene esattamente le 20 regioni italiane ufficiali, con Trentino-Alto Adige unificato',
                    'test_automatico' => 'tests/Unit/Domain/Identity/RegionTest.php::contains exactly le 20 regioni italiane ufficiali (Trentino-Alto Adige unificato)',
                ],
                [
                    'id' => 'F7-03',
                    'descrizione' => 'Ogni regione ha una label non vuota per la UI',
                    'test_automatico' => 'tests/Unit/Domain/Identity/RegionTest.php::every case has a non-empty label',
                ],
                [
                    'id' => 'F7-04',
                    'descrizione' => 'Il metodo label() restituisce il nome italiano corretto per i casi con grafia particolare (es. Valle d\'Aosta, Friuli-Venezia Giulia)',
                    'test_automatico' => 'tests/Unit/Domain/Identity/RegionTest.php::label restituisce il nome italiano corretto per i casi con grafia particolare',
                ],
                [
                    'id' => 'F7-05',
                    'descrizione' => 'La tabella users ha le colonne additive customer_type/region introdotte da questa fase',
                    'test_automatico' => 'tests/Feature/Domain/Identity/UsersTableTest.php::users table has the customer_type/region columns of Fase 7 (US-701)',
                ],
                [
                    'id' => 'F7-06',
                    'descrizione' => 'Un utente senza customer_type/region resta null senza errori',
                    'test_automatico' => 'tests/Feature/Domain/Identity/UsersTableTest.php::a user without customer_type/region stays null without errors',
                ],
                [
                    'id' => 'F7-07',
                    'descrizione' => 'customer_type/region sono castati al proprio enum backed sia in lettura sia in scrittura',
                    'test_automatico' => 'tests/Feature/Domain/Identity/UsersTableTest.php::customer_type/region are cast to their backed enum in both directions',
                ],
            ],
        ],
        [
            'titolo' => 'Stage ETL CustomerClassificationStage — inferenza automatica di tipo/regione dal nome (§14, US-702)',
            'test' => [
                [
                    'id' => 'F7-08',
                    'descrizione' => 'Un nome con prefisso GR/GP classifica come Gruppo Regionale ed estrae la regione',
                    'test_automatico' => 'tests/Feature/Import/Stages/CustomerClassificationStageTest.php::GR/GP prefix classifies as GruppoRegionale and extracts the region',
                ],
                [
                    'id' => 'F7-09',
                    'descrizione' => 'Un nome con prefisso OTCO/SO classifica come Organo Tecnico Centrale/Struttura Operativa, sempre senza regione',
                    'test_automatico' => 'tests/Feature/Import/Stages/CustomerClassificationStageTest.php::OTCO/SO prefix classifies as OrganoTecnicoStrutturaOperativa with no region',
                ],
                [
                    'id' => 'F7-10',
                    'descrizione' => 'Il pattern OTCO/SO è riconosciuto anche con spazi intorno alla barra',
                    'test_automatico' => 'tests/Feature/Import/Stages/CustomerClassificationStageTest.php::OTCO / SO with spaces around the slash is also recognized',
                ],
                [
                    'id' => 'F7-11',
                    'descrizione' => 'Un nome nel formato "nome | regione" classifica come Sezione ed estrae la regione, col o senza il prefisso C.A.I. SEZ.',
                    'test_automatico' => 'tests/Feature/Import/Stages/CustomerClassificationStageTest.php::a pipe-separated name classifies as Sezione and extracts the region, with or without the C.A.I. SEZ. prefix',
                ],
                [
                    'id' => 'F7-12',
                    'descrizione' => 'Una Sezione senza testo dopo il separatore "|" resta Sezione con regione null, mai Generico',
                    'test_automatico' => 'tests/Feature/Import/Stages/CustomerClassificationStageTest.php::a Sezione with nothing after the pipe stays Sezione with a null region, never Generico',
                ],
                [
                    'id' => 'F7-13',
                    'descrizione' => 'Un nome che non corrisponde a nessun pattern classifica come Cliente generico, senza regione',
                    'test_automatico' => 'tests/Feature/Import/Stages/CustomerClassificationStageTest.php::a name matching no pattern classifies as Generico with no region',
                ],
                [
                    'id' => 'F7-14',
                    'descrizione' => 'La normalizzazione regione gestisce le varianti di maiuscole, apostrofo e trattino del dump v1',
                    'test_automatico' => 'tests/Feature/Import/Stages/CustomerClassificationStageTest.php::region normalization handles case, apostrophe and hyphen variants from the v1 dump',
                ],
                [
                    'id' => 'F7-15',
                    'descrizione' => 'Una regione non normalizzabile registra un warning e lascia region null, senza bloccare l\'import con un\'eccezione',
                    'test_automatico' => 'tests/Feature/Import/Stages/CustomerClassificationStageTest.php::an unnormalizable region logs a warning and leaves region null instead of throwing',
                ],
                [
                    'id' => 'F7-16',
                    'descrizione' => 'Un utente senza ruolo customer non viene mai toccato dallo stage',
                    'test_automatico' => 'tests/Feature/Import/Stages/CustomerClassificationStageTest.php::a non-customer user is never touched',
                ],
                [
                    'id' => 'F7-17',
                    'descrizione' => 'Rieseguire lo stage sugli stessi dati è idempotente: la seconda corsa solo salta',
                    'test_automatico' => 'tests/Feature/Import/Stages/CustomerClassificationStageTest.php::re-running the stage on the same data is idempotent: second run only skips',
                ],
                [
                    'id' => 'F7-18',
                    'descrizione' => 'La modalità --dry-run non persiste alcuna classificazione',
                    'test_automatico' => 'tests/Feature/Import/Stages/CustomerClassificationStageTest.php::--dry-run does not persist any classification',
                ],
            ],
        ],
        [
            'titolo' => 'UI Admin — assegnazione tipo cliente e regione (§14, US-703)',
            'test' => [
                [
                    'id' => 'F7-19',
                    'descrizione' => 'I campi tipo cliente e regione sono nascosti quando nessun ruolo customer è selezionato nel form',
                    'test_automatico' => 'tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php::customer_type and region are hidden when no customer role is selected',
                ],
                [
                    'id' => 'F7-20',
                    'descrizione' => 'Il campo tipo cliente diventa visibile quando il ruolo customer viene selezionato nel form',
                    'test_automatico' => 'tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php::customer_type becomes visible when the customer role is selected in the form',
                ],
                [
                    'id' => 'F7-21',
                    'descrizione' => 'Il campo regione diventa visibile solo quando il tipo cliente è Sezione o Gruppo Regionale',
                    'test_automatico' => 'tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php::region becomes visible only when customer_type is Sezione or GruppoRegionale',
                ],
                [
                    'id' => 'F7-22',
                    'descrizione' => 'Un admin con user.assign-roles può persistere tipo cliente e regione dal form di modifica',
                    'test_automatico' => 'tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php::an admin with user.assign-roles can persist customer_type and region via the edit form',
                ],
                [
                    'id' => 'F7-23',
                    'descrizione' => 'La regione viene azzerata al salvataggio quando il tipo cliente non è più Sezione o Gruppo Regionale',
                    'test_automatico' => 'tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php::region is cleared when customer_type is not Sezione or GruppoRegionale on save',
                ],
                [
                    'id' => 'F7-24',
                    'descrizione' => 'Un admin senza user.assign-roles non vede né può modificare tipo cliente e regione',
                    'test_automatico' => 'tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php::an admin without user.assign-roles cannot see or modify customer_type and region',
                ],
                [
                    'id' => 'F7-25',
                    'descrizione' => 'La colonna tipo cliente (badge colorato) è disponibile nell\'elenco utenti per vista rapida e filtro (verificata in browser durante US-703, vedi scripts/ralph/progress.txt)',
                    'test_automatico' => 'tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Badge tipo cliente sulla dashboard (§14, US-704)',
            'test' => [
                [
                    'id' => 'F7-26',
                    'descrizione' => 'Il badge mostra l\'etichetta corretta con la regione per un cliente Sezione',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the customer type badge shows the correct label with region for a sezione customer',
                ],
                [
                    'id' => 'F7-27',
                    'descrizione' => 'Il badge mostra solo il tipo per un cliente Sezione senza regione',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the customer type badge shows just the type for a sezione customer without a region',
                ],
                [
                    'id' => 'F7-28',
                    'descrizione' => 'Il badge mostra l\'etichetta corretta con la regione per un cliente Gruppo Regionale',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the customer type badge shows the correct label with region for a gruppo regionale customer',
                ],
                [
                    'id' => 'F7-29',
                    'descrizione' => 'Il badge mostra solo il tipo per un cliente Organo Tecnico Centrale/Struttura Operativa (mai una regione)',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the customer type badge shows only the type for an organo tecnico/struttura operativa customer',
                ],
                [
                    'id' => 'F7-30',
                    'descrizione' => 'Il badge mostra solo il tipo per un cliente generico',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the customer type badge shows only the type for a generico customer',
                ],
                [
                    'id' => 'F7-31',
                    'descrizione' => 'Il badge è assente quando il cliente non ha ancora un customer_type classificato',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the customer type badge is absent when the customer has no customer_type classified',
                ],
            ],
        ],
        [
            'titolo' => 'Card "Sezioni del gruppo regionale" sulla dashboard (§14, US-705)',
            'test' => [
                [
                    'id' => 'F7-32',
                    'descrizione' => 'La card elenca solo le sezioni della stessa regione del Gruppo Regionale, col relativo conteggio ticket aperti',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the regional group sections card lists only sections in the same region, with their open ticket count',
                ],
                [
                    'id' => 'F7-33',
                    'descrizione' => 'La card mostra uno stato vuoto esplicito quando la regione non ha ancora nessuna sezione classificata',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the regional group sections card shows an explicit empty state when the region has no sections yet',
                ],
                [
                    'id' => 'F7-34',
                    'descrizione' => 'La card mostra uno stato vuoto esplicito quando il Gruppo Regionale non ha region valorizzata',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the regional group sections card shows an explicit empty state when the group has no region',
                ],
                [
                    'id' => 'F7-35',
                    'descrizione' => 'La card è assente per i clienti Sezione, Organo Tecnico Centrale/Struttura Operativa e generico',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the regional group sections card is absent for sezione, organo tecnico/struttura operativa, and generico customers',
                ],
            ],
        ],
        [
            'titolo' => 'Checkpoint di fine fase — import, classificazione, correzione admin e riflesso in dashboard (US-706)',
            'test' => [
                [
                    'id' => 'F7-36',
                    'descrizione' => 'L\'import classifica correttamente un utente per ciascuno dei 4 tipi cliente, un admin corregge manualmente il tipo di uno di essi, e la dashboard del cliente corretto riflette il nuovo tipo',
                    'test_automatico' => 'tests/Feature/EndToEnd/Fase7CheckpointEndToEndTest.php::import classifies one user of each customer type correctly, an admin corrects one manually, and the customer dashboard reflects the corrected type',
                ],
            ],
        ],
    ],
];
