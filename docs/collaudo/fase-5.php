<?php

declare(strict_types=1);

// Manifest di tracciabilità per il collaudo (UAT) di Fase 5 (Fundraising: opportunità/bandi,
// griglia di valutazione, progetti con partner e macchina a stati, vista cliente — US-501..US-509).
// Stessa disciplina di Fase 4 (docs/collaudo/fase-4.php): topic raggruppati per area funzionale del
// PRD (§6.6.1 Opportunità, §6.6.2 Griglia di valutazione, §6.6.3 Progetti, §6.6.4 Vista cliente), più
// un topic finale dedicato al checkpoint di fine fase (US-509). Ogni voce collega un criterio di
// accettazione a un test automatico REALMENTE esistente in tests/ (verificato da
// `collaudo:verify-manifest 5`). Fonte delle story: scripts/ralph/prd.json (Fase 5).
//
// Gotcha noto (già documentato per Fase 4, CLAUDE.md "Processo di collaudo"): un apostrofo in una
// descrizione di test Pest referenziata qui romperebbe il confronto byte-per-byte di
// `collaudo:verify-manifest` (il file PHP sorgente del test contiene l'apostrofo come sequenza
// letterale `\'` dentro una stringa a apici singoli, mentre lo stesso apostrofo scritto in QUESTO
// manifest verrebbe normalizzato da PHP a un apostrofo semplice, senza backslash, prima del
// confronto). Dove l'unico test Pest pertinente ha un apostrofo nella propria descrizione (es. "un
// utente con fundraising.create può creare un progetto da un'opportunità"), il riferimento
// `test_automatico` qui sotto è un percorso NUDO (solo il file, senza `::descrizione`): resta un
// riferimento a un test automatico realmente esistente (verificato da `collaudo:verify-manifest`
// tramite `file_exists`), la descrizione completa in italiano resta comunque leggibile nel campo
// `descrizione` di questo manifest (mai troncata). Questo file è puro dato: nessuna logica.

