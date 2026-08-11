# Fase 1A (Landing, Login, Recupero password) — Casi di test dettagliati

> Torna a [`README.md`](README.md) · Istruzioni generali: [`00-istruzioni-generali.md`](00-istruzioni-generali.md) · Matrice di tracciabilità: [`01-matrice-tracciabilita.md`](01-matrice-tracciabilita.md)

16 casi di test (F1A-01 — F1A-16) su 4 argomenti. Addendum contenuto alla Fase 1 (non una nuova fase del roadmap PRD §14): landing page pubblica, login con la nuova identità visiva "Montagna Servizi" (verde pino/Manrope, distinta dal tema teal del pannello) e flusso reale di recupero password. Prima di eseguire un test, leggi le convenzioni comuni in `00-istruzioni-generali.md` (in particolare le sezioni 9 "Credenziali", 13 "Preparazione e ripristino dei dati" e 14 "Convenzioni per nominare i dati di test").

## Landing pubblica

### F1A-01 — La landing "/" è raggiribile da un visitatore anonimo con una sola CTA

**Obiettivo**
Verificare che la pagina pubblica "/" sia raggiungibile senza autenticazione, mostri l'identità visiva Montagna Servizi (verde pino/Manrope) e presenti un'unica call to action verso il login del pannello — nessun form, nessuna newsletter, nessun link di navigazione multiplo.

**Riferimenti**
- Requisito: PRD v0.3.0 (landing/login/recupero password) §4.
- Test automatico: `tests/Feature/Http/LandingControllerTest.php` — `un visitatore anonimo vede la landing pubblica`.
- File/componente applicativo rilevante: `app/Http/Controllers/LandingController.php`, `resources/views/marketing/landing.blade.php`, `routes/web.php`.
- Test correlato: F1A-02 (redirect se già autenticato), F1A-16 (separazione CSS).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Anonimo (nessuna sessione)

**Prerequisiti**
- Browser in modalità anonima/privata, oppure nessuna sessione attiva sul dominio UAT.

**Dati di test**
Nessuno.

**Stato iniziale**
Nessuna sessione autenticata nel browser usato per il test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Apri `https://ticket-uat.montagnaservizi.com/` | — | La pagina carica con sfondo verde pino, logo Montagna Servizi, titolo "Concentrati su ciò che conta: la montagna e la comunità." |
| 2 | Osserva l'intera pagina (navbar, sezione centrale, footer) | — | È presente esattamente un bottone/link "Accedi" nella navbar e uno nella sezione centrale; nessun form di iscrizione, nessun secondo link di navigazione oltre al logo |
| 3 | Clicca sul bottone "Accedi" | — | Il browser naviga a `/admin/login` |

**Risultato finale atteso**
La landing è visibile senza autenticazione, con una sola azione possibile (accedere), e porta correttamente alla pagina di login del pannello.

**Controlli negativi**
Verifica che non esistano altri link cliccabili che portano a pagine applicative interne (es. link diretti a risorse del pannello): l'unica via d'ingresso è il login.

**Evidenze da acquisire**
- Screenshot della landing completa (desktop).
- Screenshot dopo il click su "Accedi" (URL nella barra degli indirizzi).

**Criterio di superamento**

PASS: la landing è raggiungibile da anonimo, mostra l'identità visiva corretta e l'unica CTA porta al login.
FAIL: la pagina non carica, mostra più di una CTA verso destinazioni diverse dal login, oppure il bottone non porta a `/admin/login`.
BLOCKED: il dominio UAT non è raggiungibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: test di sola lettura, nessun dato modificato.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F1A-02 — Un utente con sessione attiva che visita "/" viene rimandato alla dashboard

**Obiettivo**
Verificare che un utente già autenticato non veda mai la landing pubblica: la richiesta a "/" deve rimandarlo direttamente alla dashboard del pannello (che a sua volta lo indirizza per ruolo, comportamento US-113 già esistente e non toccato da questa feature).

**Riferimenti**
- Requisito: PRD v0.3.0 §4 ("se c'è una sessione di login attiva deve essere rimandato alla pagina principale").
- Test automatico: `tests/Feature/Http/LandingControllerTest.php` — `un utente con sessione attiva viene rimandato alla dashboard del pannello`.
- File/componente applicativo rilevante: `app/Http/Controllers/LandingController.php`.
- Test correlato: F1A-01.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Un qualunque utente con accesso al pannello (es. Developer)

