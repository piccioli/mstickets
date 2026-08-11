<?php

declare(strict_types=1);

// Manifest di tracciabilità per il collaudo (UAT) di Fase 0 (Fondazioni, 24 story) + Fase 1
// (Ticketing core, 14 story). Ogni voce collega un criterio di accettazione a un test
// automatico REALMENTE esistente in tests/ (verificato da `collaudo:verify-manifest 0-1`).
// Fonte delle story: scripts/ralph/archive/2026-07-26-orchestrator-v2-fase-0/prd.json (Fase 0)
// e scripts/ralph/prd.json (Fase 1). Questo file è puro dato: nessuna logica.

return [
    'fase' => '0-1',
    'titolo' => 'Fase 0 (Fondazioni) + Fase 1 (Ticketing core)',
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
        // === FASE 0 — Fondazioni ===================================================
        [
            'titolo' => 'Autenticazione, ruoli e permessi',
            'test' => [
                [
                    'id' => 'F0-01',
                    'descrizione' => 'Un utente con un ruolo applicativo valido accede al pannello',
                    'test_automatico' => 'tests/Feature/Domain/Identity/PanelAccessTest.php::a user with a valid application role can access the panel',
                ],
                [
                    'id' => 'F0-02',
                    'descrizione' => 'Un utente senza nessuno dei 5 ruoli applicativi non accede al pannello',
                    'test_automatico' => 'tests/Feature/Domain/Identity/PanelAccessTest.php::a user without any of the 5 valid roles cannot access the panel',
                ],
                [
                    'id' => 'F0-03',
                    'descrizione' => 'Un utente disattivato non accede al pannello anche con un ruolo valido',
                    'test_automatico' => 'tests/Feature/Domain/Identity/PanelAccessTest.php::a deactivated user cannot access the panel even with a valid role',
                ],
                [
                    'id' => 'F0-04',
                    'descrizione' => 'Le query di selezione utenti (es. campi di assegnazione) escludono gli utenti disattivati',
                    'test_automatico' => 'tests/Feature/Domain/Identity/PanelAccessTest.php::the active scope excludes deactivated users from a user selection query',
                ],
                [
                    'id' => 'F0-05',
                    'descrizione' => 'Il catalogo ruoli contiene esattamente i 5 ruoli previsti (Admin, Developer, Manager, Customer, Fundraising)',
                    'test_automatico' => 'tests/Unit/Domain/Identity/UserRoleTest.php::contains exactly the 5 roles of PRD §9.2, no editor',
                ],
                [
                    'id' => 'F0-06',
                    'descrizione' => 'Il catalogo permessi contiene esattamente i permessi previsti dal catalogo di dominio',
                    'test_automatico' => 'tests/Unit/Domain/Identity/PermissionTest.php::contains exactly the permission catalog of PRD §9.3',
                ],
                [
                    'id' => 'F0-07',
                    'descrizione' => 'Le tabelle di ruoli/permessi sono pubblicate correttamente: guard unico web, nessuna gestione a team',
                    'test_automatico' => 'tests/Feature/Domain/Identity/PermissionTablesTest.php::roles and permissions default to the single web guard',
                ],
                [
                    'id' => 'F0-08',
                    'descrizione' => 'Il seeder di ruoli/permessi assegna a ciascun ruolo esattamente i permessi previsti dalla matrice ruolo-permesso',
                    'test_automatico' => 'tests/Feature/Domain/Identity/RolePermissionSeederTest.php::the seeder materializes exactly the §9.4 role/permission matrix',
                ],
                [
                    'id' => 'F0-09',
                    'descrizione' => 'I permessi riservati (accesso code, accesso log, visualizzazione import) non sono assegnati a nessun ruolo salvo Admin',
                    'test_automatico' => 'tests/Feature/Domain/Identity/RolePermissionSeederTest.php::horizon.access, logs.access and import.view are not granted to any role except admin',
                ],
                [
                    'id' => 'F0-10',
                    'descrizione' => 'Il seeder di ruoli/permessi è idempotente e revoca un permesso/ruolo rimosso dal catalogo senza lasciarlo orfano',
                    'test_automatico' => 'tests/Feature/Domain/Identity/RolePermissionSeederTest.php::running the seeder twice is idempotent',
                ],
                [
                    'id' => 'F0-11',
                    'descrizione' => 'Un utente senza il permesso richiesto riceve sempre accesso negato; con il permesso viene autorizzato (nessun modello raggiungibile senza policy)',
                    'test_automatico' => 'tests/Feature/Domain/Identity/UserPolicyTest.php::a user without the matching permission is denied on every UserPolicy ability',
                ],
                [
                    'id' => 'F0-12',
                    'descrizione' => 'Un admin può assegnare/revocare ruoli e permessi diretti di un utente dalla UI; la risorsa Ruoli resta di sola lettura (nessuna creazione/modifica)',
                    'test_automatico' => 'tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php::an admin with user.assign-roles can assign a role to a user via the edit form',
                ],
                [
                    'id' => 'F0-13',
                    'descrizione' => 'La scheda utente mostra i permessi effettivi con la provenienza (dal ruolo oppure diretto)',
                    'test_automatico' => 'tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php::effective permissions are listed with their provenance (role vs direct)',
                ],
            ],
        ],
        [
            'titolo' => 'Schema dati — anagrafiche e organizzazioni',
            'test' => [
                [
                    'id' => 'F0-14',
                    'descrizione' => 'La tabella utenti rispetta i vincoli richiesti: email unica case-insensitive, soft delete',
                    'test_automatico' => 'tests/Feature/Domain/Identity/UsersTableTest.php::email can be looked up case-insensitively via the functional index',
                ],
                [
                    'id' => 'F0-15',
                    'descrizione' => 'Le organizzazioni collegano gli utenti con vincolo di unicità sulla coppia organizzazione/utente',
                    'test_automatico' => 'tests/Feature/Domain/Identity/OrganizationsTableTest.php::the organization/user pair is unique',
                ],
                [
                    'id' => 'F0-16',
                    'descrizione' => 'Le organizzazioni sono protette da policy deny-by-default (nessun accesso senza permesso)',
                    'test_automatico' => 'tests/Feature/Domain/Identity/OrganizationPolicyTest.php::a user without organization.* permissions is denied every OrganizationPolicy ability',
                ],
            ],
        ],
        [
            'titolo' => 'Schema dati — Ticketing (tabelle e vincoli)',
            'test' => [
                [
                    'id' => 'F0-17',
                    'descrizione' => 'La tabella ticket rispetta colonne, default e relazioni richieste (assegnatario, tester, richiedente, ecc.)',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketsTableTest.php::tickets table has the columns required by §5.2',
                ],
                [
                    'id' => 'F0-18',
                    'descrizione' => 'I messaggi di un ticket hanno un identificativo pubblico univoco e vengono eliminati a cascata con il ticket',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketMessagesTableTest.php::a ulid is generated automatically on creation, id stays the auto-increment primary key',
                ],
                [
                    'id' => 'F0-19',
                    'descrizione' => 'Lo storico dei ticket registra un diff strutturato dei cambiamenti, non il valore grezzo del campo',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLogsTableTest.php::event and status columns are cast to their backed enum, changes is a JSON diff (not the field body)',
                ],
                [
                    'id' => 'F0-20',
                    'descrizione' => 'Al massimo una visualizzazione per (ticket, utente, giorno) è ammessa a livello di database',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketViewsTableTest.php::the ticket/user/viewed_on triple is unique',
                ],
                [
                    'id' => 'F0-21',
                    'descrizione' => 'Un utente non può essere aggiunto due volte come partecipante dello stesso ticket',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketParticipantsTableTest.php::a ticket tracks its participants and the pair is unique',
                ],
                [
                    'id' => 'F0-22',
                    'descrizione' => 'Il collegamento ticket/tag ha un vincolo reale a livello di database, non solo applicativo',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketTagTableTest.php::the ticket/tag pair is unique',
                ],
                [
                    'id' => 'F0-23',
                    'descrizione' => 'Le righe di ore lavorate sono uniche per (giorno, utente, ticket)',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketWorkLogsTableTest.php::the work_date/user/ticket triple is unique, minutes defaults to 0',
                ],
                [
                    'id' => 'F0-24',
                    'descrizione' => 'Lo stato del ticket copre esattamente i 12 valori previsti (incluso "Testing", non "Test")',
                    'test_automatico' => 'tests/Unit/Domain/Ticketing/TicketStatusTest.php::contains exactly the 12 values of the v1, case Testing (not Test)',
                ],
                [
                    'id' => 'F0-25',
                    'descrizione' => 'I tag e le pagine di documentazione rispettano slug univoco e collegamento opzionale reciproco',
                    'test_automatico' => 'tests/Feature/Domain/Tags/TagsTableTest.php::slug is unique',
                ],
            ],
        ],
        [
            'titolo' => 'Autorizzazioni per modulo — policy deny-by-default',
            'test' => [
                [
                    'id' => 'F0-26',
                    'descrizione' => 'Un messaggio interno del ticket non è mai visibile/gestibile senza il permesso dedicato',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketMessagePolicyTest.php::a public ticket message is gated by ticket.view.*, an internal one by ticket-message.view.internal',
                ],
                [
                    'id' => 'F0-27',
                    'descrizione' => 'Lo storico del ticket è visualizzabile solo con il permesso dedicato e non è mai scrivibile manualmente dall\'utente',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLogPolicyTest.php::a user with ticket-log.view can only view logs, never write them (system-only writes)',
                ],
                [
                    'id' => 'F0-28',
                    'descrizione' => 'La gestione dei partecipanti al ticket è riservata a chi ha il permesso di assegnazione',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketParticipantPolicyTest.php::viewing participants is gated by ticket.view.*, managing them by ticket.assign',
                ],
                [
                    'id' => 'F0-29',
                    'descrizione' => 'Le visualizzazioni del ticket sono protette dalla stessa policy di visualizzazione del ticket',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketViewPolicyTest.php::a user who can view tickets can also read/write their own view markers',
                ],
                [
                    'id' => 'F0-30',
                    'descrizione' => 'Le ore lavorate registrate sono protette da policy dedicata (visualizzazione vs modifica)',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketWorkLogPolicyTest.php::viewing work logs is gated by ticket.view.*, logging hours by ticket.update.*',
                ],
                [
                    'id' => 'F0-31',
                    'descrizione' => 'I tag sono protetti da policy deny-by-default',
                    'test_automatico' => 'tests/Feature/Domain/Tags/TagPolicyTest.php::a user without tag.* permissions is denied every TagPolicy ability',
                ],
                [
                    'id' => 'F0-32',
                    'descrizione' => 'Le pagine di documentazione distinguono correttamente accesso cliente vs interno',
                    'test_automatico' => 'tests/Feature/Domain/Documentation/DocumentationPagePolicyTest.php::a customer-category page is gated by documentation.view.customer, an internal one by documentation.view.internal',
                ],
                [
                    'id' => 'F0-33',
                    'descrizione' => 'I report di attività sono protetti da policy deny-by-default',
                    'test_automatico' => 'tests/Feature/Domain/Reporting/ActivityReportPolicyTest.php::a user without any activity-report.* permission is denied every ActivityReportPolicy ability',
                ],
                [
                    'id' => 'F0-34',
                    'descrizione' => 'Le opportunità di fundraising sono protette da policy deny-by-default',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingOpportunityPolicyTest.php::a user without any fundraising.* permission is denied every FundraisingOpportunityPolicy ability',
                ],
                [
                    'id' => 'F0-35',
                    'descrizione' => 'I messaggi email sono protetti da policy deny-by-default',
                    'test_automatico' => 'tests/Feature/Domain/Mail/EmailMessagePolicyTest.php::a user without any email.* permission is denied every EmailMessagePolicy ability',
                ],
            ],
        ],
        [
            'titolo' => 'Schema dati — rendicontazione, fundraising, email e infrastruttura di importazione',
            'test' => [
                [
                    'id' => 'F0-36',
                    'descrizione' => 'Un report di attività deve avere esattamente un proprietario (utente oppure organizzazione), mai entrambi o nessuno',
                    'test_automatico' => 'tests/Feature/Domain/Reporting/ActivityReportsTableTest.php::the owner check constraint rejects a row with neither owner set',
                ],
                [
                    'id' => 'F0-37',
                    'descrizione' => 'Le opportunità di fundraising rispettano i default e le relazioni richieste (creatore, responsabile, ambito territoriale)',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingOpportunitiesTableTest.php::fundraising_opportunities table has the columns required by §5.2',
                ],
                [
                    'id' => 'F0-38',
                    'descrizione' => 'I messaggi email hanno un identificativo pubblico univoco e i vincoli di unicità richiesti',
                    'test_automatico' => 'tests/Feature/Domain/Mail/EmailMessagesTableTest.php::a ulid is generated automatically on creation, id stays the auto-increment primary key',
                ],
                [
                    'id' => 'F0-39',
                    'descrizione' => 'Le preferenze di notifica sono uniche per (utente, tipo di notifica, canale)',
                    'test_automatico' => 'tests/Feature/Domain/Mail/NotificationPreferencesTableTest.php::unique on the user/notification_type/channel triple',
                ],
                [
                    'id' => 'F0-40',
                    'descrizione' => 'Le tabelle di infrastruttura per l\'importazione (esecuzioni e mappature) rispettano lo schema richiesto',
                    'test_automatico' => 'tests/Feature/Import/ImportRunsTableTest.php::import_runs table has the columns required by §5.2',
                ],
            ],
        ],
        [
            'titolo' => 'Diagnostica e configurazione ambiente',
            'test' => [
                [
                    'id' => 'F0-41',
                    'descrizione' => 'Il comando diagnostico segnala con codice di uscita ed elenco leggibile quali controlli passano e quali falliscono, creando l\'utente di sistema se assente',
                    'test_automatico' => 'tests/Feature/Console/OrchestratorDoctorCommandTest.php::it exits successfully and reports every check when the environment is valid',
                ],
                [
                    'id' => 'F0-42',
                    'descrizione' => 'Il controllo delle variabili ambiente obbligatorie segnala ogni variabile mancante o vuota',
                    'test_automatico' => 'tests/Unit/Support/Doctor/EnvironmentVariablesCheckTest.php::a missing or empty variable fails',
                ],
                [
                    'id' => 'F0-43',
                    'descrizione' => 'Il controllo di scrittura delle directory storage rilevanti passa su un ambiente pulito',
                    'test_automatico' => 'tests/Unit/Support/Doctor/StorageWritableCheckTest.php::the relevant storage directories of a fresh install are writable',
                ],
                [
                    'id' => 'F0-44',
                    'descrizione' => 'L\'utente di sistema viene creato se assente e non consente mai l\'accesso al pannello',
                    'test_automatico' => 'tests/Unit/Support/Doctor/SystemUserCheckTest.php::it creates the system user when it does not exist yet',
                ],
                [
                    'id' => 'F0-45',
                    'descrizione' => 'Le feature flag delle automazioni schedulate sono tutte disattivate di default',
                    'test_automatico' => 'tests/Unit/OrchestratorConfigTest.php::every scheduled automation feature flag defaults to false',
                ],
            ],
        ],
        [
            'titolo' => 'Design system e tema del pannello',
            'test' => [
                [
                    'id' => 'F0-46',
                    'descrizione' => 'Il tema del pannello (colore di brand, font) deriva dai token del design system, non da valori scritti a mano',
                    'test_automatico' => 'tests/Unit/DesignTokensTest.php::reads the brand color token from resources/css/theme.css',
                ],
            ],
        ],
        [
            'titolo' => 'Seed di sviluppo',
            'test' => [
                [
                    'id' => 'F0-47',
                    'descrizione' => 'L\'ambiente locale è popolato con dati reali importati via ETL (utenti, organizzazioni, ticket, tag, documentazione, report, fundraising) invece di un seed fittizio (make setup, seeding con dati reali)',
                    'test_automatico' => 'tests/Feature/Console/V1ImportPipelineIdempotencyTest.php::a second consecutive v1:import run creates/updates nothing on every registered stage',
                ],
                [
                    'id' => 'F0-48',
                    'descrizione' => 'Una seconda esecuzione di v1:import non duplica nulla su nessuno stage (ticket, tag, documentazione, report, fundraising compresi)',
                    'test_automatico' => 'tests/Feature/Console/V1ImportPipelineIdempotencyTest.php::a second consecutive v1:import run creates/updates nothing on every registered stage',
                ],
            ],
        ],
        [
            'titolo' => 'ETL — analizzatori di v1:inspect (solo verifica struttura)',
            'test' => [
                [
                    'id' => 'F0-49',
                    'descrizione' => 'L\'analizzatore di chiavi esterne orfane conta correttamente le righe orfane e ignora i valori nulli',
                    'test_automatico' => 'tests/Unit/Import/Inspect/OrphanForeignKeyAnalyzerTest.php::counts orphan values and ignores nulls',
                ],
                [
                    'id' => 'F0-50',
                    'descrizione' => 'L\'analizzatore di email duplicate individua i duplicati che differiscono solo per maiuscole/minuscole',
                    'test_automatico' => 'tests/Unit/Import/Inspect/DuplicateEmailAnalyzerTest.php::finds duplicate emails that differ only by case',
                ],
                [
                    'id' => 'F0-51',
                    'descrizione' => 'L\'analizzatore del changes di story_logs conta i JSON interpretabili e la distribuzione delle chiavi',
                    'test_automatico' => 'tests/Unit/Import/Inspect/ChangesKeyAnalyzerTest.php::counts interpretable JSON changes and their key distribution',
                ],
                [
                    'id' => 'F0-52',
                    'descrizione' => 'L\'analizzatore di customer_request separa correttamente un elenco HTML in messaggi distinti',
                    'test_automatico' => 'tests/Unit/Import/Inspect/CustomerRequestAnalyzerTest.php::splits an HTML list customer_request into distinct messages',
                ],
                [
                    'id' => 'F0-53',
                    'descrizione' => 'L\'analizzatore dei ruoli utente v1 distingue ruoli in formato JSON, ruoli scalari e valori nulli/sconosciuti',
                    'test_automatico' => 'tests/Unit/Import/Inspect/RoleValueAnalyzerTest.php::classifies JSON array roles, scalar roles, and null/empty values',
                ],
                [
                    'id' => 'F0-54',
                    'descrizione' => 'L\'analizzatore delle incongruenze stato/timestamp trova le righe in uno stato che richiede una data assente',
                    'test_automatico' => 'tests/Unit/Import/Inspect/StatusTimestampAnalyzerTest.php::finds rows in the target status with a missing timestamp',
                ],
                [
                    'id' => 'F0-55',
                    'descrizione' => 'L\'analizzatore della gerarchia story_story individua le incongruenze rispetto a stories.parent_id in entrambe le direzioni',
                    'test_automatico' => 'tests/Unit/Import/Inspect/StoryHierarchyAnalyzerTest.php::detects mismatches between story_story rows and stories.parent_id',
                ],
                [
                    'id' => 'F0-56',
                    'descrizione' => 'L\'analizzatore dei tag polimorfici raggruppa i taggable_type e conta quelli diversi da Documentation',
                    'test_automatico' => 'tests/Unit/Import/Inspect/TaggableAnalyzerTest.php::groups taggable types and counts those different from Documentation',
                ],
            ],
        ],

        // === FASE 1 — Ticketing core =================================================
        [
            'titolo' => 'Macchina a stati del ticket',
            'test' => [
                [
                    'id' => 'F1-01',
                    'descrizione' => 'Un ticket percorre il percorso principale new -> assigned -> todo -> progress -> testing -> tested -> released -> done',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php::the main path takes a ticket from new to done through every state with coherent worked minutes',
                ],
                [
                    'id' => 'F1-02',
                    'descrizione' => 'Il percorso senza collaudo interno (new -> ... -> released -> done, saltando testing/tested) è ammesso',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php::the path without testing takes a ticket from new to done skipping testing and tested',
                ],
                [
                    'id' => 'F1-03',
                    'descrizione' => 'Ogni transizione ammessa e ogni transizione vietata della tabella di stato è verificata per attore e condizioni',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketStateMachineTest.php::admin can move a new ticket to assigned when assignee_id is provided in context',
                ],
                [
                    'id' => 'F1-04',
                    'descrizione' => 'Un developer può auto-assegnarsi un ticket nuovo ma non può assegnarlo a un collega',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketStateMachineTest.php::a developer can self-assign a new ticket (auto-assignment)',
                ],
            ],
        ],
        [
            'titolo' => 'Validazioni di dominio del ticket',
            'test' => [
                [
                    'id' => 'F1-05',
                    'descrizione' => 'La transizione verso "in test" richiede un tester assegnato',
                    'test_automatico' => 'tests/Unit/Domain/Ticketing/Rules/TicketTesterRequiredRuleTest.php::a null tester_id fails the rule with the italian message',
                ],
                [
                    'id' => 'F1-06',
                    'descrizione' => 'La transizione verso "in attesa" richiede un motivo di attesa non vuoto',
                    'test_automatico' => 'tests/Unit/Domain/Ticketing/Rules/TicketWaitingReasonRequiredRuleTest.php::null, empty and blank waiting_reason all fail the rule',
                ],
                [
                    'id' => 'F1-07',
                    'descrizione' => 'La transizione verso "problema" richiede un motivo del problema non vuoto',
                    'test_automatico' => 'tests/Unit/Domain/Ticketing/Rules/TicketProblemReasonRequiredRuleTest.php::null, empty and blank problem_reason all fail the rule',
                ],
                [
                    'id' => 'F1-08',
                    'descrizione' => 'Un ticket che ha già dei figli non può a sua volta diventare figlio di un altro ticket (profondità massima 1)',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Rules/TicketParentDepthRuleTest.php::a ticket that already has children cannot itself become a child',
                ],
            ],
        ],
        [
            'titolo' => 'Creazione e cambio di stato del ticket: log ed eventi',
            'test' => [
                [
                    'id' => 'F1-09',
                    'descrizione' => 'La creazione di un ticket lo porta in stato "nuovo" e registra uno storico con l\'utente autore',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/CreateTicketTest.php::creates a ticket in new status regardless of the status attribute passed in',
                ],
                [
                    'id' => 'F1-10',
                    'descrizione' => 'Una transizione vietata non scrive nulla e restituisce un errore leggibile',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/ChangeTicketStatusTest.php::a forbidden transition writes nothing and raises a localized validation error',
                ],
                [
                    'id' => 'F1-11',
                    'descrizione' => 'Portare un ticket "in lavorazione" retrocede automaticamente gli altri ticket in lavorazione dello stesso assegnatario',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/ChangeTicketStatusTest.php::moving a ticket to progress demotes the assignee\\\'s other in-progress tickets to todo, each with its own log',
                ],
                [
                    'id' => 'F1-12',
                    'descrizione' => 'Se la retrocessione automatica di un altro ticket fallisce, l\'intera operazione viene annullata (nessuna scrittura parziale)',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/ChangeTicketStatusTest.php::the whole transition rolls back if demoting another in-progress ticket fails',
                ],
                [
                    'id' => 'F1-13',
                    'descrizione' => 'L\'assegnazione di un ticket a un utente registra uno storico con l\'assegnatario precedente e quello nuovo',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/AssignTicketTest.php::writes an assigned ticket_log with a typed changes DTO recording the previous and new assignee',
                ],
                [
                    'id' => 'F1-14',
                    'descrizione' => 'Un cambio della descrizione del ticket non salva mai il testo nello storico, solo il fatto che sia cambiato',
                    'test_automatico' => 'tests/Unit/Domain/Ticketing/DTO/TicketLogChangesTest.php::descriptionChanged never records the field value, only the changed marker',
                ],
            ],
        ],
        [
            'titolo' => 'Propagazione esplicita ai ticket figli',
            'test' => [
                [
                    'id' => 'F1-15',
                    'descrizione' => 'Il cambio di stato si propaga ai ticket figli diretti solo se richiesto esplicitamente dall\'utente',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/ApplyStatusToChildrenTest.php::changing the parent status alone never propagates to children unless the action is invoked explicitly',
                ],
                [
                    'id' => 'F1-16',
                    'descrizione' => 'Un ticket figlio la cui transizione non è ammessa viene saltato, con motivo, senza bloccare gli altri figli',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/ApplyStatusToChildrenTest.php::a child whose transition is not allowed is skipped, with a reason, without blocking the others',
                ],
            ],
        ],
        [
            'titolo' => 'Regole sul record — chi vede e modifica quale ticket',
            'test' => [
                [
                    'id' => 'F1-17',
                    'descrizione' => 'Un developer con permesso limitato agli assegnati non può aggiornare un ticket di cui non è assegnatario né tester',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketPolicyTest.php::a developer (ticket.update.assigned) is denied a ticket they are neither assignee nor tester of',
                ],
                [
                    'id' => 'F1-18',
                    'descrizione' => 'Un cliente vede solo i ticket di cui è il richiedente, mai quelli di altri clienti',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketVisibleToScopeTest.php::ticket.view.own (customer) sees only their own tickets',
                ],
                [
                    'id' => 'F1-19',
                    'descrizione' => 'Un messaggio marcato come interno non è mai raggiungibile da un cliente, nemmeno tramite accesso diretto',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketMessageVisibleToScopeTest.php::a customer cannot reach an internal message even via direct by-id access through the scope',
                ],
            ],
        ],
        [
            'titolo' => 'Conversazione del ticket',
            'test' => [
                [
                    'id' => 'F1-20',
                    'descrizione' => 'Un nuovo messaggio pubblico viene creato con testo sanitizzato e versione solo-testo derivata',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/PostTicketMessageTest.php::creates a public web message with sanitized html and a derived plain text body',
                ],
                [
                    'id' => 'F1-21',
                    'descrizione' => 'L\'autore di un messaggio viene aggiunto ai partecipanti del ticket, senza duplicati',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/PostTicketMessageTest.php::adds the author to ticket participants if not already present',
                ],
                [
                    'id' => 'F1-22',
                    'descrizione' => 'I destinatari calcolati sono partecipanti, richiedente, assegnatario e tester, deduplicati ed esclude sempre l\'autore',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketMessageRecipientsTest.php::recipients are participants plus requester, assignee and tester, deduplicated, excluding the author',
                ],
                [
                    'id' => 'F1-23',
                    'descrizione' => 'Un messaggio del richiedente su un ticket "in attesa" lo riporta automaticamente allo stato precedente',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Listeners/RestoreTicketStatusOnRequesterMessageTest.php::a requester message on a waiting ticket restores it to previous_status, attributed to the system user',
                ],
                [
                    'id' => 'F1-24',
                    'descrizione' => 'Un messaggio del richiedente su un ticket assegnato/da fare/in lavorazione lo riporta automaticamente a "da fare"',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Listeners/RestoreTicketStatusOnRequesterMessageTest.php::a requester message on an assigned or in-progress ticket moves it to todo',
                ],
                [
                    'id' => 'F1-25',
                    'descrizione' => 'Uno script incorporato in un messaggio viene rimosso interamente, mai lasciato inline nel testo pubblicato',
                    'test_automatico' => 'tests/Unit/Domain/Ticketing/Support/TicketMessageSanitizerTest.php::strips a script tag and its content entirely, never leaving it inline',
                ],
            ],
        ],
        [
            'titolo' => 'Allegati sui messaggi',
            'test' => [
                [
                    'id' => 'F1-26',
                    'descrizione' => 'Un file di tipo ammesso viene caricato correttamente e salvato su disco privato',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/AddTicketAttachmentTest.php::stores an allowed file on the private disk and returns the media',
                ],
                [
                    'id' => 'F1-27',
                    'descrizione' => 'Un file con estensione non ammessa o oltre la dimensione massima consentita viene rifiutato',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/AddTicketAttachmentTest.php::rejects a file whose extension is not in the shared allowed list',
                ],
                [
                    'id' => 'F1-28',
                    'descrizione' => 'La rimozione di un allegato che non appartiene al messaggio indicato viene rifiutata',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/RemoveTicketAttachmentTest.php::refuses to remove a media that does not belong to the given ticket message',
                ],
                [
                    'id' => 'F1-29',
                    'descrizione' => 'Un utente che non può vedere il ticket non può scaricarne un allegato, nemmeno conoscendo il link diretto',
                    'test_automatico' => 'tests/Feature/Http/TicketAttachmentDownloadControllerTest.php::a user who cannot view the ticket is denied, even by direct id access',
                ],
                [
                    'id' => 'F1-30',
                    'descrizione' => 'Un allegato SVG viene servito sanitizzato: uno script incorporato viene rimosso prima del download',
                    'test_automatico' => 'tests/Feature/Http/TicketAttachmentDownloadControllerTest.php::serves a sanitized svg, stripping the embedded script before responding',
                ],
                [
                    'id' => 'F1-31',
                    'descrizione' => 'La lista di tipi e dimensioni ammessi per gli allegati è unica e condivisa, non duplicata in più punti',
                    'test_automatico' => 'tests/Unit/Domain/Ticketing/Support/TicketAttachmentTypesTest.php::allowed extensions merge documents, images and audio from config',
                ],
            ],
        ],
        [
            'titolo' => 'Tracciamento visualizzazioni',
            'test' => [
                [
                    'id' => 'F1-32',
                    'descrizione' => 'La prima visualizzazione di un ticket nel giorno crea un nuovo record di visualizzazione',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/RecordTicketViewTest.php::the first view of the day creates a ticket_view row',
                ],
                [
                    'id' => 'F1-33',
                    'descrizione' => 'Visualizzazioni ravvicinate entro la soglia non aggiornano il contatore, oltre la soglia lo aggiornano',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/RecordTicketViewTest.php::a second view within the throttle window does not touch last_viewed_at/view_count',
                ],
                [
                    'id' => 'F1-34',
                    'descrizione' => 'La registrazione di una visualizzazione non produce mai una voce nello storico del ticket',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/RecordTicketViewTest.php::recording a view never writes to ticket_logs',
                ],
            ],
        ],
        [
            'titolo' => 'Calcolo delle ore lavorate',
            'test' => [
                [
                    'id' => 'F1-35',
                    'descrizione' => 'Il calcolo dei minuti lavorati su un intervallo chiuso rispetta la finestra oraria configurata',
                    'test_automatico' => 'tests/Unit/Domain/TimeTracking/WorkedTimeCalculatorTest.php::computes minutes for a closed interval within a single day window',
                ],
                [
                    'id' => 'F1-36',
                    'descrizione' => 'Il weekend viene escluso dal calcolo e le ore vengono limitate alla finestra lavorativa configurata',
                    'test_automatico' => 'tests/Unit/Domain/TimeTracking/WorkedTimeCalculatorTest.php::excludes the weekend and clamps to the workday window',
                ],
                [
                    'id' => 'F1-37',
                    'descrizione' => 'Un ticket ancora in lavorazione (nessuna chiusura) ha le ore limitate a un tetto configurato, non proiettate a oggi',
                    'test_automatico' => 'tests/Unit/Domain/TimeTracking/WorkedTimeCalculatorTest.php::caps a still-open interval instead of projecting it indefinitely',
                ],
                [
                    'id' => 'F1-38',
                    'descrizione' => 'Il ricalcolo massivo aggiorna le ore lavorate del ticket in modo idempotente (nessuna riga duplicata su una seconda esecuzione)',
                    'test_automatico' => 'tests/Feature/Domain/TimeTracking/Actions/RecalculateWorkedTimeTest.php::is idempotent: running it twice does not duplicate ticket_work_logs rows',
                ],
                [
                    'id' => 'F1-39',
                    'descrizione' => 'Un cambio di stato del ticket accoda il ricalcolo delle ore, unendo più cambi ravvicinati in un solo ricalcolo',
                    'test_automatico' => 'tests/Feature/Domain/TimeTracking/Listeners/RecalculateWorkedTimeOnStatusChangeTest.php::debounces a burst of transitions on the same ticket into a single queued job',
                ],
                [
                    'id' => 'F1-40',
                    'descrizione' => 'Il comando di ricalcolo massivo permette di ricalcolare un singolo ticket o un intervallo di date',
                    'test_automatico' => 'tests/Feature/Console/TimeTrackingRecalculateCommandTest.php::--ticket limits the recalculation to a single ticket',
                ],
            ],
        ],
        [
            'titolo' => 'Scheda ticket — campi e comportamenti',
            'test' => [
                [
                    'id' => 'F1-41',
                    'descrizione' => 'Un cliente che manipola direttamente il modulo non può alterare alcun campo riservato allo staff (tipo, priorità, assegnatario, tester, ore, descrizione interna)',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php::a customer manipulating the edit form cannot alter any internal field',
                ],
                [
                    'id' => 'F1-42',
                    'descrizione' => 'Le sezioni riservate allo staff sono nascoste a un cliente nella vista di dettaglio del ticket',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php::internal sections are hidden from a customer on the view page',
                ],
                [
                    'id' => 'F1-43',
                    'descrizione' => 'Un developer che porta un ticket nuovo ad "assegnato" si auto-assegna silenziosamente, senza dover scegliere sé stesso',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php::a developer transitioning new to assigned is silently self-assigned without an assignee field',
                ],
                [
                    'id' => 'F1-44',
                    'descrizione' => 'La transizione verso "in test" richiede la scelta di un tester e fallisce leggibilmente se assente',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php::transitioning to testing requires a tester and fails without one',
                ],
                [
                    'id' => 'F1-45',
                    'descrizione' => 'Una transizione di stato vietata mostra all\'utente il messaggio di errore leggibile tramite notifica',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php::a forbidden status transition surfaces the localized state machine message via a notification',
                ],
                [
                    'id' => 'F1-46',
                    'descrizione' => 'Postare un nuovo messaggio dalla scheda ticket lo fa comparire nella conversazione',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php::posting a message via the action calls PostTicketMessage and appears in the conversation',
                ],
                [
                    'id' => 'F1-47',
                    'descrizione' => 'La gestione dei partecipanti al ticket dalla UI è visibile solo a chi ha il permesso di assegnazione',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php::a user without ticket.assign cannot see participant management actions',
                ],
                [
                    'id' => 'F1-48',
                    'descrizione' => 'La selezione di un ticket padre non valido mostra il messaggio leggibile della regola di profondità massima',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php::an invalid parent selection surfaces the readable TicketParentDepthRule message',
                ],
                [
                    'id' => 'F1-49',
                    'descrizione' => 'L\'apertura della pagina di dettaglio di un ticket registra una visualizzazione',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php::opening the view page records a throttled ticket view',
                ],
            ],
        ],
        [
            'titolo' => 'Viste operative della lista ticket',
            'test' => [
                [
                    'id' => 'F1-50',
                    'descrizione' => 'La vista "Richieste attive" include ed esclude correttamente i ticket attesi',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/ActiveRequestsQueryTest.php::includes tickets with a requester in an active status',
                ],
                [
                    'id' => 'F1-51',
                    'descrizione' => 'La vista "In attesa" ordina i ticket dal più vecchio, per giorni di attesa decrescenti',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/WaitingQueryTest.php::orders the oldest waiting ticket first (ascending status_changed_at)',
                ],
                [
                    'id' => 'F1-52',
                    'descrizione' => 'La vista "Assegnati a me" mostra solo i ticket assegnati all\'utente corrente',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/AssignedToMeQueryTest.php::includes tickets assigned to the actor that are neither new nor done',
                ],
                [
                    'id' => 'F1-53',
                    'descrizione' => 'La vista "Da testare (io tester)" mostra solo i ticket in cui l\'utente corrente è il tester',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/ToTestByMeQueryTest.php::includes tickets in testing where the actor is the tester',
                ],
                [
                    'id' => 'F1-54',
                    'descrizione' => 'La vista "In test" mostra solo i ticket nello stato di collaudo interno',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/InTestingQueryTest.php::includes any active request in testing, regardless of tester',
                ],
                [
                    'id' => 'F1-55',
                    'descrizione' => 'La vista "Problemi" mostra solo i ticket nello stato problema',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/ProblemTicketsQueryTest.php::includes active requests in problem status',
                ],
                [
                    'id' => 'F1-56',
                    'descrizione' => 'La vista "Backlog" mostra solo i ticket nello stato backlog',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/BacklogQueryTest.php::includes backlog tickets with a requester',
                ],
                [
                    'id' => 'F1-57',
                    'descrizione' => 'La vista "Archivio" mostra solo i ticket conclusi',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/ArchivedTicketsQueryTest.php::includes done and rejected tickets, with or without a requester',
                ],
                [
                    'id' => 'F1-58',
                    'descrizione' => 'La vista "Interni" mostra solo i ticket senza un richiedente esterno',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/InternalTicketsQueryTest.php::includes tickets whose requester has no customer role and are not done',
                ],
                [
                    'id' => 'F1-59',
                    'descrizione' => 'La vista "I miei ticket" per un cliente mostra solo le proprie richieste non ancora concluse',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/MyTicketsQueryTest.php::includes the customer own tickets that are not done or rejected',
                ],
                [
                    'id' => 'F1-60',
                    'descrizione' => 'La vista "Archivio" per un cliente mostra solo le proprie richieste concluse o rifiutate',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/MyArchivedTicketsQueryTest.php::includes the customer own done and rejected tickets',
                ],
                [
                    'id' => 'F1-61',
                    'descrizione' => 'La vista "Nuovi" mostra solo i ticket appena creati non ancora assegnati',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/NewTicketsQueryTest.php::includes new tickets with a requester',
                ],
                [
                    'id' => 'F1-62',
                    'descrizione' => 'La vista "In lavorazione" mostra solo i ticket nello stato progress',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/InProgressTicketsQueryTest.php::includes any in-progress ticket with a requester, regardless of assignee',
                ],
                [
                    'id' => 'F1-63',
                    'descrizione' => 'La vista "Tutti i ticket di clienti" mostra tutti i ticket con un richiedente esterno, indipendentemente dallo stato',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/AllCustomerTicketsQueryTest.php::includes tickets whose requester has the customer role, regardless of status',
                ],
            ],
        ],
        [
            'titolo' => 'Filtri della lista ticket',
            'test' => [
                [
                    'id' => 'F1-64',
                    'descrizione' => 'Il filtro per stato permette la selezione multipla',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketsTableFiltersTest.php::status filter accepts multiple values',
                ],
                [
                    'id' => 'F1-65',
                    'descrizione' => 'Il filtro per organizzazione del richiedente restringe correttamente la lista',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketsTableFiltersTest.php::organization filter narrows the list by the requester organization',
                ],
                [
                    'id' => 'F1-66',
                    'descrizione' => 'I filtri "senza tag" e "con più di un tag" restituiscono le liste corrette',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketsTableFiltersTest.php::without tags filter shows only tickets with no tag',
                ],
                [
                    'id' => 'F1-67',
                    'descrizione' => 'Il filtro periodo restringe la lista per intervallo di data di creazione o di completamento',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketsTableFiltersTest.php::period filter narrows the list by creation date range',
                ],
                [
                    'id' => 'F1-68',
                    'descrizione' => 'I filtri si combinano correttamente con una vista/tab già attiva, senza sostituirla',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketsTableFiltersTest.php::filters compose with an existing view tab instead of replacing it',
                ],
            ],
        ],
        [
            'titolo' => 'Vista di lavoro e landing per ruolo',
            'test' => [
                [
                    'id' => 'F1-69',
                    'descrizione' => 'La vista di lavoro raggruppa in colonne i ticket visibili per stato, rispettando la visibilità per ruolo',
                    'test_automatico' => 'tests/Feature/Filament/Pages/WorkBoardTest.php::columns group visible tickets by status and hide tickets outside the visibility scope',
                ],
                [
                    'id' => 'F1-70',
                    'descrizione' => 'Il selettore di assegnatario permette di vedere la vista di lavoro di un collega',
                    'test_automatico' => 'tests/Feature/Filament/Pages/WorkBoardTest.php::the assignee selector narrows the board to a single colleague',
                ],
                [
                    'id' => 'F1-71',
                    'descrizione' => 'Staff (admin/manager/developer) atterra sulla vista di lavoro dopo il login; un cliente resta sulla propria dashboard',
                    'test_automatico' => 'tests/Feature/Filament/Pages/DashboardTest.php::staff (admin/manager/developer) landing on the dashboard is redirected to the work board',
                ],
            ],
        ],
        [
            'titolo' => 'Verifica end-to-end di Fase 1',
            'test' => [
                [
                    'id' => 'F1-72',
                    'descrizione' => 'Le ore lavorate calcolate end-to-end su un intero ciclo di vita del ticket sono coerenti con i cambi di stato reali',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php::the main path takes a ticket from new to done through every state with coherent worked minutes',
                ],
                [
                    'id' => 'F1-73',
                    'descrizione' => 'Manomettere il contesto di una transizione con auto-assegnazione non permette di assegnare il ticket a un altro utente',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php::the state machine rejects an impersonated self-assignment context regardless of how it reaches ChangeTicketStatus',
                ],
                [
                    'id' => 'F1-74',
                    'descrizione' => 'Una transizione vietata tentata direttamente contro l\'azione di cambio stato (bypassando la UI) viene rifiutata e non scrive nulla',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php::a forbidden transition attempted directly against the ChangeTicketStatus action is rejected and writes nothing',
                ],
            ],
        ],
    ],
];
