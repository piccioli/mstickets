# Fase 0 (Fondazioni) — Casi di test dettagliati

> Torna a [`README.md`](README.md) · Istruzioni generali: [`00-istruzioni-generali.md`](00-istruzioni-generali.md) · Matrice di tracciabilità: [`01-matrice-tracciabilita.md`](01-matrice-tracciabilita.md)

56 casi di test (F0-01 — F0-56) su 9 argomenti. Prima di eseguire un test, leggi le convenzioni comuni in `00-istruzioni-generali.md` (in particolare le sezioni 9 "Credenziali", 13 "Preparazione e ripristino dei dati" e 14 "Convenzioni per nominare i dati di test").

## Autenticazione, ruoli e permessi

### F0-01 — Un utente con un ruolo applicativo valido accede al pannello

**Obiettivo**
Verifica che un utente con almeno uno dei 5 ruoli applicativi validi (Admin, Developer, Manager, Customer, Fundraising) e non disattivato possa autenticarsi e raggiungere il pannello Filament `/admin`. È il caso positivo del gate d'accesso implementato da `User::canAccessPanel()`.

**Riferimenti**
- Requisito/regola di dominio: gate d'accesso al pannello Filament (§9.1/§9.2 del PRD) — un utente con un ruolo applicativo valido accede al pannello.
- Test automatico: `tests/Feature/Domain/Identity/PanelAccessTest.php` — `a user with a valid application role can access the panel`
- File/componente applicativo rilevante: `app/Domain/Identity/Models/User.php` (metodo `canAccessPanel()`), `app/Domain/Identity/Enums/UserRole.php`
- Test correlato: F0-02, F0-03

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Developer

**Prerequisiti**
- Ambiente UAT raggiungibile su `https://ticket-uat.montagnaservizi.com/admin/login`.
- L'utente seed `lorena.sava@montagnaservizi.com` esiste con ruolo "Sviluppatore" (popolato dall'ETL reale, `v1:import --anonymize`).
- L'utente non ha `deactivated_at` valorizzato (stato di default al seed).

**Dati di test**
Email: `lorena.sava@montagnaservizi.com` — Password: `uat`

**Stato iniziale**
L'utente `lorena.sava@montagnaservizi.com` esiste, ha il ruolo "Sviluppatore" assegnato, non è disattivato. Il tester non ha alcuna sessione attiva sul pannello.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Aprire il browser e navigare all'URL di login del pannello | `https://ticket-uat.montagnaservizi.com/admin/login` | Viene mostrata la pagina di login di Filament |
| 2 | Compilare i campi email/password e premere "Accedi" | `lorena.sava@montagnaservizi.com` / `uat` | Il login viene accettato, nessun messaggio d'errore mostrato |
| 3 | Osservare l'URL nella barra degli indirizzi dopo il login | — | L'URL è sotto `/admin` (es. `/admin` o `/admin/tickets/work-board`), non `/admin/login` |
| 4 | Osservare il menu di navigazione laterale del pannello | — | Il menu è visibile e popolato di voci (conferma sessione autenticata) |

**Risultato finale atteso**
L'utente `lorena.sava@montagnaservizi.com` ha una sessione attiva sul pannello `/admin` e può navigare tra le pagine per cui ha permesso.

**Controlli negativi**
Nessuno applicabile (il caso negativo — utente senza ruolo — è coperto da F0-02).

**Evidenze da acquisire**
- Screenshot della pagina raggiunta subito dopo il login (con URL visibile).
- Screenshot del menu di navigazione del pannello.

**Criterio di superamento**

