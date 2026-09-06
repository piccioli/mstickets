# PRD — Piattaforma Montagna Servizi (Orchestrator v2)

**Documento di prodotto e architettura per la riscrittura completa di Orchestrator**


|                          |                                                                                               |
| ------------------------ | --------------------------------------------------------------------------------------------- |
| **Versione documento**   | 2.0                                                                                           |
| **Data**                 | 25 luglio 2026                                                                                |
| **Committente**          | Alessio Piccioli — Webmapp / Montagna Servizi S.C.p.A.                                        |
| **Destinatario**         | Claude Code (Raphael) — agente di implementazione                                             |
| **Codebase di partenza** | `orchestrator` — Laravel 10 + Laravel Nova 4, PostgreSQL, ~30.000 righe di codice applicativo |
| **Target**               | Nuovo repository, Laravel 13 + Filament 4, Docker, **schema di database riprogettato**        |


> **Modifiche rispetto alla v1.0 del documento**: lo schema del database non è più vincolato a quello di
> produzione — viene riprogettato, e i dati arrivano da una **procedura di importazione** dal dump v1 (§11).
> La sincronizzazione con Google Calendar è **fuori scope**.

---



## 0. Come usare questo documento

Questo PRD descrive **cosa** costruire e **con quali vincoli**. Non è una specifica riga-per-riga: le scelte
implementative di dettaglio sono delegate all'agente, purché rispettino i vincoli espressi qui.

### 0.1 Ordine di esecuzione obbligatorio

1. **Leggi l'intero documento** prima di scrivere codice.
2. **Importa il design** seguendo §8.1. Questo va fatto **prima** di scrivere qualunque componente di UI:
  il design system è vincolante, non decorativo.
3. **Esegui l'inventario del design** (§8.2): elenca le schermate e le feature presenti nel mockup, e classifica
  ciascuna come *in scope in questa release* o *nuova feature (fuori scope)*. Produci
   `docs/design-inventory.md`.
4. **Chiedi conferma** su questo inventario prima di procedere con la Fase 1 della roadmap (§14).
5. **Ispeziona il dump v1** prima di finalizzare lo schema (§11.3): alcune scelte di mapping dipendono da com'è
  fatto il dato reale, non da com'è dichiarato il modello.
6. Implementa seguendo la roadmap a fasi di §14, un PR/commit per fase.



### 0.2 Regole di ingaggio

- **Non copiare codice dal repository v1.** Il v1 è la *specifica del comportamento*, non un modello di
implementazione. §16 elenca gli anti-pattern che vanno attivamente evitati: se ti trovi a replicarne uno,
fermati.
- **Nessuna nuova feature.** Il design contiene schermate e funzionalità non ancora richieste. Vanno
**progettate come punti di estensione** (§15) ma **non implementate**.
- **Nessuna migrazione automatica dal v1 in produzione**: l'importazione è una procedura eseguita
consapevolmente (§11), non un ponte permanente tra i due sistemi.
- Quando una decisione non è coperta dal documento e ha impatto architetturale, **fermati e chiedi**.



### 0.3 Glossario e mappa dei nomi

Lo schema v2 rinomina le entità per coerenza con il linguaggio d'uso. Questa tabella è il riferimento per
leggere il resto del documento e per scrivere l'ETL.


| v1 (Nova)                                                                                           | v2                                       | Note                                           |
| --------------------------------------------------------------------------------------------------- | ---------------------------------------- | ---------------------------------------------- |
| `stories` / "Story"                                                                                 | `tickets` / "Ticket"                     | l'interfaccia v1 già li chiamava Ticket        |
| `stories.name`                                                                                      | `tickets.title`                          |                                                |
| `stories.user_id`                                                                                   | `tickets.assignee_id`                    | il developer assegnato                         |
| `stories.creator_id`                                                                                | `tickets.requester_id`                   | chi ha aperto la richiesta                     |
| `stories.customer_request`                                                                          | tabella `ticket_messages`                | la conversazione diventa strutturata           |
| `stories.hours`                                                                                     | `tickets.worked_minutes`                 | interi, non float                              |
| `stories.test_dev` / `test_prod`                                                                    | `tickets.staging_url` / `production_url` |                                                |
| `story_logs`                                                                                        | `ticket_logs` + `ticket_views`           | separati: eventi di dominio vs visualizzazioni |
| `users_stories_log`                                                                                 | `ticket_work_logs`                       | aggregato giornaliero derivato                 |
| `story_story`                                                                                       | *eliminata*                              | la gerarchia vive solo in `tickets.parent_id`  |
| `taggables` (parte ticket)                                                                          | `ticket_tag`                             | pivot esplicito                                |
| `tags.taggable_*`                                                                                   | `tags.documentation_id`                  | il morph polimorfico sparisce                  |
| `documentations`                                                                                    | `documentation_pages`                    |                                                |
| `users.roles` (JSON su varchar)                                                                     | tabelle `spatie/laravel-permission`      | ruoli e permessi normalizzati (§9)             |
| `users.activity_report_language`                                                                    | `users.locale`                           | è la lingua di **tutte** le comunicazioni      |
| `activity_reports.customer_id`                                                                      | `activity_reports.owner_user_id`         | il v1 puntava a `users` con un nome fuorviante |
| 34 colonne `evaluation_*`                                                                           | `fundraising_evaluation_scores`          | normalizzate in righe                          |
| `customers`, `quotes`, `products`, `epics`, `milestones`, `projects`, `deadlines`, `apps`, `layers` | *non importate*                          | §3.2                                           |


Altri termini:


| Termine            | Significato                                                                                                         |
| ------------------ | ------------------------------------------------------------------------------------------------------------------- |
| **Tag / commessa** | Nel v1 il Tag ha assunto il ruolo di *commessa*: ha una stima ore e un SAL calcolato. Non è una semplice etichetta. |
| **v1**             | Il codice attualmente in produzione (Nova).                                                                         |
| **v2**             | Il sistema descritto da questo documento.                                                                           |
| **ETL**            | La procedura di importazione dei dati dal dump v1 (§11).                                                            |


---



## 1. Contesto e obiettivi



### 1.1 Cos'è Orchestrator

Orchestrator è il gestionale interno con cui Montagna Servizi S.C.p.A. (e prima Webmapp) gestisce:

- **Help desk / ticketing**: i clienti aprono richieste via interfaccia web o via email; le richieste diventano
ticket assegnati a sviluppatori, con un ciclo di vita a 12 stati, conversazione bidirezionale, allegati.
- **Time tracking implicito**: le ore lavorate non vengono inserite a mano, ma **derivate dai log di
cambio stato** dei ticket. È una caratteristica identitaria del prodotto, non un ripiego.
- **Rendicontazione al cliente**: report di attività mensili/annuali in PDF, per cliente o per organizzazione.
- **Documentazione**: base di conoscenza interna e per il cliente, esportabile in PDF con carta intestata.
- **Fundraising**: censimento di bandi con griglia di valutazione strutturata, e gestione dei progetti candidati.



### 1.2 Perché riscrivere


| Problema                          | Evidenza nel v1                                                                                                                                                                                                                                                                                                     |
| --------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Laravel Nova è un vincolo         | 46 risorse Nova, di cui **27 sono risorse ridondanti sullo stesso modello** (15 su `Story`, 7 su `Epic`, 3 su `Quote`, 2 su `FundraisingOpportunity`/`Deadline`) create solo per applicare un filtro di index. 12 pacchetti Nova di terze parti. 1.124 righe di `fieldTrait.php` per aggirare i limiti dei campi.   |
| Business logic non governabile    | La stessa entità `Story` ha logica in **3 livelli sovrapposti**: hook `boot()`/`booted()` nel modello, `StoryObserver`, e 4 service. `saveQuietly()` sparso per rompere i loop. Validazioni implementate come `throw new Exception` dentro `saving()`.                                                              |
| Bug latenti da anni               | Precedenza operatori errata in `Story::updated()` → notifiche spurie. `$recipient->role` in un template email → attributo inesistente, i clienti ricevono sempre il template sbagliato. Enum non castati, confronti su stringhe grezze.                                                                             |
| Gestione email inaffidabile       | Nessun threading, nessuna idempotenza, duplicazione garantita dei ticket su errore SMTP, stato di elaborazione conservato solo come flag IMAP. Vedi §7.1.                                                                                                                                                           |
| **Schema del database degradato** | Relazioni verso tabelle inesistenti, `$fillable` con colonne fantasma, gerarchia ticket duplicata (colonna **e** pivot), doppia semantica di tagging, ruoli come JSON in un `varchar(255)`, la conversazione del ticket come stringa HTML accumulata, 105 migrazioni stratificate con colonne create e poi rimosse. |
| Debito tecnico strutturale        | ~2.000 righe di dead code (dominio geografico GeoHub), moduli mai completati, documentazione che dichiara 7 moduli e ne descrive 3.                                                                                                                                                                                 |
| Stack a fine vita                 | Laravel 10 e PHP 8.1 sono fuori supporto.                                                                                                                                                                                                                                                                           |




### 1.3 Obiettivi della v2

**Obiettivi primari**

1. **Schema riprogettato** e **procedura di importazione** che porta i dati di produzione dal v1 al v2 in modo
  ripetibile e verificabile (§5, §11).
2. **Parità funzionale** sui moduli in scope (§3).
3. **Sostituire Nova con Filament 4** e la nuova identità visiva definita nel design (§8).
4. **Riscrivere da zero il sottosistema email** (§7): è il requisito con il maggior impatto sulla qualità
  percepita del prodotto.
5. **Business logic esplicita e testabile**: una macchina a stati dichiarativa per il ticket, service invocati
  dall'applicazione e non da hook Eloquent, validazioni come regole di validazione.

**Obiettivi secondari**

1. Predisporre i punti di estensione per le nuove feature emerse dal design, senza implementarle (§15).
2. Copertura di test automatici sulle regole di dominio e sull'importazione (§13).

**Non obiettivi**

- Nessuna migrazione dei moduli legacy (§3.2).
- Nessuna app mobile, nessuna API pubblica nuova.
- Nessun ponte permanente di sincronizzazione tra v1 e v2.

---



## 2. Decisioni già prese

Confermate dal committente. Non riaprirle senza chiedere.


| #       | Decisione                                                                                                                                                                                                                                                                                                      | Motivazione                                                                                                                                                                                                                                                                                     |
| ------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **D1**  | **Lo schema del database è riprogettato.** Nomi coerenti, enum in colonne tipizzate, ruoli normalizzati, difetti strutturali corretti. I dati arrivano da una **procedura ETL** che importa il dump di produzione v1 (§11).                                                                                    | Il vincolo dello schema legacy avrebbe portato il debito tecnico nel nuovo sistema. Con un ETL il costo è concentrato in un componente isolato e testabile.                                                                                                                                     |
| **D2**  | **Il concetto di "cliente" si unifica su** `User` **+ ruoli.** Il modello `Customer` non esiste in v2.                                                                                                                                                                                                         | È la direzione già presa dal v1: la migrazione fundraising di settembre 2025 ha spostato le FK da `customers` a `users`. Gli enti sono già gestiti da `Organization`.                                                                                                                           |
| **D3**  | **Moduli in scope**: Ticketing (core), Tag/commesse, Time tracking, Documentation + PDF, Activity Report + Organizations, Fundraising, Utenti/ruoli, Email.                                                                                                                                                    | Sono i moduli effettivamente usati.                                                                                                                                                                                                                                                             |
| **D4**  | **Moduli fuori scope**: CRM/Preventivi, Agile legacy (`Epic`, `Milestone`, `Project`), geografico (`App`, `Layer`), `Deadline`, Changelog.                                                                                                                                                                     | `Epic`/`Project` sono operativamente sostituiti da `Tag`; `App`/`Layer` sono residui GeoHub per metà non eseguibili; il modulo preventivi non è più usato.                                                                                                                                      |
| **D5**  | **Le dashboard Nova legacy non vengono portate.** Le schermate di overview e statistiche sono quelle definite dal design.                                                                                                                                                                                      | Le 9 dashboard `HtmlCard` + Blade del v1 sono impalcature attorno ai limiti di Nova.                                                                                                                                                                                                            |
| **D6**  | **Stack**: Laravel 13 + Filament 4 (stable), PHP 8.4, PostgreSQL 16, Redis, Docker Compose.                                                                                                                                                                                                                    | Vedi §4.1.                                                                                                                                                                                                                                                                                      |
| **D7**  | **Il sottosistema email è riscritto da zero**, con persistenza su DB, threading reale e idempotenza.                                                                                                                                                                                                           | Vedi §7.                                                                                                                                                                                                                                                                                        |
| **D8**  | **Rinomina delle entità** secondo la mappa di §0.3 (`stories` → `tickets`, ecc.).                                                                                                                                                                                                                              | Il linguaggio del codice si allinea a quello degli utenti.                                                                                                                                                                                                                                      |
| **D9**  | **La conversazione del ticket diventa un'entità**: tabella `ticket_messages`, unificata con le email. L'ETL scompone l'HTML accumulato del v1 in messaggi, con fallback a un unico messaggio legacy quando il parsing non riesce.                                                                              | Rende la conversazione interrogabile, collegabile alle email e sanitizzabile.                                                                                                                                                                                                                   |
| **D10** | **L'ETL è ripetibile e idempotente**: eseguibile N volte durante lo sviluppo senza duplicare nulla, con report di validazione.                                                                                                                                                                                 | Permette di lavorare su dati reali freschi e di ripetere il cutover.                                                                                                                                                                                                                            |
| **D11** | **I dati dei moduli fuori scope non vengono importati.** Il dump di produzione resta archiviato come riferimento storico.                                                                                                                                                                                      | Nessun peso morto nel nuovo database.                                                                                                                                                                                                                                                           |
| **D12** | **La sincronizzazione con Google Calendar è fuori scope.** Nessun pacchetto, nessun comando, nessuna configurazione.                                                                                                                                                                                           | Richiesta esplicita del committente.                                                                                                                                                                                                                                                            |
| **D13** | **Ruoli e permessi con** `spatie/laravel-permission`, ma con i **ruoli dichiarati in un enum PHP** (sorgente di verità) e i **permessi in un seeder versionato**: nessuna creazione di ruoli o permessi a runtime. Le **policy restano il punto di enforcement** per tutte le regole legate al singolo record. | Standard noto e manutenibile; separa le capacità dai ruoli, eliminando i tre casi in cui il v1 usa un ruolo come surrogato di un permesso. La dichiarazione nel codice preserva analisi statica e refactor sicuri, ed evita che un admin crei un ruolo che l'interfaccia non sa rendere (§9.1). |
| **D14** | **Il ruolo** `editor` **viene eliminato.** Le capacità che concedeva (modifica della documentazione) diventano **permessi**. Allo stesso modo l'accesso a Horizon e ai log diventa un permesso, non una prerogativa del ruolo `developer`.                                                                     | Era un permesso travestito da ruolo: nel v1 esiste solo per il dominio geografico (fuori scope) e per la documentazione.                                                                                                                                                                        |


---



## 3. Scope



### 3.1 In scope


| Modulo                            | Contenuto                                                                                                                                       | Rif.     |
| --------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- | -------- |
| **M1 — Ticketing**                | ciclo di vita a 12 stati, macchina a stati esplicita, conversazione strutturata, allegati, gerarchia padre/figlio, partecipanti, priorità, tipo | §6.1     |
| **M2 — Log e time tracking**      | `ticket_logs`, `ticket_views`, `ticket_work_logs`, derivazione delle ore lavorate                                                               | §6.2     |
| **M3 — Tag / commesse**           | stima ore, SAL, associazione ai ticket                                                                                                          | §6.3     |
| **M4 — Documentation**            | pagine, categorie, generazione PDF, tag automatico                                                                                              | §6.4     |
| **M5 — Activity Report**          | report mensili/annuali per utente o organizzazione, sync dei ticket per periodo, PDF multilingua                                                | §6.5     |
| **M6 — Fundraising**              | opportunità con griglia di valutazione normalizzata, progetti, partner                                                                          | §6.6     |
| **M7 — Utenti, ruoli e permessi** | ruoli multipli + permessi granulari (D13), policy sul record, impersonation, portale cliente                                                    | §6.7, §9 |
| **M8 — Email**                    | sottosistema inbound/outbound completamente nuovo                                                                                               | §7       |
| **M9 — Automazioni**              | comandi schedulati con feature flag                                                                                                             | §10      |
| **M10 — Design system**           | import e applicazione del design                                                                                                                | §8       |
| **M11 — Importazione dal v1**     | ETL ripetibile, idempotente, con validazione                                                                                                    | §11      |




### 3.2 Fuori scope

**Moduli non portati e dati non importati** (D4, D11):

- CRM/Preventivi: `customers`, `quotes`, `products`, `recurring_products` e relativi pivot
- Agile legacy: `epics`, `milestones`, `projects`, `epic_project_tags`
- Geografico: `apps`, `layers`, `user_app` — incluso l'endpoint `GET /api/app/{id}/config.json`
- Scadenze: `deadlines`, `deadlineables`
- Preferiti: `favorites` (usati solo su `Project`)
- **Changelog**: il v1 legge 57 directory in `changelog/` e genera dashboard dinamiche per minor release. Non è
un modulo di dominio ma documentazione di rilascio: da sostituire con le release note del repository (Q11)
- **Google Calendar** (D12)

**Conseguenze per l'ETL:**

- `stories.epic_id` e `stories.project_id` **non vengono importati**. Se l'ispezione del dump (§11.3) rivela
che l'associazione ticket→progetto è informazione ancora usata, segnalalo: l'alternativa naturale è
importarla come **tag**, non come entità.
- `Story::deadlines()` non esiste in v2.
- Il ricalcolo automatico dello stato dell'Epic dalle story non viene portato.
- I `tags` del v1 collegati polimorficamente a `Project`, `Customer` o `App` vengono importati come **tag
semplici**, perdendo il link alla risorsa (che non esiste più in v2). Solo il link a `Documentation` viene
preservato, come FK esplicita.

**Feature presenti nel design ma non implementate:** vedi §15.

---



## 4. Architettura tecnica



### 4.1 Stack


| Componente                | Versione / scelta                                                       | Note                                                                                    |
| ------------------------- | ----------------------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| PHP                       | **8.4**                                                                 |                                                                                         |
| Framework                 | **Laravel 13** (`^13.0`)                                                | ultima major al momento della stesura                                                   |
| Admin panel               | **Filament 4** (`^4.0`) stabile                                         | Tailwind CSS v4, MFA integrata, performance su tabelle grandi. **Non** usare la beta v5 |
| Database                  | **PostgreSQL 16**                                                       | PostGIS **non serve**: nessun modulo geografico in scope                                |
| Cache / queue / sessioni  | **Redis 7**                                                             |                                                                                         |
| Queue monitor             | **Laravel Horizon**                                                     |                                                                                         |
| Media                     | **spatie/laravel-medialibrary** v11+                                    | vedi §5.2 per le collection                                                             |
| PDF                       | **spatie/laravel-pdf** (Browsershot) *oppure* `barryvdh/laravel-dompdf` | scelta motivata in §6.4.3                                                               |
| Email inbound             | **webklex/php-imap** (standalone) o webhook del provider                | §7.4                                                                                    |
| Ruoli e permessi          | **spatie/laravel-permission** v6+                                       | storage di ruoli e permessi. **Non** sostituisce le policy: vedi §9.1                   |
| Autorizzazione sul record | Policy Laravel native                                                   | è qui che vive la maggior parte delle regole di questo sistema (§9.4)                   |
| Impersonation             | pacchetto Filament dedicato                                             | Filament 4 non la include (§6.7.2)                                                      |
| Sanitizzazione HTML       | `mews/purifier` o equivalente con allowlist                             | obbligatoria su ogni HTML di provenienza esterna                                        |
| Test                      | **Pest 3+**                                                             |                                                                                         |
| Static analysis           | **Larastan / PHPStan** livello 6 minimo                                 |                                                                                         |
| Formatter                 | **Laravel Pint**                                                        | preset `laravel`                                                                        |
| Assets                    | **Vite**                                                                |                                                                                         |


**Non** installare: `spatie/laravel-google-calendar` (D12), `spatie/laravel-translatable` (i campi tradotti del
v1 erano tutti su `Quote`/`Product`, fuori scope), `overtrue/laravel-favorite` (usato solo su `Project`).

### 4.2 Docker

`docker-compose.yml` con i servizi:


