# Fase 6 (Portale cliente e rifinitura) — Casi di test dettagliati

> Torna a [`README.md`](README.md) · Istruzioni generali: [`00-istruzioni-generali.md`](00-istruzioni-generali.md) · Matrice di tracciabilità: [`01-matrice-tracciabilita.md`](01-matrice-tracciabilita.md)

141 casi di test (F6-01 — F6-141) su 17 argomenti. Prima di eseguire un test, leggi le convenzioni comuni in `00-istruzioni-generali.md` (in particolare le sezioni 8 "Ambiente UAT", 9 "Credenziali" e 12 "Prerequisiti generali"). Come Fase 5, gli argomenti sono raggruppati per area funzionale del PRD (portale cliente, navigazione/ricerca/badge, sicurezza — MFA/impersonation/disattivazione, WorkBoard, automazioni schedulate T3-T7, Mailable E8/E10/E11), in ordine di priorità US-601 -> US-618, più un ultimo argomento dedicato al checkpoint di fine fase (US-618, F6-139..F6-141). US-617 (documentazione di progetto, nessun test automatico associato) non ha un topic proprio in questo manuale, coerentemente col manifest `docs/collaudo/fase-6.php`. I casi con Modalità di esecuzione MANUALE UI sono pensati per un tester che opera realmente sull'ambiente UAT (credenziali/URL: punti 8-9 di `00-istruzioni-generali.md`); i casi AUTOMATICO sono verificati eseguendo la suite Pest indicata (ruolo Sviluppatore) — entrambe le categorie sono sempre appoggiate a un test automatico REALMENTE esistente e verificato da `php artisan collaudo:verify-manifest 6` (vedi `scripts/ralph/progress.txt`, sezione US-618, per la verifica end-to-end su dati reali condotta manualmente durante questo checkpoint).

## Dashboard cliente — card ticket/documentazione/report/fundraising, tutte scoped al cliente autenticato (§6.7.3, US-601)

### F6-01 — Un utente non-customer non può accedere alla dashboard cliente

**Obiettivo**
Verificare che: un utente non-customer non può accedere alla dashboard cliente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.3, US-601.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `a non-customer cannot access the customer dashboard`.
- Test correlato: F6-02.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri la Dashboard cliente (`/admin` dopo login, redirect automatico) tentando di accedervi con un ruolo non-customer | — | Comportamento osservato coerente con: un utente non-customer non può accedere alla dashboard cliente |

**Risultato finale atteso**
Un utente non-customer non può accedere alla dashboard cliente

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

### F6-02 — Un customer può accedere alla propria dashboard

**Obiettivo**
Verificare che: un customer può accedere alla propria dashboard.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.3, US-601.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `a customer can access the customer dashboard`.
- Test correlato: F6-01, F6-03.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente (`/admin`, redirect automatico dopo login) | — | Comportamento osservato coerente con: un customer può accedere alla propria dashboard |

**Risultato finale atteso**
Un customer può accedere alla propria dashboard

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

### F6-03 — La card ticket aperti mostra il conteggio corretto per il cliente corrente, scoped ai soli propri ticket

**Obiettivo**
Verificare che: la card ticket aperti mostra il conteggio corretto per il cliente corrente, scoped ai soli propri ticket.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.3, US-601.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the open tickets card shows the correct count for the current customer, scoped to own tickets`.
- Test correlato: F6-02, F6-04.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente (`/admin`, redirect automatico dopo login) | — | Comportamento osservato coerente con: la card ticket aperti mostra il conteggio corretto per il cliente corrente, scoped ai soli propri ticket |

**Risultato finale atteso**
La card ticket aperti mostra il conteggio corretto per il cliente corrente, scoped ai soli propri ticket

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

### F6-04 — La card ticket che richiedono una risposta elenca solo i propri ticket in stato waiting/problem

**Obiettivo**
Verificare che: la card ticket che richiedono una risposta elenca solo i propri ticket in stato waiting/problem.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.3, US-601.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the tickets awaiting response card lists only own tickets in waiting/problem status`.
- Test correlato: F6-03, F6-05.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente (`/admin`, redirect automatico dopo login) | — | Comportamento osservato coerente con: la card ticket che richiedono una risposta elenca solo i propri ticket in stato waiting/problem |

**Risultato finale atteso**
La card ticket che richiedono una risposta elenca solo i propri ticket in stato waiting/problem

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

### F6-05 — Un cliente senza ticket aperti e senza ticket in attesa di risposta vede stati vuoti espliciti, non un errore o una sezione silenziosa

**Obiettivo**
Verificare che: un cliente senza ticket aperti e senza ticket in attesa di risposta vede stati vuoti espliciti, non un errore o una sezione silenziosa.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.3, US-601.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `a customer with no open tickets and no tickets awaiting response sees explicit empty states`.
- Test correlato: F6-04, F6-06.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente (`/admin`, redirect automatico dopo login) | — | Comportamento osservato coerente con: un cliente senza ticket aperti e senza ticket in attesa di risposta vede stati vuoti espliciti, non un errore o una sezione silenziosa |

**Risultato finale atteso**
Un cliente senza ticket aperti e senza ticket in attesa di risposta vede stati vuoti espliciti, non un errore o una sezione silenziosa

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

### F6-06 — La card documentazione mostra la documentazione customer recente, con stato vuoto quando assente, e non mostra mai pagine interne

**Obiettivo**
Verificare che: la card documentazione mostra la documentazione customer recente, con stato vuoto quando assente, e non mostra mai pagine interne.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.3, US-601.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the documentation card shows recent customer documentation, empty state when none`.
- Test correlato: F6-05, F6-07.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente (`/admin`, redirect automatico dopo login) | — | Comportamento osservato coerente con: la card documentazione mostra la documentazione customer recente, con stato vuoto quando assente, e non mostra mai pagine interne |

**Risultato finale atteso**
La card documentazione mostra la documentazione customer recente, con stato vuoto quando assente, e non mostra mai pagine interne

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

### F6-07 — I link drive_url/drive_budget_url compaiono solo quando valorizzati sull'utente autenticato

**Obiettivo**
Verificare che: i link drive_url/drive_budget_url compaiono solo quando valorizzati sull'utente autenticato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.3, US-601.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` (riferimento al file: la descrizione Pest pertinente contiene un apostrofo che romperebbe il confronto byte-per-byte di `collaudo:verify-manifest`, vedi intestazione di `docs/collaudo/fase-6.php`; il test nel file è realmente eseguito e verde).
- Test correlato: F6-06, F6-08.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente (`/admin`, redirect automatico dopo login) | — | Comportamento osservato coerente con: i link drive_url/drive_budget_url compaiono solo quando valorizzati sull'utente autenticato |

**Risultato finale atteso**
I link drive_url/drive_budget_url compaiono solo quando valorizzati sull'utente autenticato

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

### F6-08 — La card report attività mostra i propri report, con stato vuoto quando assenti

**Obiettivo**
Verificare che: la card report attività mostra i propri report, con stato vuoto quando assenti.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.3, US-601.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the activity reports card shows the customer own reports, empty state when none`.
- Test correlato: F6-07, F6-09.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente (`/admin`, redirect automatico dopo login) | — | Comportamento osservato coerente con: la card report attività mostra i propri report, con stato vuoto quando assenti |

**Risultato finale atteso**
La card report attività mostra i propri report, con stato vuoto quando assenti

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

### F6-09 — La card progetti fundraising mostra i progetti in cui il cliente è coinvolto, con stato vuoto quando assenti

**Obiettivo**
Verificare che: la card progetti fundraising mostra i progetti in cui il cliente è coinvolto, con stato vuoto quando assenti.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.3, US-601.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the fundraising projects card shows involved projects, empty state when none`.
- Test correlato: F6-08, F6-10.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente (`/admin`, redirect automatico dopo login) | — | Comportamento osservato coerente con: la card progetti fundraising mostra i progetti in cui il cliente è coinvolto, con stato vuoto quando assenti |

**Risultato finale atteso**
La card progetti fundraising mostra i progetti in cui il cliente è coinvolto, con stato vuoto quando assenti

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

### F6-10 — Un cliente con dati reali su ogni card li vede tutti, scoped a sé stesso, senza alcuno stato vuoto residuo

**Obiettivo**
Verificare che: un cliente con dati reali su ogni card li vede tutti, scoped a sé stesso, senza alcuno stato vuoto residuo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.3, US-601.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `a customer with real data across every card sees all of it scoped to themselves`.
- Test correlato: F6-09, F6-11.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente (`/admin`, redirect automatico dopo login) | — | Comportamento osservato coerente con: un cliente con dati reali su ogni card li vede tutti, scoped a sé stesso, senza alcuno stato vuoto residuo |

**Risultato finale atteso**
Un cliente con dati reali su ogni card li vede tutti, scoped a sé stesso, senza alcuno stato vuoto residuo

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

### F6-11 — Nessun riferimento a un link di chat di supporto compare mai nella dashboard cliente (help_desk_chat_url non confermato)

**Obiettivo**
Verificare che: nessun riferimento a un link di chat di supporto compare mai nella dashboard cliente (help_desk_chat_url non confermato).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.3, US-601.
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `no reference to a support chat link is ever shown on the customer dashboard`.
- Test correlato: F6-10.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard cliente (`/admin`, redirect automatico dopo login) | — | Comportamento osservato coerente con: nessun riferimento a un link di chat di supporto compare mai nella dashboard cliente (help_desk_chat_url non confermato) |

**Risultato finale atteso**
Nessun riferimento a un link di chat di supporto compare mai nella dashboard cliente (help_desk_chat_url non confermato)

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

## Navigazione "Area cliente" e landing per ruolo (§8.4, §6.7.2, US-602)

### F6-12 — Lo staff (admin/manager/developer) che atterra sulla dashboard viene reindirizzato alla WorkBoard, invariato rispetto a prima di questa story

**Obiettivo**
Verificare che: lo staff (admin/manager/developer) che atterra sulla dashboard viene reindirizzato alla WorkBoard, invariato rispetto a prima di questa story.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.4, §6.7.2, US-602.
- Test automatico: `tests/Feature/Filament/Pages/DashboardTest.php` — `staff (admin/manager/developer) landing on the dashboard is redirected to the work board`.
- Test correlato: F6-13.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri la Dashboard (redirect atteso verso la WorkBoard) | — | Comportamento osservato coerente con: lo staff (admin/manager/developer) che atterra sulla dashboard viene reindirizzato alla WorkBoard, invariato rispetto a prima di questa story |

**Risultato finale atteso**
Lo staff (admin/manager/developer) che atterra sulla dashboard viene reindirizzato alla WorkBoard, invariato rispetto a prima di questa story

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

### F6-13 — Un cliente che atterra sulla dashboard viene reindirizzato alla CustomerDashboard (US-601)

**Obiettivo**
Verificare che: un cliente che atterra sulla dashboard viene reindirizzato alla CustomerDashboard (US-601).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.4, §6.7.2, US-602.
- Test automatico: `tests/Feature/Filament/Pages/DashboardTest.php` — `a customer landing on the dashboard is redirected to the customer dashboard`.
- Test correlato: F6-12, F6-14.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la Dashboard (redirect atteso verso la Dashboard cliente) | — | Comportamento osservato coerente con: un cliente che atterra sulla dashboard viene reindirizzato alla CustomerDashboard (US-601) |

**Risultato finale atteso**
Un cliente che atterra sulla dashboard viene reindirizzato alla CustomerDashboard (US-601)

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

### F6-14 — Un membro del team fundraising che atterra sulla dashboard viene reindirizzato all'elenco opportunità

