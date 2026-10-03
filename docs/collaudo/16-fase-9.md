# Fase 9 (Sincronizzazione live CAI/RUNTS, fallback CF/PIVA, upload manuale documenti, menu Gruppo Regionale) — Casi di test dettagliati

> Torna a [`README.md`](README.md) · Istruzioni generali: [`00-istruzioni-generali.md`](00-istruzioni-generali.md) · Matrice di tracciabilità: [`01-matrice-tracciabilita.md`](01-matrice-tracciabilita.md)

174 casi di test (F9-01 — F9-174) su 36 argomenti. Prima di eseguire un test, leggi le convenzioni comuni in `00-istruzioni-generali.md` (in particolare le sezioni 8 "Ambiente UAT", 9 "Credenziali" e 12 "Prerequisiti generali"). Gli argomenti sono raggruppati per user story/storia del ramo `ralph/orchestrator-v2-fase-9` — che contiene tre filoni distinti: rifiniture ticket/email (Richiesta obbligatoria, rimozione "Ticket padre", footer aziendale), sincronizzazione live CAI/RUNTS (US-901..US-928 più una decina di storie non numerate), e fallback CF/PIVA + sorgente documento/upload manuale + menu Gruppo Regionale — in ordine cronologico di sviluppo, più un ultimo argomento dedicato al checkpoint di fine fase. I casi AUTOMATICO sono verificati eseguendo la suite Pest (PHP, ruolo Sviluppatore) o pytest (servizio Python `cai-runts-scraper`, stesso ruolo); i casi MANUALE UI sono pensati per un tester che opera realmente sull'ambiente UAT (credenziali/URL: punti 8-9 di `00-istruzioni-generali.md`) — entrambe le categorie sono sempre appoggiate a un test automatico REALMENTE esistente e verificato da `php artisan collaudo:verify-manifest 9`.

## Campo "Richiesta" obbligatorio alla creazione ticket, rimozione del campo "Ticket padre"

### F9-01 — Creare un ticket richiede sempre una Richiesta

**Obiettivo**
Verificare che: creare un ticket richiede sempre una Richiesta.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Campo "Richiesta" obbligatorio alla creazione ticket, rimozione del campo "Ticket padre"".
- Test automatico: `tests/Feature/Filament/Ticketing/TicketResourceTest.php` — `creating a ticket requires a richiesta`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Ticketing/TicketResourceTest.php`.
- Test correlato: F9-02, F9-03, F9-04, F9-05

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Creare un ticket richiede sempre una Richiesta |

**Risultato finale atteso**
Creare un ticket richiede sempre una Richiesta.

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

### F9-02 — La Richiesta diventa il primo messaggio pubblico del ticket, con i suoi allegati

**Obiettivo**
Verificare che: la Richiesta diventa il primo messaggio pubblico del ticket, con i suoi allegati.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Campo "Richiesta" obbligatorio alla creazione ticket, rimozione del campo "Ticket padre"".
- Test automatico: `tests/Feature/Filament/Ticketing/TicketResourceTest.php` — `the richiesta becomes the first public message of the ticket, with its attachments`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Ticketing/TicketResourceTest.php`.
- Test correlato: F9-01, F9-03, F9-04, F9-05

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | La Richiesta diventa il primo messaggio pubblico del ticket, con i suoi allegati |

**Risultato finale atteso**
La Richiesta diventa il primo messaggio pubblico del ticket, con i suoi allegati.

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

### F9-03 — Anche lo staff che crea un ticket fornisce la Richiesta come primo messaggio pubblico

**Obiettivo**
Verificare che: anche lo staff che crea un ticket fornisce la Richiesta come primo messaggio pubblico.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Campo "Richiesta" obbligatorio alla creazione ticket, rimozione del campo "Ticket padre"".
- Test automatico: `tests/Feature/Filament/Ticketing/TicketResourceTest.php` — `staff creating a ticket also provides the richiesta as the first public message`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Ticketing/TicketResourceTest.php`.
- Test correlato: F9-01, F9-02, F9-04, F9-05

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Anche lo staff che crea un ticket fornisce la Richiesta come primo messaggio pubblico |

**Risultato finale atteso**
Anche lo staff che crea un ticket fornisce la Richiesta come primo messaggio pubblico.

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

### F9-04 — Il campo "Ticket padre" è assente dal form di creazione ma resta presente in modifica

**Obiettivo**
Verificare che: il campo "Ticket padre" è assente dal form di creazione ma resta presente in modifica.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Campo "Richiesta" obbligatorio alla creazione ticket, rimozione del campo "Ticket padre"".
- Test automatico: `tests/Feature/Filament/Ticketing/TicketResourceTest.php` — `the parent ticket field is absent from the create form but still present on edit`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Ticketing/TicketResourceTest.php`.
- Test correlato: F9-01, F9-02, F9-03, F9-05

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Il campo "Ticket padre" è assente dal form di creazione ma resta presente in modifica |

**Risultato finale atteso**
Il campo "Ticket padre" è assente dal form di creazione ma resta presente in modifica.

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

### F9-05 — Il campo nascosto "Ticket padre" in creazione non è impostabile manipolando la fillForm