| Servizio    | Immagine                  | Note                                                                                                                                                                      |
| ----------- | ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app`       | build locale, PHP 8.4-FPM | volume del progetto                                                                                                                                                       |
| `web`       | `nginx:alpine`            | reverse proxy su `app`, unico entrypoint HTTP                                                                                                                             |
| `db`        | `postgres:16-alpine`      | volume persistente, healthcheck                                                                                                                                           |
| `db_legacy` | `postgres:16-alpine`      | **database di appoggio per l'ETL**: ospita il dump v1 in sola lettura. Avviabile con un profilo Compose dedicato (`--profile etl`) così che non giri in esercizio normale |
| `redis`     | `redis:7-alpine`          | volume persistente                                                                                                                                                        |
| `queue`     | come `app`                | Horizon, restart always                                                                                                                                                   |
| `scheduler` | come `app`                | `php artisan schedule:work` — **non** un cron di sistema                                                                                                                  |
| `mailpit`   | `axllent/mailpit`         | SMTP + **IMAP** locale per testare l'inbound in sviluppo                                                                                                                  |


Requisiti:

- **Nessun** `user: root`, nessun `chown` manuale nel README. Risolvi il mismatch di UID host/container in
build-time (`ARG UID`/`GID`) — nel v1 questo è documentato come "problema noto", in v2 non deve esistere.
- `.env.example` **completo e veritiero**: nel v1 manca ogni variabile `IMAP_`* e `QUEUE_CONNECTION` divergeva
tra `.env` di produzione e l'example. Ogni variabile usata dal codice deve essere presente e commentata.
- Un solo comando per il setup da zero: `make setup` che fa build, up, `composer install`, `key:generate`,
`migrate`, seed di sviluppo.
- Il **seed di sviluppo** deve rendere l'app navigabile senza il dump: un utente per ogni ruolo (credenziali
stampate a fine setup), l'utente di sistema, 2 organizzazioni, ~40 ticket distribuiti su tutti gli stati e i
tipi con conversazioni e allegati finti, ~10 tag con stime, 5 pagine di documentazione (internal + customer),
2 report di attività, 3 opportunità fundraising (una valutata) con 2 progetti.
- Per l'importazione dal v1 la procedura è quella di §11.2 (più passi, ciascuno verificabile). Fornisci i
target `make etl-up`, `make etl-load DUMP=...`, `make etl-run`, `make etl-validate` come alias dei comandi
Artisan, **senza** nascondere i passi in un unico comando opaco: l'ispezione e la validazione vanno lette.
- Healthcheck su tutti i servizi con dipendenze `condition: service_healthy`.



### 4.3 Struttura applicativa

Struttura a **moduli di dominio**, non a tipo tecnico.

```
app/
├── Domain/
│   ├── Ticketing/
│   │   ├── Models/            Ticket, TicketMessage, TicketLog, TicketView
│   │   ├── Enums/             TicketStatus, TicketType, TicketPriority
│   │   ├── StateMachine/      TicketStateMachine, Transition, guard
│   │   ├── Actions/           ChangeTicketStatus, AssignTicket, PostTicketMessage, ...
│   │   ├── Queries/           query object riusabili (sostituiscono gli indexQuery Nova)
│   │   ├── Events/            TicketCreated, TicketStatusChanged, TicketMessagePosted, ...
│   │   └── Listeners/
│   ├── TimeTracking/          TicketWorkLog, WorkedTimeCalculator
│   ├── Tags/
│   ├── Documentation/
│   ├── Reporting/             ActivityReport, Organization
│   ├── Fundraising/
│   ├── Identity/              User, UserRole, Permission, membership
│   └── Mail/                  sottosistema email (§7)
├── Import/                    ETL dal v1 (§11) — isolato, eliminabile a cutover concluso
│   ├── Stages/
│   ├── Mappers/
│   ├── Parsers/               parsing della conversazione HTML v1
│   └── Validation/
├── Filament/
│   ├── Resources/             una resource per entità, con tabs (non 27 resource ridondanti)
│   ├── Pages/
│   ├── Widgets/
│   └── Providers/
├── Support/
└── Providers/
```

`app/Import/` è **codice a termine**: va scritto in modo che possa essere rimosso in blocco quando il cutover
è concluso, senza toccare il resto. Nessuna classe di dominio deve dipendere da esso.

### 4.4 Principi architetturali vincolanti

Questi principi esistono per correggere problemi specifici del v1. Sono vincoli, non suggerimenti.

**A1 — Niente business logic negli hook Eloquent.**
I modelli contengono: `$fillable`, `$casts`, relazioni, scope, accessor. **Nessun** `boot()`/`booted()` con
logica di dominio, **nessun** Observer che cambia stato. Ogni mutazione significativa passa da una **Action**
esplicita. Motivo: nel v1 il cambio di stato di un ticket attraversa `Story::boot()`, `Story::booted()`,
`StoryObserver` e 4 service, con `saveQuietly()` usato per interrompere le ricorsioni; nessuno può dire con
certezza cosa accadrà a un `$story->save()`.

**A2 — Macchina a stati dichiarativa.**
Le transizioni sono definite in **una tabella dichiarativa** (§6.1.3): stato di partenza, stato di arrivo,
ruoli autorizzati, guard, side effect. Nessun `if` sparso. Una transizione non ammessa produce un errore di
validazione localizzato, **non** un'eccezione.

**A3 — Validazione nel layer di validazione.**
"Testing richiede un tester", "Waiting richiede un motivo", "un ticket figlio non può avere figli" sono
**regole di validazione**, non `throw new Exception` dentro `saving()`.

**A4 — Enum sempre castati.** Zero confronti su stringhe grezze.

**A5 — Effetti collaterali via eventi e queue.** Notifiche, email, ricalcolo tempi: **listener di eventi di
dominio**, accodati. Nessun invio SMTP sincrono dentro una richiesta HTTP.

**A6 — Query object invece di sottoclassi di resource.** Vedi §8.5.

**A7 — Idempotenza esplicita** per tutto ciò che è attivato da uno scheduler, da una coda o dall'ETL: chiave
di idempotenza, lock (`WithoutOverlapping`), retry con backoff, dead letter.

**A8 — Nessuna colonna, relazione o campo fantasma.** Ogni `$fillable`, ogni relazione, ogni accessor
corrisponde a qualcosa che esiste. §16 elenca quelli del v1.

**A9 — Lo schema è la documentazione.** Nomi espliciti, vincoli a livello di DB (`NOT NULL`, FK, unique, check
dove sensato), nessun campo il cui significato dipenda da una convenzione non scritta. Se una colonna esiste,
è usata.

---



## 5. Modello dati



### 5.1 Principi dello schema

1. **Nomenclatura**: tabelle al plurale, snake_case, inglese. Pivot con i due nomi al singolare in ordine
  alfabetico (`ticket_tag`) tranne dove un nome descrittivo è più chiaro
   (`fundraising_project_partners`).
2. **Enum come** `varchar` **+ backed enum PHP**, con valori in **lowercase slug**. Nessun tipo `ENUM` nativo di
  PostgreSQL (impossibile da estendere senza migrazione).
3. **Timestamp**: `created_at`/`updated_at` su tutto. `deleted_at` (soft delete) su `tickets`, `users`,
  `tags`, `documentation_pages` — le entità la cui cancellazione accidentale sarebbe grave.
4. **Vincoli espliciti**: FK con `ON DELETE` dichiarato caso per caso, unique dove esiste unicità logica,
  `NOT NULL` come default mentale.
5. **Nessun dato derivato senza rigenerazione**: le colonne denormalizzate (`tickets.worked_minutes`,
  i totali di valutazione) devono essere ricostruibili da un comando.
6. **Continuità degli identificativi**: `users.id`, `tickets.id`, `tags.id`,
  `documentation_pages.id`, `organizations.id`, `activity_reports.id` e le entità fundraising **conservano
   l'id del v1**. I clienti conoscono i numeri di ticket e i link contengono id: cambiarli romperebbe
   riferimenti esterni e la posta già inviata. Le sequenze vanno riallineate a fine import.



### 5.2 Tabelle

Notazione: `PK` chiave primaria, `FK→x` foreign key, `?` nullable, `U` unique.

#### Identità

`users`


| Colonna                                | Tipo                               | Note                                                                                                                                                      |
| -------------------------------------- | ---------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `id`                                   | bigserial PK                       | conservato dal v1                                                                                                                                         |
| `name`                                 | varchar(255)                       |                                                                                                                                                           |
| `email`                                | varchar(255) U                     | confronti sempre **case-insensitive**: indice funzionale su `lower(email)`                                                                                |
| `email_verified_at`                    | timestamp?                         |                                                                                                                                                           |
| `password`                             | varchar(255)?                      | nullable: esistono utenti creati dall'ETL che non hanno mai fatto login                                                                                   |
| `remember_token`                       | varchar(100)?                      |                                                                                                                                                           |
| `locale`                               | varchar(5) not null default `'it'` | lingua di **tutte** le comunicazioni (§7.6)                                                                                                               |
| `drive_url`                            | varchar?                           | cartella Drive del cliente, mostrata nel portale                                                                                                          |
| `drive_budget_url`                     | varchar?                           |                                                                                                                                                           |
| `help_desk_chat_url`                   | varchar?                           | link alla chat di supporto, mostrato nel portale cliente (§6.7.3). **Da creare solo se la feature è confermata** (Q17): una colonna inutilizzata viola A9 |
| `deactivated_at`                       | timestamp?                         | utente disattivato: non può accedere ma resta nei dati storici come richiedente/assegnatario. §6.7.5                                                      |
| `created_at`/`updated_at`/`deleted_at` |                                    |                                                                                                                                                           |


**Ruoli e permessi** — sostituiscono la colonna JSON `users.roles`

Le tabelle sono quelle standard di `spatie/laravel-permission` (migrazioni pubblicate dal pacchetto, non
scritte a mano): `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`.

Vincoli specifici di questo progetto:

- `guard_name` **unico** (`web`): non serve il multi-guard, non usarlo.
- **Nessun uso di** `teams`: la funzionalità va disattivata in configurazione.
- Il contenuto di `roles` e `permissions` è **seedato dal codice** e non modificabile a runtime: vedi §9.2.
- `Permission::create()` non va invocata dal codice applicativo, solo dal seeder.

Il modello completo di autorizzazione è in **§9**.

`organizations`

`id` PK (conservato), `name` varchar not null, `locale` varchar(5) not null default `'it'`, timestamps.

`organization_user`

`id` PK, `organization_id` FK cascade, `user_id` FK cascade, timestamps, **U(coppia)**.

#### Ticketing

`tickets`


| Colonna                                | Tipo                                      | Note                                                                                                         |
| -------------------------------------- | ----------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| `id`                                   | bigserial PK                              | **conservato dal v1**                                                                                        |
| `parent_id`                            | bigint? FK→tickets on delete set null     | **unica** sorgente della gerarchia. Profondità massima 1                                                     |
| `title`                                | varchar(255) not null                     |                                                                                                              |
| `description`                          | text?                                     | descrizione tecnica **interna**, non visibile ai clienti                                                     |
| `status`                               | varchar(20) not null default `'new'`      | `TicketStatus`                                                                                               |
| `previous_status`                      | varchar(20)?                              | stato da cui si è entrati in `waiting`/`problem`. **Esplicito**: nel v1 era ricostruito a posteriori dai log |
| `status_changed_at`                    | timestamp not null                        | timestamp dell'ultimo cambio di stato. Rende immediati i "giorni in attesa"                                  |
| `type`                                 | varchar(20) not null default `'helpdesk'` | `TicketType`                                                                                                 |
| `priority`                             | varchar(10) not null default `'low'`      | `TicketPriority`                                                                                             |
| `requester_id`                         | bigint? FK→users set null                 | chi ha aperto la richiesta                                                                                   |
| `assignee_id`                          | bigint? FK→users set null                 | developer assegnato                                                                                          |
| `tester_id`                            | bigint? FK→users set null                 |                                                                                                              |
| `fundraising_project_id`               | bigint? FK→fundraising_projects set null  |                                                                                                              |
| `waiting_reason`                       | text?                                     | obbligatorio se `status = waiting`                                                                           |
| `problem_reason`                       | text?                                     | obbligatorio se `status = problem`                                                                           |
| `estimated_hours`                      | numeric(6,2)?                             |                                                                                                              |
| `worked_minutes`                       | integer not null default 0                | **derivato** da `ticket_logs` (§6.2.2)                                                                       |
| `staging_url`                          | varchar?                                  |                                                                                                              |
| `production_url`                       | varchar?                                  |                                                                                                              |
| `released_at`                          | timestamp?                                |                                                                                                              |
| `done_at`                              | timestamp?                                |                                                                                                              |
| `created_at`/`updated_at`/`deleted_at` |                                           |                                                                                                              |


Indici: `status`, `(assignee_id, status)`, `(requester_id, status)`, `(tester_id, status)`, `parent_id`,
`(status, status_changed_at)`, `done_at`, indice full-text su `title`.

Colonne del v1 **non** portate: `epic_id`, `project_id`, `history_log`, `pull_request_link`,
`customer_request` (→ `ticket_messages`).

`ticket_messages` — la conversazione (D9)


| Colonna                   | Tipo                                    | Note                                                                                                                                                         |
| ------------------------- | --------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `id`                      | bigserial PK                            |                                                                                                                                                              |
| `ulid`                    | char(26) U                              | identificativo pubblico, usato nei token di reply (§7.3.6)                                                                                                   |
| `ticket_id`               | bigint FK cascade                       |                                                                                                                                                              |
| `author_id`               | bigint? FK→users set null               | null per messaggi di sistema o mittenti non riconosciuti                                                                                                     |
| `author_email`            | varchar?                                | valorizzata quando `author_id` è null                                                                                                                        |
| `channel`                 | varchar(10) not null                    | `web` | `email` | `system`                                                                                                                                   |
| `visibility`              | varchar(10) not null default `'public'` | `public` (visibile al richiedente) | `internal` (nota interna). In questa release l'UI espone **solo** `public`; `internal` è un punto di estensione (§15.2) |
| `body_html`               | text?                                   | **sanitizzato**                                                                                                                                              |
| `body_text`               | text?                                   |                                                                                                                                                              |
| `email_message_id`        | bigint? FK→email_messages set null      | collegamento al record di trasporto                                                                                                                          |
| `is_legacy_import`        | boolean not null default false          | true per i messaggi ricostruiti dall'HTML v1                                                                                                                 |
| `posted_at`               | timestamp not null                      |                                                                                                                                                              |
| `created_at`/`updated_at` |                                         |                                                                                                                                                              |


Indici: `(ticket_id, posted_at)`, `author_id`, `email_message_id`.
Media: collection `attachments` (medialibrary) su `TicketMessage`.

`ticket_logs` — eventi di dominio del ticket


| Colonna                   | Tipo                           | Note                                                                                                                     |
| ------------------------- | ------------------------------ | ------------------------------------------------------------------------------------------------------------------------ |
| `id`                      | bigserial PK                   |                                                                                                                          |
| `ticket_id`               | bigint FK cascade              |                                                                                                                          |
| `user_id`                 | bigint? FK→users set null      | **sempre valorizzato** all'atto della scrittura, con fallback sull'utente di sistema                                     |
| `event`                   | varchar(30) not null           | `created`, `status_changed`, `assigned`, `updated`, `message_posted`, `attachment_added`, `attachment_removed`, `system` |
| `from_status`             | varchar(20)?                   | valorizzate solo per `status_changed`. **Colonne, non JSON**: è la correzione più importante rispetto al v1              |
| `to_status`               | varchar(20)?                   |                                                                                                                          |
| `changes`                 | jsonb?                         | diff dei campi modificati. Il corpo di `description` **non** viene salvato: si registra il marker `"changed"`            |
| `is_system`               | boolean not null default false | true per le transizioni automatiche                                                                                      |
| `occurred_at`             | timestamp not null             |                                                                                                                          |
| `created_at`/`updated_at` |                                |                                                                                                                          |


Indici: `(ticket_id, occurred_at)`, `(user_id, occurred_at)`, `(to_status, occurred_at)`,
`(event, occurred_at)`, GIN su `changes`.

`ticket_views` — tracciamento visualizzazioni, **separato** dai log (nel v1 sono mescolati)

`id` PK, `ticket_id` FK cascade, `user_id` FK cascade, `viewed_on` date not null,
`last_viewed_at` timestamp not null, `view_count` integer not null default 1, timestamps,
**U(**`ticket_id`**,** `user_id`**,** `viewed_on`**)**.

`ticket_participants`

`id` PK, `ticket_id` FK cascade, `user_id` FK cascade, timestamps, **U(coppia)**.

`ticket_tag`

`id` PK, `ticket_id` FK cascade, `tag_id` FK cascade, timestamps, **U(coppia)**.

`ticket_work_logs` — aggregato giornaliero derivato (era `users_stories_log`)

`id` PK, `work_date` date not null, `user_id` FK cascade, `ticket_id` FK cascade,
`minutes` integer not null default 0, timestamps, **U(**`work_date`**,** `user_id`**,** `ticket_id`**)**,
indici su `work_date` e `(user_id, work_date)`.

#### Tag / commesse

`tags`

`id` PK (conservato), `name` varchar not null, `slug` varchar U, `description` text?,
`estimated_hours` numeric(8,2)?, `documentation_id` bigint? FK→documentation_pages set null,
timestamps, `deleted_at`?.

Il morph polimorfico del v1 sparisce: l'unico collegamento superstite è quello alla pagina di documentazione,
che diventa una FK esplicita.

#### Documentazione

`documentation_pages`

`id` PK (conservato), `title` varchar not null, `slug` varchar U, `body` text **not null**,
`category` varchar(10) not null default `'customer'` (`DocumentationCategory`), `pdf_path` varchar?,
`pdf_generated_at` timestamp?, timestamps, `deleted_at`?.

Media: collection `documents` e `images`.
Nel v1 esisteva una relazione `creator()` verso una colonna inesistente: **non** riprodurla. Se serve tracciare
l'autore, aggiungi `created_by` FK→users nullable — l'ETL non potrà popolarla e resterà null sui dati storici.

#### Rendicontazione

`activity_reports`


| Colonna                   | Tipo                             | Note                                                             |
| ------------------------- | -------------------------------- | ---------------------------------------------------------------- |
| `id`                      | bigserial PK                     | conservato                                                       |
| `owner_kind`              | varchar(15) not null             | `user` | `organization`                                          |
| `owner_user_id`           | bigint? FK→users cascade         | nel v1 la colonna si chiamava `customer_id` ma puntava a `users` |
| `owner_organization_id`   | bigint? FK→organizations cascade |                                                                  |
| `period_type`             | varchar(10) not null             | `monthly` | `annual`                                             |
| `year`                    | smallint not null                |                                                                  |
| `month`                   | smallint?                        | not null se `period_type = monthly`                              |
| `locale`                  | varchar(5) not null              | lingua con cui il PDF è stato generato                           |
| `pdf_path`                | varchar?                         |                                                                  |
| `pdf_generated_at`        | timestamp?                       |                                                                  |
| `created_at`/`updated_at` |                                  |                                                                  |


**U(**`owner_kind`**,** `owner_user_id`**,** `owner_organization_id`**,** `period_type`**,** `year`**,** `month`**)**.
Check: esattamente uno tra `owner_user_id` e `owner_organization_id` valorizzato, coerente con `owner_kind`.

`activity_report_ticket`

`id` PK, `activity_report_id` FK cascade, `ticket_id` FK cascade, timestamps, **U(coppia)**.

#### Fundraising

`fundraising_opportunities`


| Colonna                     | Tipo                                      | Note                                 |
| --------------------------- | ----------------------------------------- | ------------------------------------ |
| `id`                        | bigserial PK                              | conservato                           |
| `name`                      | varchar not null                          |                                      |
| `official_url`              | varchar?                                  |                                      |
| `endowment_fund`            | numeric(15,2)?                            |                                      |
| `deadline`                  | date **not null**                         |                                      |
| `program_name`              | varchar?                                  |                                      |
| `sponsor`                   | varchar?                                  |                                      |
| `cofinancing_quota`         | numeric(5,2)?                             | percentuale                          |
| `max_contribution`          | numeric(15,2)?                            |                                      |
| `territorial_scope`         | varchar(20) not null default `'national'` | `TerritorialScope`                   |
| `beneficiary_requirements`  | text?                                     |                                      |
| `lead_requirements`         | text?                                     |                                      |
| `created_by`                | bigint FK→users **not null**              |                                      |
| `responsible_user_id`       | bigint FK→users **not null**              |                                      |
| `evaluated_by`              | bigint? FK→users set null                 |                                      |
| `evaluated_at`              | timestamp?                                |                                      |
| `evaluation_positive_total` | smallint?                                 | **derivati**, ricostruibili (§6.6.2) |
| `evaluation_negative_total` | smallint?                                 |                                      |
| `evaluation_total`          | smallint?                                 |                                      |
| timestamps                  |                                           |                                      |


Indici: `deadline`, `territorial_scope`, `created_by`, `responsible_user_id`.

`fundraising_evaluation_scores` — le 34 colonne del v1 normalizzate in righe

`id` PK, `fundraising_opportunity_id` FK cascade, `criterion_key` varchar(40) not null,
`score` smallint not null, `notes` text?, timestamps, **U(**`fundraising_opportunity_id`**,** `criterion_key`**)**.

Il **catalogo dei criteri** (chiave, gruppo, etichetta, punteggio minimo e massimo, peso) vive in
configurazione/enum PHP, **non** nel database: è una regola di valutazione, non un dato. Elenco in §6.6.2.

`fundraising_projects`

`id` PK (conservato), `title` varchar not null, `fundraising_opportunity_id` FK cascade,
`lead_user_id` bigint? FK→users set null, `created_by` bigint FK→users, `responsible_user_id` bigint? FK→users,
`description` text?, `status` varchar(15) not null default `'draft'` (`FundraisingProjectStatus`),
`requested_amount` numeric(15,2)?, `approved_amount` numeric(15,2)?, `submitted_at` date?, `decided_at` date?,
timestamps.

`fundraising_project_partners`

`id` PK, `fundraising_project_id` FK cascade, `user_id` FK cascade, timestamps, **U(coppia)**.

#### Email

`email_messages` — registro unico di **tutte** le email, in ingresso e in uscita


| Colonna                   | Tipo                              | Note                                                                                                           |
| ------------------------- | --------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| `id`                      | bigserial PK                      |                                                                                                                |
| `ulid`                    | char(26) U                        | identificativo pubblico del record. **Il token di reply usa l'ulid del** `ticket_message`, non questo (§7.3.6) |
| `direction`               | varchar(10) not null              | `inbound` | `outbound`                                                                                         |
| `message_id`              | varchar(998)?                     | header `Message-ID`                                                                                            |
| `in_reply_to`             | varchar(998)?                     |                                                                                                                |
| `references`              | text?                             |                                                                                                                |
| `thread_id`               | bigint? FK→email_threads set null |                                                                                                                |
| `ticket_id`               | bigint? FK→tickets set null       |                                                                                                                |
| `user_id`                 | bigint? FK→users set null         | mittente/destinatario riconosciuto                                                                             |
| `from_email`, `from_name` | varchar                           |                                                                                                                |
| `to`, `cc`, `bcc`         | jsonb                             | array di `{email, name}`                                                                                       |
| `reply_to`                | varchar?                          |                                                                                                                |
| `subject`                 | text?                             |                                                                                                                |
| `body_text`               | text?                             |                                                                                                                |
| `body_html`               | text?                             | **sanitizzato**                                                                                                |
| `raw_path`                | varchar?                          | il `.eml` originale su storage                                                                                 |
| `status`                  | varchar(15) not null              | §7.3.2                                                                                                         |
| `failure_reason`          | text?                             |                                                                                                                |
| `attempts`                | smallint not null default 0       |                                                                                                                |
| `mailable_class`          | varchar?                          | solo outbound                                                                                                  |
| `provider_message_id`     | varchar?                          | ID del transport, per riconciliazione                                                                          |
| `imap_uid`                | integer?                          | solo inbound                                                                                                   |
| `imap_folder`             | varchar?                          |                                                                                                                |
| `content_hash`            | char(64)?                         | sha256 di (from + subject + body normalizzato) — deduplica                                                     |
| `received_at`, `sent_at`  | timestamp?                        |                                                                                                                |
| timestamps                |                                   |                                                                                                                |


Unique: `(direction, message_id)` dove `message_id` non è null; `(imap_folder, imap_uid)` per l'inbound.
Indici: `status`, `ticket_id`, `content_hash`, `thread_id`.

`email_threads`

`id` PK, `ticket_id` bigint? FK→tickets cascade, `subject_normalized` varchar not null,
`participants` jsonb, `last_message_at` timestamp, timestamps. Indici su `ticket_id`, `subject_normalized`,
`last_message_at`.

`participants` e `subject_normalized` alimentano il **match euristico** del thread (§7.3.6 punto 4): mittente
noto + subject normalizzato identico + thread attivo negli ultimi N giorni.

`email_attachments`

`id` PK, `email_message_id` FK cascade, `filename` varchar, `mime_type` varchar, `size_bytes` bigint,
`disk`/`path` varchar, `media_id` bigint?, `status` varchar(20)
(`stored`  `rejected_mime`  `rejected_size`  `failed`), `rejection_reason` text?, timestamps.

`email_suppressions`

`id` PK, `email` varchar U, `reason` varchar(20) (`hard_bounce`  `soft_bounce`  `complaint`  `manual` 
`loop_protection`), `bounce_count` smallint default 0, `notes` text?, `expires_at` timestamp?, timestamps.

`notification_preferences`

`id` PK, `user_id` FK cascade, `notification_type` varchar not null, `channel` varchar not null
(`mail`  `database`), `enabled` boolean not null default true, timestamps, **U(terna)**.

#### Importazione e infrastruttura

`import_runs` — audit dell'ETL (§11)

`id` PK, `started_at`, `finished_at`?, `dump_label` varchar (identificativo del dump usato),
`stages` jsonb (per ogni stage: righe lette, create, aggiornate, saltate, errori), `status` varchar
(`running`  `completed`  `failed`), `is_dry_run` boolean, `notes` text?, timestamps.

`import_mappings` — corrispondenza v1 → v2 per le entità in cui l'id **non** è conservato
(es. `ticket_messages`, `fundraising_evaluation_scores`)

`id` PK, `source_table` varchar, `source_key` varchar, `target_table` varchar, `target_id` bigint,
timestamps, **U(**`source_table`**,** `source_key`**,** `target_table`**)**.

Infrastruttura standard: `media`, `jobs`, `job_batches`, `failed_jobs`, `personal_access_tokens`, `cache`,
`sessions`, `password_reset_tokens`, `notifications`.

### 5.3 Enum

Tutti backed enum PHP con `label()` localizzata, `color()` e `icon()` dove pertinente, e le interfacce
Filament (`HasLabel`, `HasColor`, `HasIcon`).

#### `TicketStatus` (string) — **valori identici al v1**


| Case       | Valore     | Label IT       | Colore v1 | Icona |
| ---------- | ---------- | -------------- | --------- | ----- |
| `New`      | `new`      | Nuovo          | `#3b82f6` | ✨     |
| `Backlog`  | `backlog`  | Backlog        | `#64748b` | ⏱️    |
| `Assigned` | `assigned` | Assegnato      | `#ea580c` | 👤    |
| `Todo`     | `todo`     | Da fare        | `#f97316` | 📋    |
| `Progress` | `progress` | In lavorazione | `#fb923c` | ⚡     |
| `Testing`  | `testing`  | In test        | `#fdba74` | 🧪    |
| `Tested`   | `tested`   | Testato        | `#86efac` | ✅     |
| `Released` | `released` | Rilasciato     | `#16a34a` | 🌐    |
| `Done`     | `done`     | Completato     | `#4ade80` | ✔️    |
| `Problem`  | `problem`  | Problema       | `#dc2626` | ⚠️    |
| `Waiting`  | `waiting`  | In attesa      | `#eab308` | ⏸️    |
| `Rejected` | `rejected` | Rifiutato      | `#dc2626` | ❌     |


