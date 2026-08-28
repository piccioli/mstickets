# Fase 8 (Integrazione dati RUNTS-CAI — Sezioni/Sottosezioni) — Casi di test dettagliati

> Torna a [`README.md`](README.md) · Istruzioni generali: [`00-istruzioni-generali.md`](00-istruzioni-generali.md) · Matrice di tracciabilità: [`01-matrice-tracciabilita.md`](01-matrice-tracciabilita.md)

52 casi di test (F8-01 — F8-52) su 8 argomenti. Prima di eseguire un test, leggi le convenzioni comuni in `00-istruzioni-generali.md` (in particolare le sezioni 8 "Ambiente UAT", 9 "Credenziali" e 12 "Prerequisiti generali"). Gli argomenti sono raggruppati per user story del PRD (schema dati, comando di import, wiring del deploy, Filament Resource staff, mappa ed export, dashboard cliente Sezione, dettaglio cliente Gruppo Regionale), in ordine di priorità US-801 -> US-808, più un ultimo argomento dedicato al checkpoint di fine fase (US-808, F8-52). I casi con Modalità di esecuzione MANUALE UI sono pensati per un tester che opera realmente sull'ambiente UAT (credenziali/URL: punti 8-9 di `00-istruzioni-generali.md`) dopo aver eseguito `cai:import-datapack` sul datapack sincronizzato (`/opt/mstickets-uat/cai-datapack`, US-803); i casi AUTOMATICO sono verificati eseguendo la suite Pest indicata (ruolo Sviluppatore) — entrambe le categorie sono sempre appoggiate a un test automatico REALMENTE esistente e verificato da `php artisan collaudo:verify-manifest 8`.

## Schema dati App\Domain\CaiDirectory — tabelle e relazioni (US-801)

### F8-01 — La tabella cai_sections ha le colonne richieste da US-801