**Obiettivo**
Verificare che: il campo nascosto "Ticket padre" in creazione non è impostabile manipolando la fillForm.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Campo "Richiesta" obbligatorio alla creazione ticket, rimozione del campo "Ticket padre"".
- Test automatico: `tests/Feature/Filament/Ticketing/TicketResourceTest.php` — `the hidden parent ticket field on create cannot be set via a manipulated fillForm`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Ticketing/TicketResourceTest.php`.
- Test correlato: F9-01, F9-02, F9-03, F9-04

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Il campo nascosto "Ticket padre" in creazione non è impostabile manipolando la fillForm |

**Risultato finale atteso**
Il campo nascosto "Ticket padre" in creazione non è impostabile manipolando la fillForm.

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

## Email di conferma apertura ticket (E1/E2) mostra titolo e Richiesta

### F9-06 — L'email mostra il titolo e il corpo del primo messaggio pubblico (la Richiesta)

**Obiettivo**
Verificare che: l'email mostra il titolo e il corpo del primo messaggio pubblico (la Richiesta).

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Email di conferma apertura ticket (E1/E2) mostra titolo e Richiesta".
- Test automatico: `tests/Feature/Domain/Mail/Mailables/TicketRequestBodyInEmailTest.php` — `the email shows the title and the body of the first public message (the richiesta)`.
- File/componente applicativo rilevante: `tests/Feature/Domain/Mail/Mailables/TicketRequestBodyInEmailTest.php`.
- Test correlato: F9-07

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the email shows the title and the body of the first public message (the richiesta)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: L'email mostra il titolo e il corpo del primo messaggio pubblico (la Richiesta).

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-07 — Un ticket senza alcun messaggio rende l'email senza andare in errore

**Obiettivo**
Verificare che: un ticket senza alcun messaggio rende l'email senza andare in errore.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Email di conferma apertura ticket (E1/E2) mostra titolo e Richiesta".
- Test automatico: `tests/Feature/Domain/Mail/Mailables/TicketRequestBodyInEmailTest.php` — `a ticket without any message renders the email without crashing`.
- File/componente applicativo rilevante: `tests/Feature/Domain/Mail/Mailables/TicketRequestBodyInEmailTest.php`.
- Test correlato: F9-06

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a ticket without any message renders the email without crashing"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Un ticket senza alcun messaggio rende l'email senza andare in errore.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## Nuovo indirizzo e telefono Montagna Servizi nel footer delle email

### F9-08 — Il footer mostra il nuovo indirizzo/telefono aziendale, mai il vecchio indirizzo

**Obiettivo**
Verificare che: il footer mostra il nuovo indirizzo/telefono aziendale, mai il vecchio indirizzo.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Nuovo indirizzo e telefono Montagna Servizi nel footer delle email".
- Test automatico: `tests/Feature/Domain/Mail/EmailLayoutTest.php` — `the footer shows the current company address and phone number, not the old address`.
- File/componente applicativo rilevante: `tests/Feature/Domain/Mail/EmailLayoutTest.php`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the footer shows the current company address and phone number, not the old address"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il footer mostra il nuovo indirizzo/telefono aziendale, mai il vecchio indirizzo.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## cai_last_synced_at su sezioni/sottosezioni (US-901/US-902)

### F9-09 — cai_sections ha una colonna cai_last_synced_at nullable

**Obiettivo**
Verificare che: cai_sections ha una colonna cai_last_synced_at nullable.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai_last_synced_at su sezioni/sottosezioni (US-901/US-902)".
- Test automatico: `tests/Feature/Database/CaiLastSyncedAtColumnsTest.php` — `cai_sections has a nullable cai_last_synced_at column`.
- File/componente applicativo rilevante: `tests/Feature/Database/CaiLastSyncedAtColumnsTest.php`.
- Test correlato: F9-10, F9-11, F9-12

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai_sections has a nullable cai_last_synced_at column"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: cai_sections ha una colonna cai_last_synced_at nullable.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-10 — cai_subsections ha una colonna cai_last_synced_at nullable

**Obiettivo**
Verificare che: cai_subsections ha una colonna cai_last_synced_at nullable.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai_last_synced_at su sezioni/sottosezioni (US-901/US-902)".
- Test automatico: `tests/Feature/Database/CaiLastSyncedAtColumnsTest.php` — `cai_subsections has a nullable cai_last_synced_at column`.
- File/componente applicativo rilevante: `tests/Feature/Database/CaiLastSyncedAtColumnsTest.php`.
- Test correlato: F9-09, F9-11, F9-12

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai_subsections has a nullable cai_last_synced_at column"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: cai_subsections ha una colonna cai_last_synced_at nullable.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-11 — cai_last_synced_at è mass-assignable e castato a datetime su CaiSection

**Obiettivo**
Verificare che: cai_last_synced_at è mass-assignable e castato a datetime su CaiSection.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai_last_synced_at su sezioni/sottosezioni (US-901/US-902)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Models/CaiLastSyncedAtFillableTest.php` — `cai_last_synced_at is mass-assignable and cast to a datetime on CaiSection`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Models/CaiLastSyncedAtFillableTest.php`.
- Test correlato: F9-09, F9-10, F9-12

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai_last_synced_at is mass-assignable and cast to a datetime on CaiSection"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: cai_last_synced_at è mass-assignable e castato a datetime su CaiSection.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-12 — cai_last_synced_at è mass-assignable e castato a datetime su CaiSubsection

**Obiettivo**
Verificare che: cai_last_synced_at è mass-assignable e castato a datetime su CaiSubsection.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai_last_synced_at su sezioni/sottosezioni (US-901/US-902)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Models/CaiLastSyncedAtFillableTest.php` — `cai_last_synced_at is mass-assignable and cast to a datetime on CaiSubsection`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Models/CaiLastSyncedAtFillableTest.php`.
- Test correlato: F9-09, F9-10, F9-11

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai_last_synced_at is mass-assignable and cast to a datetime on CaiSubsection"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: cai_last_synced_at è mass-assignable e castato a datetime su CaiSubsection.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## CaiSectionFieldMapper condiviso fra import datapack e sync live (US-903)

### F9-13 — mapSection mappa una riga cai_* normalizzata sugli attributi di CaiSection

**Obiettivo**
Verificare che: mapSection mappa una riga cai_* normalizzata sugli attributi di CaiSection.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiSectionFieldMapper condiviso fra import datapack e sync live (US-903)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php` — `mapSection maps a normalized cai_* row to CaiSection attributes`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php`.
- Test correlato: F9-14, F9-15, F9-16

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "mapSection maps a normalized cai_* row to CaiSection attributes"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: mapSection mappa una riga cai_* normalizzata sugli attributi di CaiSection.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-14 — mapSubsection mappa una riga cai_* normalizzata sugli attributi di CaiSubsection

**Obiettivo**
Verificare che: mapSubsection mappa una riga cai_* normalizzata sugli attributi di CaiSubsection.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiSectionFieldMapper condiviso fra import datapack e sync live (US-903)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php` — `mapSubsection maps a normalized cai_* row to CaiSubsection attributes`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php`.
- Test correlato: F9-13, F9-15, F9-16

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "mapSubsection maps a normalized cai_* row to CaiSubsection attributes"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: mapSubsection mappa una riga cai_* normalizzata sugli attributi di CaiSubsection.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-15 — toCoordinate scarta valori non plausibili (|x| >= 1000)

**Obiettivo**
Verificare che: toCoordinate scarta valori non plausibili (|x| >= 1000).

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiSectionFieldMapper condiviso fra import datapack e sync live (US-903)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php` — `toCoordinate discards implausible values (|x| >= 1000)`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php`.
- Test correlato: F9-13, F9-14, F9-16

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "toCoordinate discards implausible values (|x| >= 1000)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: toCoordinate scarta valori non plausibili (|x| >= 1000).

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-16 — matchUserId cerca l'email in modo case-insensitive e con trim

**Obiettivo**
Verificare che: matchUserId cerca l'email in modo case-insensitive e con trim.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiSectionFieldMapper condiviso fra import datapack e sync live (US-903)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php` — `matchUserId is a case-insensitive, trimmed email lookup`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php`.
- Test correlato: F9-13, F9-14, F9-15

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "matchUserId is a case-insensitive, trimmed email lookup"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: matchUserId cerca l'email in modo case-insensitive e con trim.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## Normalizzatore JSON API CAI pubblica (US-904)

### F9-17 — normalizeSection converte i campi grezzi dell'API CAI nella forma cai_* del datapack

**Obiettivo**
Verificare che: normalizeSection converte i campi grezzi dell'API CAI nella forma cai_* del datapack.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Normalizzatore JSON API CAI pubblica (US-904)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php` — `normalizeSection converts raw CAI API fields to the cai_* row shape`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php`.
- Test correlato: F9-18, F9-19, F9-20

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "normalizeSection converts raw CAI API fields to the cai_* row shape"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: normalizeSection converte i campi grezzi dell'API CAI nella forma cai_* del datapack.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-18 — normalizeSection ripiega su office_address/postal_address in snake_case

**Obiettivo**
Verificare che: normalizeSection ripiega su office_address/postal_address in snake_case.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Normalizzatore JSON API CAI pubblica (US-904)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php` — `normalizeSection falls back to office_address/postal_address snake_case keys`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php`.
- Test correlato: F9-17, F9-19, F9-20

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "normalizeSection falls back to office_address/postal_address snake_case keys"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: normalizeSection ripiega su office_address/postal_address in snake_case.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-19 — normalizeSubsection converte i campi grezzi dell'API CAI, associati al codice della sezione madre

**Obiettivo**
Verificare che: normalizeSubsection converte i campi grezzi dell'API CAI, associati al codice della sezione madre.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Normalizzatore JSON API CAI pubblica (US-904)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php` — `normalizeSubsection converts raw CAI API fields to the cai_* row shape, scoped to the parent section code`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php`.
- Test correlato: F9-17, F9-18, F9-20

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "normalizeSubsection converts raw CAI API fields to the cai_* row shape, scoped to the parent section code"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: normalizeSubsection converte i campi grezzi dell'API CAI, associati al codice della sezione madre.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-20 — normalizeSubsection ripiega su lastyearMembershipsCount se currentMemberships è assente

**Obiettivo**
Verificare che: normalizeSubsection ripiega su lastyearMembershipsCount se currentMemberships è assente.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Normalizzatore JSON API CAI pubblica (US-904)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php` — `normalizeSubsection falls back to lastyearMembershipsCount when currentMemberships is absent`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php`.
- Test correlato: F9-17, F9-18, F9-19

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "normalizeSubsection falls back to lastyearMembershipsCount when currentMemberships is absent"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: normalizeSubsection ripiega su lastyearMembershipsCount se currentMemberships è assente.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## CaiApiClient con retry per l'API pubblica CAI (US-905)

### F9-21 — fetchNationalSections restituisce l'array JSON decodificato dall'endpoint sections-list-simple

**Obiettivo**
Verificare che: fetchNationalSections restituisce l'array JSON decodificato dall'endpoint sections-list-simple.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiApiClient con retry per l'API pubblica CAI (US-905)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php` — `fetchNationalSections returns the decoded JSON array from the sections-list-simple endpoint`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php`.
- Test correlato: F9-22, F9-23, F9-24

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "fetchNationalSections returns the decoded JSON array from the sections-list-simple endpoint"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: fetchNationalSections restituisce l'array JSON decodificato dall'endpoint sections-list-simple.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-22 — fetchSubsections restituisce l'array JSON decodificato dall'endpoint per-sezione

**Obiettivo**
Verificare che: fetchSubsections restituisce l'array JSON decodificato dall'endpoint per-sezione.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiApiClient con retry per l'API pubblica CAI (US-905)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php` — `fetchSubsections returns the decoded JSON array from the per-section endpoint`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php`.
- Test correlato: F9-21, F9-23, F9-24

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "fetchSubsections returns the decoded JSON array from the per-section endpoint"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: fetchSubsections restituisce l'array JSON decodificato dall'endpoint per-sezione.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-23 — fetchNationalSections ritenta fino a 3 volte su un fallimento di connessione, poi lancia un'eccezione

**Obiettivo**
Verificare che: fetchNationalSections ritenta fino a 3 volte su un fallimento di connessione, poi lancia un'eccezione.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiApiClient con retry per l'API pubblica CAI (US-905)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php` — `fetchNationalSections retries up to 3 times on connection failure then throws`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php`.
- Test correlato: F9-21, F9-22, F9-24

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "fetchNationalSections retries up to 3 times on connection failure then throws"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: fetchNationalSections ritenta fino a 3 volte su un fallimento di connessione, poi lancia un'eccezione.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-24 — fetchNationalSections ha successo se un tentativo successivo recupera

**Obiettivo**
Verificare che: fetchNationalSections ha successo se un tentativo successivo recupera.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiApiClient con retry per l'API pubblica CAI (US-905)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php` — `fetchNationalSections succeeds if a later attempt recovers`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php`.
- Test correlato: F9-21, F9-22, F9-23

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "fetchNationalSections succeeds if a later attempt recovers"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: fetchNationalSections ha successo se un tentativo successivo recupera.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## SyncCaiSectionAndSubsections — sincronizzazione live condivisa sezione+sottosezioni (US-906)

### F9-25 — run crea una nuova CaiSection con cai_last_synced_at valorizzato

**Obiettivo**
Verificare che: run crea una nuova CaiSection con cai_last_synced_at valorizzato.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "SyncCaiSectionAndSubsections — sincronizzazione live condivisa sezione+sottosezioni (US-906)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php` — `run creates a new CaiSection with cai_last_synced_at set`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php`.
- Test correlato: F9-26, F9-27, F9-28, F9-29, F9-30

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run creates a new CaiSection with cai_last_synced_at set"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run crea una nuova CaiSection con cai_last_synced_at valorizzato.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-26 — run aggiorna una CaiSection esistente quando un campo è davvero cambiato, e aggiorna sempre cai_last_synced_at

**Obiettivo**
Verificare che: run aggiorna una CaiSection esistente quando un campo è davvero cambiato, e aggiorna sempre cai_last_synced_at.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "SyncCaiSectionAndSubsections — sincronizzazione live condivisa sezione+sottosezioni (US-906)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php` — `run updates an existing CaiSection when a field actually changed, and always bumps cai_last_synced_at`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php`.
- Test correlato: F9-25, F9-27, F9-28, F9-29, F9-30

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run updates an existing CaiSection when a field actually changed, and always bumps cai_last_synced_at"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run aggiorna una CaiSection esistente quando un campo è davvero cambiato, e aggiorna sempre cai_last_synced_at.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-27 — run conta una sezione come invariata quando nulla è davvero cambiato, ma aggiorna comunque cai_last_synced_at

**Obiettivo**
Verificare che: run conta una sezione come invariata quando nulla è davvero cambiato, ma aggiorna comunque cai_last_synced_at.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "SyncCaiSectionAndSubsections — sincronizzazione live condivisa sezione+sottosezioni (US-906)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php` — `run counts a section as skipped when nothing actually changed, but still bumps cai_last_synced_at`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php`.
- Test correlato: F9-25, F9-26, F9-28, F9-29, F9-30

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run counts a section as skipped when nothing actually changed, but still bumps cai_last_synced_at"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run conta una sezione come invariata quando nulla è davvero cambiato, ma aggiorna comunque cai_last_synced_at.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-28 — run crea le sottosezioni recuperate dall'endpoint per-sezione

**Obiettivo**
Verificare che: run crea le sottosezioni recuperate dall'endpoint per-sezione.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "SyncCaiSectionAndSubsections — sincronizzazione live condivisa sezione+sottosezioni (US-906)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php` — `run creates subsections fetched from the per-section API endpoint`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php`.
- Test correlato: F9-25, F9-26, F9-27, F9-29, F9-30

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run creates subsections fetched from the per-section API endpoint"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run crea le sottosezioni recuperate dall'endpoint per-sezione.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-29 — run non scrive nulla in modalità dry-run

**Obiettivo**
Verificare che: run non scrive nulla in modalità dry-run.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "SyncCaiSectionAndSubsections — sincronizzazione live condivisa sezione+sottosezioni (US-906)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php` — `run does not write anything in dry-run mode`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php`.
- Test correlato: F9-25, F9-26, F9-27, F9-28, F9-30

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run does not write anything in dry-run mode"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run non scrive nulla in modalità dry-run.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-30 — run associa l'email di una sezione a un utente esistente, senza distinguere maiuscole/minuscole

**Obiettivo**
Verificare che: run associa l'email di una sezione a un utente esistente, senza distinguere maiuscole/minuscole.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "SyncCaiSectionAndSubsections — sincronizzazione live condivisa sezione+sottosezioni (US-906)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php` — `run matches a section email to an existing user, case-insensitively`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php`.
- Test correlato: F9-25, F9-26, F9-27, F9-28, F9-29

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run matches a section email to an existing user, case-insensitively"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run associa l'email di una sezione a un utente esistente, senza distinguere maiuscole/minuscole.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## ScrapeCaiSection — sincronizzazione live di una sola sezione (US-907)

### F9-31 — run recupera l'elenco nazionale, isola la sezione richiesta e la sincronizza

**Obiettivo**
Verificare che: run recupera l'elenco nazionale, isola la sezione richiesta e la sincronizza.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "ScrapeCaiSection — sincronizzazione live di una sola sezione (US-907)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/ScrapeCaiSectionTest.php` — `run fetches the national list, isolates the requested section and syncs it`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/ScrapeCaiSectionTest.php`.
- Test correlato: F9-32, F9-33

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run fetches the national list, isolates the requested section and syncs it"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run recupera l'elenco nazionale, isola la sezione richiesta e la sincronizza.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-32 — run recupera le sottosezioni solo della sezione richiesta, mai delle altre sezioni dell'elenco nazionale

**Obiettivo**
Verificare che: run recupera le sottosezioni solo della sezione richiesta, mai delle altre sezioni dell'elenco nazionale.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "ScrapeCaiSection — sincronizzazione live di una sola sezione (US-907)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/ScrapeCaiSectionTest.php` — `run only fetches subsections for the requested section, never for other sections in the national list`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/ScrapeCaiSectionTest.php`.
- Test correlato: F9-31, F9-33

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run only fetches subsections for the requested section, never for other sections in the national list"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run recupera le sottosezioni solo della sezione richiesta, mai delle altre sezioni dell'elenco nazionale.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-33 — run lancia un'eccezione se il codice sezione richiesto non è nell'elenco nazionale

**Obiettivo**
Verificare che: run lancia un'eccezione se il codice sezione richiesto non è nell'elenco nazionale.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "ScrapeCaiSection — sincronizzazione live di una sola sezione (US-907)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/ScrapeCaiSectionTest.php` — `run throws when the requested section code is not present in the national list`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/ScrapeCaiSectionTest.php`.
- Test correlato: F9-31, F9-32

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run throws when the requested section code is not present in the national list"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run lancia un'eccezione se il codice sezione richiesto non è nell'elenco nazionale.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## Bottone "Sincronizza dati CAI" sulla dashboard cliente Sezione (US-908, Storia 4)

### F9-34 — Il bottone è visibile solo per un cliente Sezione con una CaiSection collegata

**Obiettivo**
Verificare che: il bottone è visibile solo per un cliente Sezione con una CaiSection collegata.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Bottone "Sincronizza dati CAI" sulla dashboard cliente Sezione (US-908, Storia 4)".
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the sync cai data action is visible only for a sezione customer with a linked cai section`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Pages/CustomerDashboardTest.php`.
- Test correlato: F9-35

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer (Sezione)

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Il bottone è visibile solo per un cliente Sezione con una CaiSection collegata |

**Risultato finale atteso**
Il bottone è visibile solo per un cliente Sezione con una CaiSection collegata.

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

### F9-35 — Il bottone sincronizza dal vivo solo la sezione del cliente autenticato, tramite l'API pubblica CAI

**Obiettivo**
Verificare che: il bottone sincronizza dal vivo solo la sezione del cliente autenticato, tramite l'API pubblica CAI.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Bottone "Sincronizza dati CAI" sulla dashboard cliente Sezione (US-908, Storia 4)".
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the sync cai data action live-scrapes only the current customer's own section from the CAI API`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Pages/CustomerDashboardTest.php`.
- Test correlato: F9-34

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Il bottone sincronizza dal vivo solo la sezione del cliente autenticato, tramite l'API pubblica CAI |

**Risultato finale atteso**
Il bottone sincronizza dal vivo solo la sezione del cliente autenticato, tramite l'API pubblica CAI.

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

## Data ultimo aggiornamento CAI/RUNTS visibile nell'Infolist condiviso (US-909/US-927)

### F9-36 — La pagina sezione mostra la data dell'ultimo aggiornamento dal vivo (CAI), o un segnaposto "mai sincronizzato"

**Obiettivo**
Verificare che: la pagina sezione mostra la data dell'ultimo aggiornamento dal vivo (CAI), o un segnaposto "mai sincronizzato".

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Data ultimo aggiornamento CAI/RUNTS visibile nell'Infolist condiviso (US-909/US-927)".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `the section detail page shows the last live-sync timestamp, or a "never synced" placeholder`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: F9-37, F9-38

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | La pagina sezione mostra la data dell'ultimo aggiornamento dal vivo (CAI), o un segnaposto "mai sincronizzato" |

**Risultato finale atteso**
La pagina sezione mostra la data dell'ultimo aggiornamento dal vivo (CAI), o un segnaposto "mai sincronizzato".

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

### F9-37 — La pagina sezione mostra la data dell'ultimo aggiornamento dal vivo RUNTS, o un segnaposto "mai sincronizzato"

**Obiettivo**
Verificare che: la pagina sezione mostra la data dell'ultimo aggiornamento dal vivo RUNTS, o un segnaposto "mai sincronizzato".

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Data ultimo aggiornamento CAI/RUNTS visibile nell'Infolist condiviso (US-909/US-927)".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `the section detail page shows the last RUNTS live-sync timestamp, or a "never synced" placeholder`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: F9-36, F9-38

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | La pagina sezione mostra la data dell'ultimo aggiornamento dal vivo RUNTS, o un segnaposto "mai sincronizzato" |

**Risultato finale atteso**
La pagina sezione mostra la data dell'ultimo aggiornamento dal vivo RUNTS, o un segnaposto "mai sincronizzato".

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

### F9-38 — cai_runts_registrations ha una colonna runts_last_synced_at nullable, mass-assignable e castata a datetime

**Obiettivo**
Verificare che: cai_runts_registrations ha una colonna runts_last_synced_at nullable, mass-assignable e castata a datetime.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Data ultimo aggiornamento CAI/RUNTS visibile nell'Infolist condiviso (US-909/US-927)".
- Test automatico: `tests/Feature/Database/CaiRuntsRegistrationLastSyncedAtTest.php` — `cai_runts_registrations has a nullable runts_last_synced_at column, mass-assignable and cast to datetime`.
- File/componente applicativo rilevante: `tests/Feature/Database/CaiRuntsRegistrationLastSyncedAtTest.php`.
- Test correlato: F9-36, F9-37

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai_runts_registrations has a nullable runts_last_synced_at column, mass-assignable and cast to datetime"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: cai_runts_registrations ha una colonna runts_last_synced_at nullable, mass-assignable e castata a datetime.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## cai:sync-national — refresh mensile schedulato dietro feature flag (US-910)

### F9-39 — cai:sync-national sincronizza ogni sezione e sottosezione restituita dall'API nazionale

**Obiettivo**
Verificare che: cai:sync-national sincronizza ogni sezione e sottosezione restituita dall'API nazionale.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai:sync-national — refresh mensile schedulato dietro feature flag (US-910)".
- Test automatico: `tests/Feature/Console/CaiSyncNationalCommandTest.php` — `cai:sync-national syncs every section and subsection returned by the national API`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncNationalCommandTest.php`.
- Test correlato: F9-40, F9-41

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-national syncs every section and subsection returned by the national API"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: cai:sync-national sincronizza ogni sezione e sottosezione restituita dall'API nazionale.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-40 — cai:sync-national --dry-run non scrive nulla

**Obiettivo**
Verificare che: cai:sync-national --dry-run non scrive nulla.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai:sync-national — refresh mensile schedulato dietro feature flag (US-910)".
- Test automatico: `tests/Feature/Console/CaiSyncNationalCommandTest.php` — `cai:sync-national --dry-run does not write anything`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncNationalCommandTest.php`.
- Test correlato: F9-39, F9-41

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-national --dry-run does not write anything"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: cai:sync-national --dry-run non scrive nulla.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-41 — cai:sync-national prosegue oltre una sezione che fallisce la sincronizzazione

**Obiettivo**
Verificare che: cai:sync-national prosegue oltre una sezione che fallisce la sincronizzazione.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai:sync-national — refresh mensile schedulato dietro feature flag (US-910)".
- Test automatico: `tests/Feature/Console/CaiSyncNationalCommandTest.php` — `cai:sync-national continues past a section that fails to sync`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncNationalCommandTest.php`.
- Test correlato: F9-39, F9-40

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-national continues past a section that fails to sync"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: cai:sync-national prosegue oltre una sezione che fallisce la sincronizzazione.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## Servizio Python cai-runts-scraper — scaffold, endpoint /health, porting scraper/analyzer (US-912/US-913)

### F9-42 — L'endpoint /health del servizio risponde ok

**Obiettivo**
Verificare che: l'endpoint /health del servizio risponde ok.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Servizio Python cai-runts-scraper — scaffold, endpoint /health, porting scraper/analyzer (US-912/US-913)".
- Test automatico: `cai-runts-scraper/tests/test_main.py` — `def test_health_returns_ok`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_main.py`.
- Test correlato: F9-43, F9-44, F9-45

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_main.py -k "test_health_returns_ok"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: L'endpoint /health del servizio risponde ok.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-43 — run_scraper è una funzione asincrona con i parametri attesi (porting dal prototipo)

**Obiettivo**
Verificare che: run_scraper è una funzione asincrona con i parametri attesi (porting dal prototipo).

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Servizio Python cai-runts-scraper — scaffold, endpoint /health, porting scraper/analyzer (US-912/US-913)".
- Test automatico: `cai-runts-scraper/tests/test_ported_modules.py` — `def test_run_scraper_is_an_async_function_with_expected_parameters`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_ported_modules.py`.
- Test correlato: F9-42, F9-44, F9-45

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_ported_modules.py -k "test_run_scraper_is_an_async_function_with_expected_parameters"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run_scraper è una funzione asincrona con i parametri attesi (porting dal prototipo).

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-44 — classify_codice_pratica mappa correttamente i codici pratica noti

**Obiettivo**
Verificare che: classify_codice_pratica mappa correttamente i codici pratica noti.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Servizio Python cai-runts-scraper — scaffold, endpoint /health, porting scraper/analyzer (US-912/US-913)".
- Test automatico: `cai-runts-scraper/tests/test_ported_modules.py` — `def test_classify_codice_pratica_maps_known_codes`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_ported_modules.py`.
- Test correlato: F9-42, F9-43, F9-45

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_ported_modules.py -k "test_classify_codice_pratica_maps_known_codes"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: classify_codice_pratica mappa correttamente i codici pratica noti.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-45 — extract_bilancio_pdf è una funzione sincrona con i parametri attesi

**Obiettivo**
Verificare che: extract_bilancio_pdf è una funzione sincrona con i parametri attesi.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Servizio Python cai-runts-scraper — scaffold, endpoint /health, porting scraper/analyzer (US-912/US-913)".
- Test automatico: `cai-runts-scraper/tests/test_ported_modules.py` — `def test_extract_bilancio_pdf_is_a_sync_function_with_expected_parameters`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_ported_modules.py`.
- Test correlato: F9-42, F9-43, F9-44

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_ported_modules.py -k "test_extract_bilancio_pdf_is_a_sync_function_with_expected_parameters"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: extract_bilancio_pdf è una funzione sincrona con i parametri attesi.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## POST /scrape/runts-entity — ricerca completa di un ente su RUNTS (US-914/US-915/US-916)

### F9-46 — Restituisce found:false quando la ricerca non produce risultati

