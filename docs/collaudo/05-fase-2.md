# Fase 2 (Importazione dal v1 — ETL) — Casi di test dettagliati

> Torna a [`README.md`](README.md) · Istruzioni generali: [`00-istruzioni-generali.md`](00-istruzioni-generali.md) · Matrice di tracciabilità: [`01-matrice-tracciabilita.md`](01-matrice-tracciabilita.md)

74 casi di test (F2-01 — F2-74) su 18 argomenti. Prima di eseguire un test, leggi le convenzioni comuni in `00-istruzioni-generali.md` (in particolare le sezioni 9 "Credenziali", 13 "Preparazione e ripristino dei dati" e 14 "Convenzioni per nominare i dati di test").

Questa fase è quasi interamente CLI/database, senza UI Filament dedicata: l'ETL (`php artisan v1:import`) gira automaticamente ad ogni deploy in ambiente UAT (`migrate:fresh` → seeder ruoli/permessi → `v1:import --anonymize`), quindi la maggior parte dei test qui descritti si esegue rilanciando la suite Pest mirata (fixture sqlite in-memory, nessun dato reale coinvolto: modalità `AUTOMATICO`). Solo i test che esercitano direttamente un comando Artisan end-to-end (`v1:import`, `v1:validate`) sono `TECNICO CLI` e presuppongono un accesso a terminale/Docker equivalente all'ambiente UAT, secondo quanto descritto in `CLAUDE.md` (sezione "ETL / dump v1").

## Scaffold ETL e runner (US-201)

### F2-01 — Il runner risolve l'ordine di esecuzione dalle dipendenze dichiarate degli stage, non dall'ordine di registrazione

**Obiettivo**
Verificare che `ImportRunner::plan()` calcoli l'ordine di esecuzione degli stage a partire dalle dipendenze dichiarate da ciascuno stage, e non dall'ordine con cui gli stage sono stati registrati nel `ImportStageRegistry`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-201 (runner che risolve l'ordine dalle dipendenze dichiarate degli stage, non un ordine arbitrario); CLAUDE.md, sezione "Scaffold di `v1:import`" (`ImportRunner::plan()` esegue un ordinamento topologico).
- Test automatico: `tests/Unit/Import/Stages/ImportRunnerPlanTest.php` — `resolves execution order from declared dependencies, not registration order` (registra 3 stage fittizi in ordine sparso — `fixture_c` dipende da `fixture_b`, `fixture_b` dipende da `fixture_a`, `fixture_a` senza dipendenze — e verifica che `plan()` restituisca `['fixture_a', 'fixture_b', 'fixture_c']`).
- File/componente applicativo rilevante: `app/Import/Stages/ImportRunner.php`, `app/Import/Stages/ImportStageRegistry.php`, `tests/Feature/Import/Fixtures/FakeImportStage.php`.
- Test correlato: F2-02, F2-03.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il runner è testato con stage fittizi (`FakeImportStage`), non con stage reali.

**Dati di test**
Registro con 3 stage fittizi registrati in ordine `fixture_c` (dipende da `fixture_b`), `fixture_a` (nessuna dipendenza), `fixture_b` (dipende da `fixture_a`).

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto, solo esecuzione di `ImportRunner::plan()` in memoria.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "resolves execution order from declared dependencies, not registration order"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `plan()` restituisce gli stage nell'ordine `fixture_a`, `fixture_b`, `fixture_c` (l'ordine di dipendenza), indipendentemente dall'ordine `fixture_c`/`fixture_a`/`fixture_b` con cui sono stati passati al registro.

**Controlli negativi**
Nessuno applicabile: il caso "dipendenza non registrata"/"dipendenza circolare" sono varianti dello stesso metodo, coperte separatamente (F2-02).

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

### F2-02 — Una dipendenza circolare tra stage viene rifiutata esplicitamente

**Obiettivo**
Verificare che `ImportRunner::plan()` rilevi una dipendenza circolare tra due stage (A dipende da B, B dipende da A) e sollevi un errore esplicito invece di entrare in loop infinito o restituire un ordine arbitrario/errato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-201 (il runner segnala errore esplicito, non un ordine arbitrario, quando le dipendenze non sono risolvibili).
- Test automatico: `tests/Unit/Import/Stages/ImportRunnerPlanTest.php` — `errors explicitly on a circular dependency` (`fixture_a` dipende da `fixture_b` e `fixture_b` dipende da `fixture_a`; verifica che `plan()` lanci `ImportRunnerException`).
- File/componente applicativo rilevante: `app/Import/Stages/ImportRunner.php`, `app/Import/Stages/ImportRunnerException.php`.
- Test correlato: F2-01.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: stage fittizi (`FakeImportStage`), non stage reali.

**Dati di test**
Registro con 2 stage fittizi: `fixture_a` (dipende da `fixture_b`), `fixture_b` (dipende da `fixture_a`).

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "errors explicitly on a circular dependency"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `plan()` lancia `App\Import\Stages\ImportRunnerException` quando le dipendenze dichiarate formano un ciclo, invece di restituire un ordine o entrare in loop infinito.

**Controlli negativi**
Nessuno applicabile: il file di test copre separatamente anche "dipendenza non registrata" (stage inesistente referenziato) con lo stesso tipo di eccezione, non richiesto da questo id.

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

### F2-03 — Gli stage vengono eseguiti nell'ordine di dipendenza e i conteggi sono registrati su import_runs.stages

**Obiettivo**
Verificare che `ImportRunner::run()` esegua gli stage nell'ordine calcolato da `plan()` e che, al termine, l'esito riporti per ciascuno stage i conteggi (`read`/`created`/`updated`/`skipped`/`warnings`) restituiti dal proprio `StageResult`, con lo stato complessivo dell'esecuzione marcato come completato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-201, AC "ogni esecuzione crea/aggiorna una riga `import_runs` con `stages` jsonb con righe lette/create/aggiornate/saltate/errori per stage".
- Test automatico: `tests/Feature/Import/Stages/ImportRunnerRunTest.php` — `executes stages in dependency order and records counts on import_runs.stages` (2 stage fittizi, `fixture_b` dipende da `fixture_a`; `fixture_a` restituisce `read:3, created:3`, `fixture_b` restituisce `read:2, created:1, updated:1`; verifica `status = Completed`, `finished_at` valorizzato, e i conteggi esatti per ciascuno stage).
- File/componente applicativo rilevante: `app/Import/Stages/ImportRunner.php`, `app/Import/Models/ImportRun.php`, `app/Import/Stages/StageResult.php`, `app/Import/Enums/ImportRunStatus.php`.
- Test correlato: F2-01, F2-04.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante (usa `RefreshDatabase`, schema v2 reale in sqlite, nessun dump v1).

**Dati di test**
2 stage fittizi: `fixture_a` (nessuna dipendenza, restituisce `StageResult(read: 3, created: 3)`), `fixture_b` (dipende da `fixture_a`, restituisce `StageResult(read: 2, created: 1, updated: 1)`).

**Stato iniziale**
Una riga `import_runs` creata con `status = Running`, `stages = []`.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "executes stages in dependency order and records counts on import_runs.stages"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: l'esito di `run()` ha `status = ImportRunStatus::Completed`, `finished_at` non nullo, `stages['fixture_a'] = ['read'=>3,'created'=>3,'updated'=>0,'skipped'=>0,'warnings'=>[]]` e `stages['fixture_b'] = ['read'=>2,'created'=>1,'updated'=>1,'skipped'=>0,'warnings'=>[]]`.

**Controlli negativi**
Nessuno applicabile: il fallimento di uno stage (che interrompe l'esecuzione e marca l'esito come `Failed`) è una variante dello stesso file di test, non richiesta da questo id specifico.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato al di fuori del database di test.

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

### F2-04 — La modalità --dry-run non scrive righe sulla tabella di destinazione

**Obiettivo**
Verificare che, quando l'esecuzione è marcata come dry-run (`ImportContext::isDryRun()` vero), uno stage che normalmente scriverebbe righe sulla tabella di destinazione (qui: `import_mappings`) non scriva nulla, mentre la stessa esecuzione senza dry-run scriva effettivamente la riga.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-201, AC "`--dry-run` non scrive alcuna riga nelle tabelle di destinazione (verificato con un test che conta le righe prima/dopo su uno stage fittizio)".
- Test automatico: `tests/Feature/Import/Stages/ImportRunnerRunTest.php` — `dry-run does not write rows to the destination table` (stage fittizio che inserisce una riga in `import_mappings` solo se `! $context->isDryRun()`; esegue prima in dry-run, verifica che il conteggio di `import_mappings` resti invariato, poi esegue senza dry-run e verifica che il conteggio aumenti di 1).
- File/componente applicativo rilevante: `app/Import/Stages/ImportContext.php`, `app/Import/Stages/ImportRunner.php`.
- Test correlato: F2-03.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Uno stage fittizio che, se non dry-run, inserisce una riga in `import_mappings` (`source_table=stories`, `source_key=1`, `target_table=tickets`, `target_id=1`).

**Stato iniziale**
Tabella `import_mappings` con il conteggio iniziale registrato prima dell'esecuzione.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "dry-run does not write rows to the destination table"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: dopo l'esecuzione in dry-run il conteggio di `import_mappings` resta identico a quello iniziale; dopo una successiva esecuzione reale (non dry-run) dello stesso stage il conteggio aumenta esattamente di 1.

**Controlli negativi**
Nessuno applicabile: il confronto "dry-run vs esecuzione reale" nello stesso test è già il controllo negativo.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato al di fuori del database di test.

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

### F2-05 — --truncate è rifiutato esplicitamente in un ambiente di produzione

