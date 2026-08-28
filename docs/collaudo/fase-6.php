<?php

declare(strict_types=1);

// Manifest di tracciabilità per il collaudo (UAT) di Fase 6 (Portale cliente e
// rifinitura: dashboard/navigazione/ricerca/badge cliente, preferenze di notifica,
// MFA, impersonation, disattivazione utente, restyling WorkBoard, automazioni
// schedulate T3-T7, Mailable E8/E10/E11 — US-601..US-618). Stessa disciplina di
// Fase 5 (docs/collaudo/fase-5.php): topic raggruppati per area funzionale del PRD
// (§6.7 portale cliente, §8.4/§8.6/§8.7 navigazione/WorkBoard/UI, §9 sicurezza,
// §10.2 automazioni schedulate, §7.5.2 mailable), in ordine di priorità US-601 ->
// US-618, più un topic finale dedicato al checkpoint di fine fase (US-618). US-617
// (documentazione, nessun test automatico associato) non ha un topic proprio in
// questo manifest, come già per le story di sola documentazione nelle fasi
// precedenti. Ogni voce collega un criterio di accettazione a un test automatico
// REALMENTE esistente in tests/ (verificato da `collaudo:verify-manifest 6`). Fonte
// delle story: scripts/ralph/prd.json (Fase 6).
//
// Gotcha noto (già documentato per Fase 4/5, CLAUDE.md "Processo di collaudo"): un
// apostrofo in una descrizione di test Pest referenziata qui romperebbe il
// confronto byte-per-byte di `collaudo:verify-manifest` (il file PHP sorgente del
// test contiene l'apostrofo come sequenza letterale `\'` dentro una stringa a apici
// singoli, mentre lo stesso apostrofo scritto in QUESTO manifest verrebbe
// normalizzato da PHP a un apostrofo semplice, senza backslash, prima del
// confronto). Dove l'unico test Pest pertinente ha un apostrofo nella propria
// descrizione (es. "la pagina profilo espone la gestione della MFA per l'utente
// autenticato"), il riferimento `test_automatico` qui sotto è un percorso NUDO
// (solo il file, senza `::descrizione`): resta un riferimento a un test automatico
// realmente esistente (verificato da `collaudo:verify-manifest` tramite
// `file_exists`), la descrizione completa in italiano resta comunque leggibile nel
// campo `descrizione` di questo manifest (mai troncata). Questo file è puro dato:
// nessuna logica.