I valori restano quelli del v1 per non complicare l'ETL e per continuità dei riferimenti. Il case si chiama
`Testing` (nel v1 era `Test` con valore `testing`: incoerenza corretta).

Sui colori: l'enum è l'**unico** posto in cui vivono (nel v1 sono duplicati anche in
`config/orchestrator.php`). Quelli in tabella sono il punto di partenza: se il design (§8.3) definisce una
propria palette di stato, quella vince e va scritta nell'enum.

#### Altri enum


| Enum                                                                          | Valori v2                                                                                                                | Valori v1 corrispondenti                                                             |
| ----------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------ |
| `TicketType`                                                                  | `bug`, `feature`, `helpdesk`, `scrum`                                                                                    | `Bug`, `Feature`, `Help desk`, `Scrum` — **normalizzati dall'ETL**                   |
| `TicketPriority`                                                              | `low`, `medium`, `high`                                                                                                  | `1`, `2`, `3`                                                                        |
| `UserRole`                                                                    | `admin`, `developer`, `manager`, `customer`, `fundraising`                                                               | `editor` **eliminato** (D14): l'ETL converte gli utenti `editor` in permessi (§11.5) |
| `Permission`                                                                  | catalogo in §9.3                                                                                                         | nuovo: nel v1 le capacità sono cablate nei ruoli                                     |
| `DocumentationCategory`                                                       | `internal`, `customer`                                                                                                   | identici                                                                             |
| `ActivityReportOwnerKind`                                                     | `user`, `organization`                                                                                                   | `customer`, `organization`                                                           |
| `ActivityReportPeriodType`                                                    | `monthly`, `annual`                                                                                                      | identici                                                                             |
| `TerritorialScope`                                                            | `cooperation`, `european`, `national`, `regional`, `territorial`, `municipalities`                                       | identici (nel v1 array statico nel modello, **non** un enum)                         |
| `FundraisingProjectStatus`                                                    | `draft`, `submitted`, `approved`, `rejected`, `completed`                                                                | identici (nel v1 array statico)                                                      |
| `TicketMessageChannel`                                                        | `web`, `email`, `system`                                                                                                 | nuovo                                                                                |
| `TicketMessageVisibility`                                                     | `public`, `internal`                                                                                                     | nuovo                                                                                |
| `TicketLogEvent`                                                              | `created`, `status_changed`, `assigned`, `updated`, `message_posted`, `attachment_added`, `attachment_removed`, `system` | nuovo (nel v1 desumibile solo dal JSON)                                              |
| `EmailDirection`, `EmailStatus`, `EmailAttachmentStatus`, `SuppressionReason` | §7                                                                                                                       | nuovi                                                                                |


Enum del v1 non portati: `EpicStatus`, `QuoteStatus`, `DeadlineStatus`.

**Attenzione all'ETL**: nel v1 `StoryType::Scrum` vale `'Scrum'` ma il codice in un punto confronta con
`'scrum'` minuscolo. La normalizzazione a slug elimina la classe di bug alla radice, ma il mapping deve essere
**case-insensitive e tollerante** agli spazi (`'Help desk'`, `'help desk'`, `'Helpdesk'` → `helpdesk`) e
registrare i valori non riconosciuti nel report di validazione.

---



## 6. Requisiti funzionali



### 6.1 M1 — Ticketing

Il ticket è il cuore del sistema. Questa sezione è la più vincolante del documento.

#### 6.1.1 Attori


| Attore         | Ruolo                                  | Cosa fa                                                                                                          |
| -------------- | -------------------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| Cliente        | `customer`                             | apre ticket (web o email), legge lo stato dei propri ticket, risponde alle richieste di chiarimento, allega file |
| Sviluppatore   | `developer`                            | lavora i ticket assegnati, sposta gli stati, risponde al cliente, allega file                                    |
| Tester         | `developer` con `tester_id` sul ticket | verifica i ticket in `testing`                                                                                   |
| Manager        | `manager`                              | assegna, ripianifica, monitora, vede tutto                                                                       |
| Amministratore | `admin`                                | tutto, incluso impersonation e configurazione                                                                    |




#### 6.1.2 Stati

I 12 stati sono in §5.3. Semantica:

- **Iniziali**: `new` (creato, non assegnato), `backlog` (accodato per il futuro)
- **Lavorazione**: `assigned` (assegnato, non iniziato), `todo` (pronto), `progress` (in lavorazione ora),
`testing` (in verifica dal tester)
- **Completamento**: `tested` (verificato ok), `released` (rilasciato in produzione), `done` (chiuso)
- **Blocco**: `waiting` (attesa di terzi, richiede `waiting_reason`), `problem` (blocco tecnico, richiede
`problem_reason`)
- **Finale negativo**: `rejected`



#### 6.1.3 Macchina a stati — definizione dichiarativa

Implementa una `TicketStateMachine` con una tabella di transizioni. Ogni transizione ha: `from`, `to`,
`roles` (chi può eseguirla), `guards` (precondizioni), `effects` (side effect).

**Percorso principale**

```
new → assigned → todo → progress → testing → tested → released → done
```

**Percorso senza testing**

```
new → assigned → todo → progress → released → done
```

**Transizioni ammesse (tabella completa)**


| Da                                               | A                 | Chi                                              | Guard                         | Effetti                                                                                                                                                                                                                                                   |
| ------------------------------------------------ | ----------------- | ------------------------------------------------ | ----------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `new`                                            | `assigned`        | admin, manager, developer                        | `assignee_id` valorizzato     | —                                                                                                                                                                                                                                                         |
| `new`                                            | `backlog`         | admin, manager, developer                        | —                             | —                                                                                                                                                                                                                                                         |
| `new`                                            | `rejected`        | admin, manager                                   | —                             | notifica richiedente                                                                                                                                                                                                                                      |
| `backlog`                                        | `assigned`        | admin, manager, developer                        | `assignee_id` valorizzato     | —                                                                                                                                                                                                                                                         |
| `backlog`                                        | `todo`            | admin, manager, developer                        | `assignee_id` valorizzato     | —                                                                                                                                                                                                                                                         |
| `assigned`                                       | `todo`            | admin, manager, assegnatario                     | —                             | —                                                                                                                                                                                                                                                         |
| `todo`                                           | `progress`        | admin, manager, assegnatario                     | —                             | **demote**: gli altri ticket in `progress` dello stesso assegnatario passano a `todo` (§6.1.4)                                                                                                                                                            |
| `progress`                                       | `testing`         | admin, manager, assegnatario                     | `tester_id` valorizzato       | notifica tester                                                                                                                                                                                                                                           |
| `progress`                                       | `released`        | admin, manager, assegnatario                     | —                             | `released_at = now()`                                                                                                                                                                                                                                     |
| `progress`                                       | `todo`            | admin, manager, assegnatario                     | —                             | —                                                                                                                                                                                                                                                         |
| `testing`                                        | `tested`          | admin, manager, tester                           | —                             | notifica assegnatario                                                                                                                                                                                                                                     |
| `testing`                                        | `todo`            | admin, manager, tester                           | —                             | notifica assegnatario (test fallito)                                                                                                                                                                                                                      |
| `testing`                                        | `rejected`        | admin, manager, tester                           | —                             | notifica assegnatario + richiedente                                                                                                                                                                                                                       |
| `tested`                                         | `released`        | admin, manager, assegnatario                     | —                             | `released_at = now()`                                                                                                                                                                                                                                     |
| `released`                                       | `done`            | admin, manager, assegnatario, **sistema**        | —                             | `done_at` (§6.1.5)                                                                                                                                                                                                                                        |
| `new`, `backlog`, `assigned`, `todo`, `progress` | `waiting`         | admin, manager, assegnatario                     | `waiting_reason` non vuoto    | salva `previous_status`; notifica richiedente                                                                                                                                                                                                             |
| `new`, `backlog`, `assigned`, `todo`, `progress` | `problem`         | admin, manager, assegnatario                     | `problem_reason` non vuoto    | salva `previous_status`; notifica manager                                                                                                                                                                                                                 |
| `waiting`                                        | `previous_status` | admin, manager, assegnatario, **sistema**        | `previous_status` valorizzato | mantiene `waiting_reason`; annota il ripristino nella conversazione                                                                                                                                                                                       |
| `problem`                                        | `previous_status` | admin, manager, assegnatario                     | `previous_status` valorizzato | mantiene `problem_reason`                                                                                                                                                                                                                                 |
| *qualsiasi altro*                                | `rejected`        | admin, manager, **tester se** `status = testing` | —                             | notifica richiedente. Questa riga è la catch-all che copre gli stati non elencati sopra: le righe specifiche `new → rejected` e `testing → rejected` **prevalgono** su di essa (è il tester, non solo admin/manager, a poter rifiutare un ticket in test) |
| *qualsiasi*                                      | `done`            | **solo sistema**                                 | `type = scrum`                | transizione riservata al comando `tickets:close-scrum` (T5). Non disponibile in UI                                                                                                                                                                        |


Note sulla colonna "Chi":

- "assegnatario" = `assignee_id = auth()`, "tester" = `tester_id = auth()`, coerentemente con §9.5. Chi ha
`ticket.transition.any` non è soggetto al vincolo di rapporto con il ticket.
- Per le transizioni che partono da `new` o `backlog` — dove il ticket non è ancora assegnato — un developer
può eseguirle solo contestualmente all'**auto-assegnazione** (l'action valorizza `assignee_id` con l'utente
corrente). Rendi la regola esplicita nel guard, non implicita.

**Vantaggio dello schema v2**: `previous_status` è una **colonna**, e `status_changed_at` fornisce il timestamp
di ingresso nello stato. Nel v1 entrambi erano ricostruiti a posteriori con query sul JSON dei log, con
fallback su `new` quando la ricostruzione falliva. Ogni transizione aggiorna:
`status`, `previous_status` (solo entrando in `waiting`/`problem`), `status_changed_at`, e scrive un
`ticket_log` con `from_status`/`to_status`.

#### 6.1.4 Regola "un solo ticket in lavorazione per assegnatario"

Quando un ticket passa a `progress`, tutti gli altri ticket dello stesso `assignee_id` in `progress` passano a
`todo`. In v2:

- è un **effetto della transizione** `todo → progress`, eseguito dall'action
- ogni demozione produce il proprio `ticket_log` (così il calcolo delle ore resta corretto)
- l'operazione è in **transazione** con il cambio di stato principale



#### 6.1.5 Transizioni automatiche


| #   | Quando                                            | Cosa                                                                                                                | Note per v2                                                                                                                                                                                                                                |
| --- | ------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| T1  | Assegnazione di un assegnatario a un ticket `new` | `new → assigned`                                                                                                    | effetto dell'action `AssignTicket`, non un hook                                                                                                                                                                                            |
| T2  | Cambio di stato manuale di un ticket `new`        | `assignee_id` azzerato                                                                                              | **da confermare** (Q4): è controintuitivo e non documentato altrove nel v1                                                                                                                                                                 |
| T3  | Ogni giorno alle 18:00                            | tutti i ticket `progress` → `todo`                                                                                  | `tickets:progress-to-todo`                                                                                                                                                                                                                 |
| T4  | Ogni giorno alle 07:45                            | ticket `released` da ≥ 3 **giorni lavorativi** → `done`                                                             | `tickets:auto-close-released`. Nel v1 il log viene creato solo se si riesce a dedurre un utente; in v2 il fallback è **sempre** l'utente di sistema                                                                                        |
| T5  | Ogni giorno alle 16:00                            | ticket di tipo `scrum` creati/aggiornati oggi → `done`                                                              | `tickets:close-scrum`. Poiché parte da **qualunque** stato, richiede una transizione dichiarata dedicata (`* → done`, attore *sistema*, guard `type = scrum`): vedi l'ultima riga della tabella di §6.1.3. Senza quella riga violerebbe A2 |
| T6  | Ogni giorno                                       | ticket `waiting` da ≥ N giorni (default 7, calendariali) → `previous_status`                                        | `tickets:restore-waiting`. Il ripristino va annotato nella **conversazione**, non nella `description` (il v1 scrive nella description: sbagliato)                                                                                          |
| T7  | Il richiedente aggiunge un messaggio              | se lo stato è `waiting`, torna a `previous_status`; altrimenti se il ticket è in attesa del cliente, passa a `todo` | **semplifica la regola v1**, la cui condizione ("chi modifica non è l'assegnatario E l'assegnatario ha ruolo customer") è quasi sempre falsa ed è probabilmente un bug. Vedi Q14                                                           |


**Tutte** le transizioni automatiche devono: scrivere un `ticket_log` con `is_system = true`, essere attribuite
all'utente di sistema, essere idempotenti, loggare un riepilogo strutturato.

#### 6.1.6 Gerarchia padre/figlio

- Un ticket può avere un padre e N figli. **Profondità massima 1**: un ticket figlio non può avere figli.
- La sorgente di verità è **solo** `tickets.parent_id`. La tabella pivot `story_story` del v1 non esiste in v2:
l'ETL la usa unicamente per riconciliare eventuali righe non riflesse nella colonna (§11.4) e segnala i
conflitti nel report.
- Quando cambia lo stato del padre, lo stato dei figli **non** viene propagato automaticamente. Nel v1 sì, in
cascata. In v2 c'è un'azione esplicita "applica anche ai ticket figli". Vedi Q5.



#### 6.1.7 Conversazione (D9)

La conversazione è una **timeline di** `ticket_messages`, non una stringa HTML.

- Ogni messaggio ha autore (utente o email non riconosciuta), canale (`web`, `email`, `system`), corpo HTML
**sanitizzato** + versione testuale, timestamp, allegati.
- Un messaggio inserito da UI viene anche **inviato per email** ai destinatari: il record `ticket_messages` è
collegato al record `email_messages` di trasporto (§7).
- Un'email in arrivo diventa un `ticket_message` con `channel = email`.
- I messaggi importati dal v1 hanno `is_legacy_import = true` e vanno resi visivamente distinguibili (l'ordine
e l'attribuzione possono essere approssimativi: §11.5).
- **Sanitizzazione obbligatoria** con allowlist. Mai `{!! !!}` su contenuto di provenienza esterna: nel v1 il
corpo delle email finiva non sanitizzato nel database e veniva stampato così com'è (XSS stored).
- `visibility = internal` esiste nello schema ma **non viene esposta in questa release**: è un punto di
estensione (§15.2).

**Destinatari di un messaggio**: partecipanti + richiedente + assegnatario + tester, **deduplicati** e
**escluso l'autore**. Chi scrive viene aggiunto ai partecipanti.

Nota di realismo: nel v1 la tabella `story_participants` è **di fatto vuota**, perché il metodo che dovrebbe
popolarla è `protected`, mai invocato e logicamente rotto. Quindi "i partecipanti ricevono i messaggi" in v2 è
**una feature nuova**, non parità funzionale — nel v1 i destinatari reali erano solo richiedente + assegnatario

- tester. Verificalo sul dump e riportalo nel report di importazione.



#### 6.1.8 Allegati

- Gli allegati appartengono ai **messaggi** (`TicketMessage`, collection `attachments`).
- L'ETL attacca i media del v1 (che stanno sulla story) al messaggio legacy corrispondente, o a un messaggio
di sistema dedicato quando non è attribuibile (§11.5).
- Tipi ammessi e dimensione massima: configurabili, default in §17.2. **Una sola lista** condivisa tra UI e
inbound email: nel v1 ce ne sono due divergenti, e quella usata dall'inbound è più ristretta (scarta gif,
webp, heic, svg, bmp, tiff) e contiene il typo `applicationvnd.ms-powerpoint`.
- Ogni caricamento/rimozione produce un `ticket_log`.
- **Disco privato** + rotta di download autorizzata dalla policy del ticket, oppure URL firmate a scadenza. Nel
v1 gli allegati sono su disco pubblico: chiunque conosca l'URL scarica il file di un altro cliente (Q10).