**Obiettivo**
Verificare che: restituisce found:false quando la ricerca non produce risultati.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "POST /scrape/runts-entity — ricerca completa di un ente su RUNTS (US-914/US-915/US-916)".
- Test automatico: `cai-runts-scraper/tests/test_scrape_runts_entity.py` — `def test_scrape_runts_entity_returns_found_false_when_no_results`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_scrape_runts_entity.py`.
- Test correlato: F9-47, F9-48

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_scrape_runts_entity.py -k "test_scrape_runts_entity_returns_found_false_when_no_results"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Restituisce found:false quando la ricerca non produce risultati.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-47 — Restituisce metadati, cariche sociali e documenti quando l'ente è trovato

**Obiettivo**
Verificare che: restituisce metadati, cariche sociali e documenti quando l'ente è trovato.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "POST /scrape/runts-entity — ricerca completa di un ente su RUNTS (US-914/US-915/US-916)".
- Test automatico: `cai-runts-scraper/tests/test_scrape_runts_entity.py` — `def test_scrape_runts_entity_returns_metadata_board_members_and_documents`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_scrape_runts_entity.py`.
- Test correlato: F9-46, F9-48

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_scrape_runts_entity.py -k "test_scrape_runts_entity_returns_metadata_board_members_and_documents"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Restituisce metadati, cariche sociali e documenti quando l'ente è trovato.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-48 — Un errore dello scraper diventa un 502 esplicito, mai un errore generico

**Obiettivo**
Verificare che: un errore dello scraper diventa un 502 esplicito, mai un errore generico.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "POST /scrape/runts-entity — ricerca completa di un ente su RUNTS (US-914/US-915/US-916)".
- Test automatico: `cai-runts-scraper/tests/test_scrape_runts_entity.py` — `def test_scrape_runts_entity_returns_502_when_scraper_raises`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_scrape_runts_entity.py`.
- Test correlato: F9-46, F9-47

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_scrape_runts_entity.py -k "test_scrape_runts_entity_returns_502_when_scraper_raises"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Un errore dello scraper diventa un 502 esplicito, mai un errore generico.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## POST /analyze/bilancio — estrazione delle cifre finanziarie da un PDF (US-917)

### F9-49 — Restituisce i campi finanziari estratti da un bilancio leggibile

**Obiettivo**
Verificare che: restituisce i campi finanziari estratti da un bilancio leggibile.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "POST /analyze/bilancio — estrazione delle cifre finanziarie da un PDF (US-917)".
- Test automatico: `cai-runts-scraper/tests/test_analyze_bilancio.py` — `def test_analyze_bilancio_returns_financial_fields`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_analyze_bilancio.py`.
- Test correlato: F9-50

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_analyze_bilancio.py -k "test_analyze_bilancio_returns_financial_fields"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Restituisce i campi finanziari estratti da un bilancio leggibile.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-50 — Non fallisce mai, nemmeno su un PDF illeggibile (campi null invece di un errore)

**Obiettivo**
Verificare che: non fallisce mai, nemmeno su un PDF illeggibile (campi null invece di un errore).

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "POST /analyze/bilancio — estrazione delle cifre finanziarie da un PDF (US-917)".
- Test automatico: `cai-runts-scraper/tests/test_analyze_bilancio.py` — `def test_analyze_bilancio_never_fails_even_on_unreadable_pdf`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_analyze_bilancio.py`.
- Test correlato: F9-49

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_analyze_bilancio.py -k "test_analyze_bilancio_never_fails_even_on_unreadable_pdf"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Non fallisce mai, nemmeno su un PDF illeggibile (campi null invece di un errore).

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## Analizzatore bilanci — correzioni dei pattern su layout reali RUNTS

### F9-51 — Il totale oneri combacia anche a cavallo di un a-capo, su un layout a due colonne

**Obiettivo**
Verificare che: il totale oneri combacia anche a cavallo di un a-capo, su un layout a due colonne.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Analizzatore bilanci — correzioni dei pattern su layout reali RUNTS".
- Test automatico: `cai-runts-scraper/tests/test_analyzer_patterns.py` — `def test_totale_oneri_matches_across_the_line_break_in_a_two_column_layout`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_analyzer_patterns.py`.
- Test correlato: F9-52, F9-53, F9-54, F9-55, F9-56

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_analyzer_patterns.py -k "test_totale_oneri_matches_across_the_line_break_in_a_two_column_layout"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il totale oneri combacia anche a cavallo di un a-capo, su un layout a due colonne.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-52 — Il totale proventi combacia sul terzo numero della riga valore

**Obiettivo**
Verificare che: il totale proventi combacia sul terzo numero della riga valore.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Analizzatore bilanci — correzioni dei pattern su layout reali RUNTS".
- Test automatico: `cai-runts-scraper/tests/test_analyzer_patterns.py` — `def test_totale_proventi_matches_the_third_number_on_the_value_line`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_analyzer_patterns.py`.
- Test correlato: F9-51, F9-53, F9-54, F9-55, F9-56

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_analyzer_patterns.py -k "test_totale_proventi_matches_the_third_number_on_the_value_line"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il totale proventi combacia sul terzo numero della riga valore.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-53 — Le imposte combaciano quando un simbolo di euro separa l'etichetta dal valore

**Obiettivo**
Verificare che: le imposte combaciano quando un simbolo di euro separa l'etichetta dal valore.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Analizzatore bilanci — correzioni dei pattern su layout reali RUNTS".
- Test automatico: `cai-runts-scraper/tests/test_analyzer_patterns.py` — `def test_imposte_matches_when_a_euro_sign_separates_the_label_from_the_value`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_analyzer_patterns.py`.
- Test correlato: F9-51, F9-52, F9-54, F9-55, F9-56

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_analyzer_patterns.py -k "test_imposte_matches_when_a_euro_sign_separates_the_label_from_the_value"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Le imposte combaciano quando un simbolo di euro separa l'etichetta dal valore.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-54 — I campi già riconosciuti prima di questo fix continuano a combaciare (nessuna regressione)

**Obiettivo**
Verificare che: i campi già riconosciuti prima di questo fix continuano a combaciare (nessuna regressione).

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Analizzatore bilanci — correzioni dei pattern su layout reali RUNTS".
- Test automatico: `cai-runts-scraper/tests/test_analyzer_patterns.py` — `def test_risultato_fields_already_matched_before_this_fix_and_still_do`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_analyzer_patterns.py`.
- Test correlato: F9-51, F9-52, F9-53, F9-55, F9-56

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_analyzer_patterns.py -k "test_risultato_fields_already_matched_before_this_fix_and_still_do"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: I campi già riconosciuti prima di questo fix continuano a combaciare (nessuna regressione).

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-55 — Il risultato d'esercizio riconosce un trattino come valore zero dell'anno corrente, invece di saltare all'anno successivo

**Obiettivo**
Verificare che: il risultato d'esercizio riconosce un trattino come valore zero dell'anno corrente, invece di saltare all'anno successivo.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Analizzatore bilanci — correzioni dei pattern su layout reali RUNTS".
- Test automatico: `cai-runts-scraper/tests/test_analyzer_patterns.py` — `def test_risultato_esercizio_recognizes_a_dash_as_the_current_year_value_instead_of_skipping_to_the_next_year`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_analyzer_patterns.py`.
- Test correlato: F9-51, F9-52, F9-53, F9-54, F9-56

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_analyzer_patterns.py -k "test_risultato_esercizio_recognizes_a_dash_as_the_current_year_value_instead_of_skipping_to_the_next_year"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il risultato d'esercizio riconosce un trattino come valore zero dell'anno corrente, invece di saltare all'anno successivo.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-56 — parse_italian_number tratta un trattino isolato come zero

**Obiettivo**
Verificare che: parse_italian_number tratta un trattino isolato come zero.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Analizzatore bilanci — correzioni dei pattern su layout reali RUNTS".
- Test automatico: `cai-runts-scraper/tests/test_analyzer_patterns.py` — `def test_parse_italian_number_treats_a_lone_dash_as_zero`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_analyzer_patterns.py`.
- Test correlato: F9-51, F9-52, F9-53, F9-54, F9-55

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_analyzer_patterns.py -k "test_parse_italian_number_treats_a_lone_dash_as_zero"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: parse_italian_number tratta un trattino isolato come zero.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## Wiring docker-compose + verifica reale contro RUNTS (US-918)

### F9-57 — Il servizio cai-runts-scraper è raggiungibile via rete Docker Compose interna (verifica manuale già eseguita in sviluppo, vedi progress.txt)

**Obiettivo**
Verificare che: il servizio cai-runts-scraper è raggiungibile via rete Docker Compose interna (verifica manuale già eseguita in sviluppo, vedi progress.txt).

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Wiring docker-compose + verifica reale contro RUNTS (US-918)".
- Test automatico: `cai-runts-scraper/tests/test_main.py` — `def test_health_returns_ok`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_main.py`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_main.py -k "test_health_returns_ok"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il servizio cai-runts-scraper è raggiungibile via rete Docker Compose interna (verifica manuale già eseguita in sviluppo, vedi progress.txt).

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## CaiRuntsRegistrationFieldMapper / CaiFinancialStatementFieldMapper condivisi (US-920/US-921)

### F9-58 — mapRegistration mappa una riga sugli attributi di CaiRuntsRegistration

**Obiettivo**
Verificare che: mapRegistration mappa una riga sugli attributi di CaiRuntsRegistration.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiRuntsRegistrationFieldMapper / CaiFinancialStatementFieldMapper condivisi (US-920/US-921)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php` — `mapRegistration maps a row to CaiRuntsRegistration attributes`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php`.
- Test correlato: F9-59, F9-60, F9-61, F9-62

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "mapRegistration maps a row to CaiRuntsRegistration attributes"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: mapRegistration mappa una riga sugli attributi di CaiRuntsRegistration.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-59 — mapRegistration legge lat/lon quando presenti (fonte datapack)

**Obiettivo**
Verificare che: mapRegistration legge lat/lon quando presenti (fonte datapack).

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiRuntsRegistrationFieldMapper / CaiFinancialStatementFieldMapper condivisi (US-920/US-921)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php` — `mapRegistration reads lat/lon when present (datapack source)`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php`.
- Test correlato: F9-58, F9-60, F9-61, F9-62

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "mapRegistration reads lat/lon when present (datapack source)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: mapRegistration legge lat/lon quando presenti (fonte datapack).

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-60 — mapBoardMember concatena nome+cognome in full_name e interpreta le date

**Obiettivo**
Verificare che: mapBoardMember concatena nome+cognome in full_name e interpreta le date.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiRuntsRegistrationFieldMapper / CaiFinancialStatementFieldMapper condivisi (US-920/US-921)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php` — `mapBoardMember concatenates nome+cognome into full_name and parses dates`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php`.
- Test correlato: F9-58, F9-59, F9-61, F9-62

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "mapBoardMember concatenates nome+cognome into full_name and parses dates"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: mapBoardMember concatena nome+cognome in full_name e interpreta le date.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-61 — mapBoardMember tollera nome/cognome mancanti, producendo un full_name null

**Obiettivo**
Verificare che: mapBoardMember tollera nome/cognome mancanti, producendo un full_name null.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiRuntsRegistrationFieldMapper / CaiFinancialStatementFieldMapper condivisi (US-920/US-921)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php` — `mapBoardMember tolerates missing nome/cognome and produces a null full_name`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php`.
- Test correlato: F9-58, F9-59, F9-60, F9-62

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "mapBoardMember tolerates missing nome/cognome and produces a null full_name"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: mapBoardMember tollera nome/cognome mancanti, producendo un full_name null.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-62 — mapFinancialStatement mappa i 15 campi dell'analizzatore (nomi italiani) sulle colonne di CaiFinancialStatement

**Obiettivo**
Verificare che: mapFinancialStatement mappa i 15 campi dell'analizzatore (nomi italiani) sulle colonne di CaiFinancialStatement.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiRuntsRegistrationFieldMapper / CaiFinancialStatementFieldMapper condivisi (US-920/US-921)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiFinancialStatementFieldMapperTest.php` — `mapFinancialStatement maps the 15 Italian-named analyzer fields to CaiFinancialStatement columns`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiFinancialStatementFieldMapperTest.php`.
- Test correlato: F9-58, F9-59, F9-60, F9-61

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "mapFinancialStatement maps the 15 Italian-named analyzer fields to CaiFinancialStatement columns"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: mapFinancialStatement mappa i 15 campi dell'analizzatore (nomi italiani) sulle colonne di CaiFinancialStatement.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## CaiRuntsScraperClient + configurazione (US-922)

### F9-63 — scrapeEntity invia codice_fiscale come query parameter e restituisce il JSON decodificato

**Obiettivo**
Verificare che: scrapeEntity invia codice_fiscale come query parameter e restituisce il JSON decodificato.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiRuntsScraperClient + configurazione (US-922)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php` — `scrapeEntity sends codice_fiscale as a query parameter and returns the decoded JSON`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php`.
- Test correlato: F9-64, F9-65, F9-66, F9-67

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "scrapeEntity sends codice_fiscale as a query parameter and returns the decoded JSON"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: scrapeEntity invia codice_fiscale come query parameter e restituisce il JSON decodificato.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-64 — analyzeBilancio invia il PDF come file multipart e restituisce il JSON decodificato

**Obiettivo**
Verificare che: analyzeBilancio invia il PDF come file multipart e restituisce il JSON decodificato.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiRuntsScraperClient + configurazione (US-922)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php` — `analyzeBilancio sends the PDF as a multipart file upload and returns the decoded JSON`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php`.
- Test correlato: F9-63, F9-65, F9-66, F9-67

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "analyzeBilancio sends the PDF as a multipart file upload and returns the decoded JSON"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: analyzeBilancio invia il PDF come file multipart e restituisce il JSON decodificato.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-65 — checkEntityExists invia codice_fiscale come query parameter e restituisce il flag found

**Obiettivo**
Verificare che: checkEntityExists invia codice_fiscale come query parameter e restituisce il flag found.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiRuntsScraperClient + configurazione (US-922)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php` — `checkEntityExists sends codice_fiscale as a query parameter and returns the found flag`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php`.
- Test correlato: F9-63, F9-64, F9-66, F9-67

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "checkEntityExists sends codice_fiscale as a query parameter and returns the found flag"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: checkEntityExists invia codice_fiscale come query parameter e restituisce il flag found.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-66 — checkEntityExists restituisce false quando il servizio riporta found:false

**Obiettivo**
Verificare che: checkEntityExists restituisce false quando il servizio riporta found:false.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiRuntsScraperClient + configurazione (US-922)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php` — `checkEntityExists returns false when the service reports found: false`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php`.
- Test correlato: F9-63, F9-64, F9-65, F9-67

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "checkEntityExists returns false when the service reports found: false"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: checkEntityExists restituisce false quando il servizio riporta found:false.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-67 — checkEntityExists accetta un timeout esplicito, altrimenti usa quello di configurazione

**Obiettivo**
Verificare che: checkEntityExists accetta un timeout esplicito, altrimenti usa quello di configurazione.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "CaiRuntsScraperClient + configurazione (US-922)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php` — `checkEntityExists accepts an explicit timeout override, falling back to config when omitted`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php`.
- Test correlato: F9-63, F9-64, F9-65, F9-66

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "checkEntityExists accepts an explicit timeout override, falling back to config when omitted"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: checkEntityExists accetta un timeout esplicito, altrimenti usa quello di configurazione.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## SyncCaiRuntsRegistration — metadati, cariche sociali e documenti di bilancio (US-923/US-924/US-925)

### F9-68 — run restituisce un esito "non trovato" e non scrive nulla quando lo scraper riporta found:false

**Obiettivo**
Verificare che: run restituisce un esito "non trovato" e non scrive nulla quando lo scraper riporta found:false.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "SyncCaiRuntsRegistration — metadati, cariche sociali e documenti di bilancio (US-923/US-924/US-925)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php` — `run returns a not-found result and writes nothing when the scraper reports found: false`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php`.
- Test correlato: F9-69, F9-70, F9-71, F9-72, F9-73

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run returns a not-found result and writes nothing when the scraper reports found: false"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run restituisce un esito "non trovato" e non scrive nulla quando lo scraper riporta found:false.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-69 — run restituisce "non trovato" senza chiamare lo scraper quando la sezione non ha un codice fiscale