**Prerequisiti**
- Accesso con un account valido (es. lorena.sava@montagnaservizi.com, password "uat" — identità di riferimento popolata dall'ETL reale, `v1:import --anonymize`).

**Dati di test**
Nessuno.

**Stato iniziale**
Il tester ha già effettuato il login nel browser usato per il test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Con la sessione già autenticata, digita nella barra degli indirizzi l'URL della landing (`https://ticket-uat.montagnaservizi.com/`) | — | Il browser viene rimandato automaticamente a `/admin` (o alla vista di lavoro per il proprio ruolo), la landing pubblica non viene mai mostrata |

**Risultato finale atteso**
L'utente autenticato non vede in alcun momento il contenuto della landing pubblica.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot dell'URL nella barra degli indirizzi subito dopo il redirect (deve mostrare `/admin...`, non `/`).

**Criterio di superamento**

PASS: la richiesta a "/" da sessione autenticata produce sempre un redirect verso il pannello, mai il contenuto della landing.
FAIL: la landing pubblica viene mostrata anche con sessione attiva.
BLOCKED: impossibile autenticarsi per eseguire il test.
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

## Login

### F1A-03 — Aspetto della pagina di login conforme al design Montagna Servizi

**Obiettivo**
Verificare che la pagina di login (`/admin/login`) riproduca fedelmente il design fornito dal committente (layout a due colonne su desktop — pannello fotografico a sinistra con logo/eyebrow/titolo/vantaggi, form a destra —, stack verticale su mobile senza il pannello fotografico), con la palette verde pino e il font Manrope, distinta dal tema teal del pannello interno.

**Riferimenti**
- Requisito: PRD v0.3.0 §5, riferimento visivo `docs/features/landing-login-recupero-password-v0.3.0/design-reference.md`.
- Test automatico: `tests/Feature/Filament/Auth/LoginTest.php` — `la pagina di login renderizza il layout custom`; `tests/Feature/Http/MarketingAssetsSeparationTest.php` — `il login carica il css marketing, non il tema teal del pannello`.
- File/componente applicativo rilevante: `app/Filament/Auth/Pages/Login.php`, `resources/views/filament/auth/login.blade.php`, `resources/css/marketing.css`.
- Test correlato: F1A-16.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Anonimo

**Prerequisiti**
Nessuno.

**Dati di test**
Nessuno.

**Stato iniziale**
Nessuna sessione attiva.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Apri `/admin/login` su un browser desktop (larghezza ≥ 1200px) | — | Layout a due colonne: sinistra foto/illustrazione alpina con overlay verde scuro, logo bianco, titolo "Concentrati su ciò che conta..."; destra form "Bentornato" con campi Email/Password |
| 2 | Ridimensiona la finestra a larghezza mobile (≤ 480px) o apri da smartphone | — | Il pannello fotografico sinistro scompare; resta solo il form, leggibile e utilizzabile, senza testo tagliato o elementi che escono dallo schermo |
| 3 | Osserva il font del titolo e dei testi | — | Il carattere è "Manrope" (bold/extra-bold per i titoli), visibilmente diverso dal font del pannello interno (Nunito Sans) |

**Risultato finale atteso**
La pagina è visivamente fedele al design fornito, corretta su desktop e mobile, con la palette e il font previsti.

**Controlli negativi**
Verifica che nessun elemento del tema teal del pannello (colori, font Nunito Sans) compaia su questa pagina.

**Evidenze da acquisire**
- Screenshot desktop e mobile della pagina di login.

**Criterio di superamento**

PASS: layout, palette e font corrispondono al design su entrambi i breakpoint.
FAIL: layout rotto, testo tagliato/sovrapposto, o tema teal visibile su questa pagina.
BLOCKED: la pagina non carica.
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

### F1A-04 — Credenziali corrette autenticano e portano alla dashboard

**Obiettivo**
Verificare che l'inserimento di email e password corrette di un utente esistente autentichi con successo e porti alla dashboard del pannello, riusando l'intera logica nativa di autenticazione Filament (nessuna reimplementazione).

**Riferimenti**
- Requisito: PRD v0.3.0 §5.
- Test automatico: `tests/Feature/Filament/Auth/LoginTest.php` — `credenziali corrette autenticano e reindirizzano alla dashboard`.
- File/componente applicativo rilevante: `app/Filament/Auth/Pages/Login.php` (eredita `authenticate()` da `Filament\Auth\Pages\Login`, invariato).
- Test correlato: F1A-05.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Un qualunque utente di collaudo (es. Developer)

**Prerequisiti**
- Utente "Lorena Sava" esistente (lorena.sava@montagnaservizi.com / uat, popolato dall'ETL reale, `v1:import --anonymize`).

**Dati di test**
- Email: `lorena.sava@montagnaservizi.com`
- Password: `uat`

**Stato iniziale**
Nessuna sessione attiva.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Apri `/admin/login`, inserisci email e password | `lorena.sava@montagnaservizi.com` / `uat` | I campi accettano l'input senza errori di validazione lato client |
| 2 | Clicca "Accedi" | — | Il bottone mostra "Accesso in corso…", poi il browser viene rimandato alla vista di lavoro del developer |

**Risultato finale atteso**
L'utente è autenticato e vede il pannello corrispondente al proprio ruolo.

**Controlli negativi**
Nessuno applicabile (coperto da F1A-05).

**Evidenze da acquisire**
- Screenshot della dashboard dopo il login riuscito.

**Criterio di superamento**

PASS: il login riesce e porta al pannello.
FAIL: il login fallisce con credenziali corrette, o non avviene alcun redirect.
BLOCKED: la pagina di login non carica.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno (effettua il logout al termine se necessario continuare il collaudo come altro utente).

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F1A-05 — Credenziali errate mostrano un messaggio di errore e non autenticano

**Obiettivo**
Verificare che l'inserimento di una password errata per un'email esistente produca un messaggio di errore in italiano, senza autenticare l'utente.

**Riferimenti**
- Requisito: PRD v0.3.0 §5.
- Test automatico: `tests/Feature/Filament/Auth/LoginTest.php` — `credenziali errate mostrano un errore e non autenticano`.
- File/componente applicativo rilevante: `resources/views/filament/auth/login.blade.php` (blocco `@error('data.email')`), traduzione nativa `filament-panels::auth/pages/login.messages.failed`.
- Test correlato: F1A-04.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Anonimo

**Prerequisiti**
- Utente "Lorena Sava" esistente.

**Dati di test**
- Email: `lorena.sava@montagnaservizi.com`
- Password: `password-sbagliata`

**Stato iniziale**
Nessuna sessione attiva.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Apri `/admin/login`, inserisci l'email corretta e una password errata | `lorena.sava@montagnaservizi.com` / `password-sbagliata` | — |
| 2 | Clicca "Accedi" | — | Compare un riquadro rosso con il testo "I dati di accesso non sono corretti." La pagina resta sul login |

**Risultato finale atteso**
Nessuna sessione viene creata; il messaggio di errore è in italiano e comprensibile.

**Controlli negativi**
Verifica che il messaggio non riveli se l'email esiste o non esiste nel sistema (stesso testo in entrambi i casi).

**Evidenze da acquisire**
- Screenshot del messaggio di errore.

**Criterio di superamento**

PASS: l'errore è mostrato correttamente in italiano, nessuna sessione creata.
FAIL: nessun errore mostrato, messaggio in inglese, o l'utente risulta autenticato.
BLOCKED: la pagina di login non carica.
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

### F1A-06 — Il toggle "Mostra/Nascondi password" funziona

**Obiettivo**
Verificare che il link "Mostra password" cambi la visibilità del testo digitato nel campo password, e che il link cambi etichetta in "Nascondi password" di conseguenza.

**Riferimenti**
- Requisito: design mockup "Login Montagna Servizi", campo password.
- Test automatico: `tests/Feature/Filament/Auth/LoginTest.php` — `la vista contiene il toggle Alpine per mostrare/nascondere la password (nessuna reimplementazione JS del campo)` (verifica la presenza del binding Alpine `x-data`/`:type` nel markup; il comportamento a runtime nel browser resta verificato solo da questo test manuale, essendo puro Alpine.js lato client — nessun framework di test browser è installato nel progetto, vedi `00-istruzioni-generali.md` §3).
- File/componente applicativo rilevante: `resources/views/filament/auth/login.blade.php`.
- Test correlato: F1A-03.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Anonimo

**Prerequisiti**
Nessuno.

**Dati di test**
Nessuno (basta digitare un testo qualunque, es. `Prova123`).

**Stato iniziale**
Pagina di login caricata, campo password vuoto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Digita un testo nel campo password | `Prova123` | Il testo è mostrato come pallini/asterischi (campo di tipo password) |
| 2 | Clicca sul link "Mostra password" | — | Il testo digitato diventa leggibile in chiaro; l'etichetta del link cambia in "Nascondi password" |
| 3 | Clicca di nuovo sul link (ora "Nascondi password") | — | Il testo torna a essere oscurato; l'etichetta torna a "Mostra password" |

**Risultato finale atteso**
Il toggle funziona in entrambe le direzioni senza perdere il testo digitato.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot del campo con password visibile dopo il click.

**Criterio di superamento**

PASS: il toggle mostra/nasconde correttamente il testo in entrambe le direzioni.
FAIL: il click non ha effetto, oppure il testo digitato viene perso al cambio di visibilità.
BLOCKED: la pagina non carica o Alpine.js non si inizializza (console JS con errori).
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

### F1A-07 — "Salva per le prossime sessioni" mantiene l'accesso dopo la chiusura del browser

**Obiettivo**
Verificare che, selezionando la checkbox "Salva per le prossime sessioni" durante il login, l'utente resti autenticato anche dopo la chiusura e riapertura del browser (remember-me nativo Laravel).

**Riferimenti**
- Requisito: design mockup, checkbox "Salva per le prossime sessioni".
- Test automatico: `tests/Feature/Filament/Auth/LoginTest.php` — `"salva per le prossime sessioni" valorizza il remember token e mantiene la sessione` (verifica lato server che `remember_token` sia valorizzato; la persistenza reale attraverso la chiusura del browser va confermata manualmente, essendo legata al cookie `remember_web_...` del browser).
- File/componente applicativo rilevante: `resources/views/filament/auth/login.blade.php` (checkbox `wire:model="data.remember"`), logica nativa `Filament\Auth\Pages\Login::authenticate()`.
- Test correlato: F1A-04.

**Modalità di esecuzione**
MISTO (MANUALE UI + TECNICO DATABASE)

**Priorità**
Media

**Ruolo del tester**
Un qualunque utente di collaudo

**Prerequisiti**
- Utente "Lorena Sava" esistente.

**Dati di test**
- Email: `lorena.sava@montagnaservizi.com`, Password: `uat`.

**Stato iniziale**
Nessuna sessione attiva, cookie del browser non ripuliti tra i passi 2 e 3.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Login con la checkbox "Salva per le prossime sessioni" selezionata | credenziali sopra | Login riuscito |
| 2 | Chiudi completamente il browser (non solo la scheda) e riaprilo, senza effettuare logout | — | Navigando su `/admin`, l'utente risulta ancora autenticato, senza dover reinserire le credenziali |
| 3 | (Tecnico, opzionale) Verifica in database che `users.remember_token` per l'utente non sia null | — | Colonna popolata con un token casuale |

**Risultato finale atteso**
La sessione persiste attraverso la chiusura del browser quando la checkbox è selezionata.

**Controlli negativi**
Ripetere il login SENZA selezionare la checkbox: dopo la chiusura del browser (e l'eliminazione dei cookie di sessione, es. con gli strumenti sviluppatore), l'utente deve risultare disconnesso.

**Evidenze da acquisire**
- Screenshot del pannello dopo la riapertura del browser, ancora autenticato.

**Criterio di superamento**

PASS: con la checkbox selezionata la sessione persiste; senza, non persiste oltre la sessione del browser.
FAIL: la sessione non persiste con la checkbox selezionata, oppure persiste anche senza.
BLOCKED: impossibile testare la persistenza (limitazioni del browser di test).
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Effettua il logout al termine del test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F1A-08 — Dopo 5 tentativi di login falliti, il sesto viene bloccato temporaneamente

**Obiettivo**
Verificare che il rate limiting nativo di Filament (5 tentativi/minuto) blocchi tentativi di login ulteriori dopo 5 fallimenti consecutivi, anche con credenziali corrette al sesto tentativo, mostrando una notifica di blocco temporaneo.

**Riferimenti**
- Requisito: comportamento nativo Filament (`WithRateLimiting::rateLimit(5)` in `Filament\Auth\Pages\Login::authenticate()`), non modificato da questa feature.
- Test automatico: `tests/Feature/Filament/Auth/LoginTest.php` — `il sesto tentativo di login consecutivo viene bloccato dal rate limiting nativo`.
- File/componente applicativo rilevante: nessuno applicativo (comportamento vendor Filament, ereditato).
- Test correlato: F1A-05.

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Bassa

**Ruolo del tester**
Tecnico

**Prerequisiti**
- Utente di test dedicato (per non bloccare temporaneamente un account di collaudo condiviso).

**Dati di test**
- Un utente qualunque, 5 tentativi con password errata seguiti da un sesto con password corretta.

**Stato iniziale**
Nessun tentativo di login recente per l'IP/account usato (il contatore si azzera dopo 60 secondi).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Esegui 5 tentativi di login consecutivi con password errata, in rapida sequenza | password errata × 5 | Ogni tentativo mostra il messaggio "I dati di accesso non sono corretti." |
| 2 | Esegui immediatamente un sesto tentativo, questa volta con la password corretta | password corretta | Compare una notifica di blocco temporaneo ("Too many requests" / equivalente italiano nativo); il login NON riesce nonostante la password sia corretta |
| 3 | Attendi 60 secondi e riprova con la password corretta | password corretta | Il login riesce normalmente |

**Risultato finale atteso**
Il sesto tentativo entro la finestra di un minuto è bloccato indipendentemente dalla correttezza delle credenziali; dopo l'attesa, il login torna a funzionare.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della notifica di blocco al sesto tentativo.

**Criterio di superamento**

PASS: il sesto tentativo entro un minuto è bloccato; dopo l'attesa il login riesce.
FAIL: il sesto tentativo riesce nonostante i 5 fallimenti precedenti, oppure il blocco persiste oltre il tempo previsto.
BLOCKED: non è possibile eseguire tentativi ripetuti (es. protezioni di rete).
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il contatore si azzera automaticamente.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

## Recupero password

### F1A-09 — Richiesta di reset con un'email registrata invia il link ed è visibile su Mailpit

**Obiettivo**
Verificare che, richiedendo il reset della password per un'email esistente, il sistema mostri il pannello "Controlla la casella" e invii davvero una email reale, ricevibile su Mailpit in ambiente UAT.

**Riferimenti**
- Requisito: PRD v0.3.0 §6.1.
- Test automatico: `tests/Feature/Filament/Auth/PasswordResetTest.php` — `richiedere il reset con una email registrata invia la notifica e mostra il pannello "controlla la casella"`.
- File/componente applicativo rilevante: `app/Filament/Auth/Pages/RequestPasswordReset.php`, `resources/views/filament/auth/request-password-reset.blade.php`.
- Test correlato: F1A-10, F1A-11.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Critica

**Ruolo del tester**
Anonimo

**Prerequisiti**
- Utente "Lorena Sava" esistente (lorena.sava@montagnaservizi.com).
- Accesso a Mailpit UAT: `https://mailpit-ticket-uat.montagnaservizi.com` (credenziali Basic Auth fornite nel documento di istruzioni generali).

**Dati di test**
- Email: `lorena.sava@montagnaservizi.com`

**Stato iniziale**
Casella Mailpit vuota o comunque consultabile per individuare il nuovo messaggio per data/ora.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da `/admin/login`, clicca "Recupera password" | — | Naviga a `/admin/password-reset/request`, pannello "Hai dimenticato la password?" |
| 2 | Inserisci l'email e clicca "Invia il link di recupero" | `lorena.sava@montagnaservizi.com` | La stessa pagina mostra ora "Controlla la casella" con l'email inserita evidenziata in grassetto |
| 3 | Apri Mailpit e individua il nuovo messaggio | — | È presente un'email con oggetto "Reimposta la password", destinata a `lorena.sava@montagnaservizi.com`, contenente un bottone "Reimposta password" |
| 4 | Clicca sul bottone/link nell'email | — | Il browser naviga a `/admin/password-reset/reset?...`, pagina "Imposta una nuova password" |

**Risultato finale atteso**
Il flusso completo richiesta → email → link funziona senza errori, in italiano.

**Controlli negativi**
Nessuno applicabile (coperto da F1A-10).

**Evidenze da acquisire**
- Screenshot del pannello "Controlla la casella".
- Screenshot dell'email in Mailpit.

**Criterio di superamento**

PASS: l'email arriva su Mailpit con contenuto in italiano e link funzionante.
FAIL: nessuna email ricevuta, contenuto in inglese, o link non funzionante.
BLOCKED: Mailpit non raggiungibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno (le email restano su Mailpit fino al limite di 500 messaggi configurato).

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F1A-10 — Richiesta di reset con un'email inesistente non rivela l'assenza dell'utente

**Obiettivo**
Verificare che richiedere il reset per un'email NON registrata mostri lo stesso pannello "Controlla la casella" della F1A-09 (nessuna differenza visibile), senza inviare alcuna email reale — comportamento nativo anti-enumerazione utenti.

**Riferimenti**
- Requisito: PRD v0.3.0 §6.1 (comportamento nativo Filament preservato dalla view custom).
- Test automatico: `tests/Feature/Filament/Auth/PasswordResetTest.php` — `richiedere il reset con una email inesistente non invia notifiche ma non rivela l'assenza dell'utente`.
- File/componente applicativo rilevante: `app/Filament/Auth/Pages/RequestPasswordReset.php`.
- Test correlato: F1A-09.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Anonimo

**Prerequisiti**
Nessuno.

**Dati di test**
- Email: `nessuno-registrato-COLL@esempio.test` (indirizzo garantito inesistente).

**Stato iniziale**
Nessuna sessione attiva.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da `/admin/password-reset/request`, inserisci un'email inesistente e invia | `nessuno-registrato-COLL@esempio.test` | Compare lo stesso pannello "Controlla la casella" della F1A-09, con la stessa formulazione ("Se [email] è registrata, riceverai...") |
| 2 | Controlla Mailpit | — | Nessuna nuova email presente per quell'indirizzo |

**Risultato finale atteso**
Il comportamento osservabile dall'utente è identico a quello con un'email esistente, senza inviare email reali.

**Controlli negativi**
Confronta il tempo di risposta tra questo test e F1A-09: non deve esserci una differenza di tempo misurabile che riveli indirettamente l'esistenza dell'account (verifica indicativa, non uno strumento di timing attack).

**Evidenze da acquisire**
- Screenshot del pannello "Controlla la casella" con l'email inesistente mostrata.

**Criterio di superamento**

PASS: stesso messaggio mostrato, nessuna email inviata.
FAIL: messaggio diverso (es. "email non trovata"), oppure un'email viene comunque inviata.
BLOCKED: la pagina di richiesta non carica.
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

### F1A-11 — "Invia di nuovo" immediato è bloccato dal throttling nativo (60 secondi)

**Obiettivo**
Verificare che, dopo aver richiesto un link di recupero, cliccare immediatamente "Invia di nuovo" non inoltri una seconda email (throttling nativo del password broker Laravel, 60 secondi), e che dopo l'attesa l'invio ripetuto funzioni.

**Riferimenti**
- Requisito: comportamento nativo Laravel (`config('auth.passwords.users.throttle')`, default 60s), non modificato da questa feature.
- Test automatico: `tests/Feature/Filament/Auth/PasswordResetTest.php` — `"invia di nuovo" immediato è bloccato dal throttling nativo del broker password (60s)` e `"invia di nuovo" dopo il throttle del broker invia davvero un secondo link`.
- File/componente applicativo rilevante: `app/Filament/Auth/Pages/RequestPasswordReset.php::resend()`.
- Test correlato: F1A-09.

**Modalità di esecuzione**
MISTO (MANUALE UI + MAILPIT)

**Priorità**
Media

**Ruolo del tester**
Anonimo

**Prerequisiti**
- Utente "Lorena Sava" esistente.
- Accesso a Mailpit UAT.

**Dati di test**
- Email: `lorena.sava@montagnaservizi.com`

**Stato iniziale**
Nessuna richiesta di reset recente per questa email (attendere almeno 60 secondi dall'ultimo test F1A-09/F1A-10 su questo stesso indirizzo, oppure usare un altro utente di collaudo).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Richiedi il reset per l'email | `lorena.sava@montagnaservizi.com` | Pannello "Controlla la casella"; un'email arriva su Mailpit |
| 2 | Clicca immediatamente "Invia di nuovo" | — | Il pannello resta su "Controlla la casella" (nessun errore visibile), ma NESSUNA nuova email arriva su Mailpit entro pochi secondi |
| 3 | Attendi 60 secondi e clicca di nuovo "Invia di nuovo" | — | Una seconda email arriva su Mailpit |

**Risultato finale atteso**
Il secondo invio è effettivo solo dopo l'attesa di 60 secondi dal primo.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot/conteggio messaggi Mailpit prima e dopo il passo 2 (deve restare invariato).
- Screenshot del conteggio dopo il passo 3 (deve essere aumentato di 1).

**Criterio di superamento**

PASS: nessuna seconda email prima dei 60 secondi, una seconda email dopo.
FAIL: una seconda email arriva immediatamente al passo 2, oppure nessuna email arriva nemmeno al passo 3.
BLOCKED: Mailpit non raggiungibile.
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

### F1A-12 — Impostare una nuova password con un token valido, rispettando le regole reali

**Obiettivo**
Verificare che, seguendo un link di reset valido, sia possibile impostare una nuova password che rispetti le regole reali (8+ caratteri, almeno una maiuscola, almeno una minuscola, almeno un numero — coerenti con l'indicatore di forza visivo mostrato in pagina) e che dopo il salvataggio sia possibile accedere con la nuova password.

**Riferimenti**
- Requisito: PRD v0.3.0 §6.2 (nota tecnica su `Password::defaults()`).
- Test automatico: `tests/Feature/Filament/Auth/PasswordResetTest.php` — `un token valido permette di impostare una nuova password rispettando le regole reali`.
- File/componente applicativo rilevante: `app/Filament/Auth/Pages/ResetPassword.php`, `app/Providers/AppServiceProvider.php` (`Password::defaults()`).
- Test correlato: F1A-09, F1A-13.

**Modalità di esecuzione**
MISTO (MANUALE UI + TECNICO DATABASE)

**Priorità**
Critica

**Ruolo del tester**
Anonimo (segue un link email) + verifica successiva come utente

**Prerequisiti**
- Un link di reset valido appena ricevuto (F1A-09).

**Dati di test**
- Nuova password: `CollaudoF1A12` (rispetta le 4 regole: 8+ caratteri, maiuscola, minuscola, numero).

**Stato iniziale**
Link di reset non ancora utilizzato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Apri il link ricevuto via email | — | Pagina "Imposta una nuova password", campi vuoti, barra di forza su "Sicurezza" (grigia) |
| 2 | Digita la nuova password nel primo campo | `CollaudoF1A12` | La barra di forza diventa verde ("Sicura"); tutte le 4 voci della checklist (8+ caratteri, maiuscola, minuscola, numero) mostrano il segno di spunta |
| 3 | Digita la stessa password nel campo di conferma | `CollaudoF1A12` | Nessun messaggio di mancata corrispondenza |
| 4 | Clicca "Salva la nuova password" | — | Notifica di successo; redirect alla pagina di login |
| 5 | Accedi con l'email e la nuova password | `lorena.sava@montagnaservizi.com` / `CollaudoF1A12` | Login riuscito |

**Risultato finale atteso**
La password è aggiornata e utilizzabile per un login immediato; il vecchio link di reset non è più riutilizzabile (verifica facoltativa: ripetere il passo 1 con lo stesso link, deve fallire).

**Controlli negativi**
Ripeti il passo 1 con lo stesso link già usato: deve essere rifiutato (vedi F1A-14).

**Evidenze da acquisire**
- Screenshot della barra di forza e checklist tutte verdi/spuntate.
- Screenshot del login riuscito con la nuova password.

**Criterio di superamento**

PASS: la password viene salvata e il login con la nuova password riesce.
FAIL: il salvataggio fallisce nonostante una password valida, o il login con la nuova password non riesce.
BLOCKED: il link di reset non è raggiungibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Se necessario per continuare il collaudo con la password originale nota, effettuare un nuovo reset e ripristinarla a `uat`.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F1A-13 — Una password che non rispetta le regole reali viene rifiutata

**Obiettivo**
Verificare che una password che non rispetta almeno una delle 4 regole reali (8+ caratteri, maiuscola, minuscola, numero) venga rifiutata dal server con un messaggio d'errore, anche se l'utente ignora l'indicatore visivo e tenta comunque l'invio.

**Riferimenti**
- Requisito: PRD v0.3.0 §6.2.
- Test automatico: `tests/Feature/Filament/Auth/PasswordResetTest.php` — `una password che non rispetta le regole reali (min 8, maiuscola, numero) viene rifiutata`.
- File/componente applicativo rilevante: `app/Providers/AppServiceProvider.php` (`Password::defaults()`), `app/Filament/Auth/Pages/ResetPassword.php`.
- Test correlato: F1A-12.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Anonimo

**Prerequisiti**
- Un link di reset valido (richiederne uno nuovo, F1A-09).

**Dati di test**
- Password non valida: `tuttominuscolo` (nessuna maiuscola, nessun numero — ma 14 caratteri e tutta minuscola, quindi le regole "8+ caratteri" e "una lettera minuscola" risulterebbero soddisfatte: verifica che vengano segnalate correttamente solo le regole realmente non rispettate).

**Stato iniziale**
Link di reset non ancora utilizzato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Apri il link di reset, digita la password non valida in entrambi i campi | `tuttominuscolo` | La checklist mostra "Almeno 8 caratteri" e "Una lettera minuscola" spuntati, "Una lettera maiuscola" e "Almeno un numero" NON spuntati; barra di forza su "Media" o "Debole" |
| 2 | Clicca comunque "Salva la nuova password" | — | Il salvataggio viene rifiutato dal server con un messaggio d'errore sotto il campo password |

**Risultato finale atteso**
Nessuna password viene salvata; l'utente resta sulla pagina con l'errore visibile.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della checklist con le regole non soddisfatte evidenziate.
- Screenshot del messaggio di errore dopo il tentativo di invio.

**Criterio di superamento**

PASS: il salvataggio è rifiutato lato server con messaggio d'errore visibile.
FAIL: la password viene salvata nonostante non rispetti le regole.
BLOCKED: la pagina di reset non carica.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno (nessuna modifica effettuata).

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F1A-14 — Un link di reset già usato o inesistente viene rifiutato

**Obiettivo**
Verificare che un link di reset già utilizzato per completare un cambio password (o un link con token inventato/corrotto) non permetta un secondo reset, con un messaggio d'errore gestito (non un errore tecnico non gestito).

**Riferimenti**
- Requisito: comportamento nativo del password broker Laravel (un token è a singolo uso, invalidato dopo il primo reset riuscito).
- Test automatico: `tests/Feature/Filament/Auth/PasswordResetTest.php` — `un token inesistente o già consumato viene rifiutato con una notifica nativa, nessun reset silenzioso`.
- File/componente applicativo rilevante: `app/Filament/Auth/Pages/ResetPassword.php` (view custom, logica nativa invariata).
- Test correlato: F1A-12.

**Modalità di esecuzione**
MISTO (MANUALE UI + TECNICO CLI)

**Priorità**
Alta

**Ruolo del tester**
Anonimo

**Prerequisiti**
- Un link di reset già usato con successo (riusare quello di F1A-12, dopo aver completato quel test).

**Dati di test**
- Lo stesso URL di reset già usato in F1A-12 (salvato prima di completare quel test).

**Stato iniziale**
Il link è già stato consumato da un reset riuscito precedente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Riapri il link di reset già usato | URL salvato da F1A-12 | La pagina "Imposta una nuova password" carica normalmente (il link in sé non è "scaduto" visivamente) |
| 2 | Inserisci una nuova password valida e conferma l'invio | `AltraPassword2` | Il salvataggio viene rifiutato con una notifica d'errore nativa (token non valido); nessun redirect al login |
| 3 | (Tecnico, opzionale) Modifica manualmente l'URL sostituendo il token con una stringa casuale e ripeti il passo 2 | token inventato | Stesso rifiuto |

**Risultato finale atteso**
Un token già consumato o inesistente non permette mai un secondo reset.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della notifica di rifiuto.

**Criterio di superamento**

PASS: il secondo tentativo di reset con lo stesso token (o un token inventato) è sempre rifiutato.
FAIL: il reset riesce una seconda volta con lo stesso token, o con un token inventato.
BLOCKED: impossibile procurarsi un link già consumato per il test.
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

### F1A-15 — Un link di reset scaduto (oltre 60 minuti) viene rifiutato

**Obiettivo**
Verificare che un link di reset non utilizzato entro 60 minuti dalla richiesta non sia più valido.

**Riferimenti**
- Requisito: PRD v0.3.0 §6.1 ("il link di recupero è valido 60 minuti"), `config('auth.passwords.users.expire')` = 60.
- Test automatico: `tests/Feature/Filament/Auth/PasswordResetTest.php` — `un token scaduto oltre i 60 minuti configurati viene rifiutato`.
- File/componente applicativo rilevante: nessuno applicativo (comportamento nativo del password broker Laravel).
- Test correlato: F1A-12.

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Bassa

**Ruolo del tester**
Tecnico

**Prerequisiti**
- Accesso SSH/artisan tinker sull'ambiente di collaudo, oppure disponibilità ad attendere realmente 61 minuti dopo una richiesta di reset (impraticabile in un collaudo interattivo: si raccomanda la verifica tecnica via tinker piuttosto che l'attesa reale).

**Dati di test**
- Un token di reset generato per un utente di test, con il campo `created_at` della riga `password_reset_tokens` retrodatato di oltre 60 minuti (via `UPDATE` diretto, ambiente di collaudo non di produzione) oppure verificato tramite il test automatico corrispondente (esecuzione in CI, che usa `$this->travel(61)->minutes()` per simulare il tempo trascorso senza attese reali).

**Stato iniziale**
Un token di reset esistente, non ancora utilizzato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Genera un token di reset per un utente di test | — | Token creato, riga in `password_reset_tokens` |
| 2 | Retrodata il campo `created_at` di quella riga a più di 60 minuti nel passato (SQL diretto, solo ambiente di collaudo) | `UPDATE password_reset_tokens SET created_at = created_at - INTERVAL '61 minutes' WHERE email = '...'` | Riga aggiornata |
| 3 | Apri il link di reset corrispondente a quel token e tenta il reset con una password valida | — | Il salvataggio viene rifiutato con una notifica di token non valido/scaduto |

**Risultato finale atteso**
Un token oltre la finestra di validità configurata è sempre rifiutato.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output del comando SQL di verifica prima/dopo.
- Screenshot della notifica di rifiuto.

**Criterio di superamento**

PASS: il reset con un token scaduto è rifiutato.
FAIL: il reset riesce nonostante il token sia scaduto.
BLOCKED: non è possibile modificare direttamente il database nell'ambiente di collaudo.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Elimina la riga di test da `password_reset_tokens` se non consumata automaticamente.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

## Identità visiva e separazione dai temi

### F1A-16 — Le pagine pubbliche usano il design system "marketing", il pannello interno resta sul tema teal

**Obiettivo**
Verificare che landing, login e recupero password carichino esclusivamente il foglio di stile "marketing" (verde pino/Manrope) e MAI il tema Vite compilato del pannello (teal/Nunito Sans), e che — simmetricamente — il pannello interno post-login non carichi il foglio "marketing". Le due identità visive non devono mai mescolarsi sulla stessa pagina.

**Riferimenti**
- Requisito: PRD v0.3.0 §7 ("Font Manrope... caricato SOLO su... — non sovrascrive il font Nunito Sans del pannello").
- Test automatico: `tests/Feature/Http/MarketingAssetsSeparationTest.php` (tutti e 3 i test: landing, login, dashboard).
- File/componente applicativo rilevante: `resources/views/filament/auth/layout.blade.php` (layout dedicato, non usa `@filamentStyles`/`viteTheme` del pannello), `resources/css/marketing.css`, `vite.config.js`.
- Test correlato: F1A-03.

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Media

**Ruolo del tester**
Tecnico

**Prerequisiti**
- Accesso agli strumenti sviluppatore del browser (tab "Rete"/"Network").

**Dati di test**
Nessuno.

**Stato iniziale**
Nessuno.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Apri gli strumenti sviluppatore (tab Rete), naviga su `/` | — | Tra le richieste CSS compare un file `marketing-*.css`; NON compare alcun file `theme-*.css` del pannello |
| 2 | Ripeti su `/admin/login` | — | Stesso risultato: solo `marketing-*.css` |
| 3 | Effettua il login e osserva la dashboard (`/admin`) | — | Compare il file `theme-*.css` del pannello; NON compare `marketing-*.css` |

**Risultato finale atteso**
Le due identità visive sono sempre reciprocamente esclusive, mai caricate insieme sulla stessa pagina.

**Controlli negativi**
Verifica visivamente che nessun colore/font del pannello "trapeli" sulle pagine pubbliche e viceversa (es. bottoni pillola verde pino sul pannello, o bottoni squadrati teal sulla landing).

**Evidenze da acquisire**
- Screenshot della tab Rete per ciascuna delle 3 pagine, con il nome del file CSS caricato visibile.

**Criterio di superamento**

PASS: separazione netta confermata su tutte e 3 le pagine.
FAIL: un qualunque mix dei due fogli di stile sulla stessa pagina.
BLOCKED: strumenti sviluppatore non disponibili.
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