#### 6.1.9 Campi e comportamenti in UI


| Campo                                          | Comportamento                                                                                                               |
| ---------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| `status`                                       | **read-only nel form**. Si cambia solo con azioni di transizione esplicite. Badge colorato con icona                        |
| `waiting_reason` / `problem_reason`            | visibili e obbligatori solo quando la transizione di destinazione li richiede                                               |
| `estimated_hours` / `worked_minutes`           | visibili solo a `admin`, `manager`, `developer`. Le ore si mostrano in formato leggibile (`3h 20m`), non in minuti grezzi   |
| `type`, `priority`, `assignee_id`, `tester_id` | nascosti ai clienti                                                                                                         |
| `title`                                        | read-only per i clienti dopo la creazione                                                                                   |
| `description`                                  | interna: **non visibile ai clienti**                                                                                        |
| Conversazione                                  | timeline dei `ticket_messages` visibili (`public`) a tutti gli attori del ticket                                            |
| `staging_url` / `production_url`               | link cliccabili, solo staff                                                                                                 |
| Storico                                        | timeline dei `ticket_logs` con diff leggibile; le transizioni di stato si leggono direttamente da `from_status`/`to_status` |


Il tempo lavorato e le date (creazione, ultimo aggiornamento, `released_at`, `done_at`, `status_changed_at`)
vanno presentati in un blocco riepilogativo compatto.

### 6.2 M2 — Log e time tracking



#### 6.2.1 `ticket_logs`

Contratto: un log per **evento di dominio**, con `event` tipizzato e i cambi di stato in **colonne**
(`from_status`, `to_status`), non nel JSON. `changes` contiene il diff dei campi per gli eventi `updated`.

Regole:

- Il corpo di `description` **non** viene salvato per valore: si registra il marker `"changed"` (comportamento
v1 da mantenere, per non gonfiare la tabella).
- Ogni log ha `user_id` valorizzato: se non c'è utente autenticato si usa l'utente di sistema
(`SYSTEM_USER_EMAIL`). **Mai** `user_id = 1` hard-coded come fa il v1.
- Le mutazioni di dominio **non** usano mai `saveQuietly()`.
- Il payload di `changes` è un **DTO tipizzato** serializzato, non un array libero.
- Le visualizzazioni **non** finiscono qui: hanno la loro tabella (`ticket_views`).



#### 6.2.2 Calcolo delle ore lavorate

Le ore sono **derivate**, non inserite. Algoritmo v1 da riprodurre, con lo schema v2 che lo semplifica
drasticamente (bastano `from_status`/`to_status` e `occurred_at`, senza query sul JSON):

- Si considerano gli intervalli in cui il ticket è stato in `progress`, cioè tra un log con
`to_status = 'progress'` e il successivo log con `from_status = 'progress'`.
- Si contano solo **lunedì–venerdì** e solo le ore **9:00–17:59** (nel v1: `hour > 8 && hour < 18`, cioè i
decadi fuori finestra vengono **scartati**).
- Granularità 10 minuti.
- Il risultato va in `tickets.worked_minutes` (interi).

`ticket_work_logs` aggrega per **(giorno, utente, ticket)**. Attenzione: nel v1 la logica dell'aggregato è
**simile ma non identica** a quella del totale sul ticket, e le due differenze vanno riprodotte
consapevolmente o unificate con una decisione dichiarata:

1. l'aggregato giornaliero **clampa** l'intervallo a `[09:00, 18:00]` invece di scartare i decadi fuori
  finestra;
2. i 30 minuti **non sono un forfait, sono un tetto** (`min($minutes, 30)`) e si applicano **solo** ai log
  privi di cambio di stato; i log con cambio di stato diverso da `progress` valgono **0**.

Requisiti v2:

- Estrai l'algoritmo in un **service puro e testabile** (`WorkedTimeCalculator`), con finestra oraria, giorni
lavorativi e granularità presi da configurazione — non hard-coded.
- **Test unitari con casi noti**: ticket a cavallo della mezzanotte, del weekend, riaperto dopo settimane,
con più assegnatari, intervallo aperto (in `progress` adesso), idempotenza.
- Comando `timetracking:recalculate {--from=} {--to=} {--ticket=}` per il ribuild massivo.
- Il calcolo avviene in **coda**, come listener di `TicketStatusChanged`, con debounce per ticket.
- Un comando schedulato consolida l'aggregato giornaliero (§10.2): nel v1 il job esiste ma **non ha alcuna
cadenza**, quindi la tabella si popola solo in modo opportunistico.
- Il tetto dei 30 minuti è configurabile (`TIMETRACKING_NON_STATUS_CHANGE_CAP_MINUTES`, §17.1).
- **Decisione richiesta**: riprodurre le due politiche divergenti del v1 o unificarle su una sola.
L'unificazione **cambia i numeri storici** rispetto al v1, quindi è una scelta del committente — vedi **Q15**.
Qualunque sia la scelta, documentala e misurane l'impatto nel report di importazione (§11.7).



#### 6.2.3 Tracciamento visualizzazioni

Un record `ticket_views` per **(ticket, utente, giorno)**; `last_viewed_at` aggiornato al massimo ogni 30
minuti, `view_count` incrementato. È la semantica del v1 (dove il record era al massimo uno al giorno e i 30
minuti governavano solo il `touch()`), ma su una tabella dedicata invece che mescolata ai log.

Serve a determinare se un ticket in attesa ha avuto attività recente (§7.5.2 E7). In v2 la scrittura è un
**hook esplicito sulla pagina di dettaglio**, non un middleware che fa pattern matching sulle URL.

### 6.3 M3 — Tag / commesse

Il tag è il contenitore di commessa del sistema.

- `name`, `slug`, `description`, `estimated_hours`, collegamento opzionale a una pagina di documentazione.
- `worked_minutes` **totali**: somma di `tickets.worked_minutes` dei ticket collegati.
- `sal`: `worked_hours / estimated_hours * 100`. Nel v1 esiste sia come accessor sia come metodo duplicato:
in v2 **una sola implementazione**.
- `isClosed()`: nessun ticket collegato in uno stato diverso da `released`/`done`.
- Ticket ↔ Tag: N-N via `ticket_tag`.
- Azione: **crea un tag da un ticket** (`estimated_hours` del ticket → `estimated_hours` del tag).
- Filtri: ticket senza tag, ticket con più di un tag, tag per trimestre (dal nome).

Vista richiesta: elenco tag con stima, ore lavorate, SAL con barra di avanzamento, numero di ticket
aperti/chiusi, stato chiuso/aperto.

### 6.4 M4 — Documentation



#### 6.4.1 Modello

`title`, `slug`, `body` (rich text, obbligatorio), `category` (`internal` | `customer`), PDF generato.
Media: `documents` e `images`.

#### 6.4.2 Comportamenti

- Alla creazione, il sistema crea automaticamente un `Tag` denominato `"Documentation: <titolo>"` collegato
alla pagina; alla rinomina, il tag viene rinominato. In v2 è un **listener di evento**, non un hook
`booted()`.
- Le pagine `internal` sono visibili solo allo staff; le `customer` anche ai clienti.
- Ricerca full-text su titolo e corpo.



#### 6.4.3 Generazione PDF

- Rigenerato in coda alla creazione e a ogni modifica di titolo o corpo.
- Header con logo (`PDF_LOGO_PATH`), footer con i dati societari (`PDF_FOOTER`).
- File su storage privato, `pdf_path` + `pdf_generated_at`; download autorizzato dalla policy.
- Comando `documentation:regenerate-pdfs` per la rigenerazione **batch**, necessario dopo ogni cambio di
layout, logo o footer.
- **Scelta del renderer**: `barryvdh/laravel-dompdf` è più semplice e già noto al team, ma il v1 ha un
documento di troubleshooting dedicato su problemi di rendering. Valuta `spatie/laravel-pdf` (Chromium
headless), che rende CSS moderno correttamente e permette di riusare gli stessi stili del design. **Decidi
tu, motiva la scelta nel README**, verifica le dipendenze del container.



### 6.5 M5 — Activity Report e Organizations



#### 6.5.1 Scopo

Rendicontare al cliente (o all'organizzazione a cui appartiene) l'attività svolta in un periodo, come PDF.

#### 6.5.2 Logica

- Owner: un **utente** oppure una **organizzazione** (`owner_kind` + la FK corrispondente).
- Periodo: **mensile** (`year` + `month`) o **annuale** (`year`).
- Unicità: un solo report per (owner, tipo, periodo).
- Derivati: data di inizio e fine del periodo, nome dell'owner, etichetta del periodo (`"2026"`,
`"Febbraio 2026"`).

**Sincronizzazione dei ticket** (`syncTickets()`):

- ticket con `done_at` nel periodo
- owner utente: `requester_id = owner_user_id`
- owner organizzazione: richiedente appartenente all'organizzazione
- `sync()` sui risultati; se manca l'owner, `detach()` totale

In v1 è un Observer su `created` e su `updated` di 6 campi. In v2 è un **service** invocato esplicitamente
dall'action e dal comando di generazione, **idempotente**.

⚠️ Dipendenza da non dimenticare: la selezione avviene per `done_at`, e sul dump v1 molti ticket storici hanno
`done_at` a null. L'ETL deve ricostruire le date dai log (§11.5) **prima** di rigenerare i report, altrimenti i
ticket spariscono dalla rendicontazione.

#### 6.5.3 PDF

- Generazione in coda, lingua dall'owner (`users.locale` o `organizations.locale`), memorizzata nel report.
- Nome file basato su `PLATFORM_ACRONYM`, owner e periodo.
- Contenuto: intestazione con periodo e owner, elenco dei ticket con titolo, tipo, date, ore, e totali.
- Alla cancellazione del report il PDF viene rimosso.
- Comando `reports:generate-monthly` schedulato il primo del mese alle 12:00 per il mese precedente
(feature flag).



#### 6.5.4 Organizations

Entità minimale: nome, lingua, utenti associati (N-N). Aggrega più utenti cliente sotto un unico ente per la
rendicontazione. In UI: gestione dei membri e vista dei report dell'ente.

### 6.6 M6 — Fundraising

Visibile solo ai ruoli `admin` e `fundraising`.

#### 6.6.1 Opportunità (bandi)

Anagrafica: nome, URL ufficiale, dotazione del fondo, **scadenza** (obbligatoria), nome programma, ente
finanziatore, quota di cofinanziamento, contributo massimo, ambito territoriale, requisiti del beneficiario,
requisiti del capofila, creatore, responsabile.

Comportamenti:

- `isExpired()`, scope `active` (`deadline >= today`), `expired`
- vista separata per le opportunità scadute ("archivio")
- filtri: ambito territoriale, cofinanziamento, scaduto/attivo
- azioni: **crea un'opportunità da JSON** (import strutturato di un bando), **crea un progetto**
dall'opportunità, **crea un ticket** dall'opportunità



#### 6.6.2 Griglia di valutazione

Scheda di scoring su 5 blocchi. Nello schema v2 i punteggi sono **righe** in
`fundraising_evaluation_scores` (una per criterio), e il **catalogo dei criteri** vive in configurazione.


| Blocco                                     | `criterion_key`                                                                                                                                                | Range | Etichetta                             |
| ------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----- | ------------------------------------- |
| **Criteri principali** (con note testuali) | `criterion_a`                                                                                                                                                  | 0–5   | coerenza e rilevanza                  |
|                                            | `criterion_b`                                                                                                                                                  | 0–5   | qualità dell'idea e fattibilità       |
|                                            | `criterion_c`                                                                                                                                                  | 0–5   | impatto su soci, territorio, comunità |
|                                            | `criterion_d`                                                                                                                                                  | 0–5   | valore aggiunto e replicabilità       |
|                                            | `criterion_e`                                                                                                                                                  | 0–5   | partenariato e capacità operativa     |
|                                            | `criterion_f`                                                                                                                                                  | 0–5   | sostenibilità economica               |
| **Requisiti base**                         | `base_coerenza_bando`, `base_capofila_idoneo`, `base_partner_minimi`, `base_cofinanziamento`, `base_tempistiche`                                               | 0–1   | requisiti di ammissibilità            |
| **Qualitativi**                            | `qual_coerenza_cai`, `qual_imp_ambientale`, `qual_imp_sociale`, `qual_imp_economico`, `qual_obiettivi_chiari`, `qual_solidita_azioni`, `qual_capacita_partner` | 0–5   |                                       |
| **Premiali**                               | `prem_innovazione`, `prem_replicabilita`, `prem_comunita`, `prem_sostenibilita`                                                                                | 0–3   |                                       |
| **Rischi**                                 | `risk_tecnici`                                                                                                                                                 | 0–3   |                                       |
|                                            | `risk_finanziari`                                                                                                                                              | −3..3 |                                       |
|                                            | `risk_organizzativi`                                                                                                                                           | −2..2 |                                       |
|                                            | `risk_logistici`                                                                                                                                               | −2..2 |                                       |


Calcolo dei totali:

- `evaluation_positive_total` = somma di tutti i punteggi ≥ 0
- `evaluation_negative_total` = somma dei **valori assoluti** dei punteggi < 0 (solo i rischi possono essere
negativi)
- `evaluation_total` = positivo − negativo

Requisiti v2:

- I range sono **validati** dall'applicazione, derivati dal catalogo (nel v1 sono solo commenti SQL).
- Il calcolo è un **service puro con test unitari**, invocato dall'action di salvataggio — non un hook
`saving()` che gira a ogni salvataggio.
- `evaluated_by`/`evaluated_at` si valorizzano quando viene inserito il primo punteggio.
- La UI raggruppa i blocchi in sezioni/tab, con il totale calcolato **in tempo reale** e una visualizzazione
sintetica del punteggio.
- Aggiungere un criterio in futuro = una voce nel catalogo, **nessuna migrazione**. È il beneficio principale
della normalizzazione.



#### 6.6.3 Progetti

Titolo, opportunità di riferimento, capofila, creatore, responsabile, descrizione, stato
(`draft` → `submitted` → `approved`/`rejected` → `completed`), importo richiesto, importo approvato, data di
presentazione, data di decisione, **partner** (N-N con utenti).

Scope: per stato, per capofila, per partner, "coinvolti" (capofila OR partner OR responsabile OR creatore).
Un ticket può essere collegato a un progetto (`tickets.fundraising_project_id`).

Nota: il metodo v1 `partnerCustomers()` fa `whereHas('roles', ...)` su una colonna JSON — query non
eseguibile. In v2 i ruoli sono un pivot, quindi il filtro per ruolo è una join normale.

#### 6.6.4 Vista cliente

Il cliente vede, in sola lettura: le opportunità (elenco e dettaglio) e i progetti in cui è **coinvolto**
(capofila o partner) — il dettaglio è accessibile solo se coinvolto.

### 6.7 M7 — Utenti, ruoli e portale cliente



#### 6.7.1 Ruoli e permessi

Un utente ha **più ruoli** e, in aggiunta, può ricevere **permessi diretti**. I 5 ruoli: `admin`, `developer`,
`manager`, `customer`, `fundraising` (D14: `editor` eliminato).

I ruoli sono **comportamentali**, non solo bundle di permessi: determinano la navigazione (§8.4), la landing
(§6.7.2), la visibilità dei campi (§6.1.9) e lo scoping delle query (§8.5). I permessi sono **capacità
granulari** concedibili a un ruolo o a una singola persona.

Il modello completo, il catalogo dei permessi e la matrice sono in **§9**.

**Gestione in UI** (solo admin):

- assegnare e revocare **ruoli** a un utente
- concedere e revocare **permessi diretti** a un utente, in aggiunta a quelli dei suoi ruoli
- vedere, per un utente, i permessi **effettivi** con l'indicazione della provenienza (da quale ruolo, o
diretto)
- vedere, per un ruolo, l'elenco dei permessi che comporta (in **sola lettura**: si cambia nel seeder)

Non è possibile creare ruoli o permessi da interfaccia: §9.2 spiega perché.

#### 6.7.2 Accesso e landing

- Autenticazione via Filament, con **MFA opzionale** (nativa in Filament 4), abilitabile per ruolo.
- **Impersonation** riservata agli admin, con banner sempre visibile e log dell'azione. Filament 4 non la ha
nativa: usa un pacchetto consolidato e mantieni i metodi di autorizzazione (`canImpersonate()`,
`canBeImpersonated()`) sul modello `User`.
- Landing per ruolo:
  - `customer` → dashboard cliente
  - `admin`, `manager`, `developer` → vista di lavoro (§8.6)
  - `fundraising` → elenco opportunità



#### 6.7.3 Portale cliente

Esperienza **ristretta e semplificata** dentro lo stesso pannello:

- Dashboard con: propri ticket aperti, ticket che richiedono una risposta, documentazione `customer`, link
Drive e budget, propri report di attività, progetti fundraising in cui è coinvolto, e — se confermata (Q17) —
il link alla chat di supporto (`help_desk_chat_url`).
- Apertura di un nuovo ticket con allegati.
- Elenco dei propri ticket attivi e archivio (`done`/`rejected`).
- Risposta ai ticket.
- **Non vede**: `description` interna, tipo, priorità, assegnatario, tester, ore, messaggi `internal`, ticket
di altri.



#### 6.7.4 Preferenze utente

- Lingua delle comunicazioni (`users.locale`).
- **Preferenze di notifica**: quali comunicazioni ricevere, per tipo e canale
(`notification_preferences`). Nel v1 non esiste alcun controllo e i clienti ricevono email a ogni evento.
Il **rispetto** delle preferenze in fase di invio è un requisito della Fase 3 (§7.5.1); la **schermata** di
gestione è nella Fase 6.



#### 6.7.5 Disattivazione di un utente

Un utente non si cancella: si **disattiva** (`deactivated_at`). Un utente disattivato:

- non può accedere e non riceve comunicazioni;
- resta visibile nei dati storici come richiedente, assegnatario, tester, autore di messaggi e di log;
- non è selezionabile nei campi di assegnazione, né come partner fundraising, né nei destinatari;
- resta membro delle organizzazioni, così che i report storici dell'ente restino corretti.

L'ETL importa tutti gli utenti del v1 come attivi (il v1 non ha il concetto di disattivazione); la
disattivazione delle utenze non più valide è un'attività manuale post-cutover.

---



## 7. M8 — Sottosistema email (riscrittura completa)

È la parte del sistema con il debito più alto. Non riusare nulla del v1.

### 7.1 Perché: cosa non funziona oggi