**Obiettivo**
Verificare che: run restituisce "non trovato" senza chiamare lo scraper quando la sezione non ha un codice fiscale.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "SyncCaiRuntsRegistration — metadati, cariche sociali e documenti di bilancio (US-923/US-924/US-925)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php` — `run returns a not-found result without calling the scraper when the section has no tax_code`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php`.
- Test correlato: F9-68, F9-70, F9-71, F9-72, F9-73

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run returns a not-found result without calling the scraper when the section has no tax_code"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run restituisce "non trovato" senza chiamare lo scraper quando la sezione non ha un codice fiscale.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-70 — run crea una nuova CaiRuntsRegistration e le sue cariche sociali, aggiornando runts_last_synced_at

**Obiettivo**
Verificare che: run crea una nuova CaiRuntsRegistration e le sue cariche sociali, aggiornando runts_last_synced_at.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "SyncCaiRuntsRegistration — metadati, cariche sociali e documenti di bilancio (US-923/US-924/US-925)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php` — `run creates a new CaiRuntsRegistration and its board members, and bumps runts_last_synced_at`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php`.
- Test correlato: F9-68, F9-69, F9-71, F9-72, F9-73

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run creates a new CaiRuntsRegistration and its board members, and bumps runts_last_synced_at"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run crea una nuova CaiRuntsRegistration e le sue cariche sociali, aggiornando runts_last_synced_at.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-71 — run aggiorna una CaiRuntsRegistration esistente, aggiornando sempre runts_last_synced_at

**Obiettivo**
Verificare che: run aggiorna una CaiRuntsRegistration esistente, aggiornando sempre runts_last_synced_at.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "SyncCaiRuntsRegistration — metadati, cariche sociali e documenti di bilancio (US-923/US-924/US-925)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php` — `run updates an existing CaiRuntsRegistration and always bumps runts_last_synced_at`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php`.
- Test correlato: F9-68, F9-69, F9-70, F9-72, F9-73

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run updates an existing CaiRuntsRegistration and always bumps runts_last_synced_at"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run aggiorna una CaiRuntsRegistration esistente, aggiornando sempre runts_last_synced_at.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-72 — run scarica e salva un nuovo documento, inviando all'analisi solo i bilanci di esercizio

**Obiettivo**
Verificare che: run scarica e salva un nuovo documento, inviando all'analisi solo i bilanci di esercizio.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "SyncCaiRuntsRegistration — metadati, cariche sociali e documenti di bilancio (US-923/US-924/US-925)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php` — `run downloads and stores a new document, and dispatches analysis only for bilancio_esercizio`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php`.
- Test correlato: F9-68, F9-69, F9-70, F9-71, F9-73

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run downloads and stores a new document, and dispatches analysis only for bilancio_esercizio"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run scarica e salva un nuovo documento, inviando all'analisi solo i bilanci di esercizio.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-73 — run non riscarica né rimette in coda un documento già esistente

**Obiettivo**
Verificare che: run non riscarica né rimette in coda un documento già esistente.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "SyncCaiRuntsRegistration — metadati, cariche sociali e documenti di bilancio (US-923/US-924/US-925)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php` — `run does not re-download or re-queue a document that already exists`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php`.
- Test correlato: F9-68, F9-69, F9-70, F9-71, F9-72

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run does not re-download or re-queue a document that already exists"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: run non riscarica né rimette in coda un documento già esistente.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## AnalyzeCaiFinancialStatementDocument — job asincrono di analisi bilanci su coda Horizon dedicata (US-924)

### F9-74 — handle scarica il PDF salvato, lo analizza e crea un CaiFinancialStatement

**Obiettivo**
Verificare che: handle scarica il PDF salvato, lo analizza e crea un CaiFinancialStatement.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "AnalyzeCaiFinancialStatementDocument — job asincrono di analisi bilanci su coda Horizon dedicata (US-924)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php` — `handle downloads the stored PDF, analyzes it, and creates a CaiFinancialStatement`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php`.
- Test correlato: F9-75, F9-76, F9-77, F9-78, F9-79, F9-80

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "handle downloads the stored PDF, analyzes it, and creates a CaiFinancialStatement"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: handle scarica il PDF salvato, lo analizza e crea un CaiFinancialStatement.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-75 — handle aggiorna un CaiFinancialStatement esistente per la stessa (registrazione, anno)

**Obiettivo**
Verificare che: handle aggiorna un CaiFinancialStatement esistente per la stessa (registrazione, anno).

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "AnalyzeCaiFinancialStatementDocument — job asincrono di analisi bilanci su coda Horizon dedicata (US-924)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php` — `handle updates an existing CaiFinancialStatement for the same registration+year`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php`.
- Test correlato: F9-74, F9-76, F9-77, F9-78, F9-79, F9-80

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "handle updates an existing CaiFinancialStatement for the same registration+year"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: handle aggiorna un CaiFinancialStatement esistente per la stessa (registrazione, anno).

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-76 — handle non sovrascrive mai un campo già valorizzato con null (un secondo documento con struttura diversa non deve cancellare dati buoni)

**Obiettivo**
Verificare che: handle non sovrascrive mai un campo già valorizzato con null (un secondo documento con struttura diversa non deve cancellare dati buoni).

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "AnalyzeCaiFinancialStatementDocument — job asincrono di analisi bilanci su coda Horizon dedicata (US-924)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php` — `handle never overwrites an already-populated field with null (a second, structurally different document for the same year must not clobber good data)`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php`.
- Test correlato: F9-74, F9-75, F9-77, F9-78, F9-79, F9-80

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "handle never overwrites an already-populated field with null (a second, structurally different document for the same year must not clobber good data)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: handle non sovrascrive mai un campo già valorizzato con null (un secondo documento con struttura diversa non deve cancellare dati buoni).

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-77 — handle non fa nulla quando il CaiDocument non esiste più

**Obiettivo**
Verificare che: handle non fa nulla quando il CaiDocument non esiste più.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "AnalyzeCaiFinancialStatementDocument — job asincrono di analisi bilanci su coda Horizon dedicata (US-924)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php` — `handle does nothing when the CaiDocument no longer exists`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php`.
- Test correlato: F9-74, F9-75, F9-76, F9-78, F9-79, F9-80

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "handle does nothing when the CaiDocument no longer exists"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: handle non fa nulla quando il CaiDocument non esiste più.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-78 — handle non fa nulla quando il documento non ha un anno estraibile

**Obiettivo**
Verificare che: handle non fa nulla quando il documento non ha un anno estraibile.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "AnalyzeCaiFinancialStatementDocument — job asincrono di analisi bilanci su coda Horizon dedicata (US-924)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php` — `handle does nothing when the document has no extractable year`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php`.
- Test correlato: F9-74, F9-75, F9-76, F9-77, F9-79, F9-80

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "handle does nothing when the document has no extractable year"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: handle non fa nulla quando il documento non ha un anno estraibile.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-79 — handle marca il documento come Extracted, con estratto testo ed esito OCR, quando trova almeno un campo

**Obiettivo**
Verificare che: handle marca il documento come Extracted, con estratto testo ed esito OCR, quando trova almeno un campo.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "AnalyzeCaiFinancialStatementDocument — job asincrono di analisi bilanci su coda Horizon dedicata (US-924)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php` — `handle marks the document itself as Extracted with the raw text excerpt and OCR flag when at least one field is found`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php`.
- Test correlato: F9-74, F9-75, F9-76, F9-77, F9-78, F9-80

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "handle marks the document itself as Extracted with the raw text excerpt and OCR flag when at least one field is found"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: handle marca il documento come Extracted, con estratto testo ed esito OCR, quando trova almeno un campo.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-80 — handle marca il documento come NoDataExtracted quando nessun campo finanziario viene estratto

**Obiettivo**
Verificare che: handle marca il documento come NoDataExtracted quando nessun campo finanziario viene estratto.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "AnalyzeCaiFinancialStatementDocument — job asincrono di analisi bilanci su coda Horizon dedicata (US-924)".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php` — `handle marks the document as NoDataExtracted when every financial field comes back null`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php`.
- Test correlato: F9-74, F9-75, F9-76, F9-77, F9-78, F9-79

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "handle marks the document as NoDataExtracted when every financial field comes back null"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: handle marca il documento come NoDataExtracted quando nessun campo finanziario viene estratto.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## Bottone "Sincronizza dati RUNTS", separato da "Sincronizza dati CAI" (US-926, Storie 6/8)

### F9-81 — Il bottone è visibile solo per un cliente Sezione con una CaiSection collegata

**Obiettivo**
Verificare che: il bottone è visibile solo per un cliente Sezione con una CaiSection collegata.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Bottone "Sincronizza dati RUNTS", separato da "Sincronizza dati CAI" (US-926, Storie 6/8)".
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the sync runts data action is visible only for a sezione customer with a linked cai section`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Pages/CustomerDashboardTest.php`.
- Test correlato: F9-82, F9-83

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer (Sezione)

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Il bottone è visibile solo per un cliente Sezione con una CaiSection collegata |

**Risultato finale atteso**
Il bottone è visibile solo per un cliente Sezione con una CaiSection collegata.

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

### F9-82 — Il bottone sincronizza dal vivo la sezione del cliente tramite il servizio cai-runts-scraper

**Obiettivo**
Verificare che: il bottone sincronizza dal vivo la sezione del cliente tramite il servizio cai-runts-scraper.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Bottone "Sincronizza dati RUNTS", separato da "Sincronizza dati CAI" (US-926, Storie 6/8)".
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the sync runts data action live-scrapes the current customer's section via the cai-runts-scraper service`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Pages/CustomerDashboardTest.php`.
- Test correlato: F9-81, F9-83

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Il bottone sincronizza dal vivo la sezione del cliente tramite il servizio cai-runts-scraper |

**Risultato finale atteso**
Il bottone sincronizza dal vivo la sezione del cliente tramite il servizio cai-runts-scraper.

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

### F9-83 — Il bottone mostra una notifica informativa quando non viene trovata alcuna registrazione RUNTS

**Obiettivo**
Verificare che: il bottone mostra una notifica informativa quando non viene trovata alcuna registrazione RUNTS.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Bottone "Sincronizza dati RUNTS", separato da "Sincronizza dati CAI" (US-926, Storie 6/8)".
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the sync runts data action shows an informative notification when no RUNTS registration is found`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Pages/CustomerDashboardTest.php`.
- Test correlato: F9-81, F9-82

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Il bottone mostra una notifica informativa quando non viene trovata alcuna registrazione RUNTS |

**Risultato finale atteso**
Il bottone mostra una notifica informativa quando non viene trovata alcuna registrazione RUNTS.

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

## Tab "Differenze" — confronto fra dati CaiSection e registrazione RUNTS (Storia 7)

### F9-84 — Nessuna riga quando la sezione non ha una registrazione RUNTS collegata

**Obiettivo**
Verificare che: nessuna riga quando la sezione non ha una registrazione RUNTS collegata.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Tab "Differenze" — confronto fra dati CaiSection e registrazione RUNTS (Storia 7)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php` — `returns no rows when the section has no linked runts registration`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php`.
- Test correlato: F9-85, F9-86, F9-87, F9-88, F9-89, F9-90, F9-91

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "returns no rows when the section has no linked runts registration"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Nessuna riga quando la sezione non ha una registrazione RUNTS collegata.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-85 — Un campo è marcato "Uguale" quando i due lati coincidono (a meno di spazi)

**Obiettivo**
Verificare che: un campo è marcato "Uguale" quando i due lati coincidono (a meno di spazi).

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Tab "Differenze" — confronto fra dati CaiSection e registrazione RUNTS (Storia 7)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php` — `flags a field as equal when both sides carry the same trimmed value`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php`.
- Test correlato: F9-84, F9-86, F9-87, F9-88, F9-89, F9-90, F9-91

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "flags a field as equal when both sides carry the same trimmed value"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Un campo è marcato "Uguale" quando i due lati coincidono (a meno di spazi).

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-86 — Un campo è marcato "Diverso" quando i due lati differiscono davvero

**Obiettivo**
Verificare che: un campo è marcato "Diverso" quando i due lati differiscono davvero.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Tab "Differenze" — confronto fra dati CaiSection e registrazione RUNTS (Storia 7)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php` — `flags a field as different when the two sides genuinely differ`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php`.
- Test correlato: F9-84, F9-85, F9-87, F9-88, F9-89, F9-90, F9-91

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "flags a field as different when the two sides genuinely differ"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Un campo è marcato "Diverso" quando i due lati differiscono davvero.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-87 — Un campo è marcato "non applicabile" quando entrambi i lati sono vuoti

**Obiettivo**
Verificare che: un campo è marcato "non applicabile" quando entrambi i lati sono vuoti.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Tab "Differenze" — confronto fra dati CaiSection e registrazione RUNTS (Storia 7)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php` — `flags a field as not applicable when both sides are empty`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php`.
- Test correlato: F9-84, F9-85, F9-86, F9-88, F9-89, F9-90, F9-91

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "flags a field as not applicable when both sides are empty"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Un campo è marcato "non applicabile" quando entrambi i lati sono vuoti.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-88 — Un blocco di righe per ogni registrazione collegata, etichettato col nome della registrazione

**Obiettivo**
Verificare che: un blocco di righe per ogni registrazione collegata, etichettato col nome della registrazione.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Tab "Differenze" — confronto fra dati CaiSection e registrazione RUNTS (Storia 7)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php` — `produces one block of rows per linked registration, labelled by registration name`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php`.
- Test correlato: F9-84, F9-85, F9-86, F9-87, F9-89, F9-90, F9-91

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "produces one block of rows per linked registration, labelled by registration name"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Un blocco di righe per ogni registrazione collegata, etichettato col nome della registrazione.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-89 — L'indirizzo strutturato RUNTS viene composto in un'unica stringa comparabile

**Obiettivo**
Verificare che: l'indirizzo strutturato RUNTS viene composto in un'unica stringa comparabile.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Tab "Differenze" — confronto fra dati CaiSection e registrazione RUNTS (Storia 7)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php` — `composes the RUNTS structured address into a single comparable string`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Support/CaiSectionRuntsComparatorTest.php`.
- Test correlato: F9-84, F9-85, F9-86, F9-87, F9-88, F9-90, F9-91

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "composes the RUNTS structured address into a single comparable string"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: L'indirizzo strutturato RUNTS viene composto in un'unica stringa comparabile.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-90 — Il tab Differenze mostra il confronto fra CaiSection e la sua registrazione RUNTS

**Obiettivo**
Verificare che: il tab Differenze mostra il confronto fra CaiSection e la sua registrazione RUNTS.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Tab "Differenze" — confronto fra dati CaiSection e registrazione RUNTS (Storia 7)".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `the differences tab shows the comparison between CaiSection and its RUNTS registration`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: F9-84, F9-85, F9-86, F9-87, F9-88, F9-89, F9-91

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Il tab Differenze mostra il confronto fra CaiSection e la sua registrazione RUNTS |

**Risultato finale atteso**
Il tab Differenze mostra il confronto fra CaiSection e la sua registrazione RUNTS.

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

### F9-91 — Il tab Differenze mostra uno stato vuoto esplicito quando non c'è alcuna registrazione RUNTS collegata

**Obiettivo**
Verificare che: il tab Differenze mostra uno stato vuoto esplicito quando non c'è alcuna registrazione RUNTS collegata.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Tab "Differenze" — confronto fra dati CaiSection e registrazione RUNTS (Storia 7)".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `the differences tab shows an explicit empty state when no RUNTS registration is linked`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: F9-84, F9-85, F9-86, F9-87, F9-88, F9-89, F9-90

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Il tab Differenze mostra uno stato vuoto esplicito quando non c'è alcuna registrazione RUNTS collegata |

**Risultato finale atteso**
Il tab Differenze mostra uno stato vuoto esplicito quando non c'è alcuna registrazione RUNTS collegata.

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

## Link diretto alla scheda ufficiale CAI, indipendente dal campo website (Storia 5)

### F9-92 — Il tab Anagrafica CAI collega alla scheda ufficiale su cai.it, costruita dal codice_cai, a prescindere dal campo website

**Obiettivo**
Verificare che: il tab Anagrafica CAI collega alla scheda ufficiale su cai.it, costruita dal codice_cai, a prescindere dal campo website.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Link diretto alla scheda ufficiale CAI, indipendente dal campo website (Storia 5)".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `the CAI directory tab links to the official cai.it section page, built from codice_cai, regardless of the website field`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Il tab Anagrafica CAI collega alla scheda ufficiale su cai.it, costruita dal codice_cai, a prescindere dal campo website |

**Risultato finale atteso**
Il tab Anagrafica CAI collega alla scheda ufficiale su cai.it, costruita dal codice_cai, a prescindere dal campo website.

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

## Orari di apertura e avvisi mostrati come testo formattato, mai HTML grezzo (fix, Storia 3)

### F9-93 — Lo stile inline viene rimosso mantenendo il testo di uno span