return [
    'fase' => '6',
    'titolo' => 'Fase 6 (Portale cliente e rifinitura)',
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
            'titolo' => 'Dashboard cliente — card ticket/documentazione/report/fundraising, tutte scoped al cliente autenticato (§6.7.3, US-601)',
            'test' => [
                [
                    'id' => 'F6-01',
                    'descrizione' => 'Un utente non-customer non può accedere alla dashboard cliente',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::a non-customer cannot access the customer dashboard',
                ],
                [
                    'id' => 'F6-02',
                    'descrizione' => 'Un customer può accedere alla propria dashboard',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::a customer can access the customer dashboard',
                ],
                [
                    'id' => 'F6-03',
                    'descrizione' => 'La card ticket aperti mostra il conteggio corretto per il cliente corrente, scoped ai soli propri ticket',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the open tickets card shows the correct count for the current customer, scoped to own tickets',
                ],
                [
                    'id' => 'F6-04',
                    'descrizione' => 'La card ticket che richiedono una risposta elenca solo i propri ticket in stato waiting/problem',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the tickets awaiting response card lists only own tickets in waiting/problem status',
                ],
                [
                    'id' => 'F6-05',
                    'descrizione' => 'Un cliente senza ticket aperti e senza ticket in attesa di risposta vede stati vuoti espliciti, non un errore o una sezione silenziosa',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::a customer with no open tickets and no tickets awaiting response sees explicit empty states',
                ],
                [
                    'id' => 'F6-06',
                    'descrizione' => 'La card documentazione mostra la documentazione customer recente, con stato vuoto quando assente, e non mostra mai pagine interne',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the documentation card shows recent customer documentation, empty state when none',
                ],
                [
                    'id' => 'F6-07',
                    'descrizione' => 'I link drive_url/drive_budget_url compaiono solo quando valorizzati sull\'utente autenticato',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php',
                ],
                [
                    'id' => 'F6-08',
                    'descrizione' => 'La card report attività mostra i propri report, con stato vuoto quando assenti',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the activity reports card shows the customer own reports, empty state when none',
                ],
                [
                    'id' => 'F6-09',
                    'descrizione' => 'La card progetti fundraising mostra i progetti in cui il cliente è coinvolto, con stato vuoto quando assenti',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::the fundraising projects card shows involved projects, empty state when none',
                ],
                [
                    'id' => 'F6-10',
                    'descrizione' => 'Un cliente con dati reali su ogni card li vede tutti, scoped a sé stesso, senza alcuno stato vuoto residuo',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::a customer with real data across every card sees all of it scoped to themselves',
                ],
                [
                    'id' => 'F6-11',
                    'descrizione' => 'Nessun riferimento a un link di chat di supporto compare mai nella dashboard cliente (help_desk_chat_url non confermato)',
                    'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::no reference to a support chat link is ever shown on the customer dashboard',
                ],
            ],
        ],
        [
            'titolo' => 'Navigazione "Area cliente" e landing per ruolo (§8.4, §6.7.2, US-602)',
            'test' => [
                [
                    'id' => 'F6-12',
                    'descrizione' => 'Lo staff (admin/manager/developer) che atterra sulla dashboard viene reindirizzato alla WorkBoard, invariato rispetto a prima di questa story',
                    'test_automatico' => 'tests/Feature/Filament/Pages/DashboardTest.php::staff (admin/manager/developer) landing on the dashboard is redirected to the work board',
                ],
                [
                    'id' => 'F6-13',
                    'descrizione' => 'Un cliente che atterra sulla dashboard viene reindirizzato alla CustomerDashboard (US-601)',
                    'test_automatico' => 'tests/Feature/Filament/Pages/DashboardTest.php::a customer landing on the dashboard is redirected to the customer dashboard',
                ],
                [
                    'id' => 'F6-14',
                    'descrizione' => 'Un membro del team fundraising che atterra sulla dashboard viene reindirizzato all\'elenco opportunità',
                    'test_automatico' => 'tests/Feature/Filament/Pages/DashboardTest.php',
                ],
                [
                    'id' => 'F6-15',
                    'descrizione' => 'Un customer vede in navigazione SOLO il gruppo "Area cliente", nessuna voce dei gruppi staff',
                    'test_automatico' => 'tests/Feature/Filament/CustomerAreaNavigationTest.php::a customer sees only the Area cliente navigation group',
                ],
                [
                    'id' => 'F6-16',
                    'descrizione' => 'Uno staff member non vede mai il gruppo di navigazione "Area cliente"',
                    'test_automatico' => 'tests/Feature/Filament/CustomerAreaNavigationTest.php::a staff member does not see the Area cliente navigation group',
                ],
                [
                    'id' => 'F6-17',
                    'descrizione' => 'Una voce di navigazione riservata allo staff (es. Mailpit) resta nascosta a un customer, anche quando visibile per ambiente/URL configurato',
                    'test_automatico' => 'tests/Feature/Filament/Mail/MailpitNavigationItemTest.php::the Mailpit item is hidden from a customer even in local with the URL configured',
                ],
            ],
        ],
        [
            'titolo' => 'Ricerca globale — id/titolo/richiedente/corpo messaggio, scoped alla Policy dell\'utente (§8.7, US-603)',
            'test' => [
                [
                    'id' => 'F6-18',
                    'descrizione' => 'La ricerca globale trova un ticket per id',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketGlobalSearchTest.php::global search finds a ticket by id',
                ],
                [
                    'id' => 'F6-19',
                    'descrizione' => 'La ricerca globale trova un ticket per titolo',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketGlobalSearchTest.php::global search finds a ticket by title',
                ],
                [
                    'id' => 'F6-20',
                    'descrizione' => 'La ricerca globale trova un ticket per nome o email del richiedente',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketGlobalSearchTest.php::global search finds a ticket by requester name or email',
                ],
                [
                    'id' => 'F6-21',
                    'descrizione' => 'La ricerca globale trova un ticket per un termine presente solo nel corpo di un messaggio',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketGlobalSearchTest.php::global search finds a ticket by a term only present in a message body',
                ],
                [
                    'id' => 'F6-22',
                    'descrizione' => 'Un cliente non trova nei risultati della ricerca globale ticket appartenenti ad altri richiedenti',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketGlobalSearchTest.php::a customer does not find tickets belonging to other requesters in global search results',
                ],
            ],
        ],
        [
            'titolo' => 'Badge di navigazione con cache — "In attesa"/"Problemi"/"Da testare" (§8.4, US-604)',
            'test' => [
                [
                    'id' => 'F6-23',
                    'descrizione' => 'Il badge di navigazione mostra il conteggio combinato corretto e il tooltip col dettaglio per categoria',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketNavigationBadgeTest.php::navigation badge shows the correct combined count and tooltip breakdown',
                ],
                [
                    'id' => 'F6-24',
                    'descrizione' => 'Il badge è assente quando non c\'è nulla che richieda attenzione',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketNavigationBadgeTest.php',
                ],
                [
                    'id' => 'F6-25',
                    'descrizione' => 'I conteggi del badge sono cachati tra richieste entro il TTL: una seconda richiesta non genera una nuova query',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketNavigationBadgeTest.php::navigation badge counts are cached across requests within the ttl',
                ],
                [
                    'id' => 'F6-26',
                    'descrizione' => 'I conteggi del badge sono scoped per utente e non trapelano tra chiavi di cache di utenti diversi',
                    'test_automatico' => 'tests/Feature/Filament/Ticketing/TicketNavigationBadgeTest.php::navigation badge counts are scoped per user and do not leak across cache keys',
                ],
            ],
        ],
        [
            'titolo' => 'Schermata preferenze di notifica — Page personale su notification_preferences, per tipo/canale (§6.7.4, US-605)',
            'test' => [
                [
                    'id' => 'F6-27',
                    'descrizione' => 'Ogni utente autenticato, a prescindere dal ruolo, può accedere alla pagina preferenze',
                    'test_automatico' => 'tests/Feature/Filament/NotificationPreferencesPageTest.php::any authenticated user can access the page, regardless of role',
                ],
                [
                    'id' => 'F6-28',
                    'descrizione' => 'Un cliente non vede mai un tipo di comunicazione che riguarda solo lo staff (es. E6 "Assegnazione")',
                    'test_automatico' => 'tests/Feature/Filament/NotificationPreferencesPageTest.php::a customer never sees a notification type that only applies to staff (e.g. TicketAssigned)',
                ],
                [
                    'id' => 'F6-29',
                    'descrizione' => 'Un membro dello staff non vede mai un tipo di comunicazione che riguarda solo i clienti',
                    'test_automatico' => 'tests/Feature/Filament/NotificationPreferencesPageTest.php::a staff member never sees a notification type that only applies to customers (e.g. TicketReceivedByEmail)',
                ],
                [
                    'id' => 'F6-30',
                    'descrizione' => 'Un tipo senza riga di preferenza esistente carica come abilitato di default al primo accesso alla pagina',
                    'test_automatico' => 'tests/Feature/Filament/NotificationPreferencesPageTest.php::a type with no existing preference row defaults to enabled when the page loads',
                ],
                [
                    'id' => 'F6-31',
                    'descrizione' => 'Un tipo con una riga di preferenza disabilitata già esistente carica come disabilitato',
                    'test_automatico' => 'tests/Feature/Filament/NotificationPreferencesPageTest.php::a type with an existing disabled preference row loads as disabled',
                ],
                [
                    'id' => 'F6-32',
                    'descrizione' => 'Salvare persiste una riga updateOrCreate scoped al solo utente corrente, mai a un altro utente',
                    'test_automatico' => 'tests/Feature/Filament/NotificationPreferencesPageTest.php::saving persists an updateOrCreate row scoped to the current user only, never another user',
                ],
                [
                    'id' => 'F6-33',
                    'descrizione' => 'Salvare non scrive righe per tipi di comunicazione che non si applicano al ruolo corrente',
                    'test_automatico' => 'tests/Feature/Filament/NotificationPreferencesPageTest.php::saving does not write rows for notification types that do not apply to the current role',
                ],
                [
                    'id' => 'F6-34',
                    'descrizione' => 'Disabilitare una preferenza dalla pagina reale delle preferenze di notifica impedisce l\'invio di una comunicazione di quel tipo (verifica end-to-end UI -> invio)',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/SendOutboundTicketMailTest.php::does not queue the mailable after disabling the preference via the NotificationPreferences UI page (US-605)',
                ],
            ],
        ],
        [
            'titolo' => 'Autenticazione MFA opzionale, abilitabile per ruolo (§6.7.2, US-606)',
            'test' => [
                [
                    'id' => 'F6-35',
                    'descrizione' => 'Un ruolo per cui la MFA è obbligatoria non può accedere al pannello senza averla configurata',
                    'test_automatico' => 'tests/Feature/Filament/Auth/MultiFactorAuthenticationTest.php::un ruolo per cui la MFA è obbligatoria non può accedere al pannello senza averla configurata',
                ],
                [
                    'id' => 'F6-36',
                    'descrizione' => 'Un ruolo per cui la MFA è facoltativa accede normalmente senza averla configurata',
                    'test_automatico' => 'tests/Feature/Filament/Auth/MultiFactorAuthenticationTest.php::un ruolo per cui la MFA è facoltativa accede normalmente senza averla configurata',
                ],
                [
                    'id' => 'F6-37',
                    'descrizione' => 'Un ruolo per cui la MFA è obbligatoria accede normalmente una volta che l\'ha configurata',
                    'test_automatico' => 'tests/Feature/Filament/Auth/MultiFactorAuthenticationTest.php::un ruolo per cui la MFA è obbligatoria accede normalmente una volta configurata',
                ],
                [
                    'id' => 'F6-38',
                    'descrizione' => 'Senza alcun ruolo configurato come obbligatorio, nessun utente è forzato alla MFA',
                    'test_automatico' => 'tests/Feature/Filament/Auth/MultiFactorAuthenticationTest.php::senza ruoli configurati come obbligatori nessun utente è forzato alla MFA',
                ],
                [
                    'id' => 'F6-39',
                    'descrizione' => 'La pagina profilo espone la gestione della MFA (setup/recovery) per l\'utente autenticato',
                    'test_automatico' => 'tests/Feature/Filament/Auth/MultiFactorAuthenticationTest.php',
                ],
                [
                    'id' => 'F6-40',
                    'descrizione' => 'Un login con MFA attiva mostra la sfida di verifica e si completa solo fornendo un codice valido',
                    'test_automatico' => 'tests/Feature/Filament/Auth/MultiFactorAuthenticationTest.php::un login con MFA attiva mostra la sfida e si completa solo con un codice valido',
                ],
                [
                    'id' => 'F6-41',
                    'descrizione' => 'Un login con MFA attiva e un codice errato non completa l\'accesso',
                    'test_automatico' => 'tests/Feature/Filament/Auth/MultiFactorAuthenticationTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Impersonation — azione riservata, banner sempre visibile, azione loggata (§6.7.2, US-607)',
            'test' => [
                [
                    'id' => 'F6-42',
                    'descrizione' => 'Un admin con user.impersonate vede l\'azione "Impersona" nella tabella utenti',
                    'test_automatico' => 'tests/Feature/Filament/Identity/ImpersonationTest.php::an admin with user.impersonate sees the Impersona action on the users table',
                ],
                [
                    'id' => 'F6-43',
                    'descrizione' => 'Un admin con user.impersonate vede l\'azione "Impersona" nella pagina di visualizzazione utente',
                    'test_automatico' => 'tests/Feature/Filament/Identity/ImpersonationTest.php::an admin with user.impersonate sees the Impersona action on the user view page',
                ],
                [
                    'id' => 'F6-44',
                    'descrizione' => 'Un utente senza user.impersonate non vede mai l\'azione "Impersona"',
                    'test_automatico' => 'tests/Feature/Filament/Identity/ImpersonationTest.php::a user without user.impersonate does not see the Impersona action',
                ],
                [
                    'id' => 'F6-45',
                    'descrizione' => 'Un admin può impersonare un utente, il cambio è loggato (chi ha impersonato chi, quando), il banner è visibile con azione per uscire, e uscire ripristina la sessione originale',
                    'test_automatico' => 'tests/Feature/Filament/Identity/ImpersonationTest.php::an admin can impersonate a user, the switch is logged, and leaving restores the original session',
                ],
                [
                    'id' => 'F6-46',
                    'descrizione' => 'Un utente disattivato non può essere impersonato',
                    'test_automatico' => 'tests/Feature/Filament/Identity/ImpersonationTest.php::a deactivated user cannot be impersonated',
                ],
            ],
        ],
        [
            'titolo' => 'Disattivazione e riattivazione utente — login bloccato, esclusione dai picker, storico intatto (§6.7.5, US-608)',
            'test' => [
                [
                    'id' => 'F6-47',
                    'descrizione' => 'Un admin con user.deactivate vede l\'azione di disattivazione/riattivazione nella tabella utenti e nella pagina di visualizzazione',
                    'test_automatico' => 'tests/Feature/Filament/Identity/UserDeactivationTest.php::an admin with user.deactivate sees the toggle action on the users table and the view page',
                ],
                [
                    'id' => 'F6-48',
                    'descrizione' => 'Un utente senza user.deactivate non vede l\'azione di disattivazione/riattivazione',
                    'test_automatico' => 'tests/Feature/Filament/Identity/UserDeactivationTest.php::a user without user.deactivate does not see the toggle action',
                ],
                [
                    'id' => 'F6-49',
                    'descrizione' => 'L\'azione disattiva un utente attivo e riattiva un utente disattivato (deactivated_at valorizzato/azzerato)',
                    'test_automatico' => 'tests/Feature/Filament/Identity/UserDeactivationTest.php::the toggle action deactivates an active user and reactivates a deactivated one',
                ],
                [
                    'id' => 'F6-50',
                    'descrizione' => 'Disattivare un utente non tocca la relazione storica assegnatario/richiedente/tester su un ticket esistente',
                    'test_automatico' => 'tests/Feature/Filament/Identity/UserDeactivationTest.php::deactivating a user does not touch the historical assignee/requester/tester relation on an existing ticket',
                ],
                [
                    'id' => 'F6-51',
                    'descrizione' => 'Un utente disattivato non è più selezionabile come partner di un progetto fundraising',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/PartnersRelationManagerTest.php::un utente disattivato non è allegabile come partner (US-608)',
                ],
                [
                    'id' => 'F6-52',
                    'descrizione' => 'Un utente disattivato non riceve più comunicazioni email (la riga outbound viene marcata soppressa)',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/SendOutboundTicketMailTest.php::does not queue the mailable and marks the row suppressed when the recipient is deactivated (US-608)',
                ],
                [
                    'id' => 'F6-53',
                    'descrizione' => 'Un utente disattivato non può accedere al pannello nemmeno con un ruolo valido (login bloccato)',
                    'test_automatico' => 'tests/Feature/Domain/Identity/PanelAccessTest.php::a deactivated user cannot access the panel even with a valid role',
                ],
                [
                    'id' => 'F6-54',
                    'descrizione' => 'Lo scope "attivi" esclude gli utenti disattivati da una query di selezione utenti (base dei picker di assegnazione/tester/destinatari)',
                    'test_automatico' => 'tests/Feature/Domain/Identity/PanelAccessTest.php::the active scope excludes deactivated users from a user selection query',
                ],
            ],
        ],
        [
            'titolo' => 'Rifinitura della WorkBoard secondo il design system — stesso paradigma a colonne, card invariate, selettore assegnatario, nessuna regressione N+1 (§8.6, US-609)',
            'test' => [
                [
                    'id' => 'F6-55',
                    'descrizione' => 'Un customer senza i permessi di visualizzazione ticket non può accedere alla WorkBoard',
                    'test_automatico' => 'tests/Feature/Filament/Pages/WorkBoardTest.php::a customer without ticket view any/assigned permissions cannot access the work board',
                ],
                [
                    'id' => 'F6-56',
                    'descrizione' => 'Un developer con il permesso sui campi interni può accedere alla WorkBoard',
                    'test_automatico' => 'tests/Feature/Filament/Pages/WorkBoardTest.php::a developer with the internal fields permission can access the work board',
                ],
                [
                    'id' => 'F6-57',
                    'descrizione' => 'Le colonne raggruppano i ticket visibili per stato e nascondono i ticket fuori dallo scope di visibilità',
                    'test_automatico' => 'tests/Feature/Filament/Pages/WorkBoardTest.php::columns group visible tickets by status and hide tickets outside the visibility scope',
                ],
                [
                    'id' => 'F6-58',
                    'descrizione' => 'Il selettore di assegnatario restringe la board a un singolo collega',
                    'test_automatico' => 'tests/Feature/Filament/Pages/WorkBoardTest.php::the assignee selector narrows the board to a single colleague',
                ],
                [
                    'id' => 'F6-59',
                    'descrizione' => 'Le opzioni del selettore di assegnatario elencano solo membri dello staff (admin/manager/developer), mai clienti',
                    'test_automatico' => 'tests/Feature/Filament/Pages/WorkBoardTest.php::assignee options only list staff members (admin/manager/developer), never customers',
                ],
                [
                    'id' => 'F6-60',
                    'descrizione' => 'Il nome cliente sulla card si risolve dall\'organizzazione del richiedente, con fallback sul nome del richiedente',
                    'test_automatico' => 'tests/Feature/Filament/Pages/WorkBoardTest.php',
                ],
                [
                    'id' => 'F6-61',
                    'descrizione' => 'Le colonne eseguono un numero costante di query indipendentemente dal volume di ticket: nessuna regressione N+1 per card introdotta dalla ristilizzazione',
                    'test_automatico' => 'tests/Feature/Filament/Pages/WorkBoardTest.php::columns run a constant number of queries regardless of ticket volume (no N+1 per card)',
                ],
                [
                    'id' => 'F6-62',
                    'descrizione' => 'L\'attività recente include solo i log dei ticket visibili all\'utente corrente',
                    'test_automatico' => 'tests/Feature/Filament/Pages/WorkBoardTest.php::recent activity only includes logs of tickets visible to the current user',
                ],
            ],
        ],
        [
            'titolo' => 'Automazioni schedulate T3/T4 — tickets:progress-to-todo e tickets:auto-close-released (§10.2, US-610)',
            'test' => [
                [
                    'id' => 'F6-63',
                    'descrizione' => 'tickets:progress-to-todo in --dry-run esamina i ticket progress senza transitarne alcuno',
                    'test_automatico' => 'tests/Feature/Console/TicketsProgressToTodoCommandTest.php::--dry-run examines progress tickets without transitioning any of them',
                ],
                [
                    'id' => 'F6-64',
                    'descrizione' => 'tickets:progress-to-todo transita ogni ticket progress a todo tramite la macchina a stati e lo logga come azione di sistema',
                    'test_automatico' => 'tests/Feature/Console/TicketsProgressToTodoCommandTest.php::transitions every progress ticket to todo via the state machine and logs it as a system action',
                ],
                [
                    'id' => 'F6-65',
                    'descrizione' => 'tickets:progress-to-todo non tocca ticket in uno stato diverso da progress',
                    'test_automatico' => 'tests/Feature/Console/TicketsProgressToTodoCommandTest.php::does not touch tickets in a status other than progress',
                ],
                [
                    'id' => 'F6-66',
                    'descrizione' => 'Rieseguire tickets:progress-to-todo è idempotente: un ticket già todo non viene transitato di nuovo',
                    'test_automatico' => 'tests/Feature/Console/TicketsProgressToTodoCommandTest.php::re-running the command is idempotent: a ticket already todo is not transitioned again',
                ],
                [
                    'id' => 'F6-67',
                    'descrizione' => 'tickets:auto-close-released in --dry-run esamina i ticket released senza chiuderne alcuno',
                    'test_automatico' => 'tests/Feature/Console/TicketsAutoCloseReleasedCommandTest.php::--dry-run examines released tickets without closing any of them',
                ],
                [
                    'id' => 'F6-68',
                    'descrizione' => 'tickets:auto-close-released chiude un ticket released da almeno la soglia configurata di giorni lavorativi e valorizza done_at',
                    'test_automatico' => 'tests/Feature/Console/TicketsAutoCloseReleasedCommandTest.php::closes a ticket released for at least the configured working days threshold and stamps done_at',
                ],
                [
                    'id' => 'F6-69',
                    'descrizione' => 'tickets:auto-close-released non chiude un ticket rilasciato più recentemente della soglia',
                    'test_automatico' => 'tests/Feature/Console/TicketsAutoCloseReleasedCommandTest.php::does not close a ticket released more recently than the threshold',
                ],
                [
                    'id' => 'F6-70',
                    'descrizione' => 'tickets:auto-close-released non tocca ticket in uno stato diverso da released',
                    'test_automatico' => 'tests/Feature/Console/TicketsAutoCloseReleasedCommandTest.php::does not touch tickets in a status other than released',
                ],
                [
                    'id' => 'F6-71',
                    'descrizione' => 'Rieseguire tickets:auto-close-released è idempotente: un ticket già done non viene transitato di nuovo',
                    'test_automatico' => 'tests/Feature/Console/TicketsAutoCloseReleasedCommandTest.php::re-running the command is idempotent: a ticket already done is not transitioned again',
                ],
                [
                    'id' => 'F6-72',
                    'descrizione' => 'La macchina a stati ammette la transizione released -> done sia per l\'assegnatario sia per l\'utente di sistema (automazione T4, US-610)',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketStateMachineTest.php::released to done is allowed for the assignee and for the system user (T4 automation, US-610)',
                ],
            ],
        ],
        [
            'titolo' => 'Automazioni schedulate T5/T7 — tickets:close-scrum e tickets:archive-scrum, compromesso conservativo su v1 (§10.2, US-611)',
            'test' => [
                [
                    'id' => 'F6-73',
                    'descrizione' => 'tickets:close-scrum in --dry-run esamina i ticket scrum creati oggi senza chiuderne alcuno',
                    'test_automatico' => 'tests/Feature/Console/TicketsCloseScrumCommandTest.php::--dry-run examines scrum tickets created today without closing any of them',
                ],
                [
                    'id' => 'F6-74',
                    'descrizione' => 'tickets:close-scrum chiude un ticket scrum creato oggi e valorizza done_at',
                    'test_automatico' => 'tests/Feature/Console/TicketsCloseScrumCommandTest.php::closes a scrum ticket created today and stamps done_at',
                ],
                [
                    'id' => 'F6-75',
                    'descrizione' => 'tickets:close-scrum chiude anche un ticket scrum aggiornato oggi pur se creato in precedenza',
                    'test_automatico' => 'tests/Feature/Console/TicketsCloseScrumCommandTest.php::closes a scrum ticket updated today even if created earlier',
                ],
                [
                    'id' => 'F6-76',
                    'descrizione' => 'tickets:close-scrum non tocca un ticket scrum né creato né aggiornato oggi',
                    'test_automatico' => 'tests/Feature/Console/TicketsCloseScrumCommandTest.php::does not touch a scrum ticket neither created nor updated today',
                ],
                [
                    'id' => 'F6-77',
                    'descrizione' => 'tickets:close-scrum non tocca un ticket non-scrum creato oggi',
                    'test_automatico' => 'tests/Feature/Console/TicketsCloseScrumCommandTest.php::does not touch a non-scrum ticket created today',
                ],
                [
                    'id' => 'F6-78',
                    'descrizione' => 'Rieseguire tickets:close-scrum è idempotente: un ticket scrum già done non viene transitato di nuovo',
                    'test_automatico' => 'tests/Feature/Console/TicketsCloseScrumCommandTest.php::re-running the command is idempotent: a scrum ticket already done is not transitioned again',
                ],
                [
                    'id' => 'F6-79',
                    'descrizione' => 'tickets:archive-scrum in --dry-run esamina i ticket scrum archiviabili senza archiviarne alcuno',
                    'test_automatico' => 'tests/Feature/Console/TicketsArchiveScrumCommandTest.php::--dry-run examines archivable scrum tickets without archiving any of them',
                ],
                [
                    'id' => 'F6-80',
                    'descrizione' => 'tickets:archive-scrum archivia un ticket scrum done da almeno la soglia configurata di giorni e lo logga (colonna additiva archived_at, mai una cancellazione o un cambio di stato)',
                    'test_automatico' => 'tests/Feature/Console/TicketsArchiveScrumCommandTest.php::archives a scrum ticket done for at least the configured threshold of days and logs it',
                ],
                [
                    'id' => 'F6-81',
                    'descrizione' => 'tickets:archive-scrum non archivia un ticket scrum reso done più di recente della soglia',
                    'test_automatico' => 'tests/Feature/Console/TicketsArchiveScrumCommandTest.php::does not archive a scrum ticket done more recently than the threshold',
                ],
                [
                    'id' => 'F6-82',
                    'descrizione' => 'tickets:archive-scrum non archivia un ticket scrum che non è done',
                    'test_automatico' => 'tests/Feature/Console/TicketsArchiveScrumCommandTest.php::does not archive a scrum ticket that is not done',
                ],
                [
                    'id' => 'F6-83',
                    'descrizione' => 'tickets:archive-scrum non archivia un ticket non-scrum reso done molto tempo fa',
                    'test_automatico' => 'tests/Feature/Console/TicketsArchiveScrumCommandTest.php::does not archive a non-scrum ticket done long ago',
                ],
                [
                    'id' => 'F6-84',
                    'descrizione' => 'Rieseguire tickets:archive-scrum è idempotente: un ticket già archiviato non viene archiviato di nuovo',
                    'test_automatico' => 'tests/Feature/Console/TicketsArchiveScrumCommandTest.php::re-running the command is idempotent: an already archived ticket is not archived again',
                ],
                [
                    'id' => 'F6-85',
                    'descrizione' => 'La macchina a stati ammette * -> done per l\'utente di sistema su un ticket scrum, e SOLO per l\'utente di sistema (T5, US-611)',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketStateMachineTest.php::any status to done is allowed for the system user on a scrum ticket, and only for the system user',
                ],
                [
                    'id' => 'F6-86',
                    'descrizione' => 'L\'utente di sistema non può spostare un ticket non-scrum a done tramite la transizione T5',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketStateMachineTest.php::the system user cannot move a non-scrum ticket to done via T5',
                ],
                [
                    'id' => 'F6-87',
                    'descrizione' => 'Il catalogo TicketLogEvent contiene esattamente gli 8 valori di §6.2.1 più il nuovo evento "archived" introdotto da US-611',
                    'test_automatico' => 'tests/Unit/Domain/Ticketing/TicketLogEventTest.php::contains exactly the 8 values of §6.2.1 plus "archived" (US-611)',
                ],
            ],
        ],
        [
            'titolo' => 'Automazione schedulata T6 — tickets:restore-waiting, soglia in giorni di calendario (§10.2, US-612)',
            'test' => [
                [
                    'id' => 'F6-88',
                    'descrizione' => 'tickets:restore-waiting in --dry-run esamina i ticket waiting ripristinabili senza ripristinarne alcuno',
                    'test_automatico' => 'tests/Feature/Console/TicketsRestoreWaitingCommandTest.php::--dry-run examines restorable waiting tickets without restoring any of them',
                ],
                [
                    'id' => 'F6-89',
                    'descrizione' => 'tickets:restore-waiting ripristina un ticket in attesa da esattamente la soglia configurata di giorni di calendario',
                    'test_automatico' => 'tests/Feature/Console/TicketsRestoreWaitingCommandTest.php::restores a ticket waiting for exactly the configured threshold of calendar days',
                ],
                [
                    'id' => 'F6-90',
                    'descrizione' => 'tickets:restore-waiting ripristina un ticket in attesa da più della soglia configurata di giorni di calendario',
                    'test_automatico' => 'tests/Feature/Console/TicketsRestoreWaitingCommandTest.php::restores a ticket waiting for more than the configured threshold of calendar days',
                ],
                [
                    'id' => 'F6-91',
                    'descrizione' => 'tickets:restore-waiting non ripristina un ticket in attesa da un giorno in meno della soglia configurata',
                    'test_automatico' => 'tests/Feature/Console/TicketsRestoreWaitingCommandTest.php::does not restore a ticket waiting for one day less than the configured threshold',
                ],
                [
                    'id' => 'F6-92',
                    'descrizione' => 'tickets:restore-waiting non tocca ticket in uno stato diverso da waiting',
                    'test_automatico' => 'tests/Feature/Console/TicketsRestoreWaitingCommandTest.php::does not touch tickets in a status other than waiting',
                ],
                [
                    'id' => 'F6-93',
                    'descrizione' => 'tickets:restore-waiting non tocca un ticket in waiting privo di uno stato precedente',
                    'test_automatico' => 'tests/Feature/Console/TicketsRestoreWaitingCommandTest.php::does not touch a waiting ticket without a previous status',
                ],
                [
                    'id' => 'F6-94',
                    'descrizione' => 'Rieseguire tickets:restore-waiting è idempotente: un ticket già ripristinato non viene ritoccato',
                    'test_automatico' => 'tests/Feature/Console/TicketsRestoreWaitingCommandTest.php::re-running the command is idempotent: a restored ticket is not touched again',
                ],
            ],
        ],
        [
            'titolo' => 'Automazione schedulata — timetracking:aggregate-daily, orchestrazione mancante del job esistente (§10.2, US-613)',
            'test' => [
                [
                    'id' => 'F6-95',
                    'descrizione' => 'timetracking:aggregate-daily consolida un ticket con attività odierna, producendo gli stessi aggregati di timetracking:recalculate',
                    'test_automatico' => 'tests/Feature/Console/TimeTrackingAggregateDailyCommandTest.php::consolidates a ticket with activity today, producing the same aggregates as timetracking:recalculate',
                ],
                [
                    'id' => 'F6-96',
                    'descrizione' => 'timetracking:aggregate-daily ignora un ticket senza alcuna attività odierna',
                    'test_automatico' => 'tests/Feature/Console/TimeTrackingAggregateDailyCommandTest.php::ignores a ticket without any activity today',
                ],
                [
                    'id' => 'F6-97',
                    'descrizione' => 'timetracking:aggregate-daily in --dry-run esamina i ticket con attività odierna senza scrivere nulla',
                    'test_automatico' => 'tests/Feature/Console/TimeTrackingAggregateDailyCommandTest.php::--dry-run examines tickets with activity today without writing anything',
                ],
                [
                    'id' => 'F6-98',
                    'descrizione' => 'Eseguire timetracking:aggregate-daily due volte nello stesso giorno non duplica le righe di ticket_work_logs (idempotenza via upsert)',
                    'test_automatico' => 'tests/Feature/Console/TimeTrackingAggregateDailyCommandTest.php::running it twice on the same day does not duplicate ticket_work_logs rows',
                ],
            ],
        ],
        [
            'titolo' => 'Mailable E8 — Digest periodico giornaliero, riscritto da zero rispetto al dead code v1 (§7.5.2, US-614)',
            'test' => [
                [
                    'id' => 'F6-99',
                    'descrizione' => 'mail:send-digest invia un digest a un cliente con attività su uno dei propri ticket',
                    'test_automatico' => 'tests/Feature/Console/MailSendDigestCommandTest.php::sends a digest to a customer with activity on one of their tickets',
                ],
                [
                    'id' => 'F6-100',
                    'descrizione' => 'mail:send-digest non invia alcun digest a un cliente senza attività nelle ultime 24h',
                    'test_automatico' => 'tests/Feature/Console/MailSendDigestCommandTest.php::sends no digest to a customer without activity in the last 24h',
                ],
                [
                    'id' => 'F6-101',
                    'descrizione' => 'mail:send-digest non invia a un cliente che ha già ricevuto un digest oggi (idempotenza)',
                    'test_automatico' => 'tests/Feature/Console/MailSendDigestCommandTest.php::does not send to a customer who has already received a digest today',
                ],
                [
                    'id' => 'F6-102',
                    'descrizione' => 'mail:send-digest rispetta la preferenza di notifica E8 disabilitata dal cliente',
                    'test_automatico' => 'tests/Feature/Console/MailSendDigestCommandTest.php::respects a customer having disabled the E8 notification preference',
                ],
                [
                    'id' => 'F6-103',
                    'descrizione' => 'mail:send-digest rispetta una soppressione email attiva per il cliente',
                    'test_automatico' => 'tests/Feature/Console/MailSendDigestCommandTest.php::respects an active email suppression for the customer',
                ],
                [
                    'id' => 'F6-104',
                    'descrizione' => 'mail:send-digest in --dry-run non scrive né invia nulla',
                    'test_automatico' => 'tests/Feature/Console/MailSendDigestCommandTest.php::does not write or send anything in dry-run mode',
                ],
                [
                    'id' => 'F6-105',
                    'descrizione' => 'mail:send-digest non fallisce e non invia nulla quando non ci sono clienti',
                    'test_automatico' => 'tests/Feature/Console/MailSendDigestCommandTest.php::does not fail and sends no mail when there are no customers',
                ],
                [
                    'id' => 'F6-106',
                    'descrizione' => 'Il digest include un ticket con un nuovo messaggio pubblico dello staff nelle ultime 24h',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/BuildCustomerTicketDigestTest.php::includes a ticket with a new public message from staff in the last 24h',
                ],
                [
                    'id' => 'F6-107',
                    'descrizione' => 'Il digest esclude un messaggio pubblicato dal cliente stesso a cui è destinato il digest',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/BuildCustomerTicketDigestTest.php::excludes a message posted by the customer being digested',
                ],
                [
                    'id' => 'F6-108',
                    'descrizione' => 'Il digest esclude un messaggio interno (non pubblico)',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/BuildCustomerTicketDigestTest.php::excludes an internal message',
                ],
                [
                    'id' => 'F6-109',
                    'descrizione' => 'Il digest esclude un messaggio pubblicato prima della finestra delle 24h',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/BuildCustomerTicketDigestTest.php::excludes a message posted before the window',
                ],
                [
                    'id' => 'F6-110',
                    'descrizione' => 'Il digest include un ticket con un cambio di stato nelle ultime 24h, riportando lo stato precedente e quello corrente',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/BuildCustomerTicketDigestTest.php::includes a ticket with a status change in the last 24h, reporting from/to status',
                ],
                [
                    'id' => 'F6-111',
                    'descrizione' => 'Il digest aggrega più ticket con attività per lo stesso cliente, escludendo quelli senza attività',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/BuildCustomerTicketDigestTest.php::aggregates several tickets with activity for the same customer',
                ],
                [
                    'id' => 'F6-112',
                    'descrizione' => 'Il digest ignora ticket appartenenti a un altro cliente',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/BuildCustomerTicketDigestTest.php::ignores tickets belonging to another customer',
                ],
                [
                    'id' => 'F6-113',
                    'descrizione' => 'Il Mailable E8 renderizza un HTML ben formato che elenca ogni ticket con conteggio messaggi ed eventuale cambio di stato',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/MailDigestMailTest.php::renders well-formed HTML listing every ticket entry with its message count and status change',
                ],
                [
                    'id' => 'F6-114',
                    'descrizione' => 'Il Mailable E8 valorizza l\'header Message-Id e il Reply-To VERP dalla riga email_messages outbound',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/MailDigestMailTest.php::sets the Message-Id header and the VERP Reply-To from the outbound email_messages row',
                ],
                [
                    'id' => 'F6-115',
                    'descrizione' => 'Il Mailable E8 genera anche una versione testo semplice accanto all\'HTML',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/MailDigestMailTest.php::generates a plain-text version alongside the HTML',
                ],
                [
                    'id' => 'F6-116',
                    'descrizione' => 'Il Mailable E8 renderizza il corpo nella lingua impostata via ->locale(), mai una chiave non tradotta',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/MailDigestMailTest.php::renders the body in the language set via ->locale(), never a raw untranslated key (§7.6, US-320)',
                ],
            ],
        ],
        [
            'titolo' => 'Mailable E10 — Report attività disponibile, dispatchato da un evento di dominio (§7.5.2, US-615)',
            'test' => [
                [
                    'id' => 'F6-117',
                    'descrizione' => 'L\'evento di dominio ActivityReportPdfGenerated viene dispatchato la prima volta che il PDF è generato',
                    'test_automatico' => 'tests/Feature/Domain/Reporting/ActivityReportPdfGeneratedEventTest.php::dispatches the domain event the first time the pdf is generated',
                ],
                [
                    'id' => 'F6-118',
                    'descrizione' => 'L\'evento di dominio non viene dispatchato di nuovo quando il PDF viene rigenerato',
                    'test_automatico' => 'tests/Feature/Domain/Reporting/ActivityReportPdfGeneratedEventTest.php::does not dispatch the domain event again when the pdf is regenerated',
                ],
                [
                    'id' => 'F6-119',
                    'descrizione' => 'Il listener invia E10 all\'owner quando il PDF di un report di proprietà utente viene generato',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/SendActivityReportPdfGeneratedNotificationTest.php::sends E10 to the owner when a user-owned report pdf is generated',
                ],
                [
                    'id' => 'F6-120',
                    'descrizione' => 'Il listener invia E10 a ogni membro di un report di proprietà di un\'organizzazione',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/SendActivityReportPdfGeneratedNotificationTest.php::sends E10 to every member of an organization-owned report',
                ],
                [
                    'id' => 'F6-121',
                    'descrizione' => 'Il listener non invia a un utente che ha disabilitato questo tipo di notifica',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/SendActivityReportPdfGeneratedNotificationTest.php::does not send to a user who disabled this notification type',
                ],
                [
                    'id' => 'F6-122',
                    'descrizione' => 'Il listener implementa ShouldQueue così l\'invio avviene in modo asincrono',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/SendActivityReportPdfGeneratedNotificationTest.php::implements ShouldQueue so the send happens asynchronously',
                ],
                [
                    'id' => 'F6-123',
                    'descrizione' => 'Il Mailable E10 renderizza un HTML ben formato col periodo del report e un link di download funzionante, autorizzato dalla Policy esistente',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/ActivityReportPdfGeneratedMailTest.php::renders well-formed HTML with the period and a working download link',
                ],
                [
                    'id' => 'F6-124',
                    'descrizione' => 'Il Mailable E10 valorizza l\'header Message-Id e il Reply-To VERP dalla riga email_messages outbound',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/ActivityReportPdfGeneratedMailTest.php::sets the Message-Id header and the VERP Reply-To from the outbound email_messages row',
                ],
                [
                    'id' => 'F6-125',
                    'descrizione' => 'Il Mailable E10 genera anche una versione testo semplice accanto all\'HTML',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/ActivityReportPdfGeneratedMailTest.php::generates a plain-text version alongside the HTML',
                ],
                [
                    'id' => 'F6-126',
                    'descrizione' => 'Il Mailable E10 renderizza il corpo nella lingua impostata via ->locale(), mai una chiave non tradotta',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/ActivityReportPdfGeneratedMailTest.php::renders the body in the language set via ->locale(), never a raw untranslated key (§7.6, US-320)',
                ],
                [
                    'id' => 'F6-127',
                    'descrizione' => 'reports:generate-monthly, eseguito realmente end-to-end (comando -> job -> generazione PDF -> evento -> listener), accoda l\'email E10 per il proprietario del report',
                    'test_automatico' => 'tests/Feature/Console/ReportsGenerateMonthlySendsActivityReportPdfGeneratedMailTest.php::reports:generate-monthly ends up queuing the E10 mail for the report owner',
                ],
            ],
        ],
        [
            'titolo' => 'Mailable E11 — Developer senza ticket in lavorazione + tickets:notify-idle-developers, comando schedulato invece di un job da observer (§7.5.2, §10.2, US-616)',
            'test' => [
                [
                    'id' => 'F6-128',
                    'descrizione' => 'tickets:notify-idle-developers invia un promemoria a un developer con ticket assegnati e nessuno in lavorazione, entro la finestra oraria configurata (anche come notifica in-app)',
                    'test_automatico' => 'tests/Feature/Console/TicketsNotifyIdleDevelopersCommandTest.php::sends a reminder to a developer with assigned tickets and none in progress, within the window',
                ],
                [
                    'id' => 'F6-129',
                    'descrizione' => 'tickets:notify-idle-developers non invia alcun promemoria a un developer con un ticket in lavorazione',
                    'test_automatico' => 'tests/Feature/Console/TicketsNotifyIdleDevelopersCommandTest.php::sends no reminder to a developer with a ticket in progress',
                ],
                [
                    'id' => 'F6-130',
                    'descrizione' => 'tickets:notify-idle-developers non invia alcun promemoria a un developer il cui unico ticket assegnato è già chiuso',
                    'test_automatico' => 'tests/Feature/Console/TicketsNotifyIdleDevelopersCommandTest.php::sends no reminder to a developer whose only assigned ticket is already closed',
                ],
                [
                    'id' => 'F6-131',
                    'descrizione' => 'tickets:notify-idle-developers non invia alcun promemoria fuori dalla finestra oraria configurata',
                    'test_automatico' => 'tests/Feature/Console/TicketsNotifyIdleDevelopersCommandTest.php::sends no reminder outside the configured window',
                ],
                [
                    'id' => 'F6-132',
                    'descrizione' => 'tickets:notify-idle-developers non invia un secondo promemoria lo stesso giorno, anche in un\'esecuzione successiva entro la finestra (idempotenza sulla finestra)',
                    'test_automatico' => 'tests/Feature/Console/TicketsNotifyIdleDevelopersCommandTest.php::does not send a second reminder the same day, even in a later run within the window',
                ],
                [
                    'id' => 'F6-133',
                    'descrizione' => 'tickets:notify-idle-developers in --dry-run non scrive né invia nulla',
                    'test_automatico' => 'tests/Feature/Console/TicketsNotifyIdleDevelopersCommandTest.php::does not write or send anything in dry-run mode',
                ],
                [
                    'id' => 'F6-134',
                    'descrizione' => 'tickets:notify-idle-developers non fallisce e non invia nulla quando non ci sono developer',
                    'test_automatico' => 'tests/Feature/Console/TicketsNotifyIdleDevelopersCommandTest.php::does not fail and sends no mail when there are no developers',
                ],
                [
                    'id' => 'F6-135',
                    'descrizione' => 'Il Mailable E11 renderizza un HTML ben formato che elenca ogni ticket idle con il proprio stato',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/IdleDeveloperNoticeMailTest.php::renders well-formed HTML listing every idle ticket with its status',
                ],
                [
                    'id' => 'F6-136',
                    'descrizione' => 'Il Mailable E11 valorizza l\'header Message-Id e il Reply-To VERP dalla riga email_messages outbound',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/IdleDeveloperNoticeMailTest.php::sets the Message-Id header and the VERP Reply-To from the outbound email_messages row',
                ],
                [
                    'id' => 'F6-137',
                    'descrizione' => 'Il Mailable E11 genera anche una versione testo semplice accanto all\'HTML',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/IdleDeveloperNoticeMailTest.php::generates a plain-text version alongside the HTML',
                ],
                [
                    'id' => 'F6-138',
                    'descrizione' => 'Il Mailable E11 renderizza il corpo nella lingua impostata via ->locale(), mai una chiave non tradotta',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/IdleDeveloperNoticeMailTest.php::renders the body in the language set via ->locale(), never a raw untranslated key (§7.6, US-320)',
                ],
            ],
        ],
        [
            'titolo' => 'Checkpoint di fine fase — isolamento multi-superficie tra clienti, sequenza combinata delle automazioni, garanzia di conservatività di archive-scrum (US-618)',
            'test' => [
                [
                    'id' => 'F6-139',
                    'descrizione' => 'Due clienti con dati reali su ticket, report e fundraising restano completamente isolati attraverso dashboard, ricerca globale ed elenco ticket, non solo su una superficie alla volta',
                    'test_automatico' => 'tests/Feature/EndToEnd/Fase6CheckpointEndToEndTest.php::two customers with real data across tickets, reports and fundraising stay fully isolated across the dashboard, global search and the ticket list',
                ],
                [
                    'id' => 'F6-140',
                    'descrizione' => 'Eseguire in sequenza tutti i comandi schedulati di Fase 6 transita ogni ticket guardato esattamente una volta e mai un ticket fuori dal proprio guard, anche ripetendo l\'intera sequenza (idempotenza combinata)',
                    'test_automatico' => 'tests/Feature/EndToEnd/Fase6CheckpointEndToEndTest.php::running every Fase 6 scheduled command in sequence transitions each guarded ticket exactly once and never a ticket outside its guard',
                ],
                [
                    'id' => 'F6-141',
                    'descrizione' => 'tickets:archive-scrum è un compromesso strettamente additivo: non tocca mai lo stato del ticket né alcun campo oltre archived_at, solo un log di sistema dedicato (garanzia esplicita del compromesso segnalato al committente, US-611)',
                    'test_automatico' => 'tests/Feature/EndToEnd/Fase6CheckpointEndToEndTest.php::archive-scrum is a strictly additive compromise: it never touches ticket status or any field besides archived_at, only ever a dedicated system log',
                ],
            ],
        ],
    ],
];