**Obiettivo**
Verificare che `php artisan v1:import --truncate` sia rifiutato immediatamente (senza alcuna conferma interattiva) quando l'applicazione gira in ambiente di produzione, per evitare che un troncamento distruttivo delle tabelle di destinazione possa mai essere eseguito per errore su dati reali.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-201, AC "`--truncate` (con conferma interattiva, rifiutato fuori da un ambiente non-produzione)" — da leggersi come: fuori produzione richiede conferma interattiva, in produzione è sempre rifiutato a priori.
- Test automatico: `tests/Feature/Console/V1ImportCommandTest.php` — `--truncate is refused outright in a production environment` (forza `app()->instance('env', 'production')`, lancia `v1:import --truncate` e verifica che l'output contenga "non è consentito in ambiente di produzione" e che il comando fallisca).
- File/componente applicativo rilevante: `app/Console/Commands/V1ImportCommand.php`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a un ambiente locale/Docker equivalente a quello descritto in CLAUDE.md (sezione "ETL / dump v1"), con possibilità di eseguire `php artisan` in un contesto configurabile come ambiente di produzione (`APP_ENV=production` o equivalente).
- In alternativa, eseguire il test automatico mirato (non serve un vero dump v1/connessione `db_legacy`: il test forza l'ambiente a runtime).

**Dati di test**
Nessun dato applicativo: il comportamento dipende solo dall'ambiente (`APP_ENV`) e dall'opzione `--truncate`.

**Stato iniziale**
Ambiente applicativo impostato/percepito come produzione.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere a un ambiente con `APP_ENV=production` (o eseguire il test automatico che lo simula) | — | Ambiente riconosciuto come produzione |
| 2 | Eseguire `php artisan v1:import --truncate` | Opzione `--truncate` | Il comando termina immediatamente con un messaggio che contiene "non è consentito in ambiente di produzione", senza alcuna richiesta di conferma interattiva, ed esce con stato di fallimento |

**Risultato finale atteso**
Nessuna tabella di destinazione viene troncata: il comando si rifiuta di procedere non appena rileva l'ambiente di produzione, prima di qualunque altra operazione (nemmeno la conferma interattiva prevista fuori produzione viene mostrata).

**Controlli negativi**
Fuori da un ambiente di produzione, lo stesso comando (`v1:import --truncate`) deve invece mostrare una richiesta di conferma interattiva ("Sei sicuro di voler troncare le tabelle di destinazione prima di importare?"): rispondendo "no" il comando si annulla mostrando "Import annullato" e fallisce comunque, ma per un motivo diverso (rifiuto dell'utente, non blocco automatico) — variante coperta dallo stesso file di test, non da questo id.

**Evidenze da acquisire**
- Output completo del comando eseguito in ambiente di produzione (o del test automatico), con il messaggio di rifiuto e il codice di uscita.

**Criterio di superamento**

PASS: in ambiente di produzione il comando rifiuta `--truncate` con il messaggio atteso e termina con stato di fallimento, senza mai troncare alcuna tabella.
FAIL: il comando procede al troncamento, oppure chiede una conferma interattiva invece di rifiutare a priori, in ambiente di produzione.
BLOCKED: impossibile simulare/accedere a un ambiente riconosciuto come produzione per la verifica.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il comando si rifiuta prima di scrivere alcunché.

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

## Utenti e ruoli/permessi (US-202)

### F2-06 — "editor" non è un ruolo: viene segnalato separatamente, mai incluso nei roles

**Obiettivo**
Verificare che `UserRolesMapper::parse()`, di fronte al valore v1 `["editor"]`, non includa mai `editor` nell'elenco dei ruoli v2 risolti (`roles`), ma lo segnali separatamente tramite il flag dedicato `hadEditor`, senza generare alcun ruolo non riconosciuto.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-202/D14 ("editor" → **non** un ruolo, l'utente riceve i permessi diretti `documentation.create`/`documentation.update`).
- Test automatico: `tests/Unit/Import/Mappers/UserRolesMapperTest.php` — `editor is not a role: flagged separately, never in roles` (`UserRolesMapper::parse('["editor"]')`: verifica `roles === []`, `hadEditor === true`, `unrecognized === []`).
- File/componente applicativo rilevante: `app/Import/Mappers/UserRolesMapper.php`.
- Test correlato: F2-10.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il mapper è una funzione pura testata con stringhe JSON.

**Dati di test**
Input `'["editor"]'` (JSON serializzato di `users.roles` v1).

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto, solo esecuzione di un metodo statico puro.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "editor is not a role: flagged separately, never in roles"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `roles` è un array vuoto, `hadEditor` è `true`, `unrecognized` è un array vuoto.

**Controlli negativi**
Nessuno applicabile: il file di test copre separatamente il caso "editor mescolato a un ruolo riconosciuto" (entrambi i segnali convivono), non richiesto da questo id.

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

### F2-07 — Gli utenti v1 vengono importati in v2 con l'id preservato e le colonne mappate

**Obiettivo**
Verificare che `UsersStage` importi ogni utente `users` del v1 nella tabella `users` v2 preservando l'`id` originale e mappando correttamente le colonne v1 verso i nomi v2 (`activity_report_language`→`locale`, `google_drive_url`→`drive_url`, `google_drive_budget_url`→`drive_budget_url`).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-202, AC "Stage `users`: importa `users` con `id` conservato, mapping colonna per colonna".
- Test automatico: `tests/Feature/Import/Stages/UsersStageTest.php` — `imports v1 users into v2 with the id preserved and columns mapped` (utente v1 `id=42`, `activity_report_language=en`, `google_drive_url`/`google_drive_budget_url` valorizzati; verifica `read=1, created=1, updated=0, skipped=0` e che la riga v2 con `id=42` abbia `name`, `email`, `locale=en`, `drive_url`, `drive_budget_url` coerenti).
- File/componente applicativo rilevante: `app/Import/Stages/UsersStage.php`.
- Test correlato: F2-08, F2-70, F2-71, F2-74.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante (trait `InteractsWithLegacyDatabase`, connessione `legacy` riconfigurata su sqlite in-memory con solo le colonne v1 necessarie).

**Dati di test**
Utente v1 `id=42`, `name="Mario Rossi"`, `email="mario@example.test"`, `activity_report_language="en"`, `google_drive_url="https://drive.example/mario"`, `google_drive_budget_url="https://drive.example/mario-budget"`.

**Stato iniziale**
Nessun utente con `id=42` presente in `users` v2.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "imports v1 users into v2 with the id preserved and columns mapped"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: lo stage riporta `read=1, created=1, updated=0, skipped=0`; la riga v2 con `id=42` ha `name="Mario Rossi"`, `email="mario@example.test"`, `locale="en"`, `drive_url="https://drive.example/mario"`, `drive_budget_url="https://drive.example/mario-budget"`.

**Controlli negativi**
Nessuno applicabile: il file di test copre separatamente dry-run, idempotenza, update su riga cambiata, email duplicate ed anonimizzazione, coperti da altri id di questo manifest.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato al di fuori del database di test.

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

### F2-08 — Rieseguire lo stage sullo stesso dump è idempotente: la seconda esecuzione si limita a saltare

**Obiettivo**
Verificare che una seconda esecuzione consecutiva di `UsersStage` sullo stesso dump v1 non crei né aggiorni alcuna riga già importata correttamente: tutte le righe già presenti vengono conteggiate come "saltate" (`skipped`), senza duplicati né aggiornamenti superflui.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-202, AC "Idempotenza: rieseguire lo stage due volte sullo stesso dump non duplica utenti"; PRD principale, criterio di accettazione esplicito di Fase 2 ("una seconda esecuzione consecutiva non modifica nulla").
- Test automatico: `tests/Feature/Import/Stages/UsersStageTest.php` — `re-running the stage on the same dump is idempotent: second run only skips` (2 utenti v1; prima esecuzione `created=2, updated=0`; seconda esecuzione `created=0, updated=0, skipped=2`; conteggio finale `users` v2 = 2).
- File/componente applicativo rilevante: `app/Import/Stages/UsersStage.php`.
- Test correlato: F2-07.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
2 utenti v1 (`id=1`, `id=2` con email `giulia@example.test`).

**Stato iniziale**
Nessun utente v2 presente prima della prima esecuzione dello stage.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "re-running the stage on the same dump is idempotent: second run only skips"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la prima esecuzione riporta `created=2, updated=0`; la seconda esecuzione, sullo stesso dump, riporta `created=0, updated=0, skipped=2`; il conteggio totale di `users` v2 resta 2 (nessun duplicato).

**Controlli negativi**
Nessuno applicabile: l'assenza di duplicati/aggiornamenti alla seconda esecuzione è già il controllo negativo del test.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato al di fuori del database di test.

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

### F2-09 — Un ruolo riconosciuto viene assegnato tramite Spatie

**Obiettivo**
Verificare che `RolesPermissionsStage`, di fronte a un utente v1 con ruoli riconosciuti (`developer`, `fundraising`), assegni effettivamente questi ruoli all'utente v2 tramite le tabelle Spatie, e che segnali separatamente (come warning informativo, non un errore) l'elenco dei developer trovati come candidati manuali per `horizon.access`/`logs.access`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-202, AC "ruolo riconosciuto → assegnato via Spatie"; "`horizon.access`/`logs.access` non assegnati automaticamente: lo stage produce solo l'elenco dei developer esistenti nel report".
- Test automatico: `tests/Feature/Import/Stages/RolesPermissionsStageTest.php` — `assigns a recognized role via Spatie` (utente v1 `id=1` con `roles='["developer","fundraising"]'`, dopo il seeder `RolePermissionSeeder`; verifica `hasRole('developer')` e `hasRole('fundraising')` entrambi veri, `created=1`, e il warning esatto "Developer esistenti (candidati manuali per horizon.access/logs.access, non assegnati automaticamente): id v1 [1].").
- File/componente applicativo rilevante: `app/Import/Stages/RolesPermissionsStage.php`, `database/seeders/RolePermissionSeeder.php`.
- Test correlato: F2-06, F2-10.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Il seeder `RolePermissionSeeder` deve aver girato prima dello stage (il test lo esegue esplicitamente con `$this->seed(...)`) — vedi anche F2-XX sul fallimento esplicito se il seeder non è ancora girato (fuori da questo elenco di 74, comportamento verificato solo in altri test dello stesso file).

**Dati di test**
Utente v1 `id=1`, `email=user1@example.test`, `roles='["developer","fundraising"]'`.

**Stato iniziale**
Ruoli/permessi Spatie già seedati (`RolePermissionSeeder` eseguito); utente v2 con `id=1` già presente (creato dalla fixture di test, corrispondente all'utente v1 già "importato" da `UsersStage` in uno scenario reale).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "assigns a recognized role via Spatie"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: l'utente v2 risulta avere sia il ruolo `developer` sia `fundraising` assegnati via Spatie; lo stage riporta `created=1` e un unico warning che elenca l'utente come developer candidato manuale per `horizon.access`/`logs.access`.

**Controlli negativi**
Nessuno applicabile per questo id: il caso "ruolo non riconosciuto scartato" e "nessun ruolo valido" sono varianti dello stesso file di test, non richieste da questo id.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato al di fuori del database di test.

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

### F2-10 — "editor" concede i permessi diretti sulla documentazione invece di un ruolo, ed è segnalato se era l'unico ruolo presente

**Obiettivo**
Verificare che, quando l'unico ruolo v1 di un utente è `editor`, `RolesPermissionsStage` non assegni alcun ruolo v2 ma conceda direttamente i permessi `documentation.create` e `documentation.update` all'utente, segnalando esplicitamente nel report che nessun ruolo v2 è stato assegnato e che serve una decisione manuale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-202/D14 ("editor" → non un ruolo, l'utente riceve i permessi diretti `documentation.create`/`documentation.update`, segnalato se era l'unico ruolo).
- Test automatico: `tests/Feature/Import/Stages/RolesPermissionsStageTest.php` — `editor grants direct documentation permissions instead of a role, and is flagged if it was the only role` (utente v1 `id=1` con `roles='["editor"]'`; verifica `roles->count()===0`, `hasDirectPermission('documentation.create')` e `hasDirectPermission('documentation.update')` entrambi veri, e il warning esatto "Utente v1 #1 (user1@example.test): \"editor\" era l'unico ruolo v1 — nessun ruolo v2 assegnato (permessi diretti concessi), decidere manualmente il ruolo.").
- File/componente applicativo rilevante: `app/Import/Stages/RolesPermissionsStage.php`.
- Test correlato: F2-06, F2-09.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Seeder `RolePermissionSeeder` già eseguito (il test lo esegue esplicitamente).

**Dati di test**
Utente v1 `id=1`, `email=user1@example.test`, `roles='["editor"]'`.

**Stato iniziale**
Ruoli/permessi Spatie già seedati; utente v2 con `id=1` già presente, senza alcun ruolo/permesso diretto assegnato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "editor grants direct documentation permissions instead of a role, and is flagged if it was the only role"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: l'utente v2 non ha alcun ruolo assegnato, ma ha entrambi i permessi diretti `documentation.create`/`documentation.update`; il report contiene il warning esatto che segnala la necessità di una decisione manuale sul ruolo di questo utente.

**Controlli negativi**
Nessuno applicabile per questo id.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato al di fuori del database di test.

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

## Organizzazioni e membership (US-203)

### F2-11 — Le organizzazioni v1 vengono importate in v2 con l'id preservato e le colonne mappate

**Obiettivo**
Verificare che `OrganizationsStage` importi ogni organizzazione v1 nella tabella `organizations` v2 preservando l'`id` originale e mappando `activity_report_language` (v1) sulla colonna `locale` (v2).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-203, AC "Stage `organizations`: `id` conservato, mapping diretto".
- Test automatico: `tests/Feature/Import/Stages/OrganizationsStageTest.php` — `imports v1 organizations into v2 with the id preserved and columns mapped` (organizzazione v1 `id=7`, `name="ACME S.r.l."`, `activity_report_language="en"`; verifica `read=1, created=1, updated=0, skipped=0` e la riga v2 con `id=7` avente `name="ACME S.r.l."`, `locale="en"`).
- File/componente applicativo rilevante: `app/Import/Stages/OrganizationsStage.php`.
- Test correlato: F2-12, F2-13.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante (trait `InteractsWithLegacyDatabase`).

**Dati di test**
Organizzazione v1 `id=7`, `name="ACME S.r.l."`, `activity_report_language="en"`.

**Stato iniziale**
Nessuna organizzazione con `id=7` presente in `organizations` v2.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "imports v1 organizations into v2 with the id preserved and columns mapped"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: lo stage riporta `read=1, created=1, updated=0, skipped=0`; la riga v2 con `id=7` ha `name="ACME S.r.l."` e `locale="en"`.

**Controlli negativi**
Nessuno applicabile: dry-run, idempotenza e update sono varianti dello stesso file di test, coperte da altri id.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato al di fuori del database di test.

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

### F2-12 — Rieseguire lo stage sullo stesso dump è idempotente: la seconda esecuzione si limita a saltare

**Obiettivo**
Verificare che una seconda esecuzione consecutiva di `OrganizationsStage` sullo stesso dump v1 non crei né aggiorni alcuna riga già importata: le due organizzazioni già presenti vengono conteggiate come "saltate", senza duplicati.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-203, AC "Test di idempotenza (doppia esecuzione, conteggi invariati)".
- Test automatico: `tests/Feature/Import/Stages/OrganizationsStageTest.php` — `re-running the stage on the same dump is idempotent: second run only skips` (2 organizzazioni v1; prima esecuzione `created=2`; seconda esecuzione `created=0, updated=0, skipped=2`; conteggio finale `organizations` v2 = 2).
- File/componente applicativo rilevante: `app/Import/Stages/OrganizationsStage.php`.
- Test correlato: F2-11.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
2 organizzazioni v1 (`id=1` "ACME S.r.l.", `id=2` "Beta S.p.A.").

**Stato iniziale**
Nessuna organizzazione v2 presente prima della prima esecuzione dello stage.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "re-running the stage on the same dump is idempotent: second run only skips"` | Il comando termina con exit code 0, test passed (nota: il filtro per nome match anche il test omonimo di altri stage se eseguito senza specificare il file — su UAT preferire l'esecuzione dell'intero file `OrganizationsStageTest.php`) |

**Risultato finale atteso**
Il test Pest referenziato passa: la prima esecuzione riporta `created=2`; la seconda, sullo stesso dump, riporta `created=0, updated=0, skipped=2`; il conteggio totale di `organizations` v2 resta 2.

**Controlli negativi**
Nessuno applicabile: l'assenza di duplicati alla seconda esecuzione è già il controllo negativo del test.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato al di fuori del database di test.

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

### F2-13 — Una membership che referenzia un'organizzazione v2 inesistente viene segnalata, non manda in crash lo stage

**Obiettivo**
Verificare che `OrganizationMembersStage`, di fronte a una riga della pivot v1 `organization_user` che referenzia un'organizzazione non ancora importata in v2 (id inesistente), non interrompa l'esecuzione con un errore ma la conti come "saltata" e la segnali esplicitamente nel report, con un messaggio che menziona l'organizzazione inesistente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-203, AC "test di orfani (riga v1 che referenzia un utente/organizzazione inesistente → segnalata nel report, non un crash)".
- Test automatico: `tests/Feature/Import/Stages/OrganizationMembersStageTest.php` — `a membership referencing a non-existent v2 organization is reported, not crashed` (utente v2 `id=1` presente, membership v1 `(organization_id=999, user_id=1)` con organizzazione 999 mai importata; verifica `created=0, skipped=1`, un solo warning contenente "organizzazione inesistente", e nessuna riga scritta in `organization_user`).
- File/componente applicativo rilevante: `app/Import/Stages/OrganizationMembersStage.php`.
- Test correlato: F2-11, F2-12.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Utente v2 `id=1` già presente; riga pivot v1 `organization_user` con `organization_id=999` (inesistente in v2), `user_id=1`.

**Stato iniziale**
Nessuna organizzazione con `id=999` in v2; nessuna riga `organization_user` presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a membership referencing a non-existent v2 organization is reported, not crashed"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: lo stage riporta `created=0, skipped=1`, un solo warning che contiene la stringa "organizzazione inesistente", e nessuna riga viene scritta in `organization_user` — l'esecuzione prosegue regolarmente senza eccezioni.

**Controlli negativi**
Il file di test copre anche il caso complementare (membership che referenzia un **utente** v2 inesistente, warning con "utente inesistente"), non richiesto da questo id specifico ma verificabile con lo stesso principio.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce, lo stage lancia un'eccezione non gestita, oppure la riga orfana viene comunque scritta.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato al di fuori del database di test.

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

## Documentazione e tag (US-204)

### F2-14 — Le documentation v1 vengono importate in v2 documentation_pages con l'id preservato e le colonne mappate

**Obiettivo**
Verificare che `DocumentationStage` importi ogni riga `documentations` del v1 in `documentation_pages` di v2 mantenendo l'id originale e applicando la mappatura di colonna corretta (`name`→`title`, `description`→`body`, `category` invariata), senza riprodurre la relazione `creator()` verso una colonna inesistente che il v1 aveva (anti-pattern esplicito, §16 del PRD).

**Riferimenti**
- Requisito/regola di dominio: PRD US-204, primo AC (`documentations` → `documentation_pages`, `id` conservato, nessuna relazione `creator()` riprodotta).
- Test automatico: `tests/Feature/Import/Stages/DocumentationStageTest.php` — `imports v1 documentations into v2 documentation_pages with the id preserved and columns mapped` (dump v1 con id `15`, `name = 'Servizio di Ticketing'`, `description = '<h2>Corpo della pagina</h2>'`, `category = 'internal'`; verifica `title`, `body`, `category`, `slug = 'servizio-di-ticketing'`, `pdf_path` null).
- File/componente applicativo rilevante: `app/Import/Stages/DocumentationStage.php`.
- Test correlato: F2-15 (slug provvisorio univoco), F2-16/F2-17 (stage `tags`, dipendente da questo).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta: lo stage è testato con una tabella `documentations` sqlite in-memory predisposta dal test (trait `InteractsWithLegacyDatabase`), non con dati reali.

**Dati di test**
Riga v1 `documentations`: `id = 15`, `name = 'Servizio di Ticketing'`, `description = '<h2>Corpo della pagina</h2>'`, `category = 'internal'`.

**Stato iniziale**
Non applicabile: `documentation_pages` è vuota prima dell'esecuzione dello stage nel test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "imports v1 documentations into v2 documentation_pages with the id preserved and columns mapped"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la riga `documentation_pages` con id `15` esiste, con `title = 'Servizio di Ticketing'`, `body = '<h2>Corpo della pagina</h2>'`, `category = 'internal'`, `slug = 'servizio-di-ticketing'`, `pdf_path` nullo, e i contatori dello stage riportano `read=1`, `created=1`, `updated=0`, `skipped=0`.

**Controlli negativi**
Nessuno applicabile: il file di test copre separatamente idempotenza (F2-15/re-run), dry-run e `--limit` come varianti dello stesso stage.

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

### F2-15 — Viene generato uno slug provvisorio univoco quando due documentation v1 condividono lo stesso nome

**Obiettivo**
Verificare che, quando due righe `documentations` v1 hanno lo stesso `name`, `DocumentationStage` generi comunque due slug distinti in `documentation_pages` (suffisso numerico sul duplicato), tramite il trait riusabile `GeneratesProvisionalSlugs`, senza violare il vincolo unique sulla colonna `slug`.

**Riferimenti**
- Requisito/regola di dominio: PRD US-204 (slug generato con unicità garantita, suffisso numerico sui duplicati; slug provvisorio, il ricalcolo definitivo è delegato allo stage `derive`, US-215).
- Test automatico: `tests/Feature/Import/Stages/DocumentationStageTest.php` — `generates a unique provisional slug when two v1 documentations share the same name` (due righe v1 con id `11`/`12`, stesso `name = 'Procedura'`; verifica `slug = 'procedura'` per la prima e `slug = 'procedura-2'` per la seconda).
- File/componente applicativo rilevante: `app/Import/Stages/DocumentationStage.php`, `app/Import/Stages/Concerns/GeneratesProvisionalSlugs.php`.
- Test correlato: F2-14, F2-62 (rigenerazione slug definitivi in `derive`).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta: fixture sqlite in-memory.

**Dati di test**
Due righe v1 `documentations`: id `11` e id `12`, entrambe con `name = 'Procedura'`.

**Stato iniziale**
Non applicabile: `documentation_pages` è vuota prima dell'esecuzione.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "generates a unique provisional slug when two v1 documentations share the same name"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la pagina con id `11` ha slug `procedura`, quella con id `12` ha slug `procedura-2`.

**Controlli negativi**
Nessuno applicabile: il caso "nessun duplicato" è coperto da F2-14 come test separato.

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

### F2-16 — Il legame con una Documentation viene preservato come foreign key esplicita documentation_id

**Obiettivo**
Verificare che `TagsStage` riconosca il morph polimorfico v1 `tags.taggable_type = 'App\Models\Documentation'` e lo traduca nella FK esplicita `tags.documentation_id` di v2, quando la pagina di documentazione collegata (`taggable_id`) esiste già in v2 (importata da `DocumentationStage`, dipendenza dichiarata dello stage).

**Riferimenti**
- Requisito/regola di dominio: PRD US-204, secondo AC (il morph polimorfico v1 collassa a tag semplici, tranne il link a Documentation che diventa `documentation_id`).
- Test automatico: `tests/Feature/Import/Stages/TagsStageTest.php` — `preserves the link to Documentation as an explicit documentation_id foreign key` (pagina v2 `documentation_pages` id `17` pre-esistente; tag v1 id `40`, `taggable_id = 17`, `taggable_type = 'App\Models\Documentation'`; verifica `tags.documentation_id = 17` e nessun warning).
- File/componente applicativo rilevante: `app/Import/Stages/TagsStage.php`.
- Test correlato: F2-17 (stesso stage, link verso pagina inesistente).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta: fixture sqlite in-memory (tabella v1 `tags` creata dal test, riga v2 `documentation_pages` inserita direttamente nel test).

**Dati di test**
Riga v2 `documentation_pages` id `17` (pre-esistente). Riga v1 `tags`: id `40`, `name = 'Documentation: Procedura Operativa'`, `taggable_id = 17`, `taggable_type = 'App\Models\Documentation'`.

**Stato iniziale**
`documentation_pages` contiene solo la riga id `17` predisposta dal test; `tags` è vuota.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "preserves the link to Documentation as an explicit documentation_id foreign key"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: il tag v2 con id `40` ha `documentation_id = 17`, nessun warning riportato dallo stage.

**Controlli negativi**
Nessuno applicabile: il caso "link verso pagina inesistente" e "taggable_type diverso da Documentation" sono varianti coperte da test distinti nello stesso file (F2-17 e uno non in ambito manifest).

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

### F2-17 — Un legame a una Documentation verso una pagina v2 inesistente viene ridotto a tag semplice e segnalato, non manda in crash lo stage

**Obiettivo**
Verificare che, quando un tag v1 punta (`taggable_type = 'App\Models\Documentation'`) a un `taggable_id` che non esiste come `documentation_pages` in v2, `TagsStage` importi comunque il tag come tag semplice (`documentation_id = null`) invece di fallire, e registri un warning esplicito con l'id della pagina mancante.

**Riferimenti**
- Requisito/regola di dominio: PRD US-204 (righe orfane/con link perso segnalate, non un crash) — coerente con il pattern generale di gestione degli orfani applicato in tutti gli stage di questa fase.
- Test automatico: `tests/Feature/Import/Stages/TagsStageTest.php` — `a Documentation link to a non-existent v2 page is collapsed to a plain tag and reported, not crashed` (tag v1 id `39`, `taggable_id = 16` mai importato in `documentation_pages`; verifica `documentation_id` nullo e un warning contenente `documentazione #16 collegata inesistente`).
- File/componente applicativo rilevante: `app/Import/Stages/TagsStage.php`.
- Test correlato: F2-16.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta: fixture sqlite in-memory.

**Dati di test**
Riga v1 `tags`: id `39`, `name = 'Documentation: cartelle CAI'`, `taggable_id = 16`, `taggable_type = 'App\Models\Documentation'` (nessuna pagina v2 con id `16` esiste).

**Stato iniziale**
`documentation_pages` e `tags` (v2) sono vuote.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a Documentation link to a non-existent v2 page is collapsed to a plain tag and reported, not crashed"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: il tag v2 con id `39` viene comunque creato (`created = 1`), `documentation_id` è nullo, e lo stage riporta esattamente un warning contenente la stringa "documentazione #16 collegata inesistente" — nessuna eccezione propagata.

**Controlli negativi**
Nessuno applicabile: il comportamento "non crashare" è l'oggetto stesso del test.

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

## Mappatura ticket (US-205)

### F2-18 — Una story v1 viene importata nei ticket v2 con l'id preservato e la mappatura principale applicata

**Obiettivo**
Verificare che `TicketsStage` importi una riga `stories` v1 in `tickets` v2 mantenendo l'id, applicando la mappatura di colonna completa: titolo/descrizione, normalizzazione di tipo e priorità, risoluzione di richiedente/assegnatario/tester dagli id utente v1 (`creator_id`/`user_id`/`tester_id` → `requester_id`/`assignee_id`/`tester_id`, §0.3 del PRD), URL di staging/produzione, ore stimate, e `worked_minutes` fissato a `0` (il valore reale arriva solo dallo stage `derive`, US-215).

**Riferimenti**
- Requisito/regola di dominio: PRD US-205 (id conservato; colonne v1 fuori scope escluse esplicitamente; `worked_minutes` importato come `0` in questa story).
- Test automatico: `tests/Feature/Import/Stages/TicketsStageTest.php` — `imports a v1 story into v2 tickets with the id preserved and the main mapping applied` (story v1 id `42`, `type = 'Bug'`, `priority = 3`, con richiedente/assegnatario/tester utenti v2 già esistenti; verifica `title`, `description`, `status = 'progress'`, `type = 'bug'`, `priority = 'high'`, `requester_id`/`assignee_id`/`tester_id`, `staging_url`/`production_url`, `estimated_hours = 4.5`, `worked_minutes = 0`, `parent_id` nullo).
- File/componente applicativo rilevante: `app/Import/Stages/TicketsStage.php`.
- Test correlato: F2-19, F2-20, F2-21, F2-22.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta: fixture sqlite in-memory (tabelle v1 `stories`/`story_logs`, utenti v2 creati con la factory).

**Dati di test**
Story v1 id `42`: `name = 'Errore login'`, `description = 'Il cliente non riesce ad accedere'`, `status = 'progress'`, `type = 'Bug'`, `priority = 3`, `creator_id`/`user_id`/`tester_id` = id di tre utenti v2 di factory, `test_dev`/`test_prod` valorizzati, `estimated_hours = 4.5`; un `story_logs` con `status = 'progress'`.

**Stato iniziale**
`tickets` è vuota prima dell'esecuzione dello stage nel test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "imports a v1 story into v2 tickets with the id preserved and the main mapping applied"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: il ticket v2 con id `42` esiste con tutti i campi mappati come sopra, `worked_minutes = 0`, `parent_id` nullo, e i contatori dello stage riportano `read=1`, `created=1`, `updated=0`, `skipped=0`, nessun warning.

**Controlli negativi**
Nessuno applicabile: i casi limite di tipo/priorità fuori enum sono coperti da test separati nello stesso file, non in ambito di questo id del manifest.

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

### F2-19 — status_changed_at viene derivato dal più recente cambio di stato in story_logs

**Obiettivo**
Verificare che `TicketsStage` calcoli `tickets.status_changed_at` (colonna che non esiste nel v1) individuando, tra tutti i `story_logs` del ticket, il più recente che porta un cambio di `status`, ignorando i log con altre chiavi (es. `creator_id`) anche se più recenti.

**Riferimenti**
- Requisito/regola di dominio: PRD US-205 (`status_changed_at` derivato dal `story_logs` più recente con cambio di stato; fallback a `stories.updated_at` se assente, con segnalazione).
- Test automatico: `tests/Feature/Import/Stages/TicketsStageTest.php` — `status_changed_at is derived from the most recent story_logs status change` (log con `status=assigned` il 2026-01-01, `status=progress` il 2026-01-03, e un log senza `status` — solo `creator_id` — il 2026-01-04, più recente ma non un cambio di stato; verifica `status_changed_at = '2026-01-03 12:00:00'`, nessun warning).
- File/componente applicativo rilevante: `app/Import/Stages/TicketsStage.php`.
- Test correlato: F2-18, F2-20.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta: fixture sqlite in-memory.

**Dati di test**
Story v1 id `1`, `status = 'progress'`, `updated_at = '2026-01-05 09:00:00'`; tre `story_logs`: `{status: assigned}` il 2026-01-01 10:00, `{status: progress}` il 2026-01-03 12:00, `{creator_id: '41'}` (nessun cambio di stato) il 2026-01-04 08:00.

**Stato iniziale**
`tickets` è vuota prima dell'esecuzione dello stage nel test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "status_changed_at is derived from the most recent story_logs status change"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `tickets.status_changed_at` per il ticket id `1` vale esattamente `2026-01-03 12:00:00` (il log di cambio stato più recente, non il log più recente in assoluto), nessun warning riportato.

**Controlli negativi**
Il caso complementare (nessun log di stato disponibile, fallback su `stories.updated_at` con segnalazione) è coperto da un test distinto nello stesso file, non in ambito di questo id del manifest.

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

### F2-20 — Per un ticket in waiting, previous_status risale i log fino al primo stato diverso da waiting/problem

**Obiettivo**
Verificare che `TicketsStage` ricostruisca `tickets.previous_status` per un ticket importato nello stato `waiting` risalendo la sequenza dei `story_logs` fino al primo stato diverso sia da `waiting` sia da `problem`, saltando eventuali stati intermedi che sono anch'essi `waiting`/`problem`.

**Riferimenti**
- Requisito/regola di dominio: PRD US-205 (`previous_status`: per ticket in `waiting`/`problem`, risale ai log fino al primo stato diverso da `waiting`/`problem`; fallback `new` con segnalazione se non ricostruibile).
- Test automatico: `tests/Feature/Import/Stages/TicketsStageTest.php` — `previous_status for a waiting ticket walks back the logs to the first status different from waiting/problem` (sequenza log `assigned` → `progress` → `problem` → `waiting`; verifica `previous_status = 'progress'`, nessun warning).
- File/componente applicativo rilevante: `app/Import/Stages/TicketsStage.php`.
- Test correlato: F2-18, F2-19.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta: fixture sqlite in-memory.

**Dati di test**
Story v1 id `1`, `status = 'waiting'`; quattro `story_logs` in ordine cronologico: `{status: assigned}` (2026-01-01), `{status: progress}` (2026-01-02), `{status: problem}` (2026-01-03), `{status: waiting}` (2026-01-04).

**Stato iniziale**
`tickets` è vuota prima dell'esecuzione dello stage nel test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "previous_status for a waiting ticket walks back the logs to the first status different from waiting/problem"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `tickets.previous_status` per il ticket id `1` vale `progress` (non `problem`, che precede `waiting` nei log ma è anch'esso uno stato da saltare), nessun warning riportato.

**Controlli negativi**
Il caso complementare (nessuno stato precedente ricostruibile, fallback su `new` con segnalazione) e il caso "ticket non in waiting/problem" (previous_status resta nullo) sono coperti da test distinti nello stesso file, non in ambito di questo id del manifest.

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

### F2-21 — Un riferimento utente verso un utente v2 inesistente viene azzerato e segnalato, non manda in crash lo stage

**Obiettivo**
Verificare che, quando `stories.creator_id`/`user_id`/`tester_id` puntano a id utente che non esistono in v2, `TicketsStage` importi comunque il ticket con `requester_id`/`assignee_id`/`tester_id` nulli invece di fallire, e riporti un unico warning aggregato col conteggio totale dei riferimenti azzerati.

**Riferimenti**
- Requisito/regola di dominio: PRD US-205 (dipende da `users`; un riferimento utente inesistente è un caso limite da gestire senza interrompere lo stage).
- Test automatico: `tests/Feature/Import/Stages/TicketsStageTest.php` — `a user reference to a non-existent v2 user is nulled out and reported, not crashed` (story v1 id `1` con `creator_id=999`, `user_id=998`, `tester_id=997`, nessuno dei tre esistente in v2; verifica i tre campi v2 nulli e un warning contenente "3 riferimenti utente").
- File/componente applicativo rilevante: `app/Import/Stages/TicketsStage.php`.
- Test correlato: F2-18.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta: fixture sqlite in-memory.

**Dati di test**
Story v1 id `1`: `creator_id = 999`, `user_id = 998`, `tester_id = 997` (nessun utente v2 con questi id).

**Stato iniziale**
`tickets` è vuota prima dell'esecuzione dello stage nel test; nessun utente con id 997/998/999 esiste in v2.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a user reference to a non-existent v2 user is nulled out and reported, not crashed"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: il ticket v2 id `1` viene creato con `requester_id`, `assignee_id`, `tester_id` tutti nulli, e lo stage riporta esattamente un warning contenente la stringa "3 riferimenti utente" — nessuna eccezione propagata.

**Controlli negativi**
Nessuno applicabile: il comportamento "non crashare" è l'oggetto stesso del test.

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

### F2-22 — Rieseguire lo stage sullo stesso dump è idempotente: la seconda esecuzione si limita a saltare

**Obiettivo**
Verificare che una seconda esecuzione di `TicketsStage` sullo stesso dump v1 non crei né aggiorni alcun ticket già importato correttamente, limitandosi a saltarli (idempotenza, criterio di accettazione esplicito della Fase 2).

**Riferimenti**
- Requisito/regola di dominio: PRD §2 (idempotenza, "una seconda esecuzione consecutiva sullo stesso dump non duplica né corrompe nulla"), US-205.
- Test automatico: `tests/Feature/Import/Stages/TicketsStageTest.php` — `re-running the stage on the same dump is idempotent: second run only skips` (due story v1, una con storico di stato che porta a `waiting`; esegue lo stage due volte; verifica `created=2` alla prima esecuzione, `created=0`/`updated=0`/`skipped=2` alla seconda, `tickets` conta sempre 2 righe).
- File/componente applicativo rilevante: `app/Import/Stages/TicketsStage.php`.
- Test correlato: F2-18, F2-19, F2-20, F2-21.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta: fixture sqlite in-memory.

**Dati di test**
Due story v1: id `1` (con storico di stato `progress` → `waiting`), id `2` (nessuna variante particolare).

**Stato iniziale**
`tickets` è vuota prima della prima esecuzione dello stage nel test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "re-running the stage on the same dump is idempotent: second run only skips"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la prima esecuzione crea 2 ticket (`created=2`); la seconda esecuzione, sullo stesso dump, non crea né aggiorna nulla (`created=0`, `updated=0`, `skipped=2`), e `tickets` continua a contare esattamente 2 righe.

**Controlli negativi**
Un test correlato nello stesso file (non in ambito di questo id) verifica il caso complementare: un valore v1 realmente cambiato produce un `update` mirato senza toccare `status_changed_at`/`previous_status`/`worked_minutes` già derivati.

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

## Gerarchia dei ticket (US-206)

### F2-23 — Una gerarchia coerente a un livello da stories.parent_id viene applicata così com'è

**Obiettivo**
Verificare che `TicketHierarchyStage` applichi direttamente a `tickets.parent_id` di v2 una relazione padre/figlio a un solo livello già coerente in `stories.parent_id` v1, senza segnalazioni.

**Riferimenti**
- Requisito/regola di dominio: PRD US-206 (`stories.parent_id` è la fonte primaria della gerarchia; dipende da `tickets`, US-205).
- Test automatico: `tests/Feature/Import/Stages/TicketHierarchyStageTest.php` — `a coherent one-level hierarchy from stories.parent_id is applied as-is` (story v1 id `2` con `parent_id = 1`, entrambi i ticket v2 già presenti; verifica `tickets.parent_id` del ticket `2` = `1`, quello del ticket `1` resta nullo, nessun warning).
- File/componente applicativo rilevante: `app/Import/Stages/TicketHierarchyStage.php`.
- Test correlato: F2-24, F2-25.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta: fixture sqlite in-memory (tabelle v1 `stories`/`story_story`, ticket v2 inseriti direttamente dal test).

**Dati di test**
Story v1: id `1` (nessun padre), id `2` con `parent_id = 1`. Ticket v2 corrispondenti già presenti (id `1` e `2`).

**Stato iniziale**
`tickets` contiene già le righe id `1` e `2` (senza `parent_id` valorizzato) predisposte dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a coherent one-level hierarchy from stories.parent_id is applied as-is"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `tickets.parent_id` del ticket id `2` vale `1`, quello del ticket id `1` resta nullo, `read=1`/`updated=1`, nessun warning.

**Controlli negativi**
Nessuno applicabile: i conflitti tra fonti e la profondità >1 sono varianti coperte da test distinti (F2-24, e un test aggiuntivo su conflitto padre singolo/pivot non in ambito di questo manifest).

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

### F2-24 — Una gerarchia a 2+ livelli viene appiattita sull'antenato più in alto e segnalata

**Obiettivo**
Verificare che, quando la gerarchia v1 ha più di un livello (es. nonno → padre → figlio), `TicketHierarchyStage` la appiattisca sul vincolo di profondità massima 1 di v2 collegando ogni discendente direttamente all'antenato più in alto, riportando un warning con il conteggio dei ticket coinvolti.

**Riferimenti**
- Requisito/regola di dominio: PRD US-206 (una gerarchia che violerebbe la profondità massima 1 viene appiattita e segnalata nel report).
- Test automatico: `tests/Feature/Import/Stages/TicketHierarchyStageTest.php` — `a 2+ level hierarchy is flattened onto the topmost ancestor and reported` (catena v1 `1` ← `2` ← `3`, tre livelli; verifica sia il ticket `2` sia il ticket `3` risultano `parent_id = 1` in v2, e un warning contenente "1 ticket con una gerarchia a più di un livello").
- File/componente applicativo rilevante: `app/Import/Stages/TicketHierarchyStage.php`.
- Test correlato: F2-23, F2-25.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta: fixture sqlite in-memory.

**Dati di test**
Story v1: id `1` (nessun padre), id `2` con `parent_id = 1`, id `3` con `parent_id = 2` (catena a 3 livelli). Ticket v2 corrispondenti già presenti.

**Stato iniziale**
`tickets` contiene già le righe id `1`, `2`, `3` (senza `parent_id` valorizzato) predisposte dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a 2+ level hierarchy is flattened onto the topmost ancestor and reported"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: sia il ticket id `2` sia il ticket id `3` risultano con `parent_id = 1` (l'antenato più in alto della catena), e lo stage riporta esattamente un warning contenente "1 ticket con una gerarchia a più di un livello".

**Controlli negativi**
Nessuno applicabile: il caso a un solo livello (nessun appiattimento necessario) è coperto da F2-23 come test separato.

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

### F2-25 — Un riferimento al genitore verso un ticket v2 inesistente viene azzerato e segnalato, non manda in crash lo stage

**Obiettivo**
Verificare che, quando `stories.parent_id` punta a un id che non esiste come ticket in v2, `TicketHierarchyStage` azzeri `tickets.parent_id` invece di fallire, e riporti un warning col conteggio dei riferimenti al padre inesistenti.

**Riferimenti**
- Requisito/regola di dominio: PRD US-206 (gestione degli orfani, coerente con il pattern generale di questa fase: mai un crash su un riferimento v1 non risolvibile).
- Test automatico: `tests/Feature/Import/Stages/TicketHierarchyStageTest.php` — `a parent reference to a non-existent v2 ticket is nulled out and reported, not crashed` (story v1 id `1` con `parent_id = 999`, nessun ticket v2 con id `999`; verifica `parent_id` nullo e un warning contenente "1 riferimenti a un ticket padre inesistente in v2").
- File/componente applicativo rilevante: `app/Import/Stages/TicketHierarchyStage.php`.
- Test correlato: F2-23, F2-24.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta: fixture sqlite in-memory.

**Dati di test**
Story v1 id `1` con `parent_id = 999` (nessun ticket v2 con id `999`). Ticket v2 id `1` già presente.

**Stato iniziale**
`tickets` contiene già la riga id `1` (senza `parent_id` valorizzato) predisposta dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a parent reference to a non-existent v2 ticket is nulled out and reported, not crashed"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `tickets.parent_id` del ticket id `1` resta nullo, e lo stage riporta esattamente un warning contenente la stringa "1 riferimenti a un ticket padre inesistente in v2" — nessuna eccezione propagata.

**Controlli negativi**
Nessuno applicabile: il comportamento "non crashare" è l'oggetto stesso del test.

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

## Tag e partecipanti dei ticket (US-207)

### F2-26 — La pivot v1 ticket<->tag viene importata in v2, ignorando il lato Documentation

**Obiettivo**
Verificare che `TicketTagsStage` importi la pivot polimorfica v1 `taggables` in `ticket_tag` v2 filtrando esplicitamente le righe con `taggable_type = 'App\Models\Story'` e ignorando del tutto (non solo scartandole a valle, ma non leggendole affatto) le righe con `taggable_type = 'App\Models\Documentation'` — quel lato è già stato assorbito come FK esplicita `documentation_id` dallo stage `tags` (US-204, F2-16).

**Riferimenti**
- Requisito/regola di dominio: PRD US-207 (stage `ticket_tags`); §3.2 (regola sul morph polimorfico v1: solo il lato Documentation diventa FK esplicita, il resto collassa a tag semplice, e il lato Story alimenta questa pivot).
- Test automatico: `tests/Feature/Import/Stages/TicketTagsStageTest.php` — `imports the v1 ticket<->tag pivot into v2, ignoring the Documentation side` (due righe `taggables` sullo stesso `tag_id`, una con `taggable_type = App\Models\Story`, una con `App\Models\Documentation`; verifica che solo la prima venga letta e importata).
- File/componente applicativo rilevante: `app/Import/Stages/TicketTagsStage.php` (query `where('taggable_type', 'App\\Models\\Story')`).
- Test correlato: F2-16 (lato Documentation), F2-27.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta: il test usa il trait `InteractsWithLegacyDatabase` (`useSqliteLegacyConnection()`) e crea da sé la tabella legacy `taggables` in sqlite in-memory.

**Dati di test**
- Ticket v2 `id = 1`, tag v2 `id = 16`.
- Due righe `taggables` v1 sullo stesso `tag_id = 16`: una con `taggable_id = 1, taggable_type = 'App\Models\Story'`, una con `taggable_id = 99, taggable_type = 'App\Models\Documentation'`.

**Stato iniziale**
Non applicabile: esecuzione di uno stage contro fixture predisposte in memoria dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "imports the v1 ticket<->tag pivot into v2, ignoring the Documentation side"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il risultato dello stage riporta `read = 1` (solo la riga lato Story viene anche solo letta), `created = 1`, `skipped = 0`, nessun warning; la riga `ticket_tag(ticket_id = 1, tag_id = 16)` esiste in v2. La riga lato Documentation non produce alcuna riga né alcun conteggio, nemmeno come scarto.

**Controlli negativi**
Nessuno applicabile: l'esclusione del lato Documentation è già verificata dal conteggio `read = 1` (non due), non da un caso di errore separato.

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

### F2-27 — Un legame a un tag v2 inesistente viene segnalato, non manda in crash lo stage

**Obiettivo**
Verificare che `TicketTagsStage` non vada in crash quando una riga `taggables` referenzia un `tag_id` assente in v2, ma la scarti in modo controllato (contata in `skipped`, mai in `created`) e la segnali in un warning esplicito, senza scrivere alcuna riga pivot.

**Riferimenti**
- Requisito/regola di dominio: PRD US-207 (test di orfani per lo stage `ticket_tags`).
- Test automatico: `tests/Feature/Import/Stages/TicketTagsStageTest.php` — `a tag link referencing a non-existent v2 tag is reported, not crashed` (ticket v2 esistente, riga `taggables` con `tag_id = 999` mai importato in v2).
- File/componente applicativo rilevante: `app/Import/Stages/TicketTagsStage.php` (warning `"Associazione v1 ticket #%d ↔ tag #%d scartata: tag inesistente in v2."`).
- Test correlato: F2-26.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (fixture sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Ticket v2 `id = 1`; nessun tag v2 con `id = 999`.
- Riga `taggables` v1: `tag_id = 999, taggable_id = 1, taggable_type = 'App\Models\Story'`.

**Stato iniziale**
Non applicabile: esecuzione di uno stage contro fixture predisposte in memoria dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a tag link referencing a non-existent v2 tag is reported, not crashed"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il risultato dello stage riporta `created = 0`, `skipped = 1`, esattamente 1 warning contenente la stringa "tag inesistente"; la tabella `ticket_tag` resta vuota.

**Controlli negativi**
Coincide col caso positivo: questo stesso test È il controllo negativo per un tag v1 orfano (nessuna eccezione/crash, solo uno scarto segnalato).

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

### F2-28 — La pivot v1 ticket<->partecipante viene importata in v2

**Obiettivo**
Verificare che `TicketParticipantsStage` importi la pivot esplicita v1 `story_participants` in `ticket_participants` v2, e che produca sempre — anche quando l'importazione riesce senza alcuno scarto — un warning informativo col conteggio totale di righe lette, perché il PRD si aspetta che questo numero resti vicino a zero (§6.1.7): non è un compromesso applicato, è un dato informativo per `v1:validate`.

**Riferimenti**
- Requisito/regola di dominio: PRD US-207 (stage `ticket_participants`); §6.1.7 del PRD principale (conteggio atteso vicino a zero).
- Test automatico: `tests/Feature/Import/Stages/TicketParticipantsStageTest.php` — `imports the v1 ticket<->participant pivot into v2` (un ticket, un utente, una riga `story_participants`; verifica sia l'inserimento della pivot sia il warning informativo `"1 partecipazioni esplicite lette dal v1 ..."`).
- File/componente applicativo rilevante: `app/Import/Stages/TicketParticipantsStage.php`.
- Test correlato: F2-29.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (fixture sqlite in-memory via `InteractsWithLegacyDatabase`, tabella legacy `story_participants` creata dal test).

**Dati di test**
- Ticket v2 `id = 1`; utente v2 `id = 1` (factory).
- Riga `story_participants` v1: `story_id = 1, user_id = 1`.

**Stato iniziale**
Non applicabile: esecuzione di uno stage contro fixture predisposte in memoria dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "imports the v1 ticket<->participant pivot into v2"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il risultato dello stage riporta `read = 1`, `created = 1`, `skipped = 0`, esattamente 1 warning contenente "1 partecipazioni esplicite lette dal v1"; la riga `ticket_participants(ticket_id = 1, user_id = 1)` esiste in v2.

**Controlli negativi**
Nessuno applicabile per questo test: il warning presente non segnala un problema, è puramente informativo.

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

### F2-29 — Una partecipazione che referenzia un utente v2 inesistente viene segnalata, non manda in crash lo stage

**Obiettivo**
Verificare che `TicketParticipantsStage` non vada in crash quando una riga `story_participants` referenzia uno `user_id` assente in v2, ma la scarti (contata in `skipped`) e la segnali in un warning esplicito, senza scrivere alcuna riga pivot.

**Riferimenti**
- Requisito/regola di dominio: PRD US-207 (test di orfani per lo stage `ticket_participants`).
- Test automatico: `tests/Feature/Import/Stages/TicketParticipantsStageTest.php` — `a participation referencing a non-existent v2 user is reported, not crashed` (ticket v2 esistente, riga `story_participants` con `user_id = 999` mai importato in v2).
- File/componente applicativo rilevante: `app/Import/Stages/TicketParticipantsStage.php` (warning `"Partecipazione v1 ticket #%d ↔ utente #%d scartata: utente inesistente in v2."`).
- Test correlato: F2-28.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (fixture sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Ticket v2 `id = 1`; nessun utente v2 con `id = 999`.
- Riga `story_participants` v1: `story_id = 1, user_id = 999`.

**Stato iniziale**
Non applicabile: esecuzione di uno stage contro fixture predisposte in memoria dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a participation referencing a non-existent v2 user is reported, not crashed"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il risultato dello stage riporta `created = 0`, `skipped = 1`, un warning contenente la stringa "utente inesistente"; la tabella `ticket_participants` resta vuota.

**Controlli negativi**
Coincide col caso positivo: questo stesso test È il controllo negativo per un riferimento utente orfano (nessuna eccezione/crash, solo uno scarto segnalato).

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

## Log dei ticket (US-208)

### F2-30 — Un delta di stato diventa un evento status_changed con from_status derivato dal log precedente

**Obiettivo**
Verificare che `TicketLogsStage` traduca il JSON libero `story_logs.changes` del v1 nelle colonne esplicite `event`/`from_status`/`to_status` di v2 quando la chiave `status` è presente: l'evento risultante è sempre `status_changed`, `to_status` è il valore della chiave, e `from_status` è ricostruito dal cambio di stato precedente dello stesso ticket (`null` se è il primo). Questa traduzione è, secondo il PRD, la correzione strutturale più importante rispetto al v1.

**Riferimenti**
- Requisito/regola di dominio: PRD US-208 (stage `ticket_logs`), §5.2/§6.2.1 del PRD principale; regola di priorità mutuamente esclusiva `status` > `user_id` > fallback `updated` (docblock di `TicketLogsStage`).
- Test automatico: `tests/Feature/Import/Stages/TicketLogsStageTest.php` — `a status delta becomes a status_changed event with from_status derived from the previous log` (due righe `story_logs` sullo stesso ticket, `status = assigned` poi `status = released`; verifica entrambi i log risultanti in ordine).
- File/componente applicativo rilevante: `app/Import/Stages/TicketLogsStage.php` (metodo `resolveEvent()`).
- Test correlato: F2-31, F2-32, F2-33.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (fixture sqlite in-memory via `InteractsWithLegacyDatabase`, tabella legacy `story_logs` creata dal test).

**Dati di test**
- Ticket v2 `id = 1`; un utente autore.
- Due righe `story_logs` v1 sullo stesso `story_id = 1`: `changes = {"status":"assigned"}` a `2026-01-01 10:00:00`, poi `changes = {"status":"released"}` a `2026-01-02 10:00:00`.

**Stato iniziale**
Non applicabile: esecuzione di uno stage contro fixture predisposte in memoria dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a status delta becomes a status_changed event with from_status derived from the previous log"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il risultato dello stage riporta `read = 2`, `created = 2`, `skipped = 0`, nessun warning. Il primo `ticket_logs` (in ordine di id) ha `event = status_changed`, `from_status = null`, `to_status = assigned`; il secondo ha `event = status_changed`, `from_status = assigned`, `to_status = released`.

**Controlli negativi**
Nessuno applicabile: il comportamento sui rami alternativi del JSON (`user_id`-only → `assigned`; altre chiavi → `updated`) è coperto da altri test dello stesso file non elencati nel manifest di questa fase.

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

### F2-31 — Un log con solo la chiave "watch" viene escluso e segnalato, non importato come ticket_log

**Obiettivo**
Verificare che `TicketLogsStage` escluda dalla propria importazione le righe `story_logs` la cui unica chiave presente nel JSON `changes` è `watch` (destinate invece allo stage `ticket_views`, US-209), contandole come `skipped` e producendo un warning esplicito, senza scrivere alcuna riga `ticket_logs`.

**Riferimenti**
- Requisito/regola di dominio: PRD US-208 (esclusione dei log "solo watch"); US-209 (destinazione alternativa).
- Test automatico: `tests/Feature/Import/Stages/TicketLogsStageTest.php` — `a log with only the watch key is excluded and reported, not imported as a ticket_log` (una riga `story_logs` con `changes = {"watch": "..."}`).
- File/componente applicativo rilevante: `app/Import/Stages/TicketLogsStage.php` (controllo `array_keys($changes) === ['watch']`).
- Test correlato: F2-34, F2-36 (mutua esclusione con `ticket_views`).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (fixture sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Ticket v2 `id = 1`; un utente autore.
- Riga `story_logs` v1: `changes = {"watch": "2026-01-01 10:00:00"}`.

**Stato iniziale**
Non applicabile: esecuzione di uno stage contro fixture predisposte in memoria dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a log with only the watch key is excluded and reported, not imported as a ticket_log"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il risultato dello stage riporta `read = 1`, `created = 0`, `skipped = 1`, esattamente 1 warning contenente la stringa `sola chiave "watch"`; la tabella `ticket_logs` resta vuota.

**Controlli negativi**
Coincide col caso positivo: questo stesso test verifica che il log non generi mai una riga `ticket_logs`.

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

### F2-32 — Un log senza autore risolvibile ricade sull'utente di sistema

**Obiettivo**
Verificare che `TicketLogsStage` attribuisca all'utente di sistema (`User::system()`) un log `story_logs` il cui `user_id` è `null` (o non risolvibile in v2), segnalandolo in un warning e marcando la riga `ticket_logs` risultante con `is_system = true`.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.2.1 (fallback all'utente di sistema per un autore mancante).
- Test automatico: `tests/Feature/Import/Stages/TicketLogsStageTest.php` — `a log without a resolvable author falls back to the system user` (una riga `story_logs` con `user_id = null` e `changes = {"status":"assigned"}`).
- File/componente applicativo rilevante: `app/Import/Stages/TicketLogsStage.php` (fallback `$systemUserId ??= User::system()->id`).
- Test correlato: F2-30.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (fixture sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Ticket v2 `id = 1`.
- Riga `story_logs` v1: `user_id = null`, `changes = {"status": "assigned"}`.

**Stato iniziale**
Non applicabile: esecuzione di uno stage contro fixture predisposte in memoria dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a log without a resolvable author falls back to the system user"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il risultato dello stage riporta `created = 1`, un warning contenente la stringa "utente di sistema". La riga `ticket_logs` creata ha `user_id` pari all'id di `User::system()` e `is_system = true`.

**Controlli negativi**
Nessuno applicabile: il caso "autore risolvibile" è coperto da F2-30.

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

### F2-33 — Rieseguire lo stage sullo stesso dump è idempotente tramite import_mappings: la seconda esecuzione si limita a saltare

**Obiettivo**
Verificare che `TicketLogsStage` sia idempotente rieseguendolo due volte sullo stesso dump: essendo `ticket_logs` una tabella senza chiave naturale su cui ri-matchare, l'idempotenza è garantita registrando una riga `import_mappings` (`source_table = story_logs`, `target_table = ticket_logs`) per ogni log importato — la seconda esecuzione deve limitarsi a saltare (skip) ogni riga già mappata, senza duplicare nulla.

**Riferimenti**
- Requisito/regola di dominio: PRD §11.4 (stage 11, idempotenza tramite `import_mappings`).
- Test automatico: `tests/Feature/Import/Stages/TicketLogsStageTest.php` — `re-running the stage on the same dump is idempotent via import_mappings: second run only skips` (due righe `story_logs` sullo stesso ticket, stage eseguito due volte in sequenza).
- File/componente applicativo rilevante: `app/Import/Stages/TicketLogsStage.php`; `App\Import\Models\ImportMapping`.
- Test correlato: F2-30.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (fixture sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Ticket v2 `id = 1`; un utente autore.
- Due righe `story_logs` v1: `status = assigned` (2026-01-01), `status = released` (2026-01-02).

**Stato iniziale**
Non applicabile: esecuzione di uno stage contro fixture predisposte in memoria dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "re-running the stage on the same dump is idempotent via import_mappings: second run only skips"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
La prima esecuzione riporta `created = 2`; la seconda esecuzione, sullo stesso dump, riporta `created = 0` e `skipped = 2`. La tabella `ticket_logs` contiene esattamente 2 righe (non 4) e la tabella `import_mappings` contiene esattamente 2 righe con `target_table = ticket_logs`.

**Controlli negativi**
Coincide col caso positivo: l'assenza di duplicazione alla seconda esecuzione È il controllo.

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

## Visualizzazioni dei ticket (US-209)

### F2-34 — I log "solo watch" dello stesso giorno si aggregano in un'unica riga ticket_views

**Obiettivo**
Verificare che `TicketViewsStage` aggreghi, per ciascuna combinazione (ticket, utente, giorno), tutti i log `story_logs` con sola chiave `watch` in un'unica riga `ticket_views`, con `view_count` pari al numero di visualizzazioni del giorno e `last_viewed_at` pari al timestamp più recente del gruppo (non l'ultimo letto in ordine di id, il più recente in valore).

**Riferimenti**
- Requisito/regola di dominio: PRD US-209 (stage `ticket_views`), §6.2.3 del PRD principale.
- Test automatico: `tests/Feature/Import/Stages/TicketViewsStageTest.php` — `watch-only logs on the same day aggregate into a single ticket_views row` (tre righe `story_logs` "solo watch" per lo stesso ticket/utente/giorno, a 09:00, 14:30, 18:00).
- File/componente applicativo rilevante: `app/Import/Stages/TicketViewsStage.php` (raggruppamento in memoria per chiave `ticket_id|user_id|viewed_on`).
- Test correlato: F2-31, F2-36, F2-37.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (fixture sqlite in-memory via `InteractsWithLegacyDatabase`, tabella legacy `story_logs` creata dal test).

**Dati di test**
- Ticket v2 `id = 1`; un utente visualizzatore.
- Tre righe `story_logs` v1 con `changes = {"watch": "<stesso timestamp della riga>"}`, tutte il `2026-01-01`, rispettivamente alle 09:00:00, 14:30:00, 18:00:00.

**Stato iniziale**
Non applicabile: esecuzione di uno stage contro fixture predisposte in memoria dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "watch-only logs on the same day aggregate into a single ticket_views row"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il risultato dello stage riporta `read = 3`, `created = 1`, `skipped = 0`, nessun warning. L'unica riga `ticket_views` creata ha `ticket_id = 1`, `user_id` = l'utente visualizzatore, `viewed_on` nel giorno `2026-01-01`, `view_count = 3` e `last_viewed_at` alle 18:00:00 (il timestamp più recente del gruppo).

**Controlli negativi**
Nessuno applicabile: il caso "giorni diversi → righe separate" è coperto da un test omologo dello stesso file, non elencato in questo manifest.

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

### F2-35 — I log non "solo watch" vengono esclusi e segnalati, non importati come ticket_view

**Obiettivo**
Verificare che `TicketViewsStage` escluda dalla propria importazione le righe `story_logs` il cui JSON `changes` contiene chiavi diverse dalla sola `watch` (destinate invece allo stage `ticket_logs`, US-208), contandole come `skipped` e producendo un warning esplicito, senza scrivere alcuna riga `ticket_views`.

**Riferimenti**
- Requisito/regola di dominio: PRD US-209 (filtro complementare a `ticket_logs`).
- Test automatico: `tests/Feature/Import/Stages/TicketViewsStageTest.php` — `logs that are not watch-only are excluded and reported, not imported as a ticket_view` (una riga `story_logs` con `changes = {"status":"assigned"}`).
- File/componente applicativo rilevante: `app/Import/Stages/TicketViewsStage.php` (controllo `array_keys($changes) !== ['watch']`).
- Test correlato: F2-31, F2-36.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (fixture sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Ticket v2 `id = 1`; un utente visualizzatore.
- Riga `story_logs` v1: `changes = {"status": "assigned"}` (non "solo watch").

**Stato iniziale**
Non applicabile: esecuzione di uno stage contro fixture predisposte in memoria dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "logs that are not watch-only are excluded and reported, not imported as a ticket_view"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il risultato dello stage riporta `read = 1`, `created = 0`, `skipped = 1`, esattamente 1 warning contenente la stringa `non hanno sola chiave "watch"`; la tabella `ticket_views` resta vuota.

**Controlli negativi**
Coincide col caso positivo: questo stesso test verifica che il log non generi mai una riga `ticket_views`.

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

### F2-36 — ticket_logs e ticket_views leggono lo stesso input story_logs senza alcuna sovrapposizione

**Obiettivo**
Verificare che `TicketLogsStage` (US-208) e `TicketViewsStage` (US-209), pur leggendo entrambi l'intera tabella `story_logs`, applichino filtri mutuamente esclusivi: ogni riga finisce esattamente in una delle due destinazioni (`ticket_logs` oppure `ticket_views`), mai in entrambe e mai persa, prevenendo sia un doppio conteggio silenzioso sia una perdita di dati storici.

**Riferimenti**
- Requisito/regola di dominio: PRD US-209 ("chiarire nel codice che sono due stage letti dalla stessa tabella `story_logs` ma filtrati in modo mutuamente esclusivo").
- Test automatico: `tests/Feature/Import/Stages/TicketViewsStageTest.php` — `ticket_logs and ticket_views read the same story_logs input with zero overlap` (due righe "solo watch" su giorni diversi + una riga con `changes = {"status":"assigned"}`; entrambi gli stage vengono eseguiti sullo stesso set di 3 righe).
- File/componente applicativo rilevante: `app/Import/Stages/TicketLogsStage.php`, `app/Import/Stages/TicketViewsStage.php`.
- Test correlato: F2-30, F2-31, F2-34, F2-35.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (fixture sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Ticket v2 `id = 1`; un utente visualizzatore.
- Tre righe `story_logs` v1: due "solo watch" su giorni diversi (`2026-01-01`, `2026-01-02`), una con `changes = {"status": "assigned"}` (`2026-01-03`).

**Stato iniziale**
Non applicabile: esecuzione di due stage contro le stesse fixture predisposte in memoria dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "ticket_logs and ticket_views read the same story_logs input with zero overlap"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Entrambi gli stage riportano `read = 3` (leggono l'intera tabella `story_logs`); `TicketLogsStage` produce `created = 1` (solo la riga con `status`) e la tabella `ticket_logs` contiene 1 riga; `TicketViewsStage` produce `created = 2` (le due righe "watch" su giorni diversi, non aggregabili) e la tabella `ticket_views` contiene 2 righe. La somma delle righe create (1 + 2 = 3) coincide col totale letto, senza alcuna riga contata due volte né persa.

**Controlli negativi**
Coincide col caso positivo: l'assenza di sovrapposizione È il controllo, verificato dal conteggio esatto delle righe prodotte da ciascuno stage.

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

### F2-37 — Rieseguire lo stage sullo stesso dump è idempotente: la seconda esecuzione si limita a saltare, nessuna riga duplicata

**Obiettivo**
Verificare che `TicketViewsStage` sia idempotente rieseguendolo due volte sullo stesso dump: l'idempotenza si appoggia al vincolo unique applicativo `(ticket_id, user_id, viewed_on)` già esistente in v2 — un gruppo il cui `ticket_views` esiste già viene saltato per intero (mai un secondo insert né un aggiornamento del `view_count`), garantendo che la seconda esecuzione non duplichi né alteri il conteggio delle visualizzazioni già registrate.

**Riferimenti**
- Requisito/regola di dominio: PRD US-209 (idempotenza sul vincolo unique `(ticket_id, user_id, viewed_on)`).
- Test automatico: `tests/Feature/Import/Stages/TicketViewsStageTest.php` — `re-running the stage on the same dump is idempotent: second run only skips, no duplicate rows` (due righe "solo watch" nello stesso giorno, stage eseguito due volte in sequenza).
- File/componente applicativo rilevante: `app/Import/Stages/TicketViewsStage.php` (controllo `exists()` sul gruppo prima dell'insert).
- Test correlato: F2-34.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (fixture sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Ticket v2 `id = 1`; un utente visualizzatore.
- Due righe `story_logs` v1 "solo watch" nello stesso giorno (`2026-01-01`, alle 09:00 e alle 14:00).

**Stato iniziale**
Non applicabile: esecuzione di uno stage contro fixture predisposte in memoria dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "re-running the stage on the same dump is idempotent: second run only skips, no duplicate rows"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
La prima esecuzione riporta `created = 1` (le due righe si aggregano nell'unica riga del giorno). La seconda esecuzione, sullo stesso dump, riporta `created = 0` e `skipped = 2` (entrambe le righe sorgente del gruppo vengono scartate perché il `ticket_views` del giorno esiste già). La tabella `ticket_views` contiene esattamente 1 riga, con `view_count` rimasto a 2 (non raddoppiato a 4 dalla seconda esecuzione).

**Controlli negativi**
Coincide col caso positivo: l'assenza di duplicazione e l'invarianza del `view_count` alla seconda esecuzione SONO il controllo.

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

## Parser dei messaggi dei ticket (US-210)

### F2-38 — Una catena di reply prependute reale (story id 1641 dal dump v1) viene scomposta in ordine cronologico

**Obiettivo**
Verificare che `CustomerRequestParser::parse()` riconosca correttamente il template HTML esatto con cui il v1 prepende ogni nuova risposta (`"<Autore> ha risposto il: DD-MM-YYYY HH:MM"` seguito dal `<div>` colorato del corpo e dal separatore), e che lo scomponga in messaggi in ordine **cronologico** (dal più vecchio al più recente) invertendo l'ordine di prependimento del v1, estraendo per ciascun blocco autore e timestamp.

**Riferimenti**
- Requisito/regola di dominio: PRD US-210, AC "Parser che scompone i blocchi HTML prepesi del v1... l'ordine è invertito rispetto al v1"; CLAUDE.md sezione ETL, nota su `CustomerRequestParser`.
- Test automatico: `tests/Unit/Import/Parsers/CustomerRequestParserTest.php` — `a real prepended reply chain (story id 1641 from the v1 dump) is decomposed in chronological order` (usa un estratto HTML reale del dump v1, story id 1641: due autori — "Riccardo Bernasconi" e "OTCO/SO CCEC" — con due risposte prependute e il contenuto originale del ticket in coda).
- File/componente applicativo rilevante: `app/Import/Parsers/CustomerRequestParser.php`.
- Test correlato: F2-39, F2-40.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il parser è puro (nessun I/O), testato con una stringa HTML già estratta dal dump reale, incollata direttamente nel test.

**Dati di test**
HTML con due blocchi "ha risposto il:" prepesi (autori "Riccardo Bernasconi", 21-01-2026 11:54, e "OTCO/SO CCEC", 20-01-2026 13:58) seguiti dal contenuto originale del ticket ("aggiornare l'OTCO CCEC in piattaforma").

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto, solo esecuzione di un metodo statico puro.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a real prepended reply chain (story id 1641 from the v1 dump) is decomposed in chronological order"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il parser restituisce esattamente 3 messaggi in ordine cronologico: il messaggio 0 è il contenuto originale (`isOriginal = true`, autore/timestamp nulli, corpo contenente "aggiornare l'OTCO CCEC in piattaforma"); il messaggio 1 è la risposta di "OTCO/SO CCEC" del 20-01-2026 13:58; il messaggio 2 è la risposta di "Riccardo Bernasconi" del 21-01-2026 11:54 — quindi in ordine cronologico crescente, opposto all'ordine di prependimento del v1.

**Controlli negativi**
Nessuno applicabile: il file di test copre già, come varianti dello stesso metodo, il caso "nessun blocco di reply" (contenuto singolo) e il caso "solo blocchi di reply, nessun contenuto originale residuo".

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

### F2-39 — Una conversazione reale con quote inoltrata da Gmail (story id 3642) non viene scomposta: un unico blocco di fallback

**Obiettivo**
Verificare che una forma di conversazione accumulata diversa dal template esatto "ha risposto il:" del v1 (qui: una citazione Gmail annidata in `<blockquote>`, con la formula "Il giorno ... ha scritto:") NON venga scomposta dal parser — deve restare un unico messaggio di fallback con l'HTML integrale, senza alcuna perdita di contenuto.

**Riferimenti**
- Requisito/regola di dominio: PRD US-210, AC "Se il parsing fallisce: fallback a un unico messaggio con l'HTML integrale sanitizzato... nessuna perdita di contenuto"; CLAUDE.md, nota su `CustomerRequestParser` — "qualunque altra forma di conversazione accumulata... non viene scomposta".
- Test automatico: `tests/Unit/Import/Parsers/CustomerRequestParserTest.php` — `a real Gmail forwarded-quote conversation (story id 3642) is not decomposed: single fallback block` (estratto reale del dump v1, story id 3642: `<p>Il giorno gio 4 giu 2026 alle ore 10:18 Editoria CAI &lt;editoria@cai.it&gt; ha scritto:</p><blockquote>...</blockquote><p>Grazie mille!</p>`).
- File/componente applicativo rilevante: `app/Import/Parsers/CustomerRequestParser.php`.
- Test correlato: F2-38, F2-40.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: input HTML incollato direttamente nel test.

**Dati di test**
HTML reale con una citazione Gmail (`<blockquote>`) del tipo "Il giorno ... ha scritto:", mai riconosciuta dal parser come blocco di risposta v1.

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto, solo esecuzione di un metodo statico puro.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a real Gmail forwarded-quote conversation (story id 3642) is not decomposed: single fallback block"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il parser restituisce esattamente 1 messaggio, `isOriginal = true`, il cui corpo è **identico** all'intero HTML in input (nessun contenuto perso, nessuna scomposizione tentata).

**Controlli negativi**
Nessuno applicabile: comportamento verificato come unico caso in questo test.

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

### F2-40 — Un customer_request reale multi-risposta viene scomposto in messaggi cronologici

**Obiettivo**
Verificare che `TicketMessagesStage` (a differenza del solo parser puro, testato end-to-end contro un vero ticket v2) scomponga il `customer_request` reale della story v1 1641 in 3 righe `ticket_messages` cronologiche, risolvendo l'autore per nome quando possibile (case-insensitive esatto su `users.name`) e attribuendo il messaggio più vecchio (l'originale) al `requester_id` del ticket.

**Riferimenti**
- Requisito/regola di dominio: PRD US-210, AC su risoluzione autore/canale/visibilità dei messaggi ricostruiti.
- Test automatico: `tests/Feature/Import/Stages/TicketMessagesStageTest.php` — `a real multi-reply customer_request is decomposed into chronological messages` (ticket v2 id 1641, requester "Marco Rossi", utente "Riccardo Bernasconi" esistente in v2; il secondo autore "OTCO/SO CCEC" non risolve nessun utente).
- File/componente applicativo rilevante: `app/Import/Stages/TicketMessagesStage.php`, `app/Import/Parsers/CustomerRequestParser.php`.
- Test correlato: F2-38.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Il test usa il trait `InteractsWithLegacyDatabase` (connessione `legacy` riconfigurata su sqlite in-memory): nessun dump/connessione `db_legacy` reale richiesta.

**Dati di test**
- Ticket v2 id 1641 (`created_at` 2026-01-10 09:00:00, `updated_at` 2026-01-21 12:00:00), requester "Marco Rossi".
- Utente v2 "Riccardo Bernasconi" (per la risoluzione autore per nome).
- `stories.customer_request` (v1, id 1641) = lo stesso estratto reale usato in F2-38.

**Stato iniziale**
Database di test vuoto (RefreshDatabase), stage mai eseguito.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a real multi-reply customer_request is decomposed into chronological messages"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Lo stage legge 1 riga v1, crea 3 `ticket_messages` (0 saltati). Il messaggio più vecchio (`posted_at` 2026-01-10 09:00:00, uguale a `tickets.created_at`) ha `author_id` = id del requester "Marco Rossi". Il messaggio intermedio (`posted_at` 2026-01-20 13:58:00, autore "OTCO/SO CCEC") ha `author_id = null` (nome non risolvibile). Il messaggio più recente (`posted_at` 2026-01-21 11:54:00) ha `author_id` = id di "Riccardo Bernasconi". Tutti e 3 hanno `is_legacy_import = true`, `visibility = public`, `channel = system`, e un `ulid` valorizzato.

**Controlli negativi**
Nessuno applicabile: coperto da test dedicati distinti nello stesso file (fallback a blocco unico F2-39/F2-41, ticket senza conversazione, ticket v1 orfano rispetto a v2).

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

### F2-41 — Un tentativo di XSS nel corpo viene neutralizzato da TicketMessageSanitizer

**Obiettivo**
Verificare che `TicketMessagesStage` sanitizzi SEMPRE il corpo dei messaggi ricostruiti tramite `TicketMessageSanitizer` (riuso diretto da Fase 1, mai una seconda implementazione), neutralizzando tag `<script>` e attributi di evento (`onerror`) prima di scrivere `body_html`, mentre `body_text` (derivato dall'HTML già sanitizzato) conserva il solo testo legittimo.

**Riferimenti**
- Requisito/regola di dominio: PRD US-210, AC "HTML sempre sanitizzato con TicketMessageSanitizer già esistente da Fase 1... nessuna seconda implementazione di sanitizzazione nell'ETL".
- Test automatico: `tests/Feature/Import/Stages/TicketMessagesStageTest.php` — `an XSS attempt in the body is neutralized by TicketMessageSanitizer` (input: `<p>Testo normale</p><script>alert(document.cookie)</script><img src=x onerror="alert(1)">`).
- File/componente applicativo rilevante: `app/Import/Stages/TicketMessagesStage.php`, `app/Domain/Ticketing/Support/TicketMessageSanitizer.php`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Il test usa `InteractsWithLegacyDatabase` (connessione `legacy` su sqlite in-memory).

**Dati di test**
`stories.customer_request` = `<p>Testo normale</p><script>alert(document.cookie)</script><img src=x onerror="alert(1)">`.

**Stato iniziale**
Database di test vuoto (RefreshDatabase), stage mai eseguito.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "an XSS attempt in the body is neutralized by TicketMessageSanitizer"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Lo stage crea 1 `ticket_message` il cui `body_html` non contiene più `<script`, `onerror` né `alert(` in nessuna forma, mentre `body_text` contiene ancora "Testo normale".

**Controlli negativi**
Nessuno applicabile: unico scenario coperto da questo test, la sicurezza dell'allowlist di `TicketMessageSanitizer` è verificata in dettaglio dai test di Fase 1.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore (es. `<script>`/`onerror` sopravvivono nel body salvato).
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

### F2-42 — Rieseguire lo stage sullo stesso dump è idempotente tramite import_mappings: la seconda esecuzione si limita a saltare

**Obiettivo**
Verificare che una seconda esecuzione di `TicketMessagesStage` sullo stesso dump v1 non crei messaggi duplicati: la riconciliazione avviene tramite `ImportMapping` (nessuna chiave naturale disponibile per un messaggio ricostruito), precaricata una volta per stage.

**Riferimenti**
- Requisito/regola di dominio: PRD US-210, AC "Idempotenza tramite import_mappings su (story_id, indice, hash)"; CLAUDE.md, sezione ETL, nota su `ImportMapping`.
- Test automatico: `tests/Feature/Import/Stages/TicketMessagesStageTest.php` — `re-running the stage on the same dump is idempotent via import_mappings: second run only skips` (stessa story v1 1641 usata in F2-40, stage eseguito due volte in sequenza).
- File/componente applicativo rilevante: `app/Import/Stages/TicketMessagesStage.php`, `app/Import/Models/ImportMapping.php`.
- Test correlato: F2-40.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Il test usa `InteractsWithLegacyDatabase` (connessione `legacy` su sqlite in-memory).

**Dati di test**
Stesso ticket/`customer_request` reale della story v1 1641 (3 messaggi attesi).

**Stato iniziale**
Database di test vuoto (RefreshDatabase), stage mai eseguito.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "re-running the stage on the same dump is idempotent via import_mappings: second run only skips"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
La prima esecuzione crea 3 `ticket_messages`; la seconda esecuzione crea 0 righe e ne salta 3 (`skipped = 3`), il conteggio finale in `ticket_messages` resta 3 e le righe `import_mappings` per `target_table = ticket_messages` sono esattamente 3 (non 6).

**Controlli negativi**
Nessuno applicabile: comportamento verificato come confronto diretto prima/seconda esecuzione nello stesso test.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore (es. la seconda esecuzione duplica righe).
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

## Allegati (US-211)

### F2-43 — Un media con il file fisico presente su disco viene allegato al primo messaggio legacy del suo ticket

**Obiettivo**
Verificare che `TicketAttachmentsStage` alleghi ogni media v1 (il cui file fisico esiste davvero sul disco nominato `legacy-media`, path `<uuid>-<file_name>`) al **primo** messaggio legacy (per `posted_at`) del ticket v2 corrispondente, tramite medialibrary sulla collection `attachments`, preservando il file sorgente (`preservingOriginal()`).

**Riferimenti**
- Requisito/regola di dominio: PRD US-211, AC "Ogni media v1 viene attaccato al primo messaggio legacy del ticket corrispondente"; CLAUDE.md, sezione ETL, note su `TicketAttachmentsStage` e sul path `<uuid>-<file_name>` (fix US-219, `file_name` non univoco tra ticket diversi nel dump reale).
- Test automatico: `tests/Feature/Import/Stages/TicketAttachmentsStageTest.php` — `a media with its file present on disk is attached to the first legacy message of its ticket` (ticket 100 con due messaggi legacy, 2026-01-01 e 2026-01-02; il media va sul più vecchio dei due).
- File/componente applicativo rilevante: `app/Import/Stages/TicketAttachmentsStage.php`.
- Test correlato: F2-44, F2-45, F2-46.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Il test usa `InteractsWithLegacyDatabase` più `Storage::fake('legacy-media')`/`Storage::fake('ticket-attachments')`: nessun file reale né dump v1 richiesto.

**Dati di test**
- Ticket v2 id 100 con due `ticket_messages` legacy (`posted_at` 2026-01-01 09:00:00 e 2026-01-02 09:00:00).
- Riga `media` v1 (uuid `372a3c0f-72bd-4b12-8629-1196a0c15cc0`, `file_name = report.txt`) con il file corrispondente presente su `legacy-media` al path `372a3c0f-72bd-4b12-8629-1196a0c15cc0-report.txt`.

**Stato iniziale**
Database di test vuoto (RefreshDatabase), stage mai eseguito, dischi fake vuoti.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a media with its file present on disk is attached to the first legacy message of its ticket"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Lo stage legge 1 riga media, crea 1 riga media v2 collegata al messaggio più vecchio (`model_type = TicketMessage`, `collection_name = attachments`, `file_name = report.txt`), registra una `import_mapping` (`source_table = media`, `target_table = media`), e il file sorgente resta presente sul disco `legacy-media` (non cancellato).

**Controlli negativi**
Nessuno applicabile: coperto da test dedicati distinti (file mancante F2-44, ticket senza messaggi F2-45).

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

### F2-44 — Un media il cui file fisico è mancante viene segnalato come orfano, non allegato

**Obiettivo**
Verificare che un media v1 la cui riga esiste nel dump ma il cui file fisico è assente sul disco `legacy-media` sia trattato come **compromesso segnalato** (conteggio in `warnings`), mai come un allegato creato né come un crash dello stage.

**Riferimenti**
- Requisito/regola di dominio: PRD US-211, AC "Verifica l'esistenza fisica del file sorgente: media orfani... sono segnalati nel report, non ignorati e non contati come importati con successo".
- Test automatico: `tests/Feature/Import/Stages/TicketAttachmentsStageTest.php` — `a media whose physical file is missing is reported as orphan, not attached`.
- File/componente applicativo rilevante: `app/Import/Stages/TicketAttachmentsStage.php`.
- Test correlato: F2-43.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Il test usa `InteractsWithLegacyDatabase` più `Storage::fake('legacy-media')`.

**Dati di test**
Riga `media` v1 (ticket 200, uuid `c22e145b-2d95-46c7-99ff-1cc73480cb2f`, `file_name = missing.pdf`) SENZA alcun file corrispondente scritto sul disco fake.

**Stato iniziale**
Database di test vuoto (RefreshDatabase), stage mai eseguito, disco `legacy-media` vuoto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a media whose physical file is missing is reported as orphan, not attached"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Lo stage crea 0 media, ne salta 1, e produce il warning "1 media orfani: riga presente nel dump ma file assente su disco." — nessuna riga scritta nella tabella `media` di v2.

**Controlli negativi**
Nessuno applicabile: comportamento verificato come unico scenario in questo test.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce, il comando termina con errore, oppure lo stage tenta comunque di creare un allegato senza file.
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

### F2-45 — Un ticket senza alcun messaggio legacy ottiene un messaggio di sistema creato per ospitare i suoi allegati

**Obiettivo**
Verificare che, quando un ticket v1 con media non ha alcun `ticket_message` legacy pre-esistente, lo stage crei un messaggio di sistema ("Allegati importati", `is_legacy_import = true`, `posted_at = tickets.created_at`) e vi agganci il media — così che una futura riesecuzione ritrovi naturalmente questo stesso messaggio come "primo messaggio legacy" del ticket.

**Riferimenti**
- Requisito/regola di dominio: PRD US-211, AC "se il ticket non ha messaggi, crea un messaggio di sistema 'Allegati importati' e allega lì"; CLAUDE.md, sezione ETL, nota "Attaccare un media storico a un messaggio che potrebbe non esistere ancora".
- Test automatico: `tests/Feature/Import/Stages/TicketAttachmentsStageTest.php` — `a ticket without any legacy message gets a system message created to host its attachments`.
- File/componente applicativo rilevante: `app/Import/Stages/TicketAttachmentsStage.php`.
- Test correlato: F2-43.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Il test usa `InteractsWithLegacyDatabase` più `Storage::fake('legacy-media')`.

**Dati di test**
Ticket v2 id 300 (`created_at = 2026-02-01 10:00:00`) senza alcun `ticket_message`; media v1 con file presente sul disco fake (`bilancio.txt`).

**Stato iniziale**
Database di test vuoto (RefreshDatabase), stage mai eseguito.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a ticket without any legacy message gets a system message created to host its attachments"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Lo stage crea 1 media e produce il warning "1 ticket senza messaggi: creato un messaggio di sistema \"Allegati importati\" per ospitare gli allegati." Il nuovo `ticket_message` ha `body_text = "Allegati importati"`, `is_legacy_import = true`, `posted_at = 2026-02-01 10:00:00`, e il media risulta agganciato a quel messaggio.

**Controlli negativi**
Nessuno applicabile: comportamento verificato come unico scenario in questo test.

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

### F2-46 — Rieseguire lo stage sullo stesso dump è idempotente tramite import_mappings su media.uuid: la seconda esecuzione si limita a saltare

**Obiettivo**
Verificare che una seconda esecuzione di `TicketAttachmentsStage` sullo stesso dump non duplichi gli allegati già importati: la riconciliazione avviene tramite `ImportMapping` su `media.uuid` (garantito univoco per riga nel dump v1 reale, a differenza di `file_name`).

**Riferimenti**
- Requisito/regola di dominio: PRD US-211, AC "Idempotenza tramite media.uuid: rieseguire lo stage non duplica gli allegati".
- Test automatico: `tests/Feature/Import/Stages/TicketAttachmentsStageTest.php` — `re-running the stage on the same dump is idempotent via import_mappings on media.uuid: second run only skips`.
- File/componente applicativo rilevante: `app/Import/Stages/TicketAttachmentsStage.php`.
- Test correlato: F2-43.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Il test usa `InteractsWithLegacyDatabase` più `Storage::fake('legacy-media')`.

**Dati di test**
Ticket v2 id 700 con un messaggio legacy e un media con file presente sul disco fake, stage eseguito due volte in sequenza.

**Stato iniziale**
Database di test vuoto (RefreshDatabase), stage mai eseguito.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "re-running the stage on the same dump is idempotent via import_mappings on media.uuid: second run only skips"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
La prima esecuzione crea 1 media; la seconda esecuzione crea 0 media e ne salta 1, il conteggio finale della tabella media v2 resta 1 e le righe `import_mappings` per `target_table = media` sono esattamente 1.

**Controlli negativi**
Nessuno applicabile: comportamento verificato come confronto diretto prima/seconda esecuzione nello stesso test.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce, il comando termina con errore, oppure la seconda esecuzione duplica l'allegato.
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

## Report di attività (US-212)

### F2-47 — Un report v1 di proprietà di un utente viene importato in v2 con l'id preservato e la locale del proprietario derivata

**Obiettivo**
Verificare che `ActivityReportsStage` importi un report v1 con `owner_type = customer` (che in v1 punta in realtà a `users`, §0.3 del PRD) mappandolo su `owner_kind = user`/`owner_user_id`, con `id` preservato, e che derivi `locale` dall'utente proprietario già importato (mai da una colonna v1 diretta).

**Riferimenti**
- Requisito/regola di dominio: PRD US-212, AC "id conservato; owner_kind/owner_user_id/owner_organization_id valorizzati coerentemente"; CLAUDE.md, sezione ETL, nota su `activity_reports.locale` "non ha colonna sorgente in v1... derivato dall'owner già risolto".
- Test automatico: `tests/Feature/Import/Stages/ActivityReportsStageTest.php` — `imports a v1 user-owned report into v2 with the id preserved and the owner locale derived` (utente id 1 con `locale = en`; report v1 id 5, `owner_type = customer`, `customer_id = 1`).
- File/componente applicativo rilevante: `app/Import/Stages/ActivityReportsStage.php`.
- Test correlato: F2-48.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Il test usa `InteractsWithLegacyDatabase` (connessione `legacy` su sqlite in-memory).

**Dati di test**
- Utente v2 id 1, `locale = en`.
- Report v1 id 5: `owner_type = customer`, `customer_id = 1`, `organization_id = null`, `report_type = monthly`, `year = 2026`.

**Stato iniziale**
Database di test vuoto (RefreshDatabase), stage mai eseguito.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "imports a v1 user-owned report into v2 with the id preserved and the owner locale derived"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Lo stage legge 1 riga, crea 1 riga `activity_reports` v2 con id = 5, `owner_kind = user`, `owner_user_id = 1`, `owner_organization_id = null`, `period_type = monthly`, `year = 2026`, `locale = en` (derivata dall'utente, non da v1), `pdf_path`/`pdf_generated_at` entrambi nulli (mai importati da v1).

**Controlli negativi**
Nessuno applicabile: il caso "owner organizzazione" è coperto da un test distinto non incluso nel manifest (`imports a v1 organization-owned report...`).

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

### F2-48 — Un report v1 ambiguo (con sia customer_id che organization_id impostati) viene saltato e segnalato, senza mai violare il CHECK sul proprietario

**Obiettivo**
Verificare che `ActivityReportsStage` validi a monte, in PHP, la stessa condizione del vincolo CHECK Postgres `activity_reports_owner_check` (esattamente uno tra `customer_id`/`organization_id` valorizzato) PRIMA di scrivere la riga: un report v1 ambiguo (entrambi valorizzati) va scartato e segnalato, mai lasciato sollevare l'eccezione SQL del vincolo.

**Riferimenti**
- Requisito/regola di dominio: PRD US-212, AC "Rispetta il vincolo CHECK già esistente in v2... un record che lo violerebbe è segnalato e scartato, non causa un errore SQL non gestito"; CLAUDE.md, sezione ETL, nota "Pattern riusabile per qualunque futura tabella v2 con un CHECK 'esattamente uno tra due FK nullable'".
- Test automatico: `tests/Feature/Import/Stages/ActivityReportsStageTest.php` — `an ambiguous v1 report (both customer_id and organization_id set) is skipped and reported, never violating the owner CHECK` (utente id 1 e organizzazione id 2 entrambi esistenti in v2; report v1 con `customer_id = 1` E `organization_id = 2`).
- File/componente applicativo rilevante: `app/Import/Stages/ActivityReportsStage.php`, vincolo `activity_reports_owner_check` (migrazione US-014).
- Test correlato: F2-47.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Il test usa `InteractsWithLegacyDatabase` (connessione `legacy` su sqlite in-memory).

**Dati di test**
Utente v2 id 1; organizzazione v2 id 2; report v1 id 1: `owner_type = customer`, `customer_id = 1`, `organization_id = 2` (entrambi valorizzati, caso ambiguo).

**Stato iniziale**
Database di test vuoto (RefreshDatabase), stage mai eseguito.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "an ambiguous v1 report (both customer_id and organization_id set) is skipped and reported, never violating the owner CHECK"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Lo stage legge 1 riga, crea 0 righe, ne salta 1, produce almeno un warning non vuoto, e la tabella `activity_reports` v2 resta a 0 righe — nessuna `QueryException` sollevata dal vincolo CHECK.

**Controlli negativi**
Coperto anche dal caso limite correlato (non nel manifest): `owner_type = customer` senza alcun `customer_id` valorizzato, stesso esito atteso (scartato e segnalato).

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce, il comando termina con errore, oppure lo stage propaga un'eccezione SQL del vincolo CHECK.
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

### F2-49 — La pivot v1 activity_report<->story viene importata in v2 come activity_report_ticket

**Obiettivo**
Verificare che `ActivityReportTicketsStage` importi la pivot v1 `activity_report_story` nella pivot v2 `activity_report_ticket`, collegando un report di attività già importato al ticket corrispondente.

**Riferimenti**
- Requisito/regola di dominio: PRD US-212, AC "Stage activity_report_tickets: pivot (report_id, ticket_id), idempotente".
- Test automatico: `tests/Feature/Import/Stages/ActivityReportTicketsStageTest.php` — `imports the v1 activity_report<->story pivot into v2 as activity_report_ticket` (report v2 id 1, ticket v2 id 10, pivot v1 che li collega).
- File/componente applicativo rilevante: `app/Import/Stages/ActivityReportTicketsStage.php`.
- Test correlato: F2-50.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Il test usa `InteractsWithLegacyDatabase` (connessione `legacy` su sqlite in-memory).

**Dati di test**
Report v2 id 1 (owner utente), ticket v2 id 10, riga pivot v1 `activity_report_story` (`activity_report_id = 1`, `story_id = 10`).

**Stato iniziale**
Database di test vuoto (RefreshDatabase), stage mai eseguito.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "imports the v1 activity_report<->story pivot into v2 as activity_report_ticket"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Lo stage legge 1 riga, crea 1 riga in `activity_report_ticket` (`activity_report_id = 1`, `ticket_id = 10`), 0 righe saltate.

**Controlli negativi**
Nessuno applicabile: il caso orfano è coperto da un test distinto (F2-50) e da un ulteriore test non incluso nel manifest (associazione verso un ticket inesistente).

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

### F2-50 — Un'associazione che referenzia un report di attività inesistente viene saltata e segnalata

**Obiettivo**
Verificare che `ActivityReportTicketsStage` non vada in crash quando una riga pivot v1 referenzia un `activity_report_id` che non esiste in v2: la riga viene saltata e segnalata nel report, non propagata come errore SQL.

**Riferimenti**
- Requisito/regola di dominio: PRD US-212 (principio generale "orfano segnalato, mai crash", coerente con lo stesso pattern degli altri stage pivot).
- Test automatico: `tests/Feature/Import/Stages/ActivityReportTicketsStageTest.php` — `an association referencing a non-existent activity report is skipped and reported` (ticket v2 id 10 esistente, pivot v1 che referenzia `activity_report_id = 999`, inesistente).
- File/componente applicativo rilevante: `app/Import/Stages/ActivityReportTicketsStage.php`.
- Test correlato: F2-49.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Il test usa `InteractsWithLegacyDatabase` (connessione `legacy` su sqlite in-memory).

**Dati di test**
Ticket v2 id 10; riga pivot v1 `activity_report_story` (`activity_report_id = 999`, `story_id = 10`) — nessun report v2 con id 999.

**Stato iniziale**
Database di test vuoto (RefreshDatabase), stage mai eseguito.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "an association referencing a non-existent activity report is skipped and reported"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Lo stage salta 1 riga, ne crea 0, e produce almeno un warning non vuoto — nessuna eccezione propagata.

**Controlli negativi**
Nessuno applicabile: il caso complementare (ticket v2 inesistente) è coperto da un test distinto non incluso nel manifest, con lo stesso esito atteso.

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

## Opportunità e punteggi di fundraising (US-213)

### F2-51 — Un'opportunità di fundraising v1 viene importata in v2 con l'id preservato e le colonne mappate

**Obiettivo**
Verificare che `FundraisingOpportunitiesStage` importi una riga v1 di `fundraising_opportunities` in v2 preservando l'`id` e mappando le colonne dirette, e che i campi di valutazione (`evaluated_by`, `evaluated_at`, i tre totali) restino `null` all'inserimento: non vengono mai importati dal v1, saranno eventualmente valorizzati da un uso reale dell'app o ricalcolati dallo stage `derive` (US-215).

**Riferimenti**
- Requisito/regola di dominio: PRD US-213, AC "`fundraising_opportunities`: `id` conservato, mapping diretto delle colonne" e "I totali... non si importano da v1: restano vuoti qui, ricalcolati dallo stage `derive`".
- Test automatico: `tests/Feature/Import/Stages/FundraisingOpportunitiesStageTest.php` — `imports a v1 fundraising opportunity into v2 with the id preserved and columns mapped` (riga legacy `id=5`, `name='Bando montagna 2026'`, `territorial_scope='regional'`, `created_by=1`, `responsible_user_id=1`; verifica `read=1`, `created=1`, `updated=0`, `skipped=0`, e che la riga v2 `id=5` abbia `name`/`territorial_scope`/`created_by`/`responsible_user_id` coerenti con `evaluated_by`/`evaluated_at`/`evaluation_positive_total`/`evaluation_negative_total`/`evaluation_total` tutti `null`).
- File/componente applicativo rilevante: `app/Import/Stages/FundraisingOpportunitiesStage.php`.
- Test correlato: F2-52, F2-56.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta: il trait `InteractsWithLegacyDatabase` riconfigura a runtime la connessione `legacy` su sqlite in-memory, creando solo le colonne v1 di cui lo stage ha bisogno.

**Dati di test**
- Utente v2 `id=1` (target delle FK `created_by`/`responsible_user_id`).
- Riga legacy `fundraising_opportunities`: `id=5`, `name='Bando montagna 2026'`, `territorial_scope='regional'`, `created_by=1`, `responsible_user_id=1`.

**Stato iniziale**
Non applicabile: fixture creata interamente dal test, nessuno stato preesistente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "imports a v1 fundraising opportunity into v2 with the id preserved and columns mapped"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la riga v2 `fundraising_opportunities` con `id=5` ha le colonne mappate correttamente e i campi di valutazione (`evaluated_by`/`evaluated_at`/i tre totali) restano `null` al primo inserimento.

**Controlli negativi**
Nessuno applicabile: la protezione dei campi di valutazione da una riesecuzione è coperta separatamente da F2-52.

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

### F2-52 — Un'esecuzione ripetuta non sovrascrive mai evaluated_by/evaluated_at/i totali di valutazione impostati da un uso reale di v2 dopo l'import

**Obiettivo**
Verificare che una riesecuzione di `FundraisingOpportunitiesStage`, pur aggiornando correttamente una colonna mappata cambiata nel v1 (`name`), non tocchi mai `evaluated_by`/`evaluated_at`/`evaluation_positive_total`/`evaluation_negative_total`/`evaluation_total` quando questi sono già stati valorizzati da un uso reale dell'applicazione dopo il primo import — stesso principio già consolidato per altre colonne "derivate/mantenute dall'app dopo l'insert" nell'ETL (es. `status_changed_at`/`worked_minutes` di `TicketsStage`).

**Riferimenti**
- Requisito/regola di dominio: PRD US-213, AC sui totali di valutazione "non si importano da v1... ricalcolati dallo stage `derive`".
- Test automatico: `tests/Feature/Import/Stages/FundraisingOpportunitiesStageTest.php` — `a re-run never overwrites evaluated_by/evaluated_at/evaluation totals set by real v2 usage after import` (dopo il primo import dell'opportunità `id=1`, il test imposta manualmente su v2 `evaluated_by=2`, `evaluated_at=now()`, `evaluation_positive_total=10`, `evaluation_negative_total=2`, `evaluation_total=8` — simulando una valutazione reale fatta in v2 — poi cambia `name` sul v1 in "Bando montagna 2027 (rivisto)" e rilancia lo stage: verifica `updated=1`, `name` aggiornato, ma tutti i cinque campi di valutazione restano invariati).
- File/componente applicativo rilevante: `app/Import/Stages/FundraisingOpportunitiesStage.php`.
- Test correlato: F2-51, F2-60 (stesso principio generale "un derivato toccato da un uso reale/da uno stage successivo non va mai riscritto ciecamente alla riesecuzione").

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Opportunità `id=1`; utenti v2 `id=1` e `id=2`.
- Dopo il primo import: `evaluated_by=2`, `evaluated_at=now()`, `evaluation_positive_total=10`, `evaluation_negative_total=2`, `evaluation_total=8` impostati manualmente su v2.
- Sul v1, `name` cambiato in "Bando montagna 2027 (rivisto)" prima della seconda esecuzione.

**Stato iniziale**
Opportunità già importata una prima volta, con i campi di valutazione valorizzati da un'operazione applicativa simulata (non dall'ETL).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a re-run never overwrites evaluated_by/evaluated_at/evaluation totals set by real v2 usage after import"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la seconda esecuzione aggiorna `name` al nuovo valore v1 ma lascia `evaluated_by=2`, `evaluated_at` valorizzato ed `evaluation_positive_total=10`/`evaluation_negative_total=2`/`evaluation_total=8` invariati.

**Controlli negativi**
Nessuno applicabile: il file di test copre già sia il percorso "aggiornamento di una colonna mappata" (F2-51/test correlato "a changed v1 row is applied as an update") sia questo caso di protezione dei campi di valutazione, come varianti dello stesso stage.

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

### F2-53 — Una colonna v1 evaluation_*_score con un valore nel range diventa una riga fundraising_evaluation_scores

**Obiettivo**
Verificare che `FundraisingScoresStage` traduca una colonna v1 `evaluation_<criterio>_score` non nulla e nel range ammesso in una riga `fundraising_evaluation_scores`, con `criterion_key` corrispondente e `notes` valorizzate dalla colonna `evaluation_<criterio>_description` collegata. Nota tecnica importante: il dump reale di produzione non ha mai avuto queste colonne su `fundraising_opportunities` (la griglia di valutazione non risulta mai usata in produzione v1) — lo stage rileva dinamicamente con `Schema::hasColumn()` quali colonne `evaluation_*` esistono davvero, quindi resta corretto anche se l'ambiente reale non ne ha nessuna (vedi F2-55 per il caso limite collegato).

**Riferimenti**
- Requisito/regola di dominio: PRD US-213, AC "Ogni colonna `evaluation_*_score` non nulla → riga `fundraising_evaluation_scores` con la `criterion_key` corrispondente; le colonne `evaluation_criterion_*_description` diventano `notes`".
- Test automatico: `tests/Feature/Import/Stages/FundraisingScoresStageTest.php` — `a v1 evaluation_*_score column with a value in range becomes a fundraising_evaluation_scores row` (tabella legacy creata ad-hoc con le sole colonne `evaluation_criterion_a_score`/`evaluation_criterion_a_description`; opportunità `id=1`, `score=4`, descrizione "Coerente col bando"; verifica `read=1`, `created=1`, `skipped=0`, `warnings` vuoto, e la riga generata con `criterion_key='criterion_a'`, `score=4`, `notes='Coerente col bando'`).
- File/componente applicativo rilevante: `app/Import/Stages/FundraisingScoresStage.php`, `App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion` (catalogo criteri con range min/max).
- Test correlato: F2-54, F2-55.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Riga legacy `fundraising_opportunities` `id=1` con `evaluation_criterion_a_score=4` e `evaluation_criterion_a_description='Coerente col bando'`.
- Riga v2 `fundraising_opportunities` `id=1` già presente (target dello stage).

**Stato iniziale**
Non applicabile: fixture creata interamente dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a v1 evaluation_\*_score column with a value in range becomes a fundraising_evaluation_scores row"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: viene creata esattamente una riga `fundraising_evaluation_scores` con `criterion_key='criterion_a'`, `score=4`, `notes='Coerente col bando'`, senza alcun warning.

**Controlli negativi**
Nessuno applicabile: il caso "colonna nulla" (nessuna riga generata) e il caso "nessuna colonna evaluation_* nello schema v1" sono coperti da test dedicati dello stesso file, non parte del manifest.

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

### F2-54 — Un punteggio v1 fuori range viene troncato al range del catalogo criteri e il troncamento viene segnalato

**Obiettivo**
Verificare che `FundraisingScoresStage` tronchi (clamp) un punteggio v1 fuori dal range ammesso dal catalogo criteri (`App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion::min()`/`max()`) al limite più vicino, anziché scartarlo o persisterlo fuori range, e che il troncamento sia segnalato come warning nel report dello stage.

**Riferimenti**
- Requisito/regola di dominio: PRD US-213, AC "Punteggi fuori range → clampati al range del catalogo criteri e segnalati (conteggio nel report)".
- Test automatico: `tests/Feature/Import/Stages/FundraisingScoresStageTest.php` — `an out-of-range v1 score is clamped to the criterion catalog range and the clamp is reported` (colonne legacy `evaluation_criterion_b_score=9` e `evaluation_risk_finanziari_score=-9`; opportunità v2 `id=1` già presente; verifica `read=2`, `created=2`, `warnings` non vuoto, e i punteggi persistiti troncati: `criterion_b` → `5` (il `max()` del catalogo per quel criterio) e `risk_finanziari` → `-3` (il `min()` del catalogo per quel criterio)).
- File/componente applicativo rilevante: `app/Import/Stages/FundraisingScoresStage.php`, `App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion`.
- Test correlato: F2-53, F2-55.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Riga legacy `fundraising_opportunities` `id=1` con `evaluation_criterion_b_score=9` (range catalogo `0..5`) e `evaluation_risk_finanziari_score=-9` (range catalogo `-3..3`).
- Riga v2 `fundraising_opportunities` `id=1` già presente.

**Stato iniziale**
Non applicabile: fixture creata interamente dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "an out-of-range v1 score is clamped to the criterion catalog range and the clamp is reported"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: entrambe le righe generate hanno il punteggio troncato al range del rispettivo criterio (`5` per `criterion_b`, `-3` per `risk_finanziari`) e almeno un warning segnala il troncamento.

**Controlli negativi**
Nessuno applicabile: il test copre in un'unica esecuzione sia il troncamento verso l'alto sia verso il basso.

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

### F2-55 — Un'opportunità referenziata dalle colonne v1 evaluation_* ma assente in v2 viene saltata, non manda in crash lo stage

**Obiettivo**
Verificare che `FundraisingScoresStage` non vada in crash quando un punteggio v1 fa riferimento a un'opportunità (`fundraising_opportunities.id`) che non è mai stata importata in v2 (ad esempio perché scartata da `FundraisingOpportunitiesStage` per una FK non risolvibile, F2-51/F2-56): la riga viene saltata e segnalata, senza generare alcuna `fundraising_evaluation_scores`.

**Riferimenti**
- Requisito/regola di dominio: PRD US-213, principio generale già applicato ad altri stage con pivot/derivati verso un'entità madre potenzialmente assente (§11.4, "mai un crash, sempre un warning").
- Test automatico: `tests/Feature/Import/Stages/FundraisingScoresStageTest.php` — `an opportunity referenced by v1 evaluation columns but absent from v2 is skipped, not crashed` (riga legacy `id=999` con `evaluation_criterion_a_score=3`, nessuna riga v2 `fundraising_opportunities` con `id=999`; verifica `created=0`, `warnings` non vuoto, `fundraising_evaluation_scores` count `0`).
- File/componente applicativo rilevante: `app/Import/Stages/FundraisingScoresStage.php`.
- Test correlato: F2-53, F2-54.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Riga legacy `fundraising_opportunities` `id=999` con `evaluation_criterion_a_score=3`.
- Nessuna riga v2 corrispondente (`id=999` assente).

**Stato iniziale**
Non applicabile: fixture creata interamente dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "an opportunity referenced by v1 evaluation columns but absent from v2 is skipped, not crashed"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: nessuna riga `fundraising_evaluation_scores` viene creata, lo stage non lancia eccezioni e produce almeno un warning esplicito.

**Controlli negativi**
Nessuno applicabile.

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

## Progetti e partner di fundraising (US-214)

### F2-56 — Un progetto di fundraising v1 viene importato in v2 con l'id preservato e le colonne mappate

**Obiettivo**
Verificare che `FundraisingProjectsStage` importi un progetto v1 in v2 preservando l'`id`, mappando le colonne dirette e traducendo `submission_date`/`decision_date` v1 in `submitted_at`/`decided_at` v2.

**Riferimenti**
- Requisito/regola di dominio: PRD US-214, AC "`fundraising_projects`: `id` conservato, mapping diretto, collegato a `fundraising_opportunities`".
- Test automatico: `tests/Feature/Import/Stages/FundraisingProjectsStageTest.php` — `imports a v1 fundraising project into v2 with the id preserved and columns mapped` (riga legacy `id=5`, `title='Progetto rifugio alpino'`, `fundraising_opportunity_id=1`, `lead_user_id=1`, `created_by=1`, `responsible_user_id=1`, `status='submitted'`, `submission_date='2026-02-01'`, `decision_date=null`; verifica `read=1`, `created=1`, `updated=0`, `skipped=0`, e la riga v2 `id=5` con tutte le colonne mappate, `submitted_at='2026-02-01'`, `decided_at` null).
- File/componente applicativo rilevante: `app/Import/Stages/FundraisingProjectsStage.php`.
- Test correlato: F2-51, F2-57.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Utente v2 `id=1`; opportunità v2 `id=1` (created_by/responsible_user_id=1).
- Riga legacy `fundraising_projects` `id=5`, come sopra.

**Stato iniziale**
Non applicabile: fixture creata interamente dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "imports a v1 fundraising project into v2 with the id preserved and columns mapped"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la riga v2 `fundraising_projects` con `id=5` ha tutte le colonne mappate, incluse `submitted_at`/`decided_at` derivate da `submission_date`/`decision_date`.

**Controlli negativi**
Nessuno applicabile.

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

### F2-57 — Un progetto i cui lead_user_id/responsible_user_id non esistono in v2 vengono azzerati, non saltati

**Obiettivo**
Verificare che `FundraisingProjectsStage` distingua correttamente il trattamento delle FK non risolvibili in base alla loro criticità: `fundraising_opportunity_id`/`created_by` inesistenti in v2 fanno scartare l'intero progetto (segnalato), mentre `lead_user_id`/`responsible_user_id` inesistenti vengono semplicemente azzerati (il progetto viene comunque importato), sempre con segnalazione esplicita del compromesso.

**Riferimenti**
- Requisito/regola di dominio: PRD US-214 (nessun AC esplicito sulla distinzione skip/null, comportamento verificato dal test automatico); coerente con il principio generale "mai una perdita silenziosa di un dato importabile per un campo secondario mancante" già applicato altrove nell'ETL.
- Test automatico: `tests/Feature/Import/Stages/FundraisingProjectsStageTest.php` — `a project whose lead_user_id/responsible_user_id do not exist in v2 are nulled, not skipped` (progetto `id=1` con `lead_user_id=998` e `responsible_user_id=999`, entrambi inesistenti in v2, ma `fundraising_opportunity_id`/`created_by` validi; verifica `created=1` — non `skipped` — con due warning distinti, "1 progetti fundraising v1 con lead_user_id inesistente in v2: azzerato." e "1 progetti fundraising v1 con responsible_user_id inesistente in v2: azzerato.", e la riga v2 con entrambi i campi `null`).
- File/componente applicativo rilevante: `app/Import/Stages/FundraisingProjectsStage.php`.
- Test correlato: F2-56 (test correlati nello stesso file verificano invece che `fundraising_opportunity_id`/`created_by` inesistenti facciano scartare l'intero progetto, non solo azzerare il campo).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Utente v2 `id=1`; opportunità v2 `id=1` (created_by=1).
- Riga legacy `fundraising_projects` `id=1` con `lead_user_id=998`, `responsible_user_id=999` (entrambi inesistenti in v2), `fundraising_opportunity_id=1` e `created_by=1` (validi).

**Stato iniziale**
Non applicabile: fixture creata interamente dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a project whose lead_user_id/responsible_user_id do not exist in v2 are nulled, not skipped"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: il progetto viene comunque importato (`created=1`, `skipped=0`) con `lead_user_id`/`responsible_user_id` azzerati e due warning distinti che segnalano il compromesso.

**Controlli negativi**
Nessuno applicabile: il contrasto con lo scarto totale su `fundraising_opportunity_id`/`created_by` mancanti è coperto da test dedicati nello stesso file, non parte del manifest.

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

### F2-58 — La pivot v1 progetto di fundraising<->partner viene importata in v2

**Obiettivo**
Verificare che `FundraisingPartnersStage` importi la pivot v1 `fundraising_project_partners` (progetto↔utente partner) in v2 come pivot semplice, senza logica di trasformazione.

**Riferimenti**
- Requisito/regola di dominio: PRD US-214, AC "`fundraising_project_partners`: pivot `(project_id, user_id)`, idempotente".
- Test automatico: `tests/Feature/Import/Stages/FundraisingPartnersStageTest.php` — `imports the v1 fundraising project<->partner pivot into v2` (progetto v2 `id=1` con opportunità e uno staff-user creato appositamente, utente partner v2 `id=1`; riga legacy `fundraising_project_partners(fundraising_project_id=1, user_id=1)`; verifica `read=1`, `created=1`, `skipped=0` e l'esistenza della riga pivot v2 corrispondente).
- File/componente applicativo rilevante: `app/Import/Stages/FundraisingPartnersStage.php`.
- Test correlato: F2-56, F2-59.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Progetto v2 `id=1` (con opportunità collegata e uno staff-user dedicato `id=101`); utente v2 `id=1` come partner.
- Riga legacy `fundraising_project_partners`: `fundraising_project_id=1`, `user_id=1`.

**Stato iniziale**
Non applicabile: fixture creata interamente dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "imports the v1 fundraising project<->partner pivot into v2"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la riga pivot `fundraising_project_partners(fundraising_project_id=1, user_id=1)` esiste in v2.

**Controlli negativi**
Nessuno applicabile.

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

### F2-59 — Un partner che referenzia un progetto di fundraising v2 inesistente viene segnalato, non manda in crash lo stage

**Obiettivo**
Verificare che `FundraisingPartnersStage` non vada in crash quando la pivot v1 referenzia un progetto di fundraising mai importato in v2: la riga viene saltata e segnalata con un warning esplicito.

**Riferimenti**
- Requisito/regola di dominio: PRD US-214, principio generale "orfano segnalato, mai crash" già applicato a ogni pivot dell'ETL.
- Test automatico: `tests/Feature/Import/Stages/FundraisingPartnersStageTest.php` — `a partner referencing a non-existent v2 fundraising project is reported, not crashed` (riga legacy con `fundraising_project_id=999` inesistente e `user_id=1` valido; verifica `created=0`, `skipped=1`, `warnings[0]` contiene "progetto fundraising inesistente", pivot count `0`). Test correlato nello stesso file (`a partner referencing a non-existent v2 user is reported, not crashed`) copre il caso complementare (`user_id` inesistente, warning "utente inesistente").
- File/componente applicativo rilevante: `app/Import/Stages/FundraisingPartnersStage.php`.
- Test correlato: F2-58.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` reale richiesta (sqlite in-memory via `InteractsWithLegacyDatabase`).

**Dati di test**
- Utente v2 `id=1`.
- Riga legacy `fundraising_project_partners`: `fundraising_project_id=999` (inesistente in v2), `user_id=1`.

**Stato iniziale**
Non applicabile: fixture creata interamente dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a partner referencing a non-existent v2 fundraising project is reported, not crashed"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: nessuna riga pivot viene creata, lo stage non lancia eccezioni e il primo warning menziona esplicitamente il progetto fundraising inesistente.

**Controlli negativi**
Nessuno applicabile: il caso complementare (utente inesistente) è coperto da un test separato nello stesso file, non parte del manifest.

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

## Derive (US-215)

### F2-60 — released_at viene ricostruito a partire dalla transizione status_changed in ticket_logs, quando mancante

**Obiettivo**
Verificare che lo stage `derive` (`DeriveStage`, ultimo stage dell'ETL) ricostruisca `tickets.released_at` mancante leggendo il `ticket_logs` di transizione verso lo stato `released` già importato da `TicketLogsStage` (US-208), attribuendo l'`occurred_at` di quel log come timestamp di rilascio.

**Riferimenti**
- Requisito/regola di dominio: PRD US-215, AC "`tickets.released_at`/`done_at` mancanti pur essendo il ticket in stato `released`/`done` → ricostruiti dai `ticket_logs` importati".
- Test automatico: `tests/Feature/Import/Stages/DeriveStageTest.php` — `backfills released_at from the ticket_logs status_changed transition, when missing` (ticket `id=1` in stato `released` con `released_at=null`; due `ticket_logs`: uno `to_status=progress` a `2026-01-05 09:00:00`, uno `from_status=progress`/`to_status=released` a `2026-01-05 15:00:00`; dopo `derive`, `released_at` risulta valorizzato esattamente a `2026-01-05 15:00:00`, il timestamp del log che ha portato il ticket a `released`).
- File/componente applicativo rilevante: `app/Import/Stages/DeriveStage.php` (metodo `processTickets()`).
- Test correlato: F2-61, F2-64. Test correlati nello stesso file (non parte del manifest) verificano che un `released_at` già presente non venga mai sovrascritto, e che un ticket `released`/`done` senza alcun log di transizione corrispondente resti `null` con un warning esplicito ("1 ticket in stato released/done senza un log di transizione corrispondente: timestamp non ricostruibile, rimasto null.").

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessuna connessione `legacy`/dump v1 richiesta: `DeriveStage` non legge mai dalla connessione `legacy`, opera solo su entità già importate in v2 (fixture create direttamente sulle tabelle v2 in sqlite).

**Dati di test**
- Ticket v2 `id=1`, `status='released'`, `released_at=null`.
- `ticket_logs`: `to_status='progress'` @ `2026-01-05 09:00:00`; `from_status='progress'`, `to_status='released'` @ `2026-01-05 15:00:00`.

**Stato iniziale**
Non applicabile: fixture creata interamente dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "backfills released_at from the ticket_logs status_changed transition, when missing"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `tickets.released_at` del ticket `id=1` risulta valorizzato a `2026-01-05 15:00:00`.

**Controlli negativi**
Nessuno applicabile: i casi "già valorizzato, mai sovrascritto" e "nessun log corrispondente, resta null + warning" sono coperti da test dedicati nello stesso file, non parte del manifest.

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

### F2-61 — worked_minutes e ticket_work_logs vengono ricalcolati da un intervallo "progress" in ticket_logs, riusando RecalculateWorkedTime

**Obiettivo**
Verificare che lo stage `derive` ricalcoli `tickets.worked_minutes` e ripopoli `ticket_work_logs` per l'intero storico dei `ticket_logs` importati, riusando direttamente `App\Domain\TimeTracking\Actions\RecalculateWorkedTime` (Fase 1) senza una seconda implementazione dell'algoritmo nell'ETL.

**Riferimenti**
- Requisito/regola di dominio: PRD US-215, AC "`tickets.worked_minutes` ricalcolato con `WorkedTimeCalculator`/`RecalculateWorkedTime` già esistenti da Fase 1... per l'intero storico importato" e "`ticket_work_logs` popolato per l'intero storico".
- Test automatico: `tests/Feature/Import/Stages/DeriveStageTest.php` — `recomputes worked_minutes and ticket_work_logs from a progress interval in ticket_logs, reusing RecalculateWorkedTime` (ticket `id=1` con `worked_minutes=0`; due `ticket_logs` dello stesso developer: `to_status=progress` @ `2026-01-05 09:00:00`, `from_status=progress`/`to_status=testing` @ `2026-01-05 11:00:00` — un intervallo di 2 ore in orario lavorativo; dopo `derive`, `tickets.worked_minutes=120` e la somma di `ticket_work_logs.minutes` per quel ticket è `120`).
- File/componente applicativo rilevante: `app/Import/Stages/DeriveStage.php` (metodo `processTickets()`), `App\Domain\TimeTracking\Actions\RecalculateWorkedTime`, `App\Domain\TimeTracking\WorkedTimeCalculator`.
- Test correlato: F2-60, F2-64.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessuna connessione `legacy`/dump v1 richiesta: fixture create direttamente sulle tabelle v2.

**Dati di test**
- Ticket v2 `id=1`, `worked_minutes=0`.
- `ticket_logs` dello stesso utente developer: `to_status=progress` @ `2026-01-05 09:00:00`; `from_status=progress`, `to_status=testing` @ `2026-01-05 11:00:00`.

**Stato iniziale**
Non applicabile: fixture creata interamente dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "recomputes worked_minutes and ticket_work_logs from a progress interval in ticket_logs, reusing RecalculateWorkedTime"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `tickets.worked_minutes` del ticket `id=1` risulta `120` e la somma dei minuti in `ticket_work_logs` per lo stesso ticket è `120`.

**Controlli negativi**
Nessuno applicabile.

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

### F2-62 — Vengono rigenerati slug finali univoci per tag e documentation_pages, con suffisso numerico sui duplicati

**Obiettivo**
Verificare che lo stage `derive` ricalcoli da zero lo slug DEFINITIVO di ogni riga `tags`/`documentation_pages` (in ordine di `id`, deterministico), riusando lo stesso trait `GeneratesProvisionalSlugs` già usato per lo slug provvisorio all'insert (US-204), garantendo un suffisso numerico sui duplicati di nome.

**Riferimenti**
- Requisito/regola di dominio: PRD US-215, AC "Slug univoci definitivi per `tags` e `documentation_pages` (suffisso numerico sui duplicati)".
- Test automatico: `tests/Feature/Import/Stages/DeriveStageTest.php` — `regenerates unique final slugs for tags and documentation_pages, numeric suffix on duplicates` (due tag con lo stesso `name='Foo'` ma slug provvisori diversi `'stale-1'`/`'stale-2'`; una `documentation_pages` con `title='Guida'`, slug `'stale-doc'`; dopo `derive`: tag `id=1` → slug `'foo'`, tag `id=2` → slug `'foo-2'` (suffisso numerico sul duplicato), documentation page `id=1` → slug `'guida'`; warning "2 slug definitivi rigenerati su tags." e "1 slug definitivi rigenerati su documentation_pages.").
- File/componente applicativo rilevante: `app/Import/Stages/DeriveStage.php` (metodo `regenerateSlugs()`), `App\Import\Stages\Concerns\GeneratesProvisionalSlugs`.
- Test correlato: F2-14, F2-15 (slug provvisorio, US-204).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessuna connessione `legacy`/dump v1 richiesta: fixture create direttamente sulle tabelle v2.

**Dati di test**
- `tags`: `id=1` name `'Foo'` slug `'stale-1'`; `id=2` name `'Foo'` slug `'stale-2'`.
- `documentation_pages`: `id=1` title `'Guida'` slug `'stale-doc'`.

**Stato iniziale**
Non applicabile: fixture creata interamente dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "regenerates unique final slugs for tags and documentation_pages, numeric suffix on duplicates"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `tags.id=1` → `slug='foo'`, `tags.id=2` → `slug='foo-2'`, `documentation_pages.id=1` → `slug='guida'`, con i due warning attesi presenti.

**Controlli negativi**
Nessuno applicabile.

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

### F2-63 — Viene generato un email_thread per ogni ticket con una conversazione importata

**Obiettivo**
Verificare che lo stage `derive` generi un `email_threads` per ogni ticket che ha almeno un `ticket_messages` importato (US-210), con `subject_normalized` derivato dal titolo del ticket, `participants` come elenco distinto delle email coinvolte e `last_message_at` pari al messaggio più recente — prerequisito di Fase 3 per il threading su ticket storici.

**Riferimenti**
- Requisito/regola di dominio: PRD US-215, AC "`email_threads` generati per i ticket con conversazione importata (US-210), così che il threading funzioni anche su ticket storici".
- Test automatico: `tests/Feature/Import/Stages/DeriveStageTest.php` — `generates one email_thread per ticket with an imported conversation` (ticket `id=1`, `title='Problema di accesso'`; due `ticket_messages`: uno con autore risolto (`author_id`, email `author@example.org`) `posted_at='2026-01-05 09:00:00'`, uno senza autore risolto ma con `author_email='cliente@example.org'` `posted_at='2026-01-06 10:00:00'`; dopo `derive`, esiste un `email_threads` per `ticket_id=1` con `subject_normalized='problema di accesso'`, `participants` = `['author@example.org', 'cliente@example.org']` (ordine non garantito, verificato senza tener conto dell'ordine) e `last_message_at` pari al messaggio più recente, `2026-01-06 10:00:00`).
- File/componente applicativo rilevante: `app/Import/Stages/DeriveStage.php` (metodo `generateEmailThreads()`), `App\Domain\Mail\Parsers\SubjectNormalizer::normalizeForThreadMatching()`.
- Test correlato: F2-38..F2-42 (US-210, i messaggi importati che alimentano questa derivazione).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessuna connessione `legacy`/dump v1 richiesta: fixture create direttamente sulle tabelle v2.

**Dati di test**
- Ticket v2 `id=1`, `title='Problema di accesso'`.
- `ticket_messages`: uno con autore risolto (email `author@example.org`) @ `2026-01-05 09:00:00`; uno con `author_email='cliente@example.org'` @ `2026-01-06 10:00:00`.

**Stato iniziale**
Non applicabile: fixture creata interamente dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "generates one email_thread per ticket with an imported conversation"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: esiste esattamente un `email_threads` per `ticket_id=1` con `subject_normalized='problema di accesso'`, i due partecipanti attesi e `last_message_at` coerente col messaggio più recente.

**Controlli negativi**
Nessuno applicabile.

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

### F2-64 — Rieseguire derive sullo stesso stato è idempotente: la seconda esecuzione si limita a saltare

**Obiettivo**
Verificare che una seconda esecuzione consecutiva dello stage `derive` sullo stesso stato v2 (nessun dato v1 cambiato nel frattempo) non crei né aggiorni alcuna riga: ogni sotto-derivazione (timestamp ticket, totali fundraising, slug, email thread) risulta già coerente e viene quindi saltata — coerente col principio "ogni derivato è ricalcolato da zero, ma se il risultato coincide con quello già presente non genera una scrittura".

**Riferimenti**
- Requisito/regola di dominio: PRD US-215, AC "Idempotente per definizione: ogni derivato è ricalcolato da zero... rieseguire lo stage due volte produce lo stesso risultato esatto"; criterio di accettazione esplicito della Fase 2 (§11.7, "una seconda esecuzione consecutiva non modifica nulla").
- Test automatico: `tests/Feature/Import/Stages/DeriveStageTest.php` — `re-running derive on the same state is idempotent: second run only skips` (fixture combinata: un ticket `released` con log di transizione da ricostruire, un'opportunità fundraising con un punteggio, un tag con slug provvisorio, un `ticket_messages` legacy; lo stage viene eseguito due volte consecutive: alla seconda esecuzione `created=0`, `updated=0`, `skipped>0`, `email_threads` resta a `1` riga (nessun duplicato), mentre la prima esecuzione aveva prodotto almeno una creazione).
- File/componente applicativo rilevante: `app/Import/Stages/DeriveStage.php`.
- Test correlato: F2-60, F2-61, F2-62, F2-63, F2-69 (idempotenza dell'intera pipeline `v1:import`).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessuna connessione `legacy`/dump v1 richiesta: fixture create direttamente sulle tabelle v2.

**Dati di test**
- Ticket `released` con `ticket_logs` di transizione; opportunità fundraising con un punteggio; tag con slug provvisorio `'stale'`; un `ticket_messages` legacy con autore per email.

**Stato iniziale**
Non applicabile: fixture creata interamente dal test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "re-running derive on the same state is idempotent: second run only skips"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la seconda esecuzione di `derive` produce `created=0`, `updated=0`, `skipped>0` e non duplica nessuna riga `email_threads`.

**Controlli negativi**
Nessuno applicabile.

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

## Comando v1:validate (US-216)

### F2-65 — Un ticket entro la tolleranza del 5% sulle ore lavorate viene classificato come conforme

**Obiettivo**
Verificare che `WorkedHoursDeviationAnalyzer::analyze()` (usato da `v1:validate` per confrontare le ore lavorate v1 vs v2, §11.7 del PRD) classifichi come "entro tolleranza" un ticket il cui scostamento percentuale tra le ore v1 e i minuti v2 rientra nella soglia del 5%.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 §11.7 (confronto dei derivati: ore lavorate v1 vs v2, tolleranza per ticket) e §8 Domande Aperte, **Q6**: la soglia ±5% è dichiarata dal PRD come "assunzione operativa di riferimento, non ancora validata su un confronto reale v1/v2", da confermare o correggere col committente dopo aver visto la distribuzione reale degli scostamenti (US-216/US-219) — questo test verifica solo che l'algoritmo applichi correttamente la soglia oggi configurata, non che il 5% sia il valore definitivo.
- Test automatico: `tests/Unit/Import/Validation/WorkedHoursDeviationAnalyzerTest.php` — `classifica un ticket entro la tolleranza del 5%` (10 ore v1 contro 606 minuti v2, cioè 10h06m: scostamento dell'1%, entro tolleranza).
- File/componente applicativo rilevante: `app/Import/Validation/WorkedHoursDeviationAnalyzer.php`; `app/Console/Commands/V1ValidateCommand.php` (unico punto che invoca l'analizzatore, sui dati reali v1/v2).
- Test correlato: F2-66 (caso oltre tolleranza), F2-67/F2-68 (comando `v1:validate` completo).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: l'analizzatore è testato con un array PHP predisposto dal test, non con dati reali.

**Dati di test**
Un ticket con `v1_hours = 10.0` e `v2_minutes = 606` (10 ore e 6 minuti).

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto, solo esecuzione di un metodo statico puro.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "classifica un ticket entro la tolleranza del 5%"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: lo scostamento calcolato è dell'1% (ben entro il 5%), il ticket risulta in `within_tolerance = 1` e la lista `beyond_tolerance` resta vuota.

**Controlli negativi**
Nessuno applicabile: il caso "oltre tolleranza" è coperto separatamente da F2-66.

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
- Note: la soglia del 5% verificata da questo test è un'assunzione operativa (Q6 del PRD) non ancora confermata dal committente su un confronto reale v1/v2.

---

### F2-66 — Un ticket oltre la tolleranza del 5% viene elencato con lo scostamento percentuale

**Obiettivo**
Verificare che `WorkedHoursDeviationAnalyzer::analyze()` classifichi come "oltre tolleranza" un ticket il cui scostamento percentuale tra ore v1 e minuti v2 supera il 5%, e che lo riporti nella lista `beyond_tolerance` con lo scostamento percentuale esatto calcolato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 §11.7 e §8 Domande Aperte, **Q6** (stessa nota di F2-65: la soglia ±5% resta un'assunzione operativa di riferimento, da confermare col committente dopo aver visto la distribuzione reale degli scostamenti sul dump reale, non un valore già validato).
- Test automatico: `tests/Unit/Import/Validation/WorkedHoursDeviationAnalyzerTest.php` — `elenca un ticket oltre la tolleranza del 5% con lo scostamento percentuale` (10 ore v1 contro 12 ore v2: scostamento del 20%, oltre tolleranza).
- File/componente applicativo rilevante: `app/Import/Validation/WorkedHoursDeviationAnalyzer.php`; `app/Console/Commands/V1ValidateCommand.php`.
- Test correlato: F2-65 (caso entro tolleranza), F2-67/F2-68.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: l'analizzatore è testato con un array PHP predisposto dal test, non con dati reali.

**Dati di test**
Un ticket con `id = 42`, `v1_hours = 10.0` e `v2_minutes = 720` (12 ore).

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto, solo esecuzione di un metodo statico puro.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "elenca un ticket oltre la tolleranza del 5% con lo scostamento percentuale"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `within_tolerance = 0`, la lista `beyond_tolerance` contiene esattamente 1 elemento, con `id = 42` e `deviation_percent = 20.0`.

**Controlli negativi**
Nessuno applicabile: il caso "entro tolleranza" è coperto separatamente da F2-65.

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
- Note: la soglia del 5% verificata da questo test è un'assunzione operativa (Q6 del PRD) non ancora confermata dal committente su un confronto reale v1/v2.

---

### F2-67 — Il comando ha successo e riporta OK quando i conteggi v1/v2 e i controlli di integrità coincidono

**Obiettivo**
Verificare che `php artisan v1:validate` termini con successo e produca un report che dichiara "OK" quando i conteggi delle entità a id conservato tra v1 e v2 coincidono e nessun controllo di integrità (valori enum fuori catalogo, ecc.) fallisce.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-216, §11.7 (report di validazione: conteggi a confronto, controlli di integrità, exit di successo quando tutto coincide).
- Test automatico: `tests/Feature/Console/V1ValidateCommandTest.php` — `succeeds and reports OK when v1/v2 counts and integrity checks match` (2 utenti legacy, 2 utenti v2, 1 story legacy con `hours = 10.0`, 1 ticket v2 con `requester_id` valorizzato e `worked_minutes = 600`; il comando deve terminare con successo, stampare "Validazione superata." e salvare un report che contiene la riga `| users | 2 | 2 | 0 | OK |`, "0 valori fuori catalogo" e "Nessuna esecuzione di v1:import trovata").
- File/componente applicativo rilevante: `app/Console/Commands/V1ValidateCommand.php`.
- Test correlato: F2-68 (caso di conteggio in mismatch).

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a un ambiente con terminale/Docker secondo CLAUDE.md (sezione "ETL / dump v1"), con `db_legacy` popolato da un dump v1 coerente con lo stato v2 corrente (stesso numero di righe per le entità a id conservato) e nessun controllo di integrità già violato.
- In alternativa, in UAT: l'ultimo `v1:import` è già girato al deploy — questo test si esegue rilanciando `v1:validate` e leggendo il suo esito, senza dover rieseguire l'import.

**Dati di test**
Nessun dato ad-hoc necessario: si usa lo stato v1/v2 già coerente dell'ambiente (o, per una verifica isolata, il test automatico referenziato costruisce da sé la propria fixture sqlite).

**Stato iniziale**
`db_legacy` e lo schema v2 nello stato prodotto dall'ultimo `v1:import` andato a buon fine, nessuna discrepanza nota nei conteggi.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere a un ambiente con accesso a terminale/Docker secondo CLAUDE.md | — | Terminale con accesso all'app e a `db_legacy` |
| 2 | Eseguire il comando di validazione | `php artisan v1:validate` | Il comando termina con exit code 0 (successo) e stampa a video il testo "Validazione superata." |
| 3 | Individuare il report appena generato | `storage/app/import/validate-<timestamp>.md` | Il report esiste, contiene l'intestazione "Report v1:validate" e una riga di conteggio con esito `OK` per ogni entità a id conservato controllata |

**Risultato finale atteso**
Il comando esce con successo; il report salvato riporta, per ogni entità confrontata, conteggi v1/v2 coincidenti ed esito `OK`, e la sezione dei controlli di integrità riporta zero violazioni (es. "0 valori fuori catalogo").

**Controlli negativi**
Nessuno applicabile a questo test specifico: il caso di fallimento (conteggio non coincidente) è coperto da F2-68.

**Evidenze da acquisire**
- Output a video del comando `v1:validate`.
- Copia (o estratto) del file di report generato in `storage/app/import/`.

**Criterio di superamento**

PASS: il comando esce con successo, stampa "Validazione superata." e il report mostra tutti i conteggi/controlli come `OK`/zero violazioni.
FAIL: il comando esce con errore, oppure il report mostra una discrepanza non attesa.
BLOCKED: impossibile accedere all'ambiente con `db_legacy`/eseguire il comando.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: `v1:validate` è un comando di sola lettura, non scrive su `db_legacy` né modifica lo schema v2 (scrive solo il report su disco).

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

### F2-68 — Il comando fallisce quando il conteggio di un'entità a id preservato non corrisponde

**Obiettivo**
Verificare che `php artisan v1:validate` esca con uno status di errore quando il conteggio di un'entità a id conservato (qui: `users`) non coincide tra v1 e v2, così da poter essere usato come gate bloccante (§11.7 del PRD).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-216, §11.7 ("il comando esce con status di errore se ... i conteggi delle entità con id conservato non coincidono").
- Test automatico: `tests/Feature/Console/V1ValidateCommandTest.php` — `fails when an id-preserved entity count does not match` (3 utenti legacy contro solo 2 utenti v2: il comando deve fallire, stampare "Validazione FALLITA" e il report deve contenere la riga `| users | 3 | 2 | 1 | MISMATCH |`).
- File/componente applicativo rilevante: `app/Console/Commands/V1ValidateCommand.php`.
- Test correlato: F2-67 (caso di successo).

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a un ambiente con terminale/Docker secondo CLAUDE.md, con la possibilità di introdurre deliberatamente una discrepanza di conteggio (ad es. su un ambiente di verifica/staging isolato, mai su UAT/produzione: cancellare o non importare un utente v2 dopo il popolamento da `v1:import`).

**Dati di test**
Una discrepanza di conteggio deliberata su un'entità a id conservato (es. un utente presente in `db_legacy` ma assente nella tabella v2 `users`, magari eliminato manualmente per il solo scopo del test in un ambiente non-UAT).

**Stato iniziale**
Un ambiente in cui il conteggio v1 e v2 di un'entità a id conservato NON coincidono (predisposto ad hoc, mai su UAT).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere a un ambiente con accesso a terminale/Docker secondo CLAUDE.md, predisposto con una discrepanza di conteggio nota su un'entità a id conservato | — | Terminale con accesso all'app e a `db_legacy` |
| 2 | Eseguire il comando di validazione | `php artisan v1:validate` | Il comando termina con exit code diverso da zero e stampa a video il testo "Validazione FALLITA" |
| 3 | Ispezionare il report generato | `storage/app/import/validate-<timestamp>.md` | La riga di conteggio dell'entità con la discrepanza riporta l'esito `MISMATCH` con i due conteggi effettivi e il delta |

**Risultato finale atteso**
Il comando esce con uno status di errore e il report identifica chiaramente quale entità e quale delta ha causato il fallimento.

**Controlli negativi**
Verificare che il comando non riporti "Validazione superata." in questo scenario (nessun falso positivo).

**Evidenze da acquisire**
- Output a video del comando `v1:validate` con l'errore.
- Copia (o estratto) del report generato, con la riga `MISMATCH` evidenziata.

**Criterio di superamento**

PASS: il comando esce con status di errore, stampa "Validazione FALLITA" e il report riporta correttamente `MISMATCH` con i conteggi/delta reali.
FAIL: il comando esce con successo nonostante la discrepanza, oppure il report non la segnala.
BLOCKED: impossibile predisporre una discrepanza di conteggio in un ambiente sicuro (non-UAT) o eseguire il comando.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Ripristinare la discrepanza introdotta ad hoc (ripopolare l'utente/entità rimossa) prima di lasciare l'ambiente, se condiviso con altre attività di collaudo.

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

### F2-69 — Una seconda esecuzione consecutiva di v1:import non crea/aggiorna nulla su nessuno stage registrato

**Obiettivo**
Verificare l'idempotenza dell'intera pipeline `v1:import`: eseguendo il comando due volte consecutive sullo stesso dump v1, la seconda esecuzione non deve creare né aggiornare alcuna riga su nessuno degli stage registrati in `config('import.stages')` — è il criterio di accettazione esplicito della Fase 2 (PRD §11.7/§7 Metriche di successo).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2, criterio di accettazione esplicito "una seconda esecuzione consecutiva non modifica nulla" (US-216, §7 Metriche di successo).
- Test automatico: `tests/Feature/Console/V1ImportPipelineIdempotencyTest.php` — `a second consecutive v1:import run creates/updates nothing on every registered stage` (popola un'intera fixture legacy realistica — utenti, organizzazioni, documentazione, tag, ticket, gerarchia, log, media, report di attività, fundraising — esegue `v1:import` due volte, poi verifica che per OGNI stage registrato `created === 0` e `updated === 0` nel secondo `ImportRun`).
- File/componente applicativo rilevante: `app/Import/ImportRunner.php`, tutti gli `App\Import\Stages\*Stage`.
- Test correlato: ogni test di idempotenza per singolo stage (F2-08, F2-12, F2-22, F2-33, F2-37, F2-46, F2-58, F2-64); questo test copre invece l'INTERA pipeline con tutti gli stage reali insieme (nota CLAUDE.md: un bug di idempotenza multi-stage reale — `TicketsStage`/`derive` — è stato trovato solo da questo test, non dai test per singolo stage).

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso a un ambiente con terminale/Docker secondo CLAUDE.md (sezione "ETL / dump v1"), con `db_legacy` popolato dall'ultimo dump v1 disponibile e un ambiente v2 su cui è già stato eseguito un primo `v1:import` completo (tipicamente lo stato di UAT subito dopo un deploy, che esegue l'ETL automaticamente).

**Dati di test**
Il dump v1 reale già caricato in `db_legacy` (nessuna fixture ad hoc necessaria su UAT: il comando gira sull'intero dataset).

**Stato iniziale**
Un `v1:import` è già stato eseguito con successo su questo ambiente (stato "prima esecuzione" già presente, es. dal deploy).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere a un ambiente con accesso a terminale/Docker secondo CLAUDE.md, dove un primo `v1:import` è già stato eseguito | — | Terminale con accesso all'app |
| 2 | Rieseguire l'import una seconda volta consecutiva | `php artisan v1:import --anonymize` (stesse opzioni usate al deploy) | Il comando termina con successo, nuovo `ImportRun` con stato `Completed` |
| 3 | Ispezionare i conteggi per stage della seconda esecuzione | Consultare `import_runs.stages` (via tinker o report) dell'ultimo `ImportRun` | Per ogni stage elencato, `created = 0` e `updated = 0`; solo `skipped`/`read` possono essere maggiori di zero |

**Risultato finale atteso**
Il secondo `ImportRun` risulta `Completed` e nessuno stage registrato ha creato o aggiornato righe: l'intera pipeline è dimostrabilmente idempotente su questo ambiente.

**Controlli negativi**
Se un qualunque stage riporta `created > 0` o `updated > 0` alla seconda esecuzione, è un'anomalia di idempotenza da segnalare (mai attesa).

**Evidenze da acquisire**
- Output a video della seconda esecuzione di `v1:import`.
- Estratto di `import_runs.stages` (via tinker/query) con i conteggi per stage della seconda esecuzione.

**Criterio di superamento**

PASS: la seconda esecuzione consecutiva ha `created = 0` e `updated = 0` su ogni stage registrato.
FAIL: almeno uno stage riporta righe create o aggiornate alla seconda esecuzione.
BLOCKED: impossibile eseguire una seconda esecuzione dell'import sull'ambiente disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: una seconda esecuzione idempotente non altera lo stato applicativo oltre a quanto già presente.

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

## Password fissa fuori produzione (US-217, ridefinito da US-R08)

### F2-70 — Con --anonymize nome/email/contenuti restano sempre quelli reali del dump v1, mai alterati

**Obiettivo**
Verificare che, in seguito alla ridefinizione di US-R08, l'opzione `--anonymize` di `v1:import` NON alteri mai nome ed email di un utente importato: restano sempre i valori reali del dump v1, sia con sia senza il flag (a differenza del design originale, rimosso, che sostituiva questi campi con dati fittizi).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-217, ridefinito da US-R08 (2026-08-11): "nome/email/ruoli/contenuti restano SEMPRE quelli reali del dump v1, sia con sia senza `--anonymize`" — l'unica cosa che il flag continua a cambiare è la password (vedi F2-71).
- Test automatico: `tests/Feature/Import/Stages/UsersStageTest.php` — `--anonymize never changes name or email: they always stay the real ones from v1 (US-R08)` (utente legacy id 42, nome "Mario Rossi", email `mario.rossi@clientedavvero.it`; dopo `UsersStage` eseguito con `anonymize: true`, l'utente v2 ha ancora esattamente lo stesso nome e la stessa email).
- File/componente applicativo rilevante: `app/Import/Stages/UsersStage.php`.
- Test correlato: F2-07 (mapping colonne base), F2-71 (password fissa), F2-74 (email duplicate).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test usa il trait `InteractsWithLegacyDatabase` (connessione `legacy` riconfigurata su sqlite in-memory) e una tabella `users` v1 fixture creata dal test stesso.

**Dati di test**
Un utente legacy: `id = 42`, `name = 'Mario Rossi'`, `email = 'mario.rossi@clientedavvero.it'` (un dominio email reale, non di test).

**Stato iniziale**
Nessun utente v2 con id 42 presente prima dell'esecuzione dello stage.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "anonymize never changes name or email"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: l'utente v2 con id 42, dopo l'import con `--anonymize`, ha ancora `name = 'Mario Rossi'` ed `email = 'mario.rossi@clientedavvero.it'`, identici al dato v1 reale.

**Controlli negativi**
Nessuno applicabile: il comportamento "senza --anonymize" è implicitamente lo stesso (nessun ramo di codice separato altera questi campi in nessuno dei due casi).

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

### F2-71 — Con --anonymize la password è sempre l'hash di un valore fisso noto, mai l'hash v1 reale

**Obiettivo**
Verificare che `FixedPasswordHasher::hash()` restituisca sempre un hash Laravel valido della password fissa nota (`uat`), mai la stringa in chiaro né l'hash v1 reale, e che l'hash prodotto verifichi correttamente contro quella password fissa.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-217, ridefinito da US-R08: "con `--anonymize` la password è impostata a un hash fisso noto (password `uat`) invece dell'hash v1 reale".
- Test automatico: `tests/Unit/Import/Security/FixedPasswordHasherTest.php` — `hash returns a Laravel hash of the fixed known password, never the raw string` (verifica che l'hash restituito non sia la stringa letterale `'uat'` e che `Hash::check('uat', $hash)` sia vero).
- File/componente applicativo rilevante: `app/Import/Security/FixedPasswordHasher.php`; `app/Import/Stages/UsersStage.php` (unico chiamante, quando `--anonymize` è attivo).
- Test correlato: F2-70 (nome/email invariati), F2-72/F2-73 (altro guard di sicurezza per ambienti non-produzione).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Nessun dato esterno: il test chiama direttamente `FixedPasswordHasher::hash()` senza argomenti.

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto, solo esecuzione di un metodo statico puro.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "hash returns a Laravel hash of the fixed known password"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: l'hash restituito è diverso dalla stringa `'uat'` e `Hash::check('uat', $hash)` restituisce `true` (è un vero hash Laravel della password fissa nota).

**Controlli negativi**
Nessuno applicabile per questo test specifico (un test correlato nello stesso file, non incluso in questo elenco, verifica che l'hash non sia deterministico byte-per-byte tra due chiamate — non è un requisito UAT separato).

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

### F2-72 — Un'email verso un dominio reale non in allowlist viene bloccata fuori produzione

**Obiettivo**
Verificare che il guard applicativo `App\Support\Mail\BlockRealRecipientsOutsideProduction` blocchi l'invio effettivo di un'email verso un dominio reale non presente nell'allowlist di domini di test, quando l'ambiente non è di produzione — protezione contro l'invio accidentale di notifiche a indirizzi reali durante sviluppo/test, resa più rilevante da US-R08 (gli utenti importati hanno ora email reali anche in ambienti non di produzione).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-217/§11.8: "un guard applicativo impedisce l'invio di email verso indirizzi reali quando `APP_ENV !== 'production'`: allowlist di domini di test in configurazione".
- Test automatico: `tests/Feature/Support/Mail/BlockRealRecipientsOutsideProductionTest.php` — `an email to a real, non-allowlisted domain is blocked outside production` (allowlist impostata a `['test.orchestrator.invalid']`; un invio verso `cliente.vero@gmail.com` tramite il mailer `array` risulta in **zero** messaggi effettivamente accumulati dal transport).
- File/componente applicativo rilevante: `app/Support/Mail/BlockRealRecipientsOutsideProduction.php` (listener di `Illuminate\Mail\Events\MessageSending`, registrato in `AppServiceProvider::boot()`).
- Test correlato: F2-73 (il guard è bypassato in produzione).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun server SMTP reale richiesto: il test usa il mailer `array` di Laravel (accumula i messaggi realmente "inviati" in memoria, senza invio di rete).

**Dati di test**
Destinatario `cliente.vero@gmail.com` (dominio reale non in allowlist); allowlist di test configurata a `['test.orchestrator.invalid']`.

**Stato iniziale**
`config('mail.default') = 'array'`, ambiente applicativo non di produzione (default dei test).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "an email to a real, non-allowlisted domain is blocked outside production"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: il tentativo di invio verso `cliente.vero@gmail.com` risulta in zero messaggi effettivamente accumulati dal transport `array`, cioè l'invio è stato bloccato dal guard.

**Controlli negativi**
Nessuno applicabile per questo test specifico: il caso "dominio in allowlist consegnato regolarmente" e il caso "destinatario reale nascosto in cc/bcc anch'esso bloccato" sono coperti da altri test dello stesso file, non citati in questo manifest.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato, nessuna email reale viene inviata durante il test.

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

### F2-73 — Il guard viene bypassato del tutto in produzione, destinatari reali inclusi

**Obiettivo**
Verificare che il guard `BlockRealRecipientsOutsideProduction` non intervenga affatto quando l'ambiente applicativo è `production`: un'email verso un destinatario reale viene consegnata regolarmente, senza alcun blocco.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-217/§11.8 (il guard si applica solo quando `APP_ENV !== 'production'`; in produzione l'invio reale deve funzionare senza restrizioni aggiuntive).
- Test automatico: `tests/Feature/Support/Mail/BlockRealRecipientsOutsideProductionTest.php` — `the guard is bypassed entirely in production, real recipients included` (con l'ambiente applicativo forzato a `production`, un invio verso `cliente.vero@gmail.com` tramite il mailer `array` risulta in esattamente 1 messaggio accumulato dal transport, cioè consegnato).
- File/componente applicativo rilevante: `app/Support/Mail/BlockRealRecipientsOutsideProduction.php`.
- Test correlato: F2-72 (il guard blocca fuori produzione).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun server SMTP reale richiesto: il test usa il mailer `array` di Laravel.

**Dati di test**
Destinatario `cliente.vero@gmail.com` (dominio reale); ambiente applicativo forzato a `production` all'interno del test.

**Stato iniziale**
`config('mail.default') = 'array'`, ambiente applicativo forzato a `production` per la durata del test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the guard is bypassed entirely in production, real recipients included"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: in ambiente `production`, il tentativo di invio verso `cliente.vero@gmail.com` risulta in esattamente 1 messaggio accumulato dal transport `array`, cioè l'invio non è stato bloccato.

**Controlli negativi**
Nessuno applicabile per questo test specifico.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuno stato persistente viene modificato, nessuna email reale viene inviata durante il test.

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

## Fixture CI (US-218)

### F2-74 — Le email duplicate case-insensitive vengono segnalate senza far fallire lo stage (deviazione coperta dalla fixture CI)

**Obiettivo**
Verificare che `UsersStage`, di fronte a due utenti v1 la cui email è identica a meno del case (es. `Mario@Example.test` e `mario@example.test`), importi comunque entrambi come utenti v2 distinti e segnali il conflitto con un warning aggregato, senza far fallire lo stage — questo compromesso è uno dei casi limite espliciti richiesti dalla fixture CI ridotta di US-218.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 2 US-202 ("email case-insensitive deduplicate e segnalate se in conflitto") e US-218 (fixture CI deve coprire esplicitamente "un'email duplicata a meno del case"). Nota CLAUDE.md: `UsersStage` NON deduplica realmente le righe v2 con la stessa email a meno del case (restano due utenti v2 distinti) — per questo il caso non è incluso nella fixture del gate `v1:validate` (violerebbe il controllo di unicità e farebbe fallire il comando), pur restando coperto isolatamente da questo test.
- Test automatico: `tests/Feature/Import/Stages/UsersStageTest.php` — `reports case-insensitive duplicate emails without failing the stage` (due utenti legacy con id diversi e la stessa email a meno del case; lo stage crea 2 utenti v2, con esattamente 1 warning che menziona l'email in conflitto).
- File/componente applicativo rilevante: `app/Import/Stages/UsersStage.php`; fixture `tests/Fixtures/Import/v1-ci-fixture.sql` (US-218, il caso "email duplicata case-insensitive" NON è incluso in questa fixture proprio per non far fallire il gate CI `v1:validate`, come annotato in CLAUDE.md).
- Test correlato: F2-06 (parsing ruoli), F2-70 (nome/email invariati con --anonymize).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test usa il trait `InteractsWithLegacyDatabase` con una tabella `users` v1 fixture creata dal test stesso.

**Dati di test**
Due utenti legacy: `id = 1, email = 'Mario@Example.test'` e `id = 2, email = 'mario@example.test'` (stessa email a meno del case).

**Stato iniziale**
Nessun utente v2 presente prima dell'esecuzione dello stage.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "reports case-insensitive duplicate emails without failing the stage"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: lo stage crea 2 utenti v2 (`created = 2`, nessun crash/eccezione) e produce esattamente 1 warning aggregato che menziona l'email `mario@example.test` in conflitto.

**Controlli negativi**
Nessuno applicabile: questo stesso test copre già sia l'esito positivo (creazione riuscita di entrambi gli utenti) sia l'esito di segnalazione (warning) come un unico scenario.

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
- Note: compromesso informativo (non blocca `v1:validate`, che tratta questo caso come integrità violata se le due righe finissero nella stessa fixture di gate CI — vedi CLAUDE.md).