**Obiettivo**
Verificare che: lo stile inline viene rimosso mantenendo il testo di uno span.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Orari di apertura e avvisi mostrati come testo formattato, mai HTML grezzo (fix, Storia 3)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Support/CaiRichTextSanitizerTest.php` — `strips inline styling but keeps the text content of a span`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Support/CaiRichTextSanitizerTest.php`.
- Test correlato: F9-94, F9-95, F9-96

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "strips inline styling but keeps the text content of a span"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Lo stile inline viene rimosso mantenendo il testo di uno span.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-94 — Un tag script e il suo contenuto vengono rimossi interamente

**Obiettivo**
Verificare che: un tag script e il suo contenuto vengono rimossi interamente.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Orari di apertura e avvisi mostrati come testo formattato, mai HTML grezzo (fix, Storia 3)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Support/CaiRichTextSanitizerTest.php` — `strips a script tag and its content entirely`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Support/CaiRichTextSanitizerTest.php`.
- Test correlato: F9-93, F9-95, F9-96

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "strips a script tag and its content entirely"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Un tag script e il suo contenuto vengono rimossi interamente.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-95 — Gli attributi event handler vengono rimossi

**Obiettivo**
Verificare che: gli attributi event handler vengono rimossi.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Orari di apertura e avvisi mostrati come testo formattato, mai HTML grezzo (fix, Storia 3)".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Support/CaiRichTextSanitizerTest.php` — `strips event handler attributes`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Support/CaiRichTextSanitizerTest.php`.
- Test correlato: F9-93, F9-94, F9-96

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "strips event handler attributes"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Gli attributi event handler vengono rimossi.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-96 — Orari di apertura e avvisi sono mostrati come testo formattato, mai come sorgente HTML grezzo

**Obiettivo**
Verificare che: orari di apertura e avvisi sono mostrati come testo formattato, mai come sorgente HTML grezzo.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Orari di apertura e avvisi mostrati come testo formattato, mai HTML grezzo (fix, Storia 3)".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `office hours and notices are shown as formatted text, not raw HTML source`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: F9-93, F9-94, F9-95

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Orari di apertura e avvisi sono mostrati come testo formattato, mai come sorgente HTML grezzo |

**Risultato finale atteso**
Orari di apertura e avvisi sono mostrati come testo formattato, mai come sorgente HTML grezzo.

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

## Bilanci e allegati ordinati dal più recente al più antico (Storie 10/11)

### F9-97 — Il tab Bilanci elenca gli anni dal più recente al più antico

**Obiettivo**
Verificare che: il tab Bilanci elenca gli anni dal più recente al più antico.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Bilanci e allegati ordinati dal più recente al più antico (Storie 10/11)".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `the financial statements tab lists years from most recent to oldest`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: F9-98

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Il tab Bilanci elenca gli anni dal più recente al più antico |

**Risultato finale atteso**
Il tab Bilanci elenca gli anni dal più recente al più antico.

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

### F9-98 — Il tab Allegati elenca i documenti dall'anno più recente al più antico

**Obiettivo**
Verificare che: il tab Allegati elenca i documenti dall'anno più recente al più antico.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Bilanci e allegati ordinati dal più recente al più antico (Storie 10/11)".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `the documents tab lists attachments from most recent to oldest year`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: F9-97

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Il tab Allegati elenca i documenti dall'anno più recente al più antico |

**Risultato finale atteso**
Il tab Allegati elenca i documenti dall'anno più recente al più antico.

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

## Verifica leggera di presenza RUNTS — cai:check-runts-presence dietro feature flag

### F9-99 — L'opzione --timeout ha un default di 10 secondi

**Obiettivo**
Verificare che: l'opzione --timeout ha un default di 10 secondi.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Verifica leggera di presenza RUNTS — cai:check-runts-presence dietro feature flag".
- Test automatico: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php` — `the --timeout option defaults to 10 seconds`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php`.
- Test correlato: F9-100, F9-101, F9-102, F9-103, F9-104, F9-105, F9-106, F9-107, F9-108, F9-109, F9-110

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the --timeout option defaults to 10 seconds"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: L'opzione --timeout ha un default di 10 secondi.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-100 — Un --timeout personalizzato è accettato senza rompere un giro normale

**Obiettivo**
Verificare che: un --timeout personalizzato è accettato senza rompere un giro normale.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Verifica leggera di presenza RUNTS — cai:check-runts-presence dietro feature flag".
- Test automatico: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php` — `a custom --timeout is accepted without breaking a normal run`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php`.
- Test correlato: F9-99, F9-101, F9-102, F9-103, F9-104, F9-105, F9-106, F9-107, F9-108, F9-109, F9-110

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a custom --timeout is accepted without breaking a normal run"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Un --timeout personalizzato è accettato senza rompere un giro normale.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-101 — Il comando scrive runts_presence_status e runts_presence_checked_at per ogni sezione con codice fiscale

**Obiettivo**
Verificare che: il comando scrive runts_presence_status e runts_presence_checked_at per ogni sezione con codice fiscale.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Verifica leggera di presenza RUNTS — cai:check-runts-presence dietro feature flag".
- Test automatico: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php` — `cai:check-runts-presence writes runts_presence_status and runts_presence_checked_at for every section with a tax_code`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php`.
- Test correlato: F9-99, F9-100, F9-102, F9-103, F9-104, F9-105, F9-106, F9-107, F9-108, F9-109, F9-110

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:check-runts-presence writes runts_presence_status and runts_presence_checked_at for every section with a tax_code"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando scrive runts_presence_status e runts_presence_checked_at per ogni sezione con codice fiscale.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-102 — Un timeout della verifica scrive lo stato Timeout (non null), invece di lasciarlo non scritto

**Obiettivo**
Verificare che: un timeout della verifica scrive lo stato Timeout (non null), invece di lasciarlo non scritto.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Verifica leggera di presenza RUNTS — cai:check-runts-presence dietro feature flag".
- Test automatico: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php` — `cai:check-runts-presence writes a Timeout status (not null) when the check times out, instead of leaving it unwritten`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php`.
- Test correlato: F9-99, F9-100, F9-101, F9-103, F9-104, F9-105, F9-106, F9-107, F9-108, F9-109, F9-110

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:check-runts-presence writes a Timeout status (not null) when the check times out, instead of leaving it unwritten"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Un timeout della verifica scrive lo stato Timeout (non null), invece di lasciarlo non scritto.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-103 — --dry-run non scrive nulla, nemmeno in caso di timeout

**Obiettivo**
Verificare che: --dry-run non scrive nulla, nemmeno in caso di timeout.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Verifica leggera di presenza RUNTS — cai:check-runts-presence dietro feature flag".
- Test automatico: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php` — `cai:check-runts-presence --dry-run does not write anything, not even on timeout`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php`.
- Test correlato: F9-99, F9-100, F9-101, F9-102, F9-104, F9-105, F9-106, F9-107, F9-108, F9-109, F9-110

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:check-runts-presence --dry-run does not write anything, not even on timeout"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: --dry-run non scrive nulla, nemmeno in caso di timeout.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-104 — Il comando prosegue oltre una sezione la cui verifica fallisce con un errore diverso dal timeout, lasciandone invariato lo stato

**Obiettivo**
Verificare che: il comando prosegue oltre una sezione la cui verifica fallisce con un errore diverso dal timeout, lasciandone invariato lo stato.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Verifica leggera di presenza RUNTS — cai:check-runts-presence dietro feature flag".
- Test automatico: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php` — `cai:check-runts-presence continues past a section whose check fails with a non-timeout error, leaving its status untouched`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php`.
- Test correlato: F9-99, F9-100, F9-101, F9-102, F9-103, F9-105, F9-106, F9-107, F9-108, F9-109, F9-110

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:check-runts-presence continues past a section whose check fails with a non-timeout error, leaving its status untouched"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando prosegue oltre una sezione la cui verifica fallisce con un errore diverso dal timeout, lasciandone invariato lo stato.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-105 — Il comando salta le sezioni senza codice fiscale né partita IVA, senza mai chiamare il servizio per loro

**Obiettivo**
Verificare che: il comando salta le sezioni senza codice fiscale né partita IVA, senza mai chiamare il servizio per loro.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Verifica leggera di presenza RUNTS — cai:check-runts-presence dietro feature flag".
- Test automatico: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php` — `cai:check-runts-presence skips sections with neither tax_code nor vat_number, never calling the service for them`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php`.
- Test correlato: F9-99, F9-100, F9-101, F9-102, F9-103, F9-104, F9-106, F9-107, F9-108, F9-109, F9-110

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:check-runts-presence skips sections with neither tax_code nor vat_number, never calling the service for them"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando salta le sezioni senza codice fiscale né partita IVA, senza mai chiamare il servizio per loro.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-106 — Il comando usa la partita IVA quando il codice fiscale manca

**Obiettivo**
Verificare che: il comando usa la partita IVA quando il codice fiscale manca.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Verifica leggera di presenza RUNTS — cai:check-runts-presence dietro feature flag".
- Test automatico: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php` — `cai:check-runts-presence falls back to vat_number when tax_code is missing`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php`.
- Test correlato: F9-99, F9-100, F9-101, F9-102, F9-103, F9-104, F9-105, F9-107, F9-108, F9-109, F9-110

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:check-runts-presence falls back to vat_number when tax_code is missing"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando usa la partita IVA quando il codice fiscale manca.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-107 — Il comando preferisce il codice fiscale alla partita IVA quando sono presenti entrambi

**Obiettivo**
Verificare che: il comando preferisce il codice fiscale alla partita IVA quando sono presenti entrambi.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Verifica leggera di presenza RUNTS — cai:check-runts-presence dietro feature flag".
- Test automatico: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php` — `cai:check-runts-presence prefers tax_code over vat_number when both are present`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiCheckRuntsPresenceCommandTest.php`.
- Test correlato: F9-99, F9-100, F9-101, F9-102, F9-103, F9-104, F9-105, F9-106, F9-108, F9-109, F9-110

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:check-runts-presence prefers tax_code over vat_number when both are present"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando preferisce il codice fiscale alla partita IVA quando sono presenti entrambi.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-108 — L'endpoint /search/runts-entity restituisce found:true quando l'ente è trovato

**Obiettivo**
Verificare che: l'endpoint /search/runts-entity restituisce found:true quando l'ente è trovato.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Verifica leggera di presenza RUNTS — cai:check-runts-presence dietro feature flag".
- Test automatico: `cai-runts-scraper/tests/test_search_runts_entity.py` — `def test_search_runts_entity_returns_found_true`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_search_runts_entity.py`.
- Test correlato: F9-99, F9-100, F9-101, F9-102, F9-103, F9-104, F9-105, F9-106, F9-107, F9-109, F9-110

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_search_runts_entity.py -k "test_search_runts_entity_returns_found_true"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: L'endpoint /search/runts-entity restituisce found:true quando l'ente è trovato.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-109 — L'endpoint /search/runts-entity restituisce found:false quando l'ente non è trovato

**Obiettivo**
Verificare che: l'endpoint /search/runts-entity restituisce found:false quando l'ente non è trovato.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Verifica leggera di presenza RUNTS — cai:check-runts-presence dietro feature flag".
- Test automatico: `cai-runts-scraper/tests/test_search_runts_entity.py` — `def test_search_runts_entity_returns_found_false`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_search_runts_entity.py`.
- Test correlato: F9-99, F9-100, F9-101, F9-102, F9-103, F9-104, F9-105, F9-106, F9-107, F9-108, F9-110

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_search_runts_entity.py -k "test_search_runts_entity_returns_found_false"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: L'endpoint /search/runts-entity restituisce found:false quando l'ente non è trovato.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-110 — L'endpoint /search/runts-entity restituisce 502 quando la verifica lancia un'eccezione

**Obiettivo**
Verificare che: l'endpoint /search/runts-entity restituisce 502 quando la verifica lancia un'eccezione.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Verifica leggera di presenza RUNTS — cai:check-runts-presence dietro feature flag".
- Test automatico: `cai-runts-scraper/tests/test_search_runts_entity.py` — `def test_search_runts_entity_returns_502_when_check_raises`.
- File/componente applicativo rilevante: `cai-runts-scraper/tests/test_search_runts_entity.py`.
- Test correlato: F9-99, F9-100, F9-101, F9-102, F9-103, F9-104, F9-105, F9-106, F9-107, F9-108, F9-109

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con `cai-runts-scraper/.venv` installato (`pip install -r requirements.txt`) e suite pytest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella directory del servizio | `cd cai-runts-scraper` | Directory corrente = cai-runts-scraper/ |
| 2 | Eseguire il test automatico mirato | `.venv/bin/python -m pytest tests/test_search_runts_entity.py -k "test_search_runts_entity_returns_502_when_check_raises"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: L'endpoint /search/runts-entity restituisce 502 quando la verifica lancia un'eccezione.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## Colonna e filtro presenza RUNTS nell'Anagrafica Sezioni, stato a 3 valori

### F9-111 — La tabella è filtrabile per stato di presenza RUNTS

**Obiettivo**
Verificare che: la tabella è filtrabile per stato di presenza RUNTS.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Colonna e filtro presenza RUNTS nell'Anagrafica Sezioni, stato a 3 valori".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `the table is filterable by RUNTS presence status`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: F9-112, F9-113, F9-114

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | La tabella è filtrabile per stato di presenza RUNTS |

**Risultato finale atteso**
La tabella è filtrabile per stato di presenza RUNTS.

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

### F9-112 — Ogni caso dell'enum stato presenza RUNTS ha un'etichetta e un colore

**Obiettivo**
Verificare che: ogni caso dell'enum stato presenza RUNTS ha un'etichetta e un colore.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Colonna e filtro presenza RUNTS nell'Anagrafica Sezioni, stato a 3 valori".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Enums/CaiRuntsPresenceStatusTest.php` — `every case has a label and a color`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Enums/CaiRuntsPresenceStatusTest.php`.
- Test correlato: F9-111, F9-113, F9-114

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "every case has a label and a color"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Ogni caso dell'enum stato presenza RUNTS ha un'etichetta e un colore.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-113 — I tre casi attesi esistono con i valori stringa attesi

**Obiettivo**
Verificare che: i tre casi attesi esistono con i valori stringa attesi.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Colonna e filtro presenza RUNTS nell'Anagrafica Sezioni, stato a 3 valori".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Enums/CaiRuntsPresenceStatusTest.php` — `the three expected cases exist with the expected string values`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Enums/CaiRuntsPresenceStatusTest.php`.
- Test correlato: F9-111, F9-112, F9-114

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the three expected cases exist with the expected string values"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: I tre casi attesi esistono con i valori stringa attesi.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-114 — cai_sections ha le colonne runts_presence_status e runts_presence_checked_at, entrambe nullable, mass-assignable e castate

**Obiettivo**
Verificare che: cai_sections ha le colonne runts_presence_status e runts_presence_checked_at, entrambe nullable, mass-assignable e castate.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Colonna e filtro presenza RUNTS nell'Anagrafica Sezioni, stato a 3 valori".
- Test automatico: `tests/Feature/Database/CaiSectionRuntsPresenceColumnsTest.php` — `cai_sections has runts_presence_status and runts_presence_checked_at columns, both nullable, mass-assignable and cast`.
- File/componente applicativo rilevante: `tests/Feature/Database/CaiSectionRuntsPresenceColumnsTest.php`.
- Test correlato: F9-111, F9-112, F9-113

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai_sections has runts_presence_status and runts_presence_checked_at columns, both nullable, mass-assignable and cast"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: cai_sections ha le colonne runts_presence_status e runts_presence_checked_at, entrambe nullable, mass-assignable e castate.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## cai:sync-runts-section — sincronizzare una singola sezione da riga di comando

### F9-115 — Il comando sincronizza una singola sezione per codice_cai e ne riporta il successo

**Obiettivo**
Verificare che: il comando sincronizza una singola sezione per codice_cai e ne riporta il successo.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai:sync-runts-section — sincronizzare una singola sezione da riga di comando".
- Test automatico: `tests/Feature/Console/CaiSyncRuntsSectionCommandTest.php` — `cai:sync-runts-section syncs a single section by codice_cai and reports success`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncRuntsSectionCommandTest.php`.
- Test correlato: F9-116, F9-117, F9-118

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-runts-section syncs a single section by codice_cai and reports success"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando sincronizza una singola sezione per codice_cai e ne riporta il successo.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-116 — Il comando segnala quando non viene trovata alcuna registrazione RUNTS