return [
    'fase' => '5',
    'titolo' => 'Fase 5 (Fundraising — opportunità/bandi, griglia di valutazione, progetti e vista cliente)',
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
            'titolo' => 'Opportunità di fundraising — modello, Policy, elenco/archivio, filtri, azioni collegate (§6.6.1, US-501/502/505)',
            'test' => [
                [
                    'id' => 'F5-01',
                    'descrizione' => 'isExpired() è false quando la scadenza è oggi',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingOpportunityExpiryTest.php::isExpired is false when the deadline is today',
                ],
                [
                    'id' => 'F5-02',
                    'descrizione' => 'isExpired() è true quando la scadenza è ieri',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingOpportunityExpiryTest.php::isExpired is true when the deadline is yesterday',
                ],
                [
                    'id' => 'F5-03',
                    'descrizione' => 'Lo scope active() restituisce le opportunità con scadenza odierna o futura',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingOpportunityExpiryTest.php::scope active returns opportunities whose deadline is today or later',
                ],
                [
                    'id' => 'F5-04',
                    'descrizione' => 'Lo scope expired() restituisce le opportunità con scadenza passata',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingOpportunityExpiryTest.php::scope expired returns opportunities whose deadline is before today',
                ],
                [
                    'id' => 'F5-05',
                    'descrizione' => 'Un utente senza alcun permesso fundraising.* è negato su ogni abilità della Policy opportunità',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingOpportunityPolicyTest.php::a user without any fundraising.* permission is denied every FundraisingOpportunityPolicy ability',
                ],
                [
                    'id' => 'F5-06',
                    'descrizione' => 'FundraisingOpportunityPolicy verificata riga per riga per ogni ruolo (§9.4): solo admin/fundraising hanno view.any/create/update/delete, il customer solo view.involved',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingOpportunityPolicyTest.php::FundraisingOpportunityPolicy per ruolo, riga per riga (§9.4)',
                ],
                [
                    'id' => 'F5-07',
                    'descrizione' => 'La Resource opportunità è visibile in navigazione solo ad admin/fundraising, mai a manager/developer/customer',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingOpportunityResourceTest.php::FundraisingOpportunityResource visibility per ruolo (§9.4, mai manager/developer/customer)',
                ],
                [
                    'id' => 'F5-08',
                    'descrizione' => 'L\'elenco mostra di default solo le opportunità attive, l\'Archivio mostra solo le scadute',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingOpportunityResourceTest.php::elenco mostra solo le opportunità attive di default, archivio mostra solo le scadute',
                ],
                [
                    'id' => 'F5-09',
                    'descrizione' => 'Il filtro per ambito territoriale produce il sottoinsieme atteso di opportunità',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingOpportunityResourceTest.php::filtro ambito territoriale produce il sottoinsieme atteso',
                ],
                [
                    'id' => 'F5-10',
                    'descrizione' => 'Il filtro cofinanziamento con/senza quota produce il sottoinsieme atteso di opportunità',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingOpportunityResourceTest.php::filtro cofinanziamento con/senza quota produce il sottoinsieme atteso',
                ],
                [
                    'id' => 'F5-11',
                    'descrizione' => 'Il filtro scaduto/attivo produce il sottoinsieme atteso di opportunità',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingOpportunityResourceTest.php::filtro scaduto/attivo produce il sottoinsieme atteso',
                ],
                [
                    'id' => 'F5-12',
                    'descrizione' => 'created_by si valorizza automaticamente con l\'utente autenticato alla creazione e non è più alterabile in seguito, nemmeno modificando l\'opportunità',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingOpportunityResourceTest.php',
                ],
                [
                    'id' => 'F5-13',
                    'descrizione' => 'Le azioni "Crea progetto" e "Crea ticket" da un\'opportunità creano il record collegato con i campi precompilati attesi (title dal nome dell\'opportunità, fundraising_project_id valorizzato sul ticket solo se un progetto è già collegato), e sono nascoste a chi non ha il permesso corrispondente (fundraising.create / ticket.create)',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/CreateProjectAndTicketActionsTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Griglia di valutazione — catalogo criteri e calcolo dei totali (§6.6.2, US-503/504)',
            'test' => [
                [
                    'id' => 'F5-14',
                    'descrizione' => 'Il catalogo contiene esattamente i 26 criteri di §6.6.2, sui 5 blocchi previsti',
                    'test_automatico' => 'tests/Unit/Domain/Fundraising/FundraisingEvaluationCriterionTest.php::contains exactly the 26 criteria of §6.6.2',
                ],
                [
                    'id' => 'F5-15',
                    'descrizione' => 'I criteri principali hanno range di punteggio 0-5',
                    'test_automatico' => 'tests/Unit/Domain/Fundraising/FundraisingEvaluationCriterionTest.php::main criteria range 0 to 5',
                ],
                [
                    'id' => 'F5-16',
                    'descrizione' => 'I criteri del blocco Rischi consentono punteggi negativi, unico blocco a farlo (§6.6.2)',
                    'test_automatico' => 'tests/Unit/Domain/Fundraising/FundraisingEvaluationCriterionTest.php::risk criteria allow negative scores per §6.6.2',
                ],
                [
                    'id' => 'F5-17',
                    'descrizione' => 'CalculateEvaluationTotals somma nel totale positivo solo i punteggi >= 0',
                    'test_automatico' => 'tests/Unit/Domain/Fundraising/CalculateEvaluationTotalsTest.php::sums only positive scores into the positive total',
                ],
                [
                    'id' => 'F5-18',
                    'descrizione' => 'CalculateEvaluationTotals somma nel totale negativo il valore assoluto dei punteggi < 0',
                    'test_automatico' => 'tests/Unit/Domain/Fundraising/CalculateEvaluationTotalsTest.php::sums the absolute value of negative scores into the negative total',
                ],
                [
                    'id' => 'F5-19',
                    'descrizione' => 'Il totale complessivo è positivo meno negativo',
                    'test_automatico' => 'tests/Unit/Domain/Fundraising/CalculateEvaluationTotalsTest.php::total is positive minus negative',
                ],
                [
                    'id' => 'F5-20',
                    'descrizione' => 'Il calcolo gestisce correttamente il valore minimo e massimo di ogni range del catalogo',
                    'test_automatico' => 'tests/Unit/Domain/Fundraising/CalculateEvaluationTotalsTest.php::handles the min and max value of every catalog range',
                ],
                [
                    'id' => 'F5-21',
                    'descrizione' => 'Un criterio aggiunto al catalogo solo a runtime (nessuna migrazione) viene incluso correttamente nel calcolo dei totali',
                    'test_automatico' => 'tests/Unit/Domain/Fundraising/CalculateEvaluationTotalsTest.php::a criterion added to the catalog at runtime is included correctly without touching the database',
                ],
                [
                    'id' => 'F5-22',
                    'descrizione' => 'SaveEvaluationScores persiste una riga fundraising_evaluation_scores per criterio e calcola i totali da tutti i punteggi persistiti',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/SaveEvaluationScoresTest.php::persists a score row per criterion and computes totals from all persisted scores',
                ],
                [
                    'id' => 'F5-23',
                    'descrizione' => 'Un punteggio sotto il minimo del catalogo per quel criterio viene rifiutato',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/SaveEvaluationScoresTest.php::rejects a score below the catalog minimum',
                ],
                [
                    'id' => 'F5-24',
                    'descrizione' => 'Un punteggio sopra il massimo del catalogo per quel criterio viene rifiutato',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/SaveEvaluationScoresTest.php::rejects a score above the catalog maximum',
                ],
                [
                    'id' => 'F5-25',
                    'descrizione' => 'evaluated_by/evaluated_at si valorizzano al primo punteggio salvato',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/SaveEvaluationScoresTest.php::sets evaluated_by and evaluated_at on the first saved score',
                ],
                [
                    'id' => 'F5-26',
                    'descrizione' => 'evaluated_by/evaluated_at non vengono mai sovrascritti dai salvataggi successivi al primo',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/SaveEvaluationScoresTest.php::does not overwrite evaluated_by/evaluated_at on subsequent saves',
                ],
                [
                    'id' => 'F5-27',
                    'descrizione' => 'Compilare la griglia dalla pagina Edit persiste i punteggi e aggiorna i totali coerentemente col service di calcolo',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingEvaluationGridTest.php::compilare la griglia dalla pagina Edit persiste i punteggi e aggiorna i totali coerentemente col service',
                ],
                [
                    'id' => 'F5-28',
                    'descrizione' => 'Un punteggio fuori dal range del criterio produce un errore di validazione leggibile in UI',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingEvaluationGridTest.php::un punteggio fuori dal range del criterio produce un errore di validazione leggibile',
                ],
                [
                    'id' => 'F5-29',
                    'descrizione' => 'Il tab "Valutazione" non è visibile a chi ha solo fundraising.update, senza fundraising.evaluate',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingEvaluationGridTest.php::il tab Valutazione non è visibile a chi ha solo fundraising.update, senza fundraising.evaluate',
                ],
                [
                    'id' => 'F5-30',
                    'descrizione' => 'La griglia riprende correttamente i punteggi già persistiti quando si riapre la pagina Edit',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingEvaluationGridTest.php::la griglia riprende i punteggi già persistiti quando si riapre la pagina Edit',
                ],
            ],
        ],
        [
            'titolo' => 'Progetti di fundraising — stato, partner, Policy, elenco/filtri, collegamento ticket (§6.6.3, US-506/507)',
            'test' => [
                [
                    'id' => 'F5-31',
                    'descrizione' => 'Ogni transizione ammessa della macchina a stati del progetto (draft->submitted->approved/rejected->completed) può essere eseguita',
                    'test_automatico' => 'tests/Unit/Domain/Fundraising/FundraisingProjectStateMachineTest.php::allowed transitions can be performed',
                ],
                [
                    'id' => 'F5-32',
                    'descrizione' => 'Ogni altra transizione non elencata in tabella è vietata',
                    'test_automatico' => 'tests/Unit/Domain/Fundraising/FundraisingProjectStateMachineTest.php::every other transition is forbidden',
                ],
                [
                    'id' => 'F5-33',
                    'descrizione' => 'Gli stati terminali (rejected/completed) non hanno alcuna transizione uscente verso nessun altro stato',
                    'test_automatico' => 'tests/Unit/Domain/Fundraising/FundraisingProjectStateMachineTest.php::rejected and completed have no outgoing transition to any other status',
                ],
                [
                    'id' => 'F5-34',
                    'descrizione' => 'scopeInvolving trova il progetto per capofila, partner, responsabile o creatore (definizione di "coinvolti" per lo staff)',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingProjectInvolvementTest.php::scopeInvolving trova il progetto per capofila, partner, responsabile o creatore',
                ],
                [
                    'id' => 'F5-35',
                    'descrizione' => 'partnerCustomers() restituisce solo i partner con ruolo customer (fix del bug v1 sulla query JSON)',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingProjectInvolvementTest.php::partnerCustomers restituisce solo i partner con ruolo customer',
                ],
                [
                    'id' => 'F5-36',
                    'descrizione' => 'FundraisingProjectPolicy verificata riga per riga per ogni ruolo (§9.4), caso non coinvolto',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingProjectPolicyTest.php::FundraisingProjectPolicy per ruolo, riga per riga (§9.4), non coinvolto',
                ],
                [
                    'id' => 'F5-37',
                    'descrizione' => 'Un customer coinvolto come capofila vede il progetto ma non può scriverlo',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingProjectPolicyTest.php::un customer coinvolto come capofila vede il progetto ma non può scriverlo',
                ],
                [
                    'id' => 'F5-38',
                    'descrizione' => 'Un customer non coinvolto in nessun modo non vede il progetto, nemmeno via URL diretto',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingProjectPolicyTest.php::un customer non coinvolto in nessun modo non vede il progetto neanche via URL diretto',
                ],
                [
                    'id' => 'F5-39',
                    'descrizione' => 'La Resource progetti è visibile in navigazione solo ad admin/fundraising, mai a manager/developer/customer',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingProjectResourceTest.php::FundraisingProjectResource visibility per ruolo (§9.4, mai manager/developer/customer)',
                ],
                [
                    'id' => 'F5-40',
                    'descrizione' => 'Il filtro per stato produce il sottoinsieme atteso di progetti',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingProjectResourceTest.php::filtro stato produce il sottoinsieme atteso',
                ],
                [
                    'id' => 'F5-41',
                    'descrizione' => 'Il filtro per capofila produce il sottoinsieme atteso di progetti',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingProjectResourceTest.php::filtro capofila produce il sottoinsieme atteso',
                ],
                [
                    'id' => 'F5-42',
                    'descrizione' => 'Il filtro per partner produce il sottoinsieme atteso di progetti',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingProjectResourceTest.php::filtro partner produce il sottoinsieme atteso',
                ],
                [
                    'id' => 'F5-43',
                    'descrizione' => 'Il filtro "coinvolti" (capofila OR partner OR responsabile OR creatore) produce il sottoinsieme atteso di progetti',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingProjectResourceTest.php::filtro coinvolti produce il sottoinsieme atteso (capofila OR partner OR responsabile OR creatore)',
                ],
                [
                    'id' => 'F5-44',
                    'descrizione' => 'created_by si valorizza automaticamente con l\'utente autenticato alla creazione di un progetto',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/FundraisingProjectResourceTest.php',
                ],
                [
                    'id' => 'F5-45',
                    'descrizione' => 'Un utente fundraising può aggiungere e rimuovere un partner dal progetto tramite il relation manager "Partner"',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/PartnersRelationManagerTest.php::un utente fundraising può aggiungere e rimuovere un partner dal progetto',
                ],
                [
                    'id' => 'F5-46',
                    'descrizione' => 'Un ticket esistente può essere collegato a un progetto di fundraising tramite tickets.fundraising_project_id',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingProjectsTableTest.php::a ticket can be linked to a fundraising project',
                ],
            ],
        ],
        [
            'titolo' => 'Vista cliente — opportunità e progetti coinvolti, in sola lettura (§6.6.4, US-508)',
            'test' => [
                [
                    'id' => 'F5-47',
                    'descrizione' => 'CustomerFundraisingOpportunityResource è visibile in navigazione solo al ruolo customer',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/CustomerFundraisingOpportunityResourceTest.php::CustomerFundraisingOpportunityResource visibility per ruolo (§6.6.4, SOLO customer)',
                ],
                [
                    'id' => 'F5-48',
                    'descrizione' => 'Qualunque customer autenticato vede qualunque opportunità nell\'elenco (nessuna differenza attive/scadute) e ne può aprire il dettaglio in sola lettura',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/CustomerFundraisingOpportunityResourceTest.php',
                ],
                [
                    'id' => 'F5-49',
                    'descrizione' => 'CustomerFundraisingOpportunityResource non registra alcuna pagina di scrittura (create/edit/delete)',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/CustomerFundraisingOpportunityResourceTest.php::CustomerFundraisingOpportunityResource non registra pagine di scrittura',
                ],
                [
                    'id' => 'F5-50',
                    'descrizione' => 'La Resource opportunità riservata allo staff resta invisibile a un customer',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/CustomerFundraisingOpportunityResourceTest.php::la Resource staff resta invisibile a un customer (US-502, invariato da questa story)',
                ],
                [
                    'id' => 'F5-51',
                    'descrizione' => 'CustomerFundraisingProjectResource è visibile in navigazione solo al ruolo customer',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/CustomerFundraisingProjectResourceTest.php::CustomerFundraisingProjectResource visibility per ruolo (§6.6.4, SOLO customer)',
                ],
                [
                    'id' => 'F5-52',
                    'descrizione' => 'Un customer capofila o partner vede il proprio progetto nell\'elenco, uno non coinvolto no',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/CustomerFundraisingProjectResourceTest.php',
                ],
                [
                    'id' => 'F5-53',
                    'descrizione' => 'scopeInvolvingAsCustomer trova il progetto SOLO per capofila o partner, mai per responsabile o creatore (ruoli interni allo staff)',
                    'test_automatico' => 'tests/Feature/Domain/Fundraising/FundraisingProjectInvolvementTest.php::scopeInvolvingAsCustomer trova il progetto SOLO per capofila o partner, mai responsabile o creatore (§6.6.4)',
                ],
                [
                    'id' => 'F5-54',
                    'descrizione' => 'Essere solo responsabile o creatore non basta a far vedere il progetto a un customer in questa vista',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/CustomerFundraisingProjectResourceTest.php::responsabile/creatore da soli NON bastano a far vedere il progetto a un customer (§6.6.4)',
                ],
                [
                    'id' => 'F5-55',
                    'descrizione' => 'Il dettaglio di un progetto è raggiungibile da un customer coinvolto',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/CustomerFundraisingProjectResourceTest.php::il dettaglio è raggiungibile da un customer coinvolto',
                ],
                [
                    'id' => 'F5-56',
                    'descrizione' => 'Il dettaglio di un progetto in cui il customer non è coinvolto non è raggiungibile nemmeno via URL diretto',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/CustomerFundraisingProjectResourceTest.php::il dettaglio di un progetto in cui il customer non è coinvolto non è raggiungibile via URL diretto',
                ],
                [
                    'id' => 'F5-57',
                    'descrizione' => 'CustomerFundraisingProjectResource non registra alcuna pagina di scrittura (create/edit/delete)',
                    'test_automatico' => 'tests/Feature/Filament/Fundraising/CustomerFundraisingProjectResourceTest.php::CustomerFundraisingProjectResource non registra pagine di scrittura',
                ],
            ],
        ],
        [
            'titolo' => 'Checkpoint di fine fase — verifica end-to-end su dati reali (US-509)',
            'test' => [
                [
                    'id' => 'F5-58',
                    'descrizione' => 'I totali di valutazione ricalcolati da CalculateEvaluationTotals su una opportunità coincidono con evaluation_positive_total/.negative_total/.total persistiti da SaveEvaluationScores (replica automatica della verifica manuale eseguita su dati reali v1:import, dove le 21 opportunità importate hanno zero punteggi e zero totali — v1 non ha mai usato la griglia di valutazione, vedi FundraisingScoresStage)',
                    'test_automatico' => 'tests/Feature/EndToEnd/Fase5CheckpointEndToEndTest.php::evaluation totals recomputed from persisted scores match what SaveEvaluationScores stores on the opportunity',
                ],
                [
                    'id' => 'F5-59',
                    'descrizione' => 'Un criterio aggiunto al catalogo a runtime viene incluso correttamente nel totale di una valutazione reale, senza lasciare traccia permanente nel catalogo',
                    'test_automatico' => 'tests/Feature/EndToEnd/Fase5CheckpointEndToEndTest.php::a criterion added to the catalog at runtime is included in a real evaluation total',
                ],
                [
                    'id' => 'F5-60',
                    'descrizione' => 'Il flusso completo opportunità -> progetto -> partner -> transizione di stato funziona end-to-end con Action/Model/Resource reali in sequenza',
                    'test_automatico' => 'tests/Feature/EndToEnd/Fase5CheckpointEndToEndTest.php::the opportunity to project to partner to state transition flow works end to end',
                ],
            ],
        ],
    ],
];