PASS: il login riesce e il browser mostra una pagina sotto `/admin` diversa dalla pagina di login.
FAIL: il login viene rifiutato, oppure dopo il login il browser torna/rimane sulla pagina di login o mostra un errore 403.
BLOCKED: l'ambiente UAT non è raggiungibile o l'utente seed non esiste.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy (l'ETL reale, `v1:import --anonymize`, gira ad ogni deploy su `develop`).

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-02 — Un utente senza nessuno dei 5 ruoli applicativi non accede al pannello

**Obiettivo**
Verifica che un utente autenticabile ma privo di qualunque dei 5 ruoli applicativi validi (Admin/Developer/Manager/Customer/Fundraising) non riesca ad accedere al pannello, anche se le credenziali sono corrette. È il caso negativo del gate d'accesso.

**Riferimenti**
- Requisito/regola di dominio: gate d'accesso al pannello Filament (§9.1/§9.2 del PRD) — nessuno dei 5 ruoli validi ⇒ accesso negato.
- Test automatico: `tests/Feature/Domain/Identity/PanelAccessTest.php` — `a user without any of the 5 valid roles cannot access the panel`
- File/componente applicativo rilevante: `app/Domain/Identity/Models/User.php` (metodo `canAccessPanel()`)
- Test correlato: F0-01, F0-03

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore (predisposizione dato via CLI) + Customer/utente senza ruolo (esecuzione del tentativo di login)

**Prerequisiti**
- Accesso SSH/console all'ambiente UAT con `php artisan tinker` disponibile (il form di creazione utente del pannello non espone un campo password: US-021, `UserForm` ha solo Anagrafica/Ruoli/Permessi diretti, quindi un utente creato da UI non potrebbe mai autenticarsi via password — la creazione del dato di test richiede quindi un passo tecnico).
- Nessun utente con email `senza-ruolo@orchestrator.local` già presente.

**Dati di test**
Email: `senza-ruolo@orchestrator.local` — Password: `uat` — nessun ruolo assegnato.

**Stato iniziale**
Nessun utente con l'email indicata esiste ancora nel database UAT.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Aprire `php artisan tinker` sul container applicativo ed eseguire `\App\Domain\Identity\Models\User::factory()->create(['name' => 'Utente Test Senza Ruolo', 'email' => 'senza-ruolo@orchestrator.local', 'password' => bcrypt('uat')]);` (nessuna chiamata a `assignRole`) | comando tinker sopra | Il comando restituisce l'istanza dell'utente creato, senza errori |
| 2 | Verificare in tinker che l'utente non abbia ruoli: `\App\Domain\Identity\Models\User::where('email','senza-ruolo@orchestrator.local')->first()->getRoleNames();` | comando tinker sopra | Il comando restituisce una collection vuota |
| 3 | Aprire il browser e navigare alla pagina di login del pannello | `https://ticket-uat.montagnaservizi.com/admin/login` | Viene mostrata la pagina di login |
| 4 | Compilare email/password e premere "Accedi" | `senza-ruolo@orchestrator.local` / `uat` | Il tentativo di accesso al pannello viene rifiutato (l'utente non atterra su una pagina del pannello) |

**Risultato finale atteso**
L'utente `senza-ruolo@orchestrator.local` non ottiene mai una sessione utilizzabile sul pannello `/admin`, pur avendo credenziali corrette.

**Controlli negativi**
Il tentativo di login con credenziali corrette ma nessun ruolo applicativo deve essere sempre respinto dal gate del pannello (non solo dal form di login).

**Evidenze da acquisire**
- Output del comando tinker di verifica ruoli (passo 2).
- Screenshot della pagina/messaggio ottenuto dopo il tentativo di login al passo 4.

**Criterio di superamento**

PASS: dopo il login il browser non mostra alcuna pagina del pannello (rimane sulla pagina di login o mostra un errore di accesso negato).
FAIL: l'utente riesce a raggiungere una qualunque pagina sotto `/admin` dopo il login.
BLOCKED: non è possibile eseguire `tinker` sull'ambiente UAT per creare il dato di test.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Eliminare l'utente di test creato: `\App\Domain\Identity\Models\User::where('email','senza-ruolo@orchestrator.local')->forceDelete();` via tinker. In alternativa, il dataset si rigenera comunque al prossimo deploy.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-03 — Un utente disattivato non accede al pannello anche con un ruolo valido

**Obiettivo**
Verifica che la disattivazione (`deactivated_at` valorizzato) prevalga sempre sul possesso di un ruolo applicativo valido, incluso il ruolo Admin: un utente disattivato non deve mai accedere al pannello.

**Riferimenti**
- Requisito/regola di dominio: gate d'accesso al pannello Filament (§9.1) — `deactivated_at` non nullo ⇒ accesso negato, controllato prima del controllo sul ruolo.
- Test automatico: `tests/Feature/Domain/Identity/PanelAccessTest.php` — `a deactivated user cannot access the panel even with a valid role`
- File/componente applicativo rilevante: `app/Domain/Identity/Models/User.php` (metodo `canAccessPanel()`, primo controllo su `deactivated_at`)
- Test correlato: F0-01, F0-04

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore (predisposizione dato via CLI) + utente disattivato (esecuzione del tentativo di login)

**Prerequisiti**
- Accesso a `php artisan tinker` sull'ambiente UAT (il form utente del pannello non espone un campo per `deactivated_at`: US-021, `UserInfolist` lo mostra in sola lettura, `UserForm` non lo include come campo modificabile — la disattivazione non è impostabile da UI in questa release).
- Nessun utente con email `disattivato@orchestrator.local` già presente.

**Dati di test**
Email: `disattivato@orchestrator.local` — Password: `uat` — Ruolo: Admin — `deactivated_at`: data/ora corrente.

**Stato iniziale**
Nessun utente con l'email indicata esiste ancora nel database UAT.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | In `php artisan tinker`, creare l'utente disattivato con ruolo Admin: `$u = \App\Domain\Identity\Models\User::factory()->create(['name' => 'Amministratore Disattivato Collaudo', 'email' => 'disattivato@orchestrator.local', 'password' => bcrypt('uat'), 'deactivated_at' => now()]); $u->assignRole('admin');` | comando tinker sopra | Il comando restituisce l'utente creato senza errori |
| 2 | Verificare in tinker che l'utente abbia il ruolo e `deactivated_at` valorizzato: `\App\Domain\Identity\Models\User::where('email','disattivato@orchestrator.local')->first(['deactivated_at'])->deactivated_at;` | comando tinker sopra | Il comando restituisce una data/ora non nulla |
| 3 | Aprire il browser e navigare alla pagina di login del pannello | `https://ticket-uat.montagnaservizi.com/admin/login` | Viene mostrata la pagina di login |
| 4 | Compilare email/password e premere "Accedi" | `disattivato@orchestrator.local` / `uat` | Il tentativo di accesso al pannello viene rifiutato nonostante il ruolo Admin |

**Risultato finale atteso**
L'utente `disattivato@orchestrator.local`, pur avendo il ruolo Admin, non ottiene mai una sessione utilizzabile sul pannello.

**Controlli negativi**
Il possesso del ruolo più privilegiato (Admin) non deve mai bypassare il controllo di disattivazione.

**Evidenze da acquisire**
- Output del comando tinker di verifica al passo 2.
- Screenshot dell'esito del tentativo di login al passo 4.

**Criterio di superamento**

PASS: dopo il login il browser non mostra alcuna pagina del pannello.
FAIL: l'utente disattivato riesce a raggiungere una pagina sotto `/admin`.
BLOCKED: non è possibile eseguire `tinker` sull'ambiente UAT per creare il dato di test.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Eliminare l'utente di test: `\App\Domain\Identity\Models\User::where('email','disattivato@orchestrator.local')->forceDelete();` via tinker. In alternativa, il dataset si rigenera al prossimo deploy.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-04 — Le query di selezione utenti escludono gli utenti disattivati

**Obiettivo**
Verifica che lo scope `User::scopeActive()` sia realmente applicato nei campi di selezione utenti dell'interfaccia (es. assegnatario/tester del ticket), così che un utente disattivato non compaia mai come opzione selezionabile.

**Riferimenti**
- Requisito/regola di dominio: `User::scopeActive()` come punto unico per escludere utenti disattivati da qualunque query di selezione (nota in CLAUDE.md, US-020).
- Test automatico: `tests/Feature/Domain/Identity/PanelAccessTest.php` — `the active scope excludes deactivated users from a user selection query`
- File/componente applicativo rilevante: `app/Domain/Identity/Models/User.php` (metodo `scopeActive()`), `app/Filament/Resources/Tickets/Schemas/TicketForm.php` (metodo `activeUsersQuery()`, campi assegnatario/tester)
- Test correlato: F0-03

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore (predisposizione dato via CLI) + Manager (verifica UI sul form ticket)

**Prerequisiti**
- Esiste un utente disattivato con nome riconoscibile (riusare `disattivato@orchestrator.local` / "Amministratore Disattivato Collaudo" creato in F0-03, oppure crearlo ex novo con lo stesso comando tinker se F0-03 non è stato eseguito in questa sessione).
- Esiste almeno un ticket già presente in UAT su cui aprire il form di modifica (l'ETL reale, `v1:import --anonymize`, popola l'ambiente fresco con i ticket del dump v1 importato).
- Il tester ha il ruolo Manager (`manager@oc.test`), che ha `ticket.update.any` e vede la sezione "Assegnazione e classificazione".

**Dati di test**
Utente disattivato: "Amministratore Disattivato Collaudo" (`disattivato@orchestrator.local`). Utente attivo di confronto: "Lorena Sava" (`lorena.sava@montagnaservizi.com`).

**Stato iniziale**
L'utente "Amministratore Disattivato Collaudo" esiste con `deactivated_at` valorizzato. Almeno un ticket esiste nel sistema.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere al pannello come `manager@oc.test` | `manager@oc.test` / `uat` | Login riuscito, dashboard/board di lavoro visibile |
| 2 | Aprire un ticket qualunque in modifica dalla lista ticket | un qualunque ticket dell'elenco | Si apre la form di modifica del ticket |
| 3 | Aprire il menu a tendina del campo "Assegnatario" (o "Tester") nella sezione "Assegnazione e classificazione" | campo assegnatario | Il menu a tendina mostra un elenco di utenti |
| 4 | Cercare nell'elenco il nome "Amministratore Disattivato Collaudo" | testo di ricerca "Disattivato" | Il nome non compare tra le opzioni selezionabili |
| 5 | Cercare nell'elenco il nome "Lorena Sava" | testo di ricerca "Sviluppatore" | Il nome compare tra le opzioni selezionabili |

**Risultato finale atteso**
Il campo di selezione assegnatario/tester del ticket non propone mai un utente disattivato, e propone regolarmente gli utenti attivi.

**Controlli negativi**
Nessun modo per selezionare un utente disattivato come assegnatario/tester dal form.

**Evidenze da acquisire**
- Screenshot del menu a tendina aperto con la ricerca "Disattivato" (nessun risultato).
- Screenshot del menu a tendina con la ricerca "Sviluppatore" (risultato presente).

**Criterio di superamento**

PASS: l'utente disattivato non compare nel menu, l'utente attivo compare.
FAIL: l'utente disattivato compare come opzione selezionabile.
BLOCKED: nessun ticket disponibile su cui aprire il form, o utente disattivato non predisposto.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuna modifica persistente da annullare (il test non salva alcuna assegnazione). L'utente di test disattivato può essere rimosso come da F0-03.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-05 — Il catalogo ruoli contiene esattamente i 5 ruoli previsti

**Obiettivo**
Verifica che l'enum `UserRole` (fonte di verità dei ruoli applicativi, §9.2 del PRD) contenga esattamente i 5 case attesi (Admin, Developer, Manager, Customer, Fundraising) e nessun altro (in particolare nessun ruolo "editor"). Non è un controllo eseguibile in modo affidabile da un tester funzionale tramite l'interfaccia: la fonte di verità è il codice PHP dell'enum, non una lista visualizzata (l'elenco Ruoli in UI riflette lo stato già materializzato dal seeder, non l'enum stesso).

**Riferimenti**
- Requisito/regola di dominio: PRD §9.2 — catalogo ruoli applicativi.
- Test automatico: `tests/Unit/Domain/Identity/UserRoleTest.php` — `contains exactly the 5 roles of PRD §9.2, no editor`
- File/componente applicativo rilevante: `app/Domain/Identity/Enums/UserRole.php`
- Test correlato: F0-08, F0-12

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente di sviluppo/CI con dipendenze Composer installate e Pest funzionante.

**Dati di test**
Nessuno (test unitario puro sull'enum, nessun dato di sistema coinvolto).

**Stato iniziale**
Codice sorgente allineato al commit da collaudare.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Eseguire la suite mirata: `php -d memory_limit=1G vendor/bin/pest tests/Unit/Domain/Identity/UserRoleTest.php` | comando sopra | Il comando termina con esito verde (nessun test fallito) |
| 2 | Leggere l'output del test `contains exactly the 5 roles of PRD §9.2, no editor` | output del comando | Il test risulta passato (✓) |
| 3 | Aprire `app/Domain/Identity/Enums/UserRole.php` e contare i case dell'enum | file sorgente | Sono presenti esattamente 5 case: `Admin`, `Developer`, `Manager`, `Customer`, `Fundraising`; nessun case `Editor` |

**Risultato finale atteso**
La suite Pest per `UserRoleTest` è verde e il file sorgente conferma esattamente 5 case, coerenti con l'elenco atteso.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output testuale del comando Pest (screenshot o testo copiato).
- Verificato in data odierna dall'estensore di questo manuale: l'esecuzione della suite filtrata su questo file risulta verde (`PASS Tests\Unit\Domain\Identity\UserRoleTest`, 3 test superati).

**Criterio di superamento**

PASS: il test Pest indicato risulta verde.
FAIL: il test Pest indicato risulta rosso/fallito.
BLOCKED: non è possibile eseguire la suite Pest sull'ambiente di collaudo tecnico.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: test di sola lettura, nessuna scrittura su database.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-06 — Il catalogo permessi contiene esattamente i permessi previsti

**Obiettivo**
Verifica che l'enum `Permission` (catalogo permessi, §9.3 del PRD) contenga esattamente i 52 permessi attesi, nel formato `<dominio>.<azione>[.<ambito>]`, senza permessi mancanti o aggiuntivi. Come per F0-05, non è verificabile in modo affidabile tramite interfaccia utente: nessuna pagina del pannello elenca il catalogo enum in sé.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.3 — catalogo permessi.
- Test automatico: `tests/Unit/Domain/Identity/PermissionTest.php` — `contains exactly the permission catalog of PRD §9.3`
- File/componente applicativo rilevante: `app/Domain/Identity/Enums/Permission.php`
- Test correlato: F0-08, F0-09

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente di sviluppo/CI con dipendenze Composer installate e Pest funzionante.

**Dati di test**
Nessuno (test unitario puro sull'enum).

**Stato iniziale**
Codice sorgente allineato al commit da collaudare.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Eseguire la suite mirata: `php -d memory_limit=1G vendor/bin/pest tests/Unit/Domain/Identity/PermissionTest.php` | comando sopra | Il comando termina con esito verde |
| 2 | Leggere l'output del test `contains exactly the permission catalog of PRD §9.3` | output del comando | Il test risulta passato (✓) |
| 3 | Aprire `app/Domain/Identity/Enums/Permission.php` e contare i case dell'enum | file sorgente | Sono presenti esattamente 52 case, raggruppati per dominio (ticket, ticket-message, ticket-log, tag, documentation, activity-report, organization, fundraising, user, email, horizon/logs/import) |

**Risultato finale atteso**
La suite Pest per `PermissionTest` è verde e il file sorgente conferma esattamente 52 case.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output testuale del comando Pest.
- Verificato in data odierna dall'estensore di questo manuale: l'esecuzione della suite filtrata su questo file risulta verde (`PASS Tests\Unit\Domain\Identity\PermissionTest`, 3 test superati).

**Criterio di superamento**

PASS: il test Pest indicato risulta verde.
FAIL: il test Pest indicato risulta rosso/fallito.
BLOCKED: non è possibile eseguire la suite Pest sull'ambiente di collaudo tecnico.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: test di sola lettura, nessuna scrittura su database.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-07 — Le tabelle di ruoli/permessi sono pubblicate correttamente: guard unico web, nessuna gestione a team

**Obiettivo**
Verifica che l'integrazione `spatie/laravel-permission` sia configurata come richiesto dal progetto: un solo guard (`web`) per ruoli e permessi, e la gestione "a team" disattivata (`config('permission.teams') === false`). È una verifica di configurazione/infrastruttura, non un comportamento osservabile da un utente finale nell'interfaccia.

**Riferimenti**
- Requisito/regola di dominio: nota CLAUDE.md "Identità / spatie-permission (US-010)" — guard unico `web`, niente teams.
- Test automatico: `tests/Feature/Domain/Identity/PermissionTablesTest.php` — `roles and permissions default to the single web guard`
- File/componente applicativo rilevante: `config/permission.php`, `config/auth.php` (`auth.defaults.guard`)
- Test correlato: F0-08

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a `php artisan tinker` sull'ambiente da collaudare.

**Dati di test**
Nessuno (verifica di configurazione, non di dati applicativi).

**Stato iniziale**
Applicazione avviata con la configurazione del commit da collaudare.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | In `php artisan tinker`, eseguire `config('permission.teams');` | comando tinker | Il comando restituisce `false` |
| 2 | Eseguire `config('auth.defaults.guard');` | comando tinker | Il comando restituisce `"web"` |
| 3 | Eseguire `\Spatie\Permission\Models\Role::query()->distinct()->pluck('guard_name');` | comando tinker | Il risultato contiene solo il valore `"web"` (o è vuoto se nessun ruolo ancora seedato) |
| 4 | Eseguire `\Spatie\Permission\Models\Permission::query()->distinct()->pluck('guard_name');` | comando tinker | Il risultato contiene solo il valore `"web"` (o è vuoto se nessun permesso ancora seedato) |

**Risultato finale atteso**
Tutte le righe di `roles`/`permissions` usano esclusivamente il guard `web`; la gestione a team è disattivata.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output testuale dei 4 comandi tinker.

**Criterio di superamento**

PASS: tutti e 4 i comandi restituiscono i valori attesi.
FAIL: `permission.teams` risulta diverso da `false`, oppure esiste una riga con `guard_name` diverso da `web`.
BLOCKED: `tinker` non è eseguibile sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: solo letture, nessuna scrittura.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-08 — Il seeder di ruoli/permessi assegna a ciascun ruolo esattamente i permessi previsti dalla matrice

**Obiettivo**
Verifica che `RolePermissionSeeder` materializzi esattamente la matrice ruolo→permessi documentata in §9.4 del PRD (e ricopiata nel file del seeder stesso), per ciascuno dei 5 ruoli. È un controllo di confronto puntuale su decine di righe per ruolo: non è praticabile in modo affidabile da un tester funzionale via interfaccia (richiederebbe di contare a mano fino a 52 permessi per ruolo nella pagina "Ruoli" del pannello), va eseguito con una query diretta sul database.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.4 — matrice ruolo/permesso.
- Test automatico: `tests/Feature/Domain/Identity/RolePermissionSeederTest.php` — `the seeder materializes exactly the §9.4 role/permission matrix`
- File/componente applicativo rilevante: `database/seeders/RolePermissionSeeder.php` (costante `ROLE_PERMISSIONS`)
- Test correlato: F0-05, F0-06, F0-09

**Modalità di esecuzione**
TECNICO DATABASE

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a `psql` (o equivalente) sul database dell'ambiente da collaudare, oppure a `php artisan tinker`.
- Il seeder `RolePermissionSeeder` è già stato eseguito sull'ambiente (avviene ad ogni deploy).

**Dati di test**
Ruolo di riferimento per il controllo puntuale in questa procedura: `manager` (23 permessi attesi, elencati in `RolePermissionSeeder::ROLE_PERMISSIONS['manager']`).

**Stato iniziale**
Le tabelle `roles`, `permissions`, `role_has_permissions` sono popolate dall'ultima esecuzione del seeder.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Eseguire la query: `SELECT p.name FROM role_has_permissions rhp JOIN roles r ON r.id = rhp.role_id JOIN permissions p ON p.id = rhp.permission_id WHERE r.name = 'manager' ORDER BY p.name;` | query SQL sopra | La query restituisce un elenco di nomi di permesso |
| 2 | Confrontare l'elenco ottenuto con l'elenco documentato in `database/seeders/RolePermissionSeeder.php`, chiave `'manager'` | elenco atteso dal file sorgente | I due elenchi coincidono esattamente (stessi nomi, nessuno in più o in meno) |
| 3 | Eseguire la query: `SELECT COUNT(*) FROM permissions;` | query SQL sopra | Il conteggio corrisponde al numero di case dell'enum `Permission` (52) |
| 4 | Eseguire la query: `SELECT COUNT(*) FROM roles;` | query SQL sopra | Il conteggio corrisponde al numero di case dell'enum `UserRole` (5) |
| 5 | Eseguire la query: `SELECT COUNT(*) FROM role_has_permissions rhp JOIN roles r ON r.id = rhp.role_id WHERE r.name = 'admin';` | query SQL sopra | Il conteggio è pari al totale dei permessi (52): l'Admin ha tutti i permessi del catalogo |

**Risultato finale atteso**
Per ciascun ruolo, l'insieme dei permessi effettivamente presenti in `role_has_permissions` coincide esattamente con la matrice documentata nel seeder; Admin ha l'intero catalogo.

**Controlli negativi**
Nessun permesso della matrice risulta mancante o aggiuntivo per il ruolo `manager` controllato al passo 2.

**Evidenze da acquisire**
- Output delle 4 query SQL.
- Screenshot/estratto del confronto tra elenco DB ed elenco sorgente per `manager`.

**Criterio di superamento**

PASS: tutti i confronti coincidono esattamente.
FAIL: almeno un permesso risulta mancante o presente in eccesso rispetto alla matrice documentata.
BLOCKED: non è possibile eseguire query dirette sul database dell'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: solo letture, nessuna scrittura.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-09 — I permessi riservati non sono assegnati a nessun ruolo salvo Admin

**Obiettivo**
Verifica che i tre permessi "di sistema" (`horizon.access`, `logs.access`, `import.view`) risultino assegnati esclusivamente al ruolo Admin, e a nessun altro ruolo applicativo. Sono permessi sensibili (accesso a code/log/import) che non devono trapelare per errore ad altri ruoli.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.4 — permessi di sistema riservati all'Admin.
- Test automatico: `tests/Feature/Domain/Identity/RolePermissionSeederTest.php` — `horizon.access, logs.access and import.view are not granted to any role except admin`
- File/componente applicativo rilevante: `database/seeders/RolePermissionSeeder.php` (i tre permessi non compaiono in nessuna chiave di `ROLE_PERMISSIONS` tranne implicitamente in Admin, che riceve l'intero catalogo)
- Test correlato: F0-08

**Modalità di esecuzione**
TECNICO DATABASE

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a `psql` (o equivalente) sul database dell'ambiente da collaudare.
- Il seeder `RolePermissionSeeder` è già stato eseguito.

**Dati di test**
Permessi da verificare: `horizon.access`, `logs.access`, `import.view`.

**Stato iniziale**
Le tabelle `roles`, `permissions`, `role_has_permissions` sono popolate dall'ultima esecuzione del seeder.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Eseguire: `SELECT r.name FROM role_has_permissions rhp JOIN roles r ON r.id = rhp.role_id JOIN permissions p ON p.id = rhp.permission_id WHERE p.name = 'horizon.access';` | query SQL sopra | L'unico risultato restituito è `admin` |
| 2 | Ripetere la stessa query sostituendo `'horizon.access'` con `'logs.access'` | query SQL sopra | L'unico risultato restituito è `admin` |
| 3 | Ripetere la stessa query sostituendo `'horizon.access'` con `'import.view'` | query SQL sopra | L'unico risultato restituito è `admin` |

**Risultato finale atteso**
Per ciascuno dei 3 permessi, il ruolo `admin` è l'unico associato in `role_has_permissions`.

**Controlli negativi**
Nessuno dei ruoli Manager, Developer, Customer, Fundraising deve comparire tra i risultati delle 3 query.

**Evidenze da acquisire**
- Output delle 3 query SQL.

**Criterio di superamento**

PASS: tutte e 3 le query restituiscono esclusivamente `admin`.
FAIL: almeno una query restituisce un ruolo diverso da `admin`, o nessun risultato.
BLOCKED: non è possibile eseguire query dirette sul database dell'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: solo letture, nessuna scrittura.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-10 — Il seeder di ruoli/permessi è idempotente e revoca un permesso/ruolo rimosso dal catalogo senza lasciarlo orfano

**Obiettivo**
Verifica che eseguire `RolePermissionSeeder` più volte non produca duplicati né effetti collaterali, e che un permesso o ruolo creato manualmente al di fuori del catalogo enum (simulando una riga "orfana" rimasta da un refactor) venga rimosso alla riesecuzione successiva del seeder, senza lasciare riferimenti residui nelle tabelle pivot.

**Riferimenti**
- Requisito/regola di dominio: nota CLAUDE.md "Seeder idempotente ruolo → permessi (US-018)" — `firstOrCreate` + `syncPermissions` + `whereNotIn(...)->delete()` per revocare gli orfani.
- Test automatico: `tests/Feature/Domain/Identity/RolePermissionSeederTest.php` — `running the seeder twice is idempotent` (più i due test correlati sulla revoca di permesso/ruolo orfano nello stesso file)
- File/componente applicativo rilevante: `database/seeders/RolePermissionSeeder.php`
- Test correlato: F0-08

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a `php artisan tinker` e ad `artisan db:seed` su un ambiente non di produzione (staging/UAT), dove sia accettabile creare temporaneamente una riga "orfana" di test.

**Dati di test**
Permesso orfano di prova: `legacy.orphan-permission` (guard `web`), assegnato temporaneamente al ruolo `manager`.

**Stato iniziale**
Il seeder è già stato eseguito almeno una volta; le tabelle `roles`/`permissions` riflettono il catalogo enum corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Annotare il conteggio corrente: `\Spatie\Permission\Models\Permission::count();` in tinker | comando tinker | Restituisce un numero N (atteso: 52) |
| 2 | Rieseguire il seeder: `(new \Database\Seeders\RolePermissionSeeder)->run();` in tinker | comando tinker | Il comando termina senza errori |
| 3 | Ricontrollare il conteggio: `\Spatie\Permission\Models\Permission::count();` | comando tinker | Il conteggio è invariato rispetto al passo 1 (nessun duplicato introdotto) |
| 4 | Creare una riga orfana e assegnarla a `manager`: `$p = \Spatie\Permission\Models\Permission::create(['name' => 'legacy.orphan-permission', 'guard_name' => 'web']); \Spatie\Permission\Models\Role::where('name','manager')->first()->givePermissionTo($p);` | comando tinker sopra | La riga viene creata e associata senza errori |
| 5 | Verificare che la riga orfana esista: `\Spatie\Permission\Models\Permission::where('name','legacy.orphan-permission')->exists();` | comando tinker | Restituisce `true` |
| 6 | Rieseguire il seeder: `(new \Database\Seeders\RolePermissionSeeder)->run();` | comando tinker | Il comando termina senza errori |
| 7 | Verificare che la riga orfana sia stata rimossa: `\Spatie\Permission\Models\Permission::where('name','legacy.orphan-permission')->exists();` | comando tinker | Restituisce `false` |

**Risultato finale atteso**
Due esecuzioni consecutive del seeder non alterano i conteggi delle righe legittime; una riga orfana creata manualmente fuori dal catalogo enum viene rimossa dalla riesecuzione successiva, insieme al collegamento con il ruolo.

**Controlli negativi**
Il ruolo `manager` non deve mantenere alcun riferimento residuo a `legacy.orphan-permission` dopo il passo 7.

**Evidenze da acquisire**
- Output di tutti i comandi tinker dei passi 1-7.

**Criterio di superamento**

PASS: tutti i risultati attesi ai passi 1-7 si verificano.
FAIL: il conteggio cambia tra due esecuzioni consecutive del seeder, oppure la riga orfana sopravvive dopo il passo 6.
BLOCKED: non è possibile eseguire `tinker`/il seeder sull'ambiente da collaudare senza rischio per dati reali.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Se il test viene interrotto prima del passo 6/7, rimuovere manualmente la riga di test: `\Spatie\Permission\Models\Permission::where('name','legacy.orphan-permission')->delete();`. In condizioni normali il passo 6 già ripristina lo stato corretto.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-11 — Un utente senza il permesso richiesto riceve sempre accesso negato; con il permesso viene autorizzato

**Obiettivo**
Verifica il funzionamento deny-by-default di `UserPolicy`: un utente privo dei permessi `user.*` non deve poter compiere alcuna azione sugli utenti (visualizzare l'elenco, vedere il dettaglio, creare, modificare, disattivare, assegnare ruoli, concedere permessi), mentre un utente con il permesso corretto deve poterla compiere.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.1/§9.3 — ogni azione sul modello `User` è gated da un permesso dedicato, nessuna azione raggiungibile senza policy.
- Test automatico: `tests/Feature/Domain/Identity/UserPolicyTest.php` — `a user without the matching permission is denied on every UserPolicy ability` (e il test correlato `a user with the matching permission is authorized on each UserPolicy ability` nello stesso file)
- File/componente applicativo rilevante: `app/Domain/Identity/Policies/UserPolicy.php`, `app/Filament/Resources/Users/UserResource.php`, `app/Filament/Resources/Users/Tables/UsersTable.php`, `app/Filament/Resources/Users/Pages/EditUser.php`
- Test correlato: F0-12, F0-13

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Customer (caso negativo) + Admin (caso positivo) + Sviluppatore (verifica tecnica dell'abilità "impersonate", priva di un pulsante in UI in questa release)

**Prerequisiti**
- Utenti seed `infosentieroitalia@cai.it` (ruolo Customer: nessun permesso `user.*` nella matrice §9.4) e `info@montagnaservizi.com` (tutti i permessi) disponibili.
- Esiste almeno un altro utente nell'elenco su cui provare le azioni (es. `manager@oc.test`).

**Dati di test**
Attore negativo: `infosentieroitalia@cai.it`. Attore positivo: `info@montagnaservizi.com`. Utente bersaglio: `manager@oc.test`.

**Stato iniziale**
Gli utenti seed esistono con i ruoli assegnati dall'ETL reale (`v1:import --anonymize`) e da `collaudo:ensure-manager-account` (per `manager@oc.test`, ruolo non presente in v1).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere al pannello come `infosentieroitalia@cai.it` | `infosentieroitalia@cai.it` / `uat` | Login riuscito |
| 2 | Verificare se la voce di menu "Utenti" è visibile in navigazione | — | La voce "Utenti" non compare nel menu (Customer non ha `user.view`) |
| 3 | Tentare di navigare direttamente all'URL della lista utenti (`/admin/users`) | URL diretto | Viene mostrata una pagina di accesso negato (403), non l'elenco utenti |
| 4 | Effettuare il logout e accedere come `info@montagnaservizi.com` | `info@montagnaservizi.com` / `uat` | Login riuscito |
| 5 | Aprire la voce di menu "Utenti" | — | L'elenco utenti viene mostrato correttamente (Admin ha `user.view`) |
| 6 | Aprire in modifica l'utente `manager@oc.test` e osservare i pulsanti disponibili nella testata | — | Sono visibili i pulsanti Visualizza/Elimina (coerenti con `user.view`/`user.deactivate` posseduti da Admin) |
| 7 | (Verifica tecnica) In `php artisan tinker`, per l'abilità "impersonate" (priva di un pulsante dedicato nell'interfaccia in questa release): `$admin = \App\Domain\Identity\Models\User::where('email','info@montagnaservizi.com')->first(); $target = \App\Domain\Identity\Models\User::where('email','manager@oc.test')->first(); $admin->can('impersonate', $target);` | comando tinker sopra | Restituisce `true` (Admin ha `user.impersonate`) |
| 8 | Ripetere il comando del passo 7 sostituendo `$admin` con l'utente Customer | comando tinker sopra | Restituisce `false` (Customer non ha `user.impersonate`) |

**Risultato finale atteso**
Customer (nessun permesso `user.*`) non può accedere in alcun modo alla gestione utenti; Admin (tutti i permessi) può farlo integralmente, incluse le abilità prive di un riscontro visivo diretto in UI.

**Controlli negativi**
Il tentativo di navigazione diretta all'URL dell'elenco utenti da parte di Customer (passo 3) deve essere respinto lato server, non solo nascosto dal menu.

**Evidenze da acquisire**
- Screenshot del menu di Customer senza la voce "Utenti".
- Screenshot della pagina 403 al passo 3.
- Screenshot dell'elenco utenti e della form di modifica visti da Admin.
- Output dei comandi tinker dei passi 7-8.

**Criterio di superamento**

PASS: tutti i risultati attesi dei passi 1-8 si verificano.
FAIL: Customer riesce ad accedere all'elenco utenti o a un'azione di gestione, oppure Admin viene bloccato su un'azione per cui ha il permesso.
BLOCKED: gli utenti seed non sono disponibili sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuna modifica persistente da annullare (nessun salvataggio effettuato).

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note: l'abilità "impersonate" non ha un pulsante dedicato nell'interfaccia in questa release: il caso positivo/negativo è verificato solo tecnicamente (passi 7-8). Se il Product Owner prevede un punto di ingresso UI per l'impersonificazione in una fase successiva, questo test andrà esteso con una procedura UI dedicata.

---

### F0-12 — Un admin può assegnare/revocare ruoli e permessi diretti di un utente dalla UI; la risorsa Ruoli resta di sola lettura

**Obiettivo**
Verifica che un utente con `user.assign-roles`/`user.grant-permissions` possa assegnare un ruolo e concedere un permesso diretto a un altro utente tramite la form di modifica utente del pannello, e che la risorsa "Ruoli" non esponga alcuna azione di creazione, modifica o eliminazione (i ruoli si materializzano solo dal seeder).

**Riferimenti**
- Requisito/regola di dominio: nota CLAUDE.md "UI gestione ruoli/permessi — primo Filament Resource del repo (US-021)".
- Test automatico: `tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php` — `an admin with user.assign-roles can assign a role to a user via the edit form` (e i test correlati nello stesso file su concessione permesso diretto e assenza di azioni di scrittura sulla risorsa Ruoli)
- File/componente applicativo rilevante: `app/Filament/Resources/Users/Schemas/UserForm.php` (sezioni "Ruoli"/"Permessi diretti"), `app/Filament/Resources/Roles/RoleResource.php` (metodi `canCreate()`/`canEdit()`/`canDelete()`/`canDeleteAny()` sempre `false`), `app/Filament/Resources/Roles/Pages/ListRoles.php`, `app/Filament/Resources/Roles/Pages/ViewRole.php`
- Test correlato: F0-13, F0-11

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Admin

**Prerequisiti**
- Utente seed `info@montagnaservizi.com` disponibile (ha sia `user.assign-roles` sia `user.grant-permissions`, essendo Admin l'unico ruolo con l'intero catalogo).
- Esiste almeno un altro utente su cui assegnare ruolo/permesso (es. `sara.mariani@montagnaservizi.com`, o un utente di test dedicato creato al passo 1).

**Dati di test**
Utente bersaglio: nome "Utente Test Ruoli" (creato al passo 1). Ruolo da assegnare: "Cliente" (Customer). Permesso diretto da concedere: "Creare ticket" (`ticket.create`).

**Stato iniziale**
Nessun utente con nome "Utente Test Ruoli" esiste ancora. Le tabelle Ruoli/Permessi sono popolate dal seeder.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere al pannello come `info@montagnaservizi.com` e creare un nuovo utente da "Utenti" → "Nuovo" | Nome: "Utente Test Ruoli", Email: `utente-test-ruoli@orchestrator.local`, Locale: `it` | L'utente viene creato e si viene reindirizzati alla sua scheda |
| 2 | Aprire l'utente appena creato in modifica | — | Si apre la form di modifica con le sezioni "Anagrafica", "Ruoli", "Permessi diretti" visibili |
| 3 | Nella sezione "Ruoli", selezionare la checkbox "Cliente" e salvare | Ruolo: "Cliente" | Il salvataggio avviene senza errori di validazione |
| 4 | Aprire la scheda (vista) dell'utente e osservare la sezione "Ruoli assegnati" | — | Compare il badge "Cliente" |
| 5 | Tornare in modifica, nella sezione "Permessi diretti" selezionare la checkbox "Creare ticket" e salvare | Permesso: "Creare ticket" | Il salvataggio avviene senza errori di validazione |
| 6 | Navigare alla voce di menu "Ruoli" | — | Si apre l'elenco dei 5 ruoli, in sola visualizzazione |
| 7 | Osservare la testata dell'elenco Ruoli e la testata della scheda di un ruolo aperto in visualizzazione | — | Non è presente alcun pulsante "Nuovo"/"Crea", "Modifica" o "Elimina" in nessuna delle due pagine |
| 8 | Tentare di navigare direttamente a un ipotetico URL di modifica ruolo (es. `/admin/roles/1/edit`) | URL diretto | Viene mostrata una pagina di errore (la rotta non esiste) |

**Risultato finale atteso**
L'utente di test ha il ruolo Cliente e il permesso diretto `ticket.create`; la risorsa Ruoli resta interamente di sola lettura, senza alcun punto di ingresso per crearne/modificarne/eliminarne uno.

**Controlli negativi**
Nessuna azione di scrittura (crea/modifica/elimina) deve essere raggiungibile sulla risorsa Ruoli, né tramite pulsanti né tramite URL diretto.

**Evidenze da acquisire**
- Screenshot della form di modifica con "Cliente" selezionato.
- Screenshot della scheda utente con il badge "Cliente".
- Screenshot dell'elenco Ruoli senza pulsante "Nuovo".
- Screenshot dell'esito del tentativo di URL diretto al passo 8.

**Criterio di superamento**

PASS: tutti i risultati attesi dei passi 1-8 si verificano.
FAIL: il ruolo/permesso non viene assegnato correttamente, oppure è presente un qualunque punto di ingresso di scrittura sulla risorsa Ruoli.
BLOCKED: non è possibile creare l'utente di test o accedere come Admin sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Eliminare l'utente di test "Utente Test Ruoli" dalla UI (azione "Elimina" nella scheda utente), oppure lasciarlo: il dataset si rigenera comunque al prossimo deploy.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-13 — La scheda utente mostra i permessi effettivi con la provenienza (dal ruolo oppure diretto)

**Obiettivo**
Verifica che la sezione "Permessi effettivi" della scheda utente elenchi tutti i permessi posseduti dall'utente (derivati dai ruoli assegnati e/o concessi direttamente), indicando per ciascuno la provenienza esatta: nome del ruolo che lo concede, oppure la dicitura "diretto", oppure entrambi se il permesso arriva da entrambe le fonti.

**Riferimenti**
- Requisito/regola di dominio: nota CLAUDE.md "UI gestione ruoli/permessi — primo Filament Resource del repo (US-021)" — provenienza dei permessi effettivi.
- Test automatico: `tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php` — `effective permissions are listed with their provenance (role vs direct)` (e il test correlato nello stesso file sul permesso concesso sia da ruolo sia direttamente)
- File/componente applicativo rilevante: `app/Filament/Resources/Users/Schemas/UserInfolist.php` (metodo `effectivePermissionLines()`)
- Test correlato: F0-12

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Utente di test "Utente Test Ruoli" con ruolo Cliente e permesso diretto `ticket.create` già predisposto come in F0-12 (oppure ripetere i passi 1-5 di F0-12 se non ancora eseguiti in questa sessione).

**Dati di test**
Utente: "Utente Test Ruoli". Ruolo assegnato: "Cliente" (concede tra gli altri "Creare ticket" già come permesso di ruolo, essendo `ticket.create` nella matrice Customer). Permesso diretto aggiuntivo concesso: "Creare ticket" (`ticket.create`, lo stesso permesso già derivato dal ruolo, per osservare il caso "doppia provenienza").

**Stato iniziale**
L'utente "Utente Test Ruoli" ha il ruolo Cliente assegnato e il permesso diretto `ticket.create` concesso (F0-12).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere al pannello come `info@montagnaservizi.com` e aprire la scheda (vista) dell'utente "Utente Test Ruoli" | — | Si apre la scheda utente con le sezioni "Ruoli assegnati" e "Permessi effettivi" |
| 2 | Osservare la sezione "Permessi effettivi" e individuare la riga relativa a "Creare ticket" | — | La riga mostra sia il nome del ruolo "Cliente" sia la dicitura "diretto" tra parentesi (doppia provenienza) |
| 3 | Individuare nella stessa sezione una riga relativa a un permesso derivato solo dal ruolo Cliente (es. "Visualizzare documentazione cliente") | — | La riga mostra il nome del ruolo "Cliente" tra parentesi, senza la dicitura "diretto" |
| 4 | Verificare che non compaiano permessi non posseduti (es. "Eliminare utenti") | — | Nessuna riga relativa a permessi non posseduti dall'utente |

**Risultato finale atteso**
La sezione "Permessi effettivi" riflette fedelmente l'unione di permessi da ruolo e diretti, con provenienza esplicita e corretta per ciascuna riga.

**Controlli negativi**
Nessun permesso non posseduto dall'utente deve comparire nell'elenco.

**Evidenze da acquisire**
- Screenshot della sezione "Permessi effettivi" con la riga a doppia provenienza evidenziata.

**Criterio di superamento**

PASS: le provenienze mostrate corrispondono esattamente a quanto atteso ai passi 2-4.
FAIL: una provenienza risulta mancante, errata, o un permesso non posseduto compare in elenco.
BLOCKED: l'utente di test non è disponibile o non ha lo stato richiesto dai prerequisiti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuna modifica aggiuntiva effettuata in questo test (sola visualizzazione). Vedi ripristino di F0-12 per l'utente di test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

## Schema dati — anagrafiche e organizzazioni

### F0-14 — La tabella utenti rispetta i vincoli richiesti: email unica case-insensitive, soft delete

**Obiettivo**
Verifica che la tabella `users` esponga un indice funzionale che consenta la ricerca per email ignorando maiuscole/minuscole (oltre al vincolo `unique` case-sensitive standard sulla colonna), e che i record utente supportino la cancellazione soft (recuperabile). È una verifica di schema a livello di indice database, non un comportamento visibile direttamente in un'unica schermata dell'interfaccia.

**Riferimenti**
- Requisito/regola di dominio: PRD §5.2 — colonne/vincoli della tabella `users`; nota CLAUDE.md "Identità / spatie-permission (US-010)" — pattern indice funzionale case-insensitive.
- Test automatico: `tests/Feature/Domain/Identity/UsersTableTest.php` — `email can be looked up case-insensitively via the functional index`
- File/componente applicativo rilevante: `database/migrations/0001_01_01_000000_create_users_table.php` (istruzione `create index users_email_lower_index on users (lower(email))`), `app/Domain/Identity/Models/User.php` (trait `SoftDeletes`)
- Test correlato: F0-15

**Modalità di esecuzione**
TECNICO DATABASE

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a `psql` (o equivalente) sul database dell'ambiente da collaudare.

**Dati di test**
Email di prova: `Mixed.Case@Example.test` (inserita con questa combinazione esatta di maiuscole/minuscole).

**Stato iniziale**
Nessun utente con questa email (in nessuna combinazione di maiuscole/minuscole) esiste ancora.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Verificare l'esistenza dell'indice funzionale: `SELECT indexname, indexdef FROM pg_indexes WHERE tablename = 'users' AND indexname = 'users_email_lower_index';` | query SQL sopra | La query restituisce una riga, con `indexdef` contenente `lower(email)` |
| 2 | Inserire un utente di prova con email mista: (via tinker) `\App\Domain\Identity\Models\User::create(['name' => 'Test Case Email', 'email' => 'Mixed.Case@Example.test']);` | comando tinker sopra | L'utente viene creato senza errori |
| 3 | Eseguire la ricerca case-insensitive: `SELECT id FROM users WHERE lower(email) = lower('mixed.CASE@example.TEST');` | query SQL sopra | La query restituisce la riga creata al passo 2 |
| 4 | Eliminare (soft delete) l'utente di prova: (via tinker) `\App\Domain\Identity\Models\User::where('email','Mixed.Case@Example.test')->first()->delete();` | comando tinker sopra | Il comando termina senza errori |
| 5 | Verificare che l'utente non compaia in una query applicativa standard ma sia recuperabile: `SELECT deleted_at FROM users WHERE email = 'Mixed.Case@Example.test';` | query SQL sopra | La colonna `deleted_at` non è nulla (record presente ma marcato come cancellato) |

**Risultato finale atteso**
L'indice funzionale su `lower(email)` esiste ed è utilizzabile per ricerche case-insensitive; l'eliminazione di un utente è una soft delete (la riga resta nel database con `deleted_at` valorizzato).

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output delle query dei passi 1, 3 e 5.

**Criterio di superamento**

PASS: l'indice esiste, la ricerca case-insensitive trova la riga, la cancellazione risulta soft (deleted_at valorizzato, riga non fisicamente rimossa).
FAIL: l'indice non esiste, la ricerca case-insensitive non trova la riga, oppure la riga viene rimossa fisicamente dalla cancellazione.
BLOCKED: non è possibile eseguire query dirette sul database o comandi tinker sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere definitivamente l'utente di prova: (via tinker) `\App\Domain\Identity\Models\User::withTrashed()->where('email','Mixed.Case@Example.test')->first()->forceDelete();`.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-15 — Le organizzazioni collegano gli utenti con vincolo di unicità sulla coppia organizzazione/utente

**Obiettivo**
Verifica che la tabella pivot `organization_user` imponga un vincolo di unicità reale a livello di database sulla coppia `(organization_id, user_id)`, impedendo che lo stesso utente venga collegato due volte alla stessa organizzazione. Non esiste ancora, in questa release, una risorsa Filament dedicata alle organizzazioni: il vincolo non è quindi osservabile tramite interfaccia utente e va verificato direttamente sullo schema/dati.

**Riferimenti**
- Requisito/regola di dominio: PRD §5.2 — vincolo di unicità sulla coppia organizzazione/utente.
- Test automatico: `tests/Feature/Domain/Identity/OrganizationsTableTest.php` — `the organization/user pair is unique`
- File/componente applicativo rilevante: `database/migrations/2026_07_25_230638_create_organization_user_table.php` (`$table->unique(['organization_id', 'user_id'])`), `app/Domain/Identity/Models/User.php` (relazione `organizations()`)
- Test correlato: F0-14

**Modalità di esecuzione**
TECNICO DATABASE

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a `psql` (o equivalente) e/o `php artisan tinker` sull'ambiente da collaudare.
- Esiste almeno un'organizzazione tra quelle importate dall'ETL (numero e nomi dipendono dal dump caricato, non più un insieme fisso — vedi punto 13 di `00-istruzioni-generali.md`) e un utente (es. `infosentieroitalia@cai.it`).

**Dati di test**
Organizzazione: una qualunque tra quelle presenti nell'ambiente. Utente: `infosentieroitalia@cai.it`.

**Stato iniziale**
L'organizzazione e l'utente esistono; non è ancora garantito che siano già collegati (il passo 1 li collega esplicitamente se non lo sono già).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Verificare a livello di schema l'esistenza del vincolo: `SELECT indexname, indexdef FROM pg_indexes WHERE tablename = 'organization_user';` | query SQL sopra | È presente un indice unique sulla coppia `(organization_id, user_id)` |
| 2 | In tinker, collegare l'utente all'organizzazione: `$org = \App\Domain\Identity\Models\Organization::where('name','CAI Sezione di Aosta')->first(); $user = \App\Domain\Identity\Models\User::where('email','infosentieroitalia@cai.it')->first(); $org->users()->syncWithoutDetaching([$user->id]);` | comando tinker sopra | Il comando termina senza errori, il collegamento risulta presente |
| 3 | Tentare di inserire manualmente una riga duplicata per la stessa coppia: `INSERT INTO organization_user (organization_id, user_id, created_at, updated_at) VALUES (<id_org>, <id_user>, now(), now());` (sostituendo gli id reali osservati al passo 2) | query SQL sopra | L'inserimento viene rifiutato dal database con un errore di violazione del vincolo unique |

**Risultato finale atteso**
Non è possibile avere due righe `organization_user` per la stessa coppia `(organization_id, user_id)`: il database rifiuta l'inserimento duplicato.

**Controlli negativi**
Il secondo inserimento della stessa coppia deve fallire con un errore di vincolo di unicità, non essere silenziosamente ignorato né accettato.

**Evidenze da acquisire**
- Output della query sugli indici (passo 1).
- Messaggio di errore del database al tentativo di inserimento duplicato (passo 3).

**Criterio di superamento**

PASS: l'indice unique esiste e l'inserimento duplicato viene rifiutato dal database.
FAIL: l'inserimento duplicato viene accettato senza errore.
BLOCKED: non è possibile eseguire query dirette sul database dell'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuna riga duplicata resta nel database (il tentativo del passo 3 fallisce e non scrive nulla). Il collegamento creato al passo 2 può restare: riflette uno stato legittimo dei dati di collaudo.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-16 — Le organizzazioni sono protette da policy deny-by-default

**Obiettivo**
Verifica che nessuna azione sul modello `Organization` (visualizzare l'elenco, vedere il dettaglio, creare, modificare, eliminare) sia consentita a un utente privo dei permessi `organization.*` corrispondenti. Non esiste ancora, in questa release, una risorsa Filament per le organizzazioni: la verifica non è quindi eseguibile tramite interfaccia utente e va condotta a livello di codice/policy.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.1 — deny-by-default per ogni modello di dominio senza controllo esplicito sulla relazione col record (livello 1, solo permesso).
- Test automatico: `tests/Feature/Domain/Identity/OrganizationPolicyTest.php` — `a user without organization.* permissions is denied every OrganizationPolicy ability` (e il test correlato nello stesso file sul caso positivo con il permesso corrispondente)
- File/componente applicativo rilevante: `app/Domain/Identity/Policies/OrganizationPolicy.php`
- Test correlato: F0-11, F0-15

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente di sviluppo/CI con dipendenze Composer installate e Pest funzionante.

**Dati di test**
Nessuno oltre a quelli generati dal test automatico stesso (un utente senza permessi e un'organizzazione di prova).

**Stato iniziale**
Codice sorgente allineato al commit da collaudare.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Eseguire la suite mirata: `php -d memory_limit=1G vendor/bin/pest tests/Feature/Domain/Identity/OrganizationPolicyTest.php` | comando sopra | Il comando termina con esito verde |
| 2 | Leggere l'output del test `a user without organization.* permissions is denied every OrganizationPolicy ability` | output del comando | Il test risulta passato (✓): tutte le abilità (viewAny, view, create, update, delete) risultano negate |
| 3 | Leggere l'output del test `a user with the matching organization.* permission is authorized` | output del comando | Il test risulta passato (✓): ogni abilità risulta concessa con il permesso corrispondente |
| 4 | Aprire `app/Domain/Identity/Policies/OrganizationPolicy.php` e verificare che ogni metodo controlli un permesso `organization.*` dedicato | file sorgente | Ogni metodo (`viewAny`, `view`, `create`, `update`, `delete`) chiama `$user->can(Permission::Organization...)`, nessun metodo restituisce `true` incondizionatamente |

**Risultato finale atteso**
La suite Pest per `OrganizationPolicyTest` è verde e il codice sorgente conferma che ogni abilità è gated da un permesso `organization.*` dedicato, senza percorsi che bypassano il controllo.

**Controlli negativi**
Nessuno dei metodi della policy deve restituire `true` senza passare da `$user->can(...)`.

**Evidenze da acquisire**
- Output testuale del comando Pest.
- Verificato in data odierna dall'estensore di questo manuale: l'esecuzione della suite filtrata su questo file risulta verde (`PASS Tests\Feature\Domain\Identity\OrganizationPolicyTest`, 2 test superati).

**Criterio di superamento**

PASS: il test Pest indicato risulta verde e l'ispezione del codice conferma il controllo di permesso su ogni metodo.
FAIL: il test Pest indicato risulta rosso/fallito, oppure un metodo della policy risulta privo del controllo di permesso.
BLOCKED: non è possibile eseguire la suite Pest sull'ambiente di collaudo tecnico.
NOT APPLICABLE: Non previsto per questo test (nessuna risorsa UI esiste ancora per le organizzazioni in questa release).

**Ripristino**
Nessuno: test di sola lettura, nessuna scrittura su database.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

## Schema dati — Ticketing (tabelle e vincoli)

### F0-17 — La tabella `tickets` rispetta colonne, default e relazioni richieste

**Obiettivo**
Verificare che la tabella `tickets` esponga esattamente le colonne richieste dal PRD §5.2 (incluse le foreign key verso richiedente, assegnatario, tester e ticket padre) e che i comportamenti di default/integrità referenziale siano quelli documentati: un ticket creato senza valori espliciti riceve stato "Nuovo", tipo "Helpdesk", priorità "Bassa" e 0 minuti lavorati; l'eliminazione dell'utente richiedente/assegnatario/tester non elimina il ticket (la FK azzera solo il riferimento); l'eliminazione di un ticket padre non elimina i figli; un ticket eliminato (soft delete) resta in tabella ma sparisce dalle query applicative di default.

**Riferimenti**
- Requisito/regola di dominio: PRD §5.2 (schema `tickets`)
- Test automatico: `tests/Feature/Domain/Ticketing/TicketsTableTest.php` — file con 8 test (colonne, default documentati, cast enum, gerarchia padre/figlio, FK nullificate su parent/requester, soft delete, assenza di un unique accidentale su requester/status); test citato nel manifest: `tickets table has the columns required by §5.2`
- File/componente applicativo rilevante: migrazione di creazione `tickets`, modello `App\Domain\Ticketing\Models\Ticket`
- Test correlato: F0-24 (lo stato di default "Nuovo" è uno dei 12 valori dell'enum `TicketStatus`)

**Modalità di esecuzione**
MISTO (TECNICO DATABASE per lo schema/i vincoli FK; TECNICO CLI per i comportamenti di default ed Eloquent, non osservabili da un `\d` psql)

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso psql all'istanza Postgres dell'ambiente UAT
- Accesso a una shell nel container applicativo UAT per eseguire `php artisan tinker`

**Dati di test**
Nessun dato reale coinvolto: il test crea e rimuove record temporanei (`title = 'Verifica default UAT'`, un utente di factory) durante la sessione, senza toccare i ticket importati dall'ETL.

**Stato iniziale**
Ambiente UAT con i ticket importati dall'ETL reale (`v1:import --anonymize`) e nessuna riga aggiuntiva.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | In psql, eseguire `\d tickets` | — | Sono presenti tutte le 24 colonne: `id, parent_id, title, description, status, previous_status, status_changed_at, type, priority, requester_id, assignee_id, tester_id, fundraising_project_id, waiting_reason, problem_reason, estimated_hours, worked_minutes, staging_url, production_url, released_at, done_at, created_at, updated_at, deleted_at` |
| 2 | Nello stesso output, individuare le foreign key su `requester_id`, `assignee_id`, `tester_id`, `parent_id` | — | Ogni FK è dichiarata con azione `ON DELETE SET NULL` |
| 3 | Aprire `php artisan tinker` nel container app ed eseguire `$t = \App\Domain\Ticketing\Models\Ticket::create(['title' => 'Verifica default UAT', 'status_changed_at' => now()])->fresh();` poi `[$t->status, $t->type, $t->priority, $t->worked_minutes];` | — | Il risultato è `[TicketStatus::New, TicketType::Helpdesk, TicketPriority::Low, 0]` |
| 4 | Nella stessa sessione: `$u = \App\Domain\Identity\Models\User::factory()->create(); $t2 = \App\Domain\Ticketing\Models\Ticket::create(['title' => 'Verifica FK requester', 'requester_id' => $u->id, 'status_changed_at' => now()]); $u->forceDelete(); $t2->fresh()->requester_id;` | — | Il valore restituito è `null`; `\App\Domain\Ticketing\Models\Ticket::find($t2->id)` non è `null` (il ticket resta) |
| 5 | `$t->delete();` poi `\App\Domain\Ticketing\Models\Ticket::find($t->id);` e infine `\App\Domain\Ticketing\Models\Ticket::withTrashed()->find($t->id);` | — | La prima query restituisce `null`, la seconda restituisce il record (soft delete confermato) |

**Risultato finale atteso**
Schema, default e comportamenti FK/soft-delete della tabella `tickets` coincidono con quanto descritto nel PRD §5.2; nessuna riga di prova residua visibile ai tester funzionali al termine.

**Controlli negativi**
Nessuno applicabile (test di sola verifica struttura/comportamento, non un tentativo di azione da bloccare).

**Evidenze da acquisire**
- Output testuale del comando `\d tickets`
- Trascrizione/screenshot della sessione `tinker` con i risultati dei passi 3-5

**Criterio di superamento**

PASS: tutte le colonne, le azioni FK e i comportamenti di default osservati corrispondono a quanto descritto.
FAIL: manca una colonna, una FK ha un'azione diversa da `SET NULL`, oppure un default non corrisponde.
BLOCKED: impossibile accedere al database o alla shell del container applicativo UAT.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Eliminare i record temporanei creati in `tinker` (`Ticket::withTrashed()->find(...)->forceDelete()`, idem per l'utente di factory); in ogni caso il prossimo deploy rigenera l'ambiente con l'ETL reale (`v1:import --anonymize`).

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-18 — I messaggi di un ticket hanno un identificativo pubblico univoco e vengono eliminati a cascata col ticket

**Obiettivo**
Verificare che ogni messaggio di un ticket riceva automaticamente un ULID pubblico (colonna `ulid`, distinto dalla chiave primaria intera `id`), che quell'ULID sia vincolato come univoco a livello di database, e che l'eliminazione definitiva di un ticket elimini a cascata tutti i suoi messaggi.

**Riferimenti**
- Requisito/regola di dominio: PRD §5.2 (schema `ticket_messages`)
- Test automatico: `tests/Feature/Domain/Ticketing/TicketMessagesTableTest.php` — test citato: `a ulid is generated automatically on creation, id stays the auto-increment primary key` (il file contiene anche i test sull'unicità dell'ulid e sulla cascata di eliminazione, entrambi nello scope di questo caso)
- File/componente applicativo rilevante: migrazione `ticket_messages`, modello `App\Domain\Ticketing\Models\TicketMessage` (trait `HasUlids` con `uniqueIds()` sovrascritto)
- Test correlato: F0-19 (stesso pattern di cascata FK verso `tickets`, applicato a `ticket_logs`)

**Modalità di esecuzione**
MISTO (TECNICO DATABASE per schema/FK a cascata; TECNICO CLI per la generazione automatica dell'ULID, comportamento del livello Eloquent non osservabile da solo SQL)

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso psql all'istanza Postgres UAT
- Accesso a `php artisan tinker` nel container app UAT

**Dati di test**
Un ticket temporaneo (`title = 'Ticket per test cascata'`) e un messaggio ad esso collegato, creati ed eliminati nella stessa sessione di verifica.

**Stato iniziale**
Ambiente UAT con i 40 ticket seedati; nessun messaggio di prova presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | In psql, eseguire `\d ticket_messages` | — | Sono presenti le colonne `id, ulid, ticket_id, author_id, author_email, channel, visibility, body_html, body_text, email_message_id, is_legacy_import, posted_at, created_at, updated_at`; `ulid` ha un vincolo `UNIQUE`; `ticket_id` ha una FK con azione `ON DELETE CASCADE` |
| 2 | In `tinker`: `$ticket = \App\Domain\Ticketing\Models\Ticket::create(['title' => 'Ticket per test cascata', 'status_changed_at' => now()]); $m = \App\Domain\Ticketing\Models\TicketMessage::create(['ticket_id' => $ticket->id, 'channel' => \App\Domain\Ticketing\Enums\TicketMessageChannel::Web, 'body_text' => 'Prova', 'posted_at' => now()]);` poi `[$m->ulid, $m->getKeyName(), $m->id];` | — | `ulid` è una stringa non vuota in formato ULID (26 caratteri), `getKeyName()` restituisce `'id'`, `id` è un intero |
| 3 | `$ticket->forceDelete();` poi `\App\Domain\Ticketing\Models\TicketMessage::find($m->id);` | — | Il messaggio non è più trovabile: la FK a cascata lo ha eliminato insieme al ticket |

**Risultato finale atteso**
Ogni messaggio ha un ULID pubblico univoco generato automaticamente, distinto dalla PK intera; l'eliminazione definitiva di un ticket elimina sempre i suoi messaggi, senza lasciare righe orfane in `ticket_messages`.

**Controlli negativi**
Tentare di forzare due messaggi con lo stesso `ulid` (assegnazione diretta dell'attributo, poiché `ulid` non è mass-assignable) e salvare il secondo: l'operazione deve fallire con una violazione del vincolo unique a livello di database.

**Evidenze da acquisire**
- Output di `\d ticket_messages`
- Trascrizione della sessione `tinker` (passi 2-3 e del controllo negativo)

**Criterio di superamento**

PASS: ULID generato e univoco, PK `id` intera, cascata di eliminazione confermata.
FAIL: `ulid` mancante/non univoco a livello DB, oppure il messaggio sopravvive all'eliminazione del ticket.
BLOCKED: impossibile accedere al database o alla shell applicativa UAT.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il ticket e il messaggio di prova sono già stati eliminati dal passo 3 della procedura stessa.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-19 — Lo storico dei ticket registra un diff strutturato dei cambiamenti, non il valore grezzo del campo

**Obiettivo**
Verificare che `ticket_logs` memorizzi `event`/`from_status`/`to_status` come valori dell'enum tipizzato `TicketStatus`/`TicketLogEvent` (non stringhe grezze) e che la colonna `changes` contenga sempre un diff strutturato (es. `{"status": {"from": "new", "to": "assigned"}}`), mai il corpo integrale di un campo modificato; verificare inoltre che l'eliminazione definitiva di un ticket elimini a cascata il suo storico.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.2.1 (schema `ticket_logs`); convenzione di dominio "mai il valore grezzo del campo nel log" (vedi anche `TicketLogChanges::descriptionChanged()`, Fase 1)
- Test automatico: `tests/Feature/Domain/Ticketing/TicketLogsTableTest.php` — test citato: `event and status columns are cast to their backed enum, changes is a JSON diff (not the field body)`
- File/componente applicativo rilevante: migrazione `ticket_logs`, modello `App\Domain\Ticketing\Models\TicketLog`
- Test correlato: F0-27 (visualizzazione dello storico gestita da `TicketLogPolicy`)

**Modalità di esecuzione**
MISTO (TECNICO DATABASE per schema/FK a cascata; TECNICO CLI per il cast enum e la forma del JSON, comportamento Eloquent non verificabile da solo SQL)

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso psql all'istanza Postgres UAT
- Accesso a `php artisan tinker` nel container app UAT

**Dati di test**
Un ticket temporaneo e una riga di log con `changes = ['status' => ['from' => 'new', 'to' => 'assigned']]`.

**Stato iniziale**
Ambiente UAT con i 40 ticket seedati; ciascuno ha già righe reali in `ticket_logs` prodotte dal seeder/dalla macchina a stati (non toccate da questo test).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | In psql, eseguire `\d ticket_logs` | — | Sono presenti le colonne `id, ticket_id, user_id, event, from_status, to_status, changes, is_system, occurred_at, created_at, updated_at`; `ticket_id` ha FK `ON DELETE CASCADE`; `changes` è di tipo `jsonb` |
| 2 | In `tinker`: `$ticket = \App\Domain\Ticketing\Models\Ticket::create(['title' => 'Ticket con log di prova', 'status_changed_at' => now()]); $log = \App\Domain\Ticketing\Models\TicketLog::create(['ticket_id' => $ticket->id, 'event' => \App\Domain\Ticketing\Enums\TicketLogEvent::StatusChanged, 'from_status' => \App\Domain\Ticketing\Enums\TicketStatus::New, 'to_status' => \App\Domain\Ticketing\Enums\TicketStatus::Assigned, 'changes' => ['status' => ['from' => 'new', 'to' => 'assigned']], 'occurred_at' => now()])->fresh();` poi `[$log->event, $log->from_status, $log->to_status, $log->changes, $log->is_system];` | — | Il risultato è `[TicketLogEvent::StatusChanged, TicketStatus::New, TicketStatus::Assigned, ['status' => ['from' => 'new', 'to' => 'assigned']], false]` — nessuna stringa grezza, il diff è la struttura passata |
| 3 | `$ticket->forceDelete();` poi `\App\Domain\Ticketing\Models\TicketLog::find($log->id);` | — | Il log non è più trovabile: cascata di eliminazione confermata |

**Risultato finale atteso**
`ticket_logs` cast correttamente enum su `event`/`from_status`/`to_status`, `changes` conserva un diff strutturato leggibile (non un valore di campo grezzo), e la cascata verso `tickets` è confermata.

**Controlli negativi**
Nessuno applicabile: il test verifica una capacità dello schema, non un'azione da rifiutare.

**Evidenze da acquisire**
- Output di `\d ticket_logs`
- Trascrizione della sessione `tinker`

**Criterio di superamento**

PASS: cast enum corretti, `changes` è la struttura diff attesa, cascata confermata.
FAIL: un campo non è castato all'enum atteso, `changes` contiene un valore diverso dal diff strutturato, oppure il log sopravvive all'eliminazione del ticket.
BLOCKED: impossibile accedere al database o alla shell applicativa UAT.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il ticket e il log di prova sono già stati eliminati dal passo 3.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-20 — Al massimo una visualizzazione per (ticket, utente, giorno) è ammessa a livello di database

**Obiettivo**
Verificare che la tabella `ticket_views` imponga a livello di database (non solo applicativo) l'unicità della tripla `(ticket_id, user_id, viewed_on)`, coerentemente con la regola "una sola riga di visualizzazione per utente per ticket per giorno" (PRD §6.2.3).

**Riferimenti**
- Requisito/regola di dominio: PRD §6.2.3
- Test automatico: `tests/Feature/Domain/Ticketing/TicketViewsTableTest.php` — test citato: `the ticket/user/viewed_on triple is unique`
- File/componente applicativo rilevante: migrazione `ticket_views`, modello `App\Domain\Ticketing\Models\TicketView`
- Test correlato: F0-29 (la stessa tabella è protetta anche da `TicketViewPolicy`)

**Modalità di esecuzione**
TECNICO DATABASE

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso psql all'istanza Postgres UAT
- Conoscere l'`id` di un ticket esistente e di un utente esistente (es. il ticket con titolo "Il pulsante «Rinnova tessera» non risponde su Safari mobile" e l'utente `infosentieroitalia@cai.it`)

**Dati di test**
`ticket_id` e `user_id` di un ticket/utente reali dell'ambiente UAT; `viewed_on = CURRENT_DATE`.

**Stato iniziale**
Nessuna riga di `ticket_views` per la coppia (ticket, utente) scelta nella data odierna.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | In psql, eseguire `\d ticket_views` | — | Sono presenti le colonne `id, ticket_id, user_id, viewed_on, last_viewed_at, view_count, created_at, updated_at` e un indice `UNIQUE` sulla tripla `(ticket_id, user_id, viewed_on)` |
| 2 | Eseguire `INSERT INTO ticket_views (ticket_id, user_id, viewed_on, last_viewed_at, view_count, created_at, updated_at) VALUES (<id_ticket>, <id_utente>, CURRENT_DATE, now(), 1, now(), now());` | id ticket/utente reali | L'inserimento va a buon fine (1 riga inserita) |
| 3 | Ripetere esattamente lo stesso `INSERT` del passo 2 | Stessi valori | Postgres rifiuta l'inserimento con un errore di violazione del vincolo unique (`duplicate key value violates unique constraint`) |
| 4 | Ripetere l'`INSERT` cambiando solo `viewed_on` al giorno successivo | `viewed_on = CURRENT_DATE + 1` | L'inserimento va a buon fine: il vincolo non blocca righe che differiscono per il giorno |

**Risultato finale atteso**
Il vincolo unique sulla tripla `(ticket_id, user_id, viewed_on)` esiste realmente a livello di database e blocca solo i duplicati esatti della tripla.

**Controlli negativi**
Il passo 3 stesso è il controllo negativo: l'inserimento duplicato deve essere rifiutato dal database, non solo dall'eventuale validazione applicativa.

**Evidenze da acquisire**
- Output di `\d ticket_views`
- Messaggio di errore Postgres del passo 3

**Criterio di superamento**

PASS: il duplicato esatto è rifiutato, la variazione sul giorno è accettata.
FAIL: il duplicato esatto viene inserito senza errore, oppure manca l'indice unique in `\d`.
BLOCKED: impossibile accedere al database.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
`DELETE FROM ticket_views WHERE ticket_id = <id_ticket> AND user_id = <id_utente> AND viewed_on IN (CURRENT_DATE, CURRENT_DATE + 1);`

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-21 — Un utente non può essere aggiunto due volte come partecipante dello stesso ticket

**Obiettivo**
Verificare che la tabella `ticket_participants` imponga a livello di database l'unicità della coppia `(ticket_id, user_id)`, impedendo che uno stesso utente compaia due volte come partecipante dello stesso ticket.

**Riferimenti**
- Requisito/regola di dominio: PRD §5.2 (schema `ticket_participants`)
- Test automatico: `tests/Feature/Domain/Ticketing/TicketParticipantsTableTest.php` — test citato: `a ticket tracks its participants and the pair is unique`
- File/componente applicativo rilevante: migrazione `ticket_participants`, relazione `Ticket::participants()`
- Test correlato: F0-28 (gestione partecipanti riservata a chi ha `ticket.assign`)

**Modalità di esecuzione**
TECNICO DATABASE

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso psql all'istanza Postgres UAT
- Conoscere l'`id` di un ticket e di un utente reali (es. `lorena.sava@montagnaservizi.com`)

**Dati di test**
`ticket_id` e `user_id` di un ticket/utente reali.

**Stato iniziale**
Nessuna riga di `ticket_participants` per la coppia scelta.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | In psql, eseguire `\d ticket_participants` | — | Sono presenti le colonne `id, ticket_id, user_id, created_at, updated_at` e un indice `UNIQUE` sulla coppia `(ticket_id, user_id)` |
| 2 | Eseguire `INSERT INTO ticket_participants (ticket_id, user_id, created_at, updated_at) VALUES (<id_ticket>, <id_utente>, now(), now());` | id ticket/utente reali | L'inserimento va a buon fine |
| 3 | Ripetere esattamente lo stesso `INSERT` | Stessi valori | Postgres rifiuta l'inserimento per violazione del vincolo unique |
| 4 | Ripetere l'`INSERT` con lo stesso `ticket_id` ma un `user_id` diverso | Un secondo utente reale | L'inserimento va a buon fine: il vincolo è sulla coppia, non sul solo `ticket_id` |

**Risultato finale atteso**
Il vincolo unique sulla coppia `(ticket_id, user_id)` esiste realmente a livello di database.

**Controlli negativi**
Il passo 3 è il controllo negativo: il duplicato esatto deve essere rifiutato dal database.

**Evidenze da acquisire**
- Output di `\d ticket_participants`
- Messaggio di errore Postgres del passo 3

**Criterio di superamento**

PASS: il duplicato esatto è rifiutato, la coppia con utente diverso è accettata.
FAIL: il duplicato viene inserito senza errore.
BLOCKED: impossibile accedere al database.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
`DELETE FROM ticket_participants WHERE ticket_id = <id_ticket> AND user_id IN (<id_utente>, <id_secondo_utente>);`

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-22 — Il collegamento ticket/tag ha un vincolo reale a livello di database, non solo applicativo

**Obiettivo**
Verificare che l'unicità della coppia `(ticket_id, tag_id)` nella tabella pivot `ticket_tag` sia un vincolo reale del database, non solo una regola applicata dall'applicazione: un inserimento diretto in SQL (che bypassa completamente Eloquent/le validazioni applicative) deve comunque essere rifiutato in caso di duplicato.

**Riferimenti**
- Requisito/regola di dominio: PRD §5.2 (schema `ticket_tag`)
- Test automatico: `tests/Feature/Domain/Ticketing/TicketTagTableTest.php` — test citato: `the ticket/tag pair is unique` (il test stesso usa `DB::table('ticket_tag')->insert()`, bypassando Eloquent, proprio per dimostrare che il vincolo è reale a livello DB)
- File/componente applicativo rilevante: migrazione `ticket_tag`, relazione `Ticket::tags()`
- Test correlato: F0-25 (schema di `tags`, incluso il vincolo FK verso `ticket_tag`)

**Modalità di esecuzione**
TECNICO DATABASE

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso psql all'istanza Postgres UAT
- Conoscere l'`id` di un ticket e di un tag reali (es. il tag "Frontend")

**Dati di test**
`ticket_id` e `tag_id` di un ticket/tag reali dell'ambiente UAT.

**Stato iniziale**
Il ticket scelto non ha già un collegamento verso il tag scelto (verificare prima con `SELECT * FROM ticket_tag WHERE ticket_id = <id> AND tag_id = <id>;`).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | In psql, eseguire `\d ticket_tag` | — | Sono presenti le colonne `id, ticket_id, tag_id, created_at, updated_at` e un indice `UNIQUE` sulla coppia `(ticket_id, tag_id)` |
| 2 | Eseguire `INSERT INTO ticket_tag (ticket_id, tag_id, created_at, updated_at) VALUES (<id_ticket>, <id_tag>, now(), now());` | id ticket/tag reali (una coppia non già esistente) | L'inserimento va a buon fine |
| 3 | Ripetere esattamente lo stesso `INSERT` | Stessi valori | Postgres rifiuta l'inserimento per violazione del vincolo unique, anche essendo un `INSERT` SQL diretto senza passare da alcuna validazione applicativa |

**Risultato finale atteso**
Il vincolo di unicità su `(ticket_id, tag_id)` è imposto dal database stesso, indipendentemente da qualunque controllo lato applicazione.

**Controlli negativi**
Il passo 3 è il controllo negativo principale del test.

**Evidenze da acquisire**
- Output di `\d ticket_tag`
- Messaggio di errore Postgres del passo 3

**Criterio di superamento**

PASS: il duplicato via SQL diretto è rifiutato dal database.
FAIL: il duplicato viene inserito senza errore (il vincolo esisterebbe solo a livello applicativo, non DB).
BLOCKED: impossibile accedere al database.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
`DELETE FROM ticket_tag WHERE ticket_id = <id_ticket> AND tag_id = <id_tag>;` (attenzione a non rimuovere collegamenti già presenti nel seed prima di eseguire la verifica preliminare dello stato iniziale).

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-23 — Le righe di ore lavorate sono uniche per (giorno, utente, ticket)

**Obiettivo**
Verificare che la tabella `ticket_work_logs` imponga a livello di database l'unicità della tripla `(work_date, user_id, ticket_id)` e che la colonna `minutes` abbia un valore di default `0` quando non specificato.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.2.2 (schema `ticket_work_logs`)
- Test automatico: `tests/Feature/Domain/Ticketing/TicketWorkLogsTableTest.php` — test citato: `the work_date/user/ticket triple is unique, minutes defaults to 0`
- File/componente applicativo rilevante: migrazione `ticket_work_logs`, modello `App\Domain\Ticketing\Models\TicketWorkLog`
- Test correlato: F0-30 (la stessa tabella è protetta da `TicketWorkLogPolicy`)

**Modalità di esecuzione**
TECNICO DATABASE

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso psql all'istanza Postgres UAT
- Conoscere l'`id` di un ticket e di un utente reali

**Dati di test**
`work_date = CURRENT_DATE`, `ticket_id`/`user_id` di un ticket/utente reali.

**Stato iniziale**
Nessuna riga di `ticket_work_logs` per la tripla scelta nella data odierna.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | In psql, eseguire `\d ticket_work_logs` | — | Sono presenti le colonne `id, work_date, user_id, ticket_id, minutes, created_at, updated_at`; `minutes` ha default `0`; esiste un indice `UNIQUE` sulla tripla `(work_date, user_id, ticket_id)` |
| 2 | Eseguire `INSERT INTO ticket_work_logs (work_date, user_id, ticket_id, created_at, updated_at) VALUES (CURRENT_DATE, <id_utente>, <id_ticket>, now(), now());` (senza specificare `minutes`) poi `SELECT minutes FROM ticket_work_logs WHERE work_date = CURRENT_DATE AND user_id = <id_utente> AND ticket_id = <id_ticket>;` | id ticket/utente reali | L'inserimento va a buon fine e `minutes` vale `0` |
| 3 | Ripetere lo stesso `INSERT` del passo 2 | Stessi valori | Postgres rifiuta l'inserimento per violazione del vincolo unique |

**Risultato finale atteso**
Il vincolo unique sulla tripla `(work_date, user_id, ticket_id)` esiste a livello di database e `minutes` ha default `0`.

**Controlli negativi**
Il passo 3 è il controllo negativo: il duplicato esatto deve essere rifiutato.

**Evidenze da acquisire**
- Output di `\d ticket_work_logs`
- Risultato della `SELECT` del passo 2
- Messaggio di errore Postgres del passo 3

**Criterio di superamento**

PASS: default `minutes = 0` confermato, duplicato rifiutato dal database.
FAIL: `minutes` non ha il default atteso, oppure il duplicato viene inserito senza errore.
BLOCKED: impossibile accedere al database.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
`DELETE FROM ticket_work_logs WHERE work_date = CURRENT_DATE AND user_id = <id_utente> AND ticket_id = <id_ticket>;`

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-24 — Lo stato del ticket copre esattamente i 12 valori previsti (incluso "Testing", non "Test")

**Obiettivo**
Verificare che il catalogo degli stati del ticket (`TicketStatus`) contenga esattamente i 12 valori previsti, nell'ordine e con i valori grezzi documentati, e che il caso relativo al collaudo interno sia identificato come `Testing` (non `Test`) nel codice sorgente. Verificare inoltre che l'interfaccia utente rifletta correttamente tutte le 12 etichette italiane.

**Riferimenti**
- Requisito/regola di dominio: PRD §5.2/§5.3 (12 stati del ciclo di vita del ticket)
- Test automatico: `tests/Unit/Domain/Ticketing/TicketStatusTest.php` — test citato: `contains exactly the 12 values of the v1, case Testing (not Test)` (il file contiene anche i test sull'implementazione delle interfacce Filament `HasLabel`/`HasColor`/`HasIcon` per ogni caso)
- File/componente applicativo rilevante: `app/Domain/Ticketing/Enums/TicketStatus.php`
- Test correlato: F0-17 (la colonna `status` di `tickets` è castata su questo stesso enum)

**Modalità di esecuzione**
MISTO (MANUALE UI per la verifica delle 12 etichette in interfaccia; TECNICO CLI per il confronto puntuale di valori/ordine; la convenzione di naming del case PHP `Testing` non è osservabile né in UI né in database, è una verifica di sola lettura del codice sorgente)

**Priorità**
Alta

**Ruolo del tester**
Manager (per la parte UI) e Sviluppatore (per la parte tinker/codice)

**Prerequisiti**
- Utenza `manager@oc.test` / `uat`
- Accesso a `php artisan tinker` nel container app UAT

**Dati di test**
I 12 valori attesi in ordine: `new` ("Nuovo"), `backlog` ("Backlog"), `assigned` ("Assegnato"), `todo` ("Da fare"), `progress` ("In lavorazione"), `testing` ("In test"), `tested` ("Testato"), `released` ("Rilasciato"), `done` ("Completato"), `problem` ("Problema"), `waiting` ("In attesa"), `rejected` ("Rifiutato").

**Stato iniziale**
Ambiente UAT con i 40 ticket seedati (coprono ciclicamente tutti e 12 gli stati).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere al pannello `/admin` come Manager e aprire la lista Ticket | manager@oc.test / uat | La lista si apre mostrando i ticket con una colonna "Stato" a badge colorato |
| 2 | Aprire il filtro "Stato" sulla tabella | — | Il menu a tendina elenca esattamente 12 opzioni, con le etichette italiane elencate nei Dati di test (inclusa "In test", mai "Test") |
| 3 | Scorrere la lista e individuare almeno un ticket per ciascuna delle etichette "In test" e "Testato" | — | I badge di stato mostrano il testo corretto ("In test"/"Testato"), non un valore grezzo come "testing"/"tested" |
| 4 | Nel container app, aprire `php artisan tinker` ed eseguire `array_map(fn ($c) => $c->value, \App\Domain\Ticketing\Enums\TicketStatus::cases());` | — | L'array restituito è, in ordine, `['new','backlog','assigned','todo','progress','testing','tested','released','done','problem','waiting','rejected']` (12 elementi) |
| 5 | Nella stessa sessione, eseguire `\App\Domain\Ticketing\Enums\TicketStatus::Testing->name;` | — | Il valore restituito è la stringa `'Testing'` |

**Risultato finale atteso**
L'enum `TicketStatus` contiene esattamente 12 casi con i valori/etichette documentati; il caso relativo al collaudo interno è identificato nel codice come `Testing`; l'interfaccia utente riflette correttamente tutte le etichette.

**Controlli negativi**
Verificare che il filtro "Stato" non mostri un tredicesimo valore né un valore duplicato/anomalo (es. un residuo `'test'` invece di `'testing'`).

**Evidenze da acquisire**
- Screenshot del menu a tendina del filtro "Stato" con le 12 opzioni
- Screenshot di almeno un badge "In test" e uno "Testato" nella lista
- Trascrizione della sessione `tinker` (passi 4-5)

**Criterio di superamento**

PASS: 12 valori/etichette corretti sia in UI sia da codice, case PHP `Testing` confermato.
FAIL: numero di stati diverso da 12, un'etichetta errata, oppure il case si chiama `Test` invece di `Testing`.
BLOCKED: impossibile accedere al pannello UAT o alla shell applicativa.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: test di sola lettura, nessuna scrittura effettuata.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-25 — I tag e le pagine di documentazione rispettano slug univoco e collegamento opzionale reciproco

**Obiettivo**
Verificare che la tabella `tags` imponga l'unicità dello `slug` a livello di database e che il collegamento opzionale verso una pagina di documentazione (`tags.documentation_id`) si comporti come atteso: l'eliminazione definitiva della pagina di documentazione collegata azzera il riferimento sul tag (non lo elimina), mentre l'eliminazione definitiva di un tag elimina a cascata i suoi collegamenti nella tabella pivot `ticket_tag`.

**Riferimenti**
- Requisito/regola di dominio: PRD §5.2 (schema `tags`)
- Test automatico: `tests/Feature/Domain/Tags/TagsTableTest.php` — test citato: `slug is unique` (il file contiene anche i test sul collegamento a `documentation_pages` e sulla FK a cascata verso `ticket_tag`, entrambi nello scope di questo caso)
- File/componente applicativo rilevante: migrazione `tags`, modello `App\Domain\Tags\Models\Tag`
- Test correlato: F0-22 (stesso schema pivot `ticket_tag`, verificato dal lato opposto)

**Modalità di esecuzione**
TECNICO DATABASE

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso psql all'istanza Postgres UAT
- Conoscere l'`id` di un tag esistente (uno qualunque tra quelli importati dall'ETL) privo di collegamenti in uso durante il test

**Dati di test**
Uno slug duplicato per il test di unicità (lo slug di un tag già esistente nell'ambiente); un tag e una pagina di documentazione temporanei per il test del collegamento opzionale.

**Stato iniziale**
Ambiente UAT con i tag e le pagine di documentazione importati dall'ETL reale (`v1:import --anonymize`, numero variabile secondo il dump caricato).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | In psql, eseguire `\d tags` | — | Sono presenti le colonne `id, name, slug, description, estimated_hours, documentation_id, created_at, updated_at, deleted_at`; `slug` ha un indice `UNIQUE`; `documentation_id` ha FK con azione `ON DELETE SET NULL` verso `documentation_pages` |
| 2 | Eseguire `INSERT INTO tags (name, slug, created_at, updated_at) VALUES ('Backend duplicato', 'backend', now(), now());` | slug `'backend'`, già usato dal tag seedato "Backend" | Postgres rifiuta l'inserimento per violazione del vincolo unique su `slug` |
| 3 | Creare un tag e una pagina di documentazione temporanei collegati: `INSERT INTO documentation_pages (title, slug, body, category, created_at, updated_at) VALUES ('Pagina di prova', 'pagina-di-prova', 'contenuto', 'customer', now(), now()) RETURNING id;` (annotare l'id `<doc_id>`), poi `INSERT INTO tags (name, slug, documentation_id, created_at, updated_at) VALUES ('Tag di prova', 'tag-di-prova', <doc_id>, now(), now()) RETURNING id;` (annotare `<tag_id>`) | — | Entrambi gli inserimenti riescono; `SELECT documentation_id FROM tags WHERE id = <tag_id>;` restituisce `<doc_id>` |
| 4 | Eliminare la pagina di documentazione: `DELETE FROM documentation_pages WHERE id = <doc_id>;` poi `SELECT documentation_id FROM tags WHERE id = <tag_id>;` | — | Il tag esiste ancora e `documentation_id` è ora `NULL` |
| 5 | Collegare il tag di prova a un ticket esistente (`INSERT INTO ticket_tag (ticket_id, tag_id, created_at, updated_at) VALUES (<id_ticket>, <tag_id>, now(), now());`), poi eliminare il tag (`DELETE FROM tags WHERE id = <tag_id>;`) e infine `SELECT * FROM ticket_tag WHERE tag_id = <tag_id>;` | id di un ticket reale | La riga in `ticket_tag` è scomparsa: la FK a cascata verso `tags` ha eliminato il collegamento insieme al tag |

**Risultato finale atteso**
`slug` è realmente univoco a livello database; il collegamento tag→documentazione è opzionale e si azzera (non elimina il tag) quando la pagina collegata sparisce; l'eliminazione di un tag elimina a cascata i suoi collegamenti in `ticket_tag`.

**Controlli negativi**
Il passo 2 è il controllo negativo principale (slug duplicato rifiutato).

**Evidenze da acquisire**
- Output di `\d tags`
- Messaggio di errore Postgres del passo 2
- Risultati delle `SELECT` dei passi 3-5

**Criterio di superamento**

PASS: slug univoco confermato, nullify su eliminazione pagina confermato, cascata su eliminazione tag confermata.
FAIL: uno qualunque dei tre comportamenti non corrisponde.
BLOCKED: impossibile accedere al database.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
`DELETE FROM tags WHERE slug = 'tag-di-prova';` (se non già eliminato al passo 5); verificare che non restino righe orfane in `ticket_tag` con `tag_id` inesistente.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

## Autorizzazioni per modulo — policy deny-by-default

### F0-26 — Un messaggio interno del ticket non è mai visibile senza il permesso dedicato

**Obiettivo**
Verificare che un messaggio di ticket con visibilità "interna" (`ticket-message.view.internal`) sia visibile solo a chi possiede quel permesso, mentre un messaggio pubblico resta visibile a chiunque possa già vedere il ticket. Nessuna interfaccia di questa release permette di creare un messaggio interno (`PostTicketMessage` crea sempre canale `web`/visibilità `public`): il messaggio interno va predisposto direttamente in database, poi la sua visibilità va osservata nella pagina di dettaglio del ticket.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.3/§9.5 (permesso `ticket-message.view.internal`)
- Test automatico: `tests/Feature/Domain/Ticketing/TicketMessagePolicyTest.php` — test citato: `a public ticket message is gated by ticket.view.*, an internal one by ticket-message.view.internal`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Policies/TicketMessagePolicy.php` (metodo `view()`), `TicketMessage::scopeVisibleTo()`, applicato in `app/Filament/Resources/Tickets/Schemas/TicketInfolist.php` (sezione "Conversazione", righe 109-124)
- Test correlato: F0-27 (stesso pattern di gating su un'altra sotto-risorsa del ticket)

**Modalità di esecuzione**
MISTO (TECNICO DATABASE per creare il messaggio interno, che nessuna UI di questa release può produrre; MANUALE UI per osservarne la visibilità differenziata per ruolo)

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore (predisposizione dati) e Manager/Customer (verifica UI)

**Prerequisiti**
- Accesso psql all'istanza Postgres UAT
- Utenze `manager@oc.test` e `infosentieroitalia@cai.it` (password `uat`)
- Un ticket il cui `requester_id` sia il Customer di collaudo (vale per tutti i 40 ticket seedati, dato che il richiedente è sempre "Sentiero Italia CAI - SICAI")

**Dati di test**
Ticket di riferimento: uno qualunque dei 40 ticket seedati (es. il primo, titolo "Il pulsante «Rinnova tessera» non risponde su Safari mobile"). Testo del messaggio interno da inserire: `'Nota interna di collaudo: non visibile al cliente.'`

**Stato iniziale**
Nessun messaggio con `visibility = 'internal'` esiste ancora sul ticket scelto (la conversazione seedata è composta solo da messaggi pubblici, canale web).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Individuare l'`id` del ticket di riferimento (`SELECT id FROM tickets WHERE title = 'Il pulsante «Rinnova tessera» non risponde su Safari mobile';`) | — | Un solo `id` restituito |
| 2 | Inserire il messaggio interno via psql: `INSERT INTO ticket_messages (ticket_id, channel, visibility, body_text, posted_at, created_at, updated_at) VALUES (<id_ticket>, 'web', 'internal', 'Nota interna di collaudo: non visibile al cliente.', now(), now(), now());` | id ticket, testo sopra | L'inserimento va a buon fine |
| 3 | Accedere al pannello come Manager e aprire il dettaglio del ticket di riferimento | manager@oc.test / uat | Nella sezione "Conversazione" compare anche il messaggio "Nota interna di collaudo: non visibile al cliente." |
| 4 | Disconnettersi e accedere come Customer, aprire lo stesso ticket (è una propria richiesta, quindi visibile) | infosentieroitalia@cai.it / uat | Nella sezione "Conversazione" il messaggio interno NON compare; sono visibili solo i messaggi pubblici della conversazione seedata |

**Risultato finale atteso**
Il messaggio con visibilità interna è visibile solo a chi ha il permesso `ticket-message.view.internal` (Manager, Developer, Admin) e mai a un Customer, indipendentemente dal fatto che il Customer possa già vedere il ticket stesso.

**Controlli negativi**
Verificare che il Customer, pur avendo accesso al ticket (è il proprio), non trovi alcuna traccia del testo del messaggio interno nella pagina (né nel DOM visibile né in un elemento nascosto).

**Evidenze da acquisire**
- Screenshot della conversazione vista da Manager (messaggio interno presente)
- Screenshot della conversazione vista da Customer (messaggio interno assente)

**Criterio di superamento**

PASS: il messaggio interno è visibile a Manager e assente per Customer.
FAIL: il messaggio interno è visibile anche al Customer, oppure non è visibile nemmeno al Manager.
BLOCKED: impossibile inserire il messaggio via psql o accedere al pannello con le utenze richieste.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
`DELETE FROM ticket_messages WHERE body_text = 'Nota interna di collaudo: non visibile al cliente.';`

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-27 — Lo storico del ticket è visualizzabile solo con il permesso dedicato e non è mai scrivibile manualmente

**Obiettivo**
Verificare che la sezione "Storico" del dettaglio ticket (basata su `ticket_logs`) sia visibile solo a chi ha il permesso `ticket-log.view`, e che in nessun caso — nemmeno per un utente con quel permesso — esista un modo dall'interfaccia per aggiungere, modificare o eliminare manualmente una riga di storico: le righe sono scritte solo dal sistema (transizioni di stato, assegnazioni).

**Riferimenti**
- Requisito/regola di dominio: PRD §9.3 (permesso `ticket-log.view`); §6.2.1 (storico scritto solo dal sistema)
- Test automatico: `tests/Feature/Domain/Ticketing/TicketLogPolicyTest.php` — test citato: `a user with ticket-log.view can only view logs, never write them (system-only writes)`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Policies/TicketLogPolicy.php`, `app/Filament/Resources/Tickets/Schemas/TicketInfolist.php` (sezione "Storico", riga 145-146: `->visible(fn (): bool => (bool) Auth::user()?->can(Permission::TicketLogView))`)
- Test correlato: F0-26 (stesso pattern di gating sulla conversazione del ticket)

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Manager e Customer

**Prerequisiti**
- Utenze `manager@oc.test` e `infosentieroitalia@cai.it` (password `uat`)
- Un ticket qualunque tra i 40 seedati (il Customer di collaudo è richiedente di tutti)

**Dati di test**
Ticket di riferimento: uno qualunque dei 40 ticket seedati.

**Stato iniziale**
Il ticket scelto ha già righe di storico prodotte dal seeder/dalla macchina a stati (creazione, eventuale cambio di stato).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere come Manager e aprire il dettaglio del ticket di riferimento | manager@oc.test / uat | La sezione "Storico" è presente e mostra almeno una riga (evento, utente, data) |
| 2 | Nella stessa sezione "Storico", cercare un pulsante o un'azione per aggiungere/modificare/eliminare una riga | — | Non esiste alcun controllo di scrittura: la sezione è un elenco di sola lettura |
| 3 | Disconnettersi e accedere come Customer, aprire lo stesso ticket | infosentieroitalia@cai.it / uat | La sezione "Storico" non è presente affatto nella pagina |

**Risultato finale atteso**
Lo storico è visibile in sola lettura solo a chi ha `ticket-log.view` (Manager/Developer/Admin) e completamente assente dalla pagina per chi non lo ha (Customer); nessuna azione di scrittura manuale è mai disponibile, per nessun ruolo.

**Controlli negativi**
Verificare che nella pagina vista dal Customer non compaia nemmeno l'intestazione "Storico" vuota (la sezione intera deve essere nascosta, non solo il contenuto).

**Evidenze da acquisire**
- Screenshot della sezione "Storico" vista da Manager
- Screenshot della pagina vista da Customer (sezione assente)

**Criterio di superamento**

PASS: sezione visibile in sola lettura per Manager, assente per Customer.
FAIL: la sezione è visibile al Customer, oppure esiste un controllo di scrittura per qualunque ruolo.
BLOCKED: impossibile accedere al pannello con le utenze richieste.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: test di sola lettura, nessuna scrittura effettuata.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-28 — La gestione dei partecipanti al ticket è riservata a chi ha il permesso di assegnazione

**Obiettivo**
Verificare che le azioni "Aggiungi partecipante"/"Rimuovi partecipante" sulla pagina di dettaglio ticket siano visibili solo a chi possiede il permesso `ticket.assign`, mentre la sola visualizzazione dei partecipanti resta legata alla normale visibilità del ticket.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.3 (permesso `ticket.assign` riusato per la gestione partecipanti, nessun permesso dedicato nel catalogo)
- Test automatico: `tests/Feature/Domain/Ticketing/TicketParticipantPolicyTest.php` — test citato: `viewing participants is gated by ticket.view.*, managing them by ticket.assign`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Policies/TicketParticipantPolicy.php`, `app/Filament/Resources/Tickets/Pages/ViewTicket.php` (metodo `participantActions()`, righe 116-161: le due action sono costruite solo se `$user->can(Permission::TicketAssign)`)
- Test correlato: F0-21 (schema/unicità della tabella `ticket_participants` sottostante)

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Manager e Customer

**Prerequisiti**
- Utenze `manager@oc.test` (ha `ticket.assign`) e `infosentieroitalia@cai.it` (non ha `ticket.assign`, solo `ticket.view.own`), password `uat`
- Un ticket qualunque tra i 40 seedati

**Dati di test**
Ticket di riferimento: uno qualunque dei 40 ticket seedati. Utente da aggiungere come partecipante: un utente attivo qualsiasi non già partecipante (es. `sara.mariani@montagnaservizi.com`).

**Stato iniziale**
Il ticket di riferimento ha già una sezione "Partecipanti" (eventualmente vuota o con l'autore dei messaggi già presente).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere come Manager e aprire il dettaglio del ticket di riferimento | manager@oc.test / uat | Tra le azioni di intestazione compaiono "Aggiungi partecipante" e "Rimuovi partecipante" |
| 2 | Eseguire "Aggiungi partecipante" selezionando un utente non ancora partecipante | Sara Mariani | Il partecipante compare nella sezione "Partecipanti"; notifica di successo "Partecipante aggiunto" |
| 3 | Eseguire "Rimuovi partecipante" sull'utente appena aggiunto | Sara Mariani | Il partecipante scompare dalla sezione "Partecipanti"; notifica di successo "Partecipante rimosso" |
| 4 | Disconnettersi e accedere come Customer, aprire lo stesso ticket | infosentieroitalia@cai.it / uat | La sezione "Partecipanti" è visibile (il Customer può vedere il proprio ticket), ma tra le azioni di intestazione NON compaiono "Aggiungi partecipante" né "Rimuovi partecipante" |

**Risultato finale atteso**
Solo chi ha `ticket.assign` (Manager/Developer/Admin) può aggiungere/rimuovere partecipanti; chiunque possa vedere il ticket può vedere l'elenco partecipanti, senza poterlo modificare senza il permesso.

**Controlli negativi**
Verificare che il Customer non possa richiamare l'azione di aggiunta/rimozione partecipante nemmeno digitando manualmente l'URL dell'azione Livewire (comportamento atteso: nessuna azione disponibile lato server, coerente con `TicketParticipantPolicy::create()/update()/delete()` che richiedono `ticket.assign`).

**Evidenze da acquisire**
- Screenshot delle azioni di intestazione viste da Manager (azioni presenti)
- Screenshot delle azioni di intestazione viste da Customer (azioni assenti)
- Screenshot della sezione "Partecipanti" prima/dopo i passi 2-3

**Criterio di superamento**

PASS: azioni presenti solo per Manager, elenco partecipanti comunque visibile al Customer senza controlli di modifica.
FAIL: il Customer vede o riesce ad azionare "Aggiungi"/"Rimuovi partecipante".
BLOCKED: impossibile accedere al pannello con le utenze richieste.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Verificare che il ticket di riferimento non abbia partecipanti residui aggiunti per il test (il passo 3 li rimuove già); in caso di residui, rimuoverli con la stessa azione "Rimuovi partecipante" da Manager.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-29 — Le visualizzazioni del ticket sono protette dalla stessa policy di visualizzazione del ticket

**Obiettivo**
Verificare che `TicketViewPolicy` (governa `ticket_views`, il marcatore "ultima visualizzazione") sia deny-by-default per chi non ha alcun permesso di visualizzazione ticket, e che chi ha `ticket.view.own`/`ticket.view.any`/`ticket.view.assigned` possa anche leggere/scrivere i propri marcatori di visualizzazione. Questa tabella non ha un'interfaccia utente dedicata in questa release (nessuna lista "visualizzazioni" è mostrata nel pannello): il marcatore viene scritto automaticamente all'apertura della pagina di dettaglio ticket, non è un'azione che un utente compie consapevolmente né può negare a sé stesso. La verifica dell'autorizzazione è quindi possibile solo a livello di codice/test automatico.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.3/§6.2.3 (nessun permesso dedicato nel catalogo: la policy riusa `ticket.view.*`)
- Test automatico: `tests/Feature/Domain/Ticketing/TicketViewPolicyTest.php` — test citato: `a user who can view tickets can also read/write their own view markers`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Policies/TicketViewPolicy.php`
- Test correlato: F0-20 (schema/unicità della tabella `ticket_views` sottostante)

**Modalità di esecuzione**
AUTOMATICO — nessuna interfaccia utente esiste per elencare o gestire `ticket_views` in questa release (il marcatore è scritto in automatico da `RecordTicketView` all'apertura della pagina ticket): la policy è verificabile solo eseguendo il test automatico o interrogando l'autorizzazione da `tinker`.

**Priorità**
Bassa

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a una shell con la suite di test disponibile (`php -d memory_limit=1G vendor/bin/pest`) oppure a `php artisan tinker` sull'ambiente di riferimento

**Dati di test**
Nessun dato reale necessario: il test automatico crea i propri utenti/permessi/ticket in un database di test isolato (SQLite in memoria).

**Stato iniziale**
N/A (test automatico, ambiente di test isolato).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Eseguire `php -d memory_limit=1G vendor/bin/pest --filter="TicketViewPolicyTest"` | — | I 2 test del file passano: un utente senza permessi di visualizzazione ticket è negato su ogni abilità (`viewAny/view/create/update/delete`); un utente con `ticket.view.own` è autorizzato su tutte |
| 2 | (facoltativo, in alternativa al passo 1) In `tinker`, creare un utente senza ruoli/permessi e verificare `$user->can('viewAny', \App\Domain\Ticketing\Models\TicketView::class);` | — | Restituisce `false` |
| 3 | Nella stessa sessione, assegnare il permesso `ticket.view.own` all'utente e ripetere la verifica | — | Restituisce `true` |

**Risultato finale atteso**
`TicketViewPolicy` nega per difetto e autorizza correttamente in presenza di un permesso di visualizzazione ticket, coerentemente con l'assenza di un permesso dedicato nel catalogo §9.3.

**Controlli negativi**
Il passo 1/2 stesso è il controllo negativo (utente senza permessi negato su tutte le abilità).

**Evidenze da acquisire**
- Output della suite Pest (passo 1) o trascrizione della sessione `tinker` (passi 2-3)

**Criterio di superamento**

PASS: il test automatico passa (o la verifica manuale in tinker produce gli stessi esiti).
FAIL: il test fallisce, o un utente senza permessi risulta autorizzato.
BLOCKED: impossibile eseguire la suite di test o accedere a `tinker`.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: test automatico su database isolato, nessun effetto sull'ambiente UAT.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-30 — Le ore lavorate registrate sono protette da policy dedicata (visualizzazione vs modifica)

**Obiettivo**
Verificare che `TicketWorkLogPolicy` distingua correttamente tra la capacità di visualizzare le righe di ore lavorate (gated da `ticket.view.*`) e quella di registrarle/modificarle (gated da `ticket.update.*`), e che un utente privo di qualunque permesso ticket sia negato su ogni abilità. Non esiste un'interfaccia utente per creare/elencare singole righe di `ticket_work_logs` in questa release (le ore lavorate sono calcolate e riscritte automaticamente da `RecalculateWorkedTime`, mai da un input utente diretto): la policy è verificabile solo a livello di codice/test automatico.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.3/§6.2.2 (nessun permesso `timetracking.*` dedicato: la policy riusa `ticket.view.*`/`ticket.update.*`)
- Test automatico: `tests/Feature/Domain/Ticketing/TicketWorkLogPolicyTest.php` — test citato: `viewing work logs is gated by ticket.view.*, logging hours by ticket.update.*`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Policies/TicketWorkLogPolicy.php`
- Test correlato: F0-23 (schema/unicità della tabella `ticket_work_logs` sottostante)

**Modalità di esecuzione**
AUTOMATICO — nessuna interfaccia utente espone singole righe di `ticket_work_logs` in questa release: verificabile solo eseguendo il test automatico o interrogando l'autorizzazione da `tinker`.

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a una shell con la suite di test disponibile

**Dati di test**
Nessun dato reale necessario: il test automatico usa un database di test isolato.

**Stato iniziale**
N/A (test automatico, ambiente di test isolato).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Eseguire `php -d memory_limit=1G vendor/bin/pest --filter="TicketWorkLogPolicyTest"` | — | I 2 test del file passano: un utente senza alcun permesso ticket è negato su ogni abilità; un utente con `ticket.view.own` può solo vedere (`view`=true, `create`=false), un utente con `ticket.update.own` può anche creare/modificare/eliminare |
| 2 | (facoltativo) In `tinker`, verificare che un utente con solo `ticket.view.own` abbia `$user->can('create', \App\Domain\Ticketing\Models\TicketWorkLog::class);` false e `$user->can('view', $workLog);` true su una riga di test | — | Coerente con il passo 1 |

**Risultato finale atteso**
La policy distingue correttamente visualizzazione (`ticket.view.*`) da scrittura (`ticket.update.*`) delle ore lavorate, negando per difetto chi non ha alcun permesso.

**Controlli negativi**
Il passo 1 stesso include il controllo negativo (utente senza permessi negato su tutto; utente solo-visualizzazione negato sulla creazione).

**Evidenze da acquisire**
- Output della suite Pest (passo 1)

**Criterio di superamento**

PASS: il test automatico passa.
FAIL: il test fallisce.
BLOCKED: impossibile eseguire la suite di test.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: test automatico su database isolato, nessun effetto sull'ambiente UAT.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-31 — I tag sono protetti da policy deny-by-default

**Obiettivo**
Verificare che `TagPolicy` neghi per difetto ogni abilità (`viewAny/view/create/update/delete/restore/forceDelete`) a un utente privo dei permessi `tag.*`, e che ciascun permesso specifico autorizzi esattamente l'abilità corrispondente. Nessuna interfaccia Filament per la gestione dei tag esiste ancora in questa release (i tag sono usati solo come etichette/filtro lato ticket): la policy è verificabile solo a livello di codice/test automatico.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.3 (permessi `tag.view/create/update/delete`)
- Test automatico: `tests/Feature/Domain/Tags/TagPolicyTest.php` — test citato: `a user without tag.* permissions is denied every TagPolicy ability`
- File/componente applicativo rilevante: `app/Domain/Tags/Policies/TagPolicy.php`
- Test correlato: F0-25 (schema della tabella `tags` sottostante)

**Modalità di esecuzione**
AUTOMATICO — nessuna interfaccia utente esiste ancora per la gestione dei tag in questa release: la policy è verificabile solo a livello di codice/test automatico.

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a una shell con la suite di test disponibile

**Dati di test**
Nessun dato reale necessario: il test automatico usa un database di test isolato.

**Stato iniziale**
N/A (test automatico, ambiente di test isolato).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Eseguire `php -d memory_limit=1G vendor/bin/pest --filter="TagPolicyTest"` | — | I 2 test del file passano: un utente senza permessi `tag.*` è negato su tutte le 7 abilità; un utente con il permesso corrispondente (`tag.view`/`tag.create`/`tag.update`/`tag.delete`) è autorizzato esattamente su quell'abilità (incluse `restore`/`forceDelete`, entrambe gated da `tag.delete`) |

**Risultato finale atteso**
`TagPolicy` è deny-by-default e ogni abilità corrisponde esattamente al permesso `tag.*` documentato.

**Controlli negativi**
Il passo 1 stesso è il controllo negativo (utente senza permessi negato su tutte le abilità).

**Evidenze da acquisire**
- Output della suite Pest

**Criterio di superamento**

PASS: il test automatico passa.
FAIL: il test fallisce (una qualunque abilità risulta autorizzata senza il permesso corrispondente, o negata pur avendolo).
BLOCKED: impossibile eseguire la suite di test.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: test automatico su database isolato, nessun effetto sull'ambiente UAT.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-32 — Le pagine di documentazione distinguono correttamente accesso cliente vs interno

**Obiettivo**
Verificare che `DocumentationPagePolicy` gestisca correttamente le due categorie di pagina (`customer`/`internal`): una pagina di categoria "cliente" è visibile solo con `documentation.view.customer`, una di categoria "interna" solo con `documentation.view.internal`, e un utente privo di entrambi i permessi è negato su ogni abilità. Nessuna interfaccia Filament per la documentazione esiste ancora in questa release: la policy è verificabile solo a livello di codice/test automatico.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.3 (permessi `documentation.view.customer`/`documentation.view.internal`)
- Test automatico: `tests/Feature/Domain/Documentation/DocumentationPagePolicyTest.php` — test citato: `a customer-category page is gated by documentation.view.customer, an internal one by documentation.view.internal`
- File/componente applicativo rilevante: `app/Domain/Documentation/Policies/DocumentationPagePolicy.php`
- Test correlato: F0-25 (schema di `tags`, che può collegarsi opzionalmente a una pagina di documentazione)

**Modalità di esecuzione**
AUTOMATICO — nessuna interfaccia utente esiste ancora per la documentazione in questa release: la policy è verificabile solo a livello di codice/test automatico.

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a una shell con la suite di test disponibile

**Dati di test**
Nessun dato reale necessario: il test automatico usa un database di test isolato con due pagine, una per categoria.

**Stato iniziale**
N/A (test automatico, ambiente di test isolato). Nota: l'ambiente UAT ha comunque 5 pagine di documentazione reali seedate (3 `customer`, 2 `internal`), utili solo come riferimento concettuale, non raggiungibili da UI.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Eseguire `php -d memory_limit=1G vendor/bin/pest --filter="DocumentationPagePolicyTest"` | — | I 3 test del file passano: un utente senza permessi `documentation.*` è negato su ogni abilità; un utente con `documentation.view.customer` vede solo la pagina di categoria cliente (non quella interna); un utente con `documentation.view.internal` vede solo quella interna (non quella cliente); un utente con `documentation.create/update/delete` può creare/modificare/eliminare (incluso `restore`/`forceDelete` via `documentation.delete`) |

**Risultato finale atteso**
`DocumentationPagePolicy` separa correttamente le due categorie di visibilità e nega per difetto chi non ha alcun permesso `documentation.*`.

**Controlli negativi**
Il passo 1 include il controllo negativo incrociato: chi ha solo `documentation.view.customer` deve risultare negato sulla pagina `internal`, e viceversa.

**Evidenze da acquisire**
- Output della suite Pest

**Criterio di superamento**

PASS: il test automatico passa.
FAIL: il test fallisce (es. un permesso di una categoria autorizza anche l'altra).
BLOCKED: impossibile eseguire la suite di test.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: test automatico su database isolato, nessun effetto sull'ambiente UAT.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-33 — I report di attività sono protetti da policy deny-by-default

**Obiettivo**
Verificare che `ActivityReportPolicy` neghi per difetto ogni abilità (`viewAny/view/create/update/delete/generatePdf`) a un utente privo dei permessi `activity-report.*`, e che ciascun permesso specifico autorizzi esattamente l'abilità corrispondente. Nessuna interfaccia Filament per i report di attività esiste ancora in questa release (generazione PDF fuori scope, Fase 4): la policy è verificabile solo a livello di codice/test automatico.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.3 (permessi `activity-report.*`)
- Test automatico: `tests/Feature/Domain/Reporting/ActivityReportPolicyTest.php` — test citato: `a user without any activity-report.* permission is denied every ActivityReportPolicy ability`
- File/componente applicativo rilevante: `app/Domain/Reporting/Policies/ActivityReportPolicy.php`
- Test correlato: F0-34 (stesso schema di policy deny-by-default senza interfaccia utente in questa release)

**Modalità di esecuzione**
AUTOMATICO — nessuna interfaccia utente esiste ancora per i report di attività in questa release: la policy è verificabile solo a livello di codice/test automatico.

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a una shell con la suite di test disponibile

**Dati di test**
Nessun dato reale necessario: il test automatico usa un database di test isolato con un report di esempio (`owner_kind='user'`, periodo mensile luglio 2026).

**Stato iniziale**
N/A (test automatico, ambiente di test isolato). Nota: l'ambiente UAT ha comunque report di attività reali seedati, non raggiungibili da UI.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Eseguire `php -d memory_limit=1G vendor/bin/pest --filter="ActivityReportPolicyTest"` | — | I 2 test del file passano: un utente senza permessi `activity-report.*` è negato su tutte le 6 abilità (incluso `generatePdf`); un utente con `activity-report.view.own`/`create`/`update`/`delete`/`generate-pdf` è autorizzato esattamente sull'abilità corrispondente |

**Risultato finale atteso**
`ActivityReportPolicy` è deny-by-default e ogni abilità corrisponde esattamente al permesso `activity-report.*` documentato.

**Controlli negativi**
Il passo 1 stesso è il controllo negativo (utente senza permessi negato su tutte le abilità).

**Evidenze da acquisire**
- Output della suite Pest

**Criterio di superamento**

PASS: il test automatico passa.
FAIL: il test fallisce.
BLOCKED: impossibile eseguire la suite di test.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: test automatico su database isolato, nessun effetto sull'ambiente UAT.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-34 — Le opportunità di fundraising sono protette da policy deny-by-default

**Obiettivo**
Verificare che `FundraisingOpportunityPolicy` neghi per difetto ogni abilità (`viewAny/view/create/update/delete/evaluate`) a un utente privo dei permessi `fundraising.*`, e che ciascun permesso specifico autorizzi esattamente l'abilità corrispondente. Nessuna interfaccia Filament dedicata al fundraising esiste ancora in questa release (fuori ambito fino alla Fase 5): la policy è verificabile solo a livello di codice/test automatico.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.3 (permessi `fundraising.*`)
- Test automatico: `tests/Feature/Domain/Fundraising/FundraisingOpportunityPolicyTest.php` — test citato: `a user without any fundraising.* permission is denied every FundraisingOpportunityPolicy ability`
- File/componente applicativo rilevante: `app/Domain/Fundraising/Policies/FundraisingOpportunityPolicy.php`
- Test correlato: F0-33 (stesso schema di policy deny-by-default senza interfaccia utente in questa release)

**Modalità di esecuzione**
AUTOMATICO — nessuna interfaccia utente esiste ancora per il fundraising in questa release: la policy è verificabile solo a livello di codice/test automatico.

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a una shell con la suite di test disponibile

**Dati di test**
Nessun dato reale necessario: il test automatico usa un database di test isolato con un'opportunità di esempio ("Bando Regione X").

**Stato iniziale**
N/A (test automatico, ambiente di test isolato). Nota: l'ambiente UAT ha comunque 3 opportunità di fundraising reali seedate, non raggiungibili da UI.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Eseguire `php -d memory_limit=1G vendor/bin/pest --filter="FundraisingOpportunityPolicyTest"` | — | I 2 test del file passano: un utente senza permessi `fundraising.*` è negato su tutte le 6 abilità (incluso `evaluate`); un utente con `fundraising.view.involved`/`create`/`update`/`delete`/`evaluate` è autorizzato esattamente sull'abilità corrispondente |

**Risultato finale atteso**
`FundraisingOpportunityPolicy` è deny-by-default e ogni abilità corrisponde esattamente al permesso `fundraising.*` documentato.

**Controlli negativi**
Il passo 1 stesso è il controllo negativo (utente senza permessi negato su tutte le abilità).

**Evidenze da acquisire**
- Output della suite Pest

**Criterio di superamento**

PASS: il test automatico passa.
FAIL: il test fallisce.
BLOCKED: impossibile eseguire la suite di test.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: test automatico su database isolato, nessun effetto sull'ambiente UAT.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-35 — I messaggi email sono protetti da policy deny-by-default

**Obiettivo**
Verificare che `EmailMessagePolicy` neghi per difetto ogni abilità (`viewAny/view/create/update/delete`) a un utente privo dei permessi `email.*`, e che `email.view` conceda solo la lettura mentre `email.manage` conceda anche la scrittura. Nessuna interfaccia Filament per la posta elettronica esiste ancora in questa release (sottosistema email reale fuori ambito fino alla Fase 3): la policy è verificabile solo a livello di codice/test automatico.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.3 (permessi `email.view`/`email.manage`)
- Test automatico: `tests/Feature/Domain/Mail/EmailMessagePolicyTest.php` — test citato: `a user without any email.* permission is denied every EmailMessagePolicy ability`
- File/componente applicativo rilevante: `app/Domain/Mail/Policies/EmailMessagePolicy.php`
- Test correlato: F0-26 (stesso principio di gating "visualizzazione ristretta" applicato a un'altra sotto-risorsa del ticket)

**Modalità di esecuzione**
AUTOMATICO — nessuna interfaccia utente esiste ancora per la posta elettronica in questa release: la policy è verificabile solo a livello di codice/test automatico.

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a una shell con la suite di test disponibile

**Dati di test**
Nessun dato reale necessario: il test automatico usa un database di test isolato con un messaggio email di esempio (`direction='inbound'`, `from_email='cliente@example.com'`, `status='received'`).

**Stato iniziale**
N/A (test automatico, ambiente di test isolato). Nessun messaggio email reale esiste in UAT: il sottosistema email non è ancora in funzione (nessun invio/ricezione reale, nessun Mailpit richiesto da questo pacchetto di test).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Eseguire `php -d memory_limit=1G vendor/bin/pest --filter="EmailMessagePolicyTest"` | — | I 2 test del file passano: un utente senza permessi `email.*` è negato su tutte le 5 abilità; un utente con `email.view` può solo vedere (`update`=false); un utente con `email.manage` può anche creare/modificare/eliminare |

**Risultato finale atteso**
`EmailMessagePolicy` è deny-by-default; `email.view` concede sola lettura, `email.manage` concede anche la scrittura — coerente con la natura potenzialmente sensibile (PII) del contenuto delle email.

**Controlli negativi**
Il passo 1 include il controllo negativo incrociato: un utente con solo `email.view` deve risultare negato su `update`/`create`/`delete`.

**Evidenze da acquisire**
- Output della suite Pest

**Criterio di superamento**

PASS: il test automatico passa.
FAIL: il test fallisce (es. `email.view` autorizza anche la scrittura).
BLOCKED: impossibile eseguire la suite di test.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: test automatico su database isolato, nessun effetto sull'ambiente UAT.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

## Schema dati — rendicontazione, fundraising, email e infrastruttura di importazione

### F0-36 — Un report di attività deve avere esattamente un proprietario (utente oppure organizzazione)

**Obiettivo**
Verificare che una riga di `activity_reports` non possa mai esistere senza un proprietario
valorizzato: il record deve appartenere o a un utente (`owner_user_id`) o a un'organizzazione
(`owner_organization_id`), mai a nessuno dei due. Questa regola (§5.2 del PRD) impedisce di generare
in futuro un report di rendicontazione "orfano", non attribuibile a nessuno. In questa release il
modulo Rendicontazione esiste solo come schema dati: non c'è ancora alcuna Filament Resource né
generazione PDF collegata (nessuna cartella `app/Filament/Resources/*Report*` nel repository).

**Riferimenti**
- Requisito/regola di dominio: PRD §5.2, tabella `activity_reports` — vincolo "esattamente un
  proprietario tra owner_user_id/owner_organization_id, coerente con owner_kind".
- Test automatico: `tests/Feature/Domain/Reporting/ActivityReportsTableTest.php` — `the owner check
  constraint rejects a row with neither owner set`
- File/componente applicativo rilevante: `database/migrations/2026_07_26_110000_create_activity_reports_table.php`
  (vincolo CHECK `activity_reports_owner_check`, reale `ALTER TABLE ... ADD CONSTRAINT` su Postgres,
  emulato con trigger `BEFORE INSERT`/`BEFORE UPDATE` su sqlite); `App\Domain\Reporting\Models\ActivityReport`.
- Test correlato: Nessuno (nello stesso file esistono anche i test "rejects a row with both owners
  set" e "rejects owner_kind inconsistent with the valorized owner", che verificano le due varianti
  simmetriche dello stesso vincolo — utili come controlli negativi aggiuntivi, vedi sotto).

**Modalità di esecuzione**
TECNICO DATABASE

**Priorità**
Alta

**Ruolo del tester**
Amministratore di sistema

**Prerequisiti**
- Accesso diretto al database Postgres dell'ambiente da collaudare (locale: `docker compose exec db
  psql -U <utente> <database>`; per una connessione diretta al Postgres dell'ambiente UAT:
  DA VERIFICARE CON IL PRODUCT OWNER, credenziali/hostname non documentati in questo repository).
- Migrazioni applicate (`activity_reports` esistente con il vincolo `activity_reports_owner_check`).
- Almeno un utente esistente in `users` di cui annotare l'`id` (es. l'utente Customer del seeder).

**Dati di test**
```sql
insert into activity_reports (owner_kind, period_type, year, month, locale, created_at, updated_at)
values ('user', 'monthly', 2026, 7, 'it', now(), now());
```
Nessun valore per `owner_user_id`/`owner_organization_id`: entrambi omessi (NULL).

**Stato iniziale**
Tabella `activity_reports` con lo schema previsto da §5.2, nessuna riga con proprietario nullo.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Connettersi al database Postgres dell'ambiente | `psql` sull'istanza del progetto | Prompt `psql` connesso al database applicativo |
| 2 | Eseguire l'INSERT dei dati di test riportato sopra, senza `owner_user_id` né `owner_organization_id` | Query SQL sopra | Il server rifiuta l'INSERT con un errore di violazione del vincolo `activity_reports_owner_check` (`ERROR: new row for relation "activity_reports" violates check constraint "activity_reports_owner_check"`) |
| 3 | Verificare che nessuna riga sia stata effettivamente inserita | `select count(*) from activity_reports where owner_kind = 'user' and owner_user_id is null and owner_organization_id is null;` | Il conteggio restituito è `0` |

**Risultato finale atteso**
Il database rifiuta categoricamente qualunque riga di `activity_reports` priva sia di
`owner_user_id` sia di `owner_organization_id`; nessuna riga di questo tipo è mai presente in tabella.

**Controlli negativi**
Ripetere l'INSERT valorizzando **entrambi** `owner_user_id` e `owner_organization_id` con id validi
contemporaneamente: deve essere respinto allo stesso modo (stesso vincolo, stesso messaggio di
errore) — coperto anche dal test automatico correlato "rejects a row with both owners set" nello
stesso file.

**Evidenze da acquisire**
- Output testuale completo della sessione `psql` (comando eseguito + messaggio di errore restituito).
- Output della query di verifica del conteggio a 0.

**Criterio di superamento**

PASS: l'INSERT senza proprietario viene rifiutato dal database con un errore di violazione del
vincolo CHECK, e nessuna riga orfana risulta presente in tabella.
FAIL: l'INSERT viene accettato (la riga orfana viene creata) oppure il messaggio di errore non è
riconducibile al vincolo `activity_reports_owner_check`.
BLOCKED: impossibile connettersi al database dell'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuna riga viene mai persistita (l'INSERT fallisce): non è necessario alcun ripristino. Se il test
è stato eseguito sul database UAT, in ogni caso il prossimo deploy esegue `migrate:fresh --seed` e
ricrea l'intero dataset da zero.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-37 — Le opportunità di fundraising rispettano i default e le relazioni richieste

**Obiettivo**
Verificare che `fundraising_opportunities` esponga tutte le colonne richieste da §5.2 del PRD, che
`territorial_scope` assuma di default il valore `national` quando non specificato, e che le
relazioni obbligatorie verso `users` (`created_by`, `responsible_user_id`) e quella opzionale
(`evaluated_by`, che si azzera se l'utente valutatore viene eliminato) siano realmente vincolate a
livello di database. Anche questo modulo esiste in questa release solo come schema dati: nessuna
Filament Resource per le opportunità di fundraising è presente nel repository.

**Riferimenti**
- Requisito/regola di dominio: PRD §5.2, tabella `fundraising_opportunities`.
- Test automatico: `tests/Feature/Domain/Fundraising/FundraisingOpportunitiesTableTest.php` —
  `fundraising_opportunities table has the columns required by §5.2` (nello stesso file: `defaults
  territorial_scope to national`, `belongs to a creator and a responsible user`, `evaluated_by is
  nullable and set null on user delete`).
- File/componente applicativo rilevante: `database/migrations/2026_07_26_120000_create_fundraising_opportunities_table.php`;
  `App\Domain\Fundraising\Models\FundraisingOpportunity`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
TECNICO DATABASE

**Priorità**
Media

**Ruolo del tester**
Amministratore di sistema

**Prerequisiti**
- Accesso diretto al database Postgres dell'ambiente da collaudare (vedi nota di connessione in F0-36).
- Migrazioni applicate (`fundraising_opportunities` esistente).
- Due utenti esistenti in `users` di cui annotare gli `id` (es. l'utente Fundraising del seeder come
  creatore/responsabile, un secondo utente come valutatore da eliminare al passo 4).

**Dati di test**
```sql
insert into fundraising_opportunities (name, deadline, created_by, responsible_user_id, evaluated_by, evaluated_at, created_at, updated_at)
values ('Bando di collaudo F0-37', '2026-12-31', <id_creatore>, <id_creatore>, <id_valutatore>, now(), now(), now());
```
(nessun valore esplicito per `territorial_scope`, per verificarne il default).

**Stato iniziale**
Tabella `fundraising_opportunities` vuota o con solo i dati del seeder; nessuna riga con
`name = 'Bando di collaudo F0-37'`.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Verificare l'elenco colonne della tabella | `\d fundraising_opportunities` in `psql` | Sono presenti tutte le colonne di §5.2: `id, name, official_url, endowment_fund, deadline, program_name, sponsor, cofinancing_quota, max_contribution, territorial_scope, beneficiary_requirements, lead_requirements, created_by, responsible_user_id, evaluated_by, evaluated_at, evaluation_positive_total, evaluation_negative_total, evaluation_total, created_at, updated_at` |
| 2 | Eseguire l'INSERT dei dati di test riportato sopra | Query SQL sopra | La riga viene creata con successo |
| 3 | Leggere il valore di `territorial_scope` della riga appena creata | `select territorial_scope from fundraising_opportunities where name = 'Bando di collaudo F0-37';` | Il valore restituito è `national` |
| 4 | Eliminare (hard delete) l'utente valutatore usato al passo 2 | `delete from users where id = <id_valutatore>;` | La cancellazione riesce senza errore di violazione FK |
| 5 | Rileggere la riga dell'opportunità | `select evaluated_by from fundraising_opportunities where name = 'Bando di collaudo F0-37';` | Il valore di `evaluated_by` è ora `NULL` |

**Risultato finale atteso**
La tabella espone tutte le colonne di §5.2; una riga creata senza specificare `territorial_scope`
riceve il default `national`; la cancellazione dell'utente valutatore azzera `evaluated_by` senza
impedire la cancellazione dell'utente (nessun blocco FK), coerentemente con `nullOnDelete()`.

**Controlli negativi**
Tentare l'INSERT omettendo `created_by` (colonna obbligatoria, `foreignId('created_by')->constrained('users')`
senza `nullable()`): il database deve rifiutare l'operazione (violazione `NOT NULL`).

**Evidenze da acquisire**
- Output di `\d fundraising_opportunities`.
- Output delle query di verifica del default e della `evaluated_by` dopo la cancellazione.

**Criterio di superamento**

PASS: tutte le colonne sono presenti, il default `territorial_scope = national` viene applicato, e
`evaluated_by` si azzera correttamente alla cancellazione dell'utente valutatore.
FAIL: manca una colonna richiesta, oppure il default non viene applicato, oppure la cancellazione
dell'utente valutatore fallisce o non azzera `evaluated_by`.
BLOCKED: impossibile connettersi al database dell'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Eliminare la riga di prova: `delete from fundraising_opportunities where name = 'Bando di collaudo F0-37';`.
Se eseguito sul database UAT, il prossimo deploy esegue comunque `migrate:fresh --seed` e ricrea
l'intero dataset da zero.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-38 — I messaggi email hanno un identificativo pubblico univoco (ULID) distinto dalla chiave primaria

**Obiettivo**
Verificare che ogni riga di `email_messages` riceva automaticamente, alla creazione, un
identificativo pubblico `ulid` univoco (formato ULID), mentre la chiave primaria resta `id`
(intero autoincrementante, usato per FK/ordinamento interno). A differenza dei test di puro schema
di questo blocco, questa generazione avviene nel livello applicativo Eloquent (trait `HasUlids` con
`uniqueIds()` sovrascritto), non con un default a livello di colonna SQL: non è quindi verificabile
con un semplice INSERT SQL, serve il livello applicativo PHP.

**Riferimenti**
- Requisito/regola di dominio: PRD §5.2, tabella `email_messages` — colonna `ulid` come
  identificativo pubblico, `id` come chiave primaria tecnica.
- Test automatico: `tests/Feature/Domain/Mail/EmailMessagesTableTest.php` — `a ulid is generated
  automatically on creation, id stays the auto-increment primary key`
- File/componente applicativo rilevante: `app/Domain/Mail/Models/EmailMessage.php` (trait
  `Illuminate\Database\Eloquent\Concerns\HasUlids`, metodo `uniqueIds(): array` che restituisce
  `['ulid']`); `database/migrations/2026_07_26_130100_create_email_messages_table.php`
  (`$table->ulid('ulid')->unique()`).
- Test correlato: Nessuno.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate (`composer install`) e suite Pest funzionante.
- Nessun dato applicativo richiesto: il test costruisce da sé la riga di prova.

**Dati di test**
Nessuno oltre a quelli generati dalla funzione di supporto del test (`makeEmailMessage()`:
`direction = inbound`, `from_email = mittente@example.com`, `status = received`).

**Stato iniziale**
Non applicabile: il test gira su un database di test isolato (SQLite in memoria, `RefreshDatabase`),
non sull'ambiente UAT.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto (dove risiede `artisan`) |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a ulid is generated automatically on creation, id stays the auto-increment primary key"` (o l'intero file `tests/Feature/Domain/Mail/EmailMessagesTableTest.php`) | Il comando termina con exit code 0 e il test riportato risulta verde/passed |
| 3 | Leggere l'output del test | Output del comando | Nessun fallimento riportato per il test in oggetto |

**Risultato finale atteso**
Il test Pest referenziato passa: ogni `EmailMessage` creato riceve un `ulid` non vuoto in formato
ULID valido, la chiave primaria del modello resta `id` (intero), coerentemente con quanto verificato
dalle asserzioni del test.

**Controlli negativi**
Nessuno applicabile: la generazione del ULID non ha un percorso alternativo da testare in negativo
(non è possibile, tramite l'interfaccia Eloquent, creare una riga senza che il ULID venga generato).

**Evidenze da acquisire**
- Output completo del comando Pest eseguito (incluso exit code).

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il comando termina con un test fallito o con errore.
BLOCKED: l'ambiente locale/CI non è disponibile o le dipendenze non sono installabili.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: `RefreshDatabase` ricostruisce lo schema di test ad ogni esecuzione, nessun dato persiste
oltre la singola esecuzione del test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-39 — Le preferenze di notifica sono uniche per (utente, tipo di notifica, canale)

**Obiettivo**
Verificare che non possano coesistere due righe di `notification_preferences` per la stessa
combinazione di utente, tipo di notifica e canale: altrimenti lo stesso evento potrebbe generare
comportamenti di notifica ambigui/contraddittori per lo stesso utente. Anche questo modulo esiste in
questa release solo come schema dati: nessuna interfaccia utente per gestire le preferenze di
notifica è presente nel repository.

**Riferimenti**
- Requisito/regola di dominio: PRD §5.2, tabella `notification_preferences` — vincolo unique
  `(user_id, notification_type, channel)`.
- Test automatico: `tests/Feature/Domain/Mail/NotificationPreferencesTableTest.php` — `unique on the
  user/notification_type/channel triple`
- File/componente applicativo rilevante: `database/migrations/2026_07_26_130400_create_notification_preferences_table.php`
  (`$table->unique(['user_id', 'notification_type', 'channel'])`); `App\Domain\Mail\Models\NotificationPreference`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
TECNICO DATABASE

**Priorità**
Alta

**Ruolo del tester**
Amministratore di sistema

**Prerequisiti**
- Accesso diretto al database Postgres dell'ambiente da collaudare (vedi nota di connessione in F0-36).
- Un utente esistente in `users` di cui annotare l'`id`.

**Dati di test**
```sql
insert into notification_preferences (user_id, notification_type, channel, enabled, created_at, updated_at)
values (<id_utente>, 'ticket.assigned', 'mail', true, now(), now());
```
Ripetuto identico una seconda volta.

**Stato iniziale**
Nessuna riga di `notification_preferences` per l'utente scelto con `notification_type =
'ticket.assigned'` e `channel = 'mail'`.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Eseguire il primo INSERT dei dati di test | Query SQL sopra | La riga viene creata con successo |
| 2 | Ripetere esattamente lo stesso INSERT (stesso `user_id`, `notification_type`, `channel`) | Query SQL identica | Il server rifiuta l'INSERT con un errore di violazione del vincolo unique composito |
| 3 | Verificare che esista una sola riga per quella combinazione | `select count(*) from notification_preferences where user_id = <id_utente> and notification_type = 'ticket.assigned' and channel = 'mail';` | Il conteggio restituito è `1` |

**Risultato finale atteso**
Il database impedisce l'esistenza di più di una riga per la stessa combinazione (utente, tipo di
notifica, canale).

**Controlli negativi**
Inserire una riga con lo stesso `user_id`/`notification_type` ma `channel` diverso (es. `push`):
deve essere accettata senza violare il vincolo (la combinazione è diversa).

**Evidenze da acquisire**
- Output testuale della sessione `psql` con il messaggio di errore del secondo INSERT.
- Output della query di conteggio.

**Criterio di superamento**

PASS: il secondo INSERT identico viene rifiutato e in tabella resta una sola riga per la
combinazione testata.
FAIL: il secondo INSERT viene accettato (due righe identiche coesistono).
BLOCKED: impossibile connettersi al database dell'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Eliminare la riga di prova: `delete from notification_preferences where user_id = <id_utente> and
notification_type = 'ticket.assigned' and channel = 'mail';`. Se eseguito sul database UAT, il
prossimo deploy esegue comunque `migrate:fresh --seed` e ricrea l'intero dataset da zero.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-40 — Le tabelle di infrastruttura per l'importazione rispettano lo schema richiesto

**Obiettivo**
Verificare che `import_runs` (la tabella che traccerà le esecuzioni dell'importazione dati dal
sistema v1, Fase 2, non ancora iniziata) esponga tutte le colonne richieste da §5.2, e che i default
a livello di database (`status = 'running'`, `is_dry_run = false`) siano applicati correttamente
quando non specificati. Nessuna interfaccia utente esiste per questa tabella in questa release: è
infrastruttura predisposta per una fase futura.

**Riferimenti**
- Requisito/regola di dominio: PRD §5.2, tabella `import_runs`.
- Test automatico: `tests/Feature/Import/ImportRunsTableTest.php` — `import_runs table has the
  columns required by §5.2` (nello stesso file: `casts stages to array, status to enum and
  is_dry_run to boolean`, `status defaults to running when not specified`).
- File/componente applicativo rilevante: `database/migrations/2026_07_26_140000_create_import_runs_table.php`;
  `App\Import\Models\ImportRun`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
TECNICO DATABASE

**Priorità**
Bassa

**Ruolo del tester**
Amministratore di sistema

**Prerequisiti**
- Accesso diretto al database Postgres dell'ambiente da collaudare (vedi nota di connessione in F0-36).

**Dati di test**
```sql
insert into import_runs (started_at, dump_label, created_at, updated_at)
values (now(), 'dump-collaudo-F0-40', now(), now());
```
(nessun valore esplicito per `status`/`is_dry_run`, per verificarne il default).

**Stato iniziale**
Nessuna riga di `import_runs` con `dump_label = 'dump-collaudo-F0-40'`.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Verificare l'elenco colonne della tabella | `\d import_runs` in `psql` | Sono presenti tutte le colonne di §5.2: `id, started_at, finished_at, dump_label, stages, status, is_dry_run, notes, created_at, updated_at` |
| 2 | Eseguire l'INSERT dei dati di test riportato sopra | Query SQL sopra | La riga viene creata con successo |
| 3 | Leggere i valori di `status`/`is_dry_run`/`finished_at` della riga appena creata | `select status, is_dry_run, finished_at from import_runs where dump_label = 'dump-collaudo-F0-40';` | `status = 'running'`, `is_dry_run = false`, `finished_at` = `NULL` |

**Risultato finale atteso**
La tabella espone tutte le colonne di §5.2; una riga creata senza specificare `status`/`is_dry_run`
riceve rispettivamente i default `running` e `false`, e `finished_at` resta nullo fino a valorizzazione esplicita.

**Controlli negativi**
Nessuno applicabile: non esiste in questa fase alcuna regola di unicità/vincolo aggiuntivo su questa
tabella da verificare in negativo.

**Evidenze da acquisire**
- Output di `\d import_runs`.
- Output della query di verifica dei default.

**Criterio di superamento**

PASS: tutte le colonne sono presenti e i default (`status = running`, `is_dry_run = false`) vengono
applicati correttamente.
FAIL: manca una colonna richiesta oppure un default non viene applicato come atteso.
BLOCKED: impossibile connettersi al database dell'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Eliminare la riga di prova: `delete from import_runs where dump_label = 'dump-collaudo-F0-40';`. Se
eseguito sul database UAT, il prossimo deploy esegue comunque `migrate:fresh --seed` e ricrea
l'intero dataset da zero.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

## Diagnostica e configurazione ambiente

### F0-41 — Il comando diagnostico segnala l'esito di ogni controllo con codice di uscita coerente

**Obiettivo**
Verificare che `php artisan orchestrator:doctor` esegua in sequenza i quattro controlli previsti in
questa fase (variabili ambiente, scrittura storage, utente di sistema, feature flag), stampi un
esito leggibile riga per riga (`[OK]`/`[FAIL]`), termini con codice di uscita `0` quando tutti i
controlli passano (o `1` se anche uno solo fallisce), e crei l'utente di sistema come effetto
collaterale se non esiste ancora.

**Riferimenti**
- Requisito/regola di dominio: PRD §12 (comando diagnostico), US-022.
- Test automatico: `tests/Feature/Console/OrchestratorDoctorCommandTest.php` — `it exits
  successfully and reports every check when the environment is valid`
- File/componente applicativo rilevante: `app/Console/Commands/OrchestratorDoctorCommand.php`
  (orchestratore che itera `EnvironmentVariablesCheck`, `StorageWritableCheck`, `SystemUserCheck`,
  `FeatureFlagsCheck`); `config/orchestrator.php`.
- Test correlato: F0-42, F0-43, F0-44, F0-45 (ciascuno verifica in isolamento uno dei quattro
  controlli che questo comando orchestra).

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Critica

**Ruolo del tester**
Amministratore di sistema

**Prerequisiti**
- Accesso a un terminale sull'ambiente da collaudare (locale: `docker compose exec app ...`; oppure
  ambiente PHP/Docker locale equivalente configurato secondo `CLAUDE.md`; per l'ambiente UAT reale
  l'accesso a terminale/container: DA VERIFICARE CON IL PRODUCT OWNER).
- File `.env` dell'ambiente valorizzato con tutte le variabili elencate in
  `config('orchestrator.required_env')` (`APP_KEY`, `APP_ENV`, `APP_URL`, `DB_CONNECTION`,
  `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `REDIS_HOST`, `REDIS_PORT`,
  `FILESYSTEM_DISK`, `QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER`, `MAIL_MAILER`,
  `MAIL_HOST`, `MAIL_PORT`, `MAIL_FROM_ADDRESS`).

**Dati di test**
Nessun dato applicativo da inserire: il comando legge solo configurazione e stato del filesystem/DB.

**Stato iniziale**
Ambiente con configurazione valida; utente di sistema (email da `config('orchestrator.system_user.email')`,
default `system@orchestrator.local` — valore reale in UAT: DA VERIFICARE CON IL PRODUCT OWNER se
sovrascritto da `ORCHESTRATOR_SYSTEM_USER_EMAIL`) eventualmente già presente da un'esecuzione precedente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del progetto (o nel container `app`) | `docker compose exec app bash` (oppure equivalente locale) | Prompt di shell nella directory contenente `artisan` |
| 2 | Eseguire il comando diagnostico | `php artisan orchestrator:doctor` | Il comando produce un elenco di righe `[OK] ...`/`[FAIL] ...`, una per controllo |
| 3 | Verificare la presenza delle righe chiave nell'output | Output del comando | Sono presenti almeno le righe `[OK] Variabile env APP_KEY`, `[OK] Scrittura su storage/app`, `[OK] Utente di sistema`, e la riga finale `Tutti i controlli sono passati.` |
| 4 | Verificare il codice di uscita del processo | `echo $?` subito dopo il comando | Il valore restituito è `0` |
| 5 | Verificare l'effetto collaterale sul database | Query/tinker: `App\Domain\Identity\Models\User::where('email', config('orchestrator.system_user.email'))->exists()` | Restituisce `true` (l'utente di sistema esiste, creato se assente) |

**Risultato finale atteso**
Il comando termina con exit code `0`, riporta un esito `[OK]` per ciascun controllo attivo e la
riga di riepilogo positiva; l'utente di sistema esiste nel database al termine dell'esecuzione.

**Controlli negativi**
Rimuovere temporaneamente (o svuotare) una variabile obbligatoria (es. `APP_KEY`) dalla
configurazione effettiva e rieseguire il comando: deve comparire la riga `[FAIL] Variabile env
APP_KEY (mancante o vuota)`, la riga finale deve essere `Uno o più controlli sono falliti.` e il
codice di uscita deve essere `1` — ripristinare subito dopo il valore originale.

**Evidenze da acquisire**
- Output completo del comando (copiato integralmente, non parafrasato).
- Valore del codice di uscita (`echo $?`).

**Criterio di superamento**

PASS: l'output contiene tutte le righe attese, il codice di uscita è `0`, l'utente di sistema esiste
nel database dopo l'esecuzione.
FAIL: manca una riga attesa, oppure il codice di uscita non è `0` con ambiente valido, oppure
l'utente di sistema non viene creato/trovato.
BLOCKED: nessun accesso a terminale sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il comando è idempotente (una riesecuzione successiva trova l'utente di sistema già
presente e riporta "già presente"), non serve alcun ripristino di configurazione se il controllo
negativo è stato ripristinato correttamente al passo dei controlli negativi.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-42 — Il controllo delle variabili ambiente obbligatorie segnala ogni variabile mancante o vuota

**Obiettivo**
Verificare in isolamento la logica di `EnvironmentVariablesCheck`: per ogni variabile elencata in
`config('orchestrator.required_env')`, il controllo deve risultare fallito (`passed = false`, detail
`"mancante o vuota"`) quando il valore è `null` oppure stringa vuota, e superato quando il valore è
presente e non vuoto. Il test automatico referenziato istanzia la classe direttamente con una
configurazione di prova (non tramite il comando artisan), a differenza di F0-41 che ne verifica
l'effetto end-to-end tramite `orchestrator:doctor`.

**Riferimenti**
- Requisito/regola di dominio: PRD §12/§13.3 (lettura da `config()`, mai `env()` fuori da `config/`).
- Test automatico: `tests/Unit/Support/Doctor/EnvironmentVariablesCheckTest.php` — `a missing or
  empty variable fails`
- File/componente applicativo rilevante: `app/Support/Doctor/Checks/EnvironmentVariablesCheck.php`;
  `config/orchestrator.php` (chiave `required_env`).
- Test correlato: F0-41 (stesso controllo, verificato end-to-end tramite il comando).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Configurazione di prova impostata dal test stesso: `APP_KEY => null`, `DB_HOST => ''` (stringa
vuota), `DB_PORT => '5432'` (valore presente, atteso passare).

**Stato iniziale**
Non applicabile: il test non tocca il filesystem né il database, opera solo su un array di
configurazione in memoria per la durata del test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a missing or empty variable fails"` | Il comando termina con exit code 0 e il test risulta passed |

**Risultato finale atteso**
Il test Pest referenziato passa: una variabile `null` e una vuota risultano entrambe fallite con
detail `"mancante o vuota"`, una variabile valorizzata risulta superata.

**Controlli negativi**
Nessuno applicabile oltre a quanto già coperto dal test stesso (verifica sia il caso fallito sia,
nell'altro test dello stesso file, il caso superato).

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-43 — Il controllo di scrittura delle directory storage rilevanti passa su un ambiente pulito

**Obiettivo**
Verificare che le directory storage elencate da `StorageWritableCheck`
(`storage/app`, `storage/app/private`, `storage/app/public`, `storage/framework/cache`,
`storage/framework/sessions`, `storage/framework/views`, `storage/logs`) esistano e siano
effettivamente scrivibili dal processo applicativo su un'installazione appena predisposta: se una di
queste directory non fosse scrivibile, funzionalità critiche (log, sessioni, cache, allegati)
fallirebbero silenziosamente o con errori poco chiari a runtime.

**Riferimenti**
- Requisito/regola di dominio: PRD §12 (comando diagnostico, controllo storage), US-022.
- Test automatico: `tests/Unit/Support/Doctor/StorageWritableCheckTest.php` — `the relevant storage
  directories of a fresh install are writable`
- File/componente applicativo rilevante: `app/Support/Doctor/Checks/StorageWritableCheck.php`
  (costante `DIRECTORIES` con l'elenco esatto delle 7 sottocartelle verificate).
- Test correlato: F0-41 (le stesse righe compaiono nell'output di `orchestrator:doctor`).

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Alta

**Ruolo del tester**
Amministratore di sistema

**Prerequisiti**
- Accesso a un terminale sull'ambiente da collaudare (container `app` o equivalente locale).

**Dati di test**
Nessuno: il controllo verifica solo permessi del filesystem esistente.

**Stato iniziale**
Le 7 directory elencate esistono già (create dallo scaffold Laravel/dal Dockerfile applicativo).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del progetto (o nel container `app`) | shell nel container `app` | Prompt nella directory contenente `storage/` |
| 2 | Verificare esistenza e permessi di ciascuna delle 7 directory | `ls -ld storage/app storage/app/private storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs` | Tutte le 7 directory esistono e mostrano permesso di scrittura per l'utente/processo applicativo (bit `w` nel proprietario o gruppo del processo PHP-FPM) |
| 3 | (Alternativa/conferma) eseguire il comando diagnostico completo | `php artisan orchestrator:doctor` | Ciascuna delle 7 righe `[OK] Scrittura su storage/<percorso>` compare con detail `scrivibile` |

**Risultato finale atteso**
Tutte le 7 directory rilevanti esistono e sono scrivibili dal processo applicativo, senza alcuna
riga `[FAIL]`/`non scrivibile`/`directory inesistente` nell'output del comando diagnostico.

**Controlli negativi**
Rendere temporaneamente non scrivibile una delle directory (es. `chmod 555 storage/logs`) e
rieseguire `php artisan orchestrator:doctor`: deve comparire la riga `[FAIL] Scrittura su
storage/logs (non scrivibile)` — ripristinare subito dopo i permessi originali (es. `chmod 775
storage/logs`, coerente con la configurazione standard del progetto).

**Evidenze da acquisire**
- Output di `ls -ld` per le 7 directory.
- Output delle righe pertinenti di `php artisan orchestrator:doctor`.

**Criterio di superamento**

PASS: tutte le 7 directory risultano esistenti e scrivibili.
FAIL: almeno una directory non esiste o non è scrivibile.
BLOCKED: nessun accesso a terminale sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno in condizioni normali (nessuna modifica ai permessi). Se eseguito il controllo negativo,
assicurarsi di aver ripristinato i permessi originali della directory modificata.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-44 — L'utente di sistema viene creato se assente e non consente mai l'accesso al pannello

**Obiettivo**
Verificare che l'utente di sistema (fallback per l'autore di log/eventi generati dal sistema, non da
un utente reale) venga creato automaticamente se assente, senza password e senza alcun ruolo
assegnato — e che di conseguenza non possa né autenticarsi (nessuna password impostata) né accedere
al pannello Filament (`canAccessPanel()` richiede almeno uno dei 5 ruoli applicativi, che l'utente
di sistema non ha mai).

**Riferimenti**
- Requisito/regola di dominio: PRD §12 (utente di sistema), US-022; PRD §9.1/US-020 (gate di accesso
  al pannello, `canAccessPanel()`).
- Test automatico: `tests/Unit/Support/Doctor/SystemUserCheckTest.php` — `it creates the system user
  when it does not exist yet`
- File/componente applicativo rilevante: `app/Support/Doctor/Checks/SystemUserCheck.php`;
  `App\Domain\Identity\Models\User::system()`; `config/orchestrator.php` (chiave `system_user`).
- Test correlato: F0-41 (stesso controllo, verificato end-to-end tramite il comando).

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Media

**Ruolo del tester**
Amministratore di sistema

**Prerequisiti**
- Accesso a un terminale sull'ambiente da collaudare con `php artisan tinker` disponibile.
- Nessun utente con l'email di sistema configurata (`config('orchestrator.system_user.email')`,
  default `system@orchestrator.local`) presente nel database, oppure disponibilità a osservarne lo
  stato se già presente da un'esecuzione precedente del comando diagnostico.

**Dati di test**
Nessun dato da inserire manualmente: l'utente viene creato dal controllo stesso.

**Stato iniziale**
Utente di sistema eventualmente assente (prima esecuzione) o già presente (esecuzioni successive,
comportamento comunque idempotente).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Eseguire il comando diagnostico (crea l'utente di sistema se assente) | `php artisan orchestrator:doctor` | Riga `[OK] Utente di sistema (creato (...))` alla prima esecuzione, `(già presente (...))` alle successive |
| 2 | Aprire una shell interattiva PHP | `php artisan tinker` | Prompt Tinker aperto |
| 3 | Recuperare l'utente di sistema e verificarne la password | `App\Domain\Identity\Models\User::where('email', config('orchestrator.system_user.email'))->sole()->password` | Il valore restituito è `null` |
| 4 | Verificare l'assenza di ruoli | `App\Domain\Identity\Models\User::where('email', config('orchestrator.system_user.email'))->sole()->roles->count()` | Il valore restituito è `0` |
| 5 | Verificare l'esito di `canAccessPanel()` | `App\Domain\Identity\Models\User::where('email', config('orchestrator.system_user.email'))->sole()->canAccessPanel(new Filament\Panel)` | Il valore restituito è `false` |

**Risultato finale atteso**
L'utente di sistema esiste, ha `password = null`, nessun ruolo assegnato, e `canAccessPanel()`
restituisce `false`: non può in alcun modo autenticarsi né accedere al pannello.

**Controlli negativi**
Tentare (in Tinker) di assegnare un ruolo all'utente di sistema e rieseguire `canAccessPanel()`: se
un domani questo comportamento cambiasse inavvertitamente, andrebbe segnalato — in questa release ci
si aspetta che nessun punto del codice assegni mai un ruolo all'utente di sistema, quindi questo
passo è solo un controllo esplorativo, non un'aspettativa del PRD.

**Evidenze da acquisire**
- Output del comando diagnostico.
- Output delle 3 query Tinker (password, conteggio ruoli, `canAccessPanel()`).

**Criterio di superamento**

PASS: l'utente di sistema esiste, ha `password` nulla, zero ruoli, e `canAccessPanel()` restituisce
`false`.
FAIL: una qualunque delle verifiche precedenti non corrisponde (password valorizzata, ruoli
presenti, o accesso al pannello consentito).
BLOCKED: nessun accesso a terminale/Tinker sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: l'utente di sistema è un dato applicativo previsto e permanente, non va eliminato.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-45 — Le feature flag delle automazioni schedulate sono tutte disattivate di default

**Obiettivo**
Verificare che tutti i 12 feature flag delle automazioni schedulate (nessuna delle quali è ancora
implementata in questa release: sono punti di innesto per fasi future) risultino disattivati
(`false`) quando le relative variabili d'ambiente `ENABLE_*` non sono impostate, coerentemente con
la scelta di progetto "l'abilitazione è una scelta di deploy esplicita, mai un default attivo".

**Riferimenti**
- Requisito/regola di dominio: PRD §10.1/§10.2 (catalogo feature flag delle automazioni schedulate).
- Test automatico: `tests/Unit/OrchestratorConfigTest.php` — `every scheduled automation feature
  flag defaults to false`
- File/componente applicativo rilevante: `config/orchestrator.php` (chiave `features`);
  `app/Support/Doctor/Checks/FeatureFlagsCheck.php` (le riporta nell'output diagnostico, sempre come
  "passed": un flag disattivo non è un errore di configurazione).
- Test correlato: F0-41 (le stesse righe compaiono nell'output di `orchestrator:doctor`).

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Bassa

**Ruolo del tester**
Amministratore di sistema

**Prerequisiti**
- Accesso a un terminale sull'ambiente da collaudare.
- Nessuna delle 12 variabili `ENABLE_TICKETS_PROGRESS_TO_TODO`, `ENABLE_TICKETS_AUTO_CLOSE_RELEASED`,
  `ENABLE_TICKETS_CLOSE_SCRUM`, `ENABLE_TICKETS_RESTORE_WAITING`, `ENABLE_TICKETS_WAITING_REMINDERS`,
  `ENABLE_TICKETS_ARCHIVE_SCRUM`, `ENABLE_MAIL_FETCH_INBOUND`, `ENABLE_MAIL_RETRY_FAILED`,
  `ENABLE_TIMETRACKING_AGGREGATE`, `ENABLE_REPORTS_MONTHLY`, `ENABLE_MAIL_DIGEST`,
  `ENABLE_TICKETS_IDLE_DEVELOPER_NOTICE` impostata a un valore "vero" nel `.env` dell'ambiente
  (valore atteso in UAT: DA VERIFICARE CON IL PRODUCT OWNER, non documentato in questo repository
  se il `.env` di UAT le imposta esplicitamente a `false` o le omette).

**Dati di test**
Nessuno: il test legge solo configurazione.

**Stato iniziale**
Ambiente con le 12 variabili `ENABLE_*` assenti o impostate a `false`.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Eseguire il comando diagnostico | `php artisan orchestrator:doctor` | Compaiono 12 righe `[OK] Feature flag <nome> (...)` |
| 2 | Leggere il detail di ciascuna delle 12 righe | Output del comando | Ogni riga riporta il detail `disattivo` |

**Risultato finale atteso**
Tutti i 12 feature flag risultano `disattivo` nell'output del comando diagnostico, nessuno riporta `attivo`.

**Controlli negativi**
Impostare temporaneamente `ENABLE_MAIL_DIGEST=true` nel `.env`, rieseguire il comando: la riga
corrispondente deve mostrare `attivo` e il comando deve comunque terminare con exit code `0` (un
flag attivo non è mai un errore) — ripristinare subito dopo il valore originale.

**Evidenze da acquisire**
- Output completo delle 12 righe "Feature flag" del comando diagnostico.

**Criterio di superamento**

PASS: tutte le 12 righe riportano il detail `disattivo`.
FAIL: almeno una riga riporta `attivo` senza che sia stata impostata esplicitamente la relativa
variabile d'ambiente.
BLOCKED: nessun accesso a terminale sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Se eseguito il controllo negativo, rimuovere/ripristinare `ENABLE_MAIL_DIGEST` nel `.env`
dell'ambiente allo stato originale.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

## Design system e tema del pannello

### F0-46 — Il tema del pannello deriva dai token del design system, non da valori scritti a mano

**Obiettivo**
Verificare che il colore di brand e il font primario mostrati nel pannello Filament corrispondano
esattamente ai token dichiarati in `resources/css/theme.css` (fonte di verità unica del design
system, §US-004/US-005), letti a runtime da `App\Support\DesignTokens` — e non un valore hex/font
riscritto a mano altrove nel codice del pannello.

**Riferimenti**
- Requisito/regola di dominio: `docs/design-system.md`; nota `CLAUDE.md` "Design system
  (US-004/US-005)" — `AdminPanelProvider` usa **solo** `DesignTokens` per `->colors()`/`->font()`.
- Test automatico: `tests/Unit/DesignTokensTest.php` — `reads the brand color token from
  resources/css/theme.css`
- File/componente applicativo rilevante: `app/Support/DesignTokens.php`; `resources/css/theme.css`
  (token `--ms-brand: #17a180;`, `--ms-font-sans: 'Nunito Sans', sans-serif;`);
  `app/Filament/Providers/AdminPanelProvider.php`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
MISTO

**Priorità**
Media

**Ruolo del tester**
Admin

**Prerequisiti**
- Ambiente UAT raggiungibile e credenziali Admin (punto 9 delle istruzioni generali:
  `info@montagnaservizi.com` / `uat`).
- Per la parte automatica: ambiente locale/CI con suite Pest funzionante.
- Browser con strumenti di sviluppo (ispezione elemento/computed style) per la verifica visiva.

**Dati di test**
Nessuno: verifica solo di colore/font renderizzati, nessun dato applicativo creato.

**Stato iniziale**
Asset compilati (`npm run build`) aggiornati e disponibili (`public/build/manifest.json` presente),
altrimenti la pagina non si carica affatto (`ViteManifestNotFoundException`).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | (Parte automatica) Eseguire il test unitario sui token | `vendor/bin/pest --filter "reads the brand color token from resources/css/theme.css"` | Il comando termina con exit code 0, test passed |
| 2 | Aprire il browser sull'URL di login del pannello UAT | `https://ticket-uat.montagnaservizi.com/admin/login` | La pagina di login si carica correttamente |
| 3 | Accedere con le credenziali Admin | `info@montagnaservizi.com` / `uat` | Login riuscito, dashboard visibile |
| 4 | Ispezionare visivamente (o via strumenti sviluppatore) il colore degli elementi di brand (es. bottone primario, elementi attivi della sidebar) | Ispezione elemento del browser | Il colore corrisponde a `#17a180` (verde/teal) |
| 5 | Ispezionare il font utilizzato dal testo del pannello | Ispezione elemento del browser (computed style, `font-family`) | Il font applicato è `Nunito Sans` (o la sua stack di fallback dichiarata) |

**Risultato finale atteso**
Il test automatico sui token conferma che `resources/css/theme.css` dichiara `--ms-brand: #17a180`
e `--ms-font-sans` con primo font `Nunito Sans`; il pannello effettivamente visualizzato usa quello
stesso colore e font, senza discrepanze.

**Controlli negativi**
Nessuno applicabile: non esiste un percorso alternativo di tema da testare in negativo in questa release.

**Evidenze da acquisire**
- Output del test Pest.
- Screenshot del pannello con il colore di brand visibile.
- Screenshot del pannello di ispezione browser con il valore `font-family` calcolato.

**Criterio di superamento**

PASS: il test automatico passa e il colore/font osservati visivamente nel pannello corrispondono ai
token dichiarati in `theme.css`.
FAIL: il test automatico fallisce, oppure il colore/font effettivamente mostrato nel pannello non
corrisponde ai token dichiarati.
BLOCKED: ambiente UAT non raggiungibile, oppure credenziali Admin non funzionanti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessun dato applicativo viene creato o modificato.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

## Popolamento dati locale (ETL)

### F0-47 — `v1:import --anonymize` popola un ambiente locale completo con dati reali anonimizzati

**Obiettivo**
Verificare che l'ambiente locale non sia più popolato da un seeder di dati fittizi
(`DevelopmentSeeder`, rimosso) ma dall'ETL reale (`v1:import --anonymize`, incorporato in `make
setup`): a partire dal dump v1 più recente disponibile (`v1dumps/latest.sql`), l'import popola
utenti, organizzazioni, ticket, tag, pagine di documentazione, report di attività e opportunità/
progetti di fundraising con dati reali anonimizzati, e le 5 identità di riferimento del collaudo
(punto 9 di `00-istruzioni-generali.md`) sono presenti con le email fisse note.

**Riferimenti**
- Requisito/regola di dominio: design `docs/superpowers/specs/2026-08-02-etl-real-data-seeding-design.md`
  (US-R02); PRD §11 (M11 — ETL).
- Test automatico: `tests/Feature/Console/V1ImportPipelineIdempotencyTest.php` — `a second
  consecutive v1:import run creates/updates nothing on every registered stage` (verifica la stessa
  pipeline su una fixture di test; il popolamento completo alla prima esecuzione è precondizione
  implicita dell'asserzione di idempotenza sulla seconda).
- File/componente applicativo rilevante: `app/Console/Commands/V1ImportCommand.php`; `Makefile`
  (target `setup`); `app/Import/Anonymization/Anonymizer.php` (identità di riferimento).
- Test correlato: F0-48 (idempotenza della stessa importazione alla seconda esecuzione).

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente di sviluppo locale (Docker Compose secondo `CLAUDE.md`/`make setup`), **mai** l'ambiente UAT.
- `v1dumps/latest.sql` presente (convenzione documentata in `CLAUDE.md`, sezione ETL).
- Database locale azzerabile (`migrate:fresh` accettabile, dati non critici).
- `APP_ENV` dell'ambiente locale impostato su un valore diverso da `production` (tipicamente `local`).

**Dati di test**
Nessun dato da inserire manualmente: `make setup`/`v1:import --anonymize` popola da sé l'intero
dataset a partire dal dump.

**Stato iniziale**
Database locale vuoto (post `migrate:fresh`), ruoli/permessi già seminati da
`RolePermissionSeeder`, `db_legacy` avviato e caricato con `v1dumps/latest.sql`.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del progetto locale | `make setup` (oppure, passo per passo, `docker compose exec app php artisan v1:import --anonymize`) | Il comando termina senza errori |
| 2 | Leggere il riepilogo per stage stampato dal comando | Output di `v1:import` | Ogni stage (`users`, `tickets`, `tags`, `documentation`, `activity_reports`, `fundraising_*`, ecc.) riporta `creati` > 0 (a meno che il dump non contenga righe per quello stage) |
| 3 | Verificare la presenza delle 5 identità di riferimento | `docker compose exec app php artisan tinker --execute="echo App\Domain\Identity\Models\User::whereIn('email', ['info@montagnaservizi.com','lorena.sava@montagnaservizi.com','manager@oc.test','infosentieroitalia@cai.it','sara.mariani@montagnaservizi.com'])->count();"` | Il comando restituisce `5` (i primi 4 importati dall'ETL, il quinto — Manager — creato da `collaudo:ensure-manager-account`, eseguito da `make setup` subito dopo l'import) |
| 4 | Verificare che i ticket importati coprano più stati/tipi reali | `docker compose exec app php artisan tinker --execute="dd(App\Domain\Ticketing\Models\Ticket::distinct()->pluck('status'));"` (e analogo per `type`) | Sono presenti più valori distinti (l'elenco esatto dipende dal dump caricato, non è più un insieme fisso — vedi punto 13 di `00-istruzioni-generali.md`) |

**Risultato finale atteso**
`v1:import --anonymize` popola correttamente l'ambiente locale con dati reali anonimizzati su tutti
i moduli in scope, e le 5 identità di riferimento del collaudo sono disponibili per il login.

**Controlli negativi**
`v1:import --truncate` in un ambiente `production` deve essere rifiutato esplicitamente (già
verificato da `tests/Feature/Console/V1ImportCommandTest.php::--truncate is refused outright in a
production environment`) — **non eseguire mai questo controllo contro un ambiente realmente
configurato come `production`**.

**Evidenze da acquisire**
- Output completo di `make setup`/`v1:import --anonymize`.
- Output dei conteggi Tinker per le 5 identità di riferimento e per gli stati/tipi ticket.

**Criterio di superamento**

PASS: il comando termina senza errori, tutti gli stage popolano dati e le 5 identità di riferimento sono presenti.
FAIL: un errore inatteso durante l'import, oppure una o più identità di riferimento mancanti.
BLOCKED: ambiente di sviluppo locale non disponibile/configurabile, oppure `v1dumps/latest.sql` assente.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: è un ambiente di sviluppo locale, liberamente ripristinabile con un successivo `make setup`.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-48 — Una seconda esecuzione di `v1:import` non duplica nulla (idempotenza)

**Obiettivo**
Verificare che rieseguire `v1:import --anonymize` (senza un `migrate:fresh` di mezzo, scenario
realistico per uno sviluppatore che rilancia l'import dopo aver aggiornato `v1dumps/latest.sql`)
non produca alcun duplicato: ogni stage registrato deve riportare `creati = 0` e `aggiornati = 0`
alla seconda esecuzione, a fronte dello stesso dump.

**Riferimenti**
- Requisito/regola di dominio: nota `CLAUDE.md` "BUG DI IDEMPOTENZA REALE trovato e corretto in
  `TicketsStage`... durante la scrittura del test di idempotenza dell'intera pipeline (US-216)".
- Test automatico: `tests/Feature/Console/V1ImportPipelineIdempotencyTest.php` — `a second
  consecutive v1:import run creates/updates nothing on every registered stage`.
- File/componente applicativo rilevante: `app/Console/Commands/V1ImportCommand.php`;
  `app/Import/ImportRunner.php`; `app/Import/Models/ImportRun.php`.
- Test correlato: F0-47 (prima esecuzione dello stesso import).

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente di sviluppo locale (mai UAT), stesso di F0-47.
- `v1:import --anonymize` già eseguito una prima volta con successo (F0-47), senza modificare
  `v1dumps/latest.sql` nel frattempo.

**Dati di test**
Nessuno: si tratta di rieseguire lo stesso comando senza parametri aggiuntivi.

**Stato iniziale**
Database locale già popolato da una prima esecuzione di `v1:import --anonymize` (F0-47).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Annotare i conteggi correnti di un paio di tabelle rappresentative | `docker compose exec app php artisan tinker --execute="echo App\Domain\Ticketing\Models\Ticket::count();"` (e analogo per `App\Domain\Identity\Models\User`) | I conteggi corrispondono a quelli osservati alla fine di F0-47 |
| 2 | Rieseguire l'import | `docker compose exec app php artisan v1:import --anonymize` | Il comando termina senza errori |
| 3 | Leggere il riepilogo per stage stampato dal comando | Output di `v1:import` | Ogni stage riporta `creati: 0, aggiornati: 0` (eventuali righe sono tutte `saltati`, cioè già presenti e senza differenze) |
| 4 | Rileggere gli stessi conteggi del passo 1 | Stessa query Tinker del passo 1 | I conteggi restano identici (nessun incremento) |

**Risultato finale atteso**
Dopo una seconda esecuzione di `v1:import --anonymize` a fronte dello stesso dump, ogni stage
registrato riporta `creati = 0` e `aggiornati = 0`, e i conteggi delle tabelle principali non
cambiano.

**Controlli negativi**
Nessuno applicabile: non esiste un percorso alternativo "duplica intenzionalmente" da testare in negativo.

**Evidenze da acquisire**
- Output completo della seconda esecuzione di `v1:import --anonymize` (riepilogo per stage).
- Output dei conteggi Tinker prima e dopo la seconda esecuzione.

**Criterio di superamento**

PASS: ogni stage riporta `creati = 0, aggiornati = 0` alla seconda esecuzione e i conteggi restano invariati.
FAIL: almeno uno stage riporta `creati` o `aggiornati` > 0 alla seconda esecuzione (duplicazione o mutazione spuria rilevata).
BLOCKED: ambiente di sviluppo locale non disponibile, oppure F0-47 non è stato eseguito prima.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: è un ambiente di sviluppo locale, liberamente ripristinabile con un successivo `make setup`.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

## ETL — analizzatori di v1:inspect (solo verifica struttura)

> Nota generale su questo blocco: gli 8 analizzatori seguenti (`app/Import/Inspect/Analyzers/*.php`)
> sono classi PHP pure, senza alcuna interfaccia utente, usate dal comando `php artisan v1:inspect`
> durante l'importazione dati dal sistema v1 (Fase 2, non ancora iniziata in produzione secondo
> `CLAUDE.md`/ambito escluso del collaudo). Eseguire realmente `v1:inspect` richiederebbe un dump
> del sistema v1 caricato in `db_legacy`, normalmente non disponibile in ambiente UAT: per tutti e 8
> questi test la modalità di esecuzione realistica è quindi AUTOMATICO (suite Pest), non un vero
> collaudo TECNICO CLI del comando `v1:inspect` — lo si dichiara esplicitamente per non far credere
> a un dump v1 disponibile in UAT che in realtà non c'è.

### F0-49 — L'analizzatore di chiavi esterne orfane conta correttamente le righe orfane e ignora i valori nulli

**Obiettivo**
Verificare che `OrphanForeignKeyAnalyzer::analyze()` (funzione generica riusata per ogni relazione
FK del dump v1 da ispezionare) conti correttamente quanti valori figli non nulli non trovano
corrispondenza in un insieme di id genitori, ignorando del tutto i valori `null` (che non
rappresentano una FK orfana, semplicemente un riferimento assente), e che tronchi l'elenco dei
campioni orfani al limite configurato pur mantenendo il conteggio totale esatto.

**Riferimenti**
- Requisito/regola di dominio: PRD US-008 (ispezione dump v1, analisi di qualità dati pre-importazione).
- Test automatico: `tests/Unit/Import/Inspect/OrphanForeignKeyAnalyzerTest.php` — `counts orphan
  values and ignores nulls`
- File/componente applicativo rilevante: `app/Import/Inspect/Analyzers/OrphanForeignKeyAnalyzer.php`;
  `app/Console/Commands/V1InspectCommand.php` (unico punto che invoca l'analizzatore, contro dati
  reali della connessione `legacy`).
- Test correlato: Nessuno.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Bassa

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: l'analizzatore è testato con array PHP
  predisposti dal test, non con dati reali.

**Dati di test**
`childValues = [1, 2, null, 99, 100]`, `parentIds = [1, 2, 3]` (id 99 e 100 non hanno corrispondenza).

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto, solo esecuzione di un metodo statico puro.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "counts orphan values and ignores nulls"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: su 5 valori con un `null`, il conteggio "checked" esclude il null
(risultato atteso dal test: 4 valori controllati, 2 orfani individuati, valori orfani `[99, 100]`).

**Controlli negativi**
Nessuno applicabile: il file di test copre già il caso "zero orfani" e il caso "troncamento dei
campioni al limite mantenendo il conteggio pieno" come varianti dello stesso metodo.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-50 — L'analizzatore di email duplicate individua i duplicati che differiscono solo per maiuscole/minuscole

**Obiettivo**
Verificare che `DuplicateEmailAnalyzer::analyze()` individui correttamente due (o più) righe come
"stessa email" quando differiscono solo per maiuscole/minuscole (es. `Mario.Rossi@example.com` vs
`mario.rossi@example.com`), raggruppandole con conteggio, elenco id ed esempi grafici distinti —
informazione cruciale per decidere come deduplicare gli utenti durante l'importazione reale dal v1
(Fase 2).

**Riferimenti**
- Requisito/regola di dominio: PRD US-008 (ispezione dump v1); coerente con l'indice funzionale
  case-insensitive già adottato su `users.email` in produzione (US-010, `lower(email)`).
- Test automatico: `tests/Unit/Import/Inspect/DuplicateEmailAnalyzerTest.php` — `finds duplicate
  emails that differ only by case`
- File/componente applicativo rilevante: `app/Import/Inspect/Analyzers/DuplicateEmailAnalyzer.php`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Bassa

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
`[{id:1, email:'Mario.Rossi@example.com'}, {id:2, email:'mario.rossi@example.com'}, {id:3,
email:'unique@example.com'}]`.

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "finds duplicate emails that differ only by case"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: viene individuato un solo gruppo di duplicati
(`email_lower = 'mario.rossi@example.com'`, conteggio 2, id `[1, 2]`, esempi con la grafia
originale di entrambe le righe); l'email `unique@example.com` non genera alcun duplicato.

**Controlli negativi**
Nessuno applicabile: il file di test copre già il caso "nessun duplicato" come variante dello stesso metodo.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-51 — L'analizzatore del changes di story_logs conta i JSON interpretabili e la distribuzione delle chiavi

**Obiettivo**
Verificare che `ChangesKeyAnalyzer::analyze()` distingua correttamente, tra i valori grezzi della
colonna `story_logs.changes` del v1, quelli che sono JSON validi e strutturati come oggetto/array
(interpretabili) da quelli che non lo sono (null, stringa vuota, testo non-JSON, o un JSON scalare
come una stringa/numero puro), e che calcoli la distribuzione di frequenza di ciascuna chiave
presente nei JSON interpretabili — informazione necessaria per capire quali campi il v1 tracciava
davvero nello storico dei cambiamenti prima di mapparli alla nuova struttura `ticket_logs`.

**Riferimenti**
- Requisito/regola di dominio: PRD US-008 (ispezione dump v1, mappatura storico dei cambiamenti).
- Test automatico: `tests/Unit/Import/Inspect/ChangesKeyAnalyzerTest.php` — `counts interpretable
  JSON changes and their key distribution`
- File/componente applicativo rilevante: `app/Import/Inspect/Analyzers/ChangesKeyAnalyzer.php`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Bassa

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
`['{"status":["new","assigned"]}', '{"status":["assigned","progress"],"assignee_id":[null,3]}',
null, '', 'not json']`.

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "counts interpretable JSON changes and their key distribution"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: su 5 valori, 2 risultano interpretabili e 3 non interpretabili
(null, stringa vuota, testo non-JSON); la distribuzione delle chiavi riporta `status` presente in
entrambi i JSON interpretabili e `assignee_id` presente in uno solo.

**Controlli negativi**
Il file di test copre già come variante il caso "JSON scalare (non oggetto/array) trattato come non
interpretabile" (`'"just a string"'`, `'42'`): entrambi devono risultare non interpretabili.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-52 — L'analizzatore di customer_request separa correttamente un elenco HTML in messaggi distinti

**Obiettivo**
Verificare che `CustomerRequestAnalyzer` sappia riconoscere quando il campo `customer_request` del
v1 contiene in realtà un elenco HTML (`<li>...</li>`) che rappresenta più messaggi distinti concatenati
in un unico campo testo, separandoli correttamente uno per uno (testo pulito, senza tag HTML), a
differenza di un testo semplice senza lista che va trattato come un unico messaggio — passaggio
necessario per decidere, nella futura importazione reale, se un `customer_request` va mappato a un
solo `ticket_message` o a più righe distinte.

**Riferimenti**
- Requisito/regola di dominio: PRD US-008 (ispezione dump v1, mappatura conversazione ticket).
- Test automatico: `tests/Unit/Import/Inspect/CustomerRequestAnalyzerTest.php` — `splits an HTML
  list customer_request into distinct messages`
- File/componente applicativo rilevante: `app/Import/Inspect/Analyzers/CustomerRequestAnalyzer.php`
  (metodo `parseMessages()`).
- Test correlato: Nessuno.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Bassa

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
`'<ul><li><p>Parere positivo</p></li><li><p>Contattare Rita</p></li></ul>'`.

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "splits an HTML list customer_request into distinct messages"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: l'elenco HTML a due `<li>` viene diviso in esattamente due
messaggi, `['Parere positivo', 'Contattare Rita']`, senza tag HTML residui.

**Controlli negativi**
Il file di test copre già come varianti: testo semplice senza `<li>` trattato come singolo messaggio,
markup vuoto/solo spazi che restituisce un elenco vuoto, e il conteggio aggregato
`non_empty_count`/`multi_message_count` con relativi campioni troncati al limite configurato.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-53 — L'analizzatore dei ruoli utente v1 distingue ruoli JSON, ruoli scalari e valori nulli/sconosciuti

**Obiettivo**
Verificare che `RoleValueAnalyzer::analyze()` classifichi correttamente i valori grezzi della
colonna ruoli utente del v1 in tre categorie: array JSON di ruoli (es. `'["admin","developer"]'`),
valore scalare singolo (es. `'manager'`), e valori nulli/vuoti — mantenendo sia la distribuzione dei
valori grezzi originali sia quella dei singoli ruoli risolti, indispensabile per progettare la
mappatura verso i 5 ruoli applicativi dell'enum `UserRole` nella futura importazione reale.

**Riferimenti**
- Requisito/regola di dominio: PRD US-008 (ispezione dump v1, mappatura ruoli utente verso `UserRole`).
- Test automatico: `tests/Unit/Import/Inspect/RoleValueAnalyzerTest.php` — `classifies JSON array
  roles, scalar roles, and null/empty values`
- File/componente applicativo rilevante: `app/Import/Inspect/Analyzers/RoleValueAnalyzer.php`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Bassa

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
`['["admin","developer"]', '["admin"]', 'manager', null, '']`.

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "classifies JSON array roles, scalar roles, and null/empty values"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: su 5 valori, 2 sono nulli/vuoti, 2 sono array JSON, 1 è scalare; la
distribuzione dei ruoli risolti riporta `admin` con conteggio 2, `developer` e `manager` con
conteggio 1 ciascuno.

**Controlli negativi**
Il file di test copre già come variante il caso di un dataset completamente vuoto (`[]`), che deve
restituire tutte le distribuzioni come array vuoti e `total = 0`.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-54 — L'analizzatore delle incongruenze stato/timestamp trova le righe in uno stato che richiede una data assente

**Obiettivo**
Verificare che `StatusTimestampAnalyzer::analyze()` individui correttamente, tra le righe che si
trovano in un determinato stato target (es. `done`), quelle a cui manca il timestamp che ci si
aspetterebbe fosse valorizzato per quello stato (es. la data di completamento) — mentre ignora del
tutto le righe in stati diversi da quello target, non pertinenti al controllo.

**Riferimenti**
- Requisito/regola di dominio: PRD US-008 (ispezione dump v1, coerenza stato/timestamp storico).
- Test automatico: `tests/Unit/Import/Inspect/StatusTimestampAnalyzerTest.php` — `finds rows in the
  target status with a missing timestamp`
- File/componente applicativo rilevante: `app/Import/Inspect/Analyzers/StatusTimestampAnalyzer.php`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Bassa

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
`[{id:1, status:'done', timestamp:'2025-01-01 00:00:00'}, {id:2, status:'done', timestamp:null},
{id:3, status:'progress', timestamp:null}]`, stato target `'done'`.

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "finds rows in the target status with a missing timestamp"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: sulle 2 righe in stato `done`, 1 ha il timestamp mancante (id `2`);
la riga in stato `progress` viene esclusa dal conteggio "checked" perché non pertinente allo stato target.

**Controlli negativi**
Il file di test copre già come variante il caso in cui nessuna riga sia nello stato target (righe
tutte in uno stato diverso): sia "checked" sia "missing_count" devono risultare `0`.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-55 — L'analizzatore della gerarchia story_story individua le incongruenze rispetto a stories.parent_id in entrambe le direzioni

**Obiettivo**
Verificare che `StoryHierarchyAnalyzer::analyze()` confronti correttamente, in entrambe le
direzioni, la tabella di associazione `story_story` del v1 (righe `parent_id`/`child_id`) con la
colonna diretta `stories.parent_id`: da un lato le righe di `story_story` il cui genitore dichiarato
non coincide con quanto riportato in `stories.parent_id` per quel figlio, dall'altro le righe di
`stories` che dichiarano un genitore mai riflesso da alcuna riga di `story_story` — entrambe
incongruenze da risolvere prima di poter fidarsi di una sola delle due fonti per l'importazione reale.

**Riferimenti**
- Requisito/regola di dominio: PRD US-008 (ispezione dump v1, coerenza gerarchia ticket
  padre/figlio, coerente con il vincolo "un solo livello di profondità" di §6.1.6 della release finale).
- Test automatico: `tests/Unit/Import/Inspect/StoryHierarchyAnalyzerTest.php` — `detects mismatches
  between story_story rows and stories.parent_id`
- File/componente applicativo rilevante: `app/Import/Inspect/Analyzers/StoryHierarchyAnalyzer.php`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Bassa

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
`stories = [{id:1,parent_id:null},{id:2,parent_id:1},{id:3,parent_id:null},{id:4,parent_id:999}]`;
`storyStoryRows = [{parent_id:1,child_id:2},{parent_id:1,child_id:3}]`.

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "detects mismatches between story_story rows and stories.parent_id"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: delle 2 righe di `story_story`, 1 non è riflessa in
`stories.parent_id` (la storia `id 3` ha `parent_id = null` mentre `story_story` dichiara `parent_id
= 1`); e viceversa 1 riga di `stories` (`id 4`, `parent_id = 999`) non è riflessa in alcuna riga di
`story_story`.

**Controlli negativi**
Il file di test copre già come variante il caso "zero incongruenze" quando le due fonti sono
perfettamente coerenti tra loro.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F0-56 — L'analizzatore dei tag polimorfici raggruppa i taggable_type e conta quelli diversi da Documentation

**Obiettivo**
Verificare che `TaggableAnalyzer::analyze()` raggruppi correttamente per valore di `taggable_type` i
tag polimorfici del v1 (inclusa una categoria esplicita `(null)` per i valori nulli), e conti
separatamente quanti di essi si riferiscono a un tipo diverso da `Documentation` — l'unico tipo che
il v1 usava in modo "sbagliato" secondo l'anti-pattern noto (`Documentation::creator()` verso una
colonna inesistente, già documentato in `CLAUDE.md`): questo conteggio aiuta a stimare l'impatto di
un'eventuale migrazione dei tag verso altri tipi di entità nella nuova struttura.

**Riferimenti**
- Requisito/regola di dominio: PRD US-008 (ispezione dump v1, tag polimorfici legacy).
- Test automatico: `tests/Unit/Import/Inspect/TaggableAnalyzerTest.php` — `groups taggable types and
  counts those different from Documentation`
- File/componente applicativo rilevante: `app/Import/Inspect/Analyzers/TaggableAnalyzer.php`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Bassa

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
`['App\\Models\\Documentation', 'App\\Models\\Documentation', 'App\\Models\\Story', null]`.

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "groups taggable types and counts those different from Documentation"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: su 4 valori, `App\Models\Documentation` compare 2 volte,
`App\Models\Story` 1 volta, `(null)` 1 volta; il conteggio "diversi da Documentation" è `2` (1 per
`Story` + 1 per il valore nullo).

**Controlli negativi**
Nessuno applicabile: il singolo test copre già sia il raggruppamento sia il conteggio derivato nello
stesso scenario.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:
