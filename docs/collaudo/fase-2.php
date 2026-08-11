<?php

declare(strict_types=1);

// Manifest di tracciabilità per il collaudo (UAT) di Fase 2 (Importazione dal v1 — ETL,
// US-201..US-218: scaffold/runner, import per entità con id preservato, derive,
// v1:validate, anonimizzazione). Ogni voce collega un criterio di accettazione a un test
// automatico REALMENTE esistente in tests/ (verificato da `collaudo:verify-manifest 2`).
// Fonte delle story: scripts/ralph/prd.json (Fase 2). Questo file è puro dato: nessuna
// logica.

return [
    'fase' => '2',
    'titolo' => 'Fase 2 (Importazione dal v1 — ETL)',
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
            'titolo' => 'Scaffold ETL e runner (US-201)',
            'test' => [
                [
                    'id' => 'F2-01',
                    'descrizione' => 'Il runner risolve l\'ordine di esecuzione dalle dipendenze dichiarate degli stage, non dall\'ordine di registrazione',
                    'test_automatico' => 'tests/Unit/Import/Stages/ImportRunnerPlanTest.php::resolves execution order from declared dependencies, not registration order',
                ],
                [
                    'id' => 'F2-02',
                    'descrizione' => 'Una dipendenza circolare tra stage viene rifiutata esplicitamente',
                    'test_automatico' => 'tests/Unit/Import/Stages/ImportRunnerPlanTest.php::errors explicitly on a circular dependency',
                ],
                [
                    'id' => 'F2-03',
                    'descrizione' => 'Gli stage vengono eseguiti nell\'ordine di dipendenza e i conteggi sono registrati su import_runs.stages',
                    'test_automatico' => 'tests/Feature/Import/Stages/ImportRunnerRunTest.php::executes stages in dependency order and records counts on import_runs.stages',
                ],
                [
                    'id' => 'F2-04',
                    'descrizione' => 'La modalità --dry-run non scrive righe sulla tabella di destinazione',
                    'test_automatico' => 'tests/Feature/Import/Stages/ImportRunnerRunTest.php::dry-run does not write rows to the destination table',
                ],
                [
                    'id' => 'F2-05',
                    'descrizione' => '--truncate è rifiutato esplicitamente in un ambiente di produzione',
                    'test_automatico' => 'tests/Feature/Console/V1ImportCommandTest.php::--truncate is refused outright in a production environment',
                ],
            ],
        ],
        [
            'titolo' => 'Utenti e ruoli/permessi (US-202)',
            'test' => [
                [
                    'id' => 'F2-06',
                    'descrizione' => '"editor" non è un ruolo: viene segnalato separatamente, mai incluso nei roles',
                    'test_automatico' => 'tests/Unit/Import/Mappers/UserRolesMapperTest.php::editor is not a role: flagged separately, never in roles',
                ],
                [
                    'id' => 'F2-07',
                    'descrizione' => 'Gli utenti v1 vengono importati in v2 con l\'id preservato e le colonne mappate',
                    'test_automatico' => 'tests/Feature/Import/Stages/UsersStageTest.php::imports v1 users into v2 with the id preserved and columns mapped',
                ],
                [
                    'id' => 'F2-08',
                    'descrizione' => 'Rieseguire lo stage sullo stesso dump è idempotente: la seconda esecuzione si limita a saltare',
                    'test_automatico' => 'tests/Feature/Import/Stages/UsersStageTest.php::re-running the stage on the same dump is idempotent: second run only skips',
                ],
                [
                    'id' => 'F2-09',
                    'descrizione' => 'Un ruolo riconosciuto viene assegnato tramite Spatie',
                    'test_automatico' => 'tests/Feature/Import/Stages/RolesPermissionsStageTest.php::assigns a recognized role via Spatie',
                ],
                [
                    'id' => 'F2-10',
                    'descrizione' => '"editor" concede i permessi diretti sulla documentazione invece di un ruolo, ed è segnalato se era l\'unico ruolo presente',
                    'test_automatico' => 'tests/Feature/Import/Stages/RolesPermissionsStageTest.php::editor grants direct documentation permissions instead of a role, and is flagged if it was the only role',
                ],
            ],
        ],
        [
            'titolo' => 'Organizzazioni e membership (US-203)',
            'test' => [
                [
                    'id' => 'F2-11',
                    'descrizione' => 'Le organizzazioni v1 vengono importate in v2 con l\'id preservato e le colonne mappate',
                    'test_automatico' => 'tests/Feature/Import/Stages/OrganizationsStageTest.php::imports v1 organizations into v2 with the id preserved and columns mapped',
                ],
                [
                    'id' => 'F2-12',
                    'descrizione' => 'Rieseguire lo stage sullo stesso dump è idempotente: la seconda esecuzione si limita a saltare',
                    'test_automatico' => 'tests/Feature/Import/Stages/OrganizationsStageTest.php::re-running the stage on the same dump is idempotent: second run only skips',
                ],
                [
                    'id' => 'F2-13',
                    'descrizione' => 'Una membership che referenzia un\'organizzazione v2 inesistente viene segnalata, non manda in crash lo stage',
                    'test_automatico' => 'tests/Feature/Import/Stages/OrganizationMembersStageTest.php::a membership referencing a non-existent v2 organization is reported, not crashed',
                ],
            ],
        ],
        [
            'titolo' => 'Documentazione e tag (US-204)',
            'test' => [
                [
                    'id' => 'F2-14',
                    'descrizione' => 'Le documentation v1 vengono importate in v2 documentation_pages con l\'id preservato e le colonne mappate',
                    'test_automatico' => 'tests/Feature/Import/Stages/DocumentationStageTest.php::imports v1 documentations into v2 documentation_pages with the id preserved and columns mapped',
                ],
                [
                    'id' => 'F2-15',
                    'descrizione' => 'Viene generato uno slug provvisorio univoco quando due documentation v1 condividono lo stesso nome',
                    'test_automatico' => 'tests/Feature/Import/Stages/DocumentationStageTest.php::generates a unique provisional slug when two v1 documentations share the same name',
                ],
                [
                    'id' => 'F2-16',
                    'descrizione' => 'Il legame con una Documentation viene preservato come foreign key esplicita documentation_id',
                    'test_automatico' => 'tests/Feature/Import/Stages/TagsStageTest.php::preserves the link to Documentation as an explicit documentation_id foreign key',
                ],
                [
                    'id' => 'F2-17',
                    'descrizione' => 'Un legame a una Documentation verso una pagina v2 inesistente viene ridotto a tag semplice e segnalato, non manda in crash lo stage',
                    'test_automatico' => 'tests/Feature/Import/Stages/TagsStageTest.php::a Documentation link to a non-existent v2 page is collapsed to a plain tag and reported, not crashed',
                ],
            ],
        ],
        [
            'titolo' => 'Mappatura ticket (US-205)',
            'test' => [
                [
                    'id' => 'F2-18',
                    'descrizione' => 'Una story v1 viene importata nei ticket v2 con l\'id preservato e la mappatura principale applicata',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketsStageTest.php::imports a v1 story into v2 tickets with the id preserved and the main mapping applied',
                ],
                [
                    'id' => 'F2-19',
                    'descrizione' => 'status_changed_at viene derivato dal più recente cambio di stato in story_logs',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketsStageTest.php::status_changed_at is derived from the most recent story_logs status change',
                ],
                [
                    'id' => 'F2-20',
                    'descrizione' => 'Per un ticket in waiting, previous_status risale i log fino al primo stato diverso da waiting/problem',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketsStageTest.php::previous_status for a waiting ticket walks back the logs to the first status different from waiting/problem',
                ],
                [
                    'id' => 'F2-21',
                    'descrizione' => 'Un riferimento utente verso un utente v2 inesistente viene azzerato e segnalato, non manda in crash lo stage',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketsStageTest.php::a user reference to a non-existent v2 user is nulled out and reported, not crashed',
                ],
                [
                    'id' => 'F2-22',
                    'descrizione' => 'Rieseguire lo stage sullo stesso dump è idempotente: la seconda esecuzione si limita a saltare',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketsStageTest.php::re-running the stage on the same dump is idempotent: second run only skips',
                ],
            ],
        ],
        [
            'titolo' => 'Gerarchia dei ticket (US-206)',
            'test' => [
                [
                    'id' => 'F2-23',
                    'descrizione' => 'Una gerarchia coerente a un livello da stories.parent_id viene applicata così com\'è',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketHierarchyStageTest.php::a coherent one-level hierarchy from stories.parent_id is applied as-is',
                ],
                [
                    'id' => 'F2-24',
                    'descrizione' => 'Una gerarchia a 2+ livelli viene appiattita sull\'antenato più in alto e segnalata',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketHierarchyStageTest.php::a 2+ level hierarchy is flattened onto the topmost ancestor and reported',
                ],
                [
                    'id' => 'F2-25',
                    'descrizione' => 'Un riferimento al genitore verso un ticket v2 inesistente viene azzerato e segnalato, non manda in crash lo stage',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketHierarchyStageTest.php::a parent reference to a non-existent v2 ticket is nulled out and reported, not crashed',
                ],
            ],
        ],
        [
            'titolo' => 'Tag e partecipanti dei ticket (US-207)',
            'test' => [
                [
                    'id' => 'F2-26',
                    'descrizione' => 'La pivot v1 ticket<->tag viene importata in v2, ignorando il lato Documentation',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketTagsStageTest.php::imports the v1 ticket<->tag pivot into v2, ignoring the Documentation side',
                ],
                [
                    'id' => 'F2-27',
                    'descrizione' => 'Un legame a un tag v2 inesistente viene segnalato, non manda in crash lo stage',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketTagsStageTest.php::a tag link referencing a non-existent v2 tag is reported, not crashed',
                ],
                [
                    'id' => 'F2-28',
                    'descrizione' => 'La pivot v1 ticket<->partecipante viene importata in v2',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketParticipantsStageTest.php::imports the v1 ticket<->participant pivot into v2',
                ],
                [
                    'id' => 'F2-29',
                    'descrizione' => 'Una partecipazione che referenzia un utente v2 inesistente viene segnalata, non manda in crash lo stage',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketParticipantsStageTest.php::a participation referencing a non-existent v2 user is reported, not crashed',
                ],
            ],
        ],
        [
            'titolo' => 'Log dei ticket (US-208)',
            'test' => [
                [
                    'id' => 'F2-30',
                    'descrizione' => 'Un delta di stato diventa un evento status_changed con from_status derivato dal log precedente',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketLogsStageTest.php::a status delta becomes a status_changed event with from_status derived from the previous log',
                ],
                [
                    'id' => 'F2-31',
                    'descrizione' => 'Un log con solo la chiave "watch" viene escluso e segnalato, non importato come ticket_log',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketLogsStageTest.php::a log with only the watch key is excluded and reported, not imported as a ticket_log',
                ],
                [
                    'id' => 'F2-32',
                    'descrizione' => 'Un log senza autore risolvibile ricade sull\'utente di sistema',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketLogsStageTest.php::a log without a resolvable author falls back to the system user',
                ],
                [
                    'id' => 'F2-33',
                    'descrizione' => 'Rieseguire lo stage sullo stesso dump è idempotente tramite import_mappings: la seconda esecuzione si limita a saltare',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketLogsStageTest.php::re-running the stage on the same dump is idempotent via import_mappings: second run only skips',
                ],
            ],
        ],
        [
            'titolo' => 'Visualizzazioni dei ticket (US-209)',
            'test' => [
                [
                    'id' => 'F2-34',
                    'descrizione' => 'I log "solo watch" dello stesso giorno si aggregano in un\'unica riga ticket_views',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketViewsStageTest.php::watch-only logs on the same day aggregate into a single ticket_views row',
                ],
                [
                    'id' => 'F2-35',
                    'descrizione' => 'I log non "solo watch" vengono esclusi e segnalati, non importati come ticket_view',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketViewsStageTest.php::logs that are not watch-only are excluded and reported, not imported as a ticket_view',
                ],
                [
                    'id' => 'F2-36',
                    'descrizione' => 'ticket_logs e ticket_views leggono lo stesso input story_logs senza alcuna sovrapposizione',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketViewsStageTest.php::ticket_logs and ticket_views read the same story_logs input with zero overlap',
                ],
                [
                    'id' => 'F2-37',
                    'descrizione' => 'Rieseguire lo stage sullo stesso dump è idempotente: la seconda esecuzione si limita a saltare, nessuna riga duplicata',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketViewsStageTest.php::re-running the stage on the same dump is idempotent: second run only skips, no duplicate rows',
                ],
            ],
        ],
        [
            'titolo' => 'Parser dei messaggi dei ticket (US-210)',
            'test' => [
                [
                    'id' => 'F2-38',
                    'descrizione' => 'Una catena di reply prependute reale (story id 1641 dal dump v1) viene scomposta in ordine cronologico',
                    'test_automatico' => 'tests/Unit/Import/Parsers/CustomerRequestParserTest.php::a real prepended reply chain (story id 1641 from the v1 dump) is decomposed in chronological order',
                ],
                [
                    'id' => 'F2-39',
                    'descrizione' => 'Una conversazione reale con quote inoltrata da Gmail (story id 3642) non viene scomposta: un unico blocco di fallback',
                    'test_automatico' => 'tests/Unit/Import/Parsers/CustomerRequestParserTest.php::a real Gmail forwarded-quote conversation (story id 3642) is not decomposed: single fallback block',
                ],
                [
                    'id' => 'F2-40',
                    'descrizione' => 'Un customer_request reale multi-risposta viene scomposto in messaggi cronologici',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketMessagesStageTest.php::a real multi-reply customer_request is decomposed into chronological messages',
                ],
                [
                    'id' => 'F2-41',
                    'descrizione' => 'Un tentativo di XSS nel corpo viene neutralizzato da TicketMessageSanitizer',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketMessagesStageTest.php::an XSS attempt in the body is neutralized by TicketMessageSanitizer',
                ],
                [
                    'id' => 'F2-42',
                    'descrizione' => 'Rieseguire lo stage sullo stesso dump è idempotente tramite import_mappings: la seconda esecuzione si limita a saltare',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketMessagesStageTest.php::re-running the stage on the same dump is idempotent via import_mappings: second run only skips',
                ],
            ],
        ],
        [
            'titolo' => 'Allegati (US-211)',
            'test' => [
                [
                    'id' => 'F2-43',
                    'descrizione' => 'Un media con il file fisico presente su disco viene allegato al primo messaggio legacy del suo ticket',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketAttachmentsStageTest.php::a media with its file present on disk is attached to the first legacy message of its ticket',
                ],
                [
                    'id' => 'F2-44',
                    'descrizione' => 'Un media il cui file fisico è mancante viene segnalato come orfano, non allegato',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketAttachmentsStageTest.php::a media whose physical file is missing is reported as orphan, not attached',
                ],
                [
                    'id' => 'F2-45',
                    'descrizione' => 'Un ticket senza alcun messaggio legacy ottiene un messaggio di sistema creato per ospitare i suoi allegati',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketAttachmentsStageTest.php::a ticket without any legacy message gets a system message created to host its attachments',
                ],
                [
                    'id' => 'F2-46',
                    'descrizione' => 'Rieseguire lo stage sullo stesso dump è idempotente tramite import_mappings su media.uuid: la seconda esecuzione si limita a saltare',
                    'test_automatico' => 'tests/Feature/Import/Stages/TicketAttachmentsStageTest.php::re-running the stage on the same dump is idempotent via import_mappings on media.uuid: second run only skips',
                ],
            ],
        ],
        [
            'titolo' => 'Report di attività (US-212)',
            'test' => [
                [
                    'id' => 'F2-47',
                    'descrizione' => 'Un report v1 di proprietà di un utente viene importato in v2 con l\'id preservato e la locale del proprietario derivata',
                    'test_automatico' => 'tests/Feature/Import/Stages/ActivityReportsStageTest.php::imports a v1 user-owned report into v2 with the id preserved and the owner locale derived',
                ],
                [
                    'id' => 'F2-48',
                    'descrizione' => 'Un report v1 ambiguo (con sia customer_id che organization_id impostati) viene saltato e segnalato, senza mai violare il CHECK sul proprietario',
                    'test_automatico' => 'tests/Feature/Import/Stages/ActivityReportsStageTest.php::an ambiguous v1 report (both customer_id and organization_id set) is skipped and reported, never violating the owner CHECK',
                ],
                [
                    'id' => 'F2-49',
                    'descrizione' => 'La pivot v1 activity_report<->story viene importata in v2 come activity_report_ticket',
                    'test_automatico' => 'tests/Feature/Import/Stages/ActivityReportTicketsStageTest.php::imports the v1 activity_report<->story pivot into v2 as activity_report_ticket',
                ],
                [
                    'id' => 'F2-50',
                    'descrizione' => 'Un\'associazione che referenzia un report di attività inesistente viene saltata e segnalata',
                    'test_automatico' => 'tests/Feature/Import/Stages/ActivityReportTicketsStageTest.php::an association referencing a non-existent activity report is skipped and reported',
                ],
            ],
        ],
        [
            'titolo' => 'Opportunità e punteggi di fundraising (US-213)',
            'test' => [
                [
                    'id' => 'F2-51',
                    'descrizione' => 'Un\'opportunità di fundraising v1 viene importata in v2 con l\'id preservato e le colonne mappate',
                    'test_automatico' => 'tests/Feature/Import/Stages/FundraisingOpportunitiesStageTest.php::imports a v1 fundraising opportunity into v2 with the id preserved and columns mapped',
                ],
                [
                    'id' => 'F2-52',
                    'descrizione' => 'Un\'esecuzione ripetuta non sovrascrive mai evaluated_by/evaluated_at/i totali di valutazione impostati da un uso reale di v2 dopo l\'import',
                    'test_automatico' => 'tests/Feature/Import/Stages/FundraisingOpportunitiesStageTest.php::a re-run never overwrites evaluated_by/evaluated_at/evaluation totals set by real v2 usage after import',
                ],
                [
                    'id' => 'F2-53',
                    'descrizione' => 'Una colonna v1 evaluation_*_score con un valore nel range diventa una riga fundraising_evaluation_scores',
                    'test_automatico' => 'tests/Feature/Import/Stages/FundraisingScoresStageTest.php::a v1 evaluation_*_score column with a value in range becomes a fundraising_evaluation_scores row',
                ],
                [
                    'id' => 'F2-54',
                    'descrizione' => 'Un punteggio v1 fuori range viene troncato al range del catalogo criteri e il troncamento viene segnalato',
                    'test_automatico' => 'tests/Feature/Import/Stages/FundraisingScoresStageTest.php::an out-of-range v1 score is clamped to the criterion catalog range and the clamp is reported',
                ],
                [
                    'id' => 'F2-55',
                    'descrizione' => 'Un\'opportunità referenziata dalle colonne v1 evaluation_* ma assente in v2 viene saltata, non manda in crash lo stage',
                    'test_automatico' => 'tests/Feature/Import/Stages/FundraisingScoresStageTest.php::an opportunity referenced by v1 evaluation columns but absent from v2 is skipped, not crashed',
                ],
            ],
        ],
        [
            'titolo' => 'Progetti e partner di fundraising (US-214)',
            'test' => [
                [
                    'id' => 'F2-56',
                    'descrizione' => 'Un progetto di fundraising v1 viene importato in v2 con l\'id preservato e le colonne mappate',
                    'test_automatico' => 'tests/Feature/Import/Stages/FundraisingProjectsStageTest.php::imports a v1 fundraising project into v2 with the id preserved and columns mapped',
                ],
                [
                    'id' => 'F2-57',
                    'descrizione' => 'Un progetto i cui lead_user_id/responsible_user_id non esistono in v2 vengono azzerati, non saltati',
                    'test_automatico' => 'tests/Feature/Import/Stages/FundraisingProjectsStageTest.php::a project whose lead_user_id/responsible_user_id do not exist in v2 are nulled, not skipped',
                ],
                [
                    'id' => 'F2-58',
                    'descrizione' => 'La pivot v1 progetto di fundraising<->partner viene importata in v2',
                    'test_automatico' => 'tests/Feature/Import/Stages/FundraisingPartnersStageTest.php::imports the v1 fundraising project<->partner pivot into v2',
                ],
                [
                    'id' => 'F2-59',
                    'descrizione' => 'Un partner che referenzia un progetto di fundraising v2 inesistente viene segnalato, non manda in crash lo stage',
                    'test_automatico' => 'tests/Feature/Import/Stages/FundraisingPartnersStageTest.php::a partner referencing a non-existent v2 fundraising project is reported, not crashed',
                ],
            ],
        ],
        [
            'titolo' => 'Derive (US-215)',
            'test' => [
                [
                    'id' => 'F2-60',
                    'descrizione' => 'released_at viene ricostruito a partire dalla transizione status_changed in ticket_logs, quando mancante',
                    'test_automatico' => 'tests/Feature/Import/Stages/DeriveStageTest.php::backfills released_at from the ticket_logs status_changed transition, when missing',
                ],
                [
                    'id' => 'F2-61',
                    'descrizione' => 'worked_minutes e ticket_work_logs vengono ricalcolati da un intervallo "progress" in ticket_logs, riusando RecalculateWorkedTime',
                    'test_automatico' => 'tests/Feature/Import/Stages/DeriveStageTest.php::recomputes worked_minutes and ticket_work_logs from a progress interval in ticket_logs, reusing RecalculateWorkedTime',
                ],
                [
                    'id' => 'F2-62',
                    'descrizione' => 'Vengono rigenerati slug finali univoci per tag e documentation_pages, con suffisso numerico sui duplicati',
                    'test_automatico' => 'tests/Feature/Import/Stages/DeriveStageTest.php::regenerates unique final slugs for tags and documentation_pages, numeric suffix on duplicates',
                ],
                [
                    'id' => 'F2-63',
                    'descrizione' => 'Viene generato un email_thread per ogni ticket con una conversazione importata',
                    'test_automatico' => 'tests/Feature/Import/Stages/DeriveStageTest.php::generates one email_thread per ticket with an imported conversation',
                ],
                [
                    'id' => 'F2-64',
                    'descrizione' => 'Rieseguire derive sullo stesso stato è idempotente: la seconda esecuzione si limita a saltare',
                    'test_automatico' => 'tests/Feature/Import/Stages/DeriveStageTest.php::re-running derive on the same state is idempotent: second run only skips',
                ],
            ],
        ],
        [
            'titolo' => 'Comando v1:validate (US-216)',
            'test' => [
                [
                    'id' => 'F2-65',
                    'descrizione' => 'Un ticket entro la tolleranza del 5% sulle ore lavorate viene classificato come conforme',
                    'test_automatico' => 'tests/Unit/Import/Validation/WorkedHoursDeviationAnalyzerTest.php::classifica un ticket entro la tolleranza del 5%',
                ],
                [
                    'id' => 'F2-66',
                    'descrizione' => 'Un ticket oltre la tolleranza del 5% viene elencato con lo scostamento percentuale',
                    'test_automatico' => 'tests/Unit/Import/Validation/WorkedHoursDeviationAnalyzerTest.php::elenca un ticket oltre la tolleranza del 5% con lo scostamento percentuale',
                ],
                [
                    'id' => 'F2-67',
                    'descrizione' => 'Il comando ha successo e riporta OK quando i conteggi v1/v2 e i controlli di integrità coincidono',
                    'test_automatico' => 'tests/Feature/Console/V1ValidateCommandTest.php::succeeds and reports OK when v1/v2 counts and integrity checks match',
                ],
                [
                    'id' => 'F2-68',
                    'descrizione' => 'Il comando fallisce quando il conteggio di un\'entità a id preservato non corrisponde',
                    'test_automatico' => 'tests/Feature/Console/V1ValidateCommandTest.php::fails when an id-preserved entity count does not match',
                ],
                [
                    'id' => 'F2-69',
                    'descrizione' => 'Una seconda esecuzione consecutiva di v1:import non crea/aggiorna nulla su nessuno stage registrato',
                    'test_automatico' => 'tests/Feature/Console/V1ImportPipelineIdempotencyTest.php::a second consecutive v1:import run creates/updates nothing on every registered stage',
                ],
            ],
        ],
        [
            'titolo' => 'Password fissa fuori produzione (US-217, ridefinito da US-R08)',
            'test' => [
                [
                    'id' => 'F2-70',
                    'descrizione' => 'Con --anonymize nome/email/contenuti restano sempre quelli reali del dump v1, mai alterati',
                    'test_automatico' => 'tests/Feature/Import/Stages/UsersStageTest.php::--anonymize never changes name or email: they always stay the real ones from v1',
                ],
                [
                    'id' => 'F2-71',
                    'descrizione' => 'Con --anonymize la password è sempre l\'hash di un valore fisso noto, mai l\'hash v1 reale',
                    'test_automatico' => 'tests/Unit/Import/Security/FixedPasswordHasherTest.php::hash returns a Laravel hash of the fixed known password, never the raw string',
                ],
                [
                    'id' => 'F2-72',
                    'descrizione' => 'Un\'email verso un dominio reale non in allowlist viene bloccata fuori produzione',
                    'test_automatico' => 'tests/Feature/Support/Mail/BlockRealRecipientsOutsideProductionTest.php::an email to a real, non-allowlisted domain is blocked outside production',
                ],
                [
                    'id' => 'F2-73',
                    'descrizione' => 'Il guard viene bypassato del tutto in produzione, destinatari reali inclusi',
                    'test_automatico' => 'tests/Feature/Support/Mail/BlockRealRecipientsOutsideProductionTest.php::the guard is bypassed entirely in production, real recipients included',
                ],
            ],
        ],
        [
            'titolo' => 'Fixture CI (US-218)',
            'test' => [
                [
                    'id' => 'F2-74',
                    'descrizione' => 'Le email duplicate case-insensitive vengono segnalate senza far fallire lo stage (deviazione coperta dalla fixture CI)',
                    'test_automatico' => 'tests/Feature/Import/Stages/UsersStageTest.php::reports case-insensitive duplicate emails without failing the stage',
                ],
            ],
        ],
    ],
];