**Obiettivo**
Verificare che: la tabella cai_sections ha le colonne richieste da US-801.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-801; design doc §3 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php` — `cai_sections table has the columns required by US-801`.
- File/componente applicativo rilevante: database/migrations (tabelle `cai_sections`/`cai_subsections`/`cai_runts_registrations`/`cai_financial_statements`/`cai_board_members`/`cai_documents`, US-801); `app/Domain/CaiDirectory/Models/*.php`.
- Test correlato: F8-02, F8-03, F8-04, F8-05, F8-06.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun datapack RUNTS-CAI reale richiesto: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai_sections table has the columns required by US-801"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la tabella cai_sections ha le colonne richieste da US-801.

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

### F8-02 — La tabella cai_subsections ha le colonne richieste da US-801

**Obiettivo**
Verificare che: la tabella cai_subsections ha le colonne richieste da US-801.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-801; design doc §3 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php` — `cai_subsections table has the columns required by US-801`.
- File/componente applicativo rilevante: database/migrations (tabelle `cai_sections`/`cai_subsections`/`cai_runts_registrations`/`cai_financial_statements`/`cai_board_members`/`cai_documents`, US-801); `app/Domain/CaiDirectory/Models/*.php`.
- Test correlato: F8-01, F8-08.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun datapack RUNTS-CAI reale richiesto: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai_subsections table has the columns required by US-801"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la tabella cai_subsections ha le colonne richieste da US-801.

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

### F8-03 — La tabella cai_runts_registrations ha le colonne richieste da US-801

**Obiettivo**
Verificare che: la tabella cai_runts_registrations ha le colonne richieste da US-801.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-801; design doc §3 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php` — `cai_runts_registrations table has the columns required by US-801`.
- File/componente applicativo rilevante: database/migrations (tabelle `cai_sections`/`cai_subsections`/`cai_runts_registrations`/`cai_financial_statements`/`cai_board_members`/`cai_documents`, US-801); `app/Domain/CaiDirectory/Models/*.php`.
- Test correlato: F8-10.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun datapack RUNTS-CAI reale richiesto: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai_runts_registrations table has the columns required by US-801"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la tabella cai_runts_registrations ha le colonne richieste da US-801.

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

### F8-04 — La tabella cai_financial_statements ha le colonne richieste da US-801

**Obiettivo**
Verificare che: la tabella cai_financial_statements ha le colonne richieste da US-801.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-801; design doc §3 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php` — `cai_financial_statements table has the columns required by US-801`.
- File/componente applicativo rilevante: database/migrations (tabelle `cai_sections`/`cai_subsections`/`cai_runts_registrations`/`cai_financial_statements`/`cai_board_members`/`cai_documents`, US-801); `app/Domain/CaiDirectory/Models/*.php`.
- Test correlato: F8-10.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun datapack RUNTS-CAI reale richiesto: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai_financial_statements table has the columns required by US-801"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la tabella cai_financial_statements ha le colonne richieste da US-801.

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

### F8-05 — La tabella cai_board_members ha le colonne richieste da US-801 (tabella vuota all'origine, struttura pronta)

**Obiettivo**
Verificare che: la tabella cai_board_members ha le colonne richieste da US-801 (tabella vuota all'origine, struttura pronta).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-801 (tabella vuota all'origine, struttura pronta); design doc §3 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php` — `cai_board_members table has the columns required by US-801`.
- File/componente applicativo rilevante: database/migrations (tabelle `cai_sections`/`cai_subsections`/`cai_runts_registrations`/`cai_financial_statements`/`cai_board_members`/`cai_documents`, US-801); `app/Domain/CaiDirectory/Models/*.php`.
- Test correlato: F8-10.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun datapack RUNTS-CAI reale richiesto: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai_board_members table has the columns required by US-801"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la tabella cai_board_members ha le colonne richieste da US-801 (tabella vuota all'origine, struttura pronta).

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

### F8-06 — La tabella cai_documents ha le colonne richieste da US-801

**Obiettivo**
Verificare che: la tabella cai_documents ha le colonne richieste da US-801.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-801; design doc §3 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php` — `cai_documents table has the columns required by US-801`.
- File/componente applicativo rilevante: database/migrations (tabelle `cai_sections`/`cai_subsections`/`cai_runts_registrations`/`cai_financial_statements`/`cai_board_members`/`cai_documents`, US-801); `app/Domain/CaiDirectory/Models/*.php`.
- Test correlato: F8-10, F8-26.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun datapack RUNTS-CAI reale richiesto: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai_documents table has the columns required by US-801"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la tabella cai_documents ha le colonne richieste da US-801.

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

### F8-07 — cai_sections usa codice_cai come chiave primaria naturale, non incrementale

**Obiettivo**
Verificare che: cai_sections usa codice_cai come chiave primaria naturale, non incrementale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-801 (chiave naturale, non un id incrementale); design doc §3 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php` — `cai_sections uses codice_cai as a natural, non-incrementing primary key`.
- File/componente applicativo rilevante: database/migrations (tabelle `cai_sections`/`cai_subsections`/`cai_runts_registrations`/`cai_financial_statements`/`cai_board_members`/`cai_documents`, US-801); `app/Domain/CaiDirectory/Models/*.php`.
- Test correlato: F8-01.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun datapack RUNTS-CAI reale richiesto: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai_sections uses codice_cai as a natural, non-incrementing primary key"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: cai_sections usa codice_cai come chiave primaria naturale, non incrementale.

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

### F8-08 — Una sezione ha molte sottosezioni e appartiene a un utente (relazioni Eloquent)

**Obiettivo**
Verificare che: una sezione ha molte sottosezioni e appartiene a un utente (relazioni Eloquent).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-801; design doc §3 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php` — `a section has many subsections and belongs to a user`.
- File/componente applicativo rilevante: database/migrations (tabelle `cai_sections`/`cai_subsections`/`cai_runts_registrations`/`cai_financial_statements`/`cai_board_members`/`cai_documents`, US-801); `app/Domain/CaiDirectory/Models/*.php`.
- Test correlato: F8-01, F8-02, F8-09.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun datapack RUNTS-CAI reale richiesto: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a section has many subsections and belongs to a user"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: una sezione ha molte sottosezioni e appartiene a un utente (relazioni Eloquent).

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

### F8-09 — Eliminare l'utente collegato lascia user_id della sezione a null (FK nullable, mai un errore)

**Obiettivo**
Verificare che: eliminare l'utente collegato lascia user_id della sezione a null (FK nullable, mai un errore).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-801 (`user_id` nullable, `nullOnDelete`); design doc §3 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php` — `deleting the linked user leaves the section user_id null`.
- File/componente applicativo rilevante: database/migrations (tabelle `cai_sections`/`cai_subsections`/`cai_runts_registrations`/`cai_financial_statements`/`cai_board_members`/`cai_documents`, US-801); `app/Domain/CaiDirectory/Models/*.php`.
- Test correlato: F8-08.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun datapack RUNTS-CAI reale richiesto: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "deleting the linked user leaves the section user_id null"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: eliminare l'utente collegato lascia user_id della sezione a null (FK nullable, mai un errore).

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

### F8-10 — Una registrazione RUNTS appartiene a una sezione e ha molti bilanci, cariche sociali e documenti

**Obiettivo**
Verificare che: una registrazione RUNTS appartiene a una sezione e ha molti bilanci, cariche sociali e documenti.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-801; design doc §3 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php` — `a runts registration belongs to a section and has many statements, board members and documents`.
- File/componente applicativo rilevante: database/migrations (tabelle `cai_sections`/`cai_subsections`/`cai_runts_registrations`/`cai_financial_statements`/`cai_board_members`/`cai_documents`, US-801); `app/Domain/CaiDirectory/Models/*.php`.
- Test correlato: F8-03, F8-04, F8-05, F8-06.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun datapack RUNTS-CAI reale richiesto: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a runts registration belongs to a section and has many statements, board members and documents"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: una registrazione RUNTS appartiene a una sezione e ha molti bilanci, cariche sociali e documenti.

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

## Comando cai:import-datapack — import e matching per email (US-802)

### F8-11 — Il file datapack mancante al percorso indicato stampa un messaggio esplicito e fallisce, mai un errore criptico

**Obiettivo**
Verificare che: il file datapack mancante al percorso indicato stampa un messaggio esplicito e fallisce, mai un errore criptico.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-802 (messaggio esplicito, mai un errore PDO criptico); design doc §5 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Console/CaiImportDatapackCommandTest.php` — `missing datapack file prints an explicit message and fails, no cryptic error`.
- File/componente applicativo rilevante: app/Console/Commands/CaiImportDatapackCommand.php.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Percorso file inesistente (`/tmp/does-not-exist-*.sqlite`).

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "missing datapack file prints an explicit message and fails, no cryptic error"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: il file datapack mancante al percorso indicato stampa un messaggio esplicito e fallisce, mai un errore criptico.

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

### F8-12 — L'opzione --dry-run non scrive alcuna riga né alcun file

**Obiettivo**
Verificare che: l'opzione --dry-run non scrive alcuna riga né alcun file.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-802 (`--dry-run` non scrive); design doc §5 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Console/CaiImportDatapackCommandTest.php` — `--dry-run writes nothing`.
- File/componente applicativo rilevante: app/Domain/CaiDirectory/Import/CaiDatapackImporter.php.
- Test correlato: F8-13.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun datapack reale richiesto: il test costruisce una fixture SQLite propria in un file temporaneo (estensione `pdo_sqlite`).

**Dati di test**
Fixture SQLite generata dal test stesso (2 sezioni, 1 sottosezione, enti/bilanci/cariche sociali/allegati collegati e non collegati).

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "--dry-run writes nothing"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: l'opzione --dry-run non scrive alcuna riga né alcun file.

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

### F8-13 — L'import completo popola le sei tabelle con i campi mappati correttamente, collega gli utenti per email case-insensitive, salta gli enti senza match e copia i file degli allegati

**Obiettivo**
Verificare che: l'import completo popola le sei tabelle con i campi mappati correttamente, collega gli utenti per email case-insensitive, salta gli enti senza match e copia i file degli allegati.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-802 (mapping campi, matching email case-insensitive, skip enti non collegati, copia allegati su storage privato); design doc §5 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Console/CaiImportDatapackCommandTest.php` — `full import populates all six tables with correctly mapped fields, matches users by email case-insensitively, skips unmatched enti and copies allegati files`.
- File/componente applicativo rilevante: app/Domain/CaiDirectory/Import/CaiDatapackImporter.php, app/Support/CaiRuntsAddressFormatter.php, app/Support/CaiRuntsDateParser.php.
- Test correlato: F8-12, F8-14.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun datapack reale richiesto: il test costruisce una fixture SQLite propria in un file temporaneo (estensione `pdo_sqlite`).

**Dati di test**
Fixture SQLite generata dal test stesso (2 sezioni, 1 sottosezione, enti/bilanci/cariche sociali/allegati collegati e non collegati).

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "full import populates all six tables with correctly mapped fields, matches users by email case-insensitively, skips unmatched enti and copies allegati files"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: l'import completo popola le sei tabelle con i campi mappati correttamente, collega gli utenti per email case-insensitive, salta gli enti senza match e copia i file degli allegati.

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

### F8-14 — Eseguire l'import due volte sulla stessa fixture è idempotente (nessun duplicato, righe invariate non riscritte)

**Obiettivo**
Verificare che: eseguire l'import due volte sulla stessa fixture è idempotente (nessun duplicato, righe invariate non riscritte).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-802 (idempotenza: una seconda esecuzione non duplica né riscrive righe invariate); design doc §5 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Console/CaiImportDatapackCommandTest.php` — `running the import twice against the same fixture is idempotent (no duplicates, unchanged rows not re-updated)`.
- File/componente applicativo rilevante: app/Domain/CaiDirectory/Import/CaiDatapackImporter.php.
- Test correlato: F8-13.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun datapack reale richiesto: il test costruisce una fixture SQLite propria in un file temporaneo (estensione `pdo_sqlite`).

**Dati di test**
Fixture SQLite generata dal test stesso (2 sezioni, 1 sottosezione, enti/bilanci/cariche sociali/allegati collegati e non collegati).

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "running the import twice against the same fixture is idempotent (no duplicates, unchanged rows not re-updated)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: eseguire l'import due volte sulla stessa fixture è idempotente (nessun duplicato, righe invariate non riscritte).

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

## Wiring dell'import in make setup e nel deploy UAT (US-803)

### F8-15 — make setup esegue cai:import-datapack best-effort, dopo v1:import

**Obiettivo**
Verificare che: make setup esegue cai:import-datapack best-effort, dopo v1:import.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-803 (`make setup` locale, best-effort se il datapack non è presente).
- Test automatico: `tests/Feature/Deploy/CaiDatapackWiringTest.php` — `runs cai:import-datapack best-effort in make setup, after v1:import`.
- File/componente applicativo rilevante: Makefile.
- Test correlato: F8-18.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun servizio Docker/SSH richiesto: il test legge il contenuto testuale di `Makefile`/`deploy/remote-deploy.sh`/`docker-compose.uat.yml`/`.env.uat.example`/`bin/push-cai-datapack`.

**Dati di test**
Contenuto testuale di `Makefile`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "runs cai:import-datapack best-effort in make setup, after v1:import"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: make setup esegue cai:import-datapack best-effort, dopo v1:import.

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

### F8-16 — CAI_DATAPACK_HOST_PATH è dichiarata in .env.uat.example, coerente col percorso remoto di default di bin/push-cai-datapack

**Obiettivo**
Verificare che: cAI_DATAPACK_HOST_PATH è dichiarata in .env.uat.example, coerente col percorso remoto di default di bin/push-cai-datapack.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-803 (`CAI_DATAPACK_HOST_PATH`, stesso pattern di `LEGACY_MEDIA_HOST_PATH`).
- Test automatico: `tests/Feature/Deploy/CaiDatapackWiringTest.php` — `declares CAI_DATAPACK_HOST_PATH in .env.uat.example, matching bin/push-cai-datapack default remote path`.
- File/componente applicativo rilevante: .env.uat.example, bin/push-cai-datapack.
- Test correlato: F8-17.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun servizio Docker/SSH richiesto: il test legge il contenuto testuale di `Makefile`/`deploy/remote-deploy.sh`/`docker-compose.uat.yml`/`.env.uat.example`/`bin/push-cai-datapack`.

**Dati di test**
Contenuto testuale di `.env.uat.example` e `bin/push-cai-datapack`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "declares CAI_DATAPACK_HOST_PATH in .env.uat.example, matching bin/push-cai-datapack default remote path"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: cAI_DATAPACK_HOST_PATH è dichiarata in .env.uat.example, coerente col percorso remoto di default di bin/push-cai-datapack.

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

### F8-17 — CAI_DATAPACK_HOST_PATH è montata in sola lettura nel servizio app, stesso pattern di LEGACY_MEDIA_HOST_PATH

**Obiettivo**
Verificare che: cAI_DATAPACK_HOST_PATH è montata in sola lettura nel servizio app, stesso pattern di LEGACY_MEDIA_HOST_PATH.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-803 (bind-mount in sola lettura del servizio app).
- Test automatico: `tests/Feature/Deploy/CaiDatapackWiringTest.php` — `bind-mounts CAI_DATAPACK_HOST_PATH read-only into the app service, same pattern as LEGACY_MEDIA_HOST_PATH`.
- File/componente applicativo rilevante: docker-compose.uat.yml.
- Test correlato: F8-16.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun servizio Docker/SSH richiesto: il test legge il contenuto testuale di `Makefile`/`deploy/remote-deploy.sh`/`docker-compose.uat.yml`/`.env.uat.example`/`bin/push-cai-datapack`.

**Dati di test**
Contenuto testuale di `docker-compose.uat.yml`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "bind-mounts CAI_DATAPACK_HOST_PATH read-only into the app service, same pattern as LEGACY_MEDIA_HOST_PATH"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: cAI_DATAPACK_HOST_PATH è montata in sola lettura nel servizio app, stesso pattern di LEGACY_MEDIA_HOST_PATH.

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

### F8-18 — remote-deploy.sh esegue cai:import-datapack in modo incondizionato, dopo v1:import --anonymize, con il commento esplicito sulla ricopiatura manuale

**Obiettivo**
Verificare che: remote-deploy.sh esegue cai:import-datapack in modo incondizionato, dopo v1:import --anonymize, con il commento esplicito sulla ricopiatura manuale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-803 (esecuzione incondizionata nel deploy UAT, commento esplicito sulla ricopiatura manuale su msuat).
- Test automatico: `tests/Feature/Deploy/CaiDatapackWiringTest.php` — `runs cai:import-datapack unconditionally in remote-deploy.sh, after v1:import --anonymize`.
- File/componente applicativo rilevante: deploy/remote-deploy.sh.
- Test correlato: F8-15.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun servizio Docker/SSH richiesto: il test legge il contenuto testuale di `Makefile`/`deploy/remote-deploy.sh`/`docker-compose.uat.yml`/`.env.uat.example`/`bin/push-cai-datapack`.

**Dati di test**
Contenuto testuale di `deploy/remote-deploy.sh`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "runs cai:import-datapack unconditionally in remote-deploy.sh, after v1:import --anonymize"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: remote-deploy.sh esegue cai:import-datapack in modo incondizionato, dopo v1:import --anonymize, con il commento esplicito sulla ricopiatura manuale.

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

## Filament Resource staff — consultazione Sezioni/Sottosezioni CAI (US-804)

### F8-19 — Un utente senza cai-directory.view non accede alla lista né al dettaglio

**Obiettivo**
Verificare che: un utente senza cai-directory.view non accede alla lista né al dettaglio.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-804 (gated da `Permission::CaiDirectoryView`); design doc §6 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `a user without cai-directory.view is denied access to the list and detail pages`.
- File/componente applicativo rilevante: app/Filament/Resources/CaiSections/CaiSectionResource.php.
- Test correlato: F8-20, F8-27.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente Developer SENZA il permesso `cai-directory.view` (rimuoverlo temporaneamente se necessario, punto 13 di `00-istruzioni-generali.md`).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Nessuno specifico: il tentativo di accesso avviene prima di ogni verifica sui dati.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con un utente Developer privo del permesso `cai-directory.view` | credenziali di test | Login riuscito |
| 2 | Tenta di aprire la lista "Anagrafica CAI" e il dettaglio di una sezione qualunque | URL diretto della risorsa | Entrambe le richieste rispondono 403 Forbidden |

**Risultato finale atteso**
Un utente senza cai-directory.view non accede alla lista né al dettaglio

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-20 — Un utente con cai-directory.view accede alla lista e vede le colonne attese

**Obiettivo**
Verificare che: un utente con cai-directory.view accede alla lista e vede le colonne attese.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-804 (colonne principali: denominazione, comune, regione, utente collegato); design doc §6 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `a user with cai-directory.view can access the list page and sees the expected columns`.
- File/componente applicativo rilevante: app/Filament/Resources/CaiSections/Pages/ListCaiSections.php.
- Test correlato: F8-19, F8-22, F8-23.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer), con permesso `cai-directory.view` (concesso di default a admin/manager/developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- `cai:import-datapack` già eseguito sul datapack sincronizzato in UAT (US-803): almeno una sezione con dati RUNTS/bilanci/allegati presente.

**Dati di test**
Dati reali del dataset UAT importati dal datapack RUNTS-CAI (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente, dopo un import del datapack RUNTS-CAI.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com | Login riuscito |
| 2 | Apri "Anagrafica CAI" dal menu di navigazione staff | — | La lista mostra le sezioni importate con denominazione, comune/regione ed eventuale utente collegato |

**Risultato finale atteso**
Un utente con cai-directory.view accede alla lista e vede le colonne attese

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-21 — La risorsa è di sola consultazione: nessuna funzione di creazione, modifica o cancellazione

**Obiettivo**
Verificare che: la risorsa è di sola consultazione: nessuna funzione di creazione, modifica o cancellazione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-804 (sola consultazione: nessuna azione Create/Edit/Delete); design doc §6 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `the resource has no create, edit or delete function`.
- File/componente applicativo rilevante: app/Filament/Resources/CaiSections/CaiSectionResource.php.
- Test correlato: F8-20.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer), con permesso `cai-directory.view` (concesso di default a admin/manager/developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- `cai:import-datapack` già eseguito sul datapack sincronizzato in UAT (US-803): almeno una sezione con dati RUNTS/bilanci/allegati presente.

**Dati di test**
Dati reali del dataset UAT importati dal datapack RUNTS-CAI (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente, dopo un import del datapack RUNTS-CAI.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer e apri "Anagrafica CAI" | lorena.sava@montagnaservizi.com | Login riuscito, lista visibile |
| 2 | Verifica l'assenza di pulsanti/azioni "Nuovo", "Modifica" o "Elimina" in lista e in dettaglio | — | Nessuna azione di scrittura è presente in nessuna delle due schermate |

**Risultato finale atteso**
La risorsa è di sola consultazione: nessuna funzione di creazione, modifica o cancellazione

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-22 — La tabella è filtrabile per regione

**Obiettivo**
Verificare che: la tabella è filtrabile per regione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-804 (filtro per regione); design doc §6 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `the table is filterable by region`.
- File/componente applicativo rilevante: app/Filament/Resources/CaiSections/Pages/ListCaiSections.php.
- Test correlato: F8-20, F8-23.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer), con permesso `cai-directory.view` (concesso di default a admin/manager/developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- `cai:import-datapack` già eseguito sul datapack sincronizzato in UAT (US-803): almeno una sezione con dati RUNTS/bilanci/allegati presente.

**Dati di test**
Dati reali del dataset UAT importati dal datapack RUNTS-CAI (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente, dopo un import del datapack RUNTS-CAI.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer e apri "Anagrafica CAI" | lorena.sava@montagnaservizi.com | Login riuscito, lista visibile |
| 2 | Applica il filtro Regione su una regione presente nel dataset | es. Lombardia | La lista mostra solo le sezioni di quella regione |

**Risultato finale atteso**
La tabella è filtrabile per regione

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-23 — La tabella è filtrabile per presenza di un utente collegato

**Obiettivo**
Verificare che: la tabella è filtrabile per presenza di un utente collegato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-804 (filtro per presenza di un utente collegato); design doc §6 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `the table is filterable by presence of a linked user`.
- File/componente applicativo rilevante: app/Filament/Resources/CaiSections/Pages/ListCaiSections.php.
- Test correlato: F8-20, F8-22.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer), con permesso `cai-directory.view` (concesso di default a admin/manager/developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- `cai:import-datapack` già eseguito sul datapack sincronizzato in UAT (US-803): almeno una sezione con dati RUNTS/bilanci/allegati presente.

**Dati di test**
Dati reali del dataset UAT importati dal datapack RUNTS-CAI (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente, dopo un import del datapack RUNTS-CAI.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer e apri "Anagrafica CAI" | lorena.sava@montagnaservizi.com | Login riuscito, lista visibile |
| 2 | Applica il filtro "Con utente collegato" | — | La lista mostra solo le sezioni con un cliente Sezione collegato (`user_id` valorizzato) |

**Risultato finale atteso**
La tabella è filtrabile per presenza di un utente collegato

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-24 — Il dettaglio di una sezione con dati RUNTS, bilanci e allegati mostra i dati attesi

**Obiettivo**
Verificare che: il dettaglio di una sezione con dati RUNTS, bilanci e allegati mostra i dati attesi.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-804 (dettaglio: contatti, indirizzo, anno fondazione, soci, dati RUNTS, bilanci, allegati, sottosezioni); design doc §6 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `viewing a section with runts data, statements and attachments shows the expected data`.
- File/componente applicativo rilevante: app/Filament/Resources/CaiSections/Pages/ViewCaiSection.php.
- Test correlato: F8-25.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer), con permesso `cai-directory.view` (concesso di default a admin/manager/developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- `cai:import-datapack` già eseguito sul datapack sincronizzato in UAT (US-803): almeno una sezione con dati RUNTS/bilanci/allegati presente.

**Dati di test**
Una sezione reale con almeno un bilancio e un allegato (verificare empiricamente, punto 13).

**Stato iniziale**
Dataset UAT nello stato corrente, dopo un import del datapack RUNTS-CAI.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer e apri "Anagrafica CAI" | lorena.sava@montagnaservizi.com | Login riuscito, lista visibile |
| 2 | Apri il dettaglio di una sezione con bilanci e allegati | click sulla riga | I tab Dati CAI/Dati RUNTS/Bilanci/Allegati/Sottosezioni mostrano i dati attesi |

**Risultato finale atteso**
Il dettaglio di una sezione con dati RUNTS, bilanci e allegati mostra i dati attesi

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-25 — Il dettaglio di una sezione senza dati RUNTS, bilanci o allegati non genera errori e mostra stati vuoti

**Obiettivo**
Verificare che: il dettaglio di una sezione senza dati RUNTS, bilanci o allegati non genera errori e mostra stati vuoti.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-804 (nessun errore per una sezione senza bilanci/allegati/dati RUNTS); design doc §6 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `viewing a section without runts data, statements or attachments does not crash and shows empty states`.
- File/componente applicativo rilevante: app/Filament/Resources/CaiSections/Pages/ViewCaiSection.php.
- Test correlato: F8-24.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer), con permesso `cai-directory.view` (concesso di default a admin/manager/developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- `cai:import-datapack` già eseguito sul datapack sincronizzato in UAT (US-803): almeno una sezione con dati RUNTS/bilanci/allegati presente.

**Dati di test**
Una sezione senza bilanci né allegati né registrazione RUNTS collegata.

**Stato iniziale**
Dataset UAT nello stato corrente, dopo un import del datapack RUNTS-CAI.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer e apri "Anagrafica CAI" | lorena.sava@montagnaservizi.com | Login riuscito, lista visibile |
| 2 | Apri il dettaglio di una sezione priva di dati RUNTS/bilanci/allegati | click sulla riga | La pagina si apre senza errori, con stati vuoti espliciti nei tab pertinenti |

**Risultato finale atteso**
Il dettaglio di una sezione senza dati RUNTS, bilanci o allegati non genera errori e mostra stati vuoti

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-26 — Un utente autorizzato può scaricare un documento CAI

**Obiettivo**
Verificare che: un utente autorizzato può scaricare un documento CAI.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-804 (download autorizzato coerente col pattern allegati ticket, Fase 1); design doc §6 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `an authorized user can download a cai document`.
- File/componente applicativo rilevante: app/Http/Controllers/CaiDocumentDownloadController.php.
- Test correlato: F8-27, F8-28, F8-30.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer), con permesso `cai-directory.view` (concesso di default a admin/manager/developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- `cai:import-datapack` già eseguito sul datapack sincronizzato in UAT (US-803): almeno una sezione con dati RUNTS/bilanci/allegati presente.

**Dati di test**
Una sezione con almeno un allegato importato.

**Stato iniziale**
Dataset UAT nello stato corrente, dopo un import del datapack RUNTS-CAI.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer e apri il dettaglio di una sezione con allegati | lorena.sava@montagnaservizi.com | Tab Allegati visibile con almeno un file |
| 2 | Clicca sul link di download di un allegato | — | Il file viene scaricato correttamente (200, contenuto atteso) |

**Risultato finale atteso**
Un utente autorizzato può scaricare un documento CAI

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-27 — Un utente senza cai-directory.view non può scaricare un documento CAI

**Obiettivo**
Verificare che: un utente senza cai-directory.view non può scaricare un documento CAI.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-804 (download gated dallo stesso permesso della risorsa); design doc §6 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `a user without cai-directory.view is denied downloading a cai document`.
- File/componente applicativo rilevante: app/Http/Controllers/CaiDocumentDownloadController.php.
- Test correlato: F8-19, F8-26.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente Developer SENZA il permesso `cai-directory.view`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- URL diretto di download di un documento CAI esistente (individuato con un account autorizzato).

**Dati di test**
Un documento CAI esistente.

**Stato iniziale**
Dataset UAT nello stato corrente, dopo un import del datapack RUNTS-CAI.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con un utente Developer privo del permesso `cai-directory.view` | credenziali di test | Login riuscito |
| 2 | Tenta di aprire l'URL diretto di download di un documento CAI | URL diretto | La richiesta risponde 403 Forbidden |

**Risultato finale atteso**
Un utente senza cai-directory.view non può scaricare un documento CAI

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-28 — Un cliente può scaricare un documento della propria sezione CAI

**Obiettivo**
Verificare che: un cliente può scaricare un documento della propria sezione CAI.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-806 (allegati scaricabili dalla dashboard cliente Sezione); design doc §7 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `a customer can download a document belonging to their own cai section`.
- File/componente applicativo rilevante: app/Http/Controllers/CaiDocumentDownloadController.php.
- Test correlato: F8-26, F8-29.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Sezione realmente collegato a una `CaiSection`/`CaiSubsection` (`user_id`), individuato empiricamente sul dataset UAT (punto 13 di `00-istruzioni-generali.md`) — in assenza di un match reale, collegare temporaneamente un cliente Sezione di test e rimuovere il collegamento a fine collaudo.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- La sezione collegata deve avere almeno un allegato importato.

**Dati di test**
Dati reali del dataset UAT importati dal datapack RUNTS-CAI (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT con un cliente Sezione collegato a una CaiSection con allegati.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account cliente Sezione collegato | credenziali del cliente Sezione | Login riuscito, dashboard visibile con la card dati CAI/RUNTS |
| 2 | Apri il tab Allegati della card e clicca su un link di download | — | Il file viene scaricato correttamente (200) |

**Risultato finale atteso**
Un cliente può scaricare un documento della propria sezione CAI

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-29 — Un cliente non può scaricare un documento di un'altra sezione CAI

**Obiettivo**
Verificare che: un cliente non può scaricare un documento di un'altra sezione CAI.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-806 (mai i dati/allegati di un'altra sezione); design doc §7 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `a customer cannot download a document belonging to another cai section`.
- File/componente applicativo rilevante: app/Http/Controllers/CaiDocumentDownloadController.php.
- Test correlato: F8-28.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Sezione realmente collegato a una `CaiSection`/`CaiSubsection` (`user_id`), individuato empiricamente sul dataset UAT (punto 13 di `00-istruzioni-generali.md`) — in assenza di un match reale, collegare temporaneamente un cliente Sezione di test e rimuovere il collegamento a fine collaudo.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- URL diretto di download di un documento appartenente a una sezione DIVERSA da quella collegata al proprio account.

**Dati di test**
Un documento CAI di una sezione diversa da quella del cliente autenticato.

**Stato iniziale**
Dataset UAT con almeno due sezioni con allegati.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account cliente Sezione collegato | credenziali del cliente Sezione | Login riuscito |
| 2 | Tenta di aprire l'URL diretto di download di un documento di un'altra sezione | URL diretto | La richiesta risponde 403 Forbidden |

**Risultato finale atteso**
Un cliente non può scaricare un documento di un'altra sezione CAI

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-30 — Un cliente Gruppo Regionale può scaricare un documento di una sezione della propria regione

**Obiettivo**
Verificare che: un cliente Gruppo Regionale può scaricare un documento di una sezione della propria regione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-807 (download per un cliente Gruppo Regionale, sezioni della propria regione); design doc §7 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `a gruppo regionale customer can download a document belonging to a section in their own region`.
- File/componente applicativo rilevante: app/Http/Controllers/CaiDocumentDownloadController.php.
- Test correlato: F8-31, F8-43.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Gruppo Regionale (`customer_type = GruppoRegionale`, `region` valorizzata) con almeno una Sezione classificata nella stessa regione, individuato empiricamente sul dataset UAT (punto 13 di `00-istruzioni-generali.md`).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- La sezione della stessa regione deve avere almeno un allegato importato.

**Dati di test**
Dati reali del dataset UAT importati dal datapack RUNTS-CAI (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT con un cliente Gruppo Regionale e una Sezione con allegati nella stessa regione.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account cliente Gruppo Regionale | credenziali del Gruppo Regionale | Login riuscito, dashboard con la card "Sezioni del gruppo regionale" |
| 2 | Apri il dettaglio di una sezione della propria regione e scarica un allegato | click sulla riga, poi sul link di download | Il file viene scaricato correttamente (200) |

**Risultato finale atteso**
Un cliente Gruppo Regionale può scaricare un documento di una sezione della propria regione

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-31 — Un cliente Gruppo Regionale non può scaricare un documento di una sezione di un'altra regione

**Obiettivo**
Verificare che: un cliente Gruppo Regionale non può scaricare un documento di una sezione di un'altra regione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-807 (accesso negato fuori dalla propria regione); design doc §7 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `a gruppo regionale customer cannot download a document belonging to a section in another region`.
- File/componente applicativo rilevante: app/Http/Controllers/CaiDocumentDownloadController.php.
- Test correlato: F8-30, F8-44.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Gruppo Regionale (`customer_type = GruppoRegionale`, `region` valorizzata) con almeno una Sezione classificata nella stessa regione, individuato empiricamente sul dataset UAT (punto 13 di `00-istruzioni-generali.md`).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- URL diretto di download di un documento di una sezione di un'altra regione.

**Dati di test**
Un documento CAI di una sezione fuori dalla regione del Gruppo Regionale autenticato.

**Stato iniziale**
Dataset UAT con sezioni con allegati in più regioni.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account cliente Gruppo Regionale | credenziali del Gruppo Regionale | Login riuscito |
| 2 | Tenta di aprire l'URL diretto di download di un documento di una sezione di un'altra regione | URL diretto | La richiesta risponde 403 Forbidden |

**Risultato finale atteso**
Un cliente Gruppo Regionale non può scaricare un documento di una sezione di un'altra regione

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

## Mappa e export (staff, US-805)

### F8-32 — Un utente senza cai-directory.view non accede alla pagina mappa

**Obiettivo**
Verificare che: un utente senza cai-directory.view non accede alla pagina mappa.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-805 (gated dallo stesso permesso della risorsa).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionsMapTest.php` — `a user without cai-directory.view is denied access to the map page`.
- File/componente applicativo rilevante: app/Filament/Pages/CaiSectionsMap.php.
- Test correlato: F8-33.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente Developer SENZA il permesso `cai-directory.view`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Nessuno specifico.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con un utente Developer privo del permesso `cai-directory.view` | credenziali di test | Login riuscito |
| 2 | Tenta di aprire la pagina "Mappa sezioni" | URL diretto della pagina | La richiesta risponde 403 Forbidden |

**Risultato finale atteso**
Un utente senza cai-directory.view non accede alla pagina mappa

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-33 — Un utente con cai-directory.view vede sulla mappa solo le sezioni geolocalizzate

**Obiettivo**
Verificare che: un utente con cai-directory.view vede sulla mappa solo le sezioni geolocalizzate.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-805 (mappa Leaflet, tutte le sezioni geolocalizzate).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionsMapTest.php` — `a user with cai-directory.view sees only geolocated sections on the map`.
- File/componente applicativo rilevante: app/Filament/Pages/CaiSectionsMap.php.
- Test correlato: F8-32.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer), con permesso `cai-directory.view` (concesso di default a admin/manager/developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- `cai:import-datapack` già eseguito sul datapack sincronizzato in UAT (US-803): almeno una sezione con dati RUNTS/bilanci/allegati presente.

**Dati di test**
Dati reali del dataset UAT importati dal datapack RUNTS-CAI (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente, dopo un import del datapack RUNTS-CAI.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com | Login riuscito |
| 2 | Apri "Mappa sezioni" dal menu di navigazione staff | — | La mappa mostra un marker per ciascuna sezione con coordinate valide, nessun marker per le sezioni senza coordinate o con outlier geocodificati fuori range |

**Risultato finale atteso**
Un utente con cai-directory.view vede sulla mappa solo le sezioni geolocalizzate

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-34 — Un utente con cai-directory.view può esportare le sezioni correntemente filtrate in CSV

**Obiettivo**
Verificare che: un utente con cai-directory.view può esportare le sezioni correntemente filtrate in CSV.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-805 (export CSV delle sole sezioni filtrate/visibili).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionsExportTest.php` — `a user with cai-directory.view can export the currently filtered sections as csv`.
- File/componente applicativo rilevante: app/Filament/Resources/CaiSections/Support/CaiSectionsExporter.php.
- Test correlato: F8-35, F8-36, F8-37.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer), con permesso `cai-directory.view` (concesso di default a admin/manager/developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- `cai:import-datapack` già eseguito sul datapack sincronizzato in UAT (US-803): almeno una sezione con dati RUNTS/bilanci/allegati presente.

**Dati di test**
Dati reali del dataset UAT importati dal datapack RUNTS-CAI (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente, dopo un import del datapack RUNTS-CAI.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer e apri "Anagrafica CAI", applica un filtro (es. regione) | lorena.sava@montagnaservizi.com | Lista filtrata visibile |
| 2 | Avvia l'azione di export "CSV" | — | Viene scaricato un file CSV contenente solo le sezioni correntemente filtrate |

**Risultato finale atteso**
Un utente con cai-directory.view può esportare le sezioni correntemente filtrate in CSV

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-35 — Un utente con cai-directory.view può esportare le sezioni correntemente filtrate in GeoJSON

**Obiettivo**
Verificare che: un utente con cai-directory.view può esportare le sezioni correntemente filtrate in GeoJSON.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-805 (export GeoJSON delle sole sezioni filtrate/visibili).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionsExportTest.php` — `a user with cai-directory.view can export the currently filtered sections as geojson`.
- File/componente applicativo rilevante: app/Filament/Resources/CaiSections/Support/CaiSectionsExporter.php.
- Test correlato: F8-34, F8-36, F8-37.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer), con permesso `cai-directory.view` (concesso di default a admin/manager/developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- `cai:import-datapack` già eseguito sul datapack sincronizzato in UAT (US-803): almeno una sezione con dati RUNTS/bilanci/allegati presente.

**Dati di test**
Dati reali del dataset UAT importati dal datapack RUNTS-CAI (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente, dopo un import del datapack RUNTS-CAI.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer e apri "Anagrafica CAI", applica un filtro (es. regione) | lorena.sava@montagnaservizi.com | Lista filtrata visibile |
| 2 | Avvia l'azione di export "GeoJSON" | — | Viene scaricato un file GeoJSON valido contenente solo le sezioni correntemente filtrate |

**Risultato finale atteso**
Un utente con cai-directory.view può esportare le sezioni correntemente filtrate in GeoJSON

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-36 — Un utente con cai-directory.view può esportare le sezioni correntemente filtrate in XLSX

**Obiettivo**
Verificare che: un utente con cai-directory.view può esportare le sezioni correntemente filtrate in XLSX.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-805 (export XLSX delle sole sezioni filtrate/visibili).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionsExportTest.php` — `a user with cai-directory.view can export the currently filtered sections as xlsx`.
- File/componente applicativo rilevante: app/Filament/Resources/CaiSections/Support/CaiSectionsExporter.php.
- Test correlato: F8-34, F8-35, F8-37.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer), con permesso `cai-directory.view` (concesso di default a admin/manager/developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- `cai:import-datapack` già eseguito sul datapack sincronizzato in UAT (US-803): almeno una sezione con dati RUNTS/bilanci/allegati presente.

**Dati di test**
Dati reali del dataset UAT importati dal datapack RUNTS-CAI (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente, dopo un import del datapack RUNTS-CAI.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer e apri "Anagrafica CAI", applica un filtro (es. regione) | lorena.sava@montagnaservizi.com | Lista filtrata visibile |
| 2 | Avvia l'azione di export "XLSX" | — | Viene scaricato un file XLSX apribile contenente solo le sezioni correntemente filtrate |

**Risultato finale atteso**
Un utente con cai-directory.view può esportare le sezioni correntemente filtrate in XLSX

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-37 — Un utente senza cai-directory.view non vede le azioni di export

**Obiettivo**
Verificare che: un utente senza cai-directory.view non vede le azioni di export.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-805 (azioni di export gated dallo stesso permesso).
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionsExportTest.php` — `a user without cai-directory.view cannot see the export actions`.
- File/componente applicativo rilevante: app/Filament/Resources/CaiSections/CaiSectionResource.php.
- Test correlato: F8-34, F8-35, F8-36.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente Developer SENZA il permesso `cai-directory.view`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Nessuno specifico.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con un utente Developer privo del permesso `cai-directory.view` | credenziali di test | Login riuscito (se possibile accedere al pannello con altri permessi) |
| 2 | Verifica l'assenza delle azioni CSV/XLSX/GeoJSON | — | Nessuna azione di export è visibile |

**Risultato finale atteso**
Un utente senza cai-directory.view non vede le azioni di export

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

## Dati CAI sulla dashboard del cliente Sezione (US-806)

### F8-38 — La card CAI mostra i dati della sezione collegata per un cliente Sezione

**Obiettivo**
Verificare che: la card CAI mostra i dati della sezione collegata per un cliente Sezione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-806 (contatti ufficiali, anno fondazione, soci, bilanci, allegati, sottosezioni); design doc §7 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the cai directory card shows the linked cai section data for a sezione customer`.
- File/componente applicativo rilevante: app/Filament/Pages/CustomerDashboard.php.
- Test correlato: F8-39, F8-40, F8-41.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Sezione realmente collegato a una `CaiSection`/`CaiSubsection` (`user_id`), individuato empiricamente sul dataset UAT (punto 13 di `00-istruzioni-generali.md`) — in assenza di un match reale, collegare temporaneamente un cliente Sezione di test e rimuovere il collegamento a fine collaudo.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT importati dal datapack RUNTS-CAI (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT con un cliente Sezione collegato a una CaiSection.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account cliente Sezione collegato | credenziali del cliente Sezione | Login riuscito |
| 2 | Apri la dashboard cliente e individua la card "I miei dati CAI/RUNTS" | — | La card mostra contatti, anno fondazione, soci, bilanci, allegati e sottosezioni della propria sezione |

**Risultato finale atteso**
La card CAI mostra i dati della sezione collegata per un cliente Sezione

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-39 — La card CAI non mostra mai i dati di un'altra sezione

**Obiettivo**
Verificare che: la card CAI non mostra mai i dati di un'altra sezione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-806 (isolamento: mai i dati di un'altra sezione); design doc §7 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the cai directory card never leaks another sezione's data`.
- File/componente applicativo rilevante: app/Filament/Pages/CustomerDashboard.php.
- Test correlato: F8-38.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Sezione realmente collegato a una `CaiSection`/`CaiSubsection` (`user_id`), individuato empiricamente sul dataset UAT (punto 13 di `00-istruzioni-generali.md`) — in assenza di un match reale, collegare temporaneamente un cliente Sezione di test e rimuovere il collegamento a fine collaudo.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali di almeno due sezioni CAI distinte.

**Stato iniziale**
Dataset UAT con almeno due clienti Sezione collegati a sezioni diverse.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account cliente Sezione collegato | credenziali del cliente Sezione | Login riuscito |
| 2 | Verifica che la card CAI/RUNTS mostri solo i dati della propria sezione | — | Nessun dato di un'altra sezione (nome, contatti, bilanci) compare nella pagina |

**Risultato finale atteso**
La card CAI non mostra mai i dati di un'altra sezione

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-40 — La card CAI mostra i dati della sottosezione collegata quando nessuna sezione è collegata

**Obiettivo**
Verificare che: la card CAI mostra i dati della sottosezione collegata quando nessuna sezione è collegata.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-806 (collegamento anche a una CaiSubsection); design doc §7 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the cai directory card shows the linked cai subsection data when no cai section is linked`.
- File/componente applicativo rilevante: app/Filament/Pages/CustomerDashboard.php.
- Test correlato: F8-38.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Sezione collegato a una `CaiSubsection` (non a una `CaiSection`), individuato empiricamente sul dataset UAT (punto 13 di `00-istruzioni-generali.md`).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT importati dal datapack RUNTS-CAI (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT con un cliente Sezione collegato a una CaiSubsection.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account cliente collegato a una sottosezione | credenziali del cliente | Login riuscito |
| 2 | Apri la dashboard cliente e individua la card "I miei dati CAI/RUNTS" | — | La card mostra i soli contatti diretti della sottosezione (nessun campo RUNTS/bilanci/sottosezioni proprie) |

**Risultato finale atteso**
La card CAI mostra i dati della sottosezione collegata quando nessuna sezione è collegata

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-41 — La card CAI mostra uno stato vuoto esplicito per un cliente Sezione senza sezione o sottosezione collegata

**Obiettivo**
Verificare che: la card CAI mostra uno stato vuoto esplicito per un cliente Sezione senza sezione o sottosezione collegata.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-806 (stato vuoto esplicito, mai una card assente silenziosa); design doc §7 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the cai directory card shows an explicit empty state for a sezione customer without a linked cai section or subsection`.
- File/componente applicativo rilevante: app/Filament/Pages/CustomerDashboard.php.
- Test correlato: F8-38.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Sezione (`customer_type = Sezione`) senza alcuna `CaiSection`/`CaiSubsection` collegata (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Nessuna sezione/sottosezione CAI collegata.

**Stato iniziale**
Dataset UAT con un cliente Sezione senza alcun collegamento CaiSection/CaiSubsection.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account cliente Sezione senza match | credenziali del cliente | Login riuscito |
| 2 | Apri la dashboard cliente e individua la card "I miei dati CAI/RUNTS" | — | La card mostra il messaggio esplicito "Nessun dato CAI/RUNTS disponibile per la tua sezione" |

**Risultato finale atteso**
La card CAI mostra uno stato vuoto esplicito per un cliente Sezione senza sezione o sottosezione collegata

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-42 — La card CAI è assente per i clienti non-Sezione

**Obiettivo**
Verificare che: la card CAI è assente per i clienti non-Sezione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-806 (card presente solo per customer_type = Sezione); design doc §7 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the cai directory card is absent for non-sezione customers`.
- File/componente applicativo rilevante: app/Filament/Pages/CustomerDashboard.php.
- Test correlato: F8-38.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente con `customer_type` diverso da Sezione (es. Gruppo Regionale, Organo Tecnico/Struttura Operativa o Generico).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Nessuno specifico.

**Stato iniziale**
Dataset UAT con un cliente non-Sezione.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con un cliente non-Sezione | credenziali del cliente | Login riuscito |
| 2 | Apri la dashboard cliente | — | La card "I miei dati CAI/RUNTS" non è presente |

**Risultato finale atteso**
La card CAI è assente per i clienti non-Sezione

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

## Dettaglio sezione dalla dashboard del Gruppo Regionale (US-807)

### F8-43 — Un cliente Gruppo Regionale può aprire il dettaglio di una sezione della propria regione

**Obiettivo**
Verificare che: un cliente Gruppo Regionale può aprire il dettaglio di una sezione della propria regione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-807 (link dalla card "Sezioni del gruppo regionale", dettaglio della propria regione).
- Test automatico: `tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php` — `a gruppo regionale customer can open the detail of a section in their own region`.
- File/componente applicativo rilevante: app/Filament/Pages/CaiSectionRegionalDetail.php.
- Test correlato: F8-44, F8-49, F8-51.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Gruppo Regionale (`customer_type = GruppoRegionale`, `region` valorizzata) con almeno una Sezione classificata nella stessa regione, individuato empiricamente sul dataset UAT (punto 13 di `00-istruzioni-generali.md`).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT importati dal datapack RUNTS-CAI (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT con un cliente Gruppo Regionale e una Sezione classificata nella stessa regione.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account cliente Gruppo Regionale | credenziali del Gruppo Regionale | Login riuscito, card "Sezioni del gruppo regionale" visibile |
| 2 | Clicca su una riga della card per aprire il dettaglio | click sulla riga | La pagina di dettaglio si apre mostrando i dati della sezione scelta |

**Risultato finale atteso**
Un cliente Gruppo Regionale può aprire il dettaglio di una sezione della propria regione

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-44 — Un tentativo diretto di aprire una sezione di un'altra regione è respinto (403)

**Obiettivo**
Verificare che: un tentativo diretto di aprire una sezione di un'altra regione è respinto (403).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-807 (autorizzazione verificata lato server, non solo assente dal link in UI).
- Test automatico: `tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php` — `a direct attempt to open a section of another region is forbidden`.
- File/componente applicativo rilevante: app/Filament/Pages/CaiSectionRegionalDetail.php.
- Test correlato: F8-43, F8-31.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Gruppo Regionale (`customer_type = GruppoRegionale`, `region` valorizzata) con almeno una Sezione classificata nella stessa regione, individuato empiricamente sul dataset UAT (punto 13 di `00-istruzioni-generali.md`).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- Una Sezione classificata in una regione DIVERSA da quella del Gruppo Regionale autenticato.

**Dati di test**
Una sezione di una regione diversa da quella del Gruppo Regionale.

**Stato iniziale**
Dataset UAT con sezioni classificate in più regioni.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account cliente Gruppo Regionale | credenziali del Gruppo Regionale | Login riuscito |
| 2 | Componi manualmente l'URL di dettaglio di una sezione di un'altra regione e aprilo | URL manipolato | La richiesta risponde 403 Forbidden |

**Risultato finale atteso**
Un tentativo diretto di aprire una sezione di un'altra regione è respinto (403)

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-45 — Un cliente Gruppo Regionale senza regione valorizzata non può aprire alcun dettaglio sezione

**Obiettivo**
Verificare che: un cliente Gruppo Regionale senza regione valorizzata non può aprire alcun dettaglio sezione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-807 (nessuna sezione senza region valorizzata).
- Test automatico: `tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php` — `a gruppo regionale customer without a region cannot open any section detail`.
- File/componente applicativo rilevante: app/Filament/Pages/CaiSectionRegionalDetail.php.
- Test correlato: F8-44.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Gruppo Regionale SENZA `region` valorizzata (rimuoverla temporaneamente se necessario, punto 13).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Una qualunque sezione classificata.

**Stato iniziale**
Dataset UAT con un cliente Gruppo Regionale senza regione.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account cliente Gruppo Regionale senza regione | credenziali del cliente | Login riuscito |
| 2 | Tenta di aprire l'URL di dettaglio di una sezione qualunque | URL diretto | La richiesta risponde 403 Forbidden |

**Risultato finale atteso**
Un cliente Gruppo Regionale senza regione valorizzata non può aprire alcun dettaglio sezione

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-46 — Un cliente Sezione non può accedere alla pagina di dettaglio del Gruppo Regionale

**Obiettivo**
Verificare che: un cliente Sezione non può accedere alla pagina di dettaglio del Gruppo Regionale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-807 (pagina riservata ai clienti Gruppo Regionale).
- Test automatico: `tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php` — `a sezione customer cannot access the regional group detail page`.
- File/componente applicativo rilevante: app/Filament/Pages/CaiSectionRegionalDetail.php.
- Test correlato: F8-47.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Sezione realmente collegato a una `CaiSection`/`CaiSubsection` (`user_id`), individuato empiricamente sul dataset UAT (punto 13 di `00-istruzioni-generali.md`) — in assenza di un match reale, collegare temporaneamente un cliente Sezione di test e rimuovere il collegamento a fine collaudo.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Una sezione qualunque della propria regione.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con un account cliente Sezione | credenziali del cliente Sezione | Login riuscito |
| 2 | Tenta di aprire l'URL di dettaglio di una sezione qualunque | URL diretto | La richiesta risponde 403 Forbidden |

**Risultato finale atteso**
Un cliente Sezione non può accedere alla pagina di dettaglio del Gruppo Regionale

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-47 — Un cliente non-customer non può accedere alla pagina di dettaglio del Gruppo Regionale

**Obiettivo**
Verificare che: un cliente non-customer non può accedere alla pagina di dettaglio del Gruppo Regionale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-807 (pagina riservata ai clienti).
- Test automatico: `tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php` — `a non-customer cannot access the regional group detail page`.
- File/componente applicativo rilevante: app/Filament/Pages/CaiSectionRegionalDetail.php.
- Test correlato: F8-46.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Una sezione qualunque.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com | Login riuscito |
| 2 | Tenta di aprire l'URL di dettaglio di una sezione qualunque | URL diretto | La richiesta risponde 403 Forbidden |

**Risultato finale atteso**
Un cliente non-customer non può accedere alla pagina di dettaglio del Gruppo Regionale

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-48 — Aprire il dettaglio per un utente che non è una Sezione risulta non trovato (404)

**Obiettivo**
Verificare che: aprire il dettaglio per un utente che non è una Sezione risulta non trovato (404).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-807 (`{record}` risolto come utente cliente Sezione, 404 se non lo è).
- Test automatico: `tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php` — `opening the detail for a user that is not a sezione is not found`.
- File/componente applicativo rilevante: app/Filament/Pages/CaiSectionRegionalDetail.php.
- Test correlato: F8-43.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Gruppo Regionale (`customer_type = GruppoRegionale`, `region` valorizzata) con almeno una Sezione classificata nella stessa regione, individuato empiricamente sul dataset UAT (punto 13 di `00-istruzioni-generali.md`).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).
- Un utente qualunque che NON sia un cliente Sezione (es. un altro cliente Gruppo Regionale).

**Dati di test**
Id di un utente che non è un cliente Sezione.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account cliente Gruppo Regionale | credenziali del Gruppo Regionale | Login riuscito |
| 2 | Componi l'URL di dettaglio usando l'id di un utente che non è una Sezione | URL manipolato | La richiesta risponde 404 Not Found |

**Risultato finale atteso**
Aprire il dettaglio per un utente che non è una Sezione risulta non trovato (404)

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-49 — La pagina di dettaglio mostra lo stesso contenuto della dashboard del cliente Sezione, riusando lo stesso Infolist

**Obiettivo**
Verificare che: la pagina di dettaglio mostra lo stesso contenuto della dashboard del cliente Sezione, riusando lo stesso Infolist.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-807 (stesso contenuto della card cliente Sezione, nessuna duplicazione di markup/logica); design doc §7 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php` — `the detail page shows the same cai section data as the customer own dashboard, reusing the same infolist`.
- File/componente applicativo rilevante: resources/views/filament/pages/partials/cai-directory-detail.blade.php.
- Test correlato: F8-38, F8-43.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Gruppo Regionale (`customer_type = GruppoRegionale`, `region` valorizzata) con almeno una Sezione classificata nella stessa regione, individuato empiricamente sul dataset UAT (punto 13 di `00-istruzioni-generali.md`).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT importati dal datapack RUNTS-CAI (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT con una Sezione con dati CAI/RUNTS nella stessa regione del Gruppo Regionale.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account cliente Gruppo Regionale | credenziali del Gruppo Regionale | Login riuscito |
| 2 | Apri il dettaglio di una sezione con dati CAI/RUNTS della propria regione | click sulla riga | La pagina mostra contatti, dati RUNTS, bilanci, allegati e sottosezioni, come sulla dashboard del cliente Sezione (US-806) |

**Risultato finale atteso**
La pagina di dettaglio mostra lo stesso contenuto della dashboard del cliente Sezione, riusando lo stesso Infolist

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-50 — La pagina di dettaglio mostra uno stato vuoto esplicito per una sezione senza dati CAI collegati

**Obiettivo**
Verificare che: la pagina di dettaglio mostra uno stato vuoto esplicito per una sezione senza dati CAI collegati.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-807 (stato vuoto esplicito, coerente con US-806); design doc §7 (docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md).
- Test automatico: `tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php` — `the detail page shows an explicit empty state for a section without linked cai data`.
- File/componente applicativo rilevante: resources/views/filament/pages/partials/cai-directory-detail.blade.php.
- Test correlato: F8-41, F8-49.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Gruppo Regionale (`customer_type = GruppoRegionale`, `region` valorizzata) con almeno una Sezione classificata nella stessa regione, individuato empiricamente sul dataset UAT (punto 13 di `00-istruzioni-generali.md`).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Una sezione della propria regione senza alcun collegamento a dati CAI/RUNTS.

**Stato iniziale**
Dataset UAT con una Sezione priva di dati CAI/RUNTS nella regione del Gruppo Regionale.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account cliente Gruppo Regionale | credenziali del Gruppo Regionale | Login riuscito |
| 2 | Apri il dettaglio di una sezione senza dati CAI collegati | click sulla riga | La pagina mostra lo stato vuoto esplicito "Nessun dato CAI/RUNTS disponibile per questa sezione" |

**Risultato finale atteso**
La pagina di dettaglio mostra uno stato vuoto esplicito per una sezione senza dati CAI collegati

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F8-51 — La card "Sezioni del gruppo regionale" sulla dashboard cliente collega alla pagina di dettaglio sezione

**Obiettivo**
Verificare che: la card "Sezioni del gruppo regionale" sulla dashboard cliente collega alla pagina di dettaglio sezione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-807 (ogni riga della card diventa un link al dettaglio).
- Test automatico: `tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php` — `the regional group sections card on the customer dashboard links to the section detail page`.
- File/componente applicativo rilevante: app/Filament/Pages/CustomerDashboard.php.
- Test correlato: F8-43.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un cliente Gruppo Regionale (`customer_type = GruppoRegionale`, `region` valorizzata) con almeno una Sezione classificata nella stessa regione, individuato empiricamente sul dataset UAT (punto 13 di `00-istruzioni-generali.md`).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT importati dal datapack RUNTS-CAI (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT con un cliente Gruppo Regionale e una Sezione classificata nella stessa regione.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account cliente Gruppo Regionale | credenziali del Gruppo Regionale | Login riuscito |
| 2 | Individua la card "Sezioni del gruppo regionale" e verifica che ogni riga sia un link cliccabile al dettaglio | — | Ogni riga della card apre la pagina di dettaglio della sezione corrispondente |

**Risultato finale atteso**
La card "Sezioni del gruppo regionale" sulla dashboard cliente collega alla pagina di dettaglio sezione

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

## Checkpoint di fine fase — flusso end-to-end import, consultazione staff, dashboard cliente Sezione e Gruppo Regionale (US-808)

### F8-52 — Il flusso completo RUNTS-CAI funziona end-to-end: import, matching per email, consultazione staff, dashboard cliente Sezione e dettaglio scoped del cliente Gruppo Regionale

**Obiettivo**
Verificare che: il flusso completo RUNTS-CAI funziona end-to-end: import, matching per email, consultazione staff, dashboard cliente Sezione e dettaglio scoped del cliente Gruppo Regionale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 8 (scripts/ralph/prd.json), US-808 (flusso end-to-end completo di fase, design doc §8 "Test previsti").
- Test automatico: `tests/Feature/EndToEnd/Fase8CheckpointEndToEndTest.php` — `the full RUNTS-CAI flow works end-to-end: import, email matching, staff consultation, sezione dashboard and regional group scoped detail`.
- File/componente applicativo rilevante: tests/Feature/EndToEnd/Fase8CheckpointEndToEndTest.php.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun datapack reale richiesto: il test costruisce una fixture SQLite propria (import, matching, staff, dashboard cliente Sezione e Gruppo Regionale).

**Dati di test**
Fixture SQLite ridotta (2 sezioni, un ente collegato con bilancio e allegato) più 3 utenti cliente di test (Sezione con match, Sezione senza match, Gruppo Regionale della stessa regione).

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the full RUNTS-CAI flow works end-to-end: import, email matching, staff consultation, sezione dashboard and regional group scoped detail"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: il flusso completo RUNTS-CAI funziona end-to-end: import, matching per email, consultazione staff, dashboard cliente Sezione e dettaglio scoped del cliente Gruppo Regionale.

**Controlli negativi**
Il tentativo di accesso diretto del Gruppo Regionale alla sezione di un'altra regione, nello stesso test, risponde 403 (non solo assente dal link in UI).

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