**Obiettivo**
Verificare che: il comando segnala quando non viene trovata alcuna registrazione RUNTS.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai:sync-runts-section — sincronizzare una singola sezione da riga di comando".
- Test automatico: `tests/Feature/Console/CaiSyncRuntsSectionCommandTest.php` — `cai:sync-runts-section reports when no RUNTS registration is found`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncRuntsSectionCommandTest.php`.
- Test correlato: F9-115, F9-117, F9-118

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-runts-section reports when no RUNTS registration is found"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando segnala quando non viene trovata alcuna registrazione RUNTS.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-117 — Il comando fallisce esplicitamente quando il codice_cai non esiste

**Obiettivo**
Verificare che: il comando fallisce esplicitamente quando il codice_cai non esiste.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai:sync-runts-section — sincronizzare una singola sezione da riga di comando".
- Test automatico: `tests/Feature/Console/CaiSyncRuntsSectionCommandTest.php` — `cai:sync-runts-section fails explicitly when the codice_cai does not exist`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncRuntsSectionCommandTest.php`.
- Test correlato: F9-115, F9-116, F9-118

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-runts-section fails explicitly when the codice_cai does not exist"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando fallisce esplicitamente quando il codice_cai non esiste.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-118 — Il comando segnala esplicitamente un errore quando lo scrape fallisce, senza un'eccezione non gestita

**Obiettivo**
Verificare che: il comando segnala esplicitamente un errore quando lo scrape fallisce, senza un'eccezione non gestita.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai:sync-runts-section — sincronizzare una singola sezione da riga di comando".
- Test automatico: `tests/Feature/Console/CaiSyncRuntsSectionCommandTest.php` — `cai:sync-runts-section reports an error explicitly when the scrape fails, without an unhandled exception`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncRuntsSectionCommandTest.php`.
- Test correlato: F9-115, F9-116, F9-117

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-runts-section reports an error explicitly when the scrape fails, without an unhandled exception"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando segnala esplicitamente un errore quando lo scrape fallisce, senza un'eccezione non gestita.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## cai:sync-runts-all — sincronizzare tutte le sezioni con retry, --limit e --codes

### F9-119 — Il comando sincronizza ogni sezione con codice fiscale e riporta un riepilogo

**Obiettivo**
Verificare che: il comando sincronizza ogni sezione con codice fiscale e riporta un riepilogo.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai:sync-runts-all — sincronizzare tutte le sezioni con retry, --limit e --codes".
- Test automatico: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php` — `cai:sync-runts-all syncs every section with a tax_code and reports a summary`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php`.
- Test correlato: F9-120, F9-121, F9-122, F9-123, F9-124, F9-125, F9-126

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-runts-all syncs every section with a tax_code and reports a summary"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando sincronizza ogni sezione con codice fiscale e riporta un riepilogo.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-120 — Il comando salta le sezioni senza codice fiscale, senza mai chiamare lo scraper per loro

**Obiettivo**
Verificare che: il comando salta le sezioni senza codice fiscale, senza mai chiamare lo scraper per loro.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai:sync-runts-all — sincronizzare tutte le sezioni con retry, --limit e --codes".
- Test automatico: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php` — `cai:sync-runts-all skips sections without a tax_code, never calling the scraper for them`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php`.
- Test correlato: F9-119, F9-121, F9-122, F9-123, F9-124, F9-125, F9-126

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-runts-all skips sections without a tax_code, never calling the scraper for them"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando salta le sezioni senza codice fiscale, senza mai chiamare lo scraper per loro.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-121 — Il comando salta una sezione col codice fiscale palesemente non valido (es. "0" o "."), senza chiamare lo scraper

**Obiettivo**
Verificare che: il comando salta una sezione col codice fiscale palesemente non valido (es. "0" o "."), senza chiamare lo scraper.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai:sync-runts-all — sincronizzare tutte le sezioni con retry, --limit e --codes".
- Test automatico: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php` — `cai:sync-runts-all skips a section whose tax_code is obviously invalid (e.g. "0" or "."), never calling the scraper`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php`.
- Test correlato: F9-119, F9-120, F9-122, F9-123, F9-124, F9-125, F9-126

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-runts-all skips a section whose tax_code is obviously invalid (e.g. "0" or "."), never calling the scraper"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando salta una sezione col codice fiscale palesemente non valido (es. "0" o "."), senza chiamare lo scraper.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-122 — Il comando prosegue con la sezione successiva quando una fallisce, senza fermare il giro

**Obiettivo**
Verificare che: il comando prosegue con la sezione successiva quando una fallisce, senza fermare il giro.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai:sync-runts-all — sincronizzare tutte le sezioni con retry, --limit e --codes".
- Test automatico: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php` — `cai:sync-runts-all continues with the next section when one fails, without stopping the batch`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php`.
- Test correlato: F9-119, F9-120, F9-121, F9-123, F9-124, F9-125, F9-126

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-runts-all continues with the next section when one fails, without stopping the batch"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando prosegue con la sezione successiva quando una fallisce, senza fermare il giro.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-123 — Il comando ritenta automaticamente una sezione fallita una volta, e la conta come sincronizzata se il retry recupera

**Obiettivo**
Verificare che: il comando ritenta automaticamente una sezione fallita una volta, e la conta come sincronizzata se il retry recupera.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai:sync-runts-all — sincronizzare tutte le sezioni con retry, --limit e --codes".
- Test automatico: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php` — `cai:sync-runts-all automatically retries a section that failed once, and counts it as synced if the retry recovers`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php`.
- Test correlato: F9-119, F9-120, F9-121, F9-122, F9-124, F9-125, F9-126

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-runts-all automatically retries a section that failed once, and counts it as synced if the retry recovers"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando ritenta automaticamente una sezione fallita una volta, e la conta come sincronizzata se il retry recupera.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-124 — Il comando conta una sezione "non trovata" separatamente da una sincronizzata

**Obiettivo**
Verificare che: il comando conta una sezione "non trovata" separatamente da una sincronizzata.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai:sync-runts-all — sincronizzare tutte le sezioni con retry, --limit e --codes".
- Test automatico: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php` — `cai:sync-runts-all counts a not-found section separately from a synced one`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php`.
- Test correlato: F9-119, F9-120, F9-121, F9-122, F9-123, F9-125, F9-126

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-runts-all counts a not-found section separately from a synced one"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando conta una sezione "non trovata" separatamente da una sincronizzata.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-125 — L'opzione --limit elabora solo le prime N sezioni

**Obiettivo**
Verificare che: l'opzione --limit elabora solo le prime N sezioni.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai:sync-runts-all — sincronizzare tutte le sezioni con retry, --limit e --codes".
- Test automatico: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php` — `cai:sync-runts-all --limit processes only the first N sections`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php`.
- Test correlato: F9-119, F9-120, F9-121, F9-122, F9-123, F9-124, F9-126

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-runts-all --limit processes only the first N sections"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: L'opzione --limit elabora solo le prime N sezioni.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-126 — L'opzione --codes limita la sincronizzazione all'elenco di codice_cai indicato, separati da virgola

**Obiettivo**
Verificare che: l'opzione --codes limita la sincronizzazione all'elenco di codice_cai indicato, separati da virgola.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "cai:sync-runts-all — sincronizzare tutte le sezioni con retry, --limit e --codes".
- Test automatico: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php` — `cai:sync-runts-all --codes restricts the sync to the given comma-separated codice_cai`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php`.
- Test correlato: F9-119, F9-120, F9-121, F9-122, F9-123, F9-124, F9-125

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-runts-all --codes restricts the sync to the given comma-separated codice_cai"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: L'opzione --codes limita la sincronizzazione all'elenco di codice_cai indicato, separati da virgola.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## Pagina admin "Bilanci non interpretati" per rivedere il parser

### F9-127 — Un utente senza cai-directory.review-unparsed-documents non accede alla pagina

**Obiettivo**
Verificare che: un utente senza cai-directory.review-unparsed-documents non accede alla pagina.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Pagina admin "Bilanci non interpretati" per rivedere il parser".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiUnparsedFinancialStatementsTest.php` — `a user without cai-directory.review-unparsed-documents is denied access to the page`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiUnparsedFinancialStatementsTest.php`.
- Test correlato: F9-128, F9-129, F9-130

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Un utente senza cai-directory.review-unparsed-documents non accede alla pagina |

**Risultato finale atteso**
Un utente senza cai-directory.review-unparsed-documents non accede alla pagina.

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

### F9-128 — Un utente con cai-directory.review-unparsed-documents accede alla pagina

**Obiettivo**
Verificare che: un utente con cai-directory.review-unparsed-documents accede alla pagina.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Pagina admin "Bilanci non interpretati" per rivedere il parser".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiUnparsedFinancialStatementsTest.php` — `a user with cai-directory.review-unparsed-documents can access the page`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiUnparsedFinancialStatementsTest.php`.
- Test correlato: F9-127, F9-129, F9-130

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Un utente con cai-directory.review-unparsed-documents accede alla pagina |

**Risultato finale atteso**
Un utente con cai-directory.review-unparsed-documents accede alla pagina.

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

### F9-129 — La tabella elenca solo i bilanci di esercizio senza dati estratti, mostrando sezione, anno e fonte RUNTS fissa

**Obiettivo**
Verificare che: la tabella elenca solo i bilanci di esercizio senza dati estratti, mostrando sezione, anno e fonte RUNTS fissa.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Pagina admin "Bilanci non interpretati" per rivedere il parser".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiUnparsedFinancialStatementsTest.php` — `the table lists only bilancio_esercizio documents with no data extracted, showing section, year and a fixed RUNTS source`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiUnparsedFinancialStatementsTest.php`.
- Test correlato: F9-127, F9-128, F9-130

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | La tabella elenca solo i bilanci di esercizio senza dati estratti, mostrando sezione, anno e fonte RUNTS fissa |

**Risultato finale atteso**
La tabella elenca solo i bilanci di esercizio senza dati estratti, mostrando sezione, anno e fonte RUNTS fissa.

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

### F9-130 — Le azioni di download e visualizzazione testo grezzo sono visibili per un documento non interpretato

**Obiettivo**
Verificare che: le azioni di download e visualizzazione testo grezzo sono visibili per un documento non interpretato.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Pagina admin "Bilanci non interpretati" per rivedere il parser".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiUnparsedFinancialStatementsTest.php` — `the download and view-raw-text actions are visible for an unparsed document`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiUnparsedFinancialStatementsTest.php`.
- Test correlato: F9-127, F9-128, F9-129

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Le azioni di download e visualizzazione testo grezzo sono visibili per un documento non interpretato |

**Risultato finale atteso**
Le azioni di download e visualizzazione testo grezzo sono visibili per un documento non interpretato.

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

## Import datapack — estensioni: scope su una sola sezione, skip campi RUNTS

### F9-131 — Un import scoped (onlyCaiSectionCode) importa solo la sezione richiesta, le sue sottosezioni e i suoi dati RUNTS

**Obiettivo**
Verificare che: un import scoped (onlyCaiSectionCode) importa solo la sezione richiesta, le sue sottosezioni e i suoi dati RUNTS.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Import datapack — estensioni: scope su una sola sezione, skip campi RUNTS".
- Test automatico: `tests/Feature/Console/CaiImportDatapackCommandTest.php` — `a scoped import (onlyCaiSectionCode) imports only the requested section, its subsections and its runts data`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiImportDatapackCommandTest.php`.
- Test correlato: F9-132

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a scoped import (onlyCaiSectionCode) imports only the requested section, its subsections and its runts data"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Un import scoped (onlyCaiSectionCode) importa solo la sezione richiesta, le sue sottosezioni e i suoi dati RUNTS.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-132 — skipSectionFields importa solo le tabelle di fonte RUNTS, lasciando invariata una CaiSection/CaiSubsection già importata

**Obiettivo**
Verificare che: skipSectionFields importa solo le tabelle di fonte RUNTS, lasciando invariata una CaiSection/CaiSubsection già importata.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Import datapack — estensioni: scope su una sola sezione, skip campi RUNTS".
- Test automatico: `tests/Feature/Console/CaiImportDatapackCommandTest.php` — `skipSectionFields imports only the RUNTS-sourced tables, leaving an already-imported CaiSection/CaiSubsection untouched`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiImportDatapackCommandTest.php`.
- Test correlato: F9-131

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "skipSectionFields imports only the RUNTS-sourced tables, leaving an already-imported CaiSection/CaiSubsection untouched"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: skipSectionFields importa solo le tabelle di fonte RUNTS, lasciando invariata una CaiSection/CaiSubsection già importata.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

## Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato

### F9-133 — La generazione trova una sezione nonostante differenze di prefisso/forma giuridica/spaziatura, riportando entrambi i codici

**Obiettivo**
Verificare che: la generazione trova una sezione nonostante differenze di prefisso/forma giuridica/spaziatura, riportando entrambi i codici.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiTaxCodeFallbackGeneratorTest.php` — `generate matches a section despite prefix/legal-form/spacing differences and reports both codes`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiTaxCodeFallbackGeneratorTest.php`.
- Test correlato: F9-134, F9-135, F9-136, F9-137, F9-138, F9-139, F9-140, F9-141, F9-142, F9-143, F9-144, F9-145, F9-146

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "generate matches a section despite prefix/legal-form/spacing differences and reports both codes"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: La generazione trova una sezione nonostante differenze di prefisso/forma giuridica/spaziatura, riportando entrambi i codici.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-134 — Le righe senza corrispondenza sono riportate, mai scartate in silenzio

**Obiettivo**
Verificare che: le righe senza corrispondenza sono riportate, mai scartate in silenzio.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiTaxCodeFallbackGeneratorTest.php` — `generate reports rows without a matching section instead of dropping them silently`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiTaxCodeFallbackGeneratorTest.php`.
- Test correlato: F9-133, F9-135, F9-136, F9-137, F9-138, F9-139, F9-140, F9-141, F9-142, F9-143, F9-144, F9-145, F9-146

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "generate reports rows without a matching section instead of dropping them silently"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Le righe senza corrispondenza sono riportate, mai scartate in silenzio.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-135 — Una riga senza CF né PIVA viene saltata

**Obiettivo**
Verificare che: una riga senza CF né PIVA viene saltata.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiTaxCodeFallbackGeneratorTest.php` — `generate skips a row with neither CF nor PIVA`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiTaxCodeFallbackGeneratorTest.php`.
- Test correlato: F9-133, F9-134, F9-136, F9-137, F9-138, F9-139, F9-140, F9-141, F9-142, F9-143, F9-144, F9-145, F9-146

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "generate skips a row with neither CF nor PIVA"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Una riga senza CF né PIVA viene saltata.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-136 — Due sezioni che normalizzano allo stesso nome non vengono mai fatte corrispondere

**Obiettivo**
Verificare che: due sezioni che normalizzano allo stesso nome non vengono mai fatte corrispondere.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Import/CaiTaxCodeFallbackGeneratorTest.php` — `generate never matches two sections that normalize to the same name`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Import/CaiTaxCodeFallbackGeneratorTest.php`.
- Test correlato: F9-133, F9-134, F9-135, F9-137, F9-138, F9-139, F9-140, F9-141, F9-142, F9-143, F9-144, F9-145, F9-146

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "generate never matches two sections that normalize to the same name"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Due sezioni che normalizzano allo stesso nome non vengono mai fatte corrispondere.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-137 — Il comando cai:generate-tax-code-fallback scrive le corrispondenze trovate in JSON e riporta le righe senza corrispondenza

**Obiettivo**
Verificare che: il comando cai:generate-tax-code-fallback scrive le corrispondenze trovate in JSON e riporta le righe senza corrispondenza.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato".
- Test automatico: `tests/Feature/Console/CaiGenerateTaxCodeFallbackCommandTest.php` — `cai:generate-tax-code-fallback writes the matched entries as JSON and reports unmatched rows`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiGenerateTaxCodeFallbackCommandTest.php`.
- Test correlato: F9-133, F9-134, F9-135, F9-136, F9-138, F9-139, F9-140, F9-141, F9-142, F9-143, F9-144, F9-145, F9-146

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:generate-tax-code-fallback writes the matched entries as JSON and reports unmatched rows"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando cai:generate-tax-code-fallback scrive le corrispondenze trovate in JSON e riporta le righe senza corrispondenza.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-138 — Il comando fallisce esplicitamente quando il file Excel sorgente non esiste

