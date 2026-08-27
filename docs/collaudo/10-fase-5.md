# Fase 5 (Fundraising — opportunità/bandi, griglia di valutazione, progetti e vista cliente) — Casi di test dettagliati

> Torna a [`README.md`](README.md) · Istruzioni generali: [`00-istruzioni-generali.md`](00-istruzioni-generali.md) · Matrice di tracciabilità: [`01-matrice-tracciabilita.md`](01-matrice-tracciabilita.md)

60 casi di test (F5-01 — F5-60) su 5 argomenti. Prima di eseguire un test, leggi le convenzioni comuni
in `00-istruzioni-generali.md` (in particolare le sezioni 9 "Credenziali" e 12 "Prerequisiti generali").
Come Fase 4, gli argomenti sono raggruppati per area funzionale del PRD: §6.6.1 Opportunità di
fundraising (US-501/502/505), §6.6.2 Griglia di valutazione (US-503/504), §6.6.3 Progetti di
fundraising (US-506/507), §6.6.4 Vista cliente (US-508), più un ultimo argomento dedicato al
checkpoint di fine fase (US-509, F5-58..F5-60), che replica in automatico — con dati sintetici ma
rappresentativi — la verifica manuale eseguita su dati reali importati da v1 (`v1:import --anonymize`,
21 opportunità/33 progetti/9 partner fundraising reali) in ambiente Docker durante lo sviluppo di
questa story (vedi `scripts/ralph/progress.txt`, sezione US-509).

## Opportunità di fundraising (US-501/502/505)

### F5-01 — isExpired() è false quando la scadenza è oggi