| #   | Problema                                                                                                                                                                                                                                         | Effetto                                                                                                                                       |
| --- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | **Nessun threading.** Nessun `Message-ID`, `In-Reply-To`, `References`, nessun token nel subject, nessun reply-address dedicato                                                                                                                  | Ogni risposta di un cliente apre un **ticket duplicato**                                                                                      |
| 2   | **Nessuna idempotenza.** Il ticket viene salvato *prima* dell'invio della mail di conferma; se l'SMTP fallisce, l'eccezione impedisce di marcare la mail come letta                                                                              | Alla successiva esecuzione (5 min) si crea un **secondo ticket identico**, e poi un terzo, indefinitamente                                    |
| 3   | **Lo stato di elaborazione vive sul server IMAP** (flag `Seen`)                                                                                                                                                                                  | Se un umano apre la casella con un client mail, quelle email **non verranno mai elaborate**                                                   |
| 4   | **Nessuna persistenza su DB.** Zero tabelle email nelle 105 migrazioni                                                                                                                                                                           | Impossibile rispondere a "questa email è stata elaborata? questa notifica è stata inviata?"                                                   |
| 5   | **Nessun lock.** Job schedulato ogni 5 minuti senza `WithoutOverlapping` né `$timeout`                                                                                                                                                           | Esecuzioni sovrapposte → duplicati                                                                                                            |
| 6   | **Errori invisibili.** `try/catch` senza rethrow                                                                                                                                                                                                 | Il job risulta sempre "riuscito" in Horizon                                                                                                   |
| 7   | **Rischio di mail loop.** Auto-reply incondizionato a mittenti arbitrari, senza controlli su `Auto-Submitted`, `Precedence`, `List-Id`, senza rate limit; fan-out sincrono a *tutti* i developer                                                 | Un bounce da `MAILER-DAEMON` genera un auto-reply al demone di posta + N email ai developer. Lo spam riceve conferma che l'indirizzo è valido |
| 8   | **Parsing distruttivo.** `preg_replace('/---.*?---/s', '', $body)` cancella tutto tra due `---`; nessuna gestione delle mail solo-HTML; nessuna rimozione del testo citato                                                                       | Corpo dei ticket mutilato; il thread cresce accumulando lo storico a ogni risposta                                                            |
| 9   | **XSS stored.** Body non sanitizzato salvato nel DB e stampato con `{!! !!}`                                                                                                                                                                     |                                                                                                                                               |
| 10  | **I developer non vengono avvisati dei ticket via email.** L'hook che invia la notifica è racchiuso in `if (auth()->user())`, mai vero in coda                                                                                                   | Un ticket aperto via email **non avvisa nessuno in azienda**                                                                                  |
| 11  | **Bug nel template di notifica**: `@if ($recipient->role === 'customer')` — attributo inesistente, sempre falso                                                                                                                                  | I clienti ricevono sempre il template dello staff, con un link a cui non hanno accesso e **senza l'indicazione del nuovo stato**              |
| 12  | **Precedenza operatori errata**: `$a && $b && $c || $d`                                                                                                                                                                                          | Email di notifica spurie a ogni salvataggio di ticket in `testing`/`tested`/`done`/`rejected`                                                 |
| 13  | **Invio sincrono.** Nessuna Mailable implementa `ShouldQueue`                                                                                                                                                                                    | Rispondere a un ticket apre N connessioni SMTP dentro la request HTTP                                                                         |
| 14  | **Localizzazione non funzionante.** Testi hard-coded in italiano; i 2 usi di `__()` nelle Mailable puntano a chiavi che non esistono (manca `lang/it.json`); subject del digest in inglese e corpo in italiano; la lingua dell'utente è ignorata |                                                                                                                                               |
| 15  | **Allegati**: nome file non sanitizzato usato come path (collisioni, path traversal), delete fuori da `finally`, nessun limite di dimensione o numero, whitelist MIME con un typo, scarto silenzioso                                             | Perdita di allegati con ticket creato                                                                                                         |
| 16  | **Nessuna gestione bounce/DSN/complaint.** Nessuna soppressione                                                                                                                                                                                  |                                                                                                                                               |
| 17  | `app/Services/EmailService.php` è un **file vuoto** mai referenziato; `SendDigestEmail` è dead code con 4 bug; il comando dei reminder non è schedulato, quindi non parte mai                                                                    |                                                                                                                                               |
| 18  | Hard-coding: account IMAP `'default'`, folder `'INBOX'`, `info@webmapp.it` in un template inviato ai clienti di Montagna Servizi, un indirizzo personale in una rotta, `user_id => 1` per i log di sistema                                       |                                                                                                                                               |
| 19  | Rotte `/mailable` e `/logs` **senza autenticazione**: espongono i dati di un cliente reale e i log applicativi                                                                                                                                   |                                                                                                                                               |
| 20  | `.env.example` non contiene **nessuna** variabile `IMAP_`*                                                                                                                                                                                       | Un nuovo deploy non sa cosa configurare                                                                                                       |




### 7.2 Requisiti architetturali


| #   | Requisito                                                                                                                                                           |
| --- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| R1  | **Ogni email, in ingresso e in uscita, è persistita** in `email_messages` prima di qualunque effetto collaterale. Il DB è la sorgente di verità, non il server IMAP |
| R2  | **Threading reale**: `Message-ID` generato e conservato in uscita; `In-Reply-To`/`References` letti in ingresso; correlazione ticket ↔ thread esplicita             |
| R3  | **Idempotenza**: nessuna email produce due volte lo stesso effetto. Chiavi: `(imap_folder, imap_uid)`, `message_id`, `content_hash`                                 |
| R4  | **Transazionalità**: creazione del ticket/messaggio e registrazione dell'email nella stessa transazione. Le notifiche partono **dopo il commit**, in coda           |
| R5  | **Tutto asincrono**: ogni Mailable è `ShouldQueue`, con `$tries`, `$backoff`, `retryUntil`. Nessun SMTP in una request                                              |
| R6  | **Osservabilità**: log strutturato con correlazione `message_id ↔ ticket_id ↔ user_id`, e una UI di amministrazione (§7.7)                                          |
| R7  | **Protezione anti-loop**: controlli obbligatori prima di ogni auto-reply (§7.3.4)                                                                                   |
| R8  | **Localizzazione reale**: ogni comunicazione nella lingua del destinatario (§7.6)                                                                                   |
| R9  | **Sicurezza**: sanitizzazione HTML in ingresso, nomi file sanitizzati, limiti di dimensione, nessuna rotta di debug non autenticata                                 |
| R10 | **Testabilità**: fixture `.eml` reali in `tests/Fixtures/emails/` e test di integrazione sul parser. Il v1 ha zero test sull'inbound                                |




### 7.3 Inbound



#### 7.3.1 Pipeline

```
Fetch (IMAP o webhook)
  → Store raw (.eml su storage + record email_messages status=received)
  → Parse (headers, body, allegati)
  → Classify (bounce? autoreply? bulk? loop?)
  → Identify sender (User)
  → Resolve thread (risposta a ticket esistente? nuovo ticket?)
  → Apply (crea ticket | aggiungi ticket_message | quarantena)
  → Import attachments
  → Move IMAP message (Processed | Errors | Quarantine)
  → Notify (in coda, dopo commit)
```

Ogni step aggiorna lo `status` del record ed è **ripetibile senza effetti duplicati**.

#### 7.3.2 Stati di `email_messages`

Inbound: `received` → `parsed` → `classified` → `applied` | `quarantined` | `discarded` | `failed`
Outbound: `queued` → `sent` | `failed` | `bounced` | `suppressed`

#### 7.3.3 Fetch

- Configurazione dell'account via env, **tutte** le variabili documentate in `.env.example`.
- Supporto **OAuth2** per Google/Microsoft oltre a user+password: la casella attuale è Gmail e le app password
sono in via di dismissione. Se OAuth2 è troppo per questa release, implementa password ma **isola il transport**
dietro un'interfaccia, così da poter aggiungere OAuth2 senza toccare la pipeline.
- Folder configurabili: `INBOX` (sorgente), `Processed`, `Errors`, `Quarantine`. I messaggi vengono
**spostati**, non solo marcati.
- Query con `limit` **obbligatorio** per esecuzione (default 50) e `since` configurabile. Il v1 scarica tutti
gli unseen con `fetch_body = true`: rischio OOM.
- Il messaggio grezzo va **sempre** archiviato come `.eml` su storage prima del parsing: è la rete di sicurezza
per riprocessare senza IMAP.
- `WithoutOverlapping` + `$timeout` + `$tries` sul job. `finally` per il disconnect.



#### 7.3.4 Classificazione — scarti obbligatori

Prima di qualunque azione, scarta (status `discarded`, motivo registrato, **senza inviare nulla**) i messaggi
con:

- header `Auto-Submitted` diverso da `no`
- `Precedence: bulk` / `list` / `junk`
- presenza di `List-Id` o `List-Unsubscribe`
- `X-Auto-Response-Suppress`
- mittente `MAILER-DAEMON`, `postmaster@`, `no-reply@`, `noreply@`, o vuoto
- `Content-Type: multipart/report; report-type=delivery-status` → è un **DSN**: instradalo alla gestione bounce
(§7.5.5), non al ticketing
- mittente presente in `email_suppressions`
- mittente uguale all'indirizzo della piattaforma (protezione loop diretto)

Rate limit: massimo N auto-reply per indirizzo per finestra (default 3/ora, 10/giorno). Superata la soglia il
messaggio viene elaborato ma **nessun auto-reply** parte, e l'indirizzo va in `email_suppressions` con motivo
`loop_protection` e `expires_at`.

#### 7.3.5 Parsing

- **Subject**: normalizzazione dei prefissi (`Re:`, `RE:`, `R:`, `Fw:`, `Fwd:`, `AW:`, `I:`, `Rif:`), anche
ripetuti e in cascata. Estrazione del token ticket se presente (§7.3.6).
- **Body**: preferisci `text/plain`; se manca, converti l'HTML in testo. **Nessuna regex distruttiva.**
- **Rimozione del testo citato**: algoritmo esplicito e testato per quoted-reply e firme (`On ... wrote:`,
`Il ... ha scritto:`, `>`, `-----Original Message-----`, `--`  come separatore, blocchi Outlook `From:`/`Da:`).
Il testo rimosso resta nel `.eml`, quindi la perdita non è distruttiva.
- **HTML**: `body_html` **sanitizzato** con allowlist; il rendering usa sempre la versione sanitizzata.
- **Mittente**: `From`, con fallback documentato su `Reply-To`/`Sender`. Null-safe.
- **Charset**: gestione esplicita, con fallback e log quando il decoding fallisce.



#### 7.3.6 Identificazione del mittente e del thread

**Mittente**: match **case-insensitive** su `users.email` (lo schema v2 ha l'indice funzionale su
`lower(email)`). Se non trovato: prova il sub-address (`nome+tag@dominio` → `nome@dominio`); **non** inferire
l'utente dal dominio (rischio di attribuzione errata); se non identificato → §7.3.8.

**Thread**, in ordine di priorità:

1. **Token nel reply-address**: le email in uscita usano `ticket+<ulid>@dominio` (VERP / plus-addressing) dove
  l'ulid è quello del `ticket_message`. È il metodo più affidabile e richiede una casella catch-all — vedi Q2.
