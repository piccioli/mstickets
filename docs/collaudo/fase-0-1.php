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
            ['ruolo' => 'Admin', 'email' => 'admin@orchestrator.local', 'password' => 'password'],
            ['ruolo' => 'Developer', 'email' => 'developer@orchestrator.local', 'password' => 'password'],
            ['ruolo' => 'Manager', 'email' => 'manager@orchestrator.local', 'password' => 'password'],
            ['ruolo' => 'Customer', 'email' => 'customer@orchestrator.local', 'password' => 'password'],
            ['ruolo' => 'Fundraising', 'email' => 'fundraising@orchestrator.local', 'password' => 'password'],
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
                    'test_automatico' => 'tests/Feature/Domain/Identity/PanelAccessTest.php',
                ],
                [
                    'id' => 'F0-02',
                    'descrizione' => 'Un utente senza nessuno dei 5 ruoli applicativi non accede al pannello',
                    'test_automatico' => 'tests/Feature/Domain/Identity/PanelAccessTest.php',
                ],
                [
                    'id' => 'F0-03',
                    'descrizione' => 'Un utente disattivato non accede al pannello anche con un ruolo valido',
                    'test_automatico' => 'tests/Feature/Domain/Identity/PanelAccessTest.php',
                ],
                [
                    'id' => 'F0-04',
                    'descrizione' => 'Le query di selezione utenti (es. campi di assegnazione) escludono gli utenti disattivati',
                    'test_automatico' => 'tests/Feature/Domain/Identity/PanelAccessTest.php',
                ],
                [
                    'id' => 'F0-05',
                    'descrizione' => 'Il catalogo ruoli contiene esattamente i 5 ruoli previsti (Admin, Developer, Manager, Customer, Fundraising)',
                    'test_automatico' => 'tests/Unit/Domain/Identity/UserRoleTest.php',
                ],
                [
                    'id' => 'F0-06',
                    'descrizione' => 'Il catalogo permessi contiene esattamente i permessi previsti dal catalogo di dominio',
                    'test_automatico' => 'tests/Unit/Domain/Identity/PermissionTest.php',
                ],
                [
                    'id' => 'F0-07',
                    'descrizione' => 'Le tabelle di ruoli/permessi sono pubblicate correttamente: guard unico web, nessuna gestione a team',
                    'test_automatico' => 'tests/Feature/Domain/Identity/PermissionTablesTest.php',
                ],
                [
                    'id' => 'F0-08',
                    'descrizione' => 'Il seeder di ruoli/permessi assegna a ciascun ruolo esattamente i permessi previsti dalla matrice ruolo-permesso',
                    'test_automatico' => 'tests/Feature/Domain/Identity/RolePermissionSeederTest.php',
                ],
                [
                    'id' => 'F0-09',
                    'descrizione' => 'I permessi riservati (accesso code, accesso log, visualizzazione import) non sono assegnati a nessun ruolo salvo Admin',
                    'test_automatico' => 'tests/Feature/Domain/Identity/RolePermissionSeederTest.php',
                ],
                [
                    'id' => 'F0-10',
                    'descrizione' => 'Il seeder di ruoli/permessi è idempotente e revoca un permesso/ruolo rimosso dal catalogo senza lasciarlo orfano',
                    'test_automatico' => 'tests/Feature/Domain/Identity/RolePermissionSeederTest.php',
                ],
                [
                    'id' => 'F0-11',
                    'descrizione' => 'Un utente senza il permesso richiesto riceve sempre accesso negato; con il permesso viene autorizzato (nessun modello raggiungibile senza policy)',
                    'test_automatico' => 'tests/Feature/Domain/Identity/UserPolicyTest.php',
                ],
                [
                    'id' => 'F0-12',
                    'descrizione' => 'Un admin può assegnare/revocare ruoli e permessi diretti di un utente dalla UI; la risorsa Ruoli resta di sola lettura (nessuna creazione/modifica)',
                    'test_automatico' => 'tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php',
                ],
                [
                    'id' => 'F0-13',
                    'descrizione' => 'La scheda utente mostra i permessi effettivi con la provenienza (dal ruolo oppure diretto)',
                    'test_automatico' => 'tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Schema dati — anagrafiche e organizzazioni',
            'test' => [
                [
                    'id' => 'F0-14',
                    'descrizione' => 'La tabella utenti rispetta i vincoli richiesti: email unica case-insensitive, soft delete',
                    'test_automatico' => 'tests/Feature/Domain/Identity/UsersTableTest.php',
                ],
                [
                    'id' => 'F0-15',
                    'descrizione' => 'Le organizzazioni collegano gli utenti con vincolo di unicità sulla coppia organizzazione/utente',
                    'test_automatico' => 'tests/Feature/Domain/Identity/OrganizationsTableTest.php',
                ],
                [
                    'id' => 'F0-16',
                    'descrizione' => 'Le organizzazioni sono protette da policy deny-by-default (nessun accesso senza permesso)',
                    'test_automatico' => 'tests/Feature/Domain/Identity/OrganizationPolicyTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Schema dati — Ticketing (tabelle e vincoli)',
            'test' => [
                [
                    'id' => 'F0-17',
                    'descrizione' => 'La tabella ticket rispetta colonne, default e relazioni richieste (assegnatario, tester, richiedente, ecc.)',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketsTableTest.php',
                ],
                [
                    'id' => 'F0-18',
                    'descrizione' => 'I messaggi di un ticket hanno un identificativo pubblico univoco e vengono eliminati a cascata con il ticket',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketMessagesTableTest.php',
                ],
                [
                    'id' => 'F0-19',
                    'descrizione' => 'Lo storico dei ticket registra un diff strutturato dei cambiamenti, non il valore grezzo del campo',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLogsTableTest.php',
                ],
                [
                    'id' => 'F0-20',
                    'descrizione' => 'Al massimo una visualizzazione per (ticket, utente, giorno) è ammessa a livello di database',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketViewsTableTest.php',
                ],
                [
                    'id' => 'F0-21',
                    'descrizione' => 'Un utente non può essere aggiunto due volte come partecipante dello stesso ticket',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketParticipantsTableTest.php',
                ],
                [
                    'id' => 'F0-22',
                    'descrizione' => 'Il collegamento ticket/tag ha un vincolo reale a livello di database, non solo applicativo',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketTagTableTest.php',
                ],
                [
                    'id' => 'F0-23',
                    'descrizione' => 'Le righe di ore lavorate sono uniche per (giorno, utente, ticket)',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketWorkLogsTableTest.php',
                ],
                [
                    'id' => 'F0-24',
                    'descrizione' => 'Lo stato del ticket copre esattamente i 12 valori previsti (incluso "Testing", non "Test")',
                    'test_automatico' => 'tests/Unit/Domain/Ticketing/TicketStatusTest.php',
                ],
                [
                    'id' => 'F0-25',
                    'descrizione' => 'I tag e le pagine di documentazione rispettano slug univoco e collegamento opzionale reciproco',
                    'test_automatico' => 'tests/Feature/Domain/Tags/TagsTableTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Autorizzazioni per modulo — policy deny-by-default',
            'test' => [
                [
                    'id' => 'F0-26',
                    'descrizione' => 'Un messaggio interno del ticket non è mai visibile/gestibile senza il permesso dedicato',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketMessagePolicyTest.php',
                ],
                [
                    'id' => 'F0-27',
                    'descrizione' => 'Lo storico del ticket è visualizzabile solo con il permesso dedicato e non è mai scrivibile manualmente dall\'utente',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLogPolicyTest.php',
                ],
                [
                    'id' => 'F0-28',
                    'descrizione' => 'La gestione dei partecipanti al ticket è riservata a chi ha il permesso di assegnazione',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketParticipantPolicyTest.php',
                ],
                [
                    'id' => 'F0-29',
                    'descrizione' => 'Le visualizzazioni del ticket sono protette dalla stessa policy di visualizzazione del ticket',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketViewPolicyTest.php',
                ],
                [
                    'id' => 'F0-30',
                    'descrizione' => 'Le ore lavorate registrate sono protette da policy dedicata (visualizzazione vs modifica)',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketWorkLogPolicyTest.php',
                ],
                [
                    'id' => 'F0-31',
                    'descrizione' => 'I tag sono protetti da policy deny-by-default',
                    'test_automatico' => 'tests/Feature/Domain/Tags/TagPolicyTest.php',
                ],
                [
                    'id' => 'F0-32',
                    'descrizione' => 'Le pagine di documentazione distinguono correttamente accesso cliente vs interno',
                    'test_automatico' => 'tests/Feature/Domain/Documentation/DocumentationPagePolicyTest.php',
                ],
                [
                    'id' => 'F0-33',
                    'descrizione' => 'I report di attività sono protetti da policy deny-by-default',
                    'test_automatico' => 'tests/Feature/Domain/Reporting/ActivityReportPolicyTest.php',
                ],
                [
                    'id' => 'F0-34',
                    'descrizione' => 'Le opportunità di fundraising sono protette da policy deny-by-default',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingOpportunityPolicyTest.php',
                ],
                [
                    'id' => 'F0-35',
                    'descrizione' => 'I messaggi email sono protetti da policy deny-by-default',
                    'test_automatico' => 'tests/Feature/Domain/Mail/EmailMessagePolicyTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Schema dati — rendicontazione, fundraising, email e infrastruttura di importazione',
            'test' => [
                [
                    'id' => 'F0-36',
                    'descrizione' => 'Un report di attività deve avere esattamente un proprietario (utente oppure organizzazione), mai entrambi o nessuno',
                    'test_automatico' => 'tests/Feature/Domain/Reporting/ActivityReportsTableTest.php',
                ],
                [
                    'id' => 'F0-37',
                    'descrizione' => 'Le opportunità di fundraising rispettano i default e le relazioni richieste (creatore, responsabile, ambito territoriale)',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingOpportunitiesTableTest.php',
                ],
                [
                    'id' => 'F0-38',
                    'descrizione' => 'I messaggi email hanno un identificativo pubblico univoco e i vincoli di unicità richiesti',
                    'test_automatico' => 'tests/Feature/Domain/Mail/EmailMessagesTableTest.php',
                ],
                [
                    'id' => 'F0-39',
                    'descrizione' => 'Le preferenze di notifica sono uniche per (utente, tipo di notifica, canale)',
                    'test_automatico' => 'tests/Feature/Domain/Mail/NotificationPreferencesTableTest.php',
                ],
                [
                    'id' => 'F0-40',
                    'descrizione' => 'Le tabelle di infrastruttura per l\'importazione (esecuzioni e mappature) rispettano lo schema richiesto',
                    'test_automatico' => 'tests/Feature/Import/ImportRunsTableTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Diagnostica e configurazione ambiente',
            'test' => [
                [
                    'id' => 'F0-41',
                    'descrizione' => 'Il comando diagnostico segnala con codice di uscita ed elenco leggibile quali controlli passano e quali falliscono, creando l\'utente di sistema se assente',
                    'test_automatico' => 'tests/Feature/Console/OrchestratorDoctorCommandTest.php',
                ],
                [
                    'id' => 'F0-42',
                    'descrizione' => 'Il controllo delle variabili ambiente obbligatorie segnala ogni variabile mancante o vuota',
                    'test_automatico' => 'tests/Unit/Support/Doctor/EnvironmentVariablesCheckTest.php',
                ],
                [
                    'id' => 'F0-43',
                    'descrizione' => 'Il controllo di scrittura delle directory storage rilevanti passa su un ambiente pulito',
                    'test_automatico' => 'tests/Unit/Support/Doctor/StorageWritableCheckTest.php',
                ],
                [
                    'id' => 'F0-44',
                    'descrizione' => 'L\'utente di sistema viene creato se assente e non consente mai l\'accesso al pannello',
                    'test_automatico' => 'tests/Unit/Support/Doctor/SystemUserCheckTest.php',
                ],
                [
                    'id' => 'F0-45',
                    'descrizione' => 'Le feature flag delle automazioni schedulate sono tutte disattivate di default',
                    'test_automatico' => 'tests/Unit/OrchestratorConfigTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Design system e tema del pannello',
            'test' => [
                [
                    'id' => 'F0-46',
                    'descrizione' => 'Il tema del pannello (colore di brand, font) deriva dai token del design system, non da valori scritti a mano',
                    'test_automatico' => 'tests/Unit/DesignTokensTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Seed di sviluppo',
            'test' => [
                [
                    'id' => 'F0-47',
                    'descrizione' => 'Il seed di sviluppo rifiuta di girare in produzione e popola un ambiente completo (utenti, organizzazioni, ticket, tag, documentazione, report, fundraising)',
                    'test_automatico' => 'tests/Feature/Database/Seeders/DevelopmentSeederTest.php',
                ],
                [
                    'id' => 'F0-48',
                    'descrizione' => 'Una seconda esecuzione del seed di sviluppo non duplica ticket, tag o pagine di documentazione',
                    'test_automatico' => 'tests/Feature/Database/Seeders/DevelopmentSeederTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'ETL — analizzatori di v1:inspect (solo verifica struttura)',
            'test' => [
                [
                    'id' => 'F0-49',
                    'descrizione' => 'L\'analizzatore di chiavi esterne orfane conta correttamente le righe orfane e ignora i valori nulli',
                    'test_automatico' => 'tests/Unit/Import/Inspect/OrphanForeignKeyAnalyzerTest.php',
                ],
                [
                    'id' => 'F0-50',
                    'descrizione' => 'L\'analizzatore di email duplicate individua i duplicati che differiscono solo per maiuscole/minuscole',
                    'test_automatico' => 'tests/Unit/Import/Inspect/DuplicateEmailAnalyzerTest.php',
                ],
                [
                    'id' => 'F0-51',
                    'descrizione' => 'L\'analizzatore del changes di story_logs conta i JSON interpretabili e la distribuzione delle chiavi',
                    'test_automatico' => 'tests/Unit/Import/Inspect/ChangesKeyAnalyzerTest.php',
                ],
                [
                    'id' => 'F0-52',
                    'descrizione' => 'L\'analizzatore di customer_request separa correttamente un elenco HTML in messaggi distinti',
                    'test_automatico' => 'tests/Unit/Import/Inspect/CustomerRequestAnalyzerTest.php',
                ],
                [
                    'id' => 'F0-53',
                    'descrizione' => 'L\'analizzatore dei ruoli utente v1 distingue ruoli in formato JSON, ruoli scalari e valori nulli/sconosciuti',
                    'test_automatico' => 'tests/Unit/Import/Inspect/RoleValueAnalyzerTest.php',
                ],
                [
                    'id' => 'F0-54',
                    'descrizione' => 'L\'analizzatore delle incongruenze stato/timestamp trova le righe in uno stato che richiede una data assente',
                    'test_automatico' => 'tests/Unit/Import/Inspect/StatusTimestampAnalyzerTest.php',
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
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php',
                ],
                [
                    'id' => 'F1-02',
                    'descrizione' => 'Il percorso senza collaudo interno (new -> ... -> released -> done, saltando testing/tested) è ammesso',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php',
                ],
                [
                    'id' => 'F1-03',
                    'descrizione' => 'Ogni transizione ammessa e ogni transizione vietata della tabella di stato è verificata per attore e condizioni',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketStateMachineTest.php',
                ],
                [
                    'id' => 'F1-04',
                    'descrizione' => 'Un developer può auto-assegnarsi un ticket nuovo ma non può assegnarlo a un collega',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketStateMachineTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Validazioni di dominio del ticket',
            'test' => [
                [
                    'id' => 'F1-05',
                    'descrizione' => 'La transizione verso "in test" richiede un tester assegnato',
                    'test_automatico' => 'tests/Unit/Domain/Ticketing/Rules/TicketTesterRequiredRuleTest.php',
                ],
                [
                    'id' => 'F1-06',
                    'descrizione' => 'La transizione verso "in attesa" richiede un motivo di attesa non vuoto',
                    'test_automatico' => 'tests/Unit/Domain/Ticketing/Rules/TicketWaitingReasonRequiredRuleTest.php',
                ],
                [
                    'id' => 'F1-07',
                    'descrizione' => 'La transizione verso "problema" richiede un motivo del problema non vuoto',
                    'test_automatico' => 'tests/Unit/Domain/Ticketing/Rules/TicketProblemReasonRequiredRuleTest.php',
                ],
                [
                    'id' => 'F1-08',
                    'descrizione' => 'Un ticket che ha già dei figli non può a sua volta diventare figlio di un altro ticket (profondità massima 1)',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Rules/TicketParentDepthRuleTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Creazione e cambio di stato del ticket: log ed eventi',
            'test' => [
                [
                    'id' => 'F1-09',
                    'descrizione' => 'La creazione di un ticket lo porta in stato "nuovo" e registra uno storico con l\'utente autore',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/CreateTicketTest.php',
                ],
                [
                    'id' => 'F1-10',
                    'descrizione' => 'Una transizione vietata non scrive nulla e restituisce un errore leggibile',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/ChangeTicketStatusTest.php',
                ],
                [
                    'id' => 'F1-11',
                    'descrizione' => 'Portare un ticket "in lavorazione" retrocede automaticamente gli altri ticket in lavorazione dello stesso assegnatario',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/ChangeTicketStatusTest.php',
                ],
                [
                    'id' => 'F1-12',
                    'descrizione' => 'Se la retrocessione automatica di un altro ticket fallisce, l\'intera operazione viene annullata (nessuna scrittura parziale)',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/ChangeTicketStatusTest.php',
                ],
                [
                    'id' => 'F1-13',
                    'descrizione' => 'L\'assegnazione di un ticket a un utente registra uno storico con l\'assegnatario precedente e quello nuovo',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/AssignTicketTest.php',
                ],
                [
                    'id' => 'F1-14',
                    'descrizione' => 'Un cambio della descrizione del ticket non salva mai il testo nello storico, solo il fatto che sia cambiato',
                    'test_automatico' => 'tests/Unit/Domain/Ticketing/DTO/TicketLogChangesTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Propagazione esplicita ai ticket figli',
            'test' => [
                [
                    'id' => 'F1-15',
                    'descrizione' => 'Il cambio di stato si propaga ai ticket figli diretti solo se richiesto esplicitamente dall\'utente',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/ApplyStatusToChildrenTest.php',
                ],
                [
                    'id' => 'F1-16',
                    'descrizione' => 'Un ticket figlio la cui transizione non è ammessa viene saltato, con motivo, senza bloccare gli altri figli',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/ApplyStatusToChildrenTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Regole sul record — chi vede e modifica quale ticket',
            'test' => [
                [
                    'id' => 'F1-17',
                    'descrizione' => 'Un developer con permesso limitato agli assegnati non può aggiornare un ticket di cui non è assegnatario né tester',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketPolicyTest.php',
                ],
                [
                    'id' => 'F1-18',
                    'descrizione' => 'Un cliente vede solo i ticket di cui è il richiedente, mai quelli di altri clienti',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketVisibleToScopeTest.php',
                ],
                [
                    'id' => 'F1-19',
                    'descrizione' => 'Un messaggio marcato come interno non è mai raggiungibile da un cliente, nemmeno tramite accesso diretto',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketMessageVisibleToScopeTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Conversazione del ticket',
            'test' => [
                [
                    'id' => 'F1-20',
                    'descrizione' => 'Un nuovo messaggio pubblico viene creato con testo sanitizzato e versione solo-testo derivata',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/PostTicketMessageTest.php',
                ],
                [
                    'id' => 'F1-21',
                    'descrizione' => 'L\'autore di un messaggio viene aggiunto ai partecipanti del ticket, senza duplicati',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/PostTicketMessageTest.php',
                ],
                [
                    'id' => 'F1-22',
                    'descrizione' => 'I destinatari calcolati sono partecipanti, richiedente, assegnatario e tester, deduplicati ed esclude sempre l\'autore',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketMessageRecipientsTest.php',
                ],
                [
                    'id' => 'F1-23',
                    'descrizione' => 'Un messaggio del richiedente su un ticket "in attesa" lo riporta automaticamente allo stato precedente',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Listeners/RestoreTicketStatusOnRequesterMessageTest.php',
                ],
                [
                    'id' => 'F1-24',
                    'descrizione' => 'Un messaggio del richiedente su un ticket assegnato/da fare/in lavorazione lo riporta automaticamente a "da fare"',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Listeners/RestoreTicketStatusOnRequesterMessageTest.php',
                ],
                [
                    'id' => 'F1-25',
                    'descrizione' => 'Uno script incorporato in un messaggio viene rimosso interamente, mai lasciato inline nel testo pubblicato',
                    'test_automatico' => 'tests/Unit/Domain/Ticketing/Support/TicketMessageSanitizerTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Allegati sui messaggi',
            'test' => [
                [
                    'id' => 'F1-26',
                    'descrizione' => 'Un file di tipo ammesso viene caricato correttamente e salvato su disco privato',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/AddTicketAttachmentTest.php',
                ],
                [
                    'id' => 'F1-27',
                    'descrizione' => 'Un file con estensione non ammessa o oltre la dimensione massima consentita viene rifiutato',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/AddTicketAttachmentTest.php',
                ],
                [
                    'id' => 'F1-28',
                    'descrizione' => 'La rimozione di un allegato che non appartiene al messaggio indicato viene rifiutata',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/RemoveTicketAttachmentTest.php',
                ],
                [
                    'id' => 'F1-29',
                    'descrizione' => 'Un utente che non può vedere il ticket non può scaricarne un allegato, nemmeno conoscendo il link diretto',
                    'test_automatico' => 'tests/Feature/Http/TicketAttachmentDownloadControllerTest.php',
                ],
                [
                    'id' => 'F1-30',
                    'descrizione' => 'Un allegato SVG viene servito sanitizzato: uno script incorporato viene rimosso prima del download',
                    'test_automatico' => 'tests/Feature/Http/TicketAttachmentDownloadControllerTest.php',
                ],
                [
                    'id' => 'F1-31',
                    'descrizione' => 'La lista di tipi e dimensioni ammessi per gli allegati è unica e condivisa, non duplicata in più punti',
                    'test_automatico' => 'tests/Unit/Domain/Ticketing/Support/TicketAttachmentTypesTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Tracciamento visualizzazioni',
            'test' => [
                [
                    'id' => 'F1-32',
                    'descrizione' => 'La prima visualizzazione di un ticket nel giorno crea un nuovo record di visualizzazione',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/RecordTicketViewTest.php',
                ],
                [
                    'id' => 'F1-33',
                    'descrizione' => 'Visualizzazioni ravvicinate entro la soglia non aggiornano il contatore, oltre la soglia lo aggiornano',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/RecordTicketViewTest.php',
                ],
                [
                    'id' => 'F1-34',
                    'descrizione' => 'La registrazione di una visualizzazione non produce mai una voce nello storico del ticket',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Actions/RecordTicketViewTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Calcolo delle ore lavorate',
            'test' => [
                [
                    'id' => 'F1-35',
                    'descrizione' => 'Il calcolo dei minuti lavorati su un intervallo chiuso rispetta la finestra oraria configurata',
                    'test_automatico' => 'tests/Unit/Domain/TimeTracking/WorkedTimeCalculatorTest.php',
                ],
                [
                    'id' => 'F1-36',
                    'descrizione' => 'Il weekend viene escluso dal calcolo e le ore vengono limitate alla finestra lavorativa configurata',
                    'test_automatico' => 'tests/Unit/Domain/TimeTracking/WorkedTimeCalculatorTest.php',
                ],
                [
                    'id' => 'F1-37',
                    'descrizione' => 'Un ticket ancora in lavorazione (nessuna chiusura) ha le ore limitate a un tetto configurato, non proiettate a oggi',
                    'test_automatico' => 'tests/Unit/Domain/TimeTracking/WorkedTimeCalculatorTest.php',
                ],
                [
                    'id' => 'F1-38',
                    'descrizione' => 'Il ricalcolo massivo aggiorna le ore lavorate del ticket in modo idempotente (nessuna riga duplicata su una seconda esecuzione)',
                    'test_automatico' => 'tests/Feature/Domain/TimeTracking/Actions/RecalculateWorkedTimeTest.php',
                ],
                [
                    'id' => 'F1-39',
                    'descrizione' => 'Un cambio di stato del ticket accoda il ricalcolo delle ore, unendo più cambi ravvicinati in un solo ricalcolo',
                    'test_automatico' => 'tests/Feature/Domain/TimeTracking/Listeners/RecalculateWorkedTimeOnStatusChangeTest.php',
                ],
                [
                    'id' => 'F1-40',
                    'descrizione' => 'Il comando di ricalcolo massivo permette di ricalcolare un singolo ticket o un intervallo di date',
                    'test_automatico' => 'tests/Feature/Console/TimeTrackingRecalculateCommandTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Scheda ticket — campi e comportamenti',
            'test' => [
                [
                    'id' => 'F1-41',
                    'descrizione' => 'Un cliente che manipola direttamente il modulo non può alterare alcun campo riservato allo staff (tipo, priorità, assegnatario, tester, ore, descrizione interna)',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php',
                ],
                [
                    'id' => 'F1-42',
                    'descrizione' => 'Le sezioni riservate allo staff sono nascoste a un cliente nella vista di dettaglio del ticket',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php',
                ],
                [
                    'id' => 'F1-43',
                    'descrizione' => 'Un developer che porta un ticket nuovo ad "assegnato" si auto-assegna silenziosamente, senza dover scegliere sé stesso',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php',
                ],
                [
                    'id' => 'F1-44',
                    'descrizione' => 'La transizione verso "in test" richiede la scelta di un tester e fallisce leggibilmente se assente',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php',
                ],
                [
                    'id' => 'F1-45',
                    'descrizione' => 'Una transizione di stato vietata mostra all\'utente il messaggio di errore leggibile tramite notifica',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php',
                ],
                [
                    'id' => 'F1-46',
                    'descrizione' => 'Postare un nuovo messaggio dalla scheda ticket lo fa comparire nella conversazione',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php',
                ],
                [
                    'id' => 'F1-47',
                    'descrizione' => 'La gestione dei partecipanti al ticket dalla UI è visibile solo a chi ha il permesso di assegnazione',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php',
                ],
                [
                    'id' => 'F1-48',
                    'descrizione' => 'La selezione di un ticket padre non valido mostra il messaggio leggibile della regola di profondità massima',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php',
                ],
                [
                    'id' => 'F1-49',
                    'descrizione' => 'L\'apertura della pagina di dettaglio di un ticket registra una visualizzazione',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketResourceTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Viste operative della lista ticket',
            'test' => [
                [
                    'id' => 'F1-50',
                    'descrizione' => 'La vista "Richieste attive" include ed esclude correttamente i ticket attesi',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/ActiveRequestsQueryTest.php',
                ],
                [
                    'id' => 'F1-51',
                    'descrizione' => 'La vista "In attesa" ordina i ticket dal più vecchio, per giorni di attesa decrescenti',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/WaitingQueryTest.php',
                ],
                [
                    'id' => 'F1-52',
                    'descrizione' => 'La vista "Assegnati a me" mostra solo i ticket assegnati all\'utente corrente',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/AssignedToMeQueryTest.php',
                ],
                [
                    'id' => 'F1-53',
                    'descrizione' => 'La vista "Da testare (io tester)" mostra solo i ticket in cui l\'utente corrente è il tester',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/ToTestByMeQueryTest.php',
                ],
                [
                    'id' => 'F1-54',
                    'descrizione' => 'La vista "In test" mostra solo i ticket nello stato di collaudo interno',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/InTestingQueryTest.php',
                ],
                [
                    'id' => 'F1-55',
                    'descrizione' => 'La vista "Problemi" mostra solo i ticket nello stato problema',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/ProblemTicketsQueryTest.php',
                ],
                [
                    'id' => 'F1-56',
                    'descrizione' => 'La vista "Backlog" mostra solo i ticket nello stato backlog',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/BacklogQueryTest.php',
                ],
                [
                    'id' => 'F1-57',
                    'descrizione' => 'La vista "Archivio" mostra solo i ticket conclusi',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/ArchivedTicketsQueryTest.php',
                ],
                [
                    'id' => 'F1-58',
                    'descrizione' => 'La vista "Interni" mostra solo i ticket senza un richiedente esterno',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/InternalTicketsQueryTest.php',
                ],
                [
                    'id' => 'F1-59',
                    'descrizione' => 'La vista "I miei ticket" per un cliente mostra solo le proprie richieste non ancora concluse',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/MyTicketsQueryTest.php',
                ],
                [
                    'id' => 'F1-60',
                    'descrizione' => 'La vista "Archivio" per un cliente mostra solo le proprie richieste concluse o rifiutate',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/MyArchivedTicketsQueryTest.php',
                ],
                [
                    'id' => 'F1-61',
                    'descrizione' => 'La vista "Nuovi" mostra solo i ticket appena creati non ancora assegnati',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/NewTicketsQueryTest.php',
                ],
                [
                    'id' => 'F1-62',
                    'descrizione' => 'La vista "In lavorazione" mostra solo i ticket nello stato progress',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/InProgressTicketsQueryTest.php',
                ],
                [
                    'id' => 'F1-63',
                    'descrizione' => 'La vista "Tutti i ticket di clienti" mostra tutti i ticket con un richiedente esterno, indipendentemente dallo stato',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/Queries/AllCustomerTicketsQueryTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Filtri della lista ticket',
            'test' => [
                [
                    'id' => 'F1-64',
                    'descrizione' => 'Il filtro per stato permette la selezione multipla',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketsTableFiltersTest.php',
                ],
                [
                    'id' => 'F1-65',
                    'descrizione' => 'Il filtro per organizzazione del richiedente restringe correttamente la lista',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketsTableFiltersTest.php',
                ],
                [
                    'id' => 'F1-66',
                    'descrizione' => 'I filtri "senza tag" e "con più di un tag" restituiscono le liste corrette',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketsTableFiltersTest.php',
                ],
                [
                    'id' => 'F1-67',
                    'descrizione' => 'Il filtro periodo restringe la lista per intervallo di data di creazione o di completamento',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketsTableFiltersTest.php',
                ],
                [
                    'id' => 'F1-68',
                    'descrizione' => 'I filtri si combinano correttamente con una vista/tab già attiva, senza sostituirla',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketsTableFiltersTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Vista di lavoro e landing per ruolo',
            'test' => [
                [
                    'id' => 'F1-69',
                    'descrizione' => 'La vista di lavoro raggruppa in colonne i ticket visibili per stato, rispettando la visibilità per ruolo',
                    'test_automatico' => 'tests/Feature/Filament/Pages/WorkBoardTest.php',
                ],
                [
                    'id' => 'F1-70',
                    'descrizione' => 'Il selettore di assegnatario permette di vedere la vista di lavoro di un collega',
                    'test_automatico' => 'tests/Feature/Filament/Pages/WorkBoardTest.php',
                ],
                [
                    'id' => 'F1-71',
                    'descrizione' => 'Staff (admin/manager/developer) atterra sulla vista di lavoro dopo il login; un cliente resta sulla propria dashboard',
                    'test_automatico' => 'tests/Feature/Filament/Pages/DashboardTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Verifica end-to-end di Fase 1',
            'test' => [
                [
                    'id' => 'F1-72',
                    'descrizione' => 'Le ore lavorate calcolate end-to-end su un intero ciclo di vita del ticket sono coerenti con i cambi di stato reali',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php',
                ],
                [
                    'id' => 'F1-73',
                    'descrizione' => 'Manomettere il contesto di una transizione con auto-assegnazione non permette di assegnare il ticket a un altro utente',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php',
                ],
                [
                    'id' => 'F1-74',
                    'descrizione' => 'Una transizione vietata tentata direttamente contro l\'azione di cambio stato (bypassando la UI) viene rifiutata e non scrive nulla',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php',
                ],
            ],
        ],
    ],
];