**Obiettivo**
Verificare che: il comando fallisce esplicitamente quando il file Excel sorgente non esiste.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato".
- Test automatico: `tests/Feature/Console/CaiGenerateTaxCodeFallbackCommandTest.php` — `cai:generate-tax-code-fallback fails when the source Excel file does not exist`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiGenerateTaxCodeFallbackCommandTest.php`.
- Test correlato: F9-133, F9-134, F9-135, F9-136, F9-137, F9-139, F9-140, F9-141, F9-142, F9-143, F9-144, F9-145, F9-146

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:generate-tax-code-fallback fails when the source Excel file does not exist"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando fallisce esplicitamente quando il file Excel sorgente non esiste.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-139 — Il riempimento completa codice fiscale e partita IVA mancanti dal fallback

**Obiettivo**
Verificare che: il riempimento completa codice fiscale e partita IVA mancanti dal fallback.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/FillCaiSectionFiscalCodesFromFallbackTest.php` — `run fills a missing tax_code and vat_number from the fallback`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/FillCaiSectionFiscalCodesFromFallbackTest.php`.
- Test correlato: F9-133, F9-134, F9-135, F9-136, F9-137, F9-138, F9-140, F9-141, F9-142, F9-143, F9-144, F9-145, F9-146

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run fills a missing tax_code and vat_number from the fallback"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il riempimento completa codice fiscale e partita IVA mancanti dal fallback.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-140 — Il riempimento non sovrascrive mai un valore già presente, anche se il fallback riporta un valore diverso

**Obiettivo**
Verificare che: il riempimento non sovrascrive mai un valore già presente, anche se il fallback riporta un valore diverso.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/FillCaiSectionFiscalCodesFromFallbackTest.php` — `run never overwrites a value already present, even if the fallback disagrees`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/FillCaiSectionFiscalCodesFromFallbackTest.php`.
- Test correlato: F9-133, F9-134, F9-135, F9-136, F9-137, F9-138, F9-139, F9-141, F9-142, F9-143, F9-144, F9-145, F9-146

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run never overwrites a value already present, even if the fallback disagrees"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il riempimento non sovrascrive mai un valore già presente, anche se il fallback riporta un valore diverso.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-141 — Una sezione senza dati e senza una corrispondenza nel fallback viene saltata

**Obiettivo**
Verificare che: una sezione senza dati e senza una corrispondenza nel fallback viene saltata.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/FillCaiSectionFiscalCodesFromFallbackTest.php` — `run skips a section missing data with no corresponding fallback entry`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/FillCaiSectionFiscalCodesFromFallbackTest.php`.
- Test correlato: F9-133, F9-134, F9-135, F9-136, F9-137, F9-138, F9-139, F9-140, F9-142, F9-143, F9-144, F9-145, F9-146

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run skips a section missing data with no corresponding fallback entry"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Una sezione senza dati e senza una corrispondenza nel fallback viene saltata.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-142 — In modalità dry-run il riempimento riporta cosa cambierebbe, senza scrivere

**Obiettivo**
Verificare che: in modalità dry-run il riempimento riporta cosa cambierebbe, senza scrivere.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/FillCaiSectionFiscalCodesFromFallbackTest.php` — `run in dry-run mode reports what would change without writing`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/FillCaiSectionFiscalCodesFromFallbackTest.php`.
- Test correlato: F9-133, F9-134, F9-135, F9-136, F9-137, F9-138, F9-139, F9-140, F9-141, F9-143, F9-144, F9-145, F9-146

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run in dry-run mode reports what would change without writing"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: In modalità dry-run il riempimento riporta cosa cambierebbe, senza scrivere.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-143 — Una sezione che ha già sia codice fiscale sia partita IVA non viene mai selezionata per il riempimento

**Obiettivo**
Verificare che: una sezione che ha già sia codice fiscale sia partita IVA non viene mai selezionata per il riempimento.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/FillCaiSectionFiscalCodesFromFallbackTest.php` — `run never selects a section that already has both tax_code and vat_number`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/FillCaiSectionFiscalCodesFromFallbackTest.php`.
- Test correlato: F9-133, F9-134, F9-135, F9-136, F9-137, F9-138, F9-139, F9-140, F9-141, F9-142, F9-144, F9-145, F9-146

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run never selects a section that already has both tax_code and vat_number"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Una sezione che ha già sia codice fiscale sia partita IVA non viene mai selezionata per il riempimento.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-144 — Il comando cai:fill-tax-codes-from-fallback completa i campi mancanti dal file configurato e riporta un riepilogo

**Obiettivo**
Verificare che: il comando cai:fill-tax-codes-from-fallback completa i campi mancanti dal file configurato e riporta un riepilogo.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato".
- Test automatico: `tests/Feature/Console/CaiFillTaxCodesFromFallbackCommandTest.php` — `cai:fill-tax-codes-from-fallback fills missing fields from the configured fallback file and reports a summary`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiFillTaxCodesFromFallbackCommandTest.php`.
- Test correlato: F9-133, F9-134, F9-135, F9-136, F9-137, F9-138, F9-139, F9-140, F9-141, F9-142, F9-143, F9-145, F9-146

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:fill-tax-codes-from-fallback fills missing fields from the configured fallback file and reports a summary"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il comando cai:fill-tax-codes-from-fallback completa i campi mancanti dal file configurato e riporta un riepilogo.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-145 — cai:sync-runts-all completa un codice fiscale mancante dal fallback prima di selezionare le sezioni da sincronizzare

**Obiettivo**
Verificare che: cai:sync-runts-all completa un codice fiscale mancante dal fallback prima di selezionare le sezioni da sincronizzare.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato".
- Test automatico: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php` — `cai:sync-runts-all fills a missing tax_code from the fallback before selecting sections to sync`.
- File/componente applicativo rilevante: `tests/Feature/Console/CaiSyncRuntsAllCommandTest.php`.
- Test correlato: F9-133, F9-134, F9-135, F9-136, F9-137, F9-138, F9-139, F9-140, F9-141, F9-142, F9-143, F9-144, F9-146

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "cai:sync-runts-all fills a missing tax_code from the fallback before selecting sections to sync"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: cai:sync-runts-all completa un codice fiscale mancante dal fallback prima di selezionare le sezioni da sincronizzare.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-146 — La tabella dell'Anagrafica CAI è filtrabile per "CF mancante"

**Obiettivo**
Verificare che: la tabella dell'Anagrafica CAI è filtrabile per "CF mancante".

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Fallback CF/PIVA da foglio Excel manuale, con generazione del JSON committato".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `the table is filterable by missing tax_code`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: F9-133, F9-134, F9-135, F9-136, F9-137, F9-138, F9-139, F9-140, F9-141, F9-142, F9-143, F9-144, F9-145

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | La tabella dell'Anagrafica CAI è filtrabile per "CF mancante" |

**Risultato finale atteso**
La tabella dell'Anagrafica CAI è filtrabile per "CF mancante".

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

## Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS

### F9-147 — Ogni caso dell'enum sorgente documento ha un'etichetta e un colore

**Obiettivo**
Verificare che: ogni caso dell'enum sorgente documento ha un'etichetta e un colore.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Enums/CaiDocumentSourceTest.php` — `every case has a label and a color`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Enums/CaiDocumentSourceTest.php`.
- Test correlato: F9-148, F9-149, F9-150, F9-151, F9-152, F9-153, F9-154, F9-155, F9-156, F9-157, F9-158, F9-159, F9-160, F9-161, F9-162, F9-163

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "every case has a label and a color"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Ogni caso dell'enum sorgente documento ha un'etichetta e un colore.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-148 — I tre casi attesi (runts/manual/veryfico) esistono con i valori stringa attesi

**Obiettivo**
Verificare che: i tre casi attesi (runts/manual/veryfico) esistono con i valori stringa attesi.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Enums/CaiDocumentSourceTest.php` — `the three expected cases exist with the expected string values`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Enums/CaiDocumentSourceTest.php`.
- Test correlato: F9-147, F9-149, F9-150, F9-151, F9-152, F9-153, F9-154, F9-155, F9-156, F9-157, F9-158, F9-159, F9-160, F9-161, F9-162, F9-163

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the three expected cases exist with the expected string values"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: I tre casi attesi (runts/manual/veryfico) esistono con i valori stringa attesi.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-149 — Ogni caso del vocabolario tipo documento ha un'etichetta non vuota

**Obiettivo**
Verificare che: ogni caso del vocabolario tipo documento ha un'etichetta non vuota.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Enums/CaiDocumentTypeTest.php` — `every case has a non-empty label`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Enums/CaiDocumentTypeTest.php`.
- Test correlato: F9-147, F9-148, F9-150, F9-151, F9-152, F9-153, F9-154, F9-155, F9-156, F9-157, F9-158, F9-159, F9-160, F9-161, F9-162, F9-163

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "every case has a non-empty label"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Ogni caso del vocabolario tipo documento ha un'etichetta non vuota.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-150 — Solo Mod A/B/D e le combinazioni che li contengono attivano l'analisi finanziaria automatica

**Obiettivo**
Verificare che: solo Mod A/B/D e le combinazioni che li contengono attivano l'analisi finanziaria automatica.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Enums/CaiDocumentTypeTest.php` — `only Mod A/B/D and the combinations that contain one trigger financial analysis`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Enums/CaiDocumentTypeTest.php`.
- Test correlato: F9-147, F9-148, F9-149, F9-151, F9-152, F9-153, F9-154, F9-155, F9-156, F9-157, F9-158, F9-159, F9-160, F9-161, F9-162, F9-163

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "only Mod A/B/D and the combinations that contain one trigger financial analysis"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Solo Mod A/B/D e le combinazioni che li contengono attivano l'analisi finanziaria automatica.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-151 — Il caso di fallback "Altro" esiste col valore atteso

**Obiettivo**
Verificare che: il caso di fallback "Altro" esiste col valore atteso.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Unit/Domain/CaiDirectory/Enums/CaiDocumentTypeTest.php` — `the fallback case exists with the expected value`.
- File/componente applicativo rilevante: `tests/Unit/Domain/CaiDirectory/Enums/CaiDocumentTypeTest.php`.
- Test correlato: F9-147, F9-148, F9-149, F9-150, F9-152, F9-153, F9-154, F9-155, F9-156, F9-157, F9-158, F9-159, F9-160, F9-161, F9-162, F9-163

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the fallback case exists with the expected value"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Il caso di fallback "Altro" esiste col valore atteso.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-152 — Un documento caricato a mano si collega sempre direttamente alla sezione, mai a una registrazione RUNTS

**Obiettivo**
Verificare che: un documento caricato a mano si collega sempre direttamente alla sezione, mai a una registrazione RUNTS.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/UploadCaiDocumentManuallyTest.php` — `run always attaches the document directly to the section, never to a registration`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/UploadCaiDocumentManuallyTest.php`.
- Test correlato: F9-147, F9-148, F9-149, F9-150, F9-151, F9-153, F9-154, F9-155, F9-156, F9-157, F9-158, F9-159, F9-160, F9-161, F9-162, F9-163

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run always attaches the document directly to the section, never to a registration"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Un documento caricato a mano si collega sempre direttamente alla sezione, mai a una registrazione RUNTS.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-153 — Un tipo Mod A/B/D (o una combinazione che li contiene) invia il documento all'analisi finanziaria automatica

**Obiettivo**
Verificare che: un tipo Mod A/B/D (o una combinazione che li contiene) invia il documento all'analisi finanziaria automatica.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/UploadCaiDocumentManuallyTest.php` — `run dispatches the financial-statement analysis job for a Mod A/B/D type or a combination that contains one`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/UploadCaiDocumentManuallyTest.php`.
- Test correlato: F9-147, F9-148, F9-149, F9-150, F9-151, F9-152, F9-154, F9-155, F9-156, F9-157, F9-158, F9-159, F9-160, F9-161, F9-162, F9-163

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run dispatches the financial-statement analysis job for a Mod A/B/D type or a combination that contains one"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Un tipo Mod A/B/D (o una combinazione che li contiene) invia il documento all'analisi finanziaria automatica.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-154 — Un tipo narrativo (relazioni, verbali, bilancio sociale, altro) non invia mai all'analisi automatica

**Obiettivo**
Verificare che: un tipo narrativo (relazioni, verbali, bilancio sociale, altro) non invia mai all'analisi automatica.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Actions/UploadCaiDocumentManuallyTest.php` — `run never dispatches the analysis job for a narrative type (relazioni, verbali, bilancio sociale, altro)`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Actions/UploadCaiDocumentManuallyTest.php`.
- Test correlato: F9-147, F9-148, F9-149, F9-150, F9-151, F9-152, F9-153, F9-155, F9-156, F9-157, F9-158, F9-159, F9-160, F9-161, F9-162, F9-163

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "run never dispatches the analysis job for a narrative type (relazioni, verbali, bilancio sociale, altro)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Un tipo narrativo (relazioni, verbali, bilancio sociale, altro) non invia mai all'analisi automatica.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-155 — Una sezione può avere documenti e bilanci collegati direttamente, senza alcuna registrazione RUNTS

**Obiettivo**
Verificare che: una sezione può avere documenti e bilanci collegati direttamente, senza alcuna registrazione RUNTS.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php` — `a section has many documents and financial statements attached directly, without any runts registration`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/CaiDirectorySchemaTest.php`.
- Test correlato: F9-147, F9-148, F9-149, F9-150, F9-151, F9-152, F9-153, F9-154, F9-156, F9-157, F9-158, F9-159, F9-160, F9-161, F9-162, F9-163

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a section has many documents and financial statements attached directly, without any runts registration"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: Una sezione può avere documenti e bilanci collegati direttamente, senza alcuna registrazione RUNTS.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-156 — L'analisi crea un CaiFinancialStatement chiave-sezione per un documento senza registrazione

**Obiettivo**
Verificare che: l'analisi crea un CaiFinancialStatement chiave-sezione per un documento senza registrazione.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php` — `handle creates a CaiFinancialStatement keyed by section for a document with no registration`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php`.
- Test correlato: F9-147, F9-148, F9-149, F9-150, F9-151, F9-152, F9-153, F9-154, F9-155, F9-157, F9-158, F9-159, F9-160, F9-161, F9-162, F9-163

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "handle creates a CaiFinancialStatement keyed by section for a document with no registration"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: L'analisi crea un CaiFinancialStatement chiave-sezione per un documento senza registrazione.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-157 — L'analisi aggiorna un CaiFinancialStatement chiave-sezione esistente, senza toccare uno chiave-registrazione per lo stesso anno