2. `In-Reply-To` **/** `References` confrontati con i `message_id` in `email_messages`.
3. **Token nel subject**: le email in uscita includono `[#<id ticket>]`; in ingresso si cerca con regex
  ancorata. Funziona perché l'ETL **conserva gli id dei ticket** (§5.1).
4. **Euristica**: stesso mittente + subject normalizzato identico + thread aperto negli ultimi N giorni
  (default 30). Solo come ultima risorsa, registrando che il match è euristico.
5. Nessun match → nuovo ticket.



#### 7.3.7 Applicazione

**Nuovo ticket**: `title` = subject normalizzato, primo `ticket_message` con `channel = email` e il corpo,
`type = helpdesk`, `status = new`, `requester_id` = utente identificato.

**Risposta a ticket esistente**: nuovo `ticket_message` collegato, e transizione T7 (§6.1.5).

In **entrambi** i casi, dopo il commit:

- conferma di ricezione al mittente, con numero di ticket e link al portale
- **notifica allo staff** — requisito che il v1 non soddisfa (problema 10). Il gruppo di destinatari è
configurabile (`MAIL_STAFF_NOTIFICATION_GROUP`), non "tutti i developer" in un `foreach` sincrono



#### 7.3.8 Mittente non riconosciuto

Non è un errore: è un caso d'uso normale (un nuovo referente del cliente scrive per la prima volta).

- Il messaggio va in **quarantena**, non scartato.
- Notifica allo staff con mittente, subject, estratto del corpo, e le azioni **"associa a utente esistente"** /
**"crea nuovo utente e ticket"** dall'amministrazione email (§7.7).
- Auto-reply al mittente **solo se** passa i controlli di §7.3.4, con l'indirizzo di supporto **da
configurazione** — nel v1 il template dice `info@webmapp.it` ai clienti di Montagna Servizi.
- Applicando l'azione dalla quarantena, il messaggio viene **riprocessato** e genera il ticket.



#### 7.3.9 Allegati

- Nome file **sanitizzato** (slug + estensione validata) e path univoco (ULID). Mai il nome originale come
path.
- Limiti da configurazione: dimensione per allegato, totale per messaggio, numero massimo.
- Allegati `inline` (loghi, immagini di firma): **esclusi** per default, con flag per includerli.
- Validazione MIME **reale** (sniffing del contenuto), non solo l'header dichiarato.
- Un allegato scartato produce un record `email_attachments` con `status = rejected_*` e il motivo, **visibile
in UI**. Mai uno scarto silenzioso.
- L'errore su un allegato non deve far fallire l'elaborazione del messaggio.



### 7.4 Scelta tecnica per l'inbound

Opzioni in ordine di preferenza:

1. **Webhook del provider** (Mailgun / Postmark inbound / SES + SNS): elimina il polling, elimina il problema
  del flag `Seen`, fornisce il messaggio già parsato e il `Message-ID`. **Preferibile se il committente può
   configurare un provider** (Q1). Richiede una rotta con verifica di firma e comunque la pipeline di §7.3.
2. **IMAP con** `webklex/php-imap` (libreria standalone, non il wrapper Laravel), dietro un'interfaccia
  `InboundMailTransport`.

Implementa l'interfaccia con entrambe le implementazioni se il costo è basso; altrimenti implementa IMAP e
mantieni l'interfaccia pulita.

### 7.5 Outbound



#### 7.5.1 Requisiti trasversali

- Ogni Mailable è `ShouldQueue`, con retry e backoff.
- Ogni invio produce un record `email_messages` (`direction = outbound`) con `message_id` generato, thread
collegato, `mailable_class`, e `status` aggiornato dagli eventi Laravel (`MessageSending`, `MessageSent`).
- `Reply-To` valorizzato con l'indirizzo di risposta del ticket (§7.3.6) o con l'indirizzo di supporto. Il
`From` può restare un `noreply`, ma il `Reply-To` **deve** essere valido: nel v1 il flusso presuppone che il
cliente scriva a `ticket@` ma nessuna email lo dice.
- Subject con token di ticket (`[#123]`) per il threading di fallback.
- `In-Reply-To`/`References` valorizzati quando l'email fa parte di un thread.
- Nessun invio a indirizzi in `email_suppressions` (status `suppressed`).
- Nessun invio a chi ha disattivato quel tipo di notifica (`notification_preferences`).
- **Layout unico** (§7.5.4).



#### 7.5.2 Catalogo delle comunicazioni


| #   | Comunicazione                         | Trigger                                                                                                   | Destinatari                                                       | Note                                                                                                    |
| --- | ------------------------------------- | --------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| E1  | Conferma di ricezione ticket          | ticket creato via email                                                                                   | mittente                                                          | numero ticket + link al portale                                                                         |
| E2  | Conferma apertura ticket da web       | ticket creato dal cliente in UI                                                                           | richiedente                                                       | **nuovo**: il v1 non la manda                                                                           |
| E3  | Nuovo ticket da cliente               | ticket creato da un cliente (web **o email**)                                                             | gruppo staff configurabile                                        | **corregge il problema 10**                                                                             |
| E4  | Cambio di stato                       | transizione rilevante                                                                                     | destinatario pertinente per ruolo                                 | **il template deve indicare il nuovo stato** e linkare la pagina giusta per il ruolo — vedi problema 11 |
| E5  | Nuovo messaggio sul ticket            | `ticket_message` con `visibility = public`                                                                | partecipanti + richiedente + assegnatario + tester, meno l'autore | i messaggi `internal` non escono mai verso il cliente                                                   |
| E6  | Assegnazione                          | il ticket viene assegnato a un developer / tester                                                         | il nuovo assegnatario                                             | solo se diverso da chi esegue l'azione                                                                  |
| E7  | Reminder ticket in attesa             | ticket `waiting` senza attività rilevante da ≥ 3 giorni lavorativi (da `ticket_views` e `ticket_logs`)    | richiedente                                                       | **da schedulare**: nel v1 il comando esiste ma non è nello scheduler                                    |
| E8  | Digest periodico                      | riepilogo dei ticket con attività nelle 24h                                                               | clienti che lo hanno abilitato                                    | nel v1 è dead code con 4 bug: implementalo correttamente **o eliminalo** (Q3)                           |
| E9  | Mittente non riconosciuto             | email da mittente sconosciuto                                                                             | gruppo staff + (condizionalmente) mittente                        | §7.3.8                                                                                                  |
| E10 | Report di attività disponibile        | PDF generato                                                                                              | owner del report                                                  | **nuovo**, opzionale (Q3)                                                                               |
| E11 | Developer senza ticket in lavorazione | un developer con ticket assegnati non ha nulla in `progress` (v1: ritardo 30 min, solo prima delle 15:30) | il developer                                                      | promemoria interno (Q3)                                                                                 |


Ogni comunicazione ha anche una **notifica in-app** (Filament database notifications) quando il destinatario è
interno.

Assegnazione alle fasi: E1–E7 e E9 in Fase 3; E8, E10, E11 in Fase 6 **se** approvate.

#### 7.5.3 Regole di destinazione

Le regole v1 su "chi riceve cosa" contengono il bug di precedenza operatori (problema 12) e vanno
**ridefinite in modo esplicito e testato**: una tabella (attore che ha eseguito l'azione × transizione →
destinatari), coperta da test.

Principio: **nessuno riceve la notifica di un'azione che ha eseguito lui stesso.**

#### 7.5.4 Template

- **Un solo layout** (`emails/layouts/base.blade.php`) con logo, palette e tipografia **del design** (§8),
responsive.
- Ogni email ha una **versione plain-text** oltre all'HTML.
- Componenti riusabili: intestazione ticket, badge di stato, blocco messaggio, call-to-action, footer con dati
societari.
- Footer con link alle **preferenze di notifica** e, dove pertinente, `List-Unsubscribe`.
- **Zero CSS duplicato** tra template (nel v1 lo stesso blocco `<style>` è copiato in 5 file) e zero HTML
malformato.



#### 7.5.5 Bounce e soppressioni

- I DSN vengono riconosciuti (§7.3.4) e correlati all'email originale via `Message-ID` nel corpo del report.
- **Hard bounce** → `email_suppressions` con `reason = hard_bounce`; l'utente è segnalato in UI come "email non
recapitabile".
- **Soft bounce** → `bounce_count` incrementato, soppressione solo dopo N occorrenze consecutive.
- Le soppressioni sono visibili e **rimuovibili** dall'amministrazione.



### 7.6 Localizzazione

- Lingua di ogni comunicazione = `users.locale` del destinatario (fallback: `organizations.locale`, poi
`APP_LOCALE`).
- **Tutti** i testi, subject compresi, passano da file di lingua. Zero stringhe hard-coded.
- File `lang/it.json` e `lang/en.json` presenti e completi — nel v1 mancano, ed è la ragione per cui i due
`__()` presenti nelle Mailable mostrano la chiave grezza al destinatario.
- Test che verifica che ogni chiave usata esista in tutte le lingue supportate.



### 7.7 Amministrazione email

Sezione visibile ad `admin`:

- **Registro** dei messaggi in ingresso e in uscita, filtrabili per direzione, stato, mittente, destinatario,
ticket, periodo.
- Dettaglio: header, corpo, allegati, ticket collegato, thread, tentativi, errore.
- Azioni: **riprocessa**, **assegna a utente**, **collega a ticket**, **scarta**, **reinvia**.
- **Quarantena** con le azioni di §7.3.8.
- Elenco **soppressioni** con rimozione.
- Metriche essenziali: messaggi elaborati/scartati/falliti nelle 24h, tempo medio di elaborazione, bounce rate.

Questa UI è ciò che rende il sottosistema governabile: è parte del deliverable, non un extra.

---



## 8. M10 — Design system e interfaccia



### 8.1 Import del design — step obbligatorio, da eseguire per primo

Esegui questo prompt **prima** di scrivere qualunque componente di interfaccia:

> Use the claude_design MCP ([https://api.anthropic.com/v1/design/mcp](https://api.anthropic.com/v1/design/mcp), auth via `/design-login`) to import this
> project:
> [https://claude.ai/design/p/b41c13f4-8321-4716-be35-295d0bdd9d1e?file=Piattaforma+Montagna+Servizi.dc.html](https://claude.ai/design/p/b41c13f4-8321-4716-be35-295d0bdd9d1e?file=Piattaforma+Montagna+Servizi.dc.html)
>
> Focus on these files (the whole project is readable):
>
> - `Piattaforma Montagna Servizi.dc.html`
>
> Also read these files the selection imports:
>
> - `assets/montagna-servizi-mark.png`
> - `support.js`
>
> Implement: `Piattaforma Montagna Servizi.dc.html`

Se l'MCP non è disponibile o l'autenticazione fallisce, **fermati e segnalalo**: non procedere inventando un
design.

### 8.2 Deliverable dell'import

1. `docs/design-system.md` — token estratti: palette completa con i nomi semantici del mockup, tipografia
  (famiglie, scale, pesi), spaziature, raggi, ombre, breakpoint, stati interattivi, iconografia.
2. `resources/css/theme.css` — i token come custom properties + il tema Filament (Tailwind v4) derivato
  **da questi token**, non da valori riscritti a mano.
3. `docs/design-inventory.md` — per ogni schermata e componente del mockup: nome, descrizione,
  **in scope in questa release** / **nuova feature (fuori scope)**, resource/pagina Filament corrispondente.
4. `assets/` — logo e mark nei formati necessari (SVG per il pannello, PNG per email e PDF).



### 8.3 Regole di applicazione

- Il design è **vincolante** per palette, tipografia, spaziature, densità, componenti e microcopy. Dove design e
PRD divergono su un dettaglio visivo, **vince il design**; dove divergono su una regola di dominio, **vince il
PRD**.
- Se il design definisce colori di stato per i ticket, quelli **sostituiscono** la palette di §5.3 (i valori nel
DB non cambiano, cambia la resa).
- Il branding è **Montagna Servizi**: nel v1 sono rimasti riferimenti a Webmapp (indirizzi email nei template,
footer PDF di default, logo). Vanno tutti sostituiti e resi configurabili.
- Il design system si applica **anche** alle email (§7.5.4) e ai PDF (§6.4.3): un'unica identità.
- **Non inventare** schermate non presenti nel design. Se una funzionalità in scope non ha una schermata nel
mockup, usa i pattern Filament di default coerenti con i token, e **annotalo** in `docs/design-inventory.md`.



### 8.4 Struttura del pannello

Un **unico pannello Filament** multi-ruolo: la navigazione si adatta ai ruoli dell'utente. Replica il modello
del v1 (un solo Nova con visibilità per ruolo) ed evita la duplicazione delle resource.

Struttura di riferimento — **da riconciliare con il design**, che ha priorità:


| Gruppo              | Voci                                                                                                                            | Ruoli                     |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------- | ------------------------- |
| **Lavoro**          | Vista di lavoro (§8.6), Assegnati a me, Da testare (io tester), In test, In attesa, Problemi, In lavorazione, Backlog, Archivio | admin, manager, developer |
| **Ticket**          | Tutti i ticket, Nuovi, Richieste attive, Tutti i ticket di clienti, Interni                                                     | admin, manager, developer |
| **Commesse**        | Tag / commesse con SAL                                                                                                          | admin, manager, developer |
| **Fundraising**     | Opportunità, Progetti, Archivio opportunità                                                                                     | admin, fundraising        |
| **Documentazione**  | Pagine (internal + customer)                                                                                                    | tutti, contenuto filtrato |
| **Rendicontazione** | Report attività, Organizzazioni                                                                                                 | admin, manager            |
| **Amministrazione** | Utenti (con ruoli e permessi, §6.7.1), Email (§7.7), Report di importazione (§11.7), Horizon, Log                               | admin                     |
| **Area cliente**    | Dashboard, I miei ticket, Nuovo ticket, Archivio, I miei report, Documentazione, Fundraising                                    | customer                  |


Note:

- Le voci "crea nuovo" del v1 (un gruppo di menu dedicato) sono sostituite dalle azioni di creazione delle
resource, salvo indicazione diversa dal design.
- I badge/contatori sulle voci di menu (ticket in attesa, problemi, da testare) sono un requisito: in Filament
si fanno con `getNavigationBadge()`, ma **con cache** — non una query per voce a ogni render.
- La colonna "Ruoli" indica **chi vede la voce nel menu**, ed è legata al ruolo perché la navigazione è un
comportamento di ruolo (§9.2). L'accesso effettivo alla risorsa è comunque governato dai permessi e dalle
policy: Horizon e Log compaiono nel gruppo Amministrazione ma richiedono `horizon.access` / `logs.access`,
che sono concedibili anche a un developer (§9.4).



### 8.5 Da 27 resource ridondanti a una

Nel v1 esistono **15 resource Nova sullo stesso modello** `Story` (11 come sottoclassi di `Story`, 3 di
`CustomerStory`, più la base) e 7 su `Epic`, che differiscono **solo per la query di index** — nel caso di
`Epic` con i campi interamente duplicati in ciascuna. In v2:

- **una** `TicketResource`
- le viste sono **tab della tabella** (`getTabs()` con `modifyQueryUsing`) oppure **pagine** `ListRecords`
**dedicate** quando servono in navigazione con URL propria
- ogni filtro è un **query object** riusabile in `app/Domain/Ticketing/Queries/`, testabile in isolamento

Mappatura delle viste da preservare (con i nomi di colonna v2):


| Vista                     | Query                                                                                                              | Ruoli    |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------ | -------- |
| Richieste attive          | `requester_id NOT NULL AND status NOT IN (done, backlog, rejected)` — **qualunque** richiedente, staff incluso     | staff    |
| Tutti i ticket di clienti | `richiedente ha ruolo customer` — senza filtro di stato                                                            | staff    |
| Nuovi                     | `requester_id NOT NULL AND status = new`                                                                           | staff    |
| In lavorazione            | `status = progress AND requester_id NOT NULL`                                                                      | staff    |
| Assegnati a me            | `assignee_id = auth() AND status NOT IN (new, done)`                                                               | staff    |
| Da testare (io tester)    | `tester_id = auth() AND status = testing`                                                                          | staff    |
| In test                   | richieste attive + `status = testing`                                                                              | staff    |
| In attesa                 | richieste attive + `status = waiting`, **ordinate per** `status_changed_at` **crescente** (le più vecchie in cima) | staff    |
| Problemi                  | richieste attive + `status = problem`                                                                              | staff    |
| Backlog                   | `requester_id NOT NULL AND status = backlog`                                                                       | staff    |
| Archivio                  | `status IN (done, rejected)`                                                                                       | staff    |
| Interni                   | `requester_id NOT NULL AND richiedente senza ruolo customer AND status != done`                                    | staff    |
| I miei ticket (cliente)   | `requester_id = auth() AND status NOT IN (done, rejected)`                                                         | customer |
| Archivio (cliente)        | `requester_id = auth() AND status IN (done, rejected)`                                                             | customer |


**Semplificazione consentita dallo schema v2**: i "giorni in attesa" sono
`now() - status_changed_at`, una colonna ordinabile e indicizzata. Nel v1 richiedevano una subquery sul JSON dei
log con fallback su `updated_at`.

### 8.5.1 Filtri richiesti sui ticket


| Filtro                         | Campo / logica                        |
| ------------------------------ | ------------------------------------- |
| Stato                          | `status` (multi-selezione)            |
| Tipo                           | `type`                                |
| Priorità                       | `priority`                            |
| Assegnatario                   | `assignee_id`                         |
| Tester                         | `tester_id`                           |
| Richiedente                    | `requester_id`                        |
| Organizzazione del richiedente | join su `organization_user`           |
| Tag                            | `ticket_tag.tag_id`                   |
| Senza tag                      | `whereDoesntHave('tags')`             |
| Con più di un tag              | conteggio tag > 1                     |
| Tag per trimestre              | `tags.name` con pattern del trimestre |
| Periodo                        | `created_at` / `done_at` (intervallo) |


Sugli altri domini: filtro per ruolo sugli utenti (una join sulle tabelle Spatie, non più `whereJsonContains`);
anno/mese/tipo owner/tipo periodo sui report; scaduto/cofinanziamento/ambito territoriale sulle opportunità.
Le **Lens** e le **Metrics** Nova non vanno portate: sono superate dalle schermate del design (D5).

### 8.6 Vista di lavoro (ex Kanban)

Schermata principale dello staff. Nel v1 è una dashboard `HtmlCard` con Blade custom. In v2 va rifatta secondo
il design. Requisiti funzionali minimi:

- Colonne per stato, card per ticket con id, titolo, cliente, tag, priorità, tempo trascorso nello stato
(`status_changed_at`).
- Selettore di assegnatario (vedere la board di un collega).
- Cambio di stato **solo tramite le transizioni consentite**; drag & drop ammesso se la validazione lato server
resta invariata.
- Attività recenti (da `ticket_logs`).
- Performance: deve caricare in tempi accettabili sull'intero dataset importato. Query aggregate, non N+1.



### 8.7 Requisiti UI trasversali

- **Italiano** come lingua dell'interfaccia, struttura pronta per l'inglese.
- Ricerca globale sui ticket (id, titolo, richiedente, corpo dei messaggi).
- Tabelle: colonne configurabili, filtri persistenti in sessione, export.
- Stati vuoti e messaggi di errore scritti, non generici.
- Accessibilità: contrasto conforme ai token, navigazione da tastiera, label sui campi.
- **Nessun** `{!! !!}` su contenuto proveniente dall'utente o da email.
- I messaggi importati dal v1 (`is_legacy_import`) sono visivamente distinguibili nella timeline.

---



## 9. M7 — Autorizzazioni



### 9.1 I tre livelli — e perché servono tutti

L'errore da evitare è pensare che un pacchetto di permessi sostituisca le policy. In questo sistema la maggior
parte delle regole è **legata al singolo record**, e nessun pacchetto di permessi la esprime. I livelli sono
tre, con responsabilità distinte e non sovrapposte:


| Livello              | Risponde a                                                                                       | Implementazione                          | Esempio                                                                                              |
| -------------------- | ------------------------------------------------------------------------------------------------ | ---------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| **1. Permesso**      | "questo utente ha la capacità X?"                                                                | `spatie/laravel-permission` (D13)        | `ticket.update.any`, `horizon.access`                                                                |
| **2. Policy**        | "questo utente può fare X **su questo record**?"                                                 | Policy Laravel native                    | il developer aggiorna il ticket solo se `assignee_id` o `tester_id` è suo                            |
| **3. Campo / query** | "questo utente può vedere o scrivere **questo campo**, e quali righe rientrano nella sua query?" | schema dei form Filament + scope globali | il cliente non tocca `priority`; i messaggi `internal` non entrano nemmeno nella query di un cliente |


Regole vincolanti:

- **Deny by default**: se una policy non esiste, l'accesso è negato. Nel v1 `Tag`, `Organization`, `StoryLog`
e `ActivityReport` **non hanno policy** e sono di fatto aperti, limitati solo dalla visibilità del menu.
- Le policy **partono** dal permesso e poi applicano la regola sul record: `$user->can('ticket.update.any')`
oppure `$user->can('ticket.update.assigned') && $ticket->isAssignedTo($user)`. Mai un `hasRole()` dentro una
policy per esprimere una capacità: il ruolo si usa solo dove il comportamento è davvero legato al ruolo
(navigazione, landing, visibilità dei campi).
- Il livello 3 **non è cosmetico**: una restrizione applicata solo in vista è aggirabile con una richiesta
manipolata. Va imposta nello schema del form e nella query.
- Ogni livello ha test (§13.1).
- L'accesso al pannello è governato da un gate esplicito.



### 9.2 Ruoli nel codice, non a runtime

I ruoli sono un **enum PHP** (`UserRole`) ed è quella la sorgente di verità; le righe nella tabella `roles` di
Spatie sono la loro proiezione, creata da un seeder idempotente. Lo stesso vale per i permessi (`Permission`
enum + catalogo in §9.3) e per la mappa ruolo → permessi.

Perché non rendere ruoli e permessi modificabili da interfaccia:

1. I ruoli di questo sistema sono **comportamentali**. `customer` non è un insieme di permessi: cambia
  navigazione, landing, campi visibili e scoping delle query. Un ruolo creato da UI produrrebbe un utente che
   l'interfaccia non sa rendere — il pacchetto farebbe *sembrare* configurabile qualcosa la cui semantica sta
   nel codice.
2. Un enum dà completamento, analisi statica e refactor sicuri. `UserRole::Manager` è verificabile da Larastan;
  una stringa letta dal database no.
3. La mappa ruolo → permessi è una **decisione di prodotto**, quindi appartiene al versionamento: va rivista in
  code review, non cambiata in produzione senza traccia.

Cosa **è** modificabile a runtime: l'assegnazione di ruoli agli utenti, e la concessione di **permessi diretti**
a una singola persona (§6.7.1). È questo che dà la flessibilità utile senza il rischio.

Conseguenze implementative:

- Il seeder di ruoli e permessi è **idempotente** e gira ad ogni deploy (`migrate` + seed dedicato).
- Un permesso rimosso dal catalogo va **revocato** dal seeder, non lasciato orfano nel database.
- Il seeder segnala i permessi diretti che puntano a permessi non più nel catalogo.
- **Non** installare `filament-shield`: genera l'intera UI di gestione runtime di ruoli e permessi, che è
esattamente ciò che questa decisione esclude. La UI necessaria è quella minima di §6.7.1.



### 9.3 Catalogo dei permessi

Convenzione di naming: `<dominio>.<azione>[.<ambito>]`. `any` = su qualunque record, `own` = solo sui propri,
`assigned` = solo su quelli in cui l'utente è assegnatario o tester.


| Dominio         | Permessi                                                                                                                                                                                                                                      |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Ticket          | `ticket.view.any`, `ticket.view.own`, `ticket.view.assigned`, `ticket.create`, `ticket.update.any`, `ticket.update.own`, `ticket.update.assigned`, `ticket.delete`, `ticket.assign`, `ticket.transition.any`, `ticket.manage-internal-fields` |
| Messaggi ticket | `ticket-message.create`, `ticket-message.view.internal`, `ticket-message.create.internal`                                                                                                                                                     |
| Log ticket      | `ticket-log.view`                                                                                                                                                                                                                             |
| Tag / commesse  | `tag.view`, `tag.create`, `tag.update`, `tag.delete`                                                                                                                                                                                          |
| Documentazione  | `documentation.view.customer`, `documentation.view.internal`, `documentation.create`, `documentation.update`, `documentation.delete`                                                                                                          |
| Report attività | `activity-report.view.any`, `activity-report.view.own`, `activity-report.create`, `activity-report.update`, `activity-report.delete`, `activity-report.generate-pdf`                                                                          |
| Organizzazioni  | `organization.view`, `organization.create`, `organization.update`, `organization.delete`                                                                                                                                                      |
| Fundraising     | `fundraising.view.any`, `fundraising.view.involved`, `fundraising.create`, `fundraising.update`, `fundraising.delete`, `fundraising.evaluate`                                                                                                 |
| Utenti          | `user.view`, `user.create`, `user.update`, `user.deactivate`, `user.assign-roles`, `user.grant-permissions`, `user.impersonate`                                                                                                               |
| Email           | `email.view`, `email.manage`                                                                                                                                                                                                                  |
| Sistema         | `horizon.access`, `logs.access`, `import.view`                                                                                                                                                                                                |


`ticket.manage-internal-fields` è il permesso che governa il livello 3 sui ticket: tipo, priorità, assegnatario,
tester, ore stimate, `description` interna, URL degli ambienti.

### 9.4 Mappa ruolo → permessi

Questa è la tabella che il seeder materializza. `admin` riceve **tutti** i permessi del catalogo.


| Permesso                                                    | admin | manager | developer | customer | fundraising                           |
| ----------------------------------------------------------- | ----- | ------- | --------- | -------- | ------------------------------------- |
| `ticket.view.any`                                           | ✔     | ✔       | ✔         | —        | —                                     |
| `ticket.view.own`                                           | ✔     | ✔       | ✔         | ✔        | —                                     |
| `ticket.create`                                             | ✔     | ✔       | ✔         | ✔        | —                                     |
| `ticket.update.any`                                         | ✔     | ✔       | —         | —        | —                                     |
| `ticket.update.own`                                         | ✔     | ✔       | —         | ✔        | —                                     |
| `ticket.update.assigned`                                    | ✔     | ✔       | ✔         | —        | —                                     |
| `ticket.delete`                                             | ✔     | —       | —         | —        | —                                     |
| `ticket.assign`                                             | ✔     | ✔       | ✔         | —        | —                                     |
| `ticket.transition.any`                                     | ✔     | ✔       | —         | —        | —                                     |
| `ticket.manage-internal-fields`                             | ✔     | ✔       | ✔         | —        | —                                     |
| `ticket-message.create`                                     | ✔     | ✔       | ✔         | ✔        | —                                     |
| `ticket-message.view.internal`                              | ✔     | ✔       | ✔         | —        | —                                     |
| `ticket-message.create.internal`                            | ✔     | ✔       | ✔         | —        | —                                     |
| `ticket-log.view`                                           | ✔     | ✔       | ✔         | —        | —                                     |
| `tag.view`                                                  | ✔     | ✔       | ✔         | —        | —                                     |
| `tag.create` / `tag.update`                                 | ✔     | ✔       | —         | —        | —                                     |
| `tag.delete`                                                | ✔     | —       | —         | —        | —                                     |
| `documentation.view.customer`                               | ✔     | ✔       | ✔         | ✔        | ✔                                     |
| `documentation.view.internal`                               | ✔     | ✔       | ✔         | —        | ✔                                     |
| `documentation.create` / `.update`                          | ✔     | ✔       | ✔         | —        | —                                     |
| `documentation.delete`                                      | ✔     | —       | —         | —        | —                                     |
| `activity-report.view.any`                                  | ✔     | ✔       | —         | —        | —                                     |
| `activity-report.view.own`                                  | ✔     | ✔       | —         | ✔        | —                                     |
| `activity-report.create` / `.update` / `.generate-pdf`      | ✔     | —       | —         | —        | —                                     |
| `activity-report.delete`                                    | ✔     | —       | —         | —        | —                                     |
| `organization.view`                                         | ✔     | ✔       | —         | —        | —                                     |
| `organization.create` / `.update` / `.delete`               | ✔     | —       | —         | —        | —                                     |
| `fundraising.view.any`                                      | ✔     | —       | —         | —        | ✔                                     |
| `fundraising.view.involved`                                 | ✔     | —       | —         | ✔        | ✔                                     |
| `fundraising.create` / `.update` / `.evaluate`              | ✔     | —       | —         | —        | ✔                                     |
| `fundraising.delete`                                        | ✔     | —       | —         | —        | ✔                                     |
| `user.view`                                                 | ✔     | —       | —         | —        | ✔ (solo per la selezione dei partner) |
| `user.create` / `.update` / `.deactivate`                   | ✔     | —       | —         | —        | —                                     |
| `user.assign-roles` / `.grant-permissions` / `.impersonate` | ✔     | —       | —         | —        | —                                     |
| `email.view` / `email.manage`                               | ✔     | —       | —         | —        | —                                     |
| `horizon.access` / `logs.access`                            | ✔     | —       | —         | —        | —                                     |
| `import.view`                                               | ✔     | —       | —         | —        | —                                     |


Note:

- `horizon.access` e `logs.access` nel v1 sono legati al ruolo `developer`. In v2 sono permessi **non** inclusi
in alcun ruolo oltre `admin`: si concedono direttamente ai developer che ne hanno bisogno. È il caso d'uso che
giustifica i permessi diretti.
- `documentation.create`/`.update` è il permesso che sostituisce il ruolo `editor` (D14): un utente che nel v1
era `editor` riceve questi due permessi come **permessi diretti** (§11.5).
- `manager` non ha `ticket.delete` né i permessi di `delete` sugli altri domini, e non può fare
restore/forceDelete.



### 9.5 Regole sul record — competenza delle policy

Il permesso non basta: queste regole vanno nelle policy e nelle query, e sono la parte che va coperta da test.


| Regola                                                                                                                | Dove                                                                |
| --------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------- |
| Il developer aggiorna un ticket solo se `assignee_id` o `tester_id` è suo                                             | `TicketPolicy::update()`                                            |
| Il cliente vede e aggiorna solo i ticket con `requester_id` suo                                                       | `TicketPolicy` + scope                                              |
| Il cliente non vede **mai** i messaggi `visibility = internal`                                                        | scope sulla relazione, non filtro in vista                          |
| Il cliente vede solo la documentazione `category = customer`                                                          | `DocumentationPolicy` + scope                                       |
| Il cliente vede solo i propri report di attività                                                                      | `ActivityReportPolicy` + scope                                      |
| Un progetto fundraising è visibile al cliente solo se **coinvolto** (capofila OR partner OR responsabile OR creatore) | `FundraisingProjectPolicy::view()`                                  |
| Le transizioni di stato consentite dipendono dal rapporto con il ticket (assegnatario, tester)                        | `TicketStateMachine` (§6.1.3), che consulta permesso **e** rapporto |
| Il cliente non modifica tipo, priorità, assegnatario, tester, ore, `description`, URL ambienti                        | schema del form, governato da `ticket.manage-internal-fields`       |
| Gli allegati sono scaricabili solo da chi può vedere il ticket                                                        | policy sulla rotta di download (§6.1.8)                             |
| Un utente disattivato non accede e non è selezionabile                                                                | gate di accesso + scope sui campi di selezione (§6.7.5)             |




### 9.6 Sicurezza — correzioni obbligatorie rispetto al v1


| Problema v1                                                                                                                                                                      | Requisito v2                                                                                                                                                                                |
| -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Rotte `/mailable` e `/logs` senza autenticazione                                                                                                                                 | Tutte le rotte non pubbliche dietro `auth` + policy. Nessuna rotta di debug in produzione                                                                                                   |
| Rotte di test che inviano email a indirizzi hard-coded                                                                                                                           | Nessuna rotta di test nel codice di produzione: usa i test                                                                                                                                  |
| `{!! !!}` su corpo email non sanitizzato                                                                                                                                         | Sanitizzazione con allowlist, sempre, in scrittura **e** in lettura                                                                                                                         |
| Nome allegato usato come path                                                                                                                                                    | Path generati, nome sanitizzato                                                                                                                                                             |
| **Allegati serviti da URL pubbliche** del disco medialibrary: chiunque conosca l'URL scarica il file di un altro cliente                                                         | Disco **privato** + rotta di download autorizzata dalla policy del ticket, o URL firmate a scadenza. Vale anche per PDF e allegati email. L'ETL deve migrare i file sul disco privato (Q10) |
| Impersonation su qualsiasi utente                                                                                                                                                | Solo admin, con log e banner                                                                                                                                                                |
| Ruoli come JSON in un `varchar(255)`, query con `whereJsonContains` sparse                                                                                                       | Ruoli e permessi normalizzati (D13) + scope tipizzati                                                                                                                                       |
| Quattro modelli **senza policy** (`Tag`, `Organization`, `StoryLog`, `ActivityReport`): accessibili a chiunque raggiunga l'URL, perché l'unica barriera è la visibilità nel menu | Policy per ogni modello, deny by default (§9.1)                                                                                                                                             |
| Capacità cablate nei ruoli: l'accesso a Horizon e ai log dipende dal ruolo `developer`                                                                                           | Permessi espliciti, concedibili singolarmente (§9.4)                                                                                                                                        |
| Nessun controllo sui destinatari delle notifiche                                                                                                                                 | `notification_preferences` + `email_suppressions`                                                                                                                                           |


---



## 10. Automazioni schedulate



### 10.1 Regole comuni

Ogni comando schedulato deve:

- essere attivabile/disattivabile da **feature flag** in `config/orchestrator.php` letta da env (pattern del v1
da mantenere: è utile);
- essere **idempotente**;
- usare `withoutOverlapping()` e `runInBackground()` dove sensato;
- produrre un **log strutturato** con inizio, fine, durata, record esaminati/modificati/saltati, errori;
- essere eseguibile a mano con `--dry-run` che mostra cosa farebbe **senza scrivere**;
- **scrivere i** `ticket_logs` per le mutazioni che esegue, con `is_system = true` e l'utente di sistema. Nel v1
alcuni comandi creano il log a mano solo se riescono a dedurre un utente, e `orchestrator:scrum-archive` muta
senza lasciare traccia: le ore lavorate e lo storico ne risultano falsati.

Lo scheduler gira nel container `scheduler` con `schedule:work`, timezone `Europe/Rome`.

### 10.2 Catalogo


| Comando                          | Schedule                 | Flag env                               | Cosa fa                                                                                                            |
| -------------------------------- | ------------------------ | -------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| `tickets:progress-to-todo`       | 18:00                    | `ENABLE_TICKETS_PROGRESS_TO_TODO`      | tutti i ticket `progress` → `todo`                                                                                 |
| `tickets:auto-close-released`    | 07:45                    | `ENABLE_TICKETS_AUTO_CLOSE_RELEASED`   | ticket `released` da ≥ 3 giorni lavorativi → `done`, con `done_at`                                                 |
| `tickets:close-scrum`            | 16:00                    | `ENABLE_TICKETS_CLOSE_SCRUM`           | ticket di tipo `scrum` creati/aggiornati oggi → `done`                                                             |
| `tickets:restore-waiting`        | daily                    | `ENABLE_TICKETS_RESTORE_WAITING`       | ticket `waiting` da ≥ N giorni → `previous_status`. `N` da `TICKET_RESTORE_WAITING_DAYS` (default 7)               |
| `tickets:send-waiting-reminders` | daily                    | `ENABLE_TICKETS_WAITING_REMINDERS`     | reminder E7. **Nel v1 non è schedulato: va schedulato**                                                            |
| `tickets:archive-scrum`          | 05:00                    | `ENABLE_TICKETS_ARCHIVE_SCRUM`         | archiviazione dei ticket scrum. Verifica il comportamento v1 in dettaglio prima di riprodurlo (Q9)                 |
| `mail:fetch-inbound`             | ogni 5 min               | `ENABLE_MAIL_FETCH_INBOUND`            | pipeline inbound (§7.3). `WithoutOverlapping` obbligatorio. Non serve se si usa il webhook                         |
| `mail:retry-failed`              | ogni 15 min              | `ENABLE_MAIL_RETRY_FAILED`             | riprova i messaggi `failed` entro la soglia di tentativi                                                           |
| `timetracking:aggregate-daily`   | 23:30                    | `ENABLE_TIMETRACKING_AGGREGATE`        | consolida `ticket_work_logs` per la giornata. Nel v1 il job esiste ma **non ha alcuna cadenza schedulata**         |
| `reports:generate-monthly`       | 1° del mese, 12:00       | `ENABLE_REPORTS_MONTHLY`               | genera i report del mese precedente per tutti gli owner attivi                                                     |
| `timetracking:recalculate`       | manuale                  | —                                      | ricalcolo massivo delle ore (§6.2.2)                                                                               |
| `documentation:regenerate-pdfs`  | manuale                  | —                                      | rigenerazione batch dei PDF (§6.4.3)                                                                               |
| `tickets:backfill-dates`         | manuale                  | —                                      | ricostruisce `released_at`/`done_at` dai log dove sono null (§11.5)                                                |
| `mail:send-digest`               | daily, 07:00             | `ENABLE_MAIL_DIGEST`                   | E8 — **solo se approvata** (Q3). Nel v1 il job esiste ma non è dispatchato da nessuna parte                        |
| `tickets:notify-idle-developers` | ogni 30 min, 09:00–15:30 | `ENABLE_TICKETS_IDLE_DEVELOPER_NOTICE` | E11 — **solo se approvata** (Q3). Nel v1 è un job ritardato lanciato da un observer: in v2 è un comando schedulato |


**Default di tutti i flag:** `false`**.** L'abilitazione è una scelta di deploy.

### 10.3 Non riprodotti

- **Sincronizzazione Google Calendar** (D12): nel v1 `StoryObserver::updated()` chiama
`Artisan::call('sync:stories-calendar')` **in linea** a ogni aggiornamento di ticket, più un comando
schedulato alle 07:45. In v2 **non esiste**: nessun pacchetto, nessun comando, nessuna variabile d'ambiente,
nessun riferimento in UI. Se in futuro servisse, il punto di innesto è il listener di `TicketStatusChanged`
(§15.2) — mai un `Artisan::call()` dentro un observer.
- `story:update-status`: comando manuale che riallinea `new` + assegnatario → `assigned`. In v2 la transizione
T1 è garantita dall'action; la riconciliazione una-volta è nell'ETL (§11.5).
- I comandi di import del v1 (`orchestrator:import`, `ImportProducts`) riguardano moduli fuori scope.

---



## 11. M11 — Importazione dal dump v1 (ETL)

Requisito del committente: **una procedura che importa tutti i dati da un dump di produzione della v1.**

### 11.1 Principi


| #   | Principio                                                                                                                                                                          |
| --- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| P1  | **Ripetibile e idempotente** (D10): eseguibile N volte, sullo stesso dump o su uno più recente, senza duplicare né corrompere nulla. Una seconda esecuzione aggiorna, non aggiunge |
| P2  | **Non distruttiva sulla sorgente**: il dump v1 viene ripristinato in un database **separato e in sola lettura** (`db_legacy`, §4.2). L'ETL non scrive mai sul v1                   |
| P3  | **Isolata**: tutto il codice in `app/Import/`, rimovibile in blocco a cutover concluso. Nessuna classe di dominio dipende da essa                                                  |
| P4  | **A stage**: ogni entità è uno stage indipendente, eseguibile singolarmente, con dipendenze dichiarate                                                                             |
| P5  | **Verificabile**: ogni esecuzione produce un **report di validazione** (§11.7) che confronta v1 e v2 e va letto prima di dichiarare l'import riuscito                              |
| P6  | **Trasparente sui compromessi**: dove il dato v1 è ambiguo o incompleto, l'ETL applica una regola dichiarata, la registra e la conta nel report. Mai una scelta silenziosa         |
| P7  | **Continuità degli id** per le entità con riferimenti esterni (§5.1)                                                                                                               |




### 11.2 Procedura operativa

```bash
# 1. Ripristina il dump v1 nel database di appoggio (sola lettura)
make etl-up                       # avvia il servizio db_legacy
bin/load-v1-dump path/to/dump.sql

# 2. Ispeziona il dump prima di importare (§11.3)
php artisan v1:inspect

# 3. Prova a vuoto: nessuna scrittura, solo il report
php artisan v1:import --dry-run

# 4. Importa
php artisan v1:import

# 5. Derivati e riconciliazioni
php artisan v1:import --stage=derive

# 6. Verifica
php artisan v1:validate
```

Opzioni richieste su `v1:import`:


| Opzione               | Effetto                                                                                                                 |
| --------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| `--dry-run`           | nessuna scrittura, report completo                                                                                      |
| `--stage=<nome>`      | esegue un solo stage (rispettando le dipendenze o segnalando se non sono soddisfatte)                                   |
| `--from-stage=<nome>` | riprende da uno stage in poi                                                                                            |
| `--limit=N`           | importa solo i primi N record per stage: utile per un ciclo di sviluppo rapido                                          |
| `--truncate`          | svuota le tabelle di destinazione prima di importare (**solo** in ambiente non di produzione, con conferma interattiva) |
| `--anonymize`         | applica l'anonimizzazione durante l'import (§11.8)                                                                      |


L'intera procedura deve essere documentata nel README e **funzionare al primo tentativo su una macchina
pulita**.

### 11.3 Ispezione preliminare — `v1:inspect`

Da eseguire **prima** di finalizzare i mapping. Il modello v1 dichiara cose che il dato reale può smentire: il
comando serve a scoprirlo in anticipo, non a valle di un import fallito.

Deve riportare:

- conteggio righe per tabella;
- **valori distinti effettivi** di `stories.status`, `type`, `priority` (per scoprire valori fuori enum,
varianti di capitalizzazione, null);
- **valori distinti di** `users.roles`: la colonna è un `varchar(255)` che contiene JSON — vanno censiti i
formati realmente presenti, inclusi eventuali valori non-JSON o ruoli sconosciuti;
- quante `stories` hanno `customer_request` non vuota, e **quante sono parsabili** in messaggi distinti dal
parser di §11.5 (campione con esempi);
- quanti `story_logs` hanno un `changes` interpretabile, e la distribuzione delle chiavi presenti;
- quante righe di `story_story` **non** sono riflesse in `stories.parent_id` e viceversa (conflitti di
gerarchia);
- quanti ticket hanno `done_at`/`released_at` null pur essendo in stato `done`/`released`;
- quante righe ha `story_participants` (attesa: zero o quasi, §6.1.7);
- quanti `tags` hanno un `taggable_type` diverso da `Documentation`;
- quanti media esistono, la loro dimensione totale, e quanti file sono **effettivamente presenti** sul disco;
- email duplicate su `users` a meno del case;
- orfani: FK che puntano a record inesistenti, per ogni relazione in scope.

L'output va salvato in `storage/app/import/inspect-<timestamp>.md` e **allegato alla PR** della fase ETL.

### 11.4 Stage

Ordine e dipendenze. Ogni stage è idempotente: chiave di riconciliazione indicata in tabella.


| #   | Stage                       | Sorgente v1                                 | Destinazione v2                                             | Chiave di idempotenza                           |
| --- | --------------------------- | ------------------------------------------- | ----------------------------------------------------------- | ----------------------------------------------- |
| 1   | `users`                     | `users`                                     | `users`                                                     | `id` conservato                                 |
| 2   | `roles_permissions`         | `users.roles` (JSON)                        | tabelle Spatie (`model_has_roles`, `model_has_permissions`) | `(user_id, role)` / `(user_id, permission)`     |
| 3   | `organizations`             | `organizations`                             | `organizations`                                             | `id` conservato                                 |
| 4   | `organization_members`      | `organization_user`                         | `organization_user`                                         | `(organization_id, user_id)`                    |
| 5   | `documentation`             | `documentations`                            | `documentation_pages`                                       | `id` conservato                                 |
| 6   | `tags`                      | `tags`                                      | `tags`                                                      | `id` conservato                                 |
| 7   | `tickets`                   | `stories`                                   | `tickets`                                                   | `id` conservato                                 |
| 8   | `ticket_hierarchy`          | `stories.parent_id` + `story_story`         | `tickets.parent_id`                                         | `id` del ticket                                 |
| 9   | `ticket_tags`               | `taggables` (solo Story)                    | `ticket_tag`                                                | `(ticket_id, tag_id)`                           |
| 10  | `ticket_participants`       | `story_participants`                        | `ticket_participants`                                       | `(ticket_id, user_id)`                          |
| 11  | `ticket_logs`               | `story_logs` (esclusi i `watch`)            | `ticket_logs`                                               | `import_mappings` su `story_logs.id`            |
| 12  | `ticket_views`              | `story_logs` con `changes->watch`           | `ticket_views`                                              | `(ticket_id, user_id, viewed_on)`               |
| 13  | `ticket_messages`           | `stories.customer_request` (parsing, §11.5) | `ticket_messages`                                           | `import_mappings` su `(story_id, indice, hash)` |
| 14  | `ticket_attachments`        | `media` su `Story`                          | media su `TicketMessage`                                    | `media.uuid`                                    |
| 15  | `activity_reports`          | `activity_reports`                          | `activity_reports`                                          | `id` conservato                                 |
| 16  | `activity_report_tickets`   | `activity_report_story`                     | `activity_report_ticket`                                    | `(report_id, ticket_id)`                        |
| 17  | `fundraising_opportunities` | `fundraising_opportunities`                 | idem                                                        | `id` conservato                                 |
| 18  | `fundraising_scores`        | le 34 colonne `evaluation_*`                | `fundraising_evaluation_scores`                             | `(opportunity_id, criterion_key)`               |
| 19  | `fundraising_projects`      | `fundraising_projects`                      | idem                                                        | `id` conservato                                 |
| 20  | `fundraising_partners`      | `fundraising_project_partners`              | idem                                                        | `(project_id, user_id)`                         |
| 21  | `derive`                    | —                                           | valori calcolati (§11.6)                                    | ricalcolo completo, idempotente per definizione |


A fine import: **riallineamento delle sequenze** PostgreSQL per ogni tabella con id conservato (altrimenti il
primo insert applicativo va in conflitto). È un passo esplicito, non un effetto collaterale.

### 11.5 Regole di mapping non banali

Sono i punti in cui l'ETL prende decisioni. Ognuna va **implementata, dichiarata e contata nel report**.

**Ruoli e permessi** (stage 2). `users.roles` è JSON in un `varchar`. Prerequisito: il seeder di ruoli e
permessi (§9.2) deve aver già girato. Regole:

- parse tollerante; valore non parsabile → nessun ruolo assegnato + segnalazione;
- ruolo riconosciuto → assegnato tramite Spatie;
- `editor` **→ non è più un ruolo** (D14): l'utente riceve i **permessi diretti**
`documentation.create` e `documentation.update`. Se `editor` era il suo unico ruolo, va segnalato: senza
ruoli non potrà accedere al pannello, quindi va deciso caso per caso quale ruolo attribuirgli;
- ruolo non riconosciuto → scartato + segnalazione;
- utente senza ruoli → segnalato (in v2 non potrà accedere);
- `horizon.access` **e** `logs.access` **non vengono assegnati automaticamente** ai developer: nel v1 erano
impliciti nel ruolo. L'ETL produce l'elenco dei developer esistenti così che si possa decidere a chi
concederli come permessi diretti.

**Tipo e priorità del ticket** (stage 7). Mapping case-insensitive e tollerante agli spazi:
`Bug`→`bug`, `Feature`→`feature`, `Help desk`/`Helpdesk`/`help desk`→`helpdesk`, `Scrum`/`scrum`→`scrum`;
default `helpdesk` con segnalazione. Priorità `1`→`low`, `2`→`medium`, `3`→`high`; altri valori → `low` +
segnalazione.

`status_changed_at` (stage 7). Non esiste nel v1. Si deriva dal `story_logs` più recente con un cambio di
stato; se non c'è, si usa `stories.updated_at`. Conta e segnala quanti ticket usano il fallback.

`previous_status` (stage 7). Per i ticket in `waiting`/`problem`: si applica la stessa logica del v1
(risalire ai log fino al primo stato diverso da `waiting`/`problem`), con fallback `new`. Da qui in avanti la
colonna è mantenuta dall'applicazione e la ricostruzione non serve più. Conta quanti hanno richiesto il
fallback.

`ticket_logs` (stage 11). Il `changes` JSON del v1 va tradotto in `event` + `from_status`/`to_status` +
`changes`:

- presenza di `status` → `event = status_changed`, `to_status` = valore, `from_status` = lo stato del log
precedente dello stesso ticket (null se non ricostruibile);
- presenza di `user_id` → `event = assigned`;
- altre chiavi → `event = updated` con il diff;
- `viewed_at` del v1 → `occurred_at`;
- `user_id` mancante → utente di sistema;
- i log con sola chiave `watch` **non** diventano `ticket_logs`: vanno allo stage 12.

**Conversazione** (stage 13). È il mapping più delicato. Nel v1 `customer_request` è HTML accumulato in cui ogni
risposta è stata **prepesa** come blocco `<div>` con uno stile diverso per ruolo del mittente. Regole:

1. Tenta la scomposizione nei blocchi, ricavando per ciascuno: autore (dal nome/stile), timestamp se presente,
  corpo. L'ordine va **invertito** (il v1 prepende, la timeline v2 è cronologica).
2. I messaggi ricostruiti hanno `is_legacy_import = true`, `channel = email` se identificabile altrimenti
  `system`, `visibility = public`.
3. Se l'autore non è ricavabile, `author_id = null` e il messaggio è attribuito a "storico importato".
4. Se `posted_at` non è ricavabile, si usa una **distribuzione monotona** tra `created_at` e `updated_at` del
  ticket, così che l'ordine relativo sia coerente anche se le date sono approssimate. Segnala il conteggio.
5. **Se il parsing non riesce**, fallback: **un unico messaggio** con l'HTML integrale sanitizzato,
  `is_legacy_import = true`, `posted_at = stories.created_at`. Nessuna perdita di contenuto.
6. Sanitizza **sempre** l'HTML: i corpi provengono da email non filtrate ed è la sorgente dell'XSS stored del
  v1.
7. Il report elenca: messaggi ricostruiti, ticket con fallback a blocco unico, ticket senza conversazione.

**Allegati** (stage 14). I media del v1 stanno sulla `Story`, in v2 appartengono ai messaggi. Regola: attacca
al **primo messaggio legacy** del ticket; se il ticket non ha messaggi, crea un messaggio di sistema
"Allegati importati" e attacca lì. Verifica che il file esista fisicamente: i media orfani vanno **segnalati,
non ignorati**. I file vanno copiati sul **disco privato** (§9.6).

**Gerarchia** (stage 8). `stories.parent_id` è la sorgente primaria; le righe di `story_story` non riflesse
nella colonna vengono applicate solo se non creano conflitti (un figlio con due padri diversi → si tiene
`parent_id` e si segnala). Viola la profondità massima 1? Si appiattisce e si segnala.

**Punteggi fundraising** (stage 18). Ogni colonna `evaluation_*_score` non nulla diventa una riga con la
`criterion_key` corrispondente (mappa in §6.6.2); le `evaluation_criterion_*_description` diventano `notes`
della riga omonima. I punteggi fuori range vengono **clampati** al range del catalogo e segnalati. I totali
non si importano: si **ricalcolano** (stage 21) e si confrontano con quelli del v1 nel report.

**Date mancanti** (stage 21). Ricostruzione di `released_at`/`done_at` dai log per i ticket che ne sono privi
pur essendo in stato `released`/`done`. Necessario **prima** di rigenerare i report di attività, che selezionano
per `done_at` (§6.5.2).

### 11.6 Derivati (stage 21)

Ricalcolati da zero, nell'ordine:

1. `tickets.released_at` / `done_at` mancanti (regola sopra)
2. `tickets.worked_minutes` con il `WorkedTimeCalculator` v2 (§6.2.2)
3. `ticket_work_logs` per l'intero storico
4. totali di valutazione fundraising
5. slug di `tags` e `documentation_pages` (unicità garantita, con suffisso numerico sui duplicati)
6. `email_threads` per i ticket che hanno una conversazione, così che il threading funzioni anche sui ticket
  storici quando il cliente risponde a una vecchia email



### 11.7 Report di validazione — `v1:validate`

Prodotto a ogni esecuzione, salvato in `storage/app/import/` e mostrato anche nell'amministrazione (§8.4).
Deve contenere:

**Conteggi a confronto**


| Entità | v1  | v2        | Δ   | Atteso                              |
| ------ | --- | --------- | --- | ----------------------------------- |
| utenti | n   | n         | 0   | uguale                              |
| ticket | n   | n         | 0   | uguale                              |
| log    | n   | n − watch | —   | i `watch` migrano in `ticket_views` |
| ...    |     |           |     |                                     |


**Controlli di integrità**: orfani per ogni FK, unicità violate, enum fuori catalogo, ticket senza richiedente,
messaggi senza ticket, media mancanti sul disco.

**Confronto dei derivati**: ore lavorate per ticket v1 vs v2, con distribuzione degli scostamenti e l'elenco
dei ticket oltre la tolleranza (Q6). Totali di valutazione fundraising v1 vs v2 (devono coincidere
esattamente: se no, la formula è stata interpretata male).

**Compromessi applicati**, con i conteggi: ticket con `status_changed_at` da fallback, `previous_status` da
fallback, conversazioni con fallback a blocco unico, messaggi senza autore, messaggi con data stimata, ruoli
scartati, tipi normalizzati per default, punteggi clampati, conflitti di gerarchia, media orfani.

L'import è **riuscito** solo se: nessun controllo di integrità fallisce, i conteggi delle entità con id
conservato coincidono, i totali fundraising coincidono, e i compromessi sono entro le soglie concordate.

### 11.8 Anonimizzazione e dati sensibili

- `--anonymize` sostituisce nomi, email e corpi dei messaggi con dati fittizi **mantenendo le relazioni e la
distribuzione**. **Obbligatorio per ogni ambiente non di produzione.**
- Il dump **non** va committato: `.gitignore` esplicito su `storage/app/import/` e sui file di dump.
- Un guard nel codice impedisce l'invio di email verso indirizzi reali quando `APP_ENV !== 'production'`
(allowlist di domini di test).
- Il dump di produzione resta archiviato come riferimento storico dei moduli non importati (D11): documenta
**dove** è archiviato e chi ha accesso.



### 11.9 Cutover

1. Congela le modifiche sul v1 (finestra di manutenzione).
2. Genera un dump fresco.
3. Esegui `v1:inspect`, poi `v1:import`, poi `v1:validate` su un ambiente di staging identico alla produzione.
4. Verifica a campione con il committente: 10 ticket rappresentativi (uno per stato), 2 report di attività,
  1 opportunità valutata, la conversazione di un ticket con storico lungo.
5. Ripeti su produzione.
6. Reindirizza la casella email sul v2.
7. Mantieni il v1 accessibile in sola lettura per un periodo concordato.

Poiché l'ETL è ripetibile (D10), i passi 3–5 possono essere provati quante volte serve. Se in futuro servisse
importare i soli record modificati dopo una certa data (esecuzione parallela dei due sistemi), l'infrastruttura
a stage con chiavi di idempotenza lo consente aggiungendo un filtro `--since`: **non implementarlo adesso**.

---



## 12. Osservabilità e diagnostica


| Requisito           | Dettaglio                                                                                                                                                                                                                                                                   |
| ------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Log strutturati** | JSON in produzione, con `request_id`/`job_id` di correlazione. Canali separati per: email, scheduler, dominio ticket, import. **Driver** `daily` **con rotazione** — nel v1 il canale email è `single` senza rotazione                                                      |
| **Correlazione**    | ogni log include gli identificativi pertinenti (`ticket_id`, `user_id`, `email_message_id`, `import_run_id`). Nel v1 il log dell'inbound non contiene né il `Message-ID` né il mittente correlati al ticket: è impossibile ricostruire quale email ha generato quale ticket |
| **Errori**          | Sentry (o equivalente) con release tagging. I job **non** devono nascondere le eccezioni: `try/catch` senza rethrow è vietato                                                                                                                                               |
| **Coda**            | Horizon con soglie e allarmi su job falliti e tempi di attesa                                                                                                                                                                                                               |
| **Health check**    | endpoint `/up` esteso con: DB, Redis, connettività IMAP/SMTP, spazio su disco, heartbeat dello scheduler. Usato dagli healthcheck Docker                                                                                                                                    |
| **Diagnostica**     | comando `orchestrator:doctor`: variabili env obbligatorie, connessione IMAP/SMTP, permessi storage, presenza del logo PDF, esistenza dell'utente di sistema, feature flag attive, stato dell'ultimo import. È il primo comando da lanciare quando qualcosa non va           |
| **Audit**           | le azioni sensibili (impersonation, cambio ruoli, cancellazioni, azioni sull'amministrazione email, esecuzioni dell'ETL) vanno registrate in modo permanente e consultabile                                                                                                 |


---



## 13. Qualità



### 13.1 Test — copertura obbligatoria

Il v1 ha **un solo test** significativo (3 assert su una Mailable). Non è un riferimento.


| Area                                       | Tipo        | Cosa                                                                                                                                                                                                                                                                          |
| ------------------------------------------ | ----------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Macchina a stati                           | unit        | **ogni** transizione ammessa, **ogni** transizione vietata, ogni guard, per ogni ruolo                                                                                                                                                                                        |
| Regola "un solo progress per assegnatario" | feature     | demozione degli altri ticket, log generati, transazionalità                                                                                                                                                                                                                   |
| Ripristino da `waiting`/`problem`          | feature     | `previous_status` corretto, mantenimento del motivo, annotazione nella conversazione                                                                                                                                                                                          |
| Calcolo ore lavorate                       | unit        | weekend, fuori orario, a cavallo della mezzanotte, riapertura dopo settimane, più assegnatari, intervallo aperto, idempotenza                                                                                                                                                 |
| Sync `ActivityReport`                      | feature     | selezione per periodo e owner, idempotenza, unicità                                                                                                                                                                                                                           |
| Totali griglia fundraising                 | unit        | somme positive/negative, rischi negativi, valori limite, criterio aggiunto al catalogo                                                                                                                                                                                        |
| SAL dei tag                                | unit        | stima nulla, nessun ticket, arrotondamenti                                                                                                                                                                                                                                    |
| **Inbound email**                          | integration | **fixture** `.eml` **reali**: nuovo ticket, risposta a ticket esistente, mittente sconosciuto, DSN/bounce, autoreply, mail solo-HTML, allegati (validi, troppo grandi, MIME non ammesso, inline), charset non-UTF8, subject con prefissi multipli, **duplicato riprocessato** |
| **Idempotenza inbound**                    | integration | rieseguire la pipeline sullo stesso messaggio non crea un secondo ticket                                                                                                                                                                                                      |
| **Anti-loop**                              | integration | i controlli di §7.3.4 bloccano l'auto-reply                                                                                                                                                                                                                                   |
| Outbound                                   | feature     | destinatari corretti per ogni comunicazione (§7.5.3), lingua corretta, header di threading, soppressioni e preferenze rispettate                                                                                                                                              |
| Localizzazione                             | unit        | ogni chiave usata esiste in `it` e `en`                                                                                                                                                                                                                                       |
| Mappa ruolo → permessi                     | unit        | il seeder produce esattamente la matrice §9.4; è **idempotente**; un permesso rimosso dal catalogo viene revocato                                                                                                                                                             |
| Policy                                     | feature     | ogni regola sul record di §9.5, riga per riga, per ogni ruolo                                                                                                                                                                                                                 |
| Permessi diretti                           | feature     | un permesso concesso direttamente a un utente ha effetto anche se il suo ruolo non lo include; la revoca funziona                                                                                                                                                             |
| Restrizioni di campo per cliente           | feature     | un cliente non modifica tipo/priorità/assegnatario/ore né vede i messaggi `internal`, nemmeno via richiesta manipolata                                                                                                                                                        |
| **ETL — parser conversazione**             | unit        | fixture HTML reali estratte dal dump: blocchi multipli, autori diversi, HTML malformato, contenuto non parsabile → fallback                                                                                                                                                   |
| **ETL — idempotenza**                      | integration | due esecuzioni consecutive sullo stesso dataset di test producono lo stesso risultato, senza duplicati                                                                                                                                                                        |
| **ETL — mapping**                          | unit        | ruoli, tipi, priorità, punteggi fundraising, gerarchia con conflitti                                                                                                                                                                                                          |
| **ETL — validazione**                      | integration | su un dump di test ridotto e anonimizzato: i conteggi coincidono, gli orfani sono zero, i totali fundraising coincidono                                                                                                                                                       |


Serve un **dump di test ridotto e anonimizzato** committato nel repository (o generato da un seed), su cui la CI
esegue l'ETL completo a ogni PR. Senza questo, l'ETL non è testabile in modo continuo.

### 13.2 CI

Su ogni PR: Pint (check), Larastan, Pest con coverage, ETL sul dump di test + `v1:validate`, build dei
container.

### 13.3 Convenzioni di codice

- PSR-12 + Pint preset `laravel`.
- `declare(strict_types=1)`, tipi su tutti i parametri e i return.
- Nomi in **inglese** nel codice, **italiano** nelle stringhe utente.
- Un'Action = una classe = un metodo pubblico (`handle`/`__invoke`).
- Nessun `env()` fuori dai file di `config/`.
- Nessuna query nei template Blade.
- Commenti in italiano dove spiegano una regola di business non ovvia; nessun commento che ripete il codice.
- Ogni comando Artisan ha `--help` con una descrizione utile.



### 13.4 Documentazione da produrre

Il v1 ha una cartella `docs/` di buona qualità ma incompleta: 4 moduli su 7 dichiarati e mai scritti. In v2 la
documentazione è parte del deliverable.


| File                                                | Contenuto                                                                                                                                                          |
| --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `README.md`                                         | setup da zero, importazione dal dump, comandi principali, architettura in una pagina                                                                               |
| `docs/architecture.md`                              | struttura a moduli, principi A1–A9, dove sta cosa                                                                                                                  |
| `docs/data-model.md`                                | schema con diagramma, e la **mappa dei nomi v1 → v2** (§0.3)                                                                                                       |
| `docs/ticket-lifecycle.md`                          | macchina a stati con diagramma, validazioni, transizioni automatiche                                                                                               |
| `docs/email.md`                                     | pipeline inbound/outbound, configurazione, troubleshooting, catalogo delle comunicazioni                                                                           |
| `docs/time-tracking.md`                             | algoritmo, ipotesi, comandi di ricalcolo                                                                                                                           |
| `docs/authorization.md`                             | i tre livelli (§9.1), catalogo dei permessi, matrice ruolo → permessi, perché ruoli e permessi non sono modificabili a runtime, come concedere un permesso diretto |
| `docs/import-v1.md`                                 | procedura ETL, stage, **tutti i compromessi di mapping** di §11.5, come leggere il report                                                                          |
| `docs/design-system.md`, `docs/design-inventory.md` | §8.2                                                                                                                                                               |
| `docs/operations.md`                                | deploy, scheduler, coda, backup, `orchestrator:doctor`, cutover                                                                                                    |
| `docs/differences-from-v1.md`                       | differenze di comportamento rispetto al v1, con l'elenco dei bug v1 corretti: serve a spiegare agli utenti perché qualcosa "si comporta diversamente"              |


---



## 14. Roadmap

Una fase = un PR autocontenuto e verificabile. Non iniziare una fase prima che la precedente sia verificata.

### Fase 0 — Fondazioni *(nessuna funzionalità utente)*

- Repository nuovo, Docker (§4.2), Laravel 13, Filament 4, Pest, Pint, Larastan, CI.
- Import del design (§8.1) e produzione di `docs/design-system.md`, `theme.css`,
`docs/design-inventory.md`.
- Servizio `db_legacy`, `bin/load-v1-dump` e `v1:inspect` eseguito su un dump reale, con l'output allegato
alla PR. **Questo viene prima dello schema**: l'ispezione può smentire ciò che il modello v1 dichiara, e
alcune scelte di §5.2 dipendono da com'è fatto il dato reale (§0.1 punto 5).
- Schema completo (§5) come migrazioni pulite, modelli, enum castati, relazioni.
- **Ruoli e permessi**: enum `UserRole` e `Permission`, seeder idempotente che materializza la matrice di §9.4,
policy per **ogni** modello (deny by default), gate di accesso al pannello, UI minima di §6.7.1.
- `orchestrator:doctor`, seed di sviluppo.

**Criteri di accettazione**: `make setup` funziona da zero; l'app è navigabile col seed; l'autenticazione
funziona; il tema riflette il design; il report di ispezione del dump è disponibile.

**Punto di controllo obbligatorio**: presenta `docs/design-inventory.md` e il report di `v1:inspect` al
committente. Attendi conferma sulla classificazione in-scope / fuori-scope e sulle regole di mapping ambigue
emerse dall'ispezione.

### Fase 1 — Ticketing core

- Macchina a stati dichiarativa + action + validazioni + eventi.
- `TicketResource` con le viste di §8.5, campi di §6.1.9, gerarchia, partecipanti.
- Conversazione (`ticket_messages`) con timeline e allegati sui messaggi.
- `ticket_logs` con `event`/`from_status`/`to_status`, `ticket_views`.
- Time tracking (`WorkedTimeCalculator`, `ticket_work_logs`) + `timetracking:recalculate`.
- Allegati sui messaggi, su **disco privato** con download autorizzato (§6.1.8).
- **Vista di lavoro (§8.6) in versione essenziale**: è la landing di admin/manager/developer (§6.7.2), quindi non
può arrivare alla Fase 6. La rifinitura secondo il design e le ottimizzazioni di performance restano in
Fase 6.
- Test di §13.1 per macchina a stati, log, conversazione e time tracking.

**Criteri di accettazione**: un ticket percorre tutti i flussi di §6.1.3; nessuna transizione vietata è
eseguibile via UI o via richiesta manipolata; le ore si calcolano correttamente sui casi di test noti.

### Fase 2 — Importazione dal v1

- `app/Import/` completo: tutti gli stage di §11.4, i mapping di §11.5, i derivati di §11.6.
- `v1:import` con tutte le opzioni, `v1:validate` con il report di §11.7.
- Parser della conversazione con fixture reali.
- Anonimizzazione.
- Dump di test ridotto in CI.

**Criteri di accettazione**: `v1:import` su un dump di produzione reale passa `v1:validate`; una seconda
esecuzione consecutiva non modifica nulla (idempotenza dimostrata); i totali fundraising coincidono
esattamente; gli scostamenti sulle ore lavorate sono entro la tolleranza concordata (Q6); il report dei
compromessi è rivisto con il committente.

Questa fase viene **prima** dell'email perché tutto il resto dello sviluppo beneficia di lavorare su dati reali.

### Fase 3 — Sottosistema email

- Pipeline inbound completa (§7.3): classificazione, threading, idempotenza, quarantena, allegati.
- Outbound: layout unico, catalogo E1–E7 + E9, localizzazione reale, coda, soppressioni, **rispetto** delle
preferenze di notifica in fase di invio (la schermata di gestione è in Fase 6).
- Amministrazione email (§7.7).
- `mail:fetch-inbound`, `mail:retry-failed`.
- Test di integrazione con fixture `.eml`.

**Criteri di accettazione**: rispondere a un'email di notifica **aggiorna il ticket esistente** invece di
crearne uno nuovo, **anche su un ticket importato dal v1**; riprocessare lo stesso messaggio non duplica nulla;
un DSN non genera auto-reply; ogni email è ispezionabile dall'amministrazione; le comunicazioni arrivano nella
lingua dell'utente.

### Fase 4 — Rendicontazione, documentazione, commesse

- Tag/commesse con SAL.
- Documentazione + PDF + tag automatico + rigenerazione batch.
- Activity Report + Organizations + PDF multilingua + `reports:generate-monthly`.

**Criteri di accettazione**: i PDF sono conformi al design e corretti nei dati per un periodo verificato a
campione sui dati importati.

### Fase 5 — Fundraising

- Opportunità con griglia di valutazione reattiva sul catalogo dei criteri, progetti, partner, viste cliente,
azioni di creazione.

**Criteri di accettazione**: i punteggi calcolati coincidono con quelli importati; aggiungere un criterio al
catalogo non richiede migrazioni.

### Fase 6 — Portale cliente e rifinitura

- Dashboard cliente, viste ristrette, **schermata** delle preferenze di notifica, ricerca globale, badge di
navigazione, rifinitura e ottimizzazione della vista di lavoro (§8.6).
- Automazioni schedulate complete (§10.2).
- Comunicazioni opzionali E8/E10/E11 se approvate.
- Documentazione (§13.4) completa.

**Criteri di accettazione**: un utente cliente reale importato dal dump accede e vede solo i propri dati; tutti
i comandi hanno `--dry-run` e log strutturato.

### Fase 7 — DA DEFINIRE

*(Riservata a nuove funzionalità, ancora da specificare col committente.)*

### Fase 8 — DA DEFINIRE

*(Riservata a nuove funzionalità, ancora da specificare col committente.)*

### Fase 9 — DA DEFINIRE

*(Riservata a nuove funzionalità, ancora da specificare col committente.)*

### Fase 10 — Cutover

- Prova completa di §11.9 su staging.
- Confronto v1/v2 su dati reali: conteggi per stato, ore per ticket, report generati.
- Test di carico sulla vista di lavoro e sulle tabelle grandi.
- Verifica di sicurezza: rotte non autenticate, XSS, autorizzazioni per campo, allegati su disco privato.
- Piano di cutover e finestra di manutenzione concordati.

---



## 15. Nuove feature: punti di estensione, non implementazione

Il design contiene schermate e funzionalità che **non fanno parte di questa release**. La v2 deve poterle
accogliere senza rifattorizzazioni strutturali, ma **non deve implementarle**.

### 15.1 Cosa fare

1. In `docs/design-inventory.md`, classifica ogni elemento del design come *in scope in questa release* o
  *nuova feature*.
2. Per ogni nuova feature, scrivi in `docs/future-features.md`: cosa sembra fare, quali entità toccherebbe, e
  **quale punto di estensione della v2 la accoglierebbe**.
3. **Non** creare tabelle, modelli, resource, rotte o campi per queste feature.
4. **Non** creare astrazioni "per il futuro" che non servono oggi. Un punto di estensione è un confine pulito,
  non un'interfaccia vuota.



### 15.2 Punti di estensione richiesti dall'architettura


| Confine                                                                                                            | Perché                                                                                                                                                                                                                                                                                                  |
| ------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Eventi di dominio** del ticket (`TicketCreated`, `TicketStatusChanged`, `TicketMessagePosted`, `TicketAssigned`) | qualunque nuova automazione, integrazione o notifica si aggancia qui senza toccare il core. È anche il punto in cui rientrerebbe un'eventuale sincronizzazione con un calendario (D12)                                                                                                                  |
| **Macchina a stati dichiarativa**                                                                                  | aggiungere uno stato o una transizione è una riga nella tabella                                                                                                                                                                                                                                         |
| `ticket_messages.visibility`                                                                                       | le note interne al team sono già rappresentabili nello schema: attivarle è esporre un controllo in UI, non una migrazione                                                                                                                                                                               |
| `InboundMailTransport` **/** `OutboundMailTransport` come interfacce                                               | cambiare provider email o aggiungere un canale non deve toccare la pipeline                                                                                                                                                                                                                             |
| **Canali di notifica** astratti (mail, database, in futuro altri)                                                  | il catalogo §7.5.2 è indipendente dal canale                                                                                                                                                                                                                                                            |
| `WorkedTimeCalculator` con parametri espliciti                                                                     | qualunque cambio di politica sul tempo è configurazione, non codice                                                                                                                                                                                                                                     |
| **Query object** per le viste ticket                                                                               | una nuova vista è un nuovo query object, non una nuova resource                                                                                                                                                                                                                                         |
| **Catalogo dei criteri fundraising** in configurazione                                                             | nuove griglie di valutazione senza migrazioni                                                                                                                                                                                                                                                           |
| **Catalogo dei permessi** (§9.3) + permessi diretti                                                                | una nuova capacità è una voce nel catalogo e una riga nel seeder. Se un giorno servisse davvero la gestione runtime dei ruoli, il passaggio è aggiungere una UI sopra tabelle che esistono già — ma richiederebbe prima di rendere data-driven anche navigazione, landing e visibilità dei campi (§9.2) |
| **Generazione documenti** dietro un'interfaccia con template                                                       | nuovi tipi di documento riusano l'infrastruttura                                                                                                                                                                                                                                                        |
| `email_messages` **+** `ticket_messages` come timeline unificata                                                   | è la base di qualunque estensione della comunicazione con il cliente                                                                                                                                                                                                                                    |




