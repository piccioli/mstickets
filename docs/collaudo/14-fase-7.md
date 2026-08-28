# Fase 7 (Tipologia di cliente CAI) — Casi di test dettagliati

> Torna a [`README.md`](README.md) · Istruzioni generali: [`00-istruzioni-generali.md`](00-istruzioni-generali.md) · Matrice di tracciabilità: [`01-matrice-tracciabilita.md`](01-matrice-tracciabilita.md)

36 casi di test (F7-01 — F7-36) su 6 argomenti. Prima di eseguire un test, leggi le convenzioni comuni in `00-istruzioni-generali.md` (in particolare le sezioni 8 "Ambiente UAT", 9 "Credenziali" e 12 "Prerequisiti generali"). Gli argomenti sono raggruppati per user story del PRD (schema/cataloghi, stage ETL di classificazione, UI Admin di assegnazione, badge dashboard, card "Sezioni del gruppo regionale"), in ordine di priorità US-701 -> US-706, più un ultimo argomento dedicato al checkpoint di fine fase (US-706, F7-36). I casi con Modalità di esecuzione MANUALE UI sono pensati per un tester che opera realmente sull'ambiente UAT (credenziali/URL: punti 8-9 di `00-istruzioni-generali.md`); i casi AUTOMATICO sono verificati eseguendo la suite Pest indicata (ruolo Sviluppatore) — entrambe le categorie sono sempre appoggiate a un test automatico REALMENTE esistente e verificato da `php artisan collaudo:verify-manifest 7`.

## Schema e cataloghi CustomerType/Region — persistenza e cast (§14, US-701)

### F7-01 — Il catalogo CustomerType contiene esattamente i 4 tipi cliente CAI del PRD

**Obiettivo**
Verificare che: il catalogo CustomerType contiene esattamente i 4 tipi cliente CAI del PRD.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Unit/Domain/Identity/CustomerTypeTest.php` — `contains exactly the 4 tipi cliente CAI di PRD §14 (Fase 7)`.
- Test correlato: F7-02.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "contains exactly the 4 tipi cliente CAI di PRD §14 (Fase 7)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il catalogo CustomerType contiene esattamente i 4 tipi cliente CAI del PRD

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

### F7-02 — Il catalogo Region contiene esattamente le 20 regioni italiane ufficiali, con Trentino-Alto Adige unificato

**Obiettivo**
Verificare che: il catalogo Region contiene esattamente le 20 regioni italiane ufficiali, con Trentino-Alto Adige unificato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Unit/Domain/Identity/RegionTest.php` — `contains exactly le 20 regioni italiane ufficiali (Trentino-Alto Adige unificato)`.
- Test correlato: F7-01, F7-03.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "contains exactly le 20 regioni italiane ufficiali (Trentino-Alto Adige unificato)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il catalogo Region contiene esattamente le 20 regioni italiane ufficiali, con Trentino-Alto Adige unificato

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

### F7-03 — Ogni regione ha una label non vuota per la UI