**Obiettivo**
Verificare che: l'analisi aggiorna un CaiFinancialStatement chiave-sezione esistente, senza toccare uno chiave-registrazione per lo stesso anno.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php` — `handle updates an existing section-keyed CaiFinancialStatement without touching a registration-keyed one for the same year`.
- File/componente applicativo rilevante: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php`.
- Test correlato: F9-147, F9-148, F9-149, F9-150, F9-151, F9-152, F9-153, F9-154, F9-155, F9-156, F9-158, F9-159, F9-160, F9-161, F9-162, F9-163

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture.

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "handle updates an existing section-keyed CaiFinancialStatement without touching a registration-keyed one for the same year"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test referenziato passa: L'analisi aggiorna un CaiFinancialStatement chiave-sezione esistente, senza toccare uno chiave-registrazione per lo stesso anno.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando eseguito.

**Criterio di superamento**

PASS: il comando termina con exit code 0 e il test indicato risulta passed.
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

### F9-158 — L'azione "Carica documento" è visibile solo con il permesso cai-directory.upload-document

**Obiettivo**
Verificare che: l'azione "Carica documento" è visibile solo con il permesso cai-directory.upload-document.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `the upload document action is only visible with cai-directory.upload-document`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: F9-147, F9-148, F9-149, F9-150, F9-151, F9-152, F9-153, F9-154, F9-155, F9-156, F9-157, F9-159, F9-160, F9-161, F9-162, F9-163

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | L'azione "Carica documento" è visibile solo con il permesso cai-directory.upload-document |

**Risultato finale atteso**
L'azione "Carica documento" è visibile solo con il permesso cai-directory.upload-document.

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

### F9-159 — Caricare un documento lo collega sempre direttamente alla sezione, senza un selettore di registrazione nel form

**Obiettivo**
Verificare che: caricare un documento lo collega sempre direttamente alla sezione, senza un selettore di registrazione nel form.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `uploading a document always attaches it directly to the section, with no registration picker in the form`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: F9-147, F9-148, F9-149, F9-150, F9-151, F9-152, F9-153, F9-154, F9-155, F9-156, F9-157, F9-158, F9-160, F9-161, F9-162, F9-163

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Caricare un documento lo collega sempre direttamente alla sezione, senza un selettore di registrazione nel form |

**Risultato finale atteso**
Caricare un documento lo collega sempre direttamente alla sezione, senza un selettore di registrazione nel form.

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

### F9-160 — Caricare un documento di tipo Mod A invia il job di analisi finanziaria

**Obiettivo**
Verificare che: caricare un documento di tipo Mod A invia il job di analisi finanziaria.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `uploading a Mod A document dispatches the financial-statement analysis job`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: F9-147, F9-148, F9-149, F9-150, F9-151, F9-152, F9-153, F9-154, F9-155, F9-156, F9-157, F9-158, F9-159, F9-161, F9-162, F9-163

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Caricare un documento di tipo Mod A invia il job di analisi finanziaria |

**Risultato finale atteso**
Caricare un documento di tipo Mod A invia il job di analisi finanziaria.

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

### F9-161 — Un cliente può scaricare un documento collegato direttamente alla propria sezione, senza alcuna registrazione RUNTS

**Obiettivo**
Verificare che: un cliente può scaricare un documento collegato direttamente alla propria sezione, senza alcuna registrazione RUNTS.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `a customer can download a document attached directly to their own section, without any runts registration`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: F9-147, F9-148, F9-149, F9-150, F9-151, F9-152, F9-153, F9-154, F9-155, F9-156, F9-157, F9-158, F9-159, F9-160, F9-162, F9-163

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Un cliente può scaricare un documento collegato direttamente alla propria sezione, senza alcuna registrazione RUNTS |

**Risultato finale atteso**
Un cliente può scaricare un documento collegato direttamente alla propria sezione, senza alcuna registrazione RUNTS.

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

### F9-162 — Un cliente non può scaricare un documento collegato direttamente a un'altra sezione

**Obiettivo**
Verificare che: un cliente non può scaricare un documento collegato direttamente a un'altra sezione.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `a customer cannot download a document attached directly to another cai section`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: F9-147, F9-148, F9-149, F9-150, F9-151, F9-152, F9-153, F9-154, F9-155, F9-156, F9-157, F9-158, F9-159, F9-160, F9-161, F9-163

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Un cliente non può scaricare un documento collegato direttamente a un'altra sezione |

**Risultato finale atteso**
Un cliente non può scaricare un documento collegato direttamente a un'altra sezione.

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

### F9-163 — I tab Bilanci/Allegati uniscono i documenti collegati direttamente alla sezione con quelli collegati via registrazione RUNTS

**Obiettivo**
Verificare che: i tab Bilanci/Allegati uniscono i documenti collegati direttamente alla sezione con quelli collegati via registrazione RUNTS.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Sorgente documento (RUNTS/manuale/Veryfico) e caricamento manuale, senza registrazione RUNTS".
- Test automatico: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` — `the documents and financial statements tabs merge documents/bilanci attached directly to the section with those attached via a runts registration`.
- File/componente applicativo rilevante: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`.
- Test correlato: F9-147, F9-148, F9-149, F9-150, F9-151, F9-152, F9-153, F9-154, F9-155, F9-156, F9-157, F9-158, F9-159, F9-160, F9-161, F9-162

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | I tab Bilanci/Allegati uniscono i documenti collegati direttamente alla sezione con quelli collegati via registrazione RUNTS |

**Risultato finale atteso**
I tab Bilanci/Allegati uniscono i documenti collegati direttamente alla sezione con quelli collegati via registrazione RUNTS.

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

## Menu Gruppo Regionale — voci "GR"/"Sezioni" separate dalla Dashboard cliente

### F9-164 — La card "Sezioni del gruppo regionale" non compare più su questa pagina, nemmeno per un cliente Gruppo Regionale (si è spostata su una voce di navigazione propria)

**Obiettivo**
Verificare che: la card "Sezioni del gruppo regionale" non compare più su questa pagina, nemmeno per un cliente Gruppo Regionale (si è spostata su una voce di navigazione propria).

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Menu Gruppo Regionale — voci "GR"/"Sezioni" separate dalla Dashboard cliente".
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the regional group sections card is never shown on this page, not even for a gruppo regionale customer (it moved to its own navigation entry)`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Pages/CustomerDashboardTest.php`.
- Test correlato: F9-165, F9-166, F9-167, F9-168, F9-169, F9-170, F9-171, F9-172, F9-173

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer (Gruppo Regionale)

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | La card "Sezioni del gruppo regionale" non compare più su questa pagina, nemmeno per un cliente Gruppo Regionale (si è spostata su una voce di navigazione propria) |

**Risultato finale atteso**
La card "Sezioni del gruppo regionale" non compare più su questa pagina, nemmeno per un cliente Gruppo Regionale (si è spostata su una voce di navigazione propria).

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

### F9-165 — Il gruppo di navigazione è "GR" per un cliente Gruppo Regionale, "Area cliente" per qualunque altro tipo cliente

**Obiettivo**
Verificare che: il gruppo di navigazione è "GR" per un cliente Gruppo Regionale, "Area cliente" per qualunque altro tipo cliente.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Menu Gruppo Regionale — voci "GR"/"Sezioni" separate dalla Dashboard cliente".
- Test automatico: `tests/Feature/Filament/Pages/CustomerDashboardTest.php` — `the navigation group is "GR" for a gruppo regionale customer and "Area cliente" for any other customer type`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Pages/CustomerDashboardTest.php`.
- Test correlato: F9-164, F9-166, F9-167, F9-168, F9-169, F9-170, F9-171, F9-172, F9-173

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer (Gruppo Regionale)

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Il gruppo di navigazione è "GR" per un cliente Gruppo Regionale, "Area cliente" per qualunque altro tipo cliente |

**Risultato finale atteso**
Il gruppo di navigazione è "GR" per un cliente Gruppo Regionale, "Area cliente" per qualunque altro tipo cliente.

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

### F9-166 — Un cliente Gruppo Regionale può accedere alla nuova pagina "Sezioni"

**Obiettivo**
Verificare che: un cliente Gruppo Regionale può accedere alla nuova pagina "Sezioni".

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Menu Gruppo Regionale — voci "GR"/"Sezioni" separate dalla Dashboard cliente".
- Test automatico: `tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php` — `a gruppo regionale customer can access the page`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php`.
- Test correlato: F9-164, F9-165, F9-167, F9-168, F9-169, F9-170, F9-171, F9-172, F9-173

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer (Gruppo Regionale)

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Un cliente Gruppo Regionale può accedere alla nuova pagina "Sezioni" |

**Risultato finale atteso**
Un cliente Gruppo Regionale può accedere alla nuova pagina "Sezioni".

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

### F9-167 — Un cliente Sezione non può accedere alla pagina "Sezioni"

**Obiettivo**
Verificare che: un cliente Sezione non può accedere alla pagina "Sezioni".

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Menu Gruppo Regionale — voci "GR"/"Sezioni" separate dalla Dashboard cliente".
- Test automatico: `tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php` — `a sezione customer cannot access the page`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php`.
- Test correlato: F9-164, F9-165, F9-166, F9-168, F9-169, F9-170, F9-171, F9-172, F9-173

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer (Sezione)

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Un cliente Sezione non può accedere alla pagina "Sezioni" |

**Risultato finale atteso**
Un cliente Sezione non può accedere alla pagina "Sezioni".

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

### F9-168 — Un utente non-cliente non può accedere alla pagina "Sezioni"

**Obiettivo**
Verificare che: un utente non-cliente non può accedere alla pagina "Sezioni".

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Menu Gruppo Regionale — voci "GR"/"Sezioni" separate dalla Dashboard cliente".
- Test automatico: `tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php` — `a non-customer cannot access the page`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php`.
- Test correlato: F9-164, F9-165, F9-166, F9-167, F9-169, F9-170, F9-171, F9-172, F9-173

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Un utente non-cliente non può accedere alla pagina "Sezioni" |

**Risultato finale atteso**
Un utente non-cliente non può accedere alla pagina "Sezioni".

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

### F9-169 — Il gruppo di navigazione della nuova pagina è "Sezioni"

**Obiettivo**
Verificare che: il gruppo di navigazione della nuova pagina è "Sezioni".

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Menu Gruppo Regionale — voci "GR"/"Sezioni" separate dalla Dashboard cliente".
- Test automatico: `tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php` — `the navigation group is "Sezioni"`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php`.
- Test correlato: F9-164, F9-165, F9-166, F9-167, F9-168, F9-170, F9-171, F9-172, F9-173

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Il gruppo di navigazione della nuova pagina è "Sezioni" |

**Risultato finale atteso**
Il gruppo di navigazione della nuova pagina è "Sezioni".

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

### F9-170 — La pagina elenca solo le sezioni della stessa regione, col relativo conteggio di ticket aperti

**Obiettivo**
Verificare che: la pagina elenca solo le sezioni della stessa regione, col relativo conteggio di ticket aperti.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Menu Gruppo Regionale — voci "GR"/"Sezioni" separate dalla Dashboard cliente".
- Test automatico: `tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php` — `it lists only sections in the same region, with their open ticket count`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php`.
- Test correlato: F9-164, F9-165, F9-166, F9-167, F9-168, F9-169, F9-171, F9-172, F9-173

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | La pagina elenca solo le sezioni della stessa regione, col relativo conteggio di ticket aperti |

**Risultato finale atteso**
La pagina elenca solo le sezioni della stessa regione, col relativo conteggio di ticket aperti.

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

### F9-171 — Stato vuoto esplicito quando la regione non ha ancora sezioni classificate

**Obiettivo**
Verificare che: stato vuoto esplicito quando la regione non ha ancora sezioni classificate.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Menu Gruppo Regionale — voci "GR"/"Sezioni" separate dalla Dashboard cliente".
- Test automatico: `tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php` — `it shows an explicit empty state when the region has no sections yet`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php`.
- Test correlato: F9-164, F9-165, F9-166, F9-167, F9-168, F9-169, F9-170, F9-172, F9-173

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Stato vuoto esplicito quando la regione non ha ancora sezioni classificate |

**Risultato finale atteso**
Stato vuoto esplicito quando la regione non ha ancora sezioni classificate.

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

### F9-172 — Stato vuoto esplicito quando il Gruppo Regionale non ha una regione valorizzata

**Obiettivo**
Verificare che: stato vuoto esplicito quando il Gruppo Regionale non ha una regione valorizzata.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Menu Gruppo Regionale — voci "GR"/"Sezioni" separate dalla Dashboard cliente".
- Test automatico: `tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php` — `it shows an explicit empty state when the group has no region`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Pages/CustomerRegionalSectionsDashboardTest.php`.
- Test correlato: F9-164, F9-165, F9-166, F9-167, F9-168, F9-169, F9-170, F9-171, F9-173

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer (Gruppo Regionale)

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | Stato vuoto esplicito quando il Gruppo Regionale non ha una regione valorizzata |

**Risultato finale atteso**
Stato vuoto esplicito quando il Gruppo Regionale non ha una regione valorizzata.

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

### F9-173 — La pagina "Sezioni" collega alla pagina di dettaglio sezione

**Obiettivo**
Verificare che: la pagina "Sezioni" collega alla pagina di dettaglio sezione.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Menu Gruppo Regionale — voci "GR"/"Sezioni" separate dalla Dashboard cliente".
- Test automatico: `tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php` — `the regional group sections page links to the section detail page`.
- File/componente applicativo rilevante: `tests/Feature/Filament/Pages/CaiSectionRegionalDetailTest.php`.
- Test correlato: F9-164, F9-165, F9-166, F9-167, F9-168, F9-169, F9-170, F9-171, F9-172

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Credenziali di un utente con il ruolo/permesso pertinente (vedi "Ruolo del tester" sopra), punto 9 di `00-istruzioni-generali.md`.
- Accesso all'ambiente UAT raggiungibile (punto 8 di `00-istruzioni-generali.md`).

**Dati di test**
Dati già presenti nel dataset UAT, oppure creati ad-hoc secondo la convenzione del punto 14 di `00-istruzioni-generali.md` se il test lo richiede esplicitamente.

**Stato iniziale**
Dataset UAT nello stato corrente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con le credenziali del ruolo indicato in "Ruolo del tester" | credenziali di test | Login riuscito |
| 2 | Riproduci in UI il comportamento descritto in "Obiettivo", verificando il risultato osservato | dati pertinenti al test | La pagina "Sezioni" collega alla pagina di dettaglio sezione |

**Risultato finale atteso**
La pagina "Sezioni" collega alla pagina di dettaglio sezione.

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

## Checkpoint di fine fase — flusso end-to-end sui tre filoni della Fase 9

### F9-174 — Il flusso completo funziona end-to-end: creazione ticket con Richiesta, sync live CAI/RUNTS di una sezione con estrazione bilancio, fallback CF/PIVA, upload manuale di un documento e menu Gruppo Regionale scoped alla propria regione

**Obiettivo**
Verificare che: il flusso completo funziona end-to-end: creazione ticket con Richiesta, sync live CAI/RUNTS di una sezione con estrazione bilancio, fallback CF/PIVA, upload manuale di un documento e menu Gruppo Regionale scoped alla propria regione.

**Riferimenti**
- Requisito/regola di dominio: ramo `ralph/orchestrator-v2-fase-9`, argomento "Checkpoint di fine fase — flusso end-to-end sui tre filoni della Fase 9".
- Test automatico: `tests/Feature/EndToEnd/Fase9CheckpointEndToEndTest.php` — `the full Fase 9 flow works end-to-end: ticket richiesta, live CAI/RUNTS sync with bilancio extraction, CF/PIVA fallback, manual document upload and gruppo regionale menu`.
- File/componente applicativo rilevante: `tests/Feature/EndToEnd/Fase9CheckpointEndToEndTest.php`.
- Test correlato: Nessuno.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.
- Nessun dato UAT reale richiesto: il test costruisce i propri dati/fixture (ticket, sezione CAI, registrazione RUNTS, fallback CF/PIVA, documento manuale, cliente Gruppo Regionale).

**Dati di test**
Non applicabile: il test costruisce i propri dati/fixture.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the full Fase 9 flow works end-to-end"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: il flusso completo funziona end-to-end su tutti e tre i filoni della Fase 9 (creazione ticket con Richiesta, sync live CAI/RUNTS di una sezione con estrazione bilancio, fallback CF/PIVA, upload manuale di un documento e menu Gruppo Regionale scoped alla propria regione).

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


## Bilanci Sezioni 2025 nel datapack CAI (F9-175 — F9-191, US-930..US-935)

I bilanci 2025 raccolti dai Gruppi Regionali (campagna "Bilanci Sezioni 2026") entrano in Orchestrator come documenti caricati manualmente, collegati direttamente alla Sezione (nessuna registrazione RUNTS). Il comando `cai:build-manual-bilanci-datapack` aggiunge al datapack la tabella `bilanci_manuali` a partire dalle cartelle normalizzate e dall'Excel indice, riportando le anomalie di copertura; `cai:import-datapack` crea poi i documenti (idempotente per sezione e hash, senza toccare i documenti RUNTS) e li rende visibili nel tab "Allegati" della Sezione. Le etichette non riconosciute diventano il tipo "Altro" con il titolo originale; l'analisi automatica delle cifre è attiva solo con `--analyze-manual`. I casi F9-175 — F9-191 sono tutti AUTOMATICI (suite Pest, ruolo Sviluppatore) e sono elencati nel manifest `fase-9.php`; la verifica manuale su dati reali (402 file, 345 sezioni) è documentata in `progress.txt` (US-937). Il PDF di collaudo non è rigenerato in questa fase di sviluppo (`pdflatex` assente sull'host locale).

## Menu Anagrafica CAI, Bilancio 2025 e Gruppi regionali (F9-192 — F9-211, US-940..US-947)

Il menu "Anagrafica CAI" è riorganizzato in tre sotto-menu: "Sezioni" (Anagrafica sezioni, Mappa sezioni), "Bilanci" (Bilanci non interpretati, Bilancio 2025) e "Gruppi regionali" (Elenco gruppi regionali). "Bilancio 2025" elenca tutte le sezioni con quattro indicatori (file di conto economico/stato patrimoniale presente, conto economico/stato patrimoniale interpretato) e filtri per regione e per ciascun indicatore; "Elenco gruppi regionali" riassume per ogni Gruppo Regionale sezioni, sezioni con bilancio e copertura del conto economico interpretato. Il comando `cai:analyze-financial-documents --year=2025` accoda l'analisi dei documenti dell'anno (verifica su dati reali: 348 documenti accodati, 145 `Extracted` e 205 `NoDataExtracted`; 97 sezioni su 529 con conto economico interpretato, 44 con stato patrimoniale interpretato). I casi F9-192 — F9-211 sono AUTOMATICI (suite Pest, e pytest per il servizio di analisi) e sono elencati nel manifest `fase-9.php`. Il PDF di collaudo non è rigenerato (`pdflatex` assente sull'host locale): quando lo si rigenera, allineare i contatori in testa al manuale (casi e argomenti) e il dettaglio dei casi F9-192 — F9-211, generato dallo script del `docs/collaudo/CLAUDE.md`.