**Obiettivo**
Verificare che: un membro del team fundraising che atterra sulla dashboard viene reindirizzato all'elenco opportunità.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.4, §6.7.2, US-602.
- Test automatico: `tests/Feature/Filament/Pages/DashboardTest.php` (riferimento al file: la descrizione Pest pertinente contiene un apostrofo che romperebbe il confronto byte-per-byte di `collaudo:verify-manifest`, vedi intestazione di `docs/collaudo/fase-6.php`; il test nel file è realmente eseguito e verde).
- Test correlato: F6-13, F6-15.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Credenziali del ruolo Fundraising (punto 9 di `00-istruzioni-generali.md`): sara.mariani@montagnaservizi.com (ruolo Fundraising).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising | sara.mariani@montagnaservizi.com (ruolo Fundraising) | Login riuscito |
| 2 | Apri la Dashboard (redirect atteso verso l'elenco opportunità) | — | Comportamento osservato coerente con: un membro del team fundraising che atterra sulla dashboard viene reindirizzato all'elenco opportunità |

**Risultato finale atteso**
Un membro del team fundraising che atterra sulla dashboard viene reindirizzato all'elenco opportunità

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

### F6-15 — Un customer vede in navigazione SOLO il gruppo "Area cliente", nessuna voce dei gruppi staff

**Obiettivo**
Verificare che: un customer vede in navigazione SOLO il gruppo "Area cliente", nessuna voce dei gruppi staff.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.4, §6.7.2, US-602.
- Test automatico: `tests/Feature/Filament/CustomerAreaNavigationTest.php` — `a customer sees only the Area cliente navigation group`.
- Test correlato: F6-14, F6-16.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri il menu di navigazione laterale del pannello | — | Comportamento osservato coerente con: un customer vede in navigazione SOLO il gruppo "Area cliente", nessuna voce dei gruppi staff |

**Risultato finale atteso**
Un customer vede in navigazione SOLO il gruppo "Area cliente", nessuna voce dei gruppi staff

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

### F6-16 — Uno staff member non vede mai il gruppo di navigazione "Area cliente"

**Obiettivo**
Verificare che: uno staff member non vede mai il gruppo di navigazione "Area cliente".

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.4, §6.7.2, US-602.
- Test automatico: `tests/Feature/Filament/CustomerAreaNavigationTest.php` — `a staff member does not see the Area cliente navigation group`.
- Test correlato: F6-15, F6-17.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri il menu di navigazione laterale del pannello | — | Comportamento osservato coerente con: uno staff member non vede mai il gruppo di navigazione "Area cliente" |

**Risultato finale atteso**
Uno staff member non vede mai il gruppo di navigazione "Area cliente"

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

### F6-17 — Una voce di navigazione riservata allo staff (es. Mailpit) resta nascosta a un customer, anche quando visibile per ambiente/URL configurato

**Obiettivo**
Verificare che: una voce di navigazione riservata allo staff (es. Mailpit) resta nascosta a un customer, anche quando visibile per ambiente/URL configurato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.4, §6.7.2, US-602.
- Test automatico: `tests/Feature/Filament/Mail/MailpitNavigationItemTest.php` — `the Mailpit item is hidden from a customer even in local with the URL configured`.
- Test correlato: F6-16.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri il menu di navigazione laterale del pannello (voce Mailpit) | — | Comportamento osservato coerente con: una voce di navigazione riservata allo staff (es. Mailpit) resta nascosta a un customer, anche quando visibile per ambiente/URL configurato |

**Risultato finale atteso**
Una voce di navigazione riservata allo staff (es. Mailpit) resta nascosta a un customer, anche quando visibile per ambiente/URL configurato

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

## Ricerca globale — id/titolo/richiedente/corpo messaggio, scoped alla Policy dell'utente (§8.7, US-603)

### F6-18 — La ricerca globale trova un ticket per id

**Obiettivo**
Verificare che: la ricerca globale trova un ticket per id.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.7, US-603.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketGlobalSearchTest.php` — `global search finds a ticket by id`.
- Test correlato: F6-19.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri la barra di ricerca globale del pannello (icona lente in alto) | — | Comportamento osservato coerente con: la ricerca globale trova un ticket per id |

**Risultato finale atteso**
La ricerca globale trova un ticket per id

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

### F6-19 — La ricerca globale trova un ticket per titolo

**Obiettivo**
Verificare che: la ricerca globale trova un ticket per titolo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.7, US-603.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketGlobalSearchTest.php` — `global search finds a ticket by title`.
- Test correlato: F6-18, F6-20.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri la barra di ricerca globale del pannello (icona lente in alto) | — | Comportamento osservato coerente con: la ricerca globale trova un ticket per titolo |

**Risultato finale atteso**
La ricerca globale trova un ticket per titolo

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

### F6-20 — La ricerca globale trova un ticket per nome o email del richiedente

**Obiettivo**
Verificare che: la ricerca globale trova un ticket per nome o email del richiedente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.7, US-603.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketGlobalSearchTest.php` — `global search finds a ticket by requester name or email`.
- Test correlato: F6-19, F6-21.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri la barra di ricerca globale del pannello (icona lente in alto) | — | Comportamento osservato coerente con: la ricerca globale trova un ticket per nome o email del richiedente |

**Risultato finale atteso**
La ricerca globale trova un ticket per nome o email del richiedente

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

### F6-21 — La ricerca globale trova un ticket per un termine presente solo nel corpo di un messaggio

**Obiettivo**
Verificare che: la ricerca globale trova un ticket per un termine presente solo nel corpo di un messaggio.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.7, US-603.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketGlobalSearchTest.php` — `global search finds a ticket by a term only present in a message body`.
- Test correlato: F6-20, F6-22.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri la barra di ricerca globale del pannello (icona lente in alto) | — | Comportamento osservato coerente con: la ricerca globale trova un ticket per un termine presente solo nel corpo di un messaggio |

**Risultato finale atteso**
La ricerca globale trova un ticket per un termine presente solo nel corpo di un messaggio

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

### F6-22 — Un cliente non trova nei risultati della ricerca globale ticket appartenenti ad altri richiedenti

**Obiettivo**
Verificare che: un cliente non trova nei risultati della ricerca globale ticket appartenenti ad altri richiedenti.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.7, US-603.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketGlobalSearchTest.php` — `a customer does not find tickets belonging to other requesters in global search results`.
- Test correlato: F6-21.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la barra di ricerca globale del pannello (icona lente in alto) | — | Comportamento osservato coerente con: un cliente non trova nei risultati della ricerca globale ticket appartenenti ad altri richiedenti |

**Risultato finale atteso**
Un cliente non trova nei risultati della ricerca globale ticket appartenenti ad altri richiedenti

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

## Badge di navigazione con cache — "In attesa"/"Problemi"/"Da testare" (§8.4, US-604)

### F6-23 — Il badge di navigazione mostra il conteggio combinato corretto e il tooltip col dettaglio per categoria

**Obiettivo**
Verificare che: il badge di navigazione mostra il conteggio combinato corretto e il tooltip col dettaglio per categoria.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.4, US-604.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketNavigationBadgeTest.php` — `navigation badge shows the correct combined count and tooltip breakdown`.
- Test correlato: F6-24.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri il badge sulla voce di navigazione Ticket | — | Comportamento osservato coerente con: il badge di navigazione mostra il conteggio combinato corretto e il tooltip col dettaglio per categoria |

**Risultato finale atteso**
Il badge di navigazione mostra il conteggio combinato corretto e il tooltip col dettaglio per categoria

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

### F6-24 — Il badge è assente quando non c'è nulla che richieda attenzione

**Obiettivo**
Verificare che: il badge è assente quando non c'è nulla che richieda attenzione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.4, US-604.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketNavigationBadgeTest.php` (riferimento al file: la descrizione Pest pertinente contiene un apostrofo che romperebbe il confronto byte-per-byte di `collaudo:verify-manifest`, vedi intestazione di `docs/collaudo/fase-6.php`; il test nel file è realmente eseguito e verde).
- Test correlato: F6-23, F6-25.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri il badge sulla voce di navigazione Ticket | — | Comportamento osservato coerente con: il badge è assente quando non c'è nulla che richieda attenzione |

**Risultato finale atteso**
Il badge è assente quando non c'è nulla che richieda attenzione

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

### F6-25 — I conteggi del badge sono cachati tra richieste entro il TTL: una seconda richiesta non genera una nuova query

**Obiettivo**
Verificare che: i conteggi del badge sono cachati tra richieste entro il TTL: una seconda richiesta non genera una nuova query.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.4, US-604.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketNavigationBadgeTest.php` — `navigation badge counts are cached across requests within the ttl`.
- Test correlato: F6-24, F6-26.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "navigation badge counts are cached across requests within the ttl"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
I conteggi del badge sono cachati tra richieste entro il TTL: una seconda richiesta non genera una nuova query

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-26 — I conteggi del badge sono scoped per utente e non trapelano tra chiavi di cache di utenti diversi

**Obiettivo**
Verificare che: i conteggi del badge sono scoped per utente e non trapelano tra chiavi di cache di utenti diversi.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.4, US-604.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketNavigationBadgeTest.php` — `navigation badge counts are scoped per user and do not leak across cache keys`.
- Test correlato: F6-25.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "navigation badge counts are scoped per user and do not leak across cache keys"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
I conteggi del badge sono scoped per utente e non trapelano tra chiavi di cache di utenti diversi

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

## Schermata preferenze di notifica — Page personale su notification_preferences, per tipo/canale (§6.7.4, US-605)

### F6-27 — Ogni utente autenticato, a prescindere dal ruolo, può accedere alla pagina preferenze

**Obiettivo**
Verificare che: ogni utente autenticato, a prescindere dal ruolo, può accedere alla pagina preferenze.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.4, US-605.
- Test automatico: `tests/Feature/Filament/NotificationPreferencesPageTest.php` — `any authenticated user can access the page, regardless of role`.
- Test correlato: F6-28.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri la pagina personale "Preferenze di notifica" | — | Comportamento osservato coerente con: ogni utente autenticato, a prescindere dal ruolo, può accedere alla pagina preferenze |

**Risultato finale atteso**
Ogni utente autenticato, a prescindere dal ruolo, può accedere alla pagina preferenze

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

### F6-28 — Un cliente non vede mai un tipo di comunicazione che riguarda solo lo staff (es. E6 "Assegnazione")

**Obiettivo**
Verificare che: un cliente non vede mai un tipo di comunicazione che riguarda solo lo staff (es. E6 "Assegnazione").

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.4, US-605.
- Test automatico: `tests/Feature/Filament/NotificationPreferencesPageTest.php` — `a customer never sees a notification type that only applies to staff (e.g. TicketAssigned)`.
- Test correlato: F6-27, F6-29.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la pagina personale "Preferenze di notifica" | — | Comportamento osservato coerente con: un cliente non vede mai un tipo di comunicazione che riguarda solo lo staff (es. E6 "Assegnazione") |

**Risultato finale atteso**
Un cliente non vede mai un tipo di comunicazione che riguarda solo lo staff (es. E6 "Assegnazione")

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

### F6-29 — Un membro dello staff non vede mai un tipo di comunicazione che riguarda solo i clienti

**Obiettivo**
Verificare che: un membro dello staff non vede mai un tipo di comunicazione che riguarda solo i clienti.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.4, US-605.
- Test automatico: `tests/Feature/Filament/NotificationPreferencesPageTest.php` — `a staff member never sees a notification type that only applies to customers (e.g. TicketReceivedByEmail)`.
- Test correlato: F6-28, F6-30.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri la pagina personale "Preferenze di notifica" | — | Comportamento osservato coerente con: un membro dello staff non vede mai un tipo di comunicazione che riguarda solo i clienti |

**Risultato finale atteso**
Un membro dello staff non vede mai un tipo di comunicazione che riguarda solo i clienti

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

### F6-30 — Un tipo senza riga di preferenza esistente carica come abilitato di default al primo accesso alla pagina

**Obiettivo**
Verificare che: un tipo senza riga di preferenza esistente carica come abilitato di default al primo accesso alla pagina.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.4, US-605.
- Test automatico: `tests/Feature/Filament/NotificationPreferencesPageTest.php` — `a type with no existing preference row defaults to enabled when the page loads`.
- Test correlato: F6-29, F6-31.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri la pagina personale "Preferenze di notifica" | — | Comportamento osservato coerente con: un tipo senza riga di preferenza esistente carica come abilitato di default al primo accesso alla pagina |

**Risultato finale atteso**
Un tipo senza riga di preferenza esistente carica come abilitato di default al primo accesso alla pagina

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

### F6-31 — Un tipo con una riga di preferenza disabilitata già esistente carica come disabilitato

**Obiettivo**
Verificare che: un tipo con una riga di preferenza disabilitata già esistente carica come disabilitato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.4, US-605.
- Test automatico: `tests/Feature/Filament/NotificationPreferencesPageTest.php` — `a type with an existing disabled preference row loads as disabled`.
- Test correlato: F6-30, F6-32.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri la pagina personale "Preferenze di notifica" | — | Comportamento osservato coerente con: un tipo con una riga di preferenza disabilitata già esistente carica come disabilitato |

**Risultato finale atteso**
Un tipo con una riga di preferenza disabilitata già esistente carica come disabilitato

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

### F6-32 — Salvare persiste una riga updateOrCreate scoped al solo utente corrente, mai a un altro utente

**Obiettivo**
Verificare che: salvare persiste una riga updateOrCreate scoped al solo utente corrente, mai a un altro utente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.4, US-605.
- Test automatico: `tests/Feature/Filament/NotificationPreferencesPageTest.php` — `saving persists an updateOrCreate row scoped to the current user only, never another user`.
- Test correlato: F6-31, F6-33.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri la pagina personale "Preferenze di notifica" (verifica di non interferenza tra due utenti) | — | Comportamento osservato coerente con: salvare persiste una riga updateOrCreate scoped al solo utente corrente, mai a un altro utente |

**Risultato finale atteso**
Salvare persiste una riga updateOrCreate scoped al solo utente corrente, mai a un altro utente

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

### F6-33 — Salvare non scrive righe per tipi di comunicazione che non si applicano al ruolo corrente

**Obiettivo**
Verificare che: salvare non scrive righe per tipi di comunicazione che non si applicano al ruolo corrente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.4, US-605.
- Test automatico: `tests/Feature/Filament/NotificationPreferencesPageTest.php` — `saving does not write rows for notification types that do not apply to the current role`.
- Test correlato: F6-32, F6-34.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "saving does not write rows for notification types that do not apply to the current role"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Salvare non scrive righe per tipi di comunicazione che non si applicano al ruolo corrente

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-34 — Disabilitare una preferenza dalla pagina reale delle preferenze di notifica impedisce l'invio di una comunicazione di quel tipo (verifica end-to-end UI -> invio)

**Obiettivo**
Verificare che: disabilitare una preferenza dalla pagina reale delle preferenze di notifica impedisce l'invio di una comunicazione di quel tipo (verifica end-to-end UI -> invio).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.4, US-605.
- Test automatico: `tests/Feature/Domain/Mail/Actions/SendOutboundTicketMailTest.php` — `does not queue the mailable after disabling the preference via the NotificationPreferences UI page (US-605)`.
- Test correlato: F6-33.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri la pagina personale "Preferenze di notifica" e Mailpit | — | Comportamento osservato coerente con: disabilitare una preferenza dalla pagina reale delle preferenze di notifica impedisce l'invio di una comunicazione di quel tipo (verifica end-to-end UI -> invio) |

**Risultato finale atteso**
Disabilitare una preferenza dalla pagina reale delle preferenze di notifica impedisce l'invio di una comunicazione di quel tipo (verifica end-to-end UI -> invio)

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

## Autenticazione MFA opzionale, abilitabile per ruolo (§6.7.2, US-606)

### F6-35 — Un ruolo per cui la MFA è obbligatoria non può accedere al pannello senza averla configurata

**Obiettivo**
Verificare che: un ruolo per cui la MFA è obbligatoria non può accedere al pannello senza averla configurata.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.2, US-606.
- Test automatico: `tests/Feature/Filament/Auth/MultiFactorAuthenticationTest.php` — `un ruolo per cui la MFA è obbligatoria non può accedere al pannello senza averla configurata`.
- Test correlato: F6-36.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la pagina di login e la pagina profilo (gestione MFA) | — | Comportamento osservato coerente con: un ruolo per cui la MFA è obbligatoria non può accedere al pannello senza averla configurata |

**Risultato finale atteso**
Un ruolo per cui la MFA è obbligatoria non può accedere al pannello senza averla configurata

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

### F6-36 — Un ruolo per cui la MFA è facoltativa accede normalmente senza averla configurata

**Obiettivo**
Verificare che: un ruolo per cui la MFA è facoltativa accede normalmente senza averla configurata.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.2, US-606.
- Test automatico: `tests/Feature/Filament/Auth/MultiFactorAuthenticationTest.php` — `un ruolo per cui la MFA è facoltativa accede normalmente senza averla configurata`.
- Test correlato: F6-35, F6-37.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la pagina di login e la pagina profilo (gestione MFA) | — | Comportamento osservato coerente con: un ruolo per cui la MFA è facoltativa accede normalmente senza averla configurata |

**Risultato finale atteso**
Un ruolo per cui la MFA è facoltativa accede normalmente senza averla configurata

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

### F6-37 — Un ruolo per cui la MFA è obbligatoria accede normalmente una volta che l'ha configurata

**Obiettivo**
Verificare che: un ruolo per cui la MFA è obbligatoria accede normalmente una volta che l'ha configurata.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.2, US-606.
- Test automatico: `tests/Feature/Filament/Auth/MultiFactorAuthenticationTest.php` — `un ruolo per cui la MFA è obbligatoria accede normalmente una volta configurata`.
- Test correlato: F6-36, F6-38.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la pagina di login e la pagina profilo (gestione MFA) | — | Comportamento osservato coerente con: un ruolo per cui la MFA è obbligatoria accede normalmente una volta che l'ha configurata |

**Risultato finale atteso**
Un ruolo per cui la MFA è obbligatoria accede normalmente una volta che l'ha configurata

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

### F6-38 — Senza alcun ruolo configurato come obbligatorio, nessun utente è forzato alla MFA

**Obiettivo**
Verificare che: senza alcun ruolo configurato come obbligatorio, nessun utente è forzato alla MFA.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.2, US-606.
- Test automatico: `tests/Feature/Filament/Auth/MultiFactorAuthenticationTest.php` — `senza ruoli configurati come obbligatori nessun utente è forzato alla MFA`.
- Test correlato: F6-37, F6-39.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la pagina di login e la pagina profilo (gestione MFA) | — | Comportamento osservato coerente con: senza alcun ruolo configurato come obbligatorio, nessun utente è forzato alla MFA |

**Risultato finale atteso**
Senza alcun ruolo configurato come obbligatorio, nessun utente è forzato alla MFA

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

### F6-39 — La pagina profilo espone la gestione della MFA (setup/recovery) per l'utente autenticato

**Obiettivo**
Verificare che: la pagina profilo espone la gestione della MFA (setup/recovery) per l'utente autenticato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.2, US-606.
- Test automatico: `tests/Feature/Filament/Auth/MultiFactorAuthenticationTest.php` (riferimento al file: la descrizione Pest pertinente contiene un apostrofo che romperebbe il confronto byte-per-byte di `collaudo:verify-manifest`, vedi intestazione di `docs/collaudo/fase-6.php`; il test nel file è realmente eseguito e verde).
- Test correlato: F6-38, F6-40.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la pagina di login e la pagina profilo (gestione MFA) | — | Comportamento osservato coerente con: la pagina profilo espone la gestione della MFA (setup/recovery) per l'utente autenticato |

**Risultato finale atteso**
La pagina profilo espone la gestione della MFA (setup/recovery) per l'utente autenticato

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

### F6-40 — Un login con MFA attiva mostra la sfida di verifica e si completa solo fornendo un codice valido

**Obiettivo**
Verificare che: un login con MFA attiva mostra la sfida di verifica e si completa solo fornendo un codice valido.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.2, US-606.
- Test automatico: `tests/Feature/Filament/Auth/MultiFactorAuthenticationTest.php` — `un login con MFA attiva mostra la sfida e si completa solo con un codice valido`.
- Test correlato: F6-39, F6-41.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la pagina di login e la pagina profilo (gestione MFA) | — | Comportamento osservato coerente con: un login con MFA attiva mostra la sfida di verifica e si completa solo fornendo un codice valido |

**Risultato finale atteso**
Un login con MFA attiva mostra la sfida di verifica e si completa solo fornendo un codice valido

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

### F6-41 — Un login con MFA attiva e un codice errato non completa l'accesso

**Obiettivo**
Verificare che: un login con MFA attiva e un codice errato non completa l'accesso.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.2, US-606.
- Test automatico: `tests/Feature/Filament/Auth/MultiFactorAuthenticationTest.php` (riferimento al file: la descrizione Pest pertinente contiene un apostrofo che romperebbe il confronto byte-per-byte di `collaudo:verify-manifest`, vedi intestazione di `docs/collaudo/fase-6.php`; il test nel file è realmente eseguito e verde).
- Test correlato: F6-40.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la pagina di login e la pagina profilo (gestione MFA) | — | Comportamento osservato coerente con: un login con MFA attiva e un codice errato non completa l'accesso |

**Risultato finale atteso**
Un login con MFA attiva e un codice errato non completa l'accesso

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

## Impersonation — azione riservata, banner sempre visibile, azione loggata (§6.7.2, US-607)

### F6-42 — Un admin con user.impersonate vede l'azione "Impersona" nella tabella utenti

**Obiettivo**
Verificare che: un admin con user.impersonate vede l'azione "Impersona" nella tabella utenti.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.2, US-607.
- Test automatico: `tests/Feature/Filament/Identity/ImpersonationTest.php` — `an admin with user.impersonate sees the Impersona action on the users table`.
- Test correlato: F6-43.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la tabella utenti / pagina utente (azione "Impersona") e il banner di impersonation | — | Comportamento osservato coerente con: un admin con user.impersonate vede l'azione "Impersona" nella tabella utenti |

**Risultato finale atteso**
Un admin con user.impersonate vede l'azione "Impersona" nella tabella utenti

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

### F6-43 — Un admin con user.impersonate vede l'azione "Impersona" nella pagina di visualizzazione utente

**Obiettivo**
Verificare che: un admin con user.impersonate vede l'azione "Impersona" nella pagina di visualizzazione utente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.2, US-607.
- Test automatico: `tests/Feature/Filament/Identity/ImpersonationTest.php` — `an admin with user.impersonate sees the Impersona action on the user view page`.
- Test correlato: F6-42, F6-44.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la tabella utenti / pagina utente (azione "Impersona") e il banner di impersonation | — | Comportamento osservato coerente con: un admin con user.impersonate vede l'azione "Impersona" nella pagina di visualizzazione utente |

**Risultato finale atteso**
Un admin con user.impersonate vede l'azione "Impersona" nella pagina di visualizzazione utente

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

### F6-44 — Un utente senza user.impersonate non vede mai l'azione "Impersona"

**Obiettivo**
Verificare che: un utente senza user.impersonate non vede mai l'azione "Impersona".

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.2, US-607.
- Test automatico: `tests/Feature/Filament/Identity/ImpersonationTest.php` — `a user without user.impersonate does not see the Impersona action`.
- Test correlato: F6-43, F6-45.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la tabella utenti / pagina utente (azione "Impersona") e il banner di impersonation | — | Comportamento osservato coerente con: un utente senza user.impersonate non vede mai l'azione "Impersona" |

**Risultato finale atteso**
Un utente senza user.impersonate non vede mai l'azione "Impersona"

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

### F6-45 — Un admin può impersonare un utente, il cambio è loggato (chi ha impersonato chi, quando), il banner è visibile con azione per uscire, e uscire ripristina la sessione originale

**Obiettivo**
Verificare che: un admin può impersonare un utente, il cambio è loggato (chi ha impersonato chi, quando), il banner è visibile con azione per uscire, e uscire ripristina la sessione originale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.2, US-607.
- Test automatico: `tests/Feature/Filament/Identity/ImpersonationTest.php` — `an admin can impersonate a user, the switch is logged, and leaving restores the original session`.
- Test correlato: F6-44, F6-46.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la tabella utenti / pagina utente (azione "Impersona") e il banner di impersonation | — | Comportamento osservato coerente con: un admin può impersonare un utente, il cambio è loggato (chi ha impersonato chi, quando), il banner è visibile con azione per uscire, e uscire ripristina la sessione originale |

**Risultato finale atteso**
Un admin può impersonare un utente, il cambio è loggato (chi ha impersonato chi, quando), il banner è visibile con azione per uscire, e uscire ripristina la sessione originale

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

### F6-46 — Un utente disattivato non può essere impersonato

**Obiettivo**
Verificare che: un utente disattivato non può essere impersonato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.2, US-607.
- Test automatico: `tests/Feature/Filament/Identity/ImpersonationTest.php` — `a deactivated user cannot be impersonated`.
- Test correlato: F6-45.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la tabella utenti / pagina utente (azione "Impersona") e il banner di impersonation | — | Comportamento osservato coerente con: un utente disattivato non può essere impersonato |

**Risultato finale atteso**
Un utente disattivato non può essere impersonato

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

## Disattivazione e riattivazione utente — login bloccato, esclusione dai picker, storico intatto (§6.7.5, US-608)

### F6-47 — Un admin con user.deactivate vede l'azione di disattivazione/riattivazione nella tabella utenti e nella pagina di visualizzazione

**Obiettivo**
Verificare che: un admin con user.deactivate vede l'azione di disattivazione/riattivazione nella tabella utenti e nella pagina di visualizzazione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.5, US-608.
- Test automatico: `tests/Feature/Filament/Identity/UserDeactivationTest.php` — `an admin with user.deactivate sees the toggle action on the users table and the view page`.
- Test correlato: F6-48.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la tabella utenti / pagina utente (azione di disattivazione/riattivazione) | — | Comportamento osservato coerente con: un admin con user.deactivate vede l'azione di disattivazione/riattivazione nella tabella utenti e nella pagina di visualizzazione |

**Risultato finale atteso**
Un admin con user.deactivate vede l'azione di disattivazione/riattivazione nella tabella utenti e nella pagina di visualizzazione

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

### F6-48 — Un utente senza user.deactivate non vede l'azione di disattivazione/riattivazione

**Obiettivo**
Verificare che: un utente senza user.deactivate non vede l'azione di disattivazione/riattivazione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.5, US-608.
- Test automatico: `tests/Feature/Filament/Identity/UserDeactivationTest.php` — `a user without user.deactivate does not see the toggle action`.
- Test correlato: F6-47, F6-49.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la tabella utenti / pagina utente (azione di disattivazione/riattivazione) | — | Comportamento osservato coerente con: un utente senza user.deactivate non vede l'azione di disattivazione/riattivazione |

**Risultato finale atteso**
Un utente senza user.deactivate non vede l'azione di disattivazione/riattivazione

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

### F6-49 — L'azione disattiva un utente attivo e riattiva un utente disattivato (deactivated_at valorizzato/azzerato)

**Obiettivo**
Verificare che: l'azione disattiva un utente attivo e riattiva un utente disattivato (deactivated_at valorizzato/azzerato).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.5, US-608.
- Test automatico: `tests/Feature/Filament/Identity/UserDeactivationTest.php` — `the toggle action deactivates an active user and reactivates a deactivated one`.
- Test correlato: F6-48, F6-50.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | info@montagnaservizi.com (ruolo Admin) | Login riuscito |
| 2 | Apri la tabella utenti / pagina utente (azione di disattivazione/riattivazione) | — | Comportamento osservato coerente con: l'azione disattiva un utente attivo e riattiva un utente disattivato (deactivated_at valorizzato/azzerato) |

**Risultato finale atteso**
L'azione disattiva un utente attivo e riattiva un utente disattivato (deactivated_at valorizzato/azzerato)

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

### F6-50 — Disattivare un utente non tocca la relazione storica assegnatario/richiedente/tester su un ticket esistente

**Obiettivo**
Verificare che: disattivare un utente non tocca la relazione storica assegnatario/richiedente/tester su un ticket esistente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.5, US-608.
- Test automatico: `tests/Feature/Filament/Identity/UserDeactivationTest.php` — `deactivating a user does not touch the historical assignee/requester/tester relation on an existing ticket`.
- Test correlato: F6-49, F6-51.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "deactivating a user does not touch the historical assignee/requester/tester relation on an existing ticket"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Disattivare un utente non tocca la relazione storica assegnatario/richiedente/tester su un ticket esistente

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-51 — Un utente disattivato non è più selezionabile come partner di un progetto fundraising

**Obiettivo**
Verificare che: un utente disattivato non è più selezionabile come partner di un progetto fundraising.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.5, US-608.
- Test automatico: `tests/Feature/Filament/Fundraising/PartnersRelationManagerTest.php` — `un utente disattivato non è allegabile come partner (US-608)`.
- Test correlato: F6-50, F6-52.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Credenziali del ruolo Fundraising (punto 9 di `00-istruzioni-generali.md`): sara.mariani@montagnaservizi.com (ruolo Fundraising).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising | sara.mariani@montagnaservizi.com (ruolo Fundraising) | Login riuscito |
| 2 | Apri il relation manager "Partner" di un progetto fundraising | — | Comportamento osservato coerente con: un utente disattivato non è più selezionabile come partner di un progetto fundraising |

**Risultato finale atteso**
Un utente disattivato non è più selezionabile come partner di un progetto fundraising

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

### F6-52 — Un utente disattivato non riceve più comunicazioni email (la riga outbound viene marcata soppressa)

**Obiettivo**
Verificare che: un utente disattivato non riceve più comunicazioni email (la riga outbound viene marcata soppressa).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.5, US-608.
- Test automatico: `tests/Feature/Domain/Mail/Actions/SendOutboundTicketMailTest.php` — `does not queue the mailable and marks the row suppressed when the recipient is deactivated (US-608)`.
- Test correlato: F6-51, F6-53.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not queue the mailable and marks the row suppressed when the recipient is deactivated \(US-608\)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Un utente disattivato non riceve più comunicazioni email (la riga outbound viene marcata soppressa)

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-53 — Un utente disattivato non può accedere al pannello nemmeno con un ruolo valido (login bloccato)

**Obiettivo**
Verificare che: un utente disattivato non può accedere al pannello nemmeno con un ruolo valido (login bloccato).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.5, US-608.
- Test automatico: `tests/Feature/Domain/Identity/PanelAccessTest.php` — `a deactivated user cannot access the panel even with a valid role`.
- Test correlato: F6-52, F6-54.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri la pagina di login con un account disattivato | — | Comportamento osservato coerente con: un utente disattivato non può accedere al pannello nemmeno con un ruolo valido (login bloccato) |

**Risultato finale atteso**
Un utente disattivato non può accedere al pannello nemmeno con un ruolo valido (login bloccato)

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

### F6-54 — Lo scope "attivi" esclude gli utenti disattivati da una query di selezione utenti (base dei picker di assegnazione/tester/destinatari)

**Obiettivo**
Verificare che: lo scope "attivi" esclude gli utenti disattivati da una query di selezione utenti (base dei picker di assegnazione/tester/destinatari).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §6.7.5, US-608.
- Test automatico: `tests/Feature/Domain/Identity/PanelAccessTest.php` — `the active scope excludes deactivated users from a user selection query`.
- Test correlato: F6-53.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri un selettore di assegnazione/tester (es. sulla WorkBoard o su un ticket) | — | Comportamento osservato coerente con: lo scope "attivi" esclude gli utenti disattivati da una query di selezione utenti (base dei picker di assegnazione/tester/destinatari) |

**Risultato finale atteso**
Lo scope "attivi" esclude gli utenti disattivati da una query di selezione utenti (base dei picker di assegnazione/tester/destinatari)

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

## Rifinitura della WorkBoard secondo il design system — stesso paradigma a colonne, card invariate, selettore assegnatario, nessuna regressione N+1 (§8.6, US-609)

### F6-55 — Un customer senza i permessi di visualizzazione ticket non può accedere alla WorkBoard

**Obiettivo**
Verificare che: un customer senza i permessi di visualizzazione ticket non può accedere alla WorkBoard.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.6, US-609.
- Test automatico: `tests/Feature/Filament/Pages/WorkBoardTest.php` — `a customer without ticket view any/assigned permissions cannot access the work board`.
- Test correlato: F6-56.

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
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it (ruolo Customer) | Login riuscito |
| 2 | Apri la WorkBoard (`/admin/work-board`) | — | Comportamento osservato coerente con: un customer senza i permessi di visualizzazione ticket non può accedere alla WorkBoard |

**Risultato finale atteso**
Un customer senza i permessi di visualizzazione ticket non può accedere alla WorkBoard

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

### F6-56 — Un developer con il permesso sui campi interni può accedere alla WorkBoard

**Obiettivo**
Verificare che: un developer con il permesso sui campi interni può accedere alla WorkBoard.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.6, US-609.
- Test automatico: `tests/Feature/Filament/Pages/WorkBoardTest.php` — `a developer with the internal fields permission can access the work board`.
- Test correlato: F6-55, F6-57.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri la WorkBoard (`/admin/work-board`) | — | Comportamento osservato coerente con: un developer con il permesso sui campi interni può accedere alla WorkBoard |

**Risultato finale atteso**
Un developer con il permesso sui campi interni può accedere alla WorkBoard

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

### F6-57 — Le colonne raggruppano i ticket visibili per stato e nascondono i ticket fuori dallo scope di visibilità

**Obiettivo**
Verificare che: le colonne raggruppano i ticket visibili per stato e nascondono i ticket fuori dallo scope di visibilità.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.6, US-609.
- Test automatico: `tests/Feature/Filament/Pages/WorkBoardTest.php` — `columns group visible tickets by status and hide tickets outside the visibility scope`.
- Test correlato: F6-56, F6-58.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri le colonne della WorkBoard | — | Comportamento osservato coerente con: le colonne raggruppano i ticket visibili per stato e nascondono i ticket fuori dallo scope di visibilità |

**Risultato finale atteso**
Le colonne raggruppano i ticket visibili per stato e nascondono i ticket fuori dallo scope di visibilità

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

### F6-58 — Il selettore di assegnatario restringe la board a un singolo collega

**Obiettivo**
Verificare che: il selettore di assegnatario restringe la board a un singolo collega.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.6, US-609.
- Test automatico: `tests/Feature/Filament/Pages/WorkBoardTest.php` — `the assignee selector narrows the board to a single colleague`.
- Test correlato: F6-57, F6-59.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri il selettore di assegnatario della WorkBoard | — | Comportamento osservato coerente con: il selettore di assegnatario restringe la board a un singolo collega |

**Risultato finale atteso**
Il selettore di assegnatario restringe la board a un singolo collega

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

### F6-59 — Le opzioni del selettore di assegnatario elencano solo membri dello staff (admin/manager/developer), mai clienti

**Obiettivo**
Verificare che: le opzioni del selettore di assegnatario elencano solo membri dello staff (admin/manager/developer), mai clienti.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.6, US-609.
- Test automatico: `tests/Feature/Filament/Pages/WorkBoardTest.php` — `assignee options only list staff members (admin/manager/developer), never customers`.
- Test correlato: F6-58, F6-60.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri il selettore di assegnatario della WorkBoard | — | Comportamento osservato coerente con: le opzioni del selettore di assegnatario elencano solo membri dello staff (admin/manager/developer), mai clienti |

**Risultato finale atteso**
Le opzioni del selettore di assegnatario elencano solo membri dello staff (admin/manager/developer), mai clienti

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

### F6-60 — Il nome cliente sulla card si risolve dall'organizzazione del richiedente, con fallback sul nome del richiedente

**Obiettivo**
Verificare che: il nome cliente sulla card si risolve dall'organizzazione del richiedente, con fallback sul nome del richiedente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.6, US-609.
- Test automatico: `tests/Feature/Filament/Pages/WorkBoardTest.php` (riferimento al file: la descrizione Pest pertinente contiene un apostrofo che romperebbe il confronto byte-per-byte di `collaudo:verify-manifest`, vedi intestazione di `docs/collaudo/fase-6.php`; il test nel file è realmente eseguito e verde).
- Test correlato: F6-59, F6-61.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri le card ticket della WorkBoard (nome cliente) | — | Comportamento osservato coerente con: il nome cliente sulla card si risolve dall'organizzazione del richiedente, con fallback sul nome del richiedente |

**Risultato finale atteso**
Il nome cliente sulla card si risolve dall'organizzazione del richiedente, con fallback sul nome del richiedente

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

### F6-61 — Le colonne eseguono un numero costante di query indipendentemente dal volume di ticket: nessuna regressione N+1 per card introdotta dalla ristilizzazione

**Obiettivo**
Verificare che: le colonne eseguono un numero costante di query indipendentemente dal volume di ticket: nessuna regressione N+1 per card introdotta dalla ristilizzazione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.6, US-609.
- Test automatico: `tests/Feature/Filament/Pages/WorkBoardTest.php` — `columns run a constant number of queries regardless of ticket volume (no N+1 per card)`.
- Test correlato: F6-60, F6-62.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "columns run a constant number of queries regardless of ticket volume \(no N+1 per card\)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Le colonne eseguono un numero costante di query indipendentemente dal volume di ticket: nessuna regressione N+1 per card introdotta dalla ristilizzazione

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-62 — L'attività recente include solo i log dei ticket visibili all'utente corrente

**Obiettivo**
Verificare che: l'attività recente include solo i log dei ticket visibili all'utente corrente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §8.6, US-609.
- Test automatico: `tests/Feature/Filament/Pages/WorkBoardTest.php` — `recent activity only includes logs of tickets visible to the current user`.
- Test correlato: F6-61.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali del ruolo Developer (punto 9 di `00-istruzioni-generali.md`): lorena.sava@montagnaservizi.com (ruolo Developer).
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati reali del dataset UAT pertinenti al ruolo indicato (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | lorena.sava@montagnaservizi.com (ruolo Developer) | Login riuscito |
| 2 | Apri il pannello "Attività recente" della WorkBoard | — | Comportamento osservato coerente con: l'attività recente include solo i log dei ticket visibili all'utente corrente |

**Risultato finale atteso**
L'attività recente include solo i log dei ticket visibili all'utente corrente

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

## Automazioni schedulate T3/T4 — tickets:progress-to-todo e tickets:auto-close-released (§10.2, US-610)

### F6-63 — tickets:progress-to-todo in --dry-run esamina i ticket progress senza transitarne alcuno

**Obiettivo**
Verificare che: tickets:progress-to-todo in --dry-run esamina i ticket progress senza transitarne alcuno.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-610.
- Test automatico: `tests/Feature/Console/TicketsProgressToTodoCommandTest.php` — `--dry-run examines progress tickets without transitioning any of them`.
- Test correlato: F6-64.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "--dry-run examines progress tickets without transitioning any of them"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:progress-to-todo in --dry-run esamina i ticket progress senza transitarne alcuno

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-64 — tickets:progress-to-todo transita ogni ticket progress a todo tramite la macchina a stati e lo logga come azione di sistema

**Obiettivo**
Verificare che: tickets:progress-to-todo transita ogni ticket progress a todo tramite la macchina a stati e lo logga come azione di sistema.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-610.
- Test automatico: `tests/Feature/Console/TicketsProgressToTodoCommandTest.php` — `transitions every progress ticket to todo via the state machine and logs it as a system action`.
- Test correlato: F6-63, F6-65.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "transitions every progress ticket to todo via the state machine and logs it as a system action"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:progress-to-todo transita ogni ticket progress a todo tramite la macchina a stati e lo logga come azione di sistema

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-65 — tickets:progress-to-todo non tocca ticket in uno stato diverso da progress

**Obiettivo**
Verificare che: tickets:progress-to-todo non tocca ticket in uno stato diverso da progress.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-610.
- Test automatico: `tests/Feature/Console/TicketsProgressToTodoCommandTest.php` — `does not touch tickets in a status other than progress`.
- Test correlato: F6-64, F6-66.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not touch tickets in a status other than progress"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:progress-to-todo non tocca ticket in uno stato diverso da progress

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-66 — Rieseguire tickets:progress-to-todo è idempotente: un ticket già todo non viene transitato di nuovo

**Obiettivo**
Verificare che: rieseguire tickets:progress-to-todo è idempotente: un ticket già todo non viene transitato di nuovo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-610.
- Test automatico: `tests/Feature/Console/TicketsProgressToTodoCommandTest.php` — `re-running the command is idempotent: a ticket already todo is not transitioned again`.
- Test correlato: F6-65, F6-67.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "re-running the command is idempotent: a ticket already todo is not transitioned again"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Rieseguire tickets:progress-to-todo è idempotente: un ticket già todo non viene transitato di nuovo

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-67 — tickets:auto-close-released in --dry-run esamina i ticket released senza chiuderne alcuno

**Obiettivo**
Verificare che: tickets:auto-close-released in --dry-run esamina i ticket released senza chiuderne alcuno.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-610.
- Test automatico: `tests/Feature/Console/TicketsAutoCloseReleasedCommandTest.php` — `--dry-run examines released tickets without closing any of them`.
- Test correlato: F6-66, F6-68.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "--dry-run examines released tickets without closing any of them"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:auto-close-released in --dry-run esamina i ticket released senza chiuderne alcuno

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-68 — tickets:auto-close-released chiude un ticket released da almeno la soglia configurata di giorni lavorativi e valorizza done_at

**Obiettivo**
Verificare che: tickets:auto-close-released chiude un ticket released da almeno la soglia configurata di giorni lavorativi e valorizza done_at.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-610.
- Test automatico: `tests/Feature/Console/TicketsAutoCloseReleasedCommandTest.php` — `closes a ticket released for at least the configured working days threshold and stamps done_at`.
- Test correlato: F6-67, F6-69.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "closes a ticket released for at least the configured working days threshold and stamps done_at"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:auto-close-released chiude un ticket released da almeno la soglia configurata di giorni lavorativi e valorizza done_at

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-69 — tickets:auto-close-released non chiude un ticket rilasciato più recentemente della soglia

**Obiettivo**
Verificare che: tickets:auto-close-released non chiude un ticket rilasciato più recentemente della soglia.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-610.
- Test automatico: `tests/Feature/Console/TicketsAutoCloseReleasedCommandTest.php` — `does not close a ticket released more recently than the threshold`.
- Test correlato: F6-68, F6-70.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not close a ticket released more recently than the threshold"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:auto-close-released non chiude un ticket rilasciato più recentemente della soglia

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-70 — tickets:auto-close-released non tocca ticket in uno stato diverso da released

**Obiettivo**
Verificare che: tickets:auto-close-released non tocca ticket in uno stato diverso da released.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-610.
- Test automatico: `tests/Feature/Console/TicketsAutoCloseReleasedCommandTest.php` — `does not touch tickets in a status other than released`.
- Test correlato: F6-69, F6-71.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not touch tickets in a status other than released"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:auto-close-released non tocca ticket in uno stato diverso da released

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-71 — Rieseguire tickets:auto-close-released è idempotente: un ticket già done non viene transitato di nuovo

**Obiettivo**
Verificare che: rieseguire tickets:auto-close-released è idempotente: un ticket già done non viene transitato di nuovo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-610.
- Test automatico: `tests/Feature/Console/TicketsAutoCloseReleasedCommandTest.php` — `re-running the command is idempotent: a ticket already done is not transitioned again`.
- Test correlato: F6-70, F6-72.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "re-running the command is idempotent: a ticket already done is not transitioned again"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Rieseguire tickets:auto-close-released è idempotente: un ticket già done non viene transitato di nuovo

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-72 — La macchina a stati ammette la transizione released -> done sia per l'assegnatario sia per l'utente di sistema (automazione T4, US-610)

**Obiettivo**
Verificare che: la macchina a stati ammette la transizione released -> done sia per l'assegnatario sia per l'utente di sistema (automazione T4, US-610).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-610.
- Test automatico: `tests/Feature/Domain/Ticketing/TicketStateMachineTest.php` — `released to done is allowed for the assignee and for the system user (T4 automation, US-610)`.
- Test correlato: F6-71.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "released to done is allowed for the assignee and for the system user \(T4 automation, US-610\)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
La macchina a stati ammette la transizione released -> done sia per l'assegnatario sia per l'utente di sistema (automazione T4, US-610)

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

## Automazioni schedulate T5/T7 — tickets:close-scrum e tickets:archive-scrum, compromesso conservativo su v1 (§10.2, US-611)

### F6-73 — tickets:close-scrum in --dry-run esamina i ticket scrum creati oggi senza chiuderne alcuno

**Obiettivo**
Verificare che: tickets:close-scrum in --dry-run esamina i ticket scrum creati oggi senza chiuderne alcuno.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-611.
- Test automatico: `tests/Feature/Console/TicketsCloseScrumCommandTest.php` — `--dry-run examines scrum tickets created today without closing any of them`.
- Test correlato: F6-74.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "--dry-run examines scrum tickets created today without closing any of them"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:close-scrum in --dry-run esamina i ticket scrum creati oggi senza chiuderne alcuno

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-74 — tickets:close-scrum chiude un ticket scrum creato oggi e valorizza done_at

**Obiettivo**
Verificare che: tickets:close-scrum chiude un ticket scrum creato oggi e valorizza done_at.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-611.
- Test automatico: `tests/Feature/Console/TicketsCloseScrumCommandTest.php` — `closes a scrum ticket created today and stamps done_at`.
- Test correlato: F6-73, F6-75.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "closes a scrum ticket created today and stamps done_at"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:close-scrum chiude un ticket scrum creato oggi e valorizza done_at

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-75 — tickets:close-scrum chiude anche un ticket scrum aggiornato oggi pur se creato in precedenza

**Obiettivo**
Verificare che: tickets:close-scrum chiude anche un ticket scrum aggiornato oggi pur se creato in precedenza.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-611.
- Test automatico: `tests/Feature/Console/TicketsCloseScrumCommandTest.php` — `closes a scrum ticket updated today even if created earlier`.
- Test correlato: F6-74, F6-76.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "closes a scrum ticket updated today even if created earlier"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:close-scrum chiude anche un ticket scrum aggiornato oggi pur se creato in precedenza

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-76 — tickets:close-scrum non tocca un ticket scrum né creato né aggiornato oggi

**Obiettivo**
Verificare che: tickets:close-scrum non tocca un ticket scrum né creato né aggiornato oggi.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-611.
- Test automatico: `tests/Feature/Console/TicketsCloseScrumCommandTest.php` — `does not touch a scrum ticket neither created nor updated today`.
- Test correlato: F6-75, F6-77.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not touch a scrum ticket neither created nor updated today"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:close-scrum non tocca un ticket scrum né creato né aggiornato oggi

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-77 — tickets:close-scrum non tocca un ticket non-scrum creato oggi

**Obiettivo**
Verificare che: tickets:close-scrum non tocca un ticket non-scrum creato oggi.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-611.
- Test automatico: `tests/Feature/Console/TicketsCloseScrumCommandTest.php` — `does not touch a non-scrum ticket created today`.
- Test correlato: F6-76, F6-78.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not touch a non-scrum ticket created today"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:close-scrum non tocca un ticket non-scrum creato oggi

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-78 — Rieseguire tickets:close-scrum è idempotente: un ticket scrum già done non viene transitato di nuovo

**Obiettivo**
Verificare che: rieseguire tickets:close-scrum è idempotente: un ticket scrum già done non viene transitato di nuovo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-611.
- Test automatico: `tests/Feature/Console/TicketsCloseScrumCommandTest.php` — `re-running the command is idempotent: a scrum ticket already done is not transitioned again`.
- Test correlato: F6-77, F6-79.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "re-running the command is idempotent: a scrum ticket already done is not transitioned again"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Rieseguire tickets:close-scrum è idempotente: un ticket scrum già done non viene transitato di nuovo

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-79 — tickets:archive-scrum in --dry-run esamina i ticket scrum archiviabili senza archiviarne alcuno

**Obiettivo**
Verificare che: tickets:archive-scrum in --dry-run esamina i ticket scrum archiviabili senza archiviarne alcuno.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-611.
- Test automatico: `tests/Feature/Console/TicketsArchiveScrumCommandTest.php` — `--dry-run examines archivable scrum tickets without archiving any of them`.
- Test correlato: F6-78, F6-80.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "--dry-run examines archivable scrum tickets without archiving any of them"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:archive-scrum in --dry-run esamina i ticket scrum archiviabili senza archiviarne alcuno

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-80 — tickets:archive-scrum archivia un ticket scrum done da almeno la soglia configurata di giorni e lo logga (colonna additiva archived_at, mai una cancellazione o un cambio di stato)

**Obiettivo**
Verificare che: tickets:archive-scrum archivia un ticket scrum done da almeno la soglia configurata di giorni e lo logga (colonna additiva archived_at, mai una cancellazione o un cambio di stato).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-611.
- Test automatico: `tests/Feature/Console/TicketsArchiveScrumCommandTest.php` — `archives a scrum ticket done for at least the configured threshold of days and logs it`.
- Test correlato: F6-79, F6-81.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "archives a scrum ticket done for at least the configured threshold of days and logs it"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:archive-scrum archivia un ticket scrum done da almeno la soglia configurata di giorni e lo logga (colonna additiva archived_at, mai una cancellazione o un cambio di stato)

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-81 — tickets:archive-scrum non archivia un ticket scrum reso done più di recente della soglia

**Obiettivo**
Verificare che: tickets:archive-scrum non archivia un ticket scrum reso done più di recente della soglia.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-611.
- Test automatico: `tests/Feature/Console/TicketsArchiveScrumCommandTest.php` — `does not archive a scrum ticket done more recently than the threshold`.
- Test correlato: F6-80, F6-82.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not archive a scrum ticket done more recently than the threshold"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:archive-scrum non archivia un ticket scrum reso done più di recente della soglia

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-82 — tickets:archive-scrum non archivia un ticket scrum che non è done

**Obiettivo**
Verificare che: tickets:archive-scrum non archivia un ticket scrum che non è done.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-611.
- Test automatico: `tests/Feature/Console/TicketsArchiveScrumCommandTest.php` — `does not archive a scrum ticket that is not done`.
- Test correlato: F6-81, F6-83.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not archive a scrum ticket that is not done"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:archive-scrum non archivia un ticket scrum che non è done

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-83 — tickets:archive-scrum non archivia un ticket non-scrum reso done molto tempo fa

**Obiettivo**
Verificare che: tickets:archive-scrum non archivia un ticket non-scrum reso done molto tempo fa.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-611.
- Test automatico: `tests/Feature/Console/TicketsArchiveScrumCommandTest.php` — `does not archive a non-scrum ticket done long ago`.
- Test correlato: F6-82, F6-84.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not archive a non-scrum ticket done long ago"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:archive-scrum non archivia un ticket non-scrum reso done molto tempo fa

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-84 — Rieseguire tickets:archive-scrum è idempotente: un ticket già archiviato non viene archiviato di nuovo

**Obiettivo**
Verificare che: rieseguire tickets:archive-scrum è idempotente: un ticket già archiviato non viene archiviato di nuovo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-611.
- Test automatico: `tests/Feature/Console/TicketsArchiveScrumCommandTest.php` — `re-running the command is idempotent: an already archived ticket is not archived again`.
- Test correlato: F6-83, F6-85.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "re-running the command is idempotent: an already archived ticket is not archived again"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Rieseguire tickets:archive-scrum è idempotente: un ticket già archiviato non viene archiviato di nuovo

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-85 — La macchina a stati ammette * -> done per l'utente di sistema su un ticket scrum, e SOLO per l'utente di sistema (T5, US-611)

**Obiettivo**
Verificare che: la macchina a stati ammette * -> done per l'utente di sistema su un ticket scrum, e SOLO per l'utente di sistema (T5, US-611).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-611.
- Test automatico: `tests/Feature/Domain/Ticketing/TicketStateMachineTest.php` — `any status to done is allowed for the system user on a scrum ticket, and only for the system user`.
- Test correlato: F6-84, F6-86.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "any status to done is allowed for the system user on a scrum ticket, and only for the system user"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
La macchina a stati ammette * -> done per l'utente di sistema su un ticket scrum, e SOLO per l'utente di sistema (T5, US-611)

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-86 — L'utente di sistema non può spostare un ticket non-scrum a done tramite la transizione T5

**Obiettivo**
Verificare che: l'utente di sistema non può spostare un ticket non-scrum a done tramite la transizione T5.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-611.
- Test automatico: `tests/Feature/Domain/Ticketing/TicketStateMachineTest.php` — `the system user cannot move a non-scrum ticket to done via T5`.
- Test correlato: F6-85, F6-87.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the system user cannot move a non-scrum ticket to done via T5"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
L'utente di sistema non può spostare un ticket non-scrum a done tramite la transizione T5

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-87 — Il catalogo TicketLogEvent contiene esattamente gli 8 valori di §6.2.1 più il nuovo evento "archived" introdotto da US-611

**Obiettivo**
Verificare che: il catalogo TicketLogEvent contiene esattamente gli 8 valori di §6.2.1 più il nuovo evento "archived" introdotto da US-611.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-611.
- Test automatico: `tests/Unit/Domain/Ticketing/TicketLogEventTest.php` — `contains exactly the 8 values of §6.2.1 plus "archived" (US-611)`.
- Test correlato: F6-86.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "contains exactly the 8 values of §6.2.1 plus "archived" \(US-611\)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il catalogo TicketLogEvent contiene esattamente gli 8 valori di §6.2.1 più il nuovo evento "archived" introdotto da US-611

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

## Automazione schedulata T6 — tickets:restore-waiting, soglia in giorni di calendario (§10.2, US-612)

### F6-88 — tickets:restore-waiting in --dry-run esamina i ticket waiting ripristinabili senza ripristinarne alcuno

**Obiettivo**
Verificare che: tickets:restore-waiting in --dry-run esamina i ticket waiting ripristinabili senza ripristinarne alcuno.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-612.
- Test automatico: `tests/Feature/Console/TicketsRestoreWaitingCommandTest.php` — `--dry-run examines restorable waiting tickets without restoring any of them`.
- Test correlato: F6-89.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "--dry-run examines restorable waiting tickets without restoring any of them"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:restore-waiting in --dry-run esamina i ticket waiting ripristinabili senza ripristinarne alcuno

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-89 — tickets:restore-waiting ripristina un ticket in attesa da esattamente la soglia configurata di giorni di calendario

**Obiettivo**
Verificare che: tickets:restore-waiting ripristina un ticket in attesa da esattamente la soglia configurata di giorni di calendario.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-612.
- Test automatico: `tests/Feature/Console/TicketsRestoreWaitingCommandTest.php` — `restores a ticket waiting for exactly the configured threshold of calendar days`.
- Test correlato: F6-88, F6-90.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "restores a ticket waiting for exactly the configured threshold of calendar days"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:restore-waiting ripristina un ticket in attesa da esattamente la soglia configurata di giorni di calendario

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-90 — tickets:restore-waiting ripristina un ticket in attesa da più della soglia configurata di giorni di calendario

**Obiettivo**
Verificare che: tickets:restore-waiting ripristina un ticket in attesa da più della soglia configurata di giorni di calendario.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-612.
- Test automatico: `tests/Feature/Console/TicketsRestoreWaitingCommandTest.php` — `restores a ticket waiting for more than the configured threshold of calendar days`.
- Test correlato: F6-89, F6-91.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "restores a ticket waiting for more than the configured threshold of calendar days"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:restore-waiting ripristina un ticket in attesa da più della soglia configurata di giorni di calendario

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-91 — tickets:restore-waiting non ripristina un ticket in attesa da un giorno in meno della soglia configurata

**Obiettivo**
Verificare che: tickets:restore-waiting non ripristina un ticket in attesa da un giorno in meno della soglia configurata.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-612.
- Test automatico: `tests/Feature/Console/TicketsRestoreWaitingCommandTest.php` — `does not restore a ticket waiting for one day less than the configured threshold`.
- Test correlato: F6-90, F6-92.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not restore a ticket waiting for one day less than the configured threshold"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:restore-waiting non ripristina un ticket in attesa da un giorno in meno della soglia configurata

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-92 — tickets:restore-waiting non tocca ticket in uno stato diverso da waiting

**Obiettivo**
Verificare che: tickets:restore-waiting non tocca ticket in uno stato diverso da waiting.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-612.
- Test automatico: `tests/Feature/Console/TicketsRestoreWaitingCommandTest.php` — `does not touch tickets in a status other than waiting`.
- Test correlato: F6-91, F6-93.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not touch tickets in a status other than waiting"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:restore-waiting non tocca ticket in uno stato diverso da waiting

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-93 — tickets:restore-waiting non tocca un ticket in waiting privo di uno stato precedente

**Obiettivo**
Verificare che: tickets:restore-waiting non tocca un ticket in waiting privo di uno stato precedente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-612.
- Test automatico: `tests/Feature/Console/TicketsRestoreWaitingCommandTest.php` — `does not touch a waiting ticket without a previous status`.
- Test correlato: F6-92, F6-94.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not touch a waiting ticket without a previous status"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:restore-waiting non tocca un ticket in waiting privo di uno stato precedente

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-94 — Rieseguire tickets:restore-waiting è idempotente: un ticket già ripristinato non viene ritoccato

**Obiettivo**
Verificare che: rieseguire tickets:restore-waiting è idempotente: un ticket già ripristinato non viene ritoccato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-612.
- Test automatico: `tests/Feature/Console/TicketsRestoreWaitingCommandTest.php` — `re-running the command is idempotent: a restored ticket is not touched again`.
- Test correlato: F6-93.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "re-running the command is idempotent: a restored ticket is not touched again"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Rieseguire tickets:restore-waiting è idempotente: un ticket già ripristinato non viene ritoccato

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

## Automazione schedulata — timetracking:aggregate-daily, orchestrazione mancante del job esistente (§10.2, US-613)

### F6-95 — timetracking:aggregate-daily consolida un ticket con attività odierna, producendo gli stessi aggregati di timetracking:recalculate

**Obiettivo**
Verificare che: timetracking:aggregate-daily consolida un ticket con attività odierna, producendo gli stessi aggregati di timetracking:recalculate.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-613.
- Test automatico: `tests/Feature/Console/TimeTrackingAggregateDailyCommandTest.php` — `consolidates a ticket with activity today, producing the same aggregates as timetracking:recalculate`.
- Test correlato: F6-96.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "consolidates a ticket with activity today, producing the same aggregates as timetracking:recalculate"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
timetracking:aggregate-daily consolida un ticket con attività odierna, producendo gli stessi aggregati di timetracking:recalculate

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-96 — timetracking:aggregate-daily ignora un ticket senza alcuna attività odierna

**Obiettivo**
Verificare che: timetracking:aggregate-daily ignora un ticket senza alcuna attività odierna.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-613.
- Test automatico: `tests/Feature/Console/TimeTrackingAggregateDailyCommandTest.php` — `ignores a ticket without any activity today`.
- Test correlato: F6-95, F6-97.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "ignores a ticket without any activity today"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
timetracking:aggregate-daily ignora un ticket senza alcuna attività odierna

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-97 — timetracking:aggregate-daily in --dry-run esamina i ticket con attività odierna senza scrivere nulla

**Obiettivo**
Verificare che: timetracking:aggregate-daily in --dry-run esamina i ticket con attività odierna senza scrivere nulla.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-613.
- Test automatico: `tests/Feature/Console/TimeTrackingAggregateDailyCommandTest.php` — `--dry-run examines tickets with activity today without writing anything`.
- Test correlato: F6-96, F6-98.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "--dry-run examines tickets with activity today without writing anything"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
timetracking:aggregate-daily in --dry-run esamina i ticket con attività odierna senza scrivere nulla

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-98 — Eseguire timetracking:aggregate-daily due volte nello stesso giorno non duplica le righe di ticket_work_logs (idempotenza via upsert)

**Obiettivo**
Verificare che: eseguire timetracking:aggregate-daily due volte nello stesso giorno non duplica le righe di ticket_work_logs (idempotenza via upsert).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §10.2, US-613.
- Test automatico: `tests/Feature/Console/TimeTrackingAggregateDailyCommandTest.php` — `running it twice on the same day does not duplicate ticket_work_logs rows`.
- Test correlato: F6-97.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "running it twice on the same day does not duplicate ticket_work_logs rows"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Eseguire timetracking:aggregate-daily due volte nello stesso giorno non duplica le righe di ticket_work_logs (idempotenza via upsert)

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

## Mailable E8 — Digest periodico giornaliero, riscritto da zero rispetto al dead code v1 (§7.5.2, US-614)

### F6-99 — mail:send-digest invia un digest a un cliente con attività su uno dei propri ticket

**Obiettivo**
Verificare che: mail:send-digest invia un digest a un cliente con attività su uno dei propri ticket.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Console/MailSendDigestCommandTest.php` — `sends a digest to a customer with activity on one of their tickets`.
- Test correlato: F6-100.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sends a digest to a customer with activity on one of their tickets"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
mail:send-digest invia un digest a un cliente con attività su uno dei propri ticket

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-100 — mail:send-digest non invia alcun digest a un cliente senza attività nelle ultime 24h

**Obiettivo**
Verificare che: mail:send-digest non invia alcun digest a un cliente senza attività nelle ultime 24h.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Console/MailSendDigestCommandTest.php` — `sends no digest to a customer without activity in the last 24h`.
- Test correlato: F6-99, F6-101.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sends no digest to a customer without activity in the last 24h"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
mail:send-digest non invia alcun digest a un cliente senza attività nelle ultime 24h

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-101 — mail:send-digest non invia a un cliente che ha già ricevuto un digest oggi (idempotenza)

**Obiettivo**
Verificare che: mail:send-digest non invia a un cliente che ha già ricevuto un digest oggi (idempotenza).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Console/MailSendDigestCommandTest.php` — `does not send to a customer who has already received a digest today`.
- Test correlato: F6-100, F6-102.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not send to a customer who has already received a digest today"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
mail:send-digest non invia a un cliente che ha già ricevuto un digest oggi (idempotenza)

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-102 — mail:send-digest rispetta la preferenza di notifica E8 disabilitata dal cliente

**Obiettivo**
Verificare che: mail:send-digest rispetta la preferenza di notifica E8 disabilitata dal cliente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Console/MailSendDigestCommandTest.php` — `respects a customer having disabled the E8 notification preference`.
- Test correlato: F6-101, F6-103.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "respects a customer having disabled the E8 notification preference"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
mail:send-digest rispetta la preferenza di notifica E8 disabilitata dal cliente

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-103 — mail:send-digest rispetta una soppressione email attiva per il cliente

**Obiettivo**
Verificare che: mail:send-digest rispetta una soppressione email attiva per il cliente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Console/MailSendDigestCommandTest.php` — `respects an active email suppression for the customer`.
- Test correlato: F6-102, F6-104.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "respects an active email suppression for the customer"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
mail:send-digest rispetta una soppressione email attiva per il cliente

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-104 — mail:send-digest in --dry-run non scrive né invia nulla

**Obiettivo**
Verificare che: mail:send-digest in --dry-run non scrive né invia nulla.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Console/MailSendDigestCommandTest.php` — `does not write or send anything in dry-run mode`.
- Test correlato: F6-103, F6-105.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not write or send anything in dry-run mode"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
mail:send-digest in --dry-run non scrive né invia nulla

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-105 — mail:send-digest non fallisce e non invia nulla quando non ci sono clienti

**Obiettivo**
Verificare che: mail:send-digest non fallisce e non invia nulla quando non ci sono clienti.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Console/MailSendDigestCommandTest.php` — `does not fail and sends no mail when there are no customers`.
- Test correlato: F6-104, F6-106.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not fail and sends no mail when there are no customers"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
mail:send-digest non fallisce e non invia nulla quando non ci sono clienti

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-106 — Il digest include un ticket con un nuovo messaggio pubblico dello staff nelle ultime 24h

**Obiettivo**
Verificare che: il digest include un ticket con un nuovo messaggio pubblico dello staff nelle ultime 24h.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Domain/Mail/Actions/BuildCustomerTicketDigestTest.php` — `includes a ticket with a new public message from staff in the last 24h`.
- Test correlato: F6-105, F6-107.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "includes a ticket with a new public message from staff in the last 24h"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il digest include un ticket con un nuovo messaggio pubblico dello staff nelle ultime 24h

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-107 — Il digest esclude un messaggio pubblicato dal cliente stesso a cui è destinato il digest

**Obiettivo**
Verificare che: il digest esclude un messaggio pubblicato dal cliente stesso a cui è destinato il digest.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Domain/Mail/Actions/BuildCustomerTicketDigestTest.php` — `excludes a message posted by the customer being digested`.
- Test correlato: F6-106, F6-108.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "excludes a message posted by the customer being digested"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il digest esclude un messaggio pubblicato dal cliente stesso a cui è destinato il digest

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-108 — Il digest esclude un messaggio interno (non pubblico)

**Obiettivo**
Verificare che: il digest esclude un messaggio interno (non pubblico).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Domain/Mail/Actions/BuildCustomerTicketDigestTest.php` — `excludes an internal message`.
- Test correlato: F6-107, F6-109.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "excludes an internal message"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il digest esclude un messaggio interno (non pubblico)

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-109 — Il digest esclude un messaggio pubblicato prima della finestra delle 24h

**Obiettivo**
Verificare che: il digest esclude un messaggio pubblicato prima della finestra delle 24h.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Domain/Mail/Actions/BuildCustomerTicketDigestTest.php` — `excludes a message posted before the window`.
- Test correlato: F6-108, F6-110.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "excludes a message posted before the window"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il digest esclude un messaggio pubblicato prima della finestra delle 24h

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-110 — Il digest include un ticket con un cambio di stato nelle ultime 24h, riportando lo stato precedente e quello corrente

**Obiettivo**
Verificare che: il digest include un ticket con un cambio di stato nelle ultime 24h, riportando lo stato precedente e quello corrente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Domain/Mail/Actions/BuildCustomerTicketDigestTest.php` — `includes a ticket with a status change in the last 24h, reporting from/to status`.
- Test correlato: F6-109, F6-111.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "includes a ticket with a status change in the last 24h, reporting from/to status"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il digest include un ticket con un cambio di stato nelle ultime 24h, riportando lo stato precedente e quello corrente

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-111 — Il digest aggrega più ticket con attività per lo stesso cliente, escludendo quelli senza attività

**Obiettivo**
Verificare che: il digest aggrega più ticket con attività per lo stesso cliente, escludendo quelli senza attività.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Domain/Mail/Actions/BuildCustomerTicketDigestTest.php` — `aggregates several tickets with activity for the same customer`.
- Test correlato: F6-110, F6-112.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "aggregates several tickets with activity for the same customer"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il digest aggrega più ticket con attività per lo stesso cliente, escludendo quelli senza attività

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-112 — Il digest ignora ticket appartenenti a un altro cliente

**Obiettivo**
Verificare che: il digest ignora ticket appartenenti a un altro cliente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Domain/Mail/Actions/BuildCustomerTicketDigestTest.php` — `ignores tickets belonging to another customer`.
- Test correlato: F6-111, F6-113.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "ignores tickets belonging to another customer"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il digest ignora ticket appartenenti a un altro cliente

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-113 — Il Mailable E8 renderizza un HTML ben formato che elenca ogni ticket con conteggio messaggi ed eventuale cambio di stato

**Obiettivo**
Verificare che: il Mailable E8 renderizza un HTML ben formato che elenca ogni ticket con conteggio messaggi ed eventuale cambio di stato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Domain/Mail/Mailables/MailDigestMailTest.php` — `renders well-formed HTML listing every ticket entry with its message count and status change`.
- Test correlato: F6-112, F6-114.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "renders well-formed HTML listing every ticket entry with its message count and status change"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il Mailable E8 renderizza un HTML ben formato che elenca ogni ticket con conteggio messaggi ed eventuale cambio di stato

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-114 — Il Mailable E8 valorizza l'header Message-Id e il Reply-To VERP dalla riga email_messages outbound

**Obiettivo**
Verificare che: il Mailable E8 valorizza l'header Message-Id e il Reply-To VERP dalla riga email_messages outbound.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Domain/Mail/Mailables/MailDigestMailTest.php` — `sets the Message-Id header and the VERP Reply-To from the outbound email_messages row`.
- Test correlato: F6-113, F6-115.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sets the Message-Id header and the VERP Reply-To from the outbound email_messages row"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il Mailable E8 valorizza l'header Message-Id e il Reply-To VERP dalla riga email_messages outbound

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-115 — Il Mailable E8 genera anche una versione testo semplice accanto all'HTML

**Obiettivo**
Verificare che: il Mailable E8 genera anche una versione testo semplice accanto all'HTML.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Domain/Mail/Mailables/MailDigestMailTest.php` — `generates a plain-text version alongside the HTML`.
- Test correlato: F6-114, F6-116.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "generates a plain-text version alongside the HTML"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il Mailable E8 genera anche una versione testo semplice accanto all'HTML

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-116 — Il Mailable E8 renderizza il corpo nella lingua impostata via ->locale(), mai una chiave non tradotta

**Obiettivo**
Verificare che: il Mailable E8 renderizza il corpo nella lingua impostata via ->locale(), mai una chiave non tradotta.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-614.
- Test automatico: `tests/Feature/Domain/Mail/Mailables/MailDigestMailTest.php` — `renders the body in the language set via ->locale(), never a raw untranslated key (§7.6, US-320)`.
- Test correlato: F6-115.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "renders the body in the language set via ->locale\(\), never a raw untranslated key \(§7.6, US-320\)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il Mailable E8 renderizza il corpo nella lingua impostata via ->locale(), mai una chiave non tradotta

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

## Mailable E10 — Report attività disponibile, dispatchato da un evento di dominio (§7.5.2, US-615)

### F6-117 — L'evento di dominio ActivityReportPdfGenerated viene dispatchato la prima volta che il PDF è generato

**Obiettivo**
Verificare che: l'evento di dominio ActivityReportPdfGenerated viene dispatchato la prima volta che il PDF è generato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-615.
- Test automatico: `tests/Feature/Domain/Reporting/ActivityReportPdfGeneratedEventTest.php` — `dispatches the domain event the first time the pdf is generated`.
- Test correlato: F6-118.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "dispatches the domain event the first time the pdf is generated"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
L'evento di dominio ActivityReportPdfGenerated viene dispatchato la prima volta che il PDF è generato

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-118 — L'evento di dominio non viene dispatchato di nuovo quando il PDF viene rigenerato

**Obiettivo**
Verificare che: l'evento di dominio non viene dispatchato di nuovo quando il PDF viene rigenerato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-615.
- Test automatico: `tests/Feature/Domain/Reporting/ActivityReportPdfGeneratedEventTest.php` — `does not dispatch the domain event again when the pdf is regenerated`.
- Test correlato: F6-117, F6-119.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not dispatch the domain event again when the pdf is regenerated"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
L'evento di dominio non viene dispatchato di nuovo quando il PDF viene rigenerato

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-119 — Il listener invia E10 all'owner quando il PDF di un report di proprietà utente viene generato

**Obiettivo**
Verificare che: il listener invia E10 all'owner quando il PDF di un report di proprietà utente viene generato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-615.
- Test automatico: `tests/Feature/Domain/Mail/Listeners/SendActivityReportPdfGeneratedNotificationTest.php` — `sends E10 to the owner when a user-owned report pdf is generated`.
- Test correlato: F6-118, F6-120.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sends E10 to the owner when a user-owned report pdf is generated"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il listener invia E10 all'owner quando il PDF di un report di proprietà utente viene generato

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-120 — Il listener invia E10 a ogni membro di un report di proprietà di un'organizzazione

**Obiettivo**
Verificare che: il listener invia E10 a ogni membro di un report di proprietà di un'organizzazione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-615.
- Test automatico: `tests/Feature/Domain/Mail/Listeners/SendActivityReportPdfGeneratedNotificationTest.php` — `sends E10 to every member of an organization-owned report`.
- Test correlato: F6-119, F6-121.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sends E10 to every member of an organization-owned report"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il listener invia E10 a ogni membro di un report di proprietà di un'organizzazione

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-121 — Il listener non invia a un utente che ha disabilitato questo tipo di notifica

**Obiettivo**
Verificare che: il listener non invia a un utente che ha disabilitato questo tipo di notifica.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-615.
- Test automatico: `tests/Feature/Domain/Mail/Listeners/SendActivityReportPdfGeneratedNotificationTest.php` — `does not send to a user who disabled this notification type`.
- Test correlato: F6-120, F6-122.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not send to a user who disabled this notification type"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il listener non invia a un utente che ha disabilitato questo tipo di notifica

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-122 — Il listener implementa ShouldQueue così l'invio avviene in modo asincrono

**Obiettivo**
Verificare che: il listener implementa ShouldQueue così l'invio avviene in modo asincrono.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-615.
- Test automatico: `tests/Feature/Domain/Mail/Listeners/SendActivityReportPdfGeneratedNotificationTest.php` — `implements ShouldQueue so the send happens asynchronously`.
- Test correlato: F6-121, F6-123.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "implements ShouldQueue so the send happens asynchronously"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il listener implementa ShouldQueue così l'invio avviene in modo asincrono

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-123 — Il Mailable E10 renderizza un HTML ben formato col periodo del report e un link di download funzionante, autorizzato dalla Policy esistente

**Obiettivo**
Verificare che: il Mailable E10 renderizza un HTML ben formato col periodo del report e un link di download funzionante, autorizzato dalla Policy esistente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-615.
- Test automatico: `tests/Feature/Domain/Mail/Mailables/ActivityReportPdfGeneratedMailTest.php` — `renders well-formed HTML with the period and a working download link`.
- Test correlato: F6-122, F6-124.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "renders well-formed HTML with the period and a working download link"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il Mailable E10 renderizza un HTML ben formato col periodo del report e un link di download funzionante, autorizzato dalla Policy esistente

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-124 — Il Mailable E10 valorizza l'header Message-Id e il Reply-To VERP dalla riga email_messages outbound

**Obiettivo**
Verificare che: il Mailable E10 valorizza l'header Message-Id e il Reply-To VERP dalla riga email_messages outbound.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-615.
- Test automatico: `tests/Feature/Domain/Mail/Mailables/ActivityReportPdfGeneratedMailTest.php` — `sets the Message-Id header and the VERP Reply-To from the outbound email_messages row`.
- Test correlato: F6-123, F6-125.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sets the Message-Id header and the VERP Reply-To from the outbound email_messages row"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il Mailable E10 valorizza l'header Message-Id e il Reply-To VERP dalla riga email_messages outbound

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-125 — Il Mailable E10 genera anche una versione testo semplice accanto all'HTML

**Obiettivo**
Verificare che: il Mailable E10 genera anche una versione testo semplice accanto all'HTML.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-615.
- Test automatico: `tests/Feature/Domain/Mail/Mailables/ActivityReportPdfGeneratedMailTest.php` — `generates a plain-text version alongside the HTML`.
- Test correlato: F6-124, F6-126.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "generates a plain-text version alongside the HTML"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il Mailable E10 genera anche una versione testo semplice accanto all'HTML

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-126 — Il Mailable E10 renderizza il corpo nella lingua impostata via ->locale(), mai una chiave non tradotta

**Obiettivo**
Verificare che: il Mailable E10 renderizza il corpo nella lingua impostata via ->locale(), mai una chiave non tradotta.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-615.
- Test automatico: `tests/Feature/Domain/Mail/Mailables/ActivityReportPdfGeneratedMailTest.php` — `renders the body in the language set via ->locale(), never a raw untranslated key (§7.6, US-320)`.
- Test correlato: F6-125, F6-127.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "renders the body in the language set via ->locale\(\), never a raw untranslated key \(§7.6, US-320\)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il Mailable E10 renderizza il corpo nella lingua impostata via ->locale(), mai una chiave non tradotta

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-127 — reports:generate-monthly, eseguito realmente end-to-end (comando -> job -> generazione PDF -> evento -> listener), accoda l'email E10 per il proprietario del report

**Obiettivo**
Verificare che: reports:generate-monthly, eseguito realmente end-to-end (comando -> job -> generazione PDF -> evento -> listener), accoda l'email E10 per il proprietario del report.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, US-615.
- Test automatico: `tests/Feature/Console/ReportsGenerateMonthlySendsActivityReportPdfGeneratedMailTest.php` — `reports:generate-monthly ends up queuing the E10 mail for the report owner`.
- Test correlato: F6-126.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "reports:generate-monthly ends up queuing the E10 mail for the report owner"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
reports:generate-monthly, eseguito realmente end-to-end (comando -> job -> generazione PDF -> evento -> listener), accoda l'email E10 per il proprietario del report

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

## Mailable E11 — Developer senza ticket in lavorazione + tickets:notify-idle-developers, comando schedulato invece di un job da observer (§7.5.2, §10.2, US-616)

### F6-128 — tickets:notify-idle-developers invia un promemoria a un developer con ticket assegnati e nessuno in lavorazione, entro la finestra oraria configurata (anche come notifica in-app)

**Obiettivo**
Verificare che: tickets:notify-idle-developers invia un promemoria a un developer con ticket assegnati e nessuno in lavorazione, entro la finestra oraria configurata (anche come notifica in-app).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, §10.2, US-616.
- Test automatico: `tests/Feature/Console/TicketsNotifyIdleDevelopersCommandTest.php` — `sends a reminder to a developer with assigned tickets and none in progress, within the window`.
- Test correlato: F6-129.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sends a reminder to a developer with assigned tickets and none in progress, within the window"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:notify-idle-developers invia un promemoria a un developer con ticket assegnati e nessuno in lavorazione, entro la finestra oraria configurata (anche come notifica in-app)

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-129 — tickets:notify-idle-developers non invia alcun promemoria a un developer con un ticket in lavorazione

**Obiettivo**
Verificare che: tickets:notify-idle-developers non invia alcun promemoria a un developer con un ticket in lavorazione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, §10.2, US-616.
- Test automatico: `tests/Feature/Console/TicketsNotifyIdleDevelopersCommandTest.php` — `sends no reminder to a developer with a ticket in progress`.
- Test correlato: F6-128, F6-130.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sends no reminder to a developer with a ticket in progress"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:notify-idle-developers non invia alcun promemoria a un developer con un ticket in lavorazione

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-130 — tickets:notify-idle-developers non invia alcun promemoria a un developer il cui unico ticket assegnato è già chiuso

**Obiettivo**
Verificare che: tickets:notify-idle-developers non invia alcun promemoria a un developer il cui unico ticket assegnato è già chiuso.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, §10.2, US-616.
- Test automatico: `tests/Feature/Console/TicketsNotifyIdleDevelopersCommandTest.php` — `sends no reminder to a developer whose only assigned ticket is already closed`.
- Test correlato: F6-129, F6-131.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sends no reminder to a developer whose only assigned ticket is already closed"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:notify-idle-developers non invia alcun promemoria a un developer il cui unico ticket assegnato è già chiuso

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-131 — tickets:notify-idle-developers non invia alcun promemoria fuori dalla finestra oraria configurata

**Obiettivo**
Verificare che: tickets:notify-idle-developers non invia alcun promemoria fuori dalla finestra oraria configurata.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, §10.2, US-616.
- Test automatico: `tests/Feature/Console/TicketsNotifyIdleDevelopersCommandTest.php` — `sends no reminder outside the configured window`.
- Test correlato: F6-130, F6-132.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sends no reminder outside the configured window"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:notify-idle-developers non invia alcun promemoria fuori dalla finestra oraria configurata

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-132 — tickets:notify-idle-developers non invia un secondo promemoria lo stesso giorno, anche in un'esecuzione successiva entro la finestra (idempotenza sulla finestra)

**Obiettivo**
Verificare che: tickets:notify-idle-developers non invia un secondo promemoria lo stesso giorno, anche in un'esecuzione successiva entro la finestra (idempotenza sulla finestra).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, §10.2, US-616.
- Test automatico: `tests/Feature/Console/TicketsNotifyIdleDevelopersCommandTest.php` — `does not send a second reminder the same day, even in a later run within the window`.
- Test correlato: F6-131, F6-133.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not send a second reminder the same day, even in a later run within the window"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:notify-idle-developers non invia un secondo promemoria lo stesso giorno, anche in un'esecuzione successiva entro la finestra (idempotenza sulla finestra)

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-133 — tickets:notify-idle-developers in --dry-run non scrive né invia nulla

**Obiettivo**
Verificare che: tickets:notify-idle-developers in --dry-run non scrive né invia nulla.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, §10.2, US-616.
- Test automatico: `tests/Feature/Console/TicketsNotifyIdleDevelopersCommandTest.php` — `does not write or send anything in dry-run mode`.
- Test correlato: F6-132, F6-134.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not write or send anything in dry-run mode"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:notify-idle-developers in --dry-run non scrive né invia nulla

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-134 — tickets:notify-idle-developers non fallisce e non invia nulla quando non ci sono developer

**Obiettivo**
Verificare che: tickets:notify-idle-developers non fallisce e non invia nulla quando non ci sono developer.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, §10.2, US-616.
- Test automatico: `tests/Feature/Console/TicketsNotifyIdleDevelopersCommandTest.php` — `does not fail and sends no mail when there are no developers`.
- Test correlato: F6-133, F6-135.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not fail and sends no mail when there are no developers"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:notify-idle-developers non fallisce e non invia nulla quando non ci sono developer

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-135 — Il Mailable E11 renderizza un HTML ben formato che elenca ogni ticket idle con il proprio stato

**Obiettivo**
Verificare che: il Mailable E11 renderizza un HTML ben formato che elenca ogni ticket idle con il proprio stato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, §10.2, US-616.
- Test automatico: `tests/Feature/Domain/Mail/Mailables/IdleDeveloperNoticeMailTest.php` — `renders well-formed HTML listing every idle ticket with its status`.
- Test correlato: F6-134, F6-136.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "renders well-formed HTML listing every idle ticket with its status"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il Mailable E11 renderizza un HTML ben formato che elenca ogni ticket idle con il proprio stato

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-136 — Il Mailable E11 valorizza l'header Message-Id e il Reply-To VERP dalla riga email_messages outbound

**Obiettivo**
Verificare che: il Mailable E11 valorizza l'header Message-Id e il Reply-To VERP dalla riga email_messages outbound.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, §10.2, US-616.
- Test automatico: `tests/Feature/Domain/Mail/Mailables/IdleDeveloperNoticeMailTest.php` — `sets the Message-Id header and the VERP Reply-To from the outbound email_messages row`.
- Test correlato: F6-135, F6-137.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sets the Message-Id header and the VERP Reply-To from the outbound email_messages row"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il Mailable E11 valorizza l'header Message-Id e il Reply-To VERP dalla riga email_messages outbound

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-137 — Il Mailable E11 genera anche una versione testo semplice accanto all'HTML

**Obiettivo**
Verificare che: il Mailable E11 genera anche una versione testo semplice accanto all'HTML.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, §10.2, US-616.
- Test automatico: `tests/Feature/Domain/Mail/Mailables/IdleDeveloperNoticeMailTest.php` — `generates a plain-text version alongside the HTML`.
- Test correlato: F6-136, F6-138.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "generates a plain-text version alongside the HTML"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il Mailable E11 genera anche una versione testo semplice accanto all'HTML

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-138 — Il Mailable E11 renderizza il corpo nella lingua impostata via ->locale(), mai una chiave non tradotta

**Obiettivo**
Verificare che: il Mailable E11 renderizza il corpo nella lingua impostata via ->locale(), mai una chiave non tradotta.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, §7.5.2, §10.2, US-616.
- Test automatico: `tests/Feature/Domain/Mail/Mailables/IdleDeveloperNoticeMailTest.php` — `renders the body in the language set via ->locale(), never a raw untranslated key (§7.6, US-320)`.
- Test correlato: F6-137.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "renders the body in the language set via ->locale\(\), never a raw untranslated key \(§7.6, US-320\)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il Mailable E11 renderizza il corpo nella lingua impostata via ->locale(), mai una chiave non tradotta

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

## Checkpoint di fine fase — isolamento multi-superficie tra clienti, sequenza combinata delle automazioni, garanzia di conservatività di archive-scrum (US-618)

### F6-139 — Due clienti con dati reali su ticket, report e fundraising restano completamente isolati attraverso dashboard, ricerca globale ed elenco ticket, non solo su una superficie alla volta

**Obiettivo**
Verificare che: due clienti con dati reali su ticket, report e fundraising restano completamente isolati attraverso dashboard, ricerca globale ed elenco ticket, non solo su una superficie alla volta.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, US-618.
- Test automatico: `tests/Feature/EndToEnd/Fase6CheckpointEndToEndTest.php` — `two customers with real data across tickets, reports and fundraising stay fully isolated across the dashboard, global search and the ticket list`.
- Test correlato: F6-140.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "two customers with real data across tickets, reports and fundraising stay fully isolated across the dashboard, global search and the ticket list"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Due clienti con dati reali su ticket, report e fundraising restano completamente isolati attraverso dashboard, ricerca globale ed elenco ticket, non solo su una superficie alla volta

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-140 — Eseguire in sequenza tutti i comandi schedulati di Fase 6 transita ogni ticket guardato esattamente una volta e mai un ticket fuori dal proprio guard, anche ripetendo l'intera sequenza (idempotenza combinata)

**Obiettivo**
Verificare che: eseguire in sequenza tutti i comandi schedulati di Fase 6 transita ogni ticket guardato esattamente una volta e mai un ticket fuori dal proprio guard, anche ripetendo l'intera sequenza (idempotenza combinata).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, US-618.
- Test automatico: `tests/Feature/EndToEnd/Fase6CheckpointEndToEndTest.php` — `running every Fase 6 scheduled command in sequence transitions each guarded ticket exactly once and never a ticket outside its guard`.
- Test correlato: F6-139, F6-141.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "running every Fase 6 scheduled command in sequence transitions each guarded ticket exactly once and never a ticket outside its guard"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Eseguire in sequenza tutti i comandi schedulati di Fase 6 transita ogni ticket guardato esattamente una volta e mai un ticket fuori dal proprio guard, anche ripetendo l'intera sequenza (idempotenza combinata)

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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

### F6-141 — tickets:archive-scrum è un compromesso strettamente additivo: non tocca mai lo stato del ticket né alcun campo oltre archived_at, solo un log di sistema dedicato (garanzia esplicita del compromesso segnalato al committente, US-611)

**Obiettivo**
Verificare che: tickets:archive-scrum è un compromesso strettamente additivo: non tocca mai lo stato del ticket né alcun campo oltre archived_at, solo un log di sistema dedicato (garanzia esplicita del compromesso segnalato al committente, US-611).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 6, US-618.
- Test automatico: `tests/Feature/EndToEnd/Fase6CheckpointEndToEndTest.php` — `archive-scrum is a strictly additive compromise: it never touches ticket status or any field besides archived_at, only ever a dedicated system log`.
- Test correlato: F6-140.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il test costruisce i propri dati in memoria/DB di test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "archive-scrum is a strictly additive compromise: it never touches ticket status or any field besides archived_at, only ever a dedicated system log"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
tickets:archive-scrum è un compromesso strettamente additivo: non tocca mai lo stato del ticket né alcun campo oltre archived_at, solo un log di sistema dedicato (garanzia esplicita del compromesso segnalato al committente, US-611)

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il/i test indicato/i risulta/no passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

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