**Obiettivo**
Verificare che: ogni regione ha una label non vuota per la UI.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Unit/Domain/Identity/RegionTest.php` — `every case has a non-empty label`.
- Test correlato: F7-02, F7-04.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "every case has a non-empty label"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Ogni regione ha una label non vuota per la UI

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

### F7-04 — Il metodo label() restituisce il nome italiano corretto per i casi con grafia particolare (es. Valle d'Aosta, Friuli-Venezia Giulia)

**Obiettivo**
Verificare che: il metodo label() restituisce il nome italiano corretto per i casi con grafia particolare (es. Valle d'Aosta, Friuli-Venezia Giulia).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Unit/Domain/Identity/RegionTest.php` — `label restituisce il nome italiano corretto per i casi con grafia particolare`.
- Test correlato: F7-03, F7-05.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "label restituisce il nome italiano corretto per i casi con grafia particolare"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il metodo label() restituisce il nome italiano corretto per i casi con grafia particolare (es. Valle d'Aosta, Friuli-Venezia Giulia)

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

### F7-05 — La tabella users ha le colonne additive customer_type/region introdotte da questa fase

**Obiettivo**
Verificare che: la tabella users ha le colonne additive customer_type/region introdotte da questa fase.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Domain/Identity/UsersTableTest.php` — `users table has the customer_type/region columns of Fase 7 (US-701)`.
- Test correlato: F7-04, F7-06.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "users table has the customer_type/region columns of Fase 7 \(US-701\)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
La tabella users ha le colonne additive customer_type/region introdotte da questa fase

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

### F7-06 — Un utente senza customer_type/region resta null senza errori

**Obiettivo**
Verificare che: un utente senza customer_type/region resta null senza errori.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Domain/Identity/UsersTableTest.php` — `a user without customer_type/region stays null without errors`.
- Test correlato: F7-05, F7-07.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a user without customer_type/region stays null without errors"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Un utente senza customer_type/region resta null senza errori

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

### F7-07 — customer_type/region sono castati al proprio enum backed sia in lettura sia in scrittura

**Obiettivo**
Verificare che: customer_type/region sono castati al proprio enum backed sia in lettura sia in scrittura.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Domain/Identity/UsersTableTest.php` — `customer_type/region are cast to their backed enum in both directions`.
- Test correlato: F7-06.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "customer_type/region are cast to their backed enum in both directions"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
customer_type/region sono castati al proprio enum backed sia in lettura sia in scrittura

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

## Stage ETL CustomerClassificationStage — inferenza automatica di tipo/regione dal nome (§14, US-702)

### F7-08 — Un nome con prefisso GR/GP classifica come Gruppo Regionale ed estrae la regione

**Obiettivo**
Verificare che: un nome con prefisso GR/GP classifica come Gruppo Regionale ed estrae la regione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Import/Stages/CustomerClassificationStageTest.php` — `GR/GP prefix classifies as GruppoRegionale and extracts the region`.
- Test correlato: F7-09.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "GR/GP prefix classifies as GruppoRegionale and extracts the region"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Un nome con prefisso GR/GP classifica come Gruppo Regionale ed estrae la regione

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

### F7-09 — Un nome con prefisso OTCO/SO classifica come Organo Tecnico Centrale/Struttura Operativa, sempre senza regione

**Obiettivo**
Verificare che: un nome con prefisso OTCO/SO classifica come Organo Tecnico Centrale/Struttura Operativa, sempre senza regione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Import/Stages/CustomerClassificationStageTest.php` — `OTCO/SO prefix classifies as OrganoTecnicoStrutturaOperativa with no region`.
- Test correlato: F7-08, F7-10.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "OTCO/SO prefix classifies as OrganoTecnicoStrutturaOperativa with no region"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Un nome con prefisso OTCO/SO classifica come Organo Tecnico Centrale/Struttura Operativa, sempre senza regione

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

### F7-10 — Il pattern OTCO/SO è riconosciuto anche con spazi intorno alla barra

**Obiettivo**
Verificare che: il pattern OTCO/SO è riconosciuto anche con spazi intorno alla barra.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Import/Stages/CustomerClassificationStageTest.php` — `OTCO / SO with spaces around the slash is also recognized`.
- Test correlato: F7-09, F7-11.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "OTCO / SO with spaces around the slash is also recognized"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il pattern OTCO/SO è riconosciuto anche con spazi intorno alla barra

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

### F7-11 — Un nome nel formato "nome | regione" classifica come Sezione ed estrae la regione, col o senza il prefisso C.A.I. SEZ.

**Obiettivo**
Verificare che: un nome nel formato "nome | regione" classifica come Sezione ed estrae la regione, col o senza il prefisso C.A.I. SEZ..

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Import/Stages/CustomerClassificationStageTest.php` — `a pipe-separated name classifies as Sezione and extracts the region, with or without the C.A.I. SEZ. prefix`.
- Test correlato: F7-10, F7-12.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a pipe-separated name classifies as Sezione and extracts the region, with or without the C.A.I. SEZ. prefix"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Un nome nel formato "nome | regione" classifica come Sezione ed estrae la regione, col o senza il prefisso C.A.I. SEZ.

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

### F7-12 — Una Sezione senza testo dopo il separatore "|" resta Sezione con regione null, mai Generico

**Obiettivo**
Verificare che: una Sezione senza testo dopo il separatore "|" resta Sezione con regione null, mai Generico.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Import/Stages/CustomerClassificationStageTest.php` — `a Sezione with nothing after the pipe stays Sezione with a null region, never Generico`.
- Test correlato: F7-11, F7-13.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a Sezione with nothing after the pipe stays Sezione with a null region, never Generico"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Una Sezione senza testo dopo il separatore "|" resta Sezione con regione null, mai Generico

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

### F7-13 — Un nome che non corrisponde a nessun pattern classifica come Cliente generico, senza regione

**Obiettivo**
Verificare che: un nome che non corrisponde a nessun pattern classifica come Cliente generico, senza regione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Import/Stages/CustomerClassificationStageTest.php` — `a name matching no pattern classifies as Generico with no region`.
- Test correlato: F7-12, F7-14.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a name matching no pattern classifies as Generico with no region"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Un nome che non corrisponde a nessun pattern classifica come Cliente generico, senza regione

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

### F7-14 — La normalizzazione regione gestisce le varianti di maiuscole, apostrofo e trattino del dump v1

**Obiettivo**
Verificare che: la normalizzazione regione gestisce le varianti di maiuscole, apostrofo e trattino del dump v1.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Import/Stages/CustomerClassificationStageTest.php` — `region normalization handles case, apostrophe and hyphen variants from the v1 dump`.
- Test correlato: F7-13, F7-15.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "region normalization handles case, apostrophe and hyphen variants from the v1 dump"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
La normalizzazione regione gestisce le varianti di maiuscole, apostrofo e trattino del dump v1

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

### F7-15 — Una regione non normalizzabile registra un warning e lascia region null, senza bloccare l'import con un'eccezione

**Obiettivo**
Verificare che: una regione non normalizzabile registra un warning e lascia region null, senza bloccare l'import con un'eccezione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Import/Stages/CustomerClassificationStageTest.php` — `an unnormalizable region logs a warning and leaves region null instead of throwing`.
- Test correlato: F7-14, F7-16.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "an unnormalizable region logs a warning and leaves region null instead of throwing"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Una regione non normalizzabile registra un warning e lascia region null, senza bloccare l'import con un'eccezione

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

### F7-16 — Un utente senza ruolo customer non viene mai toccato dallo stage

**Obiettivo**
Verificare che: un utente senza ruolo customer non viene mai toccato dallo stage.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Import/Stages/CustomerClassificationStageTest.php` — `a non-customer user is never touched`.
- Test correlato: F7-15, F7-17.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a non-customer user is never touched"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Un utente senza ruolo customer non viene mai toccato dallo stage

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

### F7-17 — Rieseguire lo stage sugli stessi dati è idempotente: la seconda corsa solo salta

**Obiettivo**
Verificare che: rieseguire lo stage sugli stessi dati è idempotente: la seconda corsa solo salta.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Import/Stages/CustomerClassificationStageTest.php` — `re-running the stage on the same data is idempotent: second run only skips`.
- Test correlato: F7-16, F7-18.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "re-running the stage on the same data is idempotent: second run only skips"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Rieseguire lo stage sugli stessi dati è idempotente: la seconda corsa solo salta

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

### F7-18 — La modalità --dry-run non persiste alcuna classificazione

**Obiettivo**
Verificare che: la modalità --dry-run non persiste alcuna classificazione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Import/Stages/CustomerClassificationStageTest.php` — `--dry-run does not persist any classification`.
- Test correlato: F7-17.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "--dry-run does not persist any classification"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
La modalità --dry-run non persiste alcuna classificazione

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

## UI Admin — assegnazione tipo cliente e regione (§14, US-703)

### F7-19 — I campi tipo cliente e regione sono nascosti quando nessun ruolo customer è selezionato nel form

**Obiettivo**
Verificare che: i campi tipo cliente e regione sono nascosti quando nessun ruolo customer è selezionato nel form.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php` — `customer_type and region are hidden when no customer role is selected`.
- Test correlato: F7-20.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Credenziali del ruolo Admin (punto 9 di `00-istruzioni-generali.md`): info@montagnaservizi.com (ruolo Admin).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Un utente esistente senza il ruolo customer assegnato.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri Gestione utenti (`/admin/users`) e la scheda di modifica di un utente senza il ruolo customer selezionato | — | I campi "Tipo cliente" e "Regione" nella sezione Ruoli non sono visibili |

**Risultato finale atteso**
I campi tipo cliente e regione sono nascosti quando nessun ruolo customer è selezionato nel form

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

### F7-20 — Il campo tipo cliente diventa visibile quando il ruolo customer viene selezionato nel form

**Obiettivo**
Verificare che: il campo tipo cliente diventa visibile quando il ruolo customer viene selezionato nel form.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php` — `customer_type becomes visible when the customer role is selected in the form`.
- Test correlato: F7-19, F7-21.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Credenziali del ruolo Admin (punto 9 di `00-istruzioni-generali.md`): info@montagnaservizi.com (ruolo Admin).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Un utente esistente, ruolo Customer non ancora selezionato.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la scheda di modifica di un utente e seleziona il ruolo "Customer" nella CheckboxList Ruoli | Ruolo: Customer | Il campo "Tipo cliente" diventa visibile |

**Risultato finale atteso**
Il campo tipo cliente diventa visibile quando il ruolo customer viene selezionato nel form

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

### F7-21 — Il campo regione diventa visibile solo quando il tipo cliente è Sezione o Gruppo Regionale

**Obiettivo**
Verificare che: il campo regione diventa visibile solo quando il tipo cliente è Sezione o Gruppo Regionale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php` — `region becomes visible only when customer_type is Sezione or GruppoRegionale`.
- Test correlato: F7-20, F7-22.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Credenziali del ruolo Admin (punto 9 di `00-istruzioni-generali.md`): info@montagnaservizi.com (ruolo Admin).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Un utente esistente con ruolo Customer selezionato nel form.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Con il ruolo Customer selezionato, imposta "Tipo cliente" = Sezione, poi = Gruppo Regionale, poi = Organo Tecnico Centrale/Struttura Operativa, poi = Cliente generico | Tipo cliente: Sezione / Gruppo Regionale / Organo Tecnico.../ Cliente generico | Il campo "Regione" è visibile solo per Sezione e Gruppo Regionale, nascosto per gli altri due |

**Risultato finale atteso**
Il campo regione diventa visibile solo quando il tipo cliente è Sezione o Gruppo Regionale

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

### F7-22 — Un admin con user.assign-roles può persistere tipo cliente e regione dal form di modifica

**Obiettivo**
Verificare che: un admin con user.assign-roles può persistere tipo cliente e regione dal form di modifica.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php` — `an admin with user.assign-roles can persist customer_type and region via the edit form`.
- Test correlato: F7-21, F7-23.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Admin

**Prerequisiti**
- Credenziali del ruolo Admin (punto 9 di `00-istruzioni-generali.md`): info@montagnaservizi.com (ruolo Admin).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Un utente di test, ruolo Customer.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Seleziona il ruolo Customer, imposta "Tipo cliente" = Gruppo Regionale e "Regione" = Lombardia, salva | Tipo cliente: Gruppo Regionale; Regione: Lombardia | Salvataggio riuscito, nessun errore di validazione |
| 3 | Riapri la stessa scheda utente | — | I valori "Gruppo Regionale"/"Lombardia" risultano persistiti e ricaricati correttamente |

**Risultato finale atteso**
Un admin con user.assign-roles può persistere tipo cliente e regione dal form di modifica

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

### F7-23 — La regione viene azzerata al salvataggio quando il tipo cliente non è più Sezione o Gruppo Regionale

**Obiettivo**
Verificare che: la regione viene azzerata al salvataggio quando il tipo cliente non è più Sezione o Gruppo Regionale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php` — `region is cleared when customer_type is not Sezione or GruppoRegionale on save`.
- Test correlato: F7-22, F7-24.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Credenziali del ruolo Admin (punto 9 di `00-istruzioni-generali.md`): info@montagnaservizi.com (ruolo Admin).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Un utente di test con customer_type = Sezione e region valorizzata.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri un utente già classificato Sezione con una regione valorizzata, cambia "Tipo cliente" in Cliente generico, salva | Tipo cliente: Cliente generico | Salvataggio riuscito |
| 3 | Riapri la scheda utente | — | Il campo Regione risulta azzerato (null), non più il valore precedente |

**Risultato finale atteso**
La regione viene azzerata al salvataggio quando il tipo cliente non è più Sezione o Gruppo Regionale

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

### F7-24 — Un admin senza user.assign-roles non vede né può modificare tipo cliente e regione

**Obiettivo**
Verificare che: un admin senza user.assign-roles non vede né può modificare tipo cliente e regione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php` — `an admin without user.assign-roles cannot see or modify customer_type and region`.
- Test correlato: F7-23, F7-25.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Developer

**Prerequisiti**
- Un account senza il permesso `user.assign-roles` (da preparare appositamente, non tra le credenziali standard: rimuovere/non assegnare il permesso a un utente di prova con accesso al pannello).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Un account di test privo del permesso user.assign-roles.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'account di prova indicato nei prerequisiti | account di prova senza il permesso richiesto | Login riuscito |
| 2 | Accedi con un account privo del permesso user.assign-roles e apri la scheda di modifica di un utente cliente | — | I campi "Tipo cliente" e "Regione" non sono presenti nel form, non solo nascosti |

**Risultato finale atteso**
Un admin senza user.assign-roles non vede né può modificare tipo cliente e regione

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

### F7-25 — La colonna tipo cliente (badge colorato) è disponibile nell'elenco utenti per vista rapida e filtro (verificata in browser durante US-703, vedi scripts/ralph/progress.txt)

**Obiettivo**
Verificare che: la colonna tipo cliente (badge colorato) è disponibile nell'elenco utenti per vista rapida e filtro (verificata in browser durante US-703, vedi scripts/ralph/progress.txt).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php`.
- Test correlato: F7-24.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Admin

**Prerequisiti**
- Credenziali del ruolo Admin (punto 9 di `00-istruzioni-generali.md`): info@montagnaservizi.com (ruolo Admin).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dataset UAT con utenti classificati nei 4 tipi (post-import, US-702).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri Gestione utenti (`/admin/users`) | — | La colonna "Tipo cliente" (badge colorato) è visibile in tabella per gli utenti classificati |
| 3 | Usa il filtro "Tipo cliente" della tabella per restringere l'elenco a un singolo tipo | Filtro: uno dei 4 tipi cliente | L'elenco mostra solo gli utenti con quel tipo |

**Risultato finale atteso**
La colonna tipo cliente (badge colorato) è disponibile nell'elenco utenti per vista rapida e filtro (verificata in browser durante US-703, vedi scripts/ralph/progress.txt)

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

## Badge tipo cliente sulla dashboard (§14, US-704)

### F7-26 — Il badge mostra l'etichetta corretta con la regione per un cliente Sezione

**Obiettivo**
Verificare che: il badge mostra l'etichetta corretta con la regione per un cliente Sezione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the customer type badge shows the correct label with region for a sezione customer`.
- Test correlato: F7-27.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali del ruolo Customer (punto 9 di `00-istruzioni-generali.md`): infosentieroitalia@cai.it (ruolo Customer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Un cliente reale classificato Sezione con regione valorizzata (dataset UAT).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente (`/admin`, redirect automatico dopo login) | — | Il badge di testa mostra "Sezione — <Regione>" con la regione del cliente autenticato |

**Risultato finale atteso**
Il badge mostra l'etichetta corretta con la regione per un cliente Sezione

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

### F7-27 — Il badge mostra solo il tipo per un cliente Sezione senza regione

**Obiettivo**
Verificare che: il badge mostra solo il tipo per un cliente Sezione senza regione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the customer type badge shows just the type for a sezione customer without a region`.
- Test correlato: F7-26, F7-28.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali del ruolo Customer (punto 9 di `00-istruzioni-generali.md`): infosentieroitalia@cai.it (ruolo Customer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Un cliente Sezione con region = null (dataset UAT, es. uno dei residui non normalizzati documentati in scripts/ralph/progress.txt US-702).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente di un utente Sezione senza regione valorizzata | — | Il badge mostra solo "Sezione", senza alcun suffisso di regione |

**Risultato finale atteso**
Il badge mostra solo il tipo per un cliente Sezione senza regione

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

### F7-28 — Il badge mostra l'etichetta corretta con la regione per un cliente Gruppo Regionale

**Obiettivo**
Verificare che: il badge mostra l'etichetta corretta con la regione per un cliente Gruppo Regionale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the customer type badge shows the correct label with region for a gruppo regionale customer`.
- Test correlato: F7-27, F7-29.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali del ruolo Customer (punto 9 di `00-istruzioni-generali.md`): infosentieroitalia@cai.it (ruolo Customer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Un cliente reale classificato Gruppo Regionale con regione valorizzata (dataset UAT).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente di un Gruppo Regionale | — | Il badge mostra "Gruppo Regionale — <Regione>" |

**Risultato finale atteso**
Il badge mostra l'etichetta corretta con la regione per un cliente Gruppo Regionale

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

### F7-29 — Il badge mostra solo il tipo per un cliente Organo Tecnico Centrale/Struttura Operativa (mai una regione)

**Obiettivo**
Verificare che: il badge mostra solo il tipo per un cliente Organo Tecnico Centrale/Struttura Operativa (mai una regione).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the customer type badge shows only the type for an organo tecnico/struttura operativa customer`.
- Test correlato: F7-28, F7-30.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali del ruolo Customer (punto 9 di `00-istruzioni-generali.md`): infosentieroitalia@cai.it (ruolo Customer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Un cliente reale classificato Organo Tecnico Centrale/Struttura Operativa (dataset UAT).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente di un Organo Tecnico Centrale/Struttura Operativa | — | Il badge mostra solo "Organo Tecnico Centrale / Struttura Operativa", mai una regione |

**Risultato finale atteso**
Il badge mostra solo il tipo per un cliente Organo Tecnico Centrale/Struttura Operativa (mai una regione)

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

### F7-30 — Il badge mostra solo il tipo per un cliente generico

**Obiettivo**
Verificare che: il badge mostra solo il tipo per un cliente generico.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the customer type badge shows only the type for a generico customer`.
- Test correlato: F7-29, F7-31.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali del ruolo Customer (punto 9 di `00-istruzioni-generali.md`): infosentieroitalia@cai.it (ruolo Customer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Un cliente reale classificato Cliente generico (dataset UAT).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente di un cliente generico | — | Il badge mostra solo "Cliente generico" |

**Risultato finale atteso**
Il badge mostra solo il tipo per un cliente generico

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

### F7-31 — Il badge è assente quando il cliente non ha ancora un customer_type classificato

**Obiettivo**
Verificare che: il badge è assente quando il cliente non ha ancora un customer_type classificato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the customer type badge is absent when the customer has no customer_type classified`.
- Test correlato: F7-30.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali del ruolo Customer (punto 9 di `00-istruzioni-generali.md`): infosentieroitalia@cai.it (ruolo Customer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Un cliente con customer_type = null (raro su dati reali post-ETL, eventualmente da creare ad-hoc per la verifica).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente di un utente customer senza customer_type classificato | — | Nessun badge tipo cliente viene mostrato in testa alla dashboard |

**Risultato finale atteso**
Il badge è assente quando il cliente non ha ancora un customer_type classificato

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

## Card "Sezioni del gruppo regionale" sulla dashboard (§14, US-705)

### F7-32 — La card elenca solo le sezioni della stessa regione del Gruppo Regionale, col relativo conteggio ticket aperti

**Obiettivo**
Verificare che: la card elenca solo le sezioni della stessa regione del Gruppo Regionale, col relativo conteggio ticket aperti.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the regional group sections card lists only sections in the same region, with their open ticket count`.
- Test correlato: F7-33.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali del ruolo Customer (punto 9 di `00-istruzioni-generali.md`): infosentieroitalia@cai.it (ruolo Customer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Un Gruppo Regionale reale con più Sezioni classificate nella propria regione (dataset UAT, es. GR Marche).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente di un Gruppo Regionale la cui regione ha già Sezioni classificate | — | La card "Sezioni del gruppo regionale" elenca solo le Sezioni della stessa regione, ciascuna col proprio conteggio ticket aperti; nessuna sezione di un'altra regione compare |

**Risultato finale atteso**
La card elenca solo le sezioni della stessa regione del Gruppo Regionale, col relativo conteggio ticket aperti

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

### F7-33 — La card mostra uno stato vuoto esplicito quando la regione non ha ancora nessuna sezione classificata

**Obiettivo**
Verificare che: la card mostra uno stato vuoto esplicito quando la regione non ha ancora nessuna sezione classificata.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the regional group sections card shows an explicit empty state when the region has no sections yet`.
- Test correlato: F7-32, F7-34.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali del ruolo Customer (punto 9 di `00-istruzioni-generali.md`): infosentieroitalia@cai.it (ruolo Customer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Un Gruppo Regionale la cui regione non ha Sezioni classificate (dataset UAT o utente di prova ad-hoc).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente di un Gruppo Regionale la cui regione non ha ancora nessuna Sezione classificata | — | La card mostra uno stato vuoto esplicito, mai un errore o una sezione silenziosa |

**Risultato finale atteso**
La card mostra uno stato vuoto esplicito quando la regione non ha ancora nessuna sezione classificata

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

### F7-34 — La card mostra uno stato vuoto esplicito quando il Gruppo Regionale non ha region valorizzata

**Obiettivo**
Verificare che: la card mostra uno stato vuoto esplicito quando il Gruppo Regionale non ha region valorizzata.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the regional group sections card shows an explicit empty state when the group has no region`.
- Test correlato: F7-33, F7-35.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali del ruolo Customer (punto 9 di `00-istruzioni-generali.md`): infosentieroitalia@cai.it (ruolo Customer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Un Gruppo Regionale con region = null (utente di prova ad-hoc, raro su dati reali post-ETL).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente di un Gruppo Regionale con region = null | — | La card mostra lo stesso stato vuoto esplicito del caso precedente |

**Risultato finale atteso**
La card mostra uno stato vuoto esplicito quando il Gruppo Regionale non ha region valorizzata

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

### F7-35 — La card è assente per i clienti Sezione, Organo Tecnico Centrale/Struttura Operativa e generico

**Obiettivo**
Verificare che: la card è assente per i clienti Sezione, Organo Tecnico Centrale/Struttura Operativa e generico.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the regional group sections card is absent for sezione, organo tecnico/struttura operativa, and generico customers`.
- Test correlato: F7-34.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali del ruolo Customer (punto 9 di `00-istruzioni-generali.md`): infosentieroitalia@cai.it (ruolo Customer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Tre clienti reali, uno per ciascuno dei tipi Sezione/Organo Tecnico.../Generico (dataset UAT).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente di un cliente Sezione, poi di un Organo Tecnico Centrale/Struttura Operativa, poi di un cliente generico | — | La card "Sezioni del gruppo regionale" non compare in nessuno dei tre casi |

**Risultato finale atteso**
La card è assente per i clienti Sezione, Organo Tecnico Centrale/Struttura Operativa e generico

**Controlli negativi**
Il comportamento opposto (vedi test correlati) non si presenta nella stessa sessione di verifica.

**Evidenze da acquisire**
- Screenshot della schermata che mostra il comportamento atteso.

**Criterio di superamento**

PASS: il comportamento osservato in UI corrisponde al risultato atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere eventuali dati di test creati, se non reali.

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

## Checkpoint di fine fase — import, classificazione, correzione admin e riflesso in dashboard (US-706)

### F7-36 — L'import classifica correttamente un utente per ciascuno dei 4 tipi cliente, un admin corregge manualmente il tipo di uno di essi, e la dashboard del cliente corretto riflette il nuovo tipo

**Obiettivo**
Verificare che: l'import classifica correttamente un utente per ciascuno dei 4 tipi cliente, un admin corregge manualmente il tipo di uno di essi, e la dashboard del cliente corretto riflette il nuovo tipo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 7, §14.
- Test automatico: `tests/Feature/EndToEnd/Fase7CheckpointEndToEndTest.php` — `import classifies one user of each customer type correctly, an admin corrects one manually, and the customer dashboard reflects the corrected type`.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dump v1/connessione `db_legacy` richiesta: il test costruisce i propri dati in memoria/DB di test.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "import classifies one user of each customer type correctly, an admin corrects one manually, and the customer dashboard reflects the corrected type"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
L'import classifica correttamente un utente per ciascuno dei 4 tipi cliente, un admin corregge manualmente il tipo di uno di essi, e la dashboard del cliente corretto riflette il nuovo tipo

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