### 15.3 Feature note già escluse

- moduli `Profile`, `Secretariat` (Meetings, Tkeform, Newsletter, Web content, Social content), `CRM`,
`Admin Tools` — dichiarati nell'indice della documentazione v1 e mai realizzati
- sincronizzazione con Google Calendar (D12)
- app mobile, API pubblica
- multi-tenancy / multi-brand
- fatturazione, abbonamenti
- import incrementale dal v1 (§11.9)

---



## 16. Anti-pattern del v1 da non replicare

Checklist di verifica. Se durante l'implementazione ti accorgi di aver fatto una di queste cose, è un difetto.

### 16.1 Architettura

1. ❌ Business logic in `boot()`/`booted()`/Observer. → Action esplicite (A1).
2. ❌ `saveQuietly()` per interrompere ricorsioni. → Se serve, il design è sbagliato.
3. ❌ `throw new Exception` come messaggio di validazione dentro `saving()`. → Regole di validazione (A3).
4. ❌ Stessa logica in 3 livelli (modello + observer + service). → Un solo posto.
5. ❌ `Artisan::call()` dentro un observer o una request. → Job in coda.
6. ❌ Una sottoclasse di resource per ogni filtro. → Query object + tab (A6).
7. ❌ Logica di presentazione in un trait da 1.100 righe. → Componenti.