**Obiettivo**
Verificare che `FundraisingOpportunity::isExpired()` restituisca `false` quando `deadline` è la data odierna (confronto sulla sola data, non sull'orario), così che un'opportunità che scade oggi sia ancora considerata attiva.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-501, AC "isExpired(): bool (deadline < oggi, confronto sulla sola data)".
- Test automatico: `tests/Feature/Domain/Fundraising/FundraisingOpportunityExpiryTest.php` — `isExpired is false when the deadline is today`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Models\FundraisingOpportunity::isExpired()`.
- Test correlato: F5-02, F5-03, F5-04.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Una `FundraisingOpportunity` con `deadline` impostata a `today()`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "isExpired is false when the deadline is today"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
isExpired() è false quando la scadenza è oggi

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

### F5-02 — isExpired() è true quando la scadenza è ieri

**Obiettivo**
Verificare che `isExpired()` restituisca `true` quando `deadline` è nel passato, anche di un solo giorno.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-501, AC "isExpired(): bool (deadline < oggi)".
- Test automatico: `tests/Feature/Domain/Fundraising/FundraisingOpportunityExpiryTest.php` — `isExpired is true when the deadline is yesterday`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Models\FundraisingOpportunity::isExpired()`.
- Test correlato: F5-01, F5-04.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Una `FundraisingOpportunity` con `deadline` impostata a `today()->subDay()`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "isExpired is true when the deadline is yesterday"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
isExpired() è true quando la scadenza è ieri

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

### F5-03 — Lo scope active() restituisce le opportunità con scadenza odierna o futura

**Obiettivo**
Verificare che `FundraisingOpportunity::active()` includa sia le opportunità che scadono oggi sia quelle future, escludendo quelle già scadute — è la query che alimenta la vista elenco di default (US-502).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-501, AC "scope active (deadline >= today)".
- Test automatico: `tests/Feature/Domain/Fundraising/FundraisingOpportunityExpiryTest.php` — `scope active returns opportunities whose deadline is today or later`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Models\FundraisingOpportunity::scopeActive()`.
- Test correlato: F5-04, F5-08.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Tre opportunità con `deadline` rispettivamente ieri, oggi, domani.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "scope active returns opportunities whose deadline is today or later"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Lo scope active() restituisce le opportunità con scadenza odierna o futura

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

### F5-04 — Lo scope expired() restituisce le opportunità con scadenza passata

**Obiettivo**
Verificare che `FundraisingOpportunity::expired()` includa solo le opportunità con scadenza nel passato — è la query che alimenta la vista "Archivio" (US-502).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-501, AC "scope ... expired".
- Test automatico: `tests/Feature/Domain/Fundraising/FundraisingOpportunityExpiryTest.php` — `scope expired returns opportunities whose deadline is before today`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Models\FundraisingOpportunity::scopeExpired()`.
- Test correlato: F5-03, F5-08.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Tre opportunità con `deadline` rispettivamente ieri, oggi, domani.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "scope expired returns opportunities whose deadline is before today"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Lo scope expired() restituisce le opportunità con scadenza passata

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

### F5-05 — Un utente senza alcun permesso fundraising.* è negato su ogni abilità della Policy opportunità

**Obiettivo**
Verificare che, in assenza di un permesso `fundraising.*`, `FundraisingOpportunityPolicy` neghi ogni abilità (view.any, view.involved, create, update, delete) — deny by default.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-501, AC "deny by default, coerente con le Policy già esistenti da Fase 0".
- Test automatico: `tests/Feature/Domain/Fundraising/FundraisingOpportunityPolicyTest.php` — `a user without any fundraising.* permission is denied every FundraisingOpportunityPolicy ability`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Policies\FundraisingOpportunityPolicy`.
- Test correlato: F5-06.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a user without any fundraising.* permission is denied every FundraisingOpportunityPolicy ability"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Un utente senza alcun permesso fundraising.* è negato su ogni abilità della Policy opportunità

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

### F5-06 — FundraisingOpportunityPolicy verificata riga per riga per ogni ruolo (§9.4)

**Obiettivo**
Verificare, ruolo per ruolo, che solo admin e fundraising abbiano view.any/create/update/delete, mentre il customer abbia solo view.involved e mai le altre abilità — dataset Pest riga per riga, un caso per ruolo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-501, AC "§9.4: solo admin e fundraising hanno .view.any/.create/.update/.delete; customer ha solo .view.involved, mai le altre".
- Test automatico: `tests/Feature/Domain/Fundraising/FundraisingOpportunityPolicyTest.php` — `FundraisingOpportunityPolicy per ruolo, riga per riga (§9.4)`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Policies\FundraisingOpportunityPolicy`.
- Test correlato: F5-05, F5-07.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "FundraisingOpportunityPolicy per ruolo, riga per riga \(§9.4\)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
FundraisingOpportunityPolicy verificata riga per riga per ogni ruolo (§9.4)

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

### F5-07 — La Resource opportunità è visibile in navigazione solo ad admin/fundraising, mai a manager/developer/customer

**Obiettivo**
Verificare che la voce di navigazione "Opportunità" (fundraising) compaia solo per admin/fundraising e che manager/developer/customer non la vedano né vi accedano direttamente via URL.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-501/US-502, AC "Nessuna schermata del modulo compare in navigazione per manager/developer"; canViewAny() ristretto a fundraising.view.any.
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingOpportunityResourceTest.php` — `FundraisingOpportunityResource visibility per ruolo (§9.4, mai manager/developer/customer)`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingOpportunities\FundraisingOpportunityResource`.
- Test correlato: F5-05, F5-06.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Credenziali dei 5 ruoli (punto 9 di `00-istruzioni-generali.md`).

**Dati di test**
Nessuno specifico: si verifica solo la visibilità della voce di menu/rotta.

**Stato iniziale**
Nessuna opportunità necessaria.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising | sara.mariani@montagnaservizi.com | Voce di navigazione "Opportunità" visibile, elenco raggiungibile |
| 2 | Accedi come Admin | info@montagnaservizi.com | Voce di navigazione "Opportunità" visibile, elenco raggiungibile |
| 3 | Accedi come Manager/Developer/Customer, uno alla volta | manager@oc.test / lorena.sava@montagnaservizi.com / infosentieroitalia@cai.it | Nessuna voce "Opportunità" in navigazione |
| 4 | Da Manager/Developer/Customer, provare l'URL diretto dell'elenco opportunità | /admin/fundraising-opportunities | Accesso negato (403) o redirect, mai l'elenco |

**Risultato finale atteso**
Solo admin/fundraising vedono e accedono alla Resource opportunità.

**Controlli negativi**
Vedi passi 3-4 della procedura.

**Evidenze da acquisire**
- Screenshot del menu per ciascun ruolo.
- Screenshot dell'accesso negato via URL diretto per manager/developer/customer.

**Criterio di superamento**

PASS: Solo admin/fundraising vedono e accedono alla Resource opportunità.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F5-08 — L'elenco mostra di default solo le opportunità attive, l'Archivio mostra solo le scadute

**Obiettivo**
Verificare che la vista elenco predefinita mostri solo le opportunità con scadenza odierna o futura, e che una vista/tab "Archivio" separata mostri solo quelle scadute.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-502, AC "vista elenco (default: solo active) e vista separata Archivio per le scadute".
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingOpportunityResourceTest.php` — `elenco mostra solo le opportunità attive di default, archivio mostra solo le scadute`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingOpportunities\Pages\ListFundraisingOpportunities`.
- Test correlato: F5-03, F5-04.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Utente Fundraising `sara.mariani@montagnaservizi.com`.
- Almeno un'opportunità attiva e una scaduta nel dataset.

**Dati di test**
Opportunità reali del dataset UAT con scadenze sia passate sia future (verificare empiricamente, punto 13 di `00-istruzioni-generali.md`).

**Stato iniziale**
Dataset UAT con opportunità attive e scadute.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising | sara.mariani@montagnaservizi.com | Login riuscito |
| 2 | Apri l'elenco "Opportunità" (tab predefinito) | — | Solo opportunità con scadenza odierna o futura elencate |
| 3 | Passa al tab/vista "Archivio" | — | Solo opportunità con scadenza passata elencate |

**Risultato finale atteso**
L'elenco predefinito e l'Archivio mostrano rispettivamente solo attive e solo scadute, senza sovrapposizioni.

**Controlli negativi**
Nessuna opportunità scaduta nell'elenco predefinito, nessuna attiva nell'Archivio.

**Evidenze da acquisire**
- Screenshot dell'elenco predefinito.
- Screenshot della vista Archivio.

**Criterio di superamento**

PASS: L'elenco predefinito e l'Archivio mostrano rispettivamente solo attive e solo scadute, senza sovrapposizioni.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F5-09 — Il filtro per ambito territoriale produce il sottoinsieme atteso di opportunità

**Obiettivo**
Verificare che il filtro per `TerritorialScope` (locale/regionale/nazionale/ecc.) restringa l'elenco alle sole opportunità con quel valore.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-502, AC "Filtri: ambito territoriale (TerritorialScope)".
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingOpportunityResourceTest.php` — `filtro ambito territoriale produce il sottoinsieme atteso`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingOpportunities\Tables\FundraisingOpportunitiesTable`.
- Test correlato: F5-10, F5-11.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Utente Fundraising.
- Opportunità con ambiti territoriali diversi nel dataset.

**Dati di test**
Opportunità reali con almeno due valori diversi di `territorial_scope`.

**Stato iniziale**
Elenco opportunità aperto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising e apri l'elenco Opportunità | sara.mariani@montagnaservizi.com | Elenco caricato |
| 2 | Applica il filtro "Ambito territoriale" su un valore specifico | es. Nazionale | Solo le opportunità con quell'ambito restano in elenco |

**Risultato finale atteso**
L'elenco filtrato contiene esclusivamente opportunità con l'ambito territoriale selezionato.

**Controlli negativi**
Nessuna opportunità con ambito diverso resta visibile dopo il filtro.

**Evidenze da acquisire**
- Screenshot dell'elenco filtrato.

**Criterio di superamento**

PASS: L'elenco filtrato contiene esclusivamente opportunità con l'ambito territoriale selezionato.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere il filtro al termine del test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F5-10 — Il filtro cofinanziamento con/senza quota produce il sottoinsieme atteso di opportunità

**Obiettivo**
Verificare che il filtro ternario "cofinanziamento" distingua correttamente le opportunità con `cofinancing_quota` valorizzata da quelle senza.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-502, AC "Filtri: cofinanziamento (con/senza quota)".
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingOpportunityResourceTest.php` — `filtro cofinanziamento con/senza quota produce il sottoinsieme atteso`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingOpportunities\Tables\FundraisingOpportunitiesTable`.
- Test correlato: F5-09, F5-11.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Utente Fundraising.
- Opportunità con e senza `cofinancing_quota` nel dataset.

**Dati di test**
Opportunità reali, alcune con quota di cofinanziamento impostata, altre senza.

**Stato iniziale**
Elenco opportunità aperto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising e apri l'elenco Opportunità | sara.mariani@montagnaservizi.com | Elenco caricato |
| 2 | Applica il filtro "Cofinanziamento" su "Con quota" | — | Solo opportunità con quota impostata restano in elenco |
| 3 | Applica il filtro su "Senza quota" | — | Solo opportunità senza quota restano in elenco |

**Risultato finale atteso**
Ciascuna posizione del filtro produce esattamente il sottoinsieme atteso.

**Controlli negativi**
Nessuna opportunità nel sottoinsieme errato in entrambe le posizioni del filtro.

**Evidenze da acquisire**
- Screenshot dell'elenco per ciascuna posizione del filtro.

**Criterio di superamento**

PASS: Ciascuna posizione del filtro produce esattamente il sottoinsieme atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere il filtro al termine del test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F5-11 — Il filtro scaduto/attivo produce il sottoinsieme atteso di opportunità

**Obiettivo**
Verificare che il filtro ternario scaduto/attivo produca lo stesso sottoinsieme delle vista elenco/Archivio (F5-08), ma applicabile come filtro combinabile con altri.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-502, AC "Filtri: ... scaduto/attivo".
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingOpportunityResourceTest.php` — `filtro scaduto/attivo produce il sottoinsieme atteso`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingOpportunities\Tables\FundraisingOpportunitiesTable`.
- Test correlato: F5-08, F5-09, F5-10.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Utente Fundraising.
- Opportunità attive e scadute nel dataset.

**Dati di test**
Opportunità reali sia attive sia scadute.

**Stato iniziale**
Elenco opportunità aperto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising e apri l'elenco Opportunità | sara.mariani@montagnaservizi.com | Elenco caricato |
| 2 | Applica il filtro su "Attivo" | — | Solo opportunità non scadute restano in elenco |
| 3 | Applica il filtro su "Scaduto" | — | Solo opportunità scadute restano in elenco |

**Risultato finale atteso**
Ciascuna posizione del filtro produce esattamente il sottoinsieme atteso.

**Controlli negativi**
Nessuna opportunità nel sottoinsieme errato in entrambe le posizioni del filtro.

**Evidenze da acquisire**
- Screenshot dell'elenco per ciascuna posizione del filtro.

**Criterio di superamento**

PASS: Ciascuna posizione del filtro produce esattamente il sottoinsieme atteso.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere il filtro al termine del test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F5-12 — created_by si valorizza automaticamente con l'utente autenticato e non è più alterabile

**Obiettivo**
Verificare che, creando un'opportunità, `created_by` sia impostato automaticamente all'utente autenticato (mai un campo di form scrivibile) e che modificarla successivamente non alteri mai quel valore, mostrato in sola lettura via Placeholder.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-502, AC "created_by valorizzato automaticamente dall'utente autenticato alla creazione, mai editabile in un secondo momento". Riferimento identico ai test Pest `creare un'opportunità valorizza created_by con l'utente autenticato` e `modificare un'opportunità non altera mai created_by` dello stesso file (esclusi dal riferimento diretto qui sotto solo per l'apostrofo nella loro descrizione Pest, gotcha noto — vedi intestazione di `docs/collaudo/fase-5.php` — non per assenza di copertura automatica).
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingOpportunityResourceTest.php` (riferimento al file: la/le descrizione/i Pest pertinenti contengono un apostrofo che romperebbe il confronto byte-per-byte di `collaudo:verify-manifest`, vedi intestazione di `docs/collaudo/fase-5.php`; il/i test nel file sono realmente eseguiti e verdi).
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingOpportunities\Pages\CreateFundraisingOpportunity::handleRecordCreation()`.
- Test correlato: F5-44 (stesso principio sui progetti).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Utente Fundraising `sara.mariani@montagnaservizi.com`.

**Dati di test**
Un'opportunità nuova creata dal form standard.

**Stato iniziale**
Nessuna opportunità di test preesistente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising e crea una nuova opportunità con i campi obbligatori | sara.mariani@montagnaservizi.com | Opportunità creata, redirect all'elenco/dettaglio |
| 2 | Apri il dettaglio/edit dell'opportunità appena creata | — | Il campo "Creatore" mostra l'utente Fundraising, in sola lettura (Placeholder, non un input) |
| 3 | Modifica un altro campo (es. nome) e salva | — | Il campo "Creatore" resta invariato dopo il salvataggio |

**Risultato finale atteso**
created_by resta sempre l'utente che ha creato l'opportunità, mai alterabile da un salvataggio successivo.

**Controlli negativi**
Il form non espone "Creatore" come campo scrivibile in nessuna delle due pagine (create/edit).

**Evidenze da acquisire**
- Screenshot del Placeholder "Creatore" sulla pagina Edit.

**Criterio di superamento**

PASS: created_by resta sempre l'utente che ha creato l'opportunità, mai alterabile da un salvataggio successivo.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere l'opportunità di test creata, se non reale.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F5-13 — Le azioni "Crea progetto" e "Crea ticket" da un'opportunità creano il record collegato correttamente

**Obiettivo**
Verificare che l'azione "Crea progetto" sulla pagina di un'opportunità crei un `FundraisingProject` con `fundraising_opportunity_id` e `title` precompilati (editabile prima del salvataggio), e che l'azione "Crea ticket" crei un `Ticket` con `fundraising_project_id` valorizzato SOLO se un progetto è già stato collegato dall'azione precedente; entrambe le azioni devono restare nascoste a chi non ha, rispettivamente, `fundraising.create`/`ticket.create`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-505, AC "Azione Filament 'Crea progetto' ... title precompilato dal nome dell'opportunità (editabile)"; AC "Azione Filament 'Crea ticket' ... fundraising_project_id valorizzato solo se l'opportunità ha già un progetto collegato". Riferimento al file (non a una singola descrizione Pest: le 5 descrizioni del file contengono un apostrofo che romperebbe il confronto byte-per-byte di `collaudo:verify-manifest`, vedi intestazione di `docs/collaudo/fase-5.php`) — tutti e 5 i test del file sono realmente eseguiti e verdi.
- Test automatico: `tests/Feature/Filament/Fundraising/CreateProjectAndTicketActionsTest.php` (riferimento al file: la/le descrizione/i Pest pertinenti contengono un apostrofo che romperebbe il confronto byte-per-byte di `collaudo:verify-manifest`, vedi intestazione di `docs/collaudo/fase-5.php`; il/i test nel file sono realmente eseguiti e verdi).
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingOpportunities\Support\CreateFundraisingProjectAction, App\Filament\Resources\FundraisingOpportunities\Support\CreateTicketFromOpportunityAction`.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Utente Fundraising con `fundraising.create` e `ticket.create`.
- Un'opportunità esistente.

**Dati di test**
Un'opportunità reale del dataset UAT.

**Stato iniziale**
Nessun progetto/ticket collegato all'opportunità scelta.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising e apri un'opportunità | sara.mariani@montagnaservizi.com | Pagina di dettaglio caricata, azioni "Crea progetto"/"Crea ticket" visibili |
| 2 | Esegui "Crea progetto" | Titolo precompilato dal nome dell'opportunità, modificabile | Progetto creato, collegato all'opportunità |
| 3 | Esegui "Crea ticket" | — | Ticket creato con `fundraising_project_id` valorizzato al progetto appena creato |

**Risultato finale atteso**
Progetto e ticket creati con i collegamenti attesi (opportunità->progetto, progetto->ticket).

**Controlli negativi**
Con un utente privo di `fundraising.create`/`ticket.create`, le rispettive azioni non sono visibili sulla stessa pagina.

**Evidenze da acquisire**
- Screenshot della modale "Crea progetto" precompilata.
- Screenshot del ticket creato con il progetto collegato.

**Criterio di superamento**

PASS: Progetto e ticket creati con i collegamenti attesi (opportunità->progetto, progetto->ticket).
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere progetto/ticket di test creati.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

## Griglia di valutazione (US-503/504)

### F5-14 — Il catalogo contiene esattamente i 26 criteri di §6.6.2, sui 5 blocchi previsti

**Obiettivo**
Verificare che l'enum `FundraisingEvaluationCriterion` contenga esattamente i 26 criteri previsti dal PRD (6 principali + 5 requisiti base + 7 qualitativi + 4 premiali + 4 di rischio), né uno di più né uno di meno.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-503, AC "Catalogo criteri ... i 5 blocchi di §6.6.2".
- Test automatico: `tests/Unit/Domain/Fundraising/FundraisingEvaluationCriterionTest.php` — `contains exactly the 26 criteria of §6.6.2`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion`.
- Test correlato: F5-15, F5-16.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "contains exactly the 26 criteria of §6.6.2"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il catalogo contiene esattamente i 26 criteri di §6.6.2, sui 5 blocchi previsti

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

### F5-15 — I criteri principali hanno range di punteggio 0-5

**Obiettivo**
Verificare che ciascuno dei 6 criteri del blocco "Criteri principali" dichiari `min() === 0` e `max() === 5`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-503, AC "Criteri principali (criterion_a..criterion_f, range 0–5)".
- Test automatico: `tests/Unit/Domain/Fundraising/FundraisingEvaluationCriterionTest.php` — `main criteria range 0 to 5`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion::min()/max()`.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "main criteria range 0 to 5"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
I criteri principali hanno range di punteggio 0-5

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

### F5-16 — I criteri del blocco Rischi consentono punteggi negativi, unico blocco a farlo

**Obiettivo**
Verificare che solo i 4 criteri del blocco Rischi abbiano un `min()` negativo (risk_finanziari -3, risk_organizzativi/-logistici -2), mentre nessun criterio degli altri 4 blocchi lo consenta.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-503, AC "Rischi (risk_tecnici 0–3, risk_finanziari -3..3, risk_organizzativi -2..2, risk_logistici -2..2)".
- Test automatico: `tests/Unit/Domain/Fundraising/FundraisingEvaluationCriterionTest.php` — `risk criteria allow negative scores per §6.6.2`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion::min()`.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "risk criteria allow negative scores per §6.6.2"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
I criteri del blocco Rischi consentono punteggi negativi, unico blocco a farlo

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

### F5-17 — CalculateEvaluationTotals somma nel totale positivo solo i punteggi >= 0

**Obiettivo**
Verificare che `CalculateEvaluationTotals::fromScores()` somma nel totale positivo esclusivamente i punteggi >= 0, ignorando quelli negativi.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-503, AC "evaluation_positive_total = somma di tutti i punteggi >= 0".
- Test automatico: `tests/Unit/Domain/Fundraising/CalculateEvaluationTotalsTest.php` — `sums only positive scores into the positive total`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Services\CalculateEvaluationTotals::fromScores()`.
- Test correlato: F5-18, F5-19.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sums only positive scores into the positive total"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
CalculateEvaluationTotals somma nel totale positivo solo i punteggi >= 0

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

### F5-18 — CalculateEvaluationTotals somma nel totale negativo il valore assoluto dei punteggi < 0

**Obiettivo**
Verificare che il totale negativo sia la somma dei valori assoluti dei punteggi < 0 (mai un totale negativo esposto come numero negativo).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-503, AC "evaluation_negative_total = somma dei valori assoluti dei punteggi < 0".
- Test automatico: `tests/Unit/Domain/Fundraising/CalculateEvaluationTotalsTest.php` — `sums the absolute value of negative scores into the negative total`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Services\CalculateEvaluationTotals::fromScores()`.
- Test correlato: F5-17, F5-19.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sums the absolute value of negative scores into the negative total"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
CalculateEvaluationTotals somma nel totale negativo il valore assoluto dei punteggi < 0

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

### F5-19 — Il totale complessivo è positivo meno negativo

**Obiettivo**
Verificare che `evaluation_total` sia sempre `positive - negative`, mai calcolato in altro modo (es. somma algebrica diretta dei punteggi, che darebbe lo stesso risultato numerico ma non è la formula documentata).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-503, AC "evaluation_total = positivo - negativo".
- Test automatico: `tests/Unit/Domain/Fundraising/CalculateEvaluationTotalsTest.php` — `total is positive minus negative`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Services\CalculateEvaluationTotals::fromScores()`.
- Test correlato: F5-17, F5-18.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "total is positive minus negative"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il totale complessivo è positivo meno negativo

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

### F5-20 — Il calcolo gestisce correttamente il valore minimo e massimo di ogni range del catalogo

**Obiettivo**
Verificare che il calcolo dei totali resti corretto quando ogni criterio riceve esattamente il proprio valore minimo o massimo di catalogo (nessun overflow/troncamento inatteso ai limiti del range).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-503, AC "Test unitari ... valori limite (min/max di ogni range)".
- Test automatico: `tests/Unit/Domain/Fundraising/CalculateEvaluationTotalsTest.php` — `handles the min and max value of every catalog range`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Services\CalculateEvaluationTotals::fromScores()`.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "handles the min and max value of every catalog range"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il calcolo gestisce correttamente il valore minimo e massimo di ogni range del catalogo

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

### F5-21 — Un criterio aggiunto al catalogo solo a runtime viene incluso correttamente nel calcolo dei totali

**Obiettivo**
Verificare che il service operi su una semplice mappa chiave => punteggio senza dipendere da un elenco fisso di criteri: una chiave non presente nel catalogo attuale viene comunque sommata correttamente, garantendo che estendere il catalogo (nuovo case enum) non richieda mai una migrazione né tocchi questo service.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-503, AC "un criterio aggiunto al catalogo runtime (senza toccare il DB) viene incluso correttamente nel calcolo"; è anche l'AC esplicito verificato manualmente in questa story (US-509, AC2) con un case enum temporaneo aggiunto e rimosso senza lasciare traccia (vedi progress.txt).
- Test automatico: `tests/Unit/Domain/Fundraising/CalculateEvaluationTotalsTest.php` — `a criterion added to the catalog at runtime is included correctly without touching the database`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Services\CalculateEvaluationTotals::fromScores()`.
- Test correlato: F5-59.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a criterion added to the catalog at runtime is included correctly without touching the database"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Un criterio aggiunto al catalogo solo a runtime viene incluso correttamente nel calcolo dei totali

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

### F5-22 — SaveEvaluationScores persiste una riga per criterio e calcola i totali da tutti i punteggi persistiti

**Obiettivo**
Verificare che l'action persista una riga `fundraising_evaluation_scores` per ciascun criterio passato e che i totali sull'opportunità siano calcolati sull'insieme COMPLETO dei punteggi persistiti (non solo quelli passati alla chiamata corrente), restando corretti anche con salvataggi parziali/incrementali della griglia.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-503, AC "Invocato dall'action di salvataggio dei punteggi".
- Test automatico: `tests/Feature/Domain/Fundraising/SaveEvaluationScoresTest.php` — `persists a score row per criterion and computes totals from all persisted scores`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Actions\SaveEvaluationScores::run()`.
- Test correlato: F5-58.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "persists a score row per criterion and computes totals from all persisted scores"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
SaveEvaluationScores persiste una riga per criterio e calcola i totali da tutti i punteggi persistiti

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

### F5-23 — Un punteggio sotto il minimo del catalogo per quel criterio viene rifiutato

**Obiettivo**
Verificare che l'action rifiuti (eccezione con messaggio leggibile) un punteggio inferiore al minimo ammesso per quel criterio dal catalogo, senza persistere nulla.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-503, AC "il range di ogni punteggio è validato dall'applicazione contro il catalogo, mai lasciato solo a un commento SQL (v1)".
- Test automatico: `tests/Feature/Domain/Fundraising/SaveEvaluationScoresTest.php` — `rejects a score below the catalog minimum`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Actions\SaveEvaluationScores::run()`.
- Test correlato: F5-24.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "rejects a score below the catalog minimum"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Un punteggio sotto il minimo del catalogo per quel criterio viene rifiutato

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

### F5-24 — Un punteggio sopra il massimo del catalogo per quel criterio viene rifiutato

**Obiettivo**
Verificare che l'action rifiuti un punteggio superiore al massimo ammesso per quel criterio dal catalogo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-503, AC "il range di ogni punteggio è validato dall'applicazione contro il catalogo".
- Test automatico: `tests/Feature/Domain/Fundraising/SaveEvaluationScoresTest.php` — `rejects a score above the catalog maximum`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Actions\SaveEvaluationScores::run()`.
- Test correlato: F5-23.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "rejects a score above the catalog maximum"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Un punteggio sopra il massimo del catalogo per quel criterio viene rifiutato

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

### F5-25 — evaluated_by/evaluated_at si valorizzano al primo punteggio salvato

**Obiettivo**
Verificare che il primo salvataggio di un punteggio su un'opportunità non ancora valutata valorizzi `evaluated_by`/`evaluated_at`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-503, AC "evaluated_by/evaluated_at si valorizzano quando viene salvato il primo punteggio di un'opportunità".
- Test automatico: `tests/Feature/Domain/Fundraising/SaveEvaluationScoresTest.php` — `sets evaluated_by and evaluated_at on the first saved score`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Actions\SaveEvaluationScores::run()`.
- Test correlato: F5-26.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sets evaluated_by and evaluated_at on the first saved score"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
evaluated_by/evaluated_at si valorizzano al primo punteggio salvato

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

### F5-26 — evaluated_by/evaluated_at non vengono mai sovrascritti dai salvataggi successivi al primo

**Obiettivo**
Verificare che un secondo salvataggio (anche da un valutatore diverso) non alteri `evaluated_by`/`evaluated_at` già impostati dal primo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-503, AC "mai sovrascritti da salvataggi successivi".
- Test automatico: `tests/Feature/Domain/Fundraising/SaveEvaluationScoresTest.php` — `does not overwrite evaluated_by/evaluated_at on subsequent saves`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Actions\SaveEvaluationScores::run()`.
- Test correlato: F5-25.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "does not overwrite evaluated_by/evaluated_at on subsequent saves"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
evaluated_by/evaluated_at non vengono mai sovrascritti dai salvataggi successivi al primo

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

### F5-27 — Compilare la griglia dalla pagina Edit persiste i punteggi e aggiorna i totali coerentemente col service

**Obiettivo**
Verificare che compilare la griglia di valutazione dal tab "Valutazione" della pagina Edit di un'opportunità persista i punteggi e aggiorni evaluation_positive_total/.negative_total/.total in tempo reale (reattivo) senza dover salvare e ricaricare, e che il salvataggio finale sia coerente col service di calcolo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-504, AC "Totale ... calcolato e mostrato in tempo reale mentre si compila (reattivo, senza submit)".
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingEvaluationGridTest.php` — `compilare la griglia dalla pagina Edit persiste i punteggi e aggiorna i totali coerentemente col service`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingOpportunities\Schemas\FundraisingEvaluationGridForm`.
- Test correlato: F5-22, F5-30.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Utente Fundraising con `fundraising.evaluate`.
- Un'opportunità esistente.

**Dati di test**
Un'opportunità reale non ancora valutata.

**Stato iniziale**
Nessun punteggio persistito per l'opportunità scelta.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising e apri un'opportunità in modifica | sara.mariani@montagnaservizi.com | Pagina Edit caricata, tab "Valutazione" visibile |
| 2 | Apri il tab "Valutazione" e inserisci un punteggio su un criterio | es. Criterio A = 4 | Il totale mostrato in cima si aggiorna immediatamente, senza salvare |
| 3 | Inserisci punteggi su altri criteri di blocchi diversi | es. un criterio Rischi negativo | Il totale (positivo/negativo/complessivo) si aggiorna ad ogni cambio |
| 4 | Salva il form | — | Notifica di salvataggio, i punteggi e i totali persistono al ricaricamento |

**Risultato finale atteso**
I totali mostrati in tempo reale e quelli persistiti dopo il salvataggio coincidono con quanto calcolerebbe CalculateEvaluationTotals sugli stessi punteggi.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della griglia con il totale aggiornato mentre si inseriscono i punteggi.
- Screenshot dopo il salvataggio.

**Criterio di superamento**

PASS: I totali mostrati in tempo reale e quelli persistiti dopo il salvataggio coincidono con quanto calcolerebbe CalculateEvaluationTotals sugli stessi punteggi.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno, salvo opportunità di puro test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F5-28 — Un punteggio fuori dal range del criterio produce un errore di validazione leggibile in UI

**Obiettivo**
Verificare che inserire un punteggio fuori dal range min/max del criterio produca un messaggio di errore leggibile in UI, senza salvare il valore non valido.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-504, AC "Validazione dei range direttamente in UI (min/max per criterio dal catalogo), messaggio d'errore leggibile su un valore fuori range".
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingEvaluationGridTest.php` — `un punteggio fuori dal range del criterio produce un errore di validazione leggibile`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingOpportunities\Schemas\FundraisingEvaluationGridForm`.
- Test correlato: F5-23, F5-24.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Utente Fundraising con `fundraising.evaluate`.

**Dati di test**
Un valore superiore al massimo ammesso per un criterio (es. 10 su un criterio principale, range 0-5).

**Stato iniziale**
Tab Valutazione aperto su un'opportunità.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising e apri il tab Valutazione di un'opportunità | sara.mariani@montagnaservizi.com | Griglia visibile |
| 2 | Inserisci un valore fuori range su un criterio | es. 10 su un criterio 0-5 | Messaggio di errore leggibile sotto il campo |
| 3 | Tenta di salvare | — | Salvataggio bloccato, errore mostrato |

**Risultato finale atteso**
Nessun punteggio fuori range viene persistito.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot del messaggio di errore.

**Criterio di superamento**

PASS: Nessun punteggio fuori range viene persistito.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F5-29 — Il tab "Valutazione" non è visibile a chi ha solo fundraising.update, senza fundraising.evaluate

**Obiettivo**
Verificare che il tab "Valutazione" (e quindi la possibilità di scrivere punteggi) resti riservato a chi ha `fundraising.evaluate`, distinto da `fundraising.update` (che consente solo di modificare i dati anagrafici dell'opportunità).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-504, nota tecnica: "fundraising.evaluate (distinto da fundraising.update)".
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingEvaluationGridTest.php` — `il tab Valutazione non è visibile a chi ha solo fundraising.update, senza fundraising.evaluate`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingOpportunities\Schemas\FundraisingOpportunityForm`.
- Test correlato: F5-27.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Un utente con `fundraising.update` ma senza `fundraising.evaluate` (predisposto via tinker/seed dedicato).

**Dati di test**
Utente di test con solo fundraising.update.

**Stato iniziale**
Un'opportunità esistente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'utente predisposto (solo fundraising.update) | — | Login riuscito |
| 2 | Apri la pagina Edit di un'opportunità | — | Il tab "Valutazione" non è presente |

**Risultato finale atteso**
Nessun accesso alla griglia di valutazione senza fundraising.evaluate.

**Controlli negativi**
Con fundraising.evaluate aggiunto, il tab torna visibile (vedi F5-27).

**Evidenze da acquisire**
- Screenshot della pagina Edit senza il tab Valutazione.

**Criterio di superamento**

PASS: Nessun accesso alla griglia di valutazione senza fundraising.evaluate.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere l'utente di test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F5-30 — La griglia riprende correttamente i punteggi già persistiti quando si riapre la pagina Edit

**Obiettivo**
Verificare che, riaprendo la pagina Edit di un'opportunità già valutata, la griglia mostri i punteggi già persistiti (idratati dalla relazione evaluationScores), non campi vuoti.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-504, comportamento implicito dell'idratazione mutateFormDataBeforeFill.
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingEvaluationGridTest.php` — `la griglia riprende i punteggi già persistiti quando si riapre la pagina Edit`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingOpportunities\Pages\EditFundraisingOpportunity::mutateFormDataBeforeFill()`.
- Test correlato: F5-27.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Utente Fundraising.
- Un'opportunità con punteggi già salvati (es. da F5-27).

**Dati di test**
Un'opportunità con almeno un punteggio già persistito.

**Stato iniziale**
Punteggi già salvati per l'opportunità scelta.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising e apri il tab Valutazione dell'opportunità già valutata | sara.mariani@montagnaservizi.com | I campi mostrano i punteggi già salvati, non vuoti |

**Risultato finale atteso**
I valori mostrati corrispondono esattamente a quelli persistiti.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della griglia con i valori precompilati.

**Criterio di superamento**

PASS: I valori mostrati corrispondono esattamente a quelli persistiti.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

## Progetti di fundraising (US-506/507)

### F5-31 — Ogni transizione ammessa della macchina a stati del progetto può essere eseguita

**Obiettivo**
Verificare che ciascuna delle transizioni ammesse (draft->submitted, submitted->approved, submitted->rejected, approved->completed) risulti permessa da `FundraisingProjectStateMachine::canTransitionTo()`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-506, AC "enum FundraisingProjectStatus (draft->submitted->approved/rejected->completed) e transizioni ESPLICITE".
- Test automatico: `tests/Unit/Domain/Fundraising/FundraisingProjectStateMachineTest.php` — `allowed transitions can be performed`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\StateMachine\FundraisingProjectStateMachine`.
- Test correlato: F5-32, F5-33, F5-60.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "allowed transitions can be performed"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Ogni transizione ammessa della macchina a stati del progetto può essere eseguita

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

### F5-32 — Ogni altra transizione non elencata in tabella è vietata

**Obiettivo**
Verificare che ogni transizione non esplicitamente elencata (es. draft->approved saltando submitted, o un ritorno indietro) sia vietata — nessuna transizione libera non in tabella.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-506, AC "stessa disciplina della macchina a stati dei ticket, Fase 1: nessuna transizione libera non in tabella".
- Test automatico: `tests/Unit/Domain/Fundraising/FundraisingProjectStateMachineTest.php` — `every other transition is forbidden`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\StateMachine\FundraisingProjectStateMachine`.
- Test correlato: F5-31.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "every other transition is forbidden"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Ogni altra transizione non elencata in tabella è vietata

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

### F5-33 — Gli stati terminali (rejected/completed) non hanno alcuna transizione uscente

**Obiettivo**
Verificare che, una volta in rejected o completed, il progetto non possa transitare verso nessun altro stato, nemmeno indirettamente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-506, AC "draft->submitted->approved/rejected->completed" (rejected/completed sono terminali).
- Test automatico: `tests/Unit/Domain/Fundraising/FundraisingProjectStateMachineTest.php` — `rejected and completed have no outgoing transition to any other status`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\StateMachine\FundraisingProjectStateMachine::transitions()`.
- Test correlato: F5-31, F5-32.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "rejected and completed have no outgoing transition to any other status"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Gli stati terminali (rejected/completed) non hanno alcuna transizione uscente

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

### F5-34 — scopeInvolving trova il progetto per capofila, partner, responsabile o creatore

**Obiettivo**
Verificare che lo scope "coinvolti" per lo staff (§6.6.3) includa il progetto quando l'utente è capofila (lead_user_id), partner (pivot), responsabile o creatore — qualunque di questi quattro ruoli basta.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-506/507, AC "'coinvolti' (capofila OR partner OR responsabile OR creatore, stessa definizione di US-506)".
- Test automatico: `tests/Feature/Domain/Fundraising/FundraisingProjectInvolvementTest.php` — `scopeInvolving trova il progetto per capofila, partner, responsabile o creatore`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Models\FundraisingProject::scopeInvolving()`.
- Test correlato: F5-43, F5-53.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "scopeInvolving trova il progetto per capofila, partner, responsabile o creatore"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
scopeInvolving trova il progetto per capofila, partner, responsabile o creatore

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

### F5-35 — partnerCustomers() restituisce solo i partner con ruolo customer

**Obiettivo**
Verificare il fix del bug v1 esplicito nel PRD: `partnerCustomers()` filtra i partner con ruolo customer tramite la relazione pivot Spatie `roles()` (join/whereHas normale), non tramite una query su colonna JSON come in v1 (query non eseguibile in v1).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-506, AC "Fix del bug v1 ... in v2 i ruoli sono un pivot (model_has_roles), quindi lo stesso filtro è una join/whereHas normale".
- Test automatico: `tests/Feature/Domain/Fundraising/FundraisingProjectInvolvementTest.php` — `partnerCustomers restituisce solo i partner con ruolo customer`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Models\FundraisingProject::partnerCustomers()`.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "partnerCustomers restituisce solo i partner con ruolo customer"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
partnerCustomers() restituisce solo i partner con ruolo customer

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

### F5-36 — FundraisingProjectPolicy verificata riga per riga per ogni ruolo (§9.4), caso non coinvolto

**Obiettivo**
Verificare, ruolo per ruolo e per un utente NON coinvolto nel progetto, che solo admin/fundraising abbiano viewAny/view/create/update/delete, mentre il customer non coinvolto non abbia nessuna di queste abilità.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-506, AC "stesso schema permessi di US-501: admin/fundraising pieno accesso, customer solo .view.involved sui progetti in cui è coinvolto".
- Test automatico: `tests/Feature/Domain/Fundraising/FundraisingProjectPolicyTest.php` — `FundraisingProjectPolicy per ruolo, riga per riga (§9.4), non coinvolto`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Policies\FundraisingProjectPolicy`.
- Test correlato: F5-37, F5-38.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "FundraisingProjectPolicy per ruolo, riga per riga \(§9.4\), non coinvolto"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
FundraisingProjectPolicy verificata riga per riga per ogni ruolo (§9.4), caso non coinvolto

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

### F5-37 — Un customer coinvolto come capofila vede il progetto ma non può scriverlo

**Obiettivo**
Verificare che un customer capofila abbia `view` sul progetto ma non `update`/`delete` — la vista cliente (US-508) è sempre di sola lettura, indipendentemente dal ruolo di coinvolgimento.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-506/US-508, AC "customer solo .view.involved"; "Nessuna azione di scrittura visibile/eseguibile da un cliente".
- Test automatico: `tests/Feature/Domain/Fundraising/FundraisingProjectPolicyTest.php` — `un customer coinvolto come capofila vede il progetto ma non può scriverlo`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Policies\FundraisingProjectPolicy`.
- Test correlato: F5-38, F5-54.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "un customer coinvolto come capofila vede il progetto ma non può scriverlo"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Un customer coinvolto come capofila vede il progetto ma non può scriverlo

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

### F5-38 — Un customer non coinvolto in nessun modo non vede il progetto, nemmeno via URL diretto

**Obiettivo**
Verificare che un customer che non è capofila, partner, responsabile né creatore non possa vedere il progetto, nemmeno manipolando direttamente l'URL con l'id del record.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-508, AC "Il dettaglio di un progetto in cui il cliente non è coinvolto non è raggiungibile nemmeno via URL diretto".
- Test automatico: `tests/Feature/Domain/Fundraising/FundraisingProjectPolicyTest.php` — `un customer non coinvolto in nessun modo non vede il progetto neanche via URL diretto`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Policies\FundraisingProjectPolicy`.
- Test correlato: F5-56.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "un customer non coinvolto in nessun modo non vede il progetto neanche via URL diretto"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Un customer non coinvolto in nessun modo non vede il progetto, nemmeno via URL diretto

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

### F5-39 — La Resource progetti è visibile in navigazione solo ad admin/fundraising, mai a manager/developer/customer

**Obiettivo**
Verificare che la voce di navigazione "Progetti" (fundraising) compaia solo per admin/fundraising e non per manager/developer/customer.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-506/507, stesso principio di F5-07 applicato ai progetti.
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingProjectResourceTest.php` — `FundraisingProjectResource visibility per ruolo (§9.4, mai manager/developer/customer)`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingProjects\FundraisingProjectResource`.
- Test correlato: F5-07, F5-36.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Credenziali dei 5 ruoli.

**Dati di test**
Nessuno specifico.

**Stato iniziale**
Nessun progetto necessario.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising/Admin | sara.mariani@montagnaservizi.com / info@montagnaservizi.com | Voce "Progetti" visibile |
| 2 | Accedi come Manager/Developer/Customer | manager@oc.test / lorena.sava@montagnaservizi.com / infosentieroitalia@cai.it | Nessuna voce "Progetti" in navigazione, accesso diretto via URL negato |

**Risultato finale atteso**
Solo admin/fundraising vedono e accedono alla Resource progetti.

**Controlli negativi**
Vedi passo 2.

**Evidenze da acquisire**
- Screenshot del menu per ciascun ruolo.

**Criterio di superamento**

PASS: Solo admin/fundraising vedono e accedono alla Resource progetti.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F5-40 — Il filtro per stato produce il sottoinsieme atteso di progetti

**Obiettivo**
Verificare che il filtro per stato (draft/submitted/approved/rejected/completed) restringa l'elenco progetti allo stato selezionato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-507, AC "Filtri: per stato".
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingProjectResourceTest.php` — `filtro stato produce il sottoinsieme atteso`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingProjects\Tables\FundraisingProjectsTable`.
- Test correlato: F5-41, F5-42, F5-43.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Utente Fundraising.
- Progetti in stati diversi nel dataset.

**Dati di test**
Progetti reali in almeno due stati diversi (es. draft e submitted, i due presenti nel dataset UAT importato).

**Stato iniziale**
Elenco progetti aperto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising e apri l'elenco Progetti | sara.mariani@montagnaservizi.com | Elenco caricato |
| 2 | Applica il filtro "Stato" su un valore | es. Presentato (submitted) | Solo i progetti in quello stato restano in elenco |

**Risultato finale atteso**
L'elenco filtrato contiene solo progetti nello stato selezionato.

**Controlli negativi**
Nessun progetto in altro stato resta visibile.

**Evidenze da acquisire**
- Screenshot dell'elenco filtrato.

**Criterio di superamento**

PASS: L'elenco filtrato contiene solo progetti nello stato selezionato.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere il filtro.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F5-41 — Il filtro per capofila produce il sottoinsieme atteso di progetti

**Obiettivo**
Verificare che il filtro per capofila (lead_user_id) restringa l'elenco ai soli progetti con quel capofila.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-507, AC "Filtri: ... per capofila".
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingProjectResourceTest.php` — `filtro capofila produce il sottoinsieme atteso`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingProjects\Tables\FundraisingProjectsTable`.
- Test correlato: F5-40, F5-42.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Utente Fundraising.
- Progetti con capofila diversi nel dataset.

**Dati di test**
Progetti reali con capofila diversi.

**Stato iniziale**
Elenco progetti aperto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising e apri l'elenco Progetti | sara.mariani@montagnaservizi.com | Elenco caricato |
| 2 | Applica il filtro "Capofila" su un utente specifico | — | Solo i progetti con quel capofila restano in elenco |

**Risultato finale atteso**
L'elenco filtrato contiene solo progetti con il capofila selezionato.

**Controlli negativi**
Nessun progetto con capofila diverso resta visibile.

**Evidenze da acquisire**
- Screenshot dell'elenco filtrato.

**Criterio di superamento**

PASS: L'elenco filtrato contiene solo progetti con il capofila selezionato.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere il filtro.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F5-42 — Il filtro per partner produce il sottoinsieme atteso di progetti

**Obiettivo**
Verificare che il filtro per partner restringa l'elenco ai soli progetti che hanno quell'utente come partner (pivot).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-507, AC "Filtri: ... per partner".
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingProjectResourceTest.php` — `filtro partner produce il sottoinsieme atteso`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingProjects\Tables\FundraisingProjectsTable`.
- Test correlato: F5-40, F5-41, F5-45.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Utente Fundraising.
- Progetti con partner diversi nel dataset.

**Dati di test**
Progetti reali con partner diversi collegati.

**Stato iniziale**
Elenco progetti aperto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising e apri l'elenco Progetti | sara.mariani@montagnaservizi.com | Elenco caricato |
| 2 | Applica il filtro "Partner" su un utente specifico | — | Solo i progetti con quel partner restano in elenco |

**Risultato finale atteso**
L'elenco filtrato contiene solo progetti con il partner selezionato.

**Controlli negativi**
Nessun progetto senza quel partner resta visibile.

**Evidenze da acquisire**
- Screenshot dell'elenco filtrato.

**Criterio di superamento**

PASS: L'elenco filtrato contiene solo progetti con il partner selezionato.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere il filtro.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F5-43 — Il filtro "coinvolti" produce il sottoinsieme atteso di progetti

**Obiettivo**
Verificare che il filtro "coinvolti" (toggle) restringa l'elenco ai progetti in cui l'utente corrente è capofila, partner, responsabile o creatore — riusa la stessa definizione di scopeInvolving (F5-34).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-507, AC "Filtri: ... 'coinvolti' (capofila OR partner OR responsabile OR creatore, stessa definizione di US-506)".
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingProjectResourceTest.php` — `filtro coinvolti produce il sottoinsieme atteso (capofila OR partner OR responsabile OR creatore)`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingProjects\Tables\FundraisingProjectsTable`.
- Test correlato: F5-34, F5-40, F5-41, F5-42.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Utente Fundraising coinvolto in almeno un progetto come uno dei quattro ruoli.

**Dati di test**
Progetti in cui l'utente di test è coinvolto e progetti in cui non lo è.

**Stato iniziale**
Elenco progetti aperto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising e apri l'elenco Progetti | sara.mariani@montagnaservizi.com | Elenco caricato |
| 2 | Attiva il filtro/toggle "Coinvolti" | — | Solo i progetti in cui l'utente è coinvolto restano in elenco |

**Risultato finale atteso**
L'elenco filtrato contiene solo progetti in cui l'utente corrente è coinvolto.

**Controlli negativi**
Nessun progetto non-coinvolto resta visibile con il filtro attivo.

**Evidenze da acquisire**
- Screenshot dell'elenco con il filtro attivo.

**Criterio di superamento**

PASS: L'elenco filtrato contiene solo progetti in cui l'utente corrente è coinvolto.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Disattivare il filtro.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F5-44 — created_by si valorizza automaticamente con l'utente autenticato alla creazione di un progetto

**Obiettivo**
Verificare che, creando un progetto dal form standard della Resource progetti, `created_by` sia impostato automaticamente all'utente autenticato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-507, stesso principio di F5-12 applicato ai progetti. Riferimento al file (non alla singola descrizione Pest `creare un progetto valorizza created_by con l'utente autenticato`, esclusa per l'apostrofo — gotcha noto, vedi intestazione di `docs/collaudo/fase-5.php`).
- Test automatico: `tests/Feature/Filament/Fundraising/FundraisingProjectResourceTest.php` (riferimento al file: la/le descrizione/i Pest pertinenti contengono un apostrofo che romperebbe il confronto byte-per-byte di `collaudo:verify-manifest`, vedi intestazione di `docs/collaudo/fase-5.php`; il/i test nel file sono realmente eseguiti e verdi).
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingProjects\Pages\CreateFundraisingProject`.
- Test correlato: F5-12, F5-13.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Utente Fundraising con `fundraising.create`.

**Dati di test**
Un progetto nuovo creato dal form standard.

**Stato iniziale**
Nessun progetto di test preesistente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising e crea un nuovo progetto dal form standard | sara.mariani@montagnaservizi.com | Progetto creato |
| 2 | Apri il dettaglio/edit del progetto appena creato | — | Il campo "Creatore" mostra l'utente Fundraising |

**Risultato finale atteso**
created_by corrisponde all'utente che ha creato il progetto.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot del campo "Creatore".

**Criterio di superamento**

PASS: created_by corrisponde all'utente che ha creato il progetto.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere il progetto di test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F5-45 — Un utente fundraising può aggiungere e rimuovere un partner dal progetto

**Obiettivo**
Verificare che il relation manager "Partner" sulla pagina Edit del progetto permetta di collegare (AttachAction) e rimuovere (DetachAction) un utente partner.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-507, AC "Gestione partner (aggiungi/rimuovi utenti) sulla vista/edit del progetto".
- Test automatico: `tests/Feature/Filament/Fundraising/PartnersRelationManagerTest.php` — `un utente fundraising può aggiungere e rimuovere un partner dal progetto`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingProjects\RelationManagers\PartnersRelationManager`.
- Test correlato: F5-42.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Fundraising

**Prerequisiti**
- Utente Fundraising con `fundraising.update`.
- Un progetto esistente.

**Dati di test**
Un progetto senza partner e un utente da collegare come partner.

**Stato iniziale**
Nessun partner collegato al progetto scelto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising e apri un progetto in modifica | sara.mariani@montagnaservizi.com | Pagina Edit caricata, relation manager "Partner" visibile |
| 2 | Esegui "Collega" (Attach) su un utente | — | L'utente compare nell'elenco Partner |
| 3 | Esegui "Rimuovi" (Detach) sullo stesso utente | — | L'utente non compare più nell'elenco Partner |

**Risultato finale atteso**
Il partner viene collegato e rimosso correttamente dalla pivot fundraising_project_partners.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot prima/dopo l'aggiunta e la rimozione del partner.

**Criterio di superamento**

PASS: Il partner viene collegato e rimosso correttamente dalla pivot fundraising_project_partners.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno, lo stato finale coincide con quello iniziale.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F5-46 — Un ticket esistente può essere collegato a un progetto di fundraising

**Obiettivo**
Verificare che un ticket possa essere collegato a un progetto di fundraising impostando `tickets.fundraising_project_id` dal campo select sulla scheda ticket, ristretto ai progetti visibili secondo FundraisingProjectPolicy — il collegamento si fa sempre dal lato ticket, mai un'azione "aggiungi ticket" dal lato progetto.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-507, AC "tickets.fundraising_project_id collegabile da TicketResource ... non il contrario".
- Test automatico: `tests/Feature/Domain/Fundraising/FundraisingProjectsTableTest.php` — `a ticket can be linked to a fundraising project`.
- File/componente applicativo rilevante: `App\Filament\Resources\Tickets\Schemas\TicketForm`.
- Test correlato: F5-13.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Utente Developer.
- Un ticket e un progetto di fundraising esistenti.

**Dati di test**
Un ticket esistente senza progetto collegato, un progetto di fundraising visibile all'utente.

**Stato iniziale**
Ticket senza fundraising_project_id.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer e apri la scheda di un ticket | lorena.sava@montagnaservizi.com | Sezione interna con il campo "Progetto fundraising" visibile |
| 2 | Seleziona un progetto dalla select e salva | — | Il ticket risulta collegato al progetto selezionato |

**Risultato finale atteso**
tickets.fundraising_project_id persiste il progetto selezionato.

**Controlli negativi**
La select mostra solo i progetti visibili all'utente secondo la Policy, mai tutti indistintamente.

**Evidenze da acquisire**
- Screenshot del campo compilato e del salvataggio riuscito.

**Criterio di superamento**

PASS: tickets.fundraising_project_id persiste il progetto selezionato.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere il collegamento se solo di test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

## Vista cliente (US-508)

### F5-47 — CustomerFundraisingOpportunityResource è visibile in navigazione solo al ruolo customer

**Obiettivo**
Verificare che la Resource cliente delle opportunità sia visibile in navigazione solo per il ruolo customer, e non per admin/developer/manager/fundraising (che usano invece la Resource staff, F5-07).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-508, AC "Un utente customer vede, in sola lettura ... l'elenco e dettaglio delle opportunità"; nota tecnica "canViewAny(): has(view.involved) AND NOT has(view.any)".
- Test automatico: `tests/Feature/Filament/Fundraising/CustomerFundraisingOpportunityResourceTest.php` — `CustomerFundraisingOpportunityResource visibility per ruolo (§6.6.4, SOLO customer)`.
- File/componente applicativo rilevante: `App\Filament\Resources\CustomerFundraisingOpportunities\CustomerFundraisingOpportunityResource`.
- Test correlato: F5-07, F5-50.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali dei 5 ruoli.

**Dati di test**
Nessuno specifico.

**Stato iniziale**
Nessuna opportunità necessaria.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it | Voce di navigazione "Opportunità" (vista cliente) visibile |
| 2 | Accedi come Admin/Developer/Manager/Fundraising | — | La voce di navigazione cliente non compare (questi ruoli vedono, se del caso, solo la Resource staff F5-07) |

**Risultato finale atteso**
Solo il ruolo customer vede la Resource cliente delle opportunità.

**Controlli negativi**
Vedi passo 2.

**Evidenze da acquisire**
- Screenshot del menu per ciascun ruolo.

**Criterio di superamento**

PASS: Solo il ruolo customer vede la Resource cliente delle opportunità.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F5-48 — Qualunque customer vede qualunque opportunità nell'elenco e ne apre il dettaglio in sola lettura

**Obiettivo**
Verificare che qualunque customer autenticato veda qualunque opportunità nell'elenco cliente (nessuna differenza attive/scadute, a differenza della vista staff, §6.6.4 non lo richiede) e possa aprirne il dettaglio, sempre in sola lettura.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-508, AC "nessuna differenza tra attive/scadute in questa vista, §6.6.4 non lo richiede". Riferimento al file (non alle singole descrizioni Pest `qualunque customer autenticato vede qualunque opportunità nell'elenco...`/`il dettaglio di un'opportunità è raggiungibile...`, escluse per l'apostrofo — gotcha noto).
- Test automatico: `tests/Feature/Filament/Fundraising/CustomerFundraisingOpportunityResourceTest.php` (riferimento al file: la/le descrizione/i Pest pertinenti contengono un apostrofo che romperebbe il confronto byte-per-byte di `collaudo:verify-manifest`, vedi intestazione di `docs/collaudo/fase-5.php`; il/i test nel file sono realmente eseguiti e verdi).
- File/componente applicativo rilevante: `App\Filament\Resources\CustomerFundraisingOpportunities\Pages\ListCustomerFundraisingOpportunities`.
- Test correlato: F5-08.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Utente Customer.

**Dati di test**
Opportunità reali sia attive sia scadute nel dataset.

**Stato iniziale**
Nessuno specifico.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer e apri l'elenco Opportunità | infosentieroitalia@cai.it | Sia opportunità attive sia scadute sono elencate, senza distinzione di tab/archivio |
| 2 | Apri il dettaglio di un'opportunità | — | Dettaglio visualizzato in sola lettura, nessuna azione di scrittura disponibile |

**Risultato finale atteso**
L'elenco cliente non distingue attive/scadute e il dettaglio è sempre accessibile in sola lettura a qualunque customer.

**Controlli negativi**
Nessuna azione di creazione/modifica/eliminazione visibile nella vista cliente.

**Evidenze da acquisire**
- Screenshot dell'elenco e del dettaglio.

**Criterio di superamento**

PASS: L'elenco cliente non distingue attive/scadute e il dettaglio è sempre accessibile in sola lettura a qualunque customer.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F5-49 — CustomerFundraisingOpportunityResource non registra alcuna pagina di scrittura

**Obiettivo**
Verificare che `getPages()` della Resource cliente registri solo index+view, mai create/edit/delete — sola lettura reale a livello di routing, non solo azioni nascoste in UI.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-508, AC "Nessuna azione di scrittura visibile/eseguibile da un cliente ... sola lettura reale, non solo azioni nascoste in UI".
- Test automatico: `tests/Feature/Filament/Fundraising/CustomerFundraisingOpportunityResourceTest.php` — `CustomerFundraisingOpportunityResource non registra pagine di scrittura`.
- File/componente applicativo rilevante: `App\Filament\Resources\CustomerFundraisingOpportunities\CustomerFundraisingOpportunityResource::getPages()`.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "CustomerFundraisingOpportunityResource non registra pagine di scrittura"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
CustomerFundraisingOpportunityResource non registra alcuna pagina di scrittura

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

### F5-50 — La Resource opportunità riservata allo staff resta invisibile a un customer

**Obiettivo**
Verificare che introdurre la Resource cliente (US-508) non alteri in alcun modo la visibilità della Resource staff (US-502), che deve restare invisibile a un customer.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-508, verifica di non regressione su US-502.
- Test automatico: `tests/Feature/Filament/Fundraising/CustomerFundraisingOpportunityResourceTest.php` — `la Resource staff resta invisibile a un customer (US-502, invariato da questa story)`.
- File/componente applicativo rilevante: `App\Filament\Resources\FundraisingOpportunities\FundraisingOpportunityResource`.
- Test correlato: F5-07.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "la Resource staff resta invisibile a un customer \(US-502, invariato da questa story\)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
La Resource opportunità riservata allo staff resta invisibile a un customer

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

### F5-51 — CustomerFundraisingProjectResource è visibile in navigazione solo al ruolo customer

**Obiettivo**
Verificare che la Resource cliente dei progetti sia visibile in navigazione solo per il ruolo customer.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-508, stesso principio di F5-47 applicato ai progetti.
- Test automatico: `tests/Feature/Filament/Fundraising/CustomerFundraisingProjectResourceTest.php` — `CustomerFundraisingProjectResource visibility per ruolo (§6.6.4, SOLO customer)`.
- File/componente applicativo rilevante: `App\Filament\Resources\CustomerFundraisingProjects\CustomerFundraisingProjectResource`.
- Test correlato: F5-39, F5-57.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Customer

**Prerequisiti**
- Credenziali dei 5 ruoli.

**Dati di test**
Nessuno specifico.

**Stato iniziale**
Nessun progetto necessario.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | infosentieroitalia@cai.it | Voce di navigazione "Progetti" (vista cliente) visibile |
| 2 | Accedi come Admin/Developer/Manager/Fundraising | — | La voce di navigazione cliente non compare |

**Risultato finale atteso**
Solo il ruolo customer vede la Resource cliente dei progetti.

**Controlli negativi**
Vedi passo 2.

**Evidenze da acquisire**
- Screenshot del menu per ciascun ruolo.

**Criterio di superamento**

PASS: Solo il ruolo customer vede la Resource cliente dei progetti.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F5-52 — Un customer capofila o partner vede il proprio progetto nell'elenco, uno non coinvolto no

**Obiettivo**
Verificare che l'elenco cliente dei progetti mostri solo i progetti in cui il customer è capofila o partner, ed escluda sia i progetti non coinvolti sia quelli in cui è solo responsabile/creatore (ruoli interni allo staff, F5-54).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-508, AC "i progetti in cui sono coinvolto (capofila o partner — non 'responsabile'/'creatore')". Riferimento al file (non alle singole descrizioni Pest, escluse per l'apostrofo — gotcha noto).
- Test automatico: `tests/Feature/Filament/Fundraising/CustomerFundraisingProjectResourceTest.php` (riferimento al file: la/le descrizione/i Pest pertinenti contengono un apostrofo che romperebbe il confronto byte-per-byte di `collaudo:verify-manifest`, vedi intestazione di `docs/collaudo/fase-5.php`; il/i test nel file sono realmente eseguiti e verdi).
- File/componente applicativo rilevante: `App\Domain\Fundraising\Models\FundraisingProject::scopeInvolvingAsCustomer()`.
- Test correlato: F5-53, F5-54.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Un customer capofila di un progetto, un customer partner di un altro, un customer non coinvolto in nessuno.

**Dati di test**
Almeno 3 progetti: uno con il customer come capofila, uno con il customer come partner, uno senza alcun collegamento al customer.

**Stato iniziale**
Coinvolgimenti predisposti come sopra.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come il customer capofila e apri l'elenco Progetti | infosentieroitalia@cai.it | Il progetto di cui è capofila è in elenco |
| 2 | Verifica che il progetto non coinvolto non sia in elenco | — | Il progetto non coinvolto non compare |
| 3 | Accedi come il customer partner e apri l'elenco Progetti | — | Il progetto di cui è partner è in elenco |

**Risultato finale atteso**
L'elenco cliente mostra esclusivamente i progetti in cui il customer è capofila o partner.

**Controlli negativi**
Nessun progetto non coinvolto (o coinvolto solo come responsabile/creatore) compare nell'elenco.

**Evidenze da acquisire**
- Screenshot dell'elenco per ciascun customer di test.

**Criterio di superamento**

PASS: L'elenco cliente mostra esclusivamente i progetti in cui il customer è capofila o partner.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F5-53 — scopeInvolvingAsCustomer trova il progetto SOLO per capofila o partner

**Obiettivo**
Verificare che lo scope usato dalla vista cliente sia più restrittivo dello scope staff (F5-34): SOLO capofila o partner, mai responsabile o creatore, anche se questi ultimi coincidono con un utente customer.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-508, AC "i progetti in cui sono coinvolto (capofila o partner — non 'responsabile'/'creatore', quei ruoli sono interni allo staff, §6.6.4 li limita esplicitamente)".
- Test automatico: `tests/Feature/Domain/Fundraising/FundraisingProjectInvolvementTest.php` — `scopeInvolvingAsCustomer trova il progetto SOLO per capofila o partner, mai responsabile o creatore (§6.6.4)`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Models\FundraisingProject::scopeInvolvingAsCustomer()`.
- Test correlato: F5-34, F5-52, F5-54.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "scopeInvolvingAsCustomer trova il progetto SOLO per capofila o partner, mai responsabile o creatore \(§6.6.4\)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
scopeInvolvingAsCustomer trova il progetto SOLO per capofila o partner

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

### F5-54 — Essere solo responsabile o creatore non basta a far vedere il progetto a un customer

**Obiettivo**
Verificare a livello di Resource cliente (non solo di scope, F5-53) che un customer impostato come responsabile o creatore di un progetto — situazione anomala per un customer, ma non impedita a livello di schema — non lo veda comunque nell'elenco/dettaglio cliente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-508, stesso AC di F5-53, verificato end-to-end sulla Resource.
- Test automatico: `tests/Feature/Filament/Fundraising/CustomerFundraisingProjectResourceTest.php` — `responsabile/creatore da soli NON bastano a far vedere il progetto a un customer (§6.6.4)`.
- File/componente applicativo rilevante: `App\Filament\Resources\CustomerFundraisingProjects\CustomerFundraisingProjectResource`.
- Test correlato: F5-53, F5-37.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "responsabile/creatore da soli NON bastano a far vedere il progetto a un customer \(§6.6.4\)"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Essere solo responsabile o creatore non basta a far vedere il progetto a un customer

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

### F5-55 — Il dettaglio di un progetto è raggiungibile da un customer coinvolto

**Obiettivo**
Verificare che un customer coinvolto (capofila o partner) possa apre il dettaglio del progetto in sola lettura.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-508, AC "Un utente customer vede, in sola lettura ... i progetti in cui è coinvolto".
- Test automatico: `tests/Feature/Filament/Fundraising/CustomerFundraisingProjectResourceTest.php` — `il dettaglio è raggiungibile da un customer coinvolto`.
- File/componente applicativo rilevante: `App\Filament\Resources\CustomerFundraisingProjects\Pages\ViewCustomerFundraisingProject`.
- Test correlato: F5-52.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Un customer capofila o partner di un progetto reale.

**Dati di test**
Un progetto con il customer come capofila o partner.

**Stato iniziale**
Coinvolgimento già predisposto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come il customer coinvolto e apri il dettaglio del proprio progetto dall'elenco | infosentieroitalia@cai.it | Dettaglio visualizzato correttamente, in sola lettura |

**Risultato finale atteso**
Il dettaglio è raggiungibile e non espone alcuna azione di scrittura.

**Controlli negativi**
Nessuna azione di modifica/eliminazione disponibile nella pagina.

**Evidenze da acquisire**
- Screenshot del dettaglio.

**Criterio di superamento**

PASS: Il dettaglio è raggiungibile e non espone alcuna azione di scrittura.
FAIL: il comportamento osservato non corrisponde al risultato atteso.
BLOCKED: ambiente UAT non raggiungibile, o prerequisiti non soddisfatti.
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

### F5-56 — Il dettaglio di un progetto non coinvolto non è raggiungibile via URL diretto

**Obiettivo**
Verificare che un customer non coinvolto in un progetto riceva un errore (404/403) tentando di aprirne il dettaglio via URL diretto con l'id del record, anche senza passare dall'elenco.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-508, AC "Il dettaglio di un progetto in cui il cliente non è coinvolto non è raggiungibile nemmeno via URL diretto".
- Test automatico: `tests/Feature/Filament/Fundraising/CustomerFundraisingProjectResourceTest.php` — `il dettaglio di un progetto in cui il customer non è coinvolto non è raggiungibile via URL diretto`.
- File/componente applicativo rilevante: `App\Filament\Resources\CustomerFundraisingProjects\Pages\ViewCustomerFundraisingProject`.
- Test correlato: F5-38.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "il dettaglio di un progetto in cui il customer non è coinvolto non è raggiungibile via URL diretto"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il dettaglio di un progetto non coinvolto non è raggiungibile via URL diretto

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

### F5-57 — CustomerFundraisingProjectResource non registra alcuna pagina di scrittura

**Obiettivo**
Verificare che `getPages()` della Resource cliente dei progetti registri solo index+view, mai create/edit/delete.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-508, AC "sola lettura reale, non solo azioni nascoste in UI".
- Test automatico: `tests/Feature/Filament/Fundraising/CustomerFundraisingProjectResourceTest.php` — `CustomerFundraisingProjectResource non registra pagine di scrittura`.
- File/componente applicativo rilevante: `App\Filament\Resources\CustomerFundraisingProjects\CustomerFundraisingProjectResource::getPages()`.
- Test correlato: F5-49.

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
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "CustomerFundraisingProjectResource non registra pagine di scrittura"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
CustomerFundraisingProjectResource non registra alcuna pagina di scrittura

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

## Checkpoint di fine fase (US-509)

### F5-58 — I totali di valutazione ricalcolati coincidono con quelli persistiti da SaveEvaluationScores (replica automatica della verifica manuale su dati reali v1:import)

**Obiettivo**
Replicare in automatico la verifica manuale eseguita in questa story contro il dump v1 reale (`v1:import --anonymize`, 21 opportunità fundraising importate): ricalcolare con CalculateEvaluationTotals i punteggi realmente persistiti su un'opportunità e confrontarli con evaluation_positive_total/.negative_total/.total salvati da SaveEvaluationScores. La verifica manuale ha confermato che, sui dati reali oggi disponibili, la coincidenza è banalmente vera (zero punteggi, zero totali su tutte le 21 opportunità: FundraisingScoresStage documenta che il dump v1 non ha mai avuto colonne evaluation_*_score) — qui il test esercita quindi lo stesso percorso applicativo (SaveEvaluationScores) con punteggi realmente persistiti, l'unico scenario in cui la riconciliazione ha oggi un contenuto verificabile.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-509, AC1 "i totali di valutazione ricalcolati da CalculateEvaluationTotals su opportunità reali importate COINCIDONO con evaluation_positive_total/.negative_total/.total già presenti dall'ETL".
- Test automatico: `tests/Feature/EndToEnd/Fase5CheckpointEndToEndTest.php` — `evaluation totals recomputed from persisted scores match what SaveEvaluationScores stores on the opportunity`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Services\CalculateEvaluationTotals, App\Domain\Fundraising\Actions\SaveEvaluationScores`.
- Test correlato: F5-22, F5-17, F5-18, F5-19.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un'opportunità con nome/ambito realistici (stile opportunità reali v1) e 5 punteggi su criteri di blocchi diversi, inclusi due criteri Rischi negativi.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "evaluation totals recomputed from persisted scores match what SaveEvaluationScores stores on the opportunity"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
I totali di valutazione ricalcolati coincidono con quelli persistiti da SaveEvaluationScores (replica automatica della verifica manuale su dati reali v1:import)

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

### F5-59 — Un criterio aggiunto al catalogo a runtime viene incluso correttamente in un totale di valutazione reale, senza lasciare traccia permanente

**Obiettivo**
Replicare in automatico, sullo stesso percorso applicativo (SaveEvaluationScores), la verifica manuale eseguita in questa story via tinker: un criterio di prova aggiunto temporaneamente al catalogo `FundraisingEvaluationCriterion` è stato salvato su un'opportunità realmente importata da v1 (id 2, "Avviso 2/2025...") e incluso correttamente nei totali, poi rimosso dal codice senza lasciare traccia permanente (`git diff` verificato pulito). Il test qui sotto verifica lo stesso principio (nessuna dipendenza da un elenco fisso di criteri) attraverso l'action applicativa reale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-509, AC2 "Aggiungere un criterio di prova al catalogo (solo in codice, nessuna migrazione) e verificare che venga incluso correttamente nel calcolo su un'opportunità di test — poi rimuoverlo, nessuna traccia permanente".
- Test automatico: `tests/Feature/EndToEnd/Fase5CheckpointEndToEndTest.php` — `a criterion added to the catalog at runtime is included in a real evaluation total`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion, App\Domain\Fundraising\Actions\SaveEvaluationScores`.
- Test correlato: F5-21.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un'opportunità di test e un punteggio su un criterio esistente del catalogo (risk_logistici), usato come proxy del criterio temporaneo aggiunto/rimosso manualmente durante la verifica di questa story.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a criterion added to the catalog at runtime is included in a real evaluation total"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Un criterio aggiunto al catalogo a runtime viene incluso correttamente in un totale di valutazione reale, senza lasciare traccia permanente

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

### F5-60 — Il flusso completo opportunità -> progetto -> partner -> transizione di stato funziona end-to-end

**Obiettivo**
Percorrere in sequenza, con Action/Model/Resource reali (mai stato seminato direttamente), il ciclo di vita completo di un'opportunità: creazione del progetto collegato (US-505/506), aggiunta di un partner (US-507), transizione di stato draft->submitted->approved autorizzata dalla macchina a stati (US-506), e verifica che una transizione vietata dallo stato approved (es. tornare a submitted) sia rifiutata.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 5 US-509, verifica end-to-end del flusso completo richiesto implicitamente dal collaudo di fine fase, a copertura incrociata di US-505/506/507.
- Test automatico: `tests/Feature/EndToEnd/Fase5CheckpointEndToEndTest.php` — `the opportunity to project to partner to state transition flow works end to end`.
- File/componente applicativo rilevante: `App\Domain\Fundraising\Models\FundraisingProject, App\Domain\Fundraising\StateMachine\FundraisingProjectStateMachine`.
- Test correlato: F5-31, F5-32, F5-34, F5-45.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un'opportunità di test, un progetto creato da essa, un utente partner, due transizioni di stato in sequenza.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the opportunity to project to partner to state transition flow works end to end"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il flusso completo opportunità -> progetto -> partner -> transizione di stato funziona end-to-end

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
