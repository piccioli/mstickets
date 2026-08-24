# Fase 3 (Sottosistema email) — Casi di test dettagliati

> Torna a [`README.md`](README.md) · Istruzioni generali: [`00-istruzioni-generali.md`](00-istruzioni-generali.md) · Matrice di tracciabilità: [`01-matrice-tracciabilita.md`](01-matrice-tracciabilita.md)

113 casi di test (F3-01 — F3-113) su 26 argomenti. Prima di eseguire un test, leggi le convenzioni comuni in `00-istruzioni-generali.md` (in particolare le sezioni 9 "Credenziali", 10 "Accesso a Mailpit", 12 "Prerequisiti generali" e 13 "Preparazione e ripristino dei dati"). Nessun server IMAP di test/Mailpit viene mai usato come sorgente in ingresso: le email inbound vanno inviate alla casella reale monitorata da `mail:fetch-inbound` fornita dal committente; ogni email in uscita finisce invece sempre e solo su Mailpit UAT.

## Configurazione IMAP e interfaccia InboundMailTransport (US-301)

### F3-01 — Il container risolve InboundMailTransport all'implementazione Webklex

**Obiettivo**
Verificare che il binding del contenitore Laravel per `App\Domain\Mail\Contracts\InboundMailTransport` risolva sempre all'implementazione reale `App\Domain\Mail\Transports\WebklexImapTransport` (§7.4 del PRD), così da garantire che l'intera pipeline inbound usi un'unica implementazione IMAP reale e sostituibile in futuro senza toccare il codice della pipeline.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-301, AC1 (interfaccia `InboundMailTransport` con implementazione `WebklexImapTransport`, registrata in `App\Providers\MailServiceProvider`).
- Test automatico: `tests/Unit/Domain/Mail/WebklexImapTransportTest.php` — `the container resolves InboundMailTransport to the webklex implementation`.
- File/componente applicativo rilevante: `App\Domain\Mail\Contracts\InboundMailTransport`, `App\Domain\Mail\Transports\WebklexImapTransport`, `App\Providers\MailServiceProvider`.
- Test correlato: F3-02, F3-03.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Nessuno (risoluzione del contenitore, nessun dato applicativo coinvolto).

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto, solo risoluzione di un binding del container.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the container resolves InboundMailTransport to the webklex implementation"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `app(InboundMailTransport::class)` restituisce un'istanza di `WebklexImapTransport`.

**Controlli negativi**
Nessuno applicabile: un solo binding è registrato per questa interfaccia.

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

### F3-02 — La config account IMAP ha la forma richiesta da ClientManager::make()

**Obiettivo**
Verificare che `config('mail_pipeline.imap')` esponga esattamente le chiavi richieste da `Webklex\PHPIMAP\ClientManager::make()` (`host`, `port`, `encryption`, `validate_cert`, `username`, `password`), popolate solo tramite `env()` dentro `config/mail_pipeline.php` (§13.3 del PRD: nessuna chiamata `env()` fuori da quel file).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-301, AC2/AC3 (`WebklexImapTransport` configurato interamente da env, tramite `config/mail_pipeline.php`).
- Test automatico: `tests/Unit/Domain/Mail/MailPipelineConfigTest.php` — `the imap account config has the shape expected by ClientManager::make()`.
- File/componente applicativo rilevante: `config/mail_pipeline.php`.
- Test correlato: F3-01, F3-03, F3-04.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Bassa

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Nessuno (ispezione della sola struttura dell'array di configurazione).

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the imap account config has the shape expected by ClientManager::make()"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `array_keys(config('mail_pipeline.imap'))` è esattamente `['host', 'port', 'encryption', 'validate_cert', 'username', 'password']`.

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

### F3-03 — Ogni ImapFolderRole ha una cartella configurata

**Obiettivo**
Verificare che ogni caso dell'enum `App\Domain\Mail\Enums\ImapFolderRole` (`Inbox`/`Processed`/`Errors`/`Quarantine`) abbia una corrispondente voce non vuota in `config('mail_pipeline.folders')`, così che `WebklexImapTransport::folderName()` possa sempre risolvere il nome reale della cartella IMAP prima di toccare la connessione di rete.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-301, AC1 (metodo `move()` verso `Processed`/`Errors`/`Quarantine`, cartelle configurate da env).
- Test automatico: `tests/Unit/Domain/Mail/MailPipelineConfigTest.php` — `every ImapFolderRole has a configured folder name`.
- File/componente applicativo rilevante: `config/mail_pipeline.php`, `App\Domain\Mail\Enums\ImapFolderRole`, `App\Domain\Mail\Transports\WebklexImapTransport::folderName()`.
- Test correlato: F3-01, F3-02.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Bassa

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Nessuno.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "every ImapFolderRole has a configured folder name"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: per ciascuno dei 4 casi di `ImapFolderRole`, `config('mail_pipeline.folders')` contiene una chiave corrispondente valorizzata con una stringa non vuota.

**Controlli negativi**
Nessuno applicabile: il test copre già tutti i casi dell'enum in un'unica asserzione iterata.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
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

### F3-04 — Il gruppo di notifica staff è derivato da una env comma-separated

**Obiettivo**
Verificare che `config('mail_pipeline.staff_notification_group')` sia derivato correttamente da una variabile d'ambiente comma-separated (`MAIL_STAFF_NOTIFICATION_GROUP`), con trim degli spazi attorno a ciascun indirizzo e nessun valore vuoto residuo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-301, AC3 (`config/mail_pipeline.php` espone `staff_notification_group` per E3/E9, US-312).
- Test automatico: `tests/Unit/Domain/Mail/MailPipelineConfigTest.php` — `the staff notification group is parsed from a comma-separated env value`.
- File/componente applicativo rilevante: `config/mail_pipeline.php`.
- Test correlato: F3-40, F3-02.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Bassa

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Valore CSV di prova: `'staff-a@orchestrator.local, staff-b@orchestrator.local'` (con uno spazio dopo la virgola, per verificare il trim).

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the staff notification group is parsed from a comma-separated env value"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: il valore CSV di prova produce l'array `['staff-a@orchestrator.local', 'staff-b@orchestrator.local']`, senza spazi residui.

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
## Comando mail:fetch-inbound — fetch e archiviazione grezza (US-302)

### F3-05 — Il .eml grezzo è archiviato PRIMA di creare la riga email_messages (status=received)

**Obiettivo**
Verificare che, quando una nuova email arriva nella casella reale monitorata, `mail:fetch-inbound` archivi sempre il messaggio grezzo come file `.eml` sul disco dedicato e crei una riga `email_messages` con `direction=inbound`, `imap_folder`, `imap_uid`, `message_id`, `from_email`, `from_name`, `subject` e `raw_path` coerenti con il messaggio realmente arrivato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-302, AC2 (`.eml` salvato su disco dedicato PRIMA di qualunque parsing, poi riga `email_messages` creata).
- Test automatico: `tests/Feature/Console/MailFetchInboundCommandTest.php` — `archivia un nuovo messaggio come .eml prima di creare la riga email_messages` (verifica, con un transport IMAP mockato su una fixture `.eml` reale, che la riga creata riporti `direction`, `imap_folder`, `imap_uid`, `message_id`, `from_email`, `from_name`, `subject`, `raw_path` coerenti, e che il file esista davvero sul disco `raw-emails` con lo stesso contenuto grezzo del messaggio).
- File/componente applicativo rilevante: `App\Console\Commands\MailFetchInboundCommand`, `App\Domain\Mail\Actions\StoreRawInboundEmail`, `App\Domain\Mail\Models\EmailMessage`.
- Test correlato: F3-06, F3-08, F3-13.

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Cliente esterno (per l'invio dell'email) + Developer con accesso SSH al server applicativo (per l'esecuzione manuale del comando e l'ispezione del filesystem)

**Prerequisiti**
- Accesso a una casella email reale da cui inviare il messaggio di prova.
- Accesso SSH al server/container applicativo UAT (per lanciare manualmente `php artisan mail:fetch-inbound` senza attendere lo scheduler, e per ispezionare il disco di archiviazione grezza).
- In alternativa alla SSH: attendere il ciclo schedulato (ogni pochi minuti, `mail_pipeline.fetch.schedule_cron`).

**Dati di test**
- Oggetto email: `COLL-F3-05-20260824-01 richiesta di test collaudo`.
- Corpo: un breve testo qualsiasi.

**Stato iniziale**
Nessun messaggio con questo oggetto già presente nella casella/nel registro email.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Invia una email a `<indirizzo casella di supporto UAT, fornito dal committente>` | Oggetto `COLL-F3-05-20260824-01 richiesta di test collaudo` | L'email risulta inviata correttamente dal client di posta del tester |
| 2 | (Opzionale, per non attendere lo scheduler) Via SSH, lancia manualmente il comando | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 3 | Verifica tecnica: individua il file `.eml` archiviato | Ispezione del disco `raw-emails` (path configurato da `MAIL_RAW_STORAGE_DISK`) | Esiste un nuovo file `.eml` il cui contenuto include l'oggetto `COLL-F3-05-20260824-01 richiesta di test collaudo` |
| 4 | Verifica tecnica: individua la riga `email_messages` corrispondente | Query DB o Registro email (se già disponibile in amministrazione) sul `message_id`/`subject` del passo 1 | Esiste una riga con `direction=inbound`, `imap_folder`/`imap_uid` valorizzati, `raw_path` non nullo e coerente col file del passo 3 |

**Risultato finale atteso**
Il messaggio inviato risulta archiviato come `.eml` sul disco dedicato e tracciato da una riga `email_messages` coerente; l'ordine esatto (file scritto prima della riga DB) è garantito dal codice e verificato dal test automatico Pest referenziato.

**Controlli negativi**
Nessuno applicabile: l'ordine di scrittura file-poi-riga non è osservabile a runtime da un tester funzionale, solo a codice/test.

**Evidenze da acquisire**
- Screenshot/estratto del file `.eml` trovato sul disco.
- Screenshot/estratto della riga `email_messages` corrispondente.

**Criterio di superamento**

PASS: sia il file `.eml` sia la riga `email_messages` esistono e sono coerenti con l'email inviata.
FAIL: manca il file, manca la riga, o i dati non corrispondono al messaggio inviato.
BLOCKED: impossibile inviare l'email o accedere al server per la verifica.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il messaggio di test resta nel registro email/nella casella come qualunque altra email reale.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F3-06 — Rieseguire il comando sullo stesso stato IMAP non crea duplicati

**Obiettivo**
Verificare che rilanciare `mail:fetch-inbound` più volte senza che arrivino nuovi messaggi non produca righe `email_messages` duplicate per lo stesso `(imap_folder, imap_uid)`, grazie al vincolo unique già presente da Fase 0.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-302, AC3 (un messaggio il cui `(imap_folder, imap_uid)` esiste già viene saltato).
- Test automatico: `tests/Feature/Console/MailFetchInboundCommandTest.php` — `rieseguire il comando sullo stesso stato IMAP non crea duplicati`.
- File/componente applicativo rilevante: `App\Console\Commands\MailFetchInboundCommand`, `App\Domain\Mail\Actions\StoreRawInboundEmail`, vincolo unique `email_messages(imap_folder, imap_uid)`.
- Test correlato: F3-05, F3-08.

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Critica

**Ruolo del tester**
Developer con accesso SSH al server applicativo

**Prerequisiti**
- Accesso SSH al server/container applicativo UAT.
- Almeno un messaggio già presente e ancora nella cartella IMAP monitorata (es. il messaggio di prova di F3-05, se non ancora spostato).

**Dati di test**
Nessun nuovo dato: si riusa lo stato IMAP corrente (nessuna nuova email inviata tra le due esecuzioni).

**Stato iniziale**
Almeno un'esecuzione di `mail:fetch-inbound` già avvenuta (schedulata o manuale) con almeno un messaggio già archiviato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Via SSH, annota il conteggio corrente di `email_messages` | Query DB `select count(*) from email_messages` | Conteggio N |
| 2 | Lancia manualmente il comando una seconda volta, senza inviare nuove email nel frattempo | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 3 | Verifica il conteggio dopo la seconda esecuzione | Query DB `select count(*) from email_messages` | Il conteggio resta N (nessuna nuova riga per gli `imap_uid` già visti) |

**Risultato finale atteso**
Il numero di righe `email_messages` non aumenta rieseguendo il comando sullo stesso stato IMAP (nessun duplicato per lo stesso `imap_folder`/`imap_uid`).

**Controlli negativi**
Nessuno applicabile: il comportamento atteso è già l'assenza di duplicati.

**Evidenze da acquisire**
- Output dei due conteggi DB (prima e dopo la seconda esecuzione).
- Output del comando `mail:fetch-inbound` della seconda esecuzione.

**Criterio di superamento**

PASS: il conteggio delle righe `email_messages` resta invariato dopo la seconda esecuzione.
FAIL: compaiono righe duplicate per lo stesso `(imap_folder, imap_uid)`.
BLOCKED: impossibile accedere via SSH/DB per la verifica.
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

### F3-07 — IMAP viene sempre disconnesso, anche quando fetch lancia un errore

**Obiettivo**
Verificare che `mail:fetch-inbound` disconnetta sempre la connessione IMAP nel `finally`, anche quando `InboundMailTransport::fetch()` lancia un'eccezione, per non lasciare connessioni IMAP appese sul server.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-302, AC4 (`finally` per il disconnect IMAP anche in caso di eccezione).
- Test automatico: `tests/Feature/Console/MailFetchInboundCommandTest.php` — `disconnette sempre IMAP anche quando fetch lancia un errore` (con un `FakeInboundMailTransport` che lancia un'eccezione al fetch, verifica che il comando fallisca ma che `disconnect()` sia comunque stato chiamato).
- File/componente applicativo rilevante: `App\Console\Commands\MailFetchInboundCommand`.
- Test correlato: F3-05.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Nessuno (transport IMAP fittizio che simula un errore di connessione).

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "disconnette sempre IMAP anche quando fetch lancia un errore"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: il comando termina con esito fallito (exit non-zero) ma il transport IMAP fittizio registra comunque una chiamata a `disconnect()`.

**Controlli negativi**
Nessuno applicabile: non è praticamente riproducibile in un ambiente UAT reale forzare in modo affidabile un errore di rete IMAP a comando; si tratta di un comportamento di resilienza tecnica verificato dal test automatico.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
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

### F3-08 — --limit sovrascrive il default di configurazione

**Obiettivo**
Verificare che l'opzione `--limit` di `mail:fetch-inbound` sovrascriva `config('mail_pipeline.fetch.default_limit')` per quella singola esecuzione, permettendo a un tecnico di limitare il numero di messaggi elaborati in un singolo lancio manuale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-302, AC1 (limit sempre esplicito, mai "tutti gli unseen").
- Test automatico: `tests/Feature/Console/MailFetchInboundCommandTest.php` — `rispetta --limit invece del default di configurazione`.
- File/componente applicativo rilevante: `App\Console\Commands\MailFetchInboundCommand`.
- Test correlato: F3-05, F3-06.

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Alta

**Ruolo del tester**
Developer con accesso SSH al server applicativo

**Prerequisiti**
- Accesso SSH al server/container applicativo UAT.
- Almeno 2 messaggi non ancora elaborati presenti nella cartella IMAP monitorata (es. inviarne due di prova prima del test, oggetti `COLL-F3-08-20260824-01` e `COLL-F3-08-20260824-02`).

**Dati di test**
- Due email di prova con oggetti `COLL-F3-08-20260824-01` e `COLL-F3-08-20260824-02`, inviate a `<indirizzo casella di supporto UAT, fornito dal committente>` prima di lanciare il comando.

**Stato iniziale**
Le due email di prova sono presenti e non ancora archiviate in `email_messages`.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Invia le due email di prova alla casella di supporto UAT | Oggetti `COLL-F3-08-20260824-01`/`-02` | Entrambe risultano inviate |
| 2 | Via SSH, lancia il comando con limite 1 | `php artisan mail:fetch-inbound --limit=1` | Il comando termina senza errori |
| 3 | Verifica quante nuove righe `email_messages` sono state create | Query DB sulle righe con `subject` che inizia per `COLL-F3-08-` | Solo 1 delle due email risulta archiviata |
| 4 | Lancia di nuovo il comando senza `--limit` per completare l'archiviazione della seconda email | `php artisan mail:fetch-inbound` | La seconda email viene ora archiviata |

**Risultato finale atteso**
Con `--limit=1` viene elaborato al massimo un messaggio per esecuzione, indipendentemente da `mail_pipeline.fetch.default_limit`.

**Controlli negativi**
- Senza `--limit`, il comando usa il default di configurazione (verificato dal test automatico correlato `senza --limit usa mail_pipeline.fetch.default_limit`, non numerato in questo manifest).

**Evidenze da acquisire**
- Output del comando con `--limit=1`.
- Conteggio delle righe `email_messages` create al passo 3.

**Criterio di superamento**

PASS: con `--limit=1` viene archiviata al massimo un'email per esecuzione.
FAIL: vengono archiviate entrambe le email nonostante `--limit=1`.
BLOCKED: impossibile inviare le email di prova o accedere via SSH/DB.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: le email di prova restano nel registro come qualunque altra email reale.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---
## Parsing del messaggio — subject, corpo, charset (US-303)

### F3-09 — SubjectNormalizer rimuove i prefissi di risposta/inoltro anche in cascata

**Obiettivo**
Verificare che `App\Domain\Mail\Parsers\SubjectNormalizer::normalize()` rimuova in cascata i prefissi di risposta/inoltro (`Re:`/`RE:`/`R:`/`Fw:`/`Fwd:`/`AW:`/`I:`/`Rif:`) anche quando ne compaiono più d'uno concatenati, senza intaccare il resto del testo del subject.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-303, AC1 (rimozione prefissi anche ripetuti/in cascata).
- Test automatico: `tests/Unit/Domain/Mail/Parsers/SubjectNormalizerTest.php` — `rimuove prefissi in cascata` (`'Fwd: Re: RE: Problema stampante'` → `'Problema stampante'`). File correlati nello stesso test: rimozione di un singolo prefisso per ciascuna variante, estrazione del token `[#<id>]` senza rimuoverlo, decodifica di un subject con encoded-word RFC 2047.
- File/componente applicativo rilevante: `App\Domain\Mail\Parsers\SubjectNormalizer`.
- Test correlato: F3-10, F3-27.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Subject di prova: `'Fwd: Re: RE: Problema stampante'`.

**Stato iniziale**
Non applicabile: nessuno stato di sistema coinvolto, solo esecuzione di un metodo statico puro.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "rimuove prefissi in cascata"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `SubjectNormalizer::normalize('Fwd: Re: RE: Problema stampante')->subject` è `'Problema stampante'`.

**Controlli negativi**
Nessuno applicabile: le varianti singole di prefisso sono già coperte da un test parametrizzato nello stesso file.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
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

### F3-10 — EmailBodyParser preferisce il text/plain quando entrambi i corpi sono presenti

**Obiettivo**
Verificare che `App\Domain\Mail\Parsers\EmailBodyParser::parse()`, quando un messaggio ha sia `text/plain` sia `text/html`, preferisca sempre il `text/plain` per `body_text`, mantenendo comunque `body_html` sanitizzato per la visualizzazione HTML.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-303, AC2 (preferisce `text/plain`; se assente, converte l'HTML in testo, mai il contrario).
- Test automatico: `tests/Unit/Domain/Mail/Parsers/EmailBodyParserTest.php` — `preferisce il text/plain quando entrambi i corpi sono presenti`.
- File/componente applicativo rilevante: `App\Domain\Mail\Parsers\EmailBodyParser`.
- Test correlato: F3-12, F3-13.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
`textPlain = 'Testo semplice.'`, `textHtml = '<p>Testo <b>html</b>.</p>'`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "preferisce il text/plain quando entrambi i corpi sono presenti"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `bodyText` è `'Testo semplice.'` (il plain, non derivato dall'HTML) e `bodyHtml` resta `'<p>Testo <b>html</b>.</p>'`.

**Controlli negativi**
Nessuno applicabile: il caso "solo HTML" è coperto da un test dedicato nello stesso file.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
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

### F3-11 — QuotedTextRemover rimuove una citazione introdotta da "On ... wrote:"

**Obiettivo**
Verificare che `App\Domain\Mail\Parsers\QuotedTextRemover::strip()` riconosca e rimuova, in una risposta plain-text, il blocco di citazione introdotto dalla riga tipica dei client di posta anglofoni `"On ... wrote:"`, mantenendo solo il testo nuovo scritto dal mittente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-303, AC3 (riconosce `On ... wrote:`, `Il ... ha scritto:`, righe `>`, `-----Original Message-----`, firma `--`, blocchi Outlook `From:`/`Da:`; il testo rimosso resta comunque nel `.eml` archiviato).
- Test automatico: `tests/Unit/Domain/Mail/Parsers/QuotedTextRemoverTest.php` — `rimuove una citazione introdotta da "On ... wrote:"`.
- File/componente applicativo rilevante: `App\Domain\Mail\Parsers\QuotedTextRemover`.
- Test correlato: F3-10, F3-12.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Testo di prova: `"Confermo che il problema persiste.\n\nOn Wed, Feb 4, 2026 at 9:00 AM Supporto <supporto@example.test> wrote:\n> Grazie per la segnalazione."`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "rimuove una citazione introdotta da \"On ... wrote:\""` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `QuotedTextRemover::strip($text)` restituisce solo `'Confermo che il problema persiste.'`.

**Controlli negativi**
Nessuno applicabile: le altre varianti (`Il ... ha scritto:`, righe `>`, `-----Original Message-----`, firma, blocco Outlook, falso positivo su una frase che inizia per "From:") sono coperte da test dedicati nello stesso file.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
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

### F3-12 — body_html è sempre sanitizzato con la stessa allowlist del ticketing

**Obiettivo**
Verificare che `EmailBodyParser` sanitizzi sempre `body_html` riusando `App\Domain\Ticketing\Support\TicketMessageSanitizer` (la stessa allowlist già usata per i messaggi web, US-106), rimuovendo qualunque tag non ammesso (incluso `<script>`) insieme al suo contenuto, mai lasciando passare HTML non sicuro.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-303, AC4 (riuso di `TicketMessageSanitizer`, mai un output non sanitizzato salvato o mostrato — problema 9 del v1, XSS stored).
- Test automatico: `tests/Unit/Domain/Mail/Parsers/EmailBodyParserTest.php` — `sanitizza sempre il body_html rimuovendo tag non in allowlist`.
- File/componente applicativo rilevante: `App\Domain\Mail\Parsers\EmailBodyParser`, `App\Domain\Ticketing\Support\TicketMessageSanitizer`.
- Test correlato: F3-10, F3-11.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
`textHtml = '<p>Testo</p><script>alert(1)</script>'`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sanitizza sempre il body_html rimuovendo tag non in allowlist"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `bodyHtml` risultante è `'<p>Testo</p>'`, senza alcuna traccia del tag `<script>` né del suo contenuto.

**Controlli negativi**
Nessuno applicabile: questo È il controllo negativo (tag pericoloso in input, verificato assente in output).

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
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

### F3-13 — Un .eml grezzo mancante non lancia un'eccezione non gestita: il messaggio passa a failed

**Obiettivo**
Verificare che `App\Domain\Mail\Actions\ParseInboundEmail::run()` gestisca in modo controllato il caso in cui il file `.eml` referenziato da `raw_path` non esiste più sul disco, senza lanciare un'eccezione non gestita: il messaggio deve passare a `status=failed` con `failure_reason` valorizzato e un log di warning, senza fermare l'elaborazione di un batch più ampio.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-303, AC6 (un fallimento di decoding/lettura è loggato, mai un'eccezione non gestita che fa fallire l'intero fetch).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ParseInboundEmailTest.php` — `un file grezzo mancante non lancia` (verifica `status=Failed`, `failure_reason` non nullo, e un `Log::warning` ricevuto esattamente una volta).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ParseInboundEmail`.
- Test correlato: F3-05, F3-09, F3-10.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Una riga `email_messages` con `raw_path = 'inesistente.eml'` (nessun file reale su disco a quel path).

**Stato iniziale**
Non applicabile: scenario costruito interamente all'interno del test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "un file grezzo mancante non lancia"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: `ParseInboundEmail::run()` restituisce il messaggio con `status=Failed` e `failure_reason` non nullo, e viene registrato esattamente un log di livello warning — nessuna eccezione risale al chiamante.

**Controlli negativi**
Nessuno applicabile: questo test è già la verifica del caso limite (assenza del file).

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
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
## Classificazione anti-loop e scarti obbligatori (US-304)

### F3-14 — Un DSN (multipart/report, report-type delivery-status) è scartato e non va al ticketing

**Obiettivo**
Verificare che `App\Domain\Mail\Actions\ClassifyInboundEmail::run()` riconosca un messaggio `Content-Type: multipart/report; report-type=delivery-status` come una notifica di mancato recapito (DSN) e lo scarti (`status=discarded`, `failure_reason=delivery_status_notification`), instradandolo alla futura gestione bounce (US-319) invece che al ticketing.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-304, AC2 (`multipart/report; report-type=delivery-status` riconosciuto come DSN, non al ticketing).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ClassifyInboundEmailTest.php` — `un DSN (multipart/report, report-type delivery-status) è scartato e non va al ticketing` (verifica anche, nello stesso file, che un `multipart/report` con `report-type` diverso, es. `feedback-report`, NON sia trattato come DSN).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ClassifyInboundEmail`, `App\Domain\Mail\Enums\EmailDiscardReason`.
- Test correlato: F3-15, F3-76 (fuori ambito di questa porzione, US-319).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un `.eml` multipart/report sintetico (boundary + parti `text/plain` e `message/delivery-status` con `Action: failed`) con header `Content-Type: multipart/report; report-type=delivery-status`.

**Stato iniziale**
Non applicabile: nessuna riga preesistente necessaria oltre a quella creata dal test stesso in stato `Parsed`.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "un DSN \(multipart/report, report-type delivery-status\) è scartato e non va al ticketing"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: il messaggio risulta `status=Discarded` con `failure_reason` uguale al valore dell'enum `EmailDiscardReason::DeliveryStatusNotification`.

**Nota sulla scelta della modalità**
La costruzione di un `Content-Type: multipart/report` con boundary/parti MIME precise richiede la manipolazione diretta degli header e del corpo grezzo del messaggio: non è riproducibile in modo affidabile con un client di posta standard durante una sessione di collaudo funzionale, coerente con l'eccezione già prevista per gli scenari di parsing/classificazione su header particolari.

**Controlli negativi**
Nessuno applicabile: il caso "report-type diverso da delivery-status non è un DSN" è coperto da un test dedicato nello stesso file.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
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

### F3-15 — Un mittente MAILER-DAEMON/postmaster/no-reply/vuoto è scartato, un mittente normale no

**Obiettivo**
Verificare che `ClassifyInboundEmail` scarti (`status=discarded`, `failure_reason=system_sender`) i messaggi il cui mittente è `MAILER-DAEMON@...`, `postmaster@...`, `no-reply@...`, `noreply@...` o vuoto, e che un mittente normale (es. un cliente reale) non venga mai scartato da questa stessa regola.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-304, AC1 (mittente `MAILER-DAEMON`/`postmaster@`/`no-reply@`/`noreply@`/vuoto scartato).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ClassifyInboundEmailTest.php` — `un mittente MAILER-DAEMON/postmaster/no-reply/vuoto è scartato` (dataset con 5 varianti) e `un mittente normale non è scartato come mittente di sistema`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ClassifyInboundEmail`, `App\Domain\Mail\Enums\EmailDiscardReason`.
- Test correlato: F3-14, F3-16, F3-17, F3-18, F3-19.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Cinque varianti di mittente: `MAILER-DAEMON@example.test`, `postmaster@example.test`, `no-reply@example.test`, `noreply@example.test`, mittente vuoto (`''`); più un mittente normale `cliente@example.test` per il controllo negativo.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato (dataset completo) | `vendor/bin/pest --filter "un mittente MAILER-DAEMON/postmaster/no-reply/vuoto è scartato"` | Il comando termina con exit code 0, le 5 varianti del dataset risultano passed |
| 3 | Eseguire il test di controllo negativo | `vendor/bin/pest --filter "un mittente normale non è scartato come mittente di sistema"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Ognuna delle 5 varianti di mittente di sistema produce `status=Discarded`/`failure_reason=system_sender`; il mittente normale produce `status=Classified`.

**Controlli negativi**
Il passo 3 è già il controllo negativo esplicito richiesto dall'AC.

**Evidenze da acquisire**
- Output completo di entrambi i comandi Pest eseguiti.

**Criterio di superamento**

PASS: entrambi i comandi terminano con exit code 0 e i test indicati risultano passed.
FAIL: uno dei test fallisce o uno dei comandi termina con errore.
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

### F3-16 — Auto-Submitted diverso da no è scartato

**Obiettivo**
Verificare che `ClassifyInboundEmail` scarti (`status=discarded`, `failure_reason=auto_submitted`) un messaggio con header `Auto-Submitted` valorizzato diversamente da `no` (es. `auto-replied`), e che un messaggio con `Auto-Submitted: no` non venga scartato da questa regola.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-304, AC1 (header `Auto-Submitted` diverso da `no` scartato).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ClassifyInboundEmailTest.php` — `Auto-Submitted diverso da no è scartato` e `Auto-Submitted: no non è scartato`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ClassifyInboundEmail`.
- Test correlato: F3-15, F3-17.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Header `Auto-Submitted: auto-replied` per il caso da scartare; `Auto-Submitted: no` per il controllo negativo.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "Auto-Submitted diverso da no è scartato"` | Il comando termina con exit code 0, test passed |
| 3 | Eseguire il test di controllo negativo | `vendor/bin/pest --filter "Auto-Submitted: no non è scartato"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
`Auto-Submitted: auto-replied` produce `status=Discarded`/`failure_reason=auto_submitted`; `Auto-Submitted: no` produce `status=Classified`.

**Nota sulla scelta della modalità**
Impostare un header `Auto-Submitted` personalizzato non è possibile con un client di posta standard: rientra nell'eccezione prevista per gli scenari di classificazione su header particolari.

**Controlli negativi**
Il passo 3 è già il controllo negativo.

**Evidenze da acquisire**
- Output completo di entrambi i comandi Pest eseguiti.

**Criterio di superamento**

PASS: entrambi i comandi terminano con exit code 0 e i test indicati risultano passed.
FAIL: uno dei test fallisce o uno dei comandi termina con errore.
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

### F3-17 — Precedence bulk/list/junk è scartato

**Obiettivo**
Verificare che `ClassifyInboundEmail` scarti (`status=discarded`, `failure_reason=precedence`) un messaggio con header `Precedence` valorizzato `bulk`, `list` o `junk`, e che l'assenza dell'header non attivi la regola.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-304, AC1 (`Precedence: bulk`/`list`/`junk` scartato).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ClassifyInboundEmailTest.php` — `Precedence bulk/list/junk è scartato` (dataset con 3 varianti) e `Precedence assente non è scartato`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ClassifyInboundEmail`.
- Test correlato: F3-16, F3-18.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Header `Precedence: bulk` / `Precedence: list` / `Precedence: junk`; assenza dell'header per il controllo negativo.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato (dataset completo) | `vendor/bin/pest --filter "Precedence bulk/list/junk è scartato"` | Il comando termina con exit code 0, le 3 varianti risultano passed |
| 3 | Eseguire il test di controllo negativo | `vendor/bin/pest --filter "Precedence assente non è scartato"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Ognuna delle 3 varianti produce `status=Discarded`/`failure_reason=precedence`; l'assenza dell'header produce `status=Classified`.

**Controlli negativi**
Il passo 3 è già il controllo negativo.

**Evidenze da acquisire**
- Output completo di entrambi i comandi Pest eseguiti.

**Criterio di superamento**

PASS: entrambi i comandi terminano con exit code 0 e i test indicati risultano passed.
FAIL: uno dei test fallisce o uno dei comandi termina con errore.
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

### F3-18 — List-Id presente è scartato come mailing list

**Obiettivo**
Verificare che `ClassifyInboundEmail` scarti (`status=discarded`, `failure_reason=mailing_list`) un messaggio con header `List-Id` (o `List-Unsubscribe`) presente, evitando che risposte automatiche di mailing list finiscano nel ticketing.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-304, AC1 (`List-Id`/`List-Unsubscribe` scartato).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ClassifyInboundEmailTest.php` — `List-Id presente è scartato come mailing list` (con test correlati nello stesso file per `List-Unsubscribe` e per l'assenza di entrambi).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ClassifyInboundEmail`.
- Test correlato: F3-17, F3-19.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Header `List-Id: <annunci.example.test>`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "List-Id presente è scartato come mailing list"` | Il comando termina con exit code 0, test passed |
| 3 | Eseguire il test di controllo negativo | `vendor/bin/pest --filter "nessun header di mailing list non è scartato"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
`List-Id` presente produce `status=Discarded`/`failure_reason=mailing_list`; l'assenza di header di mailing list produce `status=Classified`.

**Controlli negativi**
Il passo 3 è già il controllo negativo; il caso `List-Unsubscribe` è coperto da un test analogo nello stesso file.

**Evidenze da acquisire**
- Output completo di entrambi i comandi Pest eseguiti.

**Criterio di superamento**

PASS: entrambi i comandi terminano con exit code 0 e i test indicati risultano passed.
FAIL: uno dei test fallisce o uno dei comandi termina con errore.
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

### F3-19 — X-Auto-Response-Suppress presente è scartato

**Obiettivo**
Verificare che `ClassifyInboundEmail` scarti (`status=discarded`, `failure_reason=auto_response_suppressed`) un messaggio recante l'header `X-Auto-Response-Suppress` (usato da Exchange/Outlook per indicare che il mittente non vuole ricevere risposte automatiche).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-304, AC1 (`X-Auto-Response-Suppress` scartato).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ClassifyInboundEmailTest.php` — `X-Auto-Response-Suppress presente è scartato` e `senza X-Auto-Response-Suppress non è scartato`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ClassifyInboundEmail`.
- Test correlato: F3-16, F3-18.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Header `X-Auto-Response-Suppress: All`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "X-Auto-Response-Suppress presente è scartato"` | Il comando termina con exit code 0, test passed |
| 3 | Eseguire il test di controllo negativo | `vendor/bin/pest --filter "senza X-Auto-Response-Suppress non è scartato"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
`X-Auto-Response-Suppress: All` produce `status=Discarded`/`failure_reason=auto_response_suppressed`; la sua assenza produce `status=Classified`.

**Controlli negativi**
Il passo 3 è già il controllo negativo.

**Evidenze da acquisire**
- Output completo di entrambi i comandi Pest eseguiti.

**Criterio di superamento**

PASS: entrambi i comandi terminano con exit code 0 e i test indicati risultano passed.
FAIL: uno dei test fallisce o uno dei comandi termina con errore.
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

### F3-20 — Oltre la soglia oraria il messaggio è comunque classificato ma il mittente va in loop_protection

**Obiettivo**
Verificare che, quando un mittente supera la soglia oraria configurata di messaggi inviati (`config('mail_pipeline.rate_limit.max_per_hour')`, default 3/ora), il messaggio corrente venga comunque classificato (`status=classified`, non scartato) ma il mittente venga registrato in `email_suppressions` con `reason=loop_protection` ed `expires_at` valorizzato, per impedire auto-reply futuri verso quell'indirizzo fino alla scadenza della soppressione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-304, AC3 (rate limit configurabile, default 3/ora e 10/giorno; oltre soglia nessun auto-reply, mittente in `email_suppressions` con `reason=loop_protection`).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ClassifyInboundEmailTest.php` — `oltre la soglia oraria il messaggio è comunque classificato ma il mittente va in loop_protection` (con test correlato `sotto la soglia oraria il mittente non va in soppressione`).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ClassifyInboundEmail`, `App\Domain\Mail\Models\EmailSuppression`.
- Test correlato: F3-14..F3-19, F3-38 (soppressione riusata da US-308).

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un mittente con 3 messaggi già `classified` nell'ultima ora (soglia di default `mail_pipeline.rate_limit.max_per_hour=3`), più un 4° messaggio in arrivo dallo stesso mittente.

**Stato iniziale**
3 righe `email_messages` preesistenti per lo stesso `from_email`, tutte con `status=Classified` e `received_at=now()`.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "oltre la soglia oraria il messaggio è comunque classificato ma il mittente va in loop_protection"` | Il comando termina con exit code 0, test passed |
| 3 | Eseguire il test di controllo negativo | `vendor/bin/pest --filter "sotto la soglia oraria il mittente non va in soppressione"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il 4° messaggio risulta `status=Classified` (non scartato) e viene creata una riga `email_suppressions` per quel mittente con `reason=LoopProtection` ed `expires_at` non nullo; con un solo messaggio precedente (sotto soglia) nessuna soppressione viene creata.

**Nota sulla scelta della modalità**
Il conteggio esatto degli invii ravvicinati necessari per superare la soglia (invio di più email reali nello stesso arco orario da parte del tester) non è un test affidabile da riprodurre a mano in una sessione di collaudo dal vivo: rientra esplicitamente nell'eccezione prevista per il conteggio del rate-limit anti-loop dopo N invii ravvicinati.

**Controlli negativi**
Il passo 3 è già il controllo negativo (sotto soglia).

**Evidenze da acquisire**
- Output completo di entrambi i comandi Pest eseguiti.

**Criterio di superamento**

PASS: entrambi i comandi terminano con exit code 0 e i test indicati risultano passed.
FAIL: uno dei test fallisce o uno dei comandi termina con errore.
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
- Note: Le soglie di default (3/ora, 10/giorno, sospensione 24h) sono valori proposti dal team, non ancora confermati dal committente (PRD Fase 3, §9 Open Questions) — DA VERIFICARE CON IL PRODUCT OWNER prima della chiusura definitiva della fase.

---
## Identificazione del mittente (US-305)

### F3-21 — Un mittente che corrisponde esattamente a users.email viene identificato

**Obiettivo**
Verificare che, quando un cliente registrato in piattaforma scrive una email al supporto dal proprio indirizzo reale, il sistema lo identifichi correttamente come autore/richiedente confrontando (case-insensitive) il mittente con `users.email`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-305, AC1 (match case-insensitive su `users.email`, riuso indice funzionale `lower(email)`).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ResolveEmailSenderTest.php` — `un mittente che corrisponde esattamente a users.email viene identificato` (con test correlato `il match sul mittente è case-insensitive`).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ResolveEmailSender`.
- Test correlato: F3-22, F3-23, F3-24, F3-31.

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Customer (mittente dell'email; nessun accesso al portale necessario per l'invio)

**Prerequisiti**
- Utente Customer già registrato in UAT: `infosentieroitalia@cai.it` (credenziale di collaudo, vedi Parte 1 del pacchetto).
- Accesso a quella casella email reale per inviare il messaggio di prova.
- Credenziali Admin/Developer per verificare il ticket creato nel pannello.

**Dati di test**
- Oggetto email: `COLL-F3-21-20260824-01 verifica identificazione mittente registrato`.

**Stato iniziale**
Nessun ticket con questo oggetto già presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da `infosentieroitalia@cai.it`, invia una email a `<indirizzo casella di supporto UAT, fornito dal committente>` | Oggetto `COLL-F3-21-20260824-01 verifica identificazione mittente registrato` | L'email risulta inviata |
| 2 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` per non attendere lo scheduler | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 3 | Accedi al pannello come Admin/Developer e individua il nuovo ticket | Ricerca per oggetto `COLL-F3-21-...` | Il ticket risulta creato con `Richiedente` = l'utente Customer registrato con quell'email, non un mittente non identificato |

**Risultato finale atteso**
Il ticket generato dall'email riporta come richiedente esattamente l'utente registrato il cui `email` coincide con il mittente reale.

**Controlli negativi**
Nessuno applicabile in questa modalità (il confronto case-insensitive è verificato dal test automatico).

**Evidenze da acquisire**
- Screenshot del ticket con il campo Richiedente valorizzato correttamente.

**Criterio di superamento**

PASS: il ticket viene creato con il richiedente corretto.
FAIL: il ticket non viene creato, o viene creato senza richiedente/con un richiedente errato.
BLOCKED: impossibile inviare l'email o accedere al pannello per la verifica.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il ticket di prova resta come qualunque altro ticket reale del collaudo.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F3-22 — Un mittente con sub-address (plus-addressing) viene identificato rimuovendo il tag

**Obiettivo**
Verificare che, se il cliente registrato scrive usando una variante plus-addressing del proprio indirizzo (es. `nome+tag@dominio`), il sistema lo identifichi comunque come lo stesso utente rimuovendo il tag e confrontando la parte `nome@dominio` con `users.email`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-305, AC2 (se non trovato il match esatto, prova il sub-address `nome+tag@dominio` → `nome@dominio`).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ResolveEmailSenderTest.php` — `un mittente con sub-address (plus-addressing) viene identificato rimuovendo il tag` (con test correlato `un match esatto ha priorità sul sub-address quando entrambi esisterebbero`).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ResolveEmailSender`.
- Test correlato: F3-21, F3-23.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Utente Customer registrato `infosentieroitalia@cai.it`, con un provider di posta che supporti il plus-addressing sullo stesso dominio/casella (da verificare con il committente/IT del cliente prima del test: non tutti i domini aziendali lo abilitano).
- Credenziali Admin/Developer per la verifica nel pannello.

**Dati di test**
- Oggetto email: `COLL-F3-22-20260824-01 verifica plus-addressing`.
- Mittente: una variante plus-addressing dell'indirizzo registrato (es. `infosentieroitalia+collaudo@cai.it`, se il dominio la instrada alla stessa casella).

**Stato iniziale**
Nessun ticket con questo oggetto già presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Invia una email dalla variante plus-addressing dell'indirizzo registrato a `<indirizzo casella di supporto UAT, fornito dal committente>` | Oggetto `COLL-F3-22-20260824-01 verifica plus-addressing` | L'email risulta inviata |
| 2 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 3 | Accedi come Admin/Developer e individua il nuovo ticket | Ricerca per oggetto `COLL-F3-22-...` | Il ticket risulta creato con `Richiedente` = lo stesso utente registrato (non un mittente sconosciuto/quarantena) |

**Risultato finale atteso**
Il sub-address viene ricondotto correttamente all'utente registrato con l'indirizzo base.

**Controlli negativi**
Nessuno applicabile in questa modalità.

**Evidenze da acquisire**
- Screenshot del ticket con il richiedente corretto nonostante il sub-address usato come mittente.

**Criterio di superamento**

PASS: il ticket viene creato con il richiedente corretto.
FAIL: il ticket finisce in quarantena (mittente non identificato) o viene attribuito a un utente errato.
BLOCKED: impossibile inviare l'email con plus-addressing (dominio non lo supporta) o accedere al pannello.
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

### F3-23 — Nessuna identificazione per solo dominio, anche con mittente sullo stesso dominio

**Obiettivo**
Verificare che il sistema NON identifichi mai un mittente sulla sola base del dominio: un indirizzo che condivide il dominio con un utente registrato ma non corrisponde a nessun `users.email` (né esattamente né via sub-address) deve restare non identificato, mai attribuito per errore a un collega dello stesso dominio.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-305, AC3 (non inferisce l'utente dal solo dominio del mittente — rischio di attribuzione errata, esplicitamente vietato dal PRD).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ResolveEmailSenderTest.php` — `un mittente sullo stesso dominio ma senza nessun utente corrispondente non viene mai identificato per solo dominio`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ResolveEmailSender`.
- Test correlato: F3-21, F3-22, F3-24.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Cliente esterno (mittente non registrato, ma sullo stesso dominio di un utente registrato)

**Prerequisiti**
- Un utente registrato in UAT su un dominio noto (es. `infosentieroitalia@cai.it`, dominio `cai.it`).
- Un secondo indirizzo email reale sullo stesso dominio `cai.it` MA che non corrisponde a nessun utente registrato in piattaforma (da concordare con il committente/IT del dominio prima del test, o simulare con un indirizzo dedicato al collaudo su un dominio comunque diverso, documentando la differenza nelle Note).
- Credenziali Admin/Developer per la verifica.

**Dati di test**
- Oggetto email: `COLL-F3-23-20260824-01 verifica non-identificazione per solo dominio`.

**Stato iniziale**
Nessun ticket con questo oggetto già presente; nessun utente registrato con l'indirizzo mittente usato in questo test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Invia una email dall'indirizzo dello stesso dominio ma non registrato, a `<indirizzo casella di supporto UAT, fornito dal committente>` | Oggetto `COLL-F3-23-20260824-01 verifica non-identificazione per solo dominio` | L'email risulta inviata |
| 2 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 3 | Accedi come Admin/Developer e verifica l'esito | Ricerca per oggetto `COLL-F3-23-...` nel registro email/quarantena | NESSUN ticket viene creato attribuendo il messaggio all'utente registrato dello stesso dominio; il messaggio risulta in quarantena, mittente non identificato |

**Risultato finale atteso**
Il messaggio non viene mai attribuito per errore a un utente diverso solo perché condivide il dominio: resta in quarantena.

**Controlli negativi**
Nessuno applicabile in questa modalità: l'assenza di attribuzione È il comportamento atteso.

**Evidenze da acquisire**
- Screenshot che mostri l'assenza di un ticket attribuito erroneamente e la presenza del messaggio in quarantena.

**Criterio di superamento**

PASS: nessun ticket viene attribuito all'utente del dominio omonimo; il messaggio risulta in quarantena.
FAIL: il messaggio viene attribuito a un utente registrato solo per coincidenza di dominio.
BLOCKED: impossibile reperire un indirizzo di prova sullo stesso dominio non registrato, o accedere al pannello.
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

### F3-24 — Un mittente non identificato va in quarantena, mai scartato

**Obiettivo**
Verificare che un'email proveniente da un mittente completamente sconosciuto (nessuna corrispondenza né esatta né via sub-address né di dominio) non venga mai scartata: il messaggio deve restare in `status=quarantined`, ispezionabile in amministrazione, mai perso.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-305, AC4 (se non identificato, il messaggio è marcato per la gestione di US-308/quarantena, mai scartato).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ResolveEmailSenderTest.php` — `un mittente non identificato va in quarantena, mai scartato` (con test correlato `un mittente vuoto va in quarantena`).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ResolveEmailSender`.
- Test correlato: F3-23, F3-36.

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Cliente esterno (mittente mai registrato in piattaforma)

**Prerequisiti**
- Un indirizzo email reale mai usato prima in questo ambiente UAT (es. una casella di test personale del tester, es. `collaudo-esterno-f324@<tuodominio>.test`).
- Credenziali Admin/Developer per la verifica in amministrazione.

**Dati di test**
- Oggetto email: `COLL-F3-24-20260824-01 verifica quarantena mittente sconosciuto`.

**Stato iniziale**
Nessun utente registrato con l'indirizzo mittente usato in questo test; nessun ticket con questo oggetto già presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Invia una email da un indirizzo mai registrato a `<indirizzo casella di supporto UAT, fornito dal committente>` | Oggetto `COLL-F3-24-20260824-01 verifica quarantena mittente sconosciuto` | L'email risulta inviata |
| 2 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 3 | Accedi come Admin/Developer e verifica l'esito | Ricerca per oggetto `COLL-F3-24-...` | Il messaggio risulta in quarantena, MAI scartato: nessun ticket creato, il messaggio resta ispezionabile |

**Risultato finale atteso**
Il messaggio finisce in quarantena, non nello stato "scartato".

**Controlli negativi**
Nessuno applicabile in questa modalità.

**Evidenze da acquisire**
- Screenshot del messaggio in quarantena.

**Criterio di superamento**

PASS: il messaggio risulta in quarantena, ispezionabile.
FAIL: il messaggio risulta scartato/perso, o viene attribuito erroneamente a un ticket.
BLOCKED: impossibile inviare l'email o accedere al pannello per la verifica.
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
## Risoluzione del thread — VERP, In-Reply-To, subject, euristica (US-306)

### F3-25 — Livello 1 (VERP): un token ticket+ulid valido nel To risolve il ticket_message e il suo ticket

**Obiettivo**
Verificare che una risposta inviata all'indirizzo VERP `ticket+<ulid>@dominio` (il `Reply-To` di una notifica reale generata dalla pipeline) risolva correttamente il thread al ticket originale, con il livello di match più affidabile (VERP), senza creare un nuovo ticket duplicato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-306, AC1/AC2 (ogni email in uscita usa `Reply-To: ticket+<ulid>@dominio`; in ingresso, livello 1 prova il token `ticket+<ulid>` nel `To`). Open Question del PRD: verifica concreta del plus-addressing `ticket+*@dominio` contro il dominio reale, non ancora testata end-to-end prima di questo collaudo.
- Test automatico: `tests/Feature/Domain/Mail/Actions/ResolveEmailThreadTest.php` — `livello 1 (VERP): un token ticket+ulid valido nel To risolve il ticket_message e il suo ticket`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ResolveEmailThread`, `App\Domain\Mail\Enums\ThreadMatchLevel`.
- Test correlato: F3-26, F3-29, F3-32.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Critica

**Ruolo del tester**
Customer (mittente della risposta) + Admin/Developer (per generare la notifica iniziale e verificare l'esito)

**Prerequisiti**
- Un ticket già esistente con almeno una notifica in uscita già inviata (es. crea un ticket via portale come Customer per generare E2, oppure rispondi a un ticket esistente per generare E5/E4) in modo che una riga `email_messages` outbound con `reply_to` valorizzato esista.
- Accesso a Mailpit UAT: `https://mailpit-ticket-uat.montagnaservizi.com`.

**Dati di test**
- Ticket di prova `COLL-F3-25-20260824-01`.

**Stato iniziale**
Nessun ticket/thread con questo titolo già presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Come Customer, crea un nuovo ticket dal portale | Titolo `COLL-F3-25-20260824-01` | Il ticket viene creato; una notifica (E2) viene accodata |
| 2 | Apri Mailpit e individua la notifica appena inviata | — | L'email è presente, con header `Reply-To` nella forma `ticket+<ulid>@<dominio>` |
| 3 | Annota l'indirizzo esatto del `Reply-To` | Copia l'indirizzo dall'header/sorgente del messaggio in Mailpit | Indirizzo VERP annotato |
| 4 | Invia una NUOVA email (dalla propria casella) indirizzata proprio a quell'indirizzo VERP | Oggetto libero, es. `Re: COLL-F3-25-20260824-01` | L'email risulta inviata |
| 5 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 6 | Accedi come Admin/Developer e verifica il ticket `COLL-F3-25-20260824-01` | — | È comparso un nuovo messaggio sul ticket ESISTENTE; NON è stato creato un secondo ticket |

**Risultato finale atteso**
La risposta via indirizzo VERP si aggancia al ticket originale (livello 1, il più affidabile), senza generare un ticket duplicato. Questo test costituisce anche la verifica concreta, contro il dominio email reale di Montagna Servizi, del funzionamento del plus-addressing `ticket+*@dominio` richiesta come Open Question dal PRD prima della chiusura di US-306.

**Controlli negativi**
Nessuno applicabile in questa modalità.

**Evidenze da acquisire**
- Screenshot dell'header `Reply-To` in Mailpit.
- Screenshot del ticket con il nuovo messaggio accodato (nessun ticket duplicato).

**Criterio di superamento**

PASS: la risposta si aggancia al ticket esistente, nessun duplicato creato.
FAIL: viene creato un nuovo ticket, o la risposta non risulta agganciata a nulla.
BLOCKED: impossibile generare la notifica iniziale o accedere a Mailpit/pannello.
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

### F3-26 — Livello 2 (In-Reply-To): un In-Reply-To che referenzia un message_id esistente risolve il ticket

**Obiettivo**
Verificare che, quando l'header `In-Reply-To` (o `References`) di un'email in ingresso referenzia il `message_id` di una notifica outbound già collegata a un ticket, il thread venga risolto a quel ticket (livello 2), anche in assenza di un token VERP nel `To`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-306, AC2 punto 2 (`In-Reply-To`/`References` confrontati con `email_messages.message_id`).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ResolveEmailThreadTest.php` — `livello 2 (In-Reply-To): un In-Reply-To che referenzia un message_id esistente collegato a un ticket risolve quel ticket` (con test correlato per `References`, separata da spazi).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ResolveEmailThread`.
- Test correlato: F3-25, F3-29.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un ticket con una riga `email_messages` outbound `message_id='notifica-1@example.test'`; un'email in ingresso con `in_reply_to='notifica-1@example.test'`.

**Stato iniziale**
Non applicabile: scenario costruito interamente all'interno del test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "livello 2 \(In-Reply-To\): un In-Reply-To che referenzia un message_id esistente collegato a un ticket risolve quel ticket"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: il ticket viene risolto con `matchLevel=InReplyTo`.

**Nota sulla scelta della modalità**
Impostare manualmente l'header `In-Reply-To` con un client di posta standard non è praticabile in un ambiente reale (i client di posta lo generano automaticamente solo rispondendo a un'email realmente ricevuta, e in UAT le notifiche finiscono su Mailpit anziché nella casella reale del cliente): rientra nell'eccezione per gli scenari con header particolari.

**Controlli negativi**
Nessuno applicabile: il caso `References` è coperto da un test dedicato nello stesso file.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
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

### F3-27 — Livello 3 (token subject): [#<id>] nel subject normalizzato risolve direttamente il ticket

**Obiettivo**
Verificare che un'email il cui subject contenga il token `[#<id ticket>]` (anche preceduto da `Re:`) venga agganciata direttamente a quel ticket (livello 3), utile in particolare per i ticket importati dal v1 dove non è disponibile un indirizzo VERP storico.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-306, AC2 punto 3 (token `[#<id ticket>]` nel subject normalizzato, cercato con regex ancorata — funziona sui ticket importati dal v1).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ResolveEmailThreadTest.php` — `livello 3 (token subject): [#<id>] nel subject normalizzato risolve direttamente il ticket`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ResolveEmailThread`, `App\Domain\Mail\Parsers\SubjectNormalizer`.
- Test correlato: F3-09, F3-29, F3-32.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Customer + Admin/Developer

**Prerequisiti**
- Un ticket esistente di cui si conosce l'id numerico (es. creato al passo 1 sotto, oppure un ticket già esistente).

**Dati di test**
- Ticket di prova `COLL-F3-27-20260824-01`.

**Stato iniziale**
Nessun ticket con questo titolo già presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Come Admin/Developer, crea (o individua) un ticket e annotane l'id numerico | Titolo `COLL-F3-27-20260824-01` | Ticket creato, es. id `1234` |
| 2 | Da Customer, invia una NUOVA email a `<indirizzo casella di supporto UAT, fornito dal committente>` con subject contenente il token | Oggetto `Re: [#1234] COLL-F3-27-20260824-01` | L'email risulta inviata |
| 3 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 4 | Verifica il ticket id `1234` | — | È comparso un nuovo messaggio sul ticket esistente; NON è stato creato un ticket separato |

**Risultato finale atteso**
Il token `[#1234]` nel subject aggancia la risposta al ticket corrispondente, anche senza alcun header VERP/In-Reply-To.

**Controlli negativi**
Nessuno applicabile in questa modalità.

**Evidenze da acquisire**
- Screenshot del ticket con il nuovo messaggio accodato.

**Criterio di superamento**

PASS: la risposta si aggancia al ticket indicato dal token subject.
FAIL: viene creato un nuovo ticket separato, o la risposta non risulta agganciata a nulla.
BLOCKED: impossibile inviare l'email o accedere al pannello.
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

### F3-28 — Livello 4 (euristica): stesso mittente + subject identico + thread aperto di recente, marcato come euristico

**Obiettivo**
Verificare che, in assenza di VERP/In-Reply-To/token subject, un'email dallo stesso mittente e con subject normalizzato identico a quello di un thread aperto negli ultimi N giorni (default 30, configurabile) venga comunque agganciata a quel ticket tramite l'euristica di livello 4, marcata esplicitamente come match euristico (non certo) in amministrazione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-306, AC2 punto 4 (euristica: stesso mittente + subject normalizzato identico + thread aperto negli ultimi N giorni, registrando esplicitamente che il match è euristico).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ResolveEmailThreadTest.php` — `livello 4 (euristica): stesso mittente + subject normalizzato identico + thread aperto di recente risolve il ticket, marcato esplicitamente come euristico` (con test correlati per mittente diverso e per thread fuori finestra, che NON producono match).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ResolveEmailThread`, `App\Domain\Mail\Models\EmailThread`, `App\Domain\Mail\Enums\ThreadMatchLevel`.
- Test correlato: F3-27, F3-29, F3-30.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Customer + Admin/Developer

**Prerequisiti**
- Un ticket con un thread email già associato (es. creato tramite un'email inbound precedente, così che esista una riga `email_threads` con `subject_normalized` e `last_message_at` recenti).

**Dati di test**
- Ticket di prova `COLL-F3-28-20260824-01`, subject esatto da riusare identico (senza alcun token `[#id]` e senza rispondere formalmente all'email precedente, in modo che il client di posta non generi automaticamente `In-Reply-To`).

**Stato iniziale**
Un thread email esistente sul ticket `COLL-F3-28-20260824-01`, aperto negli ultimi giorni.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da Customer, apri il ticket precedente e nota il subject esatto della conversazione email associata | — | Subject annotato |
| 2 | Componi una NUOVA email (non una risposta/reply del client, ma un messaggio nuovo) con lo STESSO subject, dallo stesso indirizzo mittente, verso `<indirizzo casella di supporto UAT, fornito dal committente>` | Subject identico a quello annotato | L'email risulta inviata, senza header `In-Reply-To`/token `[#id]` |
| 3 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 4 | Verifica il ticket | — | È comparso un nuovo messaggio sullo stesso ticket, non un ticket separato |
| 5 | (Verifica tecnica facoltativa) Nel Registro email/amministrazione, verifica che il match sia segnalato come euristico | — | Il messaggio riporta il livello di match "Euristica", non un livello certo |

**Risultato finale atteso**
Il messaggio si aggancia al ticket via euristica, esplicitamente marcata come tale.

**Controlli negativi**
Nessuno applicabile in questa modalità.

**Evidenze da acquisire**
- Screenshot del ticket con il nuovo messaggio accodato e, se disponibile, del livello di match "Euristica" in amministrazione.

**Criterio di superamento**

PASS: la risposta si aggancia al ticket via euristica.
FAIL: viene creato un nuovo ticket separato.
BLOCKED: impossibile riprodurre la condizione (nessun header automatico generato dal client di posta) o accedere al pannello.
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

### F3-29 — Un match di livello più affidabile (In-Reply-To) non è mai scavalcato dall'euristica

**Obiettivo**
Verificare che, quando un'email ha sia un `In-Reply-To` valido (livello 2) sia le condizioni per un match euristico (livello 4) verso un ticket DIVERSO (stesso subject normalizzato, mittente compatibile), il sistema risolva sempre al ticket indicato dal livello più affidabile (In-Reply-To), mai a quello suggerito dall'euristica.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-306, AC3 (un test per livello che dimostra che un match al livello N non viene mai scavalcato da un livello successivo meno affidabile).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ResolveEmailThreadTest.php` — `livello 2: un In-Reply-To valido non viene mai scavalcato dall'euristica (livello 4)` (test analogo per il livello 3/token subject nello stesso file).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ResolveEmailThread`.
- Test correlato: F3-26, F3-27, F3-28.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Due ticket: uno collegato via `In-Reply-To` a un `message_id` noto, un altro con un `EmailThread` dallo stesso subject normalizzato ("stesso oggetto") e stesso mittente. L'email in ingresso ha entrambe le condizioni contemporaneamente.

**Stato iniziale**
Non applicabile: scenario costruito interamente all'interno del test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "livello 2: un In-Reply-To valido non viene mai scavalcato"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: il risultato riporta il ticket collegato via `In-Reply-To`, con `matchLevel=InReplyTo`, mai il ticket suggerito dall'euristica.

**Nota sulla scelta della modalità**
Costruire in un'unica email sia un `In-Reply-To` valido sia le condizioni per un match euristico su un ticket diverso richiede la manipolazione diretta degli header, non riproducibile con un client di posta standard: rientra nell'eccezione per gli scenari con header particolari.

**Controlli negativi**
Nessuno applicabile: il caso analogo per il livello 3 (token subject) è coperto da un test dedicato nello stesso file.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
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

### F3-30 — Nessun match sui quattro livelli restituisce una risoluzione vuota (nuovo ticket)

**Obiettivo**
Verificare che, quando nessuno dei quattro livelli di risoluzione del thread produce un match (nuovo mittente, subject mai visto, nessun header di threading), il sistema restituisca una risoluzione vuota, che porta alla creazione di un nuovo ticket (US-307) invece che a un aggancio forzato a un ticket esistente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-306, AC2 (nessun match sui quattro livelli → il messaggio genera un nuovo ticket, US-307).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ResolveEmailThreadTest.php` — `nessun match su nessuno dei quattro livelli restituisce una risoluzione vuota (nuovo ticket)`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ResolveEmailThread`, `App\Domain\Mail\Support\ThreadResolution`.
- Test correlato: F3-25..F3-29, F3-31.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Utente Customer registrato `infosentieroitalia@cai.it` (nessun ticket/thread precedente con lo stesso subject).

**Dati di test**
- Oggetto email: `COLL-F3-30-20260824-01 richiesta del tutto nuova`.

**Stato iniziale**
Nessun ticket/thread con questo subject già presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da `infosentieroitalia@cai.it`, invia una NUOVA email (mai una risposta) a `<indirizzo casella di supporto UAT, fornito dal committente>` | Oggetto `COLL-F3-30-20260824-01 richiesta del tutto nuova` | L'email risulta inviata |
| 2 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 3 | Accedi come Admin/Developer e verifica l'esito | Ricerca per oggetto `COLL-F3-30-...` | Viene creato un NUOVO ticket (non un aggancio a un ticket preesistente) |

**Risultato finale atteso**
In assenza di qualunque segnale di threading, il sistema apre correttamente un nuovo ticket.

**Controlli negativi**
Nessuno applicabile in questa modalità.

**Evidenze da acquisire**
- Screenshot del nuovo ticket creato.

**Criterio di superamento**

PASS: viene creato un nuovo ticket.
FAIL: il messaggio viene erroneamente agganciato a un ticket esistente.
BLOCKED: impossibile inviare l'email o accedere al pannello.
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
## Applicazione — creazione ticket o nuovo messaggio, notifiche post-commit (US-307)

### F3-31 — Mittente identificato senza match di thread crea un nuovo ticket helpdesk con il primo messaggio via email

**Obiettivo**
Verificare che un'email inviata da un mittente identificato (Customer registrato), senza alcun match di thread, generi un nuovo ticket con `type=helpdesk`, `title` = subject normalizzato, `requester` = l'utente identificato, e un primo `ticket_message` con `channel=email` contenente il corpo dell'email.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-307, AC1 (nuovo ticket: riusa `CreateTicket`, `title`=subject normalizzato, primo `ticket_message` `channel=email`, `type=helpdesk`, `requester_id`=utente identificato).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php` — `mittente identificato senza match di thread crea un nuovo ticket helpdesk con il primo messaggio via email` (con test correlato `un corpo solo testo viene convertito in HTML sicuro prima di essere pubblicato sul ticket`).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ApplyInboundEmail`, `App\Domain\Ticketing\Actions\CreateTicket`, `App\Domain\Ticketing\Actions\PostTicketMessage`.
- Test correlato: F3-21, F3-30, F3-50.

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Customer + Admin/Developer

**Prerequisiti**
- Utente Customer registrato `infosentieroitalia@cai.it`.
- Credenziali Admin/Developer per la verifica del ticket creato.

**Dati di test**
- Oggetto email: `COLL-F3-31-20260824-01 non riesco ad accedere al portale`.
- Corpo: un breve testo descrittivo del problema.

**Stato iniziale**
Nessun ticket con questo titolo già presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da `infosentieroitalia@cai.it`, invia una email a `<indirizzo casella di supporto UAT, fornito dal committente>` | Oggetto `COLL-F3-31-20260824-01 non riesco ad accedere al portale` | L'email risulta inviata |
| 2 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 3 | Accedi come Admin/Developer e individua il nuovo ticket | Ricerca per titolo `COLL-F3-31-...` | Il ticket risulta creato con titolo uguale al subject, tipo Helpdesk, richiedente = utente Customer |
| 4 | Apri il dettaglio del ticket e la conversazione | — | È presente un primo messaggio con canale "Email" e corpo coerente con quanto inviato |

**Risultato finale atteso**
Un nuovo ticket helpdesk viene creato correttamente con il primo messaggio via email.

**Controlli negativi**
Nessuno applicabile in questa modalità.

**Evidenze da acquisire**
- Screenshot del ticket con titolo/tipo/richiedente corretti.
- Screenshot del primo messaggio con canale "Email".

**Criterio di superamento**

PASS: il ticket viene creato con titolo, tipo, richiedente e primo messaggio corretti.
FAIL: il ticket non viene creato, o uno di questi campi risulta errato.
BLOCKED: impossibile inviare l'email o accedere al pannello.
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

### F3-32 — Mittente identificato con match di thread accoda un messaggio sul ticket esistente invece di crearne uno nuovo

**Obiettivo**
Verificare che, quando il thread viene risolto a un ticket esistente (qui: via token subject `[#id]`), il sistema accodi un nuovo messaggio su quel ticket invece di creare un secondo ticket duplicato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-307, AC2 (risposta a ticket esistente: nuovo `ticket_message` con `channel=email`, applica T7 se l'autore è il richiedente).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php` — `mittente identificato con match di thread accoda un messaggio sul ticket esistente invece di crearne uno nuovo`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ApplyInboundEmail`.
- Test correlato: F3-27, F3-31, F3-35.

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Customer + Admin/Developer

**Prerequisiti**
- Un ticket esistente con richiedente = utente Customer registrato `infosentieroitalia@cai.it`, di cui si conosce l'id numerico.

**Dati di test**
- Ticket esistente `COLL-F3-32-20260824-01`.

**Stato iniziale**
Il ticket `COLL-F3-32-20260824-01` esiste già, con un solo messaggio.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Come Admin/Developer, crea il ticket di prova e annotane l'id | Titolo `COLL-F3-32-20260824-01` | Ticket creato, es. id `2001` |
| 2 | Da `infosentieroitalia@cai.it`, invia una email con subject contenente il token | Oggetto `Re: [#2001] COLL-F3-32-20260824-01` | L'email risulta inviata |
| 3 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 4 | Verifica il numero di ticket con questo titolo | Ricerca per titolo `COLL-F3-32-...` | Esiste UN SOLO ticket con questo titolo (`Ticket::count()` invariato) |
| 5 | Apri il ticket `2001` e la conversazione | — | È presente un secondo messaggio, accodato su quel ticket |

**Risultato finale atteso**
Il messaggio viene accodato sul ticket esistente, senza generarne un secondo.

**Controlli negativi**
Nessuno applicabile in questa modalità.

**Evidenze da acquisire**
- Screenshot che mostri un solo ticket con questo titolo e il nuovo messaggio accodato.

**Criterio di superamento**

PASS: il messaggio si aggancia al ticket esistente, nessun duplicato creato.
FAIL: viene creato un secondo ticket con lo stesso titolo.
BLOCKED: impossibile inviare l'email o accedere al pannello.
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

### F3-33 — Un fallimento nella risoluzione del ticket esistente annulla sia il messaggio sia l'aggiornamento di email_messages (stessa transazione)

**Obiettivo**
Verificare che, quando il thread viene risolto (via token subject) a un id ticket che in realtà NON esiste, l'intera operazione fallisca in modo controllato (nessuna eccezione non gestita) e venga annullata per intero nella stessa transazione: nessun ticket/messaggio orfano viene creato, e `email_messages` non viene marcata come applicata.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-307, AC3 (R4: creazione/aggiornamento ticket e aggiornamento `email_messages` nella stessa transazione; un fallimento in una delle due annulla entrambe).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php` — `un fallimento nella risoluzione del ticket esistente annulla sia la creazione del messaggio sia l'aggiornamento di email_messages`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ApplyInboundEmail`.
- Test correlato: F3-27, F3-32.

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Customer + Admin/Developer (verifica tecnica)

**Prerequisiti**
- Utente Customer registrato `infosentieroitalia@cai.it`.
- Accesso Admin/Developer (o SSH/DB) per verificare l'esito tecnico del messaggio.

**Dati di test**
- Oggetto email: `Re: [#999999999] COLL-F3-33-20260824-01 ticket inesistente` (un id ticket volutamente inesistente).

**Stato iniziale**
Nessun ticket con id `999999999` esiste in piattaforma (id palesemente fuori range).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da `infosentieroitalia@cai.it`, invia una email con subject che referenzia un id ticket inesistente | Oggetto `Re: [#999999999] COLL-F3-33-20260824-01 ticket inesistente` | L'email risulta inviata |
| 2 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori (nessuna eccezione risale al chiamante) |
| 3 | Verifica che non sia stato creato alcun ticket con questo titolo | Ricerca per titolo `COLL-F3-33-...` | Nessun ticket trovato |
| 4 | (Verifica tecnica facoltativa) Nel Registro email/amministrazione (o via query DB), individua il messaggio inbound corrispondente | Ricerca per oggetto | Il messaggio risulta in stato "Fallita", con un motivo di fallimento valorizzato; nessun `ticket_message` orfano risulta collegato |

**Risultato finale atteso**
Nessun ticket/messaggio viene creato; il messaggio email risulta marcato come fallito, senza propagare un errore fatale al comando.

**Controlli negativi**
Nessuno applicabile: questo test è già la verifica del caso limite.

**Evidenze da acquisire**
- Screenshot che mostri l'assenza del ticket.
- Screenshot/estratto del messaggio in stato "Fallita" (se il registro email è già consultabile in questo ambiente).

**Criterio di superamento**

PASS: nessun ticket viene creato, il messaggio risulta in stato di fallimento controllato.
FAIL: viene creato un ticket/messaggio orfano, oppure il comando `mail:fetch-inbound` termina con un errore fatale non gestito.
BLOCKED: impossibile inviare l'email o accedere alla verifica tecnica.
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

### F3-34 — Un fallimento nella notifica post-commit non annulla il ticket/messaggio già creati (problema 2 del v1)

**Obiettivo**
Verificare che, se l'invio della notifica post-commit (E1/E3) fallisce dopo che il ticket e il messaggio sono già stati creati e committati, il ticket e il messaggio RESTANO comunque creati: a differenza del v1 (dove un fallimento SMTP impediva di marcare l'email come elaborata, causando duplicati infiniti — problema 2 del v1), un fallimento nella notifica è isolato e non annulla nulla di già persistito.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-307, AC4 (dopo il commit, in coda: notifiche mai dentro la transazione; un fallimento della notifica non deve mai disfare ticket/messaggio già committati).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php` — `un fallimento nella notifica post-commit non annulla il ticket/messaggio già creati (problema 2 del v1)`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ApplyInboundEmail`, `App\Domain\Mail\Events\InboundEmailApplied`.
- Test correlato: F3-33, F3-50.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un listener registrato temporaneamente sull'evento `InboundEmailApplied` che lancia un'eccezione (simula un fallimento della coda di notifica).

**Stato iniziale**
Non applicabile: scenario costruito interamente all'interno del test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "un fallimento nella notifica post-commit non annulla il ticket/messaggio già creati"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: nonostante l'eccezione simulata durante la notifica, il ticket risulta `status=Applied` con il messaggio già presente e `email_messages.status=Applied`.

**Nota sulla scelta della modalità**
Forzare un fallimento della coda di notifica esattamente nella finestra tra il commit della transazione e l'invio della notifica non è riproducibile in modo affidabile in un ambiente UAT reale senza introdurre fault injection nel codice: rientra nell'eccezione per i comportamenti tecnici di resilienza.

**Controlli negativi**
Nessuno applicabile: questo test è già la verifica del caso limite.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
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

### F3-35 — La risposta del richiedente via email applica la transizione T7 (waiting torna a previous_status)

**Obiettivo**
Verificare che, quando il RICHIEDENTE di un ticket in stato "In attesa" (waiting) risponde via email, la macchina a stati applichi automaticamente la transizione T7: lo stato torna a `previous_status` (lo stato in cui il ticket si trovava prima di passare in attesa), senza intervento manuale dello staff.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-307, AC2 (applica la transizione T7 già esistente, `RestoreTicketStatusOnRequesterMessage`, US-106, quando l'autore è il richiedente).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php` — `la risposta del richiedente via email applica la transizione T7 (waiting torna a previous_status)`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ApplyInboundEmail`, `App\Domain\Ticketing\Listeners\RestoreTicketStatusOnRequesterMessage`.
- Test correlato: F3-32.

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Admin/Developer (per predisporre lo stato del ticket) + Customer (per la risposta via email)

**Prerequisiti**
- Un ticket con richiedente = utente Customer registrato `infosentieroitalia@cai.it`, portato manualmente in stato "In attesa" da uno stato precedente noto (es. "Da fare").

**Dati di test**
- Ticket di prova `COLL-F3-35-20260824-01`.

**Stato iniziale**
Il ticket `COLL-F3-35-20260824-01` è in stato "In attesa", con stato precedente noto (es. "Da fare").

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Come Admin/Developer, crea il ticket, annotane l'id, e portalo in stato "In attesa" tramite il bottone di transizione | Titolo `COLL-F3-35-20260824-01`, es. id `2050` | Il ticket risulta "In attesa", stato precedente registrato |
| 2 | Da `infosentieroitalia@cai.it`, rispondi via email referenziando quel ticket | Oggetto `Re: [#2050] COLL-F3-35-20260824-01` | L'email risulta inviata |
| 3 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 4 | Verifica lo stato del ticket `2050` | — | Lo stato è tornato a quello precedente ("Da fare"), non più "In attesa" |

**Risultato finale atteso**
La risposta del richiedente riattiva automaticamente il ticket, riportandolo allo stato precedente.

**Controlli negativi**
Nessuno applicabile in questa modalità (il caso "un membro dello staff diverso dal richiedente risponde" non applica T7 ed è fuori ambito di questo test specifico).

**Evidenze da acquisire**
- Screenshot dello stato "In attesa" prima della risposta.
- Screenshot dello stato ripristinato dopo la risposta.

**Criterio di superamento**

PASS: lo stato del ticket torna a quello precedente dopo la risposta del richiedente.
FAIL: lo stato resta "In attesa" o cambia in modo diverso da quello atteso.
BLOCKED: impossibile predisporre lo stato del ticket o inviare l'email.
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
## Mittente non riconosciuto — quarantena (US-308)

### F3-36 — Mittente non identificato non crea nessun ticket e lascia il messaggio in quarantena, mai scartato

**Obiettivo**
Verificare che un'email da un mittente mai visto prima non generi mai un ticket e non venga mai scartata: il messaggio deve restare in quarantena, ispezionabile in amministrazione, in attesa che lo staff lo associ a un cliente esistente o ne crei uno nuovo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-308, AC1 (un messaggio classificato ma con mittente non identificato va in `status=quarantined`, mai scartato).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php` — `mittente non identificato non crea nessun ticket e lascia il messaggio in quarantena`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ApplyInboundEmail`, `App\Domain\Mail\Actions\ResolveEmailSender`.
- Test correlato: F3-24, F3-37, F3-38.

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Cliente esterno (mittente mai registrato) + Admin/Developer (verifica)

**Prerequisiti**
- Un indirizzo email reale mai registrato in questo ambiente UAT.
- Credenziali Admin/Developer per la verifica in amministrazione.

**Dati di test**
- Oggetto email: `COLL-F3-36-20260824-01 informazioni sui vostri servizi` (analogo, per stile, alla fixture reale `checkpoint-mittente-sconosciuto.eml` usata dai test automatici end-to-end di questa fase).

**Stato iniziale**
Nessun ticket con questo titolo, nessun utente registrato con l'indirizzo mittente usato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da un indirizzo mai registrato, invia una email a `<indirizzo casella di supporto UAT, fornito dal committente>` | Oggetto `COLL-F3-36-20260824-01 informazioni sui vostri servizi` | L'email risulta inviata |
| 2 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 3 | Accedi come Admin/Developer e verifica che non sia stato creato alcun ticket | Ricerca per titolo `COLL-F3-36-...` | Nessun ticket creato |
| 4 | Verifica che il messaggio sia in quarantena, non scartato | Registro email/Quarantena | Il messaggio risulta in stato "In quarantena" |

**Risultato finale atteso**
Nessun ticket viene creato; il messaggio resta in quarantena, mai scartato.

**Controlli negativi**
Nessuno applicabile in questa modalità.

**Evidenze da acquisire**
- Screenshot del messaggio in quarantena.

**Criterio di superamento**

PASS: nessun ticket creato, messaggio in quarantena.
FAIL: il messaggio viene scartato o attribuito erroneamente a un ticket.
BLOCKED: impossibile inviare l'email o accedere alla verifica.
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

### F3-37 — Un mittente sconosciuto senza soppressioni attive emette EmailQuarantined con auto-reply consentito

**Obiettivo**
Verificare che, quando un mittente sconosciuto (senza soppressioni anti-loop attive su quell'indirizzo) va in quarantena, l'evento interno `EmailQuarantined` venga emesso con il flag `autoReplyAllowed=true`, la condizione che un futuro listener dovrà rispettare per decidere se inviare o meno un auto-reply di cortesia al mittente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-308, AC3 (auto-reply al mittente solo se passa tutti i controlli anti-loop di US-304).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php` — `un mittente sconosciuto senza soppressioni attive emette EmailQuarantined con auto-reply consentito`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ApplyInboundEmail`, `App\Domain\Mail\Events\EmailQuarantined`.
- Test correlato: F3-38, F3-40.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un'email da un mittente sconosciuto (`mai-visto@example.test`) senza alcuna riga `email_suppressions` attiva per quell'indirizzo.

**Stato iniziale**
Non applicabile: scenario costruito interamente all'interno del test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "un mittente sconosciuto senza soppressioni attive emette EmailQuarantined con auto-reply consentito"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: l'evento `EmailQuarantined` viene emesso con `autoReplyAllowed=true` per quel messaggio.

**Nota sulla scelta della modalità**
Il flag `autoReplyAllowed` è uno stato interno dell'evento di dominio: nessun auto-reply reale è ancora inviato in questa fase in base a questo flag (l'eventuale invio dell'auto-reply al mittente resta un'estensione futura, per ora solo E9 verso lo staff è implementata — vedi F3-40), quindi non c'è alcun effetto osservabile via Mailpit/UI che distingua questo scenario da F3-38: verificarlo richiede l'ispezione dell'evento a livello di codice.

**Controlli negativi**
Nessuno applicabile: il caso "con soppressione attiva" è il controllo negativo, coperto da F3-38.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
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

### F3-38 — Un mittente già soppresso per rate limit (US-304) emette EmailQuarantined senza consentire l'auto-reply

**Obiettivo**
Verificare che, quando un mittente sconosciuto è GIÀ soppresso per rate limit anti-loop (`reason=loop_protection`, US-304) al momento in cui il suo messaggio va in quarantena, l'evento `EmailQuarantined` venga emesso con `autoReplyAllowed=false`, impedendo qualunque futuro invio di auto-reply verso quell'indirizzo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-308, AC3 (auto-reply solo se passa tutti i controlli anti-loop di US-304).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php` — `un mittente sconosciuto già soppresso per rate limit (US-304) emette EmailQuarantined senza consentire l'auto-reply`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ApplyInboundEmail`, `App\Domain\Mail\Events\EmailQuarantined`, `App\Domain\Mail\Models\EmailSuppression`.
- Test correlato: F3-20, F3-37.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un'email da un mittente (`mai-visto@example.test`) con una riga `email_suppressions` preesistente `reason=LoopProtection`, `expires_at` futuro.

**Stato iniziale**
Non applicabile: scenario costruito interamente all'interno del test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "un mittente sconosciuto già soppresso per rate limit \(US-304\) emette EmailQuarantined senza consentire"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: l'evento `EmailQuarantined` viene emesso con `autoReplyAllowed=false`.

**Nota sulla scelta della modalità**
Come per F3-37, il flag interno non ha ancora un effetto visibile da Mailpit/UI in questa fase; riprodurre dal vivo la condizione "già soppresso per rate limit al momento della quarantena" richiederebbe il conteggio esatto degli invii ravvicinati (F3-20), non affidabile in una sessione di collaudo dal vivo.

**Controlli negativi**
Il caso "senza soppressione" è il controllo negativo, coperto da F3-37.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
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

### F3-39 — Un messaggio già in quarantena viene riprocessato con successo una volta che il mittente diventa identificabile

**Obiettivo**
Verificare che, dopo aver associato manualmente in amministrazione un mittente prima sconosciuto a un utente esistente (o dopo aver creato per lui un nuovo utente), il messaggio precedentemente in quarantena venga riprocessato con successo dalla pipeline, generando il ticket atteso, senza dover reinviare l'email originale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-308, AC4 (le azioni "associa a utente esistente"/"crea nuovo utente e ticket" vivono nell'amministrazione, US-322: applicandole, il messaggio viene riprocessato dalla pipeline e genera il ticket).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php` — `un messaggio già in quarantena viene riprocessato con successo una volta che il mittente diventa identificabile (US-322)`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ApplyInboundEmail::runForResolvedSender()`.
- Test correlato: F3-36, F3-24.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Admin (per l'azione di amministrazione) — richiede che il messaggio quarantenato di F3-36 (o un nuovo messaggio analogo) sia ancora presente

**Prerequisiti**
- Un messaggio già in quarantena (es. il risultato di F3-36, o un nuovo messaggio inviato appositamente da un indirizzo mai registrato).
- Accesso alla pagina di amministrazione "Quarantena" con permesso `email.manage`.

**Dati di test**
- Oggetto email: `COLL-F3-39-20260824-01 richiesta da mittente da associare`.

**Stato iniziale**
Il messaggio con questo oggetto risulta in quarantena, nessun utente registrato con l'indirizzo mittente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da un indirizzo mai registrato, invia una email a `<indirizzo casella di supporto UAT, fornito dal committente>` | Oggetto `COLL-F3-39-20260824-01 richiesta da mittente da associare` | L'email risulta inviata |
| 2 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il messaggio risulta in quarantena |
| 3 | Come Admin, apri la pagina "Quarantena" e individua il messaggio | Ricerca per oggetto `COLL-F3-39-...` | Il messaggio è presente, con azione "Associa a utente esistente"/"Crea nuovo utente" disponibile |
| 4 | Esegui l'azione "Associa a utente esistente" (o "Crea nuovo utente e ticket") scegliendo/creando un utente con l'indirizzo mittente | — | L'azione viene eseguita senza errori |
| 5 | Verifica l'esito | Ricerca per titolo/oggetto `COLL-F3-39-...` | È stato creato un nuovo ticket con richiedente l'utente appena associato; il messaggio non risulta più in quarantena |

**Risultato finale atteso**
Il messaggio quarantenato viene riprocessato con successo dopo l'associazione manuale, generando il ticket atteso.

**Controlli negativi**
Nessuno applicabile in questa modalità.

**Evidenze da acquisire**
- Screenshot dell'azione di associazione in amministrazione.
- Screenshot del ticket generato dopo il riprocessamento.

**Criterio di superamento**

PASS: dopo l'associazione, il ticket viene creato e il messaggio non risulta più in quarantena.
FAIL: il messaggio resta in quarantena dopo l'associazione, o l'azione fallisce.
BLOCKED: la pagina di amministrazione "Quarantena" non è raggiungibile o l'azione non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: l'utente creato/associato e il ticket generato restano come dati reali del collaudo.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F3-40 — E9 e una notifica in-app arrivano a ogni destinatario staff risolto quando un messaggio va in quarantena

**Obiettivo**
Verificare che, quando un'email va in quarantena, ogni membro dello staff del gruppo di notifica configurato (`MAIL_STAFF_NOTIFICATION_GROUP`) riceva sia la notifica email E9 (mittente sconosciuto), visibile in Mailpit, sia una notifica in-app nel pannello (campanella Filament).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-308, AC2 (notifica E9 allo staff con mittente, subject, estratto del corpo, link diretto alla quarantena).
- Test automatico: `tests/Feature/Domain/Mail/Listeners/NotifyStaffOfUnknownSenderTest.php` — `sends E9 and an in-app notification to every resolved staff recipient` (con test correlato `does not notify anyone when the staff group is empty`).
- File/componente applicativo rilevante: `App\Domain\Mail\Listeners\NotifyStaffOfUnknownSender`, `App\Domain\Mail\Mailables\UnknownSenderStaffMail`, `App\Domain\Mail\Support\StaffNotificationGroup`, `App\Domain\Mail\Support\StaffDatabaseNotification`.
- Test correlato: F3-36, F3-37, F3-38.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Cliente esterno (mittente sconosciuto) + Admin/Developer (destinatario staff, verifica notifica in-app)

**Prerequisiti**
- L'indirizzo email dell'utente Admin/Developer di collaudo è incluso nel gruppo `MAIL_STAFF_NOTIFICATION_GROUP` configurato in UAT (verificare con il committente/configurazione se non certo).
- Accesso a Mailpit UAT.
- Accesso al pannello come quello stesso Admin/Developer, per controllare la campanella delle notifiche.

**Dati di test**
- Oggetto email: `COLL-F3-40-20260824-01 mittente sconosciuto per notifica staff`.

**Stato iniziale**
Nessun messaggio con questo oggetto già presente; nessuna notifica in-app residua da un test precedente identico.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da un indirizzo mai registrato, invia una email a `<indirizzo casella di supporto UAT, fornito dal committente>` | Oggetto `COLL-F3-40-20260824-01 mittente sconosciuto per notifica staff` | L'email risulta inviata |
| 2 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il messaggio risulta in quarantena |
| 3 | Apri Mailpit e individua la notifica E9 | — | È presente un'email diretta a ciascun indirizzo staff del gruppo configurato, con mittente/subject/estratto del corpo dell'email originale |
| 4 | Accedi al pannello come membro dello staff destinatario e apri la campanella delle notifiche | — | È presente una nuova notifica in-app relativa al messaggio in quarantena |

**Risultato finale atteso**
Ogni destinatario staff configurato riceve sia l'email E9 sia la notifica in-app.

**Controlli negativi**
Nessuno applicabile in questa modalità (il caso "gruppo staff vuoto" è coperto dal test automatico correlato, non riproducibile in UAT senza svuotare una configurazione condivisa).

**Evidenze da acquisire**
- Screenshot dell'email E9 in Mailpit.
- Screenshot della notifica in-app nel pannello.

**Criterio di superamento**

PASS: sia l'email E9 sia la notifica in-app arrivano correttamente.
FAIL: manca una delle due notifiche, o il contenuto non corrisponde al messaggio originale.
BLOCKED: Mailpit non raggiungibile o impossibile accedere al pannello come destinatario staff.
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
## Allegati inbound (US-309)

### F3-41 — Un allegato regolare viene importato nella collection attachments del ticket_message con record stored

**Obiettivo**
Verificare che un allegato regolare (es. un PDF) presente in un'email inbound venga importato correttamente nella collection medialibrary `attachments` del `ticket_message` creato, con un record `email_attachments` corrispondente in stato "stored" (nome sanitizzato, path basato su ULID, MIME reale rilevato).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-309, AC1/AC4/AC7 (nome file sanitizzato, path da ULID mai nome originale; validazione MIME reale; allegato collegato al `ticket_message`).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ImportInboundEmailAttachmentsTest.php` — `importa un allegato regolare nella collection attachments del ticket_message con record stored`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ImportInboundEmailAttachments`, `App\Domain\Mail\Support\EmailAttachmentTypes`, `App\Domain\Mail\Models\EmailAttachment`.
- Test correlato: F3-31, F3-42, F3-43, F3-44.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Customer + Admin/Developer

**Prerequisiti**
- Utente Customer registrato `infosentieroitalia@cai.it`.
- Un file PDF reale di piccole dimensioni da allegare.

**Dati di test**
- Oggetto email: `COLL-F3-41-20260824-01 richiesta con allegato regolare`.
- Allegato: un file PDF reale, es. `documento-collaudo.pdf`.

**Stato iniziale**
Nessun ticket con questo titolo già presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da `infosentieroitalia@cai.it`, invia una email con un PDF allegato a `<indirizzo casella di supporto UAT, fornito dal committente>` | Oggetto `COLL-F3-41-20260824-01 richiesta con allegato regolare`, allegato `documento-collaudo.pdf` | L'email risulta inviata |
| 2 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 3 | Accedi come Admin/Developer, apri il ticket creato e il suo primo messaggio | Ricerca per titolo `COLL-F3-41-...` | Il messaggio mostra l'allegato `documento-collaudo.pdf`, scaricabile |
| 4 | Scarica l'allegato e verifica che il contenuto corrisponda al file originale | — | Il file scaricato è identico a quello inviato |

**Risultato finale atteso**
L'allegato regolare risulta correttamente importato e associato al messaggio del ticket.

**Controlli negativi**
Nessuno applicabile in questa modalità.

**Evidenze da acquisire**
- Screenshot del messaggio con l'allegato visibile.

**Criterio di superamento**

PASS: l'allegato è presente e scaricabile, con contenuto integro.
FAIL: l'allegato manca, o il contenuto è corrotto/diverso dall'originale.
BLOCKED: impossibile inviare l'email con allegato o accedere al pannello.
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

### F3-42 — Gli allegati inline sono esclusi per default, nessun record creato

**Obiettivo**
Verificare che un allegato marcato `Content-Disposition: inline` (tipicamente un'immagine di firma/logo incorporata dal client di posta) venga escluso per default dall'importazione, senza generare alcun record `email_attachments` né comparire tra gli allegati del ticket.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-309, AC3 (allegati `inline` esclusi per default, con flag esplicito per includerli).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ImportInboundEmailAttachmentsTest.php` — `gli allegati inline sono esclusi per default, nessun record creato` (con test correlato `il flag include_inline forza l'importazione degli allegati inline`).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ImportInboundEmailAttachments`.
- Test correlato: F3-41.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Customer + Admin/Developer

**Prerequisiti**
- Utente Customer registrato `infosentieroitalia@cai.it`.
- Un client di posta con una firma email che includa un'immagine/logo incorporato (tipicamente inviato come allegato `inline`), oppure un file allegato "in linea" nel corpo del messaggio (non come allegato tradizionale).

**Dati di test**
- Oggetto email: `COLL-F3-42-20260824-01 richiesta con firma con logo incorporato`.

**Stato iniziale**
Nessun ticket con questo titolo già presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da `infosentieroitalia@cai.it`, invia una email (con la propria firma contenente un logo incorporato) a `<indirizzo casella di supporto UAT, fornito dal committente>` | Oggetto `COLL-F3-42-20260824-01 richiesta con firma con logo incorporato` | L'email risulta inviata |
| 2 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 3 | Accedi come Admin/Developer e apri il messaggio creato | Ricerca per titolo `COLL-F3-42-...` | Il messaggio NON mostra alcun allegato relativo all'immagine di firma/logo incorporata |

**Risultato finale atteso**
L'immagine incorporata nella firma (inline) non compare come allegato del ticket.

**Controlli negativi**
Nessuno applicabile in questa modalità: se il proprio client di posta non produce un allegato inline verificabile, il test resta BLOCKED e va rieseguito con un client/firma che lo generi (verificabile guardando i sorgenti dell'email in Mailpit per un invio di prova preliminare).

**Evidenze da acquisire**
- Screenshot del messaggio senza l'allegato inline.

**Criterio di superamento**

PASS: l'immagine inline non compare tra gli allegati del ticket.
FAIL: l'immagine inline compare erroneamente come allegato.
BLOCKED: impossibile produrre in modo affidabile un allegato inline con gli strumenti disponibili.
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

### F3-43 — Un allegato di tipo non consentito produce un record rejected_mime, senza fallire gli altri

**Obiettivo**
Verificare che, quando un'email contiene un allegato di tipo non ammesso dalla configurazione (`mail_pipeline.attachments.allowed_mimes`/`allowed_extensions`), quell'allegato venga rifiutato con un record `email_attachments` in stato "rejected_mime" e un motivo esplicito, senza impedire l'importazione degli altri allegati regolari presenti nella stessa email.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-309, AC6 (un allegato scartato produce comunque un record con `status` che inizia con `rejected_`, mai uno scarto silenzioso; un errore su un singolo allegato non fa fallire l'elaborazione dell'intero messaggio).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ImportInboundEmailAttachmentsTest.php` — `un allegato di tipo non consentito produce un record rejected_mime, senza fallire gli altri`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ImportInboundEmailAttachments`, `App\Domain\Mail\Support\EmailAttachmentTypes`.
- Test correlato: F3-41, F3-44, F3-45.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Customer + Admin/Developer

**Prerequisiti**
- Utente Customer registrato `infosentieroitalia@cai.it`.
- Un file di un tipo palesemente non ammesso dalla configurazione di default (es. un file `.exe`), più un file regolare ammesso (es. un `.pdf`) da allegare nella stessa email.

**Dati di test**
- Oggetto email: `COLL-F3-43-20260824-01 richiesta con allegato di tipo non ammesso`.
- Allegati: un file `.exe` (non ammesso) e un file `.pdf` (ammesso), nella stessa email.

**Stato iniziale**
Nessun ticket con questo titolo già presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da `infosentieroitalia@cai.it`, invia una email con entrambi gli allegati a `<indirizzo casella di supporto UAT, fornito dal committente>` | Oggetto `COLL-F3-43-20260824-01 richiesta con allegato di tipo non ammesso` | L'email risulta inviata |
| 2 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 3 | Accedi come Admin/Developer e apri il messaggio creato | Ricerca per titolo `COLL-F3-43-...` | Il file `.pdf` compare come allegato regolare; il file `.exe` NON compare tra gli allegati |
| 4 | (Verifica tecnica facoltativa) Nel Registro email/amministrazione, individua il record dell'allegato rifiutato | — | Il record risulta in stato "rejected_mime" con un motivo esplicito |

**Risultato finale atteso**
L'allegato non ammesso viene rifiutato con traccia esplicita del motivo; l'allegato regolare viene comunque importato correttamente.

**Controlli negativi**
Nessuno applicabile: il confronto stesso (uno accettato, uno rifiutato nella stessa email) è già il controllo negativo richiesto.

**Evidenze da acquisire**
- Screenshot del messaggio con il solo allegato PDF visibile.
- Screenshot/estratto del record "rejected_mime" (se il registro email è consultabile).

**Criterio di superamento**

PASS: l'allegato non ammesso è rifiutato con motivo tracciato, l'allegato regolare è comunque importato.
FAIL: entrambi gli allegati vengono rifiutati/importati indistintamente, o l'elaborazione dell'intero messaggio fallisce.
BLOCKED: impossibile inviare l'email con entrambi gli allegati o accedere al pannello.
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

### F3-44 — Un allegato più grande del limite per singolo file produce un record rejected_size, senza fallire gli altri

**Obiettivo**
Verificare che un allegato più grande del limite configurato per singolo file (`mail_pipeline.attachments.max_file_size`, default 25 MB) venga rifiutato con un record `email_attachments` in stato "rejected_size", senza impedire l'importazione degli altri allegati regolari presenti nella stessa email.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-309, AC2/AC6 (limiti letti da `config/mail_pipeline.php`; un allegato scartato produce comunque un record con motivo, senza fermare gli altri).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ImportInboundEmailAttachmentsTest.php` — `un allegato più grande del limite per singolo file produce un record rejected_size, senza fallire gli altri` (con test correlati per il limite di dimensione totale per messaggio e per il numero massimo di allegati).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ImportInboundEmailAttachments`, `App\Domain\Mail\Support\EmailAttachmentTypes`.
- Test correlato: F3-41, F3-43, F3-45.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Customer + Admin/Developer

**Prerequisiti**
- Utente Customer registrato `infosentieroitalia@cai.it`.
- Un file reale più grande di 25 MB (limite di default), più un file regolare piccolo da allegare nella stessa email.
- Verificare che il proprio provider/client di posta e il server di invio non impongano un limite inferiore che impedirebbe l'invio stesso (in tal caso, concordare un limite di test più basso con il committente e usarlo, documentandolo nelle Note).

**Dati di test**
- Oggetto email: `COLL-F3-44-20260824-01 richiesta con allegato oltre il limite di dimensione`.
- Allegati: un file oltre 25 MB e un file piccolo regolare (es. un PDF).

**Stato iniziale**
Nessun ticket con questo titolo già presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da `infosentieroitalia@cai.it`, invia una email con entrambi gli allegati a `<indirizzo casella di supporto UAT, fornito dal committente>` | Oggetto `COLL-F3-44-20260824-01 richiesta con allegato oltre il limite di dimensione` | L'email risulta inviata |
| 2 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il comando termina senza errori |
| 3 | Accedi come Admin/Developer e apri il messaggio creato | Ricerca per titolo `COLL-F3-44-...` | Il file piccolo compare come allegato regolare; il file oltre soglia NON compare tra gli allegati |
| 4 | (Verifica tecnica facoltativa) Nel Registro email/amministrazione, individua il record dell'allegato rifiutato | — | Il record risulta in stato "rejected_size" |

**Risultato finale atteso**
L'allegato troppo grande viene rifiutato con traccia esplicita; l'allegato regolare viene comunque importato.

**Controlli negativi**
Nessuno applicabile: il confronto stesso è già il controllo negativo richiesto.

**Evidenze da acquisire**
- Screenshot del messaggio con il solo allegato regolare visibile.

**Criterio di superamento**

PASS: l'allegato oltre soglia è rifiutato con motivo tracciato, l'allegato regolare è comunque importato.
FAIL: l'allegato oltre soglia viene comunque importato, o l'elaborazione dell'intero messaggio fallisce.
BLOCKED: impossibile inviare un allegato di dimensione sufficiente (limiti del provider di posta) o accedere al pannello.
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

### F3-45 — Un errore nel salvataggio di un singolo allegato produce un record failed, senza fermare gli altri

**Obiettivo**
Verificare che, se il salvataggio fisico di un singolo allegato fallisce per un problema tecnico (es. disco di destinazione non configurato/non raggiungibile), venga prodotto un record `email_attachments` in stato "failed" con un motivo esplicito, senza fermare l'elaborazione degli altri allegati né dell'intero messaggio.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-309, AC6 (un errore su un singolo allegato non fa fallire l'elaborazione dell'intero messaggio).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ImportInboundEmailAttachmentsTest.php` — `un errore nel salvataggio di un singolo allegato produce un record failed, senza fermare gli altri né l'elaborazione` (simula un disco di destinazione inesistente via configurazione).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ImportInboundEmailAttachments`.
- Test correlato: F3-43, F3-44.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Configurazione di test con `ticketing.attachments.disk` puntato a un disco inesistente, applicata alla fixture `.eml` `richiesta-con-allegati.eml` (2 allegati non-inline: `documento.pdf`, `nota.txt`).

**Stato iniziale**
Non applicabile: scenario costruito interamente all'interno del test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "un errore nel salvataggio di un singolo allegato produce un record failed, senza fermare gli altri"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: entrambi gli allegati risultano in stato "Failed" con `rejection_reason` valorizzato, e l'elaborazione del messaggio prosegue senza eccezioni non gestite.

**Nota sulla scelta della modalità**
Rompere deliberatamente la configurazione del disco di storage degli allegati in un ambiente UAT condiviso comprometterebbe altri test in corso nella stessa sessione di collaudo: non è praticabile riprodurlo dal vivo, rientra nell'eccezione per i comportamenti tecnici di resilienza.

**Controlli negativi**
Nessuno applicabile: questo test è già la verifica del caso limite.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
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
## Layout email unico e componenti riusabili (US-310)

### F3-46 — Il layout condiviso produce HTML valido, senza errori di parsing, per un Mailable reale

**Obiettivo**
Verificare che il layout email condiviso (`resources/views/emails/layouts/base.blade.php`), usato da ogni Mailable del catalogo E1-E9, produca sempre HTML ben formato, senza errori di parsing (tag non chiusi, markup malformato), quando renderizzato per un Mailable di esempio completo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-310, AC5 (un test verifica che nessun HTML malformato venga prodotto per almeno un Mailable reale per tipo di comunicazione).
- Test automatico: `tests/Feature/Domain/Mail/EmailLayoutTest.php` — `the shared layout renders well-formed HTML with no parse errors for a real Mailable`.
- File/componente applicativo rilevante: `resources/views/emails/layouts/base.blade.php`, `Tests\Support\Mail\ExampleTicketNotificationMail`.
- Test correlato: F3-47, F3-48, F3-49.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un `ExampleTicketNotificationMail` di dimostrazione (ticket, autore, corpo sanitizzato, CTA), usato SOLO da questa suite di test — non fa parte del catalogo E1-E9 realmente inviato in produzione/UAT, quindi non è raggiungibile tramite alcuna azione utente reale nel pannello o via email.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the shared layout renders well-formed HTML with no parse errors for a real Mailable"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: l'HTML renderizzato non produce alcun errore di parsing DOM e contiene il doctype `<!doctype html>`.

**Nota sulla scelta della modalità**
Il Mailable usato da questo test è dichiaratamente solo dimostrativo/di test (non parte del catalogo E1-E9 realmente triggerato da un'azione utente): non esiste un percorso UAT realistico per "far apparire" proprio questo Mailable in Mailpit. La verifica visiva/HTML dei Mailable realmente in produzione (E1/E2) è coperta dai test del topic "Mailable E1/E2" più avanti in questo manuale.

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

### F3-47 — L'email renderizzata contiene tutti i componenti riusabili: header, badge, blocco messaggio, CTA, footer

**Obiettivo**
Verificare che l'email renderizzata dal layout condiviso contenga effettivamente tutti i componenti Blade riusabili previsti (intestazione ticket, badge di stato, blocco messaggio, bottone call-to-action, footer con dati societari), senza contenuti pericolosi (`<script>`) residui.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-310, AC3 (componenti Blade riusabili: intestazione ticket, badge di stato, blocco messaggio, CTA, footer con dati societari).
- Test automatico: `tests/Feature/Domain/Mail/EmailLayoutTest.php` — `the rendered email contains every reusable component: header, badge, message block, CTA, footer`.
- File/componente applicativo rilevante: `resources/views/components/emails/*` (ticket-header, status-badge, message-block, cta-button), layout base con footer.
- Test correlato: F3-46, F3-48, F3-49.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Stesso `ExampleTicketNotificationMail` di F3-46, con un ticket in stato "In lavorazione" (Progress) e un corpo contenente un tag `<script>` di prova (per verificarne l'assenza in output).

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the rendered email contains every reusable component"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: l'HTML contiene il numero ticket, il titolo, il badge di stato (etichetta maiuscola), il nome dell'autore, il corpo sanitizzato, il testo/URL della CTA, la ragione sociale e la P.IVA della società nel footer; nessuna traccia di `<script>`.

**Nota sulla scelta della modalità**
Stesso motivo di F3-46: il Mailable è dimostrativo/di test, non raggiungibile da un'azione utente reale in UAT. La presenza concreta di questi stessi componenti (header, badge, CTA, footer) in un'email realmente inviata è comunque osservabile visivamente in Mailpit nei test del topic "Mailable E1/E2".

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

### F3-48 — La versione plain-text è generata insieme all'HTML con lo stesso contenuto

**Obiettivo**
Verificare che ogni email generata dal layout condiviso produca sempre, insieme alla versione HTML, una versione plain-text equivalente (mai solo HTML), con lo stesso contenuto informativo e senza markup HTML residuo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-310, AC2 (ogni email ha una versione plain-text generata insieme all'HTML, mai solo HTML).
- Test automatico: `tests/Feature/Domain/Mail/EmailLayoutTest.php` — `the plain-text version is generated alongside the HTML and carries the same content`.
- File/componente applicativo rilevante: `resources/views/emails/layouts/base-text.blade.php`, `resources/views/emails/examples/ticket-notification-text.blade.php`.
- Test correlato: F3-46, F3-47.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Stessi dati di F3-46/F3-47, renderizzati tramite la vista `-text` dedicata.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the plain-text version is generated alongside the HTML and carries the same content"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: la versione testuale contiene titolo ticket, autore, corpo, testo e URL della CTA, ragione sociale, senza alcun tag `<p>`/`<strong>` residuo.

**Nota sulla scelta della modalità**
Stesso motivo di F3-46/F3-47: Mailable dimostrativo, non raggiungibile in UAT tramite un'azione utente reale. La presenza della versione plain-text accanto all'HTML per un'email realmente inviata è comunque verificabile in Mailpit (tab "Plain text") nei test del topic "Mailable E1/E2".

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

### F3-49 — Il footer mostra il link alle preferenze di notifica quando un URL è configurato

**Obiettivo**
Verificare che il footer condiviso mostri il link "Gestisci le preferenze di notifica" solo quando `config('mail_pipeline.notification_preferences_url')` è valorizzata, e lo nasconda quando è vuota (mai un link rotto verso una pagina non ancora esistente, la cui UI è fuori scope fino alla Fase 6).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-310, AC4 (footer con link alle preferenze di notifica condizionale alla configurazione).
- Test automatico: `tests/Feature/Domain/Mail/EmailLayoutTest.php` — `the footer shows the notification preferences link when a URL is configured` (con test correlato `the footer hides the notification preferences link when no URL is configured`).
- File/componente applicativo rilevante: `resources/views/emails/layouts/base.blade.php`, `config('mail_pipeline.notification_preferences_url')`.
- Test correlato: F3-46, F3-47.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
`config(['mail_pipeline.notification_preferences_url' => 'https://tickets.montagnaservizi.com/preferenze'])`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the footer shows the notification preferences link when a URL is configured"` | Il comando termina con exit code 0, test passed |
| 3 | Eseguire il test di controllo negativo | `vendor/bin/pest --filter "the footer hides the notification preferences link when no URL is configured"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Con l'URL configurato, il footer mostra il link "Gestisci le preferenze di notifica" con l'URL corretto; senza URL configurato (come attualmente in UAT, `MAIL_NOTIFICATION_PREFERENCES_URL` vuota di default), il link non compare.

**Nota sulla scelta della modalità**
In UAT questa variabile è vuota di default (la UI di gestione preferenze è fuori scope fino alla Fase 6): verificare dal vivo il comportamento "con URL configurato" richiederebbe modificare la configurazione dell'ambiente condiviso, con impatto su altre email inviate durante lo stesso collaudo — non praticabile in una sessione di collaudo funzionale.

**Controlli negativi**
Il passo 3 è già il controllo negativo.

**Evidenze da acquisire**
- Output completo di entrambi i comandi Pest eseguiti.

**Criterio di superamento**

PASS: entrambi i comandi terminano con exit code 0 e i test indicati risultano passed.
FAIL: uno dei test fallisce o uno dei comandi termina con errore.
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

## Mailable E1/E2 — conferme di ricezione/apertura ticket (US-311)

### F3-50 — E1 viene inviata quando un'email inbound applica un nuovo ticket

**Obiettivo**
Verificare che, quando un'email inbound genera un nuovo ticket, il mittente riceva la conferma di ricezione E1 (`TicketReceivedByEmailMail`), visibile in Mailpit, con numero ticket e link al portale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-311, AC1 (E1 inviata al mittente quando un ticket è creato via email, con numero ticket e link al portale).
- Test automatico: `tests/Feature/Domain/Mail/Listeners/SendTicketReceivedByEmailNotificationTest.php` — `sends E1 when the inbound email applied a new ticket` (con test correlati: nessun invio se il ticket si aggancia a uno esistente, nessun invio se il ticket non ha richiedente).
- File/componente applicativo rilevante: `App\Domain\Mail\Listeners\SendTicketReceivedByEmailNotification`, `App\Domain\Mail\Mailables\TicketReceivedByEmailMail`, `App\Domain\Mail\Events\InboundEmailApplied`.
- Test correlato: F3-31, F3-51, F3-53.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Customer + Admin/Developer

**Prerequisiti**
- Utente Customer registrato `infosentieroitalia@cai.it`.
- Accesso a Mailpit UAT: `https://mailpit-ticket-uat.montagnaservizi.com`.

**Dati di test**
- Oggetto email: `COLL-F3-50-20260824-01 verifica E1 nuovo ticket via email`.

**Stato iniziale**
Nessun ticket con questo titolo già presente; casella Mailpit consultabile per individuare il nuovo messaggio per data/ora.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da `infosentieroitalia@cai.it`, invia una email a `<indirizzo casella di supporto UAT, fornito dal committente>` | Oggetto `COLL-F3-50-20260824-01 verifica E1 nuovo ticket via email` | L'email risulta inviata |
| 2 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Un nuovo ticket viene creato |
| 3 | Apri Mailpit e individua la nuova email indirizzata a `infosentieroitalia@cai.it` | — | È presente un'email di conferma ricezione, con numero ticket e link al portale |

**Risultato finale atteso**
La conferma E1 arriva su Mailpit con contenuto corretto (numero ticket, link al portale).

**Controlli negativi**
Nessuno applicabile in questa modalità (il caso "nessun invio se il ticket è esistente" è coperto da F3-51).

**Evidenze da acquisire**
- Screenshot dell'email E1 in Mailpit.

**Criterio di superamento**

PASS: l'email E1 arriva su Mailpit con contenuto corretto.
FAIL: nessuna email ricevuta o contenuto errato.
BLOCKED: Mailpit non raggiungibile o impossibile inviare l'email.
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

### F3-51 — E1 non viene inviata quando l'email inbound si aggancia a un ticket esistente

**Obiettivo**
Verificare che, quando un'email inbound si aggancia a un ticket ESISTENTE (non ne crea uno nuovo), la conferma di ricezione E1 non venga inviata: E1 è riservata esclusivamente all'apertura di un nuovo ticket via email.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-311, AC1 (E1 inviata solo quando un ticket è creato via email).
- Test automatico: `tests/Feature/Domain/Mail/Listeners/SendTicketReceivedByEmailNotificationTest.php` — `does not send E1 when the inbound email applied an existing ticket`.
- File/componente applicativo rilevante: `App\Domain\Mail\Listeners\SendTicketReceivedByEmailNotification`.
- Test correlato: F3-32, F3-50.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Customer + Admin/Developer

**Prerequisiti**
- Un ticket esistente con richiedente = utente Customer registrato `infosentieroitalia@cai.it`, di cui si conosce l'id.
- Accesso a Mailpit UAT.

**Dati di test**
- Ticket esistente `COLL-F3-51-20260824-01`.

**Stato iniziale**
Il ticket `COLL-F3-51-20260824-01` esiste già; casella Mailpit consultabile per data/ora.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Come Admin/Developer, crea il ticket di prova e annotane l'id | Titolo `COLL-F3-51-20260824-01` | Ticket creato, es. id `2100` |
| 2 | Da `infosentieroitalia@cai.it`, invia una risposta con subject contenente il token | Oggetto `Re: [#2100] COLL-F3-51-20260824-01` | L'email risulta inviata |
| 3 | (Opzionale) Via SSH, lancia manualmente `mail:fetch-inbound` | `php artisan mail:fetch-inbound` | Il messaggio si aggancia al ticket esistente |
| 4 | Apri Mailpit e verifica le email inviate a `infosentieroitalia@cai.it` intorno a questo orario | — | NON compare alcuna nuova email di conferma "nuovo ticket ricevuto" (E1) per questo evento |

**Risultato finale atteso**
Nessuna E1 viene inviata per una risposta che si aggancia a un ticket esistente.

**Controlli negativi**
Il presente test È il controllo negativo di F3-50.

**Evidenze da acquisire**
- Screenshot di Mailpit che mostri l'assenza di una nuova E1 per questo evento.

**Criterio di superamento**

PASS: nessuna nuova E1 viene inviata.
FAIL: viene inviata una E1 anche per una risposta su ticket esistente.
BLOCKED: Mailpit non raggiungibile o impossibile inviare l'email.
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

### F3-52 — E2 viene inviata quando il ticket è creato dal canale web

**Obiettivo**
Verificare che, quando un richiedente apre un nuovo ticket dal pannello web (non via email), riceva la conferma E2 (`TicketOpenedFromWebMail`), diversa da E1 e specifica per l'apertura da portale (comunicazione nuova rispetto al v1).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-311, AC2 (E2, nuova: inviata al richiedente quando crea un ticket dal pannello web).
- Test automatico: `tests/Feature/Domain/Mail/Listeners/SendTicketOpenedFromWebMailNotificationTest.php` — `sends E2 when the ticket was created from the web channel` (con test correlati: nessun invio se il ticket è creato via email, nessun invio se il ticket non ha richiedente).
- File/componente applicativo rilevante: `App\Domain\Mail\Listeners\SendTicketOpenedFromWebMailNotification`, `App\Domain\Mail\Mailables\TicketOpenedFromWebMail`, `App\Domain\Ticketing\Events\TicketCreated`.
- Test correlato: F3-50, F3-53.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Accesso al pannello UAT come Customer (`infosentieroitalia@cai.it`).
- Accesso a Mailpit UAT.

**Dati di test**
- Titolo ticket: `COLL-F3-52-20260824-01 verifica E2 ticket dal portale`.

**Stato iniziale**
Nessun ticket con questo titolo già presente; casella Mailpit consultabile per data/ora.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi al pannello come Customer e crea un nuovo ticket | Titolo `COLL-F3-52-20260824-01 verifica E2 ticket dal portale` | Il ticket viene creato |
| 2 | Apri Mailpit e individua la nuova email indirizzata a `infosentieroitalia@cai.it` | — | È presente un'email di conferma "ticket aperto dal portale" (E2), diversa da E1 |

**Risultato finale atteso**
La conferma E2 arriva su Mailpit con contenuto coerente con l'apertura da canale web.

**Controlli negativi**
Nessuno applicabile in questa modalità.

**Evidenze da acquisire**
- Screenshot dell'email E2 in Mailpit.

**Criterio di superamento**

PASS: l'email E2 arriva su Mailpit con contenuto corretto.
FAIL: nessuna email ricevuta, o viene inviata E1 invece di E2.
BLOCKED: Mailpit non raggiungibile o impossibile creare il ticket dal pannello.
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

### F3-53 — Ogni Mailable outbound imposta Message-Id e Reply-To VERP dalla riga email_messages

**Obiettivo**
Verificare che ogni email outbound generata dalla pipeline (qui: E1/E2) imposti l'header `Message-Id` e un `Reply-To` in forma VERP (`ticket+<ulid>@dominio`) derivati dalla riga `email_messages` outbound corrispondente, così da abilitare correttamente il threading di una eventuale risposta (US-306, livello 1).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-311, AC3 (producono un record `email_messages` `direction=outbound` con `message_id` generato e `Reply-To`=indirizzo VERP del ticket).
- Test automatico: `tests/Feature/Domain/Mail/Mailables/TicketOutboundMailablesTest.php` — `sets the Message-Id header and the VERP Reply-To from the outbound email_messages row` (dataset su E1/E2/E3/E7).
- File/componente applicativo rilevante: `App\Domain\Mail\Mailables\TicketOutboundMailable`, `App\Domain\Mail\Actions\SendOutboundTicketMail`.
- Test correlato: F3-25, F3-50, F3-52.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Accesso al pannello UAT come Customer.
- Accesso a Mailpit UAT.

**Dati di test**
- Titolo ticket: `COLL-F3-53-20260824-01 verifica header Message-Id e Reply-To VERP`.

**Stato iniziale**
Nessun ticket con questo titolo già presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi al pannello come Customer e crea un nuovo ticket | Titolo `COLL-F3-53-20260824-01 verifica header Message-Id e Reply-To VERP` | Il ticket viene creato, una notifica E2 viene accodata |
| 2 | Apri Mailpit, individua l'email e visualizzane il sorgente/gli header completi | — | È presente un header `Message-Id` valorizzato e un header `Reply-To` nella forma `ticket+<ulid>@<dominio>` |

**Risultato finale atteso**
Ogni email outbound porta header `Message-Id`/`Reply-To` coerenti con la riga `email_messages` che l'ha generata.

**Controlli negativi**
Nessuno applicabile in questa modalità.

**Evidenze da acquisire**
- Screenshot del sorgente dell'email in Mailpit con gli header evidenziati.

**Criterio di superamento**

PASS: entrambi gli header sono presenti e nella forma attesa.
FAIL: uno dei due header manca o non è nella forma VERP attesa.
BLOCKED: Mailpit non raggiungibile o impossibile creare il ticket.
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

### F3-54 — Nessun invio se il destinatario è in email_suppressions: la riga outbound resta comunque tracciata come suppressed

**Obiettivo**
Verificare che, quando il destinatario di una notifica outbound (es. E1) è presente in `email_suppressions` (es. per un hard bounce precedente), il Mailable NON venga accodato/inviato, ma la riga `email_messages` outbound venga comunque creata e tracciata con `status=suppressed` e un motivo esplicito — mai un salto silenzioso.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-311, AC4 (nessun invio se il destinatario è in `email_suppressions` o ha disattivato questo tipo di notifica).
- Test automatico: `tests/Feature/Domain/Mail/Actions/SendOutboundTicketMailTest.php` — `does not queue the mailable and marks the row suppressed when the recipient email is in email_suppressions`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\SendOutboundTicketMail`, `App\Domain\Mail\Models\EmailSuppression`.
- Test correlato: F3-50, F3-53, F3-98 (fuori ambito di questa porzione, US-323).

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Developer (predisposizione tecnica della soppressione) + Customer/Manager (per triggerare l'invio)

**Prerequisiti**
- Accesso Admin/Developer con permesso `email.manage` (o accesso DB/tinker) per inserire manualmente una riga `email_suppressions` di prova.
- Un utente di prova la cui email possa essere temporaneamente soppressa senza interferire con altri test del collaudo: si raccomanda l'account Manager `manager@oc.test` (non usato come mittente/destinatario in altri test di questa porzione).

**Dati di test**
- Riga `email_suppressions` di prova per `manager@oc.test`, `reason=hard_bounce` (o inserita da amministrazione "Soppressioni" se già disponibile in questo ambiente).
- Titolo ticket: `COLL-F3-54-20260824-01 verifica nessun invio a destinatario soppresso`.

**Stato iniziale**
`manager@oc.test` non è in `email_suppressions` prima del passo 1.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Inserisci una riga di soppressione per `manager@oc.test` (via amministrazione "Soppressioni" se disponibile, altrimenti via tinker/DB con supporto di un Developer) | `email=manager@oc.test`, `reason=hard_bounce` | La riga di soppressione risulta creata |
| 2 | Accedi al pannello come `manager@oc.test` e crea un nuovo ticket (canale web, per generare E2) | Titolo `COLL-F3-54-20260824-01 verifica nessun invio a destinatario soppresso` | Il ticket viene creato |
| 3 | Apri Mailpit e verifica le email indirizzate a `manager@oc.test` intorno a questo orario | — | NESSUNA nuova email risulta inviata a `manager@oc.test` per questo ticket |
| 4 | (Verifica tecnica facoltativa) Nel Registro email/amministrazione, individua la riga outbound corrispondente | — | La riga risulta comunque tracciata, in stato "Soppressa", con motivo esplicito — non un salto silenzioso |

**Risultato finale atteso**
Nessuna email viene realmente inviata al destinatario soppresso, ma l'evento resta tracciato come "soppresso", mai perso silenziosamente.

**Controlli negativi**
Nessuno applicabile in questa modalità.

**Evidenze da acquisire**
- Screenshot dell'assenza dell'email in Mailpit.
- Screenshot della riga "Soppressa" nel registro email, se disponibile.

**Criterio di superamento**

PASS: nessuna email viene inviata al destinatario soppresso, la riga outbound resta tracciata come soppressa.
FAIL: l'email viene comunque inviata, oppure nessuna traccia della soppressione risulta nel sistema.
BLOCKED: impossibile inserire la riga di soppressione di prova o accedere a Mailpit/pannello.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovi la riga di soppressione di prova per `manager@oc.test` (da amministrazione "Soppressioni" o via tinker/DB), per non lasciare quell'account permanentemente escluso dalle notifiche future.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

## Mailable E3/E9 — notifica staff (US-312)

### F3-55 — E3 viene inviata quando un'email inbound applica un nuovo ticket per un cliente

**Obiettivo**
Verificare che, quando un cliente apre un nuovo ticket scrivendo un'email alla casella di supporto, lo staff configurato nel gruppo di notifica riceva davvero l'email E3 ("nuovo ticket cliente"), corregendo il problema 10 del v1 (nessun `foreach` sincrono su "tutti i developer").

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-312, §7.5.2 E3. `App\Domain\Mail\Listeners\NotifyStaffOfNewCustomerTicketFromEmail`, evento `InboundEmailApplied($ticket, $emailMessage, isNewTicket: true)`.
- Test automatico: `tests/Feature/Domain/Mail/Listeners/NotifyStaffOfNewCustomerTicketFromEmailTest.php` — `sends E3 when the inbound email applied a new ticket for a customer` (verifica anche, nel test correlato, che nessuna E3 parta quando l'email si aggancia a un ticket già esistente).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\SendNewCustomerTicketStaffMail`, `App\Domain\Mail\Mailables\NewCustomerTicketStaffMail`.
- Test correlato: F3-56, F3-58.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Critica

**Ruolo del tester**
Customer (mittente esterno) per il trigger, Admin per la verifica del gruppo staff

**Prerequisiti**
- Accesso alla casella di supporto UAT (indirizzo fornito dal committente) da cui inviare l'email di prova.
- Accesso a Mailpit UAT: `https://mailpit-ticket-uat.montagnaservizi.com`.
- Il gruppo `MAIL_STAFF_NOTIFICATION_GROUP` in UAT è già configurato dal committente con almeno un indirizzo reale dello staff (verificarlo con l'Admin prima del test, senza modificarlo).

**Dati di test**
- Oggetto email: `COLL-F3-55 — Non riesco ad accedere al portale`.
- Mittente: un cliente reale già noto al sistema (es. `infosentieroitalia@cai.it`).

**Stato iniziale**
Nessun ticket con oggetto `COLL-F3-55...` presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Dalla propria casella cliente, invia una nuova email alla casella di supporto UAT | Oggetto `COLL-F3-55 — Non riesco ad accedere al portale` | L'email risulta inviata |
| 2 | Attendi il prossimo fetch schedulato (o chiedi a un developer di lanciare manualmente `mail:fetch-inbound` nel container) | — | Un nuovo ticket compare in `TicketResource` con quell'oggetto |
| 3 | Apri Mailpit UAT e cerca le email più recenti | — | È presente un'email E3 ("Nuovo ticket cliente") indirizzata a ciascun indirizzo del gruppo staff configurato |
| 4 | Verifica il contenuto dell'email E3 | — | Il corpo cita il numero/oggetto del ticket appena creato e un link al dettaglio |

**Risultato finale atteso**
Ogni indirizzo del gruppo staff riceve l'email E3 su Mailpit quando un cliente apre un nuovo ticket via email.

**Controlli negativi**
Rispondere di nuovo sullo stesso ticket (stessa conversazione) non deve generare una seconda E3: verificabile ripetendo l'invio con lo stesso `In-Reply-To`/thread e controllando che in Mailpit non compaia una seconda E3 per quel ticket.

**Evidenze da acquisire**
- Screenshot del ticket appena creato in `TicketResource`.
- Screenshot dell'email E3 in Mailpit con destinatari visibili.

**Criterio di superamento**

PASS: l'email E3 arriva su Mailpit a ogni indirizzo del gruppo staff quando un cliente apre un ticket via email.
FAIL: nessuna E3 arriva, oppure arriva a indirizzi non appartenenti al gruppo staff.
BLOCKED: `mail:fetch-inbound` non è raggiungibile/schedulato in UAT, oppure il bug noto `->databaseNotifications()` (colonna `notifications.data` `text` invece di `json` su Postgres) impedisce l'apertura di una pagina autenticata necessaria alla verifica.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il ticket di prova resta nel dataset UAT, coerente con gli altri ticket di collaudo.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F3-56 — E3 viene inviata quando un cliente apre un ticket dal web

**Obiettivo**
Verificare che l'email E3 parta anche quando un cliente crea un ticket dal pannello (canale web), non solo via email — stesso Mailable, trigger diverso (`TicketCreated` invece di `InboundEmailApplied`).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-312, §7.5.2 E3.
- Test automatico: `tests/Feature/Domain/Mail/Listeners/NotifyStaffOfNewCustomerTicketFromWebTest.php` — `sends E3 when a customer opens a ticket from the web` (il test correlato nello stesso file verifica che un ticket creato via canale email non triggeri questo secondo listener, evitando una doppia E3).
- File/componente applicativo rilevante: `App\Domain\Mail\Listeners\NotifyStaffOfNewCustomerTicketFromWeb`, `App\Domain\Mail\Actions\SendNewCustomerTicketStaffMail`.
- Test correlato: F3-55.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Utente Customer `infosentieroitalia@cai.it` con accesso al pannello UAT.
- Accesso a Mailpit UAT.

**Dati di test**
- Titolo ticket: `COLL-F3-56 — Richiesta assistenza da portale`.

**Stato iniziale**
Nessun ticket `COLL-F3-56...` presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer su `https://ticket-uat.montagnaservizi.com/admin` | `infosentieroitalia@cai.it` | Login riuscito |
| 2 | Crea un nuovo ticket dal pannello | Titolo `COLL-F3-56 — Richiesta assistenza da portale` | Il ticket viene creato in stato "Nuovo" |
| 3 | Apri Mailpit UAT | — | È presente un'email E3 per ciascun indirizzo del gruppo staff, con oggetto/riferimento al nuovo ticket |

**Risultato finale atteso**
Lo staff riceve E3 anche per un ticket aperto dal portale web da un cliente.

**Controlli negativi**
Se un membro dello staff (Admin/Developer/Manager) crea un ticket assegnandosi come richiedente se stesso, nessuna E3 deve partire (il richiedente non ha il ruolo Customer): verificabile creando un ticket come Admin con `requester_id` = se stesso e controllando l'assenza di una nuova E3 in Mailpit.

**Evidenze da acquisire**
- Screenshot del ticket creato dal portale.
- Screenshot dell'email E3 in Mailpit.

**Criterio di superamento**

PASS: E3 arriva su Mailpit per un ticket cliente creato dal web.
FAIL: nessuna E3, o E3 duplicata, o inviata anche per un ticket non-cliente.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura di una pagina autenticata necessaria al test.
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

### F3-57 — E9 e una notifica in-app arrivano a ogni destinatario staff quando un messaggio va in quarantena

**Obiettivo**
Verificare che, quando arriva un'email da un mittente mai visto prima (non identificabile su `users.email`), lo staff riceva sia l'email E9 sia una notifica in-app Filament, con un messaggio che resta comunque consultabile in quarantena (mai scartato).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-312/US-308, §7.5.2 E9, §7.3.8.
- Test automatico: `tests/Feature/Domain/Mail/Listeners/NotifyStaffOfUnknownSenderTest.php` — `sends E9 and an in-app notification to every resolved staff recipient` (il test correlato verifica che, se il gruppo staff è vuoto, nessuno viene notificato, nessun errore).
- File/componente applicativo rilevante: `App\Domain\Mail\Listeners\NotifyStaffOfUnknownSender`, `App\Domain\Mail\Support\StaffDatabaseNotification`, evento `App\Domain\Mail\Events\EmailQuarantined`.
- Test correlato: F3-112 (checkpoint, stesso scenario end-to-end).

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Anonimo (mittente esterno mai registrato) per il trigger, Admin per la verifica della notifica in-app

**Prerequisiti**
- Un indirizzo email reale mai usato prima in questo ambiente (nessun utente `users` con quell'email).
- Accesso a Mailpit UAT.
- Accesso Admin al pannello UAT per verificare la campanella delle notifiche.

**Dati di test**
- Oggetto email: `COLL-F3-57 — Informazioni sui servizi`.
- Mittente: un indirizzo mai registrato, es. `collaudo-f3-57@example.com`.

**Stato iniziale**
Nessun `email_messages` con quel `from_email`.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Invia un'email alla casella di supporto UAT da un indirizzo mai registrato | Oggetto `COLL-F3-57 — Informazioni sui servizi` | Email inviata |
| 2 | Attendi il fetch (o fallo lanciare manualmente da un developer) | — | Il messaggio compare in "Quarantena" (`EmailQuarantine`), non scartato |
| 3 | Apri Mailpit UAT | — | È presente un'email E9 ("Mittente sconosciuto") per ciascun indirizzo del gruppo staff, con estratto del messaggio e link alla pagina di quarantena |
| 4 | Accedi come Admin al pannello e osserva la campanella delle notifiche | — | È presente una notifica in-app per il messaggio in quarantena |

**Risultato finale atteso**
Sia E9 (email) sia la notifica in-app raggiungono lo staff, e il messaggio resta ispezionabile in quarantena.

**Controlli negativi**
Nessuno applicabile (il caso "gruppo staff vuoto" non è configurabile in modo sicuro su UAT).

**Evidenze da acquisire**
- Screenshot del messaggio in "Quarantena".
- Screenshot dell'email E9 in Mailpit.
- Screenshot della campanella di notifiche in-app (se non impedita dal bug noto sotto).

**Criterio di superamento**

PASS: E9 arriva su Mailpit e la notifica in-app compare per lo staff.
FAIL: nessuna E9, oppure il messaggio viene scartato invece che messo in quarantena.
BLOCKED: il bug noto `->databaseNotifications()` (`text ->> unknown` su Postgres) può impedire l'apertura di qualunque pagina autenticata del pannello, inclusa la verifica della campanella — se si manifesta, non è una regressione di questo test.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il messaggio in quarantena resta nel dataset, utile anche per F3-93/F3-95.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F3-58 — Cambiare il gruppo staff in configurazione cambia i destinatari senza toccare Mailable/listener

**Obiettivo**
Verificare che il gruppo di destinatari di E3 sia derivato interamente da `config('mail_pipeline.staff_notification_group')` (env `MAIL_STAFF_NOTIFICATION_GROUP`), e non da una lista hard-coded nel codice: cambiando la configurazione, cambiano i destinatari, senza toccare `SendNewCustomerTicketStaffMail`/i listener.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-312, AC esplicito "il gruppo destinatari è letto da configurazione, non hard-coded".
- Test automatico: `tests/Feature/Domain/Mail/Actions/SendNewCustomerTicketStaffMailTest.php` — `changing the staff group in config changes the recipients without touching the mailable or listener`.
- File/componente applicativo rilevante: `App\Domain\Mail\Support\StaffNotificationGroup::recipients()`, `config/mail_pipeline.php`.
- Test correlato: F3-55, F3-56.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Bassa

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante (cambiare `MAIL_STAFF_NOTIFICATION_GROUP` in UAT richiederebbe un redeploy, non è un'azione di collaudo funzionale ripetibile in sessione).

**Dati di test**
Due configurazioni successive di `mail_pipeline.staff_notification_group`: prima un solo indirizzo, poi due indirizzi diversi.

**Stato iniziale**
Non applicabile: test automatico puro su configurazione in memoria.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "changing the staff group in config changes the recipients without touching the mailable or listener"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa, dimostrando che il numero di email E3 accodate segue esattamente il numero di indirizzi in configurazione.

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
## Mailable E4 — cambio di stato (US-313)

### F3-59 — E4 viene inviata ai destinatari del ticket quando lo stato cambia

**Obiettivo**
Verificare che un cambio di stato di un ticket generi l'email E4 verso i destinatari previsti dalla tabella attore×transizione (US-318), corregendo il problema 11 del v1 (`$recipient->role` inesistente, template unico per tutti).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-313, §7.5.2 E4.
- Test automatico: `tests/Feature/Domain/Mail/Listeners/SendTicketStatusChangedNotificationTest.php` — `sends E4 to the ticket recipients when the status changes` (transizione New→Rejected, attore Manager, richiedente Customer).
- File/componente applicativo rilevante: `App\Domain\Mail\Listeners\SendTicketStatusChangedNotification`, `App\Domain\Mail\Actions\SendTicketStatusChangedMail`, `App\Domain\Mail\Mailables\TicketStatusChangedMail`.
- Test correlato: F3-62, F3-73.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Critica

**Ruolo del tester**
Manager (per la transizione), Customer (destinatario)

**Prerequisiti**
- Un ticket `COLL-F3-59-...` con richiedente Customer (`infosentieroitalia@cai.it`), in stato "Nuovo".
- Accesso a Mailpit UAT.

**Dati di test**
- Ticket `COLL-F3-59 — Verifica notifica cambio stato`, richiedente `infosentieroitalia@cai.it`.

**Stato iniziale**
Ticket in stato "Nuovo", nessuna email precedente per questo ticket in Mailpit.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Manager e apri il ticket | `COLL-F3-59...` | Dettaglio ticket visibile |
| 2 | Transisci il ticket da "Nuovo" a "Rifiutato" | — | Il badge di stato mostra "Rifiutato" |
| 3 | Apri Mailpit UAT | — | È presente un'email E4 indirizzata al richiedente (`infosentieroitalia@cai.it`) |

**Risultato finale atteso**
Il richiedente riceve E4 quando lo stato del suo ticket cambia in una transizione rilevante.

**Controlli negativi**
Nessuno applicabile (vedi F3-61 per l'esclusione dell'attore).

**Evidenze da acquisire**
- Screenshot del ticket con lo stato aggiornato.
- Screenshot dell'email E4 in Mailpit.

**Criterio di superamento**

PASS: E4 arriva su Mailpit al richiedente dopo la transizione.
FAIL: nessuna E4, o inviata a un destinatario errato.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-60 — Il contenuto mostra un testo diverso per un destinatario cliente rispetto allo staff

**Obiettivo**
Verificare che il template E4 mostri una formulazione diversa quando il destinatario è un cliente ("Il tuo ticket...") rispetto a quando è un membro dello staff (che vede invece il nome del richiedente), pur trattandosi dello stesso evento di cambio stato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-313, AC "determina il contenuto/link in base al ruolo reale del destinatario".
- Test automatico: `tests/Feature/Domain/Mail/Mailables/TicketStatusChangedMailTest.php` — `shows different wording for a customer recipient than for a staff recipient`.
- File/componente applicativo rilevante: `App\Domain\Mail\Mailables\TicketStatusChangedMail` (proprietà `recipientIsCustomer`, decisa da `$recipient->hasRole(UserRole::Customer->value)`).
- Test correlato: F3-59, F3-62.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Manager

**Prerequisiti**
- Un ticket con richiedente Customer e assegnatario Developer entrambi valorizzati.
- Accesso a Mailpit UAT.

**Dati di test**
- Ticket `COLL-F3-60 — Testo differenziato per ruolo`, richiedente `infosentieroitalia@cai.it`, assegnatario `lorena.sava@montagnaservizi.com`, in stato "In test".

**Stato iniziale**
Ticket in stato "In test" (Testing), con richiedente e assegnatario valorizzati.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Manager e apri il ticket `COLL-F3-60...` | — | Dettaglio visibile |
| 2 | Transisci il ticket da "In test" a "Rifiutato" (transizione che notifica sia il richiedente sia l'assegnatario) | — | Badge aggiornato a "Rifiutato" |
| 3 | Apri Mailpit UAT e individua le due email E4 (una al richiedente, una all'assegnatario) | — | Due email presenti |
| 4 | Confronta il testo delle due email | — | L'email al richiedente contiene la formula "Il tuo ticket"; l'email all'assegnatario NON la contiene, ma mostra il nome del richiedente (`Infosentiero Italia` o nome reale) |

**Risultato finale atteso**
Le due email E4 hanno una formulazione diversa coerente col ruolo del destinatario.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot di entrambe le email in Mailpit, evidenziando la differenza testuale.

**Criterio di superamento**

PASS: la formulazione differisce correttamente tra cliente e staff.
FAIL: le due email hanno lo stesso testo, o il testo cliente compare anche nell'email staff.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-61 — L'attore dell'azione è sempre escluso dai destinatari, anche quando la tabella lo indicherebbe

**Obiettivo**
Verificare che chi esegue personalmente un cambio di stato non riceva mai la relativa notifica E4, anche quando la tabella attore×transizione lo includerebbe come destinatario (es. l'assegnatario nella transizione "In test"→"Testato").

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-313/US-318, principio "nessuno riceve la notifica di un'azione che ha eseguito lui stesso".
- Test automatico: `tests/Feature/Domain/Mail/Actions/SendTicketStatusChangedMailTest.php` — `excludes the actor even when the table would otherwise notify them` (transizione Testing→Tested, l'assegnatario è anche l'attore: nessuna email).
- File/componente applicativo rilevante: `App\Domain\Mail\Support\NotificationRecipientResolver::resolve()` (esclusione applicata alla fine, dopo aver risolto i ruoli).
- Test correlato: F3-59, F3-74.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Un ticket assegnato a `lorena.sava@montagnaservizi.com`, in stato "In test", con lo stesso Developer come tester.
- Accesso a Mailpit UAT (per verificare l'assenza dell'email, controllare l'intera casella dopo l'azione).

**Dati di test**
- Ticket `COLL-F3-61 — Nessuna auto-notifica`, assegnatario e tester = `lorena.sava@montagnaservizi.com`.

**Stato iniziale**
Ticket in stato "In test".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer (`lorena.sava@montagnaservizi.com`) e apri il ticket | `COLL-F3-61...` | Dettaglio visibile |
| 2 | Transisci il ticket da "In test" a "Testato" (transizione che normalmente notifica l'assegnatario) | — | Badge aggiornato a "Testato" |
| 3 | Apri Mailpit UAT e cerca email indirizzate a `lorena.sava@montagnaservizi.com` con oggetto relativo a questo ticket, inviate dopo il passo 2 | — | Nessuna nuova email E4 presente per questo ticket verso quell'indirizzo |

**Risultato finale atteso**
Nessuna email E4 raggiunge l'attore che ha eseguito personalmente la transizione.

**Controlli negativi**
Verifica che, se un secondo utente (es. Manager) esegue la stessa transizione su un ticket assegnato a un developer diverso da sé, l'email E4 arrivi regolarmente: conferma che l'esclusione riguarda solo l'attore, non l'intera regola.

**Evidenze da acquisire**
- Screenshot del ticket transito a "Testato".
- Screenshot della ricerca in Mailpit che non mostra nuove email per l'attore.

**Criterio di superamento**

PASS: nessuna E4 arriva all'attore che ha eseguito la transizione su se stesso.
FAIL: l'attore riceve comunque l'email.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-62 — La notifica raggiunge il ruolo atteso per ciascuna transizione della tabella (US-318)

**Obiettivo**
Verificare che, per ciascuna delle transizioni elencate nella tabella attore×transizione di US-318 (`new→rejected` al richiedente, `progress→testing` al tester, `testing→tested`/`testing→todo` all'assegnatario, `backlog→rejected` che ricade sul catch-all e notifica il richiedente, ecc.), E4 raggiunga esattamente il ruolo previsto.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-313/US-318.
- Test automatico: `tests/Feature/Domain/Mail/Actions/SendTicketStatusChangedMailTest.php` — `sends the notification to the expected role for each table-driven transition` (dataset di 6 transizioni: new→rejected/requester, progress→testing/tester, testing→tested/assignee, testing→todo/assignee, progress→waiting/requester, backlog→rejected via catch-all/requester).
- File/componente applicativo rilevante: `App\Domain\Mail\Support\NotificationRecipientResolver`.
- Test correlato: F3-59, F3-73, F3-75.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Manager

**Prerequisiti**
- Un ticket con richiedente (Customer), assegnatario e tester (entrambi Developer, eventualmente lo stesso account se non se ne dispone di un secondo) valorizzati.

**Dati di test**
- Ticket `COLL-F3-62 — Matrice destinatari`, con `waiting_reason`/`problem_reason` valorizzati se richiesti dalla transizione scelta.

**Stato iniziale**
Ticket in stato "In lavorazione" (Progress).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Manager e apri il ticket `COLL-F3-62...` | — | Dettaglio visibile |
| 2 | Transisci il ticket da "In lavorazione" a "In test" (progress→testing) | — | Badge "In test" |
| 3 | Apri Mailpit UAT | — | E4 presente solo per il tester del ticket, non per il richiedente né l'assegnatario |
| 4 | (Verifica tecnica facoltativa) Esegui l'intero dataset automatico che copre le altre 5 transizioni | `vendor/bin/pest --filter "sends the notification to the expected role for each table-driven transition"` | Il comando termina con exit code 0, tutte le combinazioni passed |

**Risultato finale atteso**
La transizione osservata in UI notifica esattamente il ruolo previsto dalla tabella; il resto della matrice è confermato dal test automatico.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot dell'email E4 in Mailpit con destinatario coerente col ruolo atteso.
- Output del comando Pest del passo 4.

**Criterio di superamento**

PASS: il destinatario osservato in UI corrisponde al ruolo atteso e il test automatico copre l'intera matrice con successo.
FAIL: un destinatario non corrisponde al ruolo previsto, in UI o nel test automatico.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello, oppure l'ambiente CI/locale non è disponibile per il passo 4.
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
## Mailable E5 — nuovo messaggio sul ticket (US-314)

### F3-63 — E5 viene inviata ai destinatari del ticket quando un messaggio pubblico viene pubblicato

**Obiettivo**
Verificare che, quando un membro dello staff pubblica un messaggio pubblico su un ticket, i partecipanti collegati (richiedente compreso) ricevano l'email E5.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-314, §7.5.2 E5.
- Test automatico: `tests/Feature/Domain/Mail/Listeners/SendNewTicketMessageNotificationTest.php` — `sends E5 to the ticket recipients when a public message is posted`.
- File/componente applicativo rilevante: `App\Domain\Mail\Listeners\SendNewTicketMessageNotification`, `App\Domain\Mail\Actions\SendNewTicketMessageMail`, `App\Domain\Mail\Mailables\NewTicketMessageMail`.
- Test correlato: F3-64, F3-65.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Critica

**Ruolo del tester**
Developer (autore del messaggio)

**Prerequisiti**
- Un ticket con richiedente Customer e assegnatario Developer, in una conversazione attiva.
- Accesso a Mailpit UAT.

**Dati di test**
- Ticket `COLL-F3-63 — Nuovo messaggio pubblico`, richiedente `infosentieroitalia@cai.it`, assegnatario `lorena.sava@montagnaservizi.com`.
- Testo del messaggio: "Abbiamo verificato il problema, vi aggiorniamo a breve."

**Stato iniziale**
Ticket esistente, nessun messaggio pubblico oltre a quello iniziale.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer e apri il ticket `COLL-F3-63...` | — | Dettaglio ticket visibile |
| 2 | Pubblica un nuovo messaggio pubblico (non interno) | Testo indicato sopra | Il messaggio compare nella conversazione |
| 3 | Apri Mailpit UAT | — | È presente un'email E5 indirizzata al richiedente (`infosentieroitalia@cai.it`) |

**Risultato finale atteso**
Il richiedente riceve E5 quando un messaggio pubblico viene pubblicato sul suo ticket.

**Controlli negativi**
Nessuno applicabile (vedi F3-64 per i messaggi interni).

**Evidenze da acquisire**
- Screenshot del messaggio pubblicato.
- Screenshot dell'email E5 in Mailpit.

**Criterio di superamento**

PASS: E5 arriva su Mailpit al richiedente dopo la pubblicazione del messaggio.
FAIL: nessuna E5, o inviata a un destinatario non collegato al ticket.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-64 — Un messaggio interno non genera mai E5, nemmeno verso lo staff

**Obiettivo**
Verificare che un messaggio marcato "interno" non generi mai l'email E5, nemmeno verso altri membri dello staff collegati al ticket — una nota interna non deve mai uscire dal pannello via email.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-314, AC "i messaggi visibility=internal non generano mai questa email".
- Test automatico: `tests/Feature/Domain/Mail/Listeners/SendNewTicketMessageNotificationTest.php` — `does not send E5 when the posted message is internal` (il test correlato in `Actions/SendNewTicketMessageMailTest.php`, `never sends anything for an internal message, not even to staff`, lo conferma anche a livello di Action).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\SendNewTicketMessageMail` (guard `$message->visibility !== TicketMessageVisibility::Public`, early return).
- Test correlato: F3-63.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Un ticket con assegnatario Developer e almeno un altro membro dello staff collegato (es. come tester).
- Accesso a Mailpit UAT.

**Dati di test**
- Ticket `COLL-F3-64 — Nota interna senza notifica`.
- Testo della nota interna: "Nota interna: attendere risposta del fornitore prima di procedere."

**Stato iniziale**
Ticket esistente, conversazione vuota o già presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer e apri il ticket `COLL-F3-64...` | — | Dettaglio visibile |
| 2 | Pubblica un messaggio marcato esplicitamente come "interno" | Testo indicato sopra | Il messaggio compare nella conversazione con l'indicatore di visibilità interna |
| 3 | Apri Mailpit UAT e cerca email inviate dopo il passo 2 relative a questo ticket | — | Nessuna nuova email E5 presente, per nessun destinatario |

**Risultato finale atteso**
Nessuna email E5 viene generata per un messaggio interno.

**Controlli negativi**
Ripetere lo stesso passo con un messaggio pubblico sullo stesso ticket conferma che E5 parte regolarmente in quel caso (vedi F3-63), isolando la causa alla sola visibilità.

**Evidenze da acquisire**
- Screenshot del messaggio interno pubblicato.
- Screenshot della ricerca in Mailpit priva di nuove email.

**Criterio di superamento**

PASS: nessuna E5 viene inviata per un messaggio interno.
FAIL: E5 viene comunque inviata, anche solo a un destinatario.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-65 — I destinatari sono richiedente, assegnatario e tester, escluso l'autore del messaggio

**Obiettivo**
Verificare che un messaggio pubblico venga notificato a richiedente, assegnatario e tester del ticket (oltre a eventuali partecipanti), ma mai al suo stesso autore.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-314, riuso di `Ticket::messageRecipients()` (US-106).
- Test automatico: `tests/Feature/Domain/Mail/Actions/SendNewTicketMessageMailTest.php` — `notifies requester, assignee and tester but excludes the author of the message` (il test correlato `includes participants in addition to requester, assignee and tester` conferma che i partecipanti aggiunti al ticket sono inclusi allo stesso modo).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\SendNewTicketMessageMail::run()`, `Ticket::messageRecipients(User $author)`.
- Test correlato: F3-63.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Developer (come assegnatario e autore del messaggio)

**Prerequisiti**
- Un ticket con richiedente (Customer), assegnatario (Developer, autore del messaggio) e tester (un secondo account Developer; se non disponibile, l'Admin crea un utente temporaneo con ruolo Developer come prerequisito).

**Dati di test**
- Ticket `COLL-F3-65 — Destinatari multipli`, richiedente `infosentieroitalia@cai.it`, assegnatario `lorena.sava@montagnaservizi.com`, tester = utente temporaneo Developer.

**Stato iniziale**
Ticket esistente con i tre ruoli valorizzati.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come assegnatario (`lorena.sava@montagnaservizi.com`) e apri il ticket | `COLL-F3-65...` | Dettaglio visibile |
| 2 | Pubblica un messaggio pubblico | Testo qualunque | Messaggio pubblicato |
| 3 | Apri Mailpit UAT | — | Sono presenti due email E5: una al richiedente, una al tester; nessuna email E5 all'assegnatario (autore) |

**Risultato finale atteso**
I destinatari di E5 sono esattamente richiedente e tester, mai l'autore del messaggio.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot delle due email E5 in Mailpit.
- Screenshot che conferma l'assenza di E5 verso l'assegnatario/autore.

**Criterio di superamento**

PASS: E5 raggiunge richiedente e tester, mai l'autore del messaggio.
FAIL: manca un destinatario atteso, oppure l'autore riceve comunque l'email.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Se creato, rimuovere l'utente Developer temporaneo usato come tester.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

## Mailable E6 — assegnazione (US-315)

### F3-66 — E6 viene accodata al nuovo assegnatario quando TicketAssigned viene dispatchato

**Obiettivo**
Verificare che, quando un ticket viene assegnato a un developer, questi riceva l'email E6 di notifica assegnazione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-315, §7.5.2 E6.
- Test automatico: `tests/Feature/Domain/Mail/Listeners/SendTicketAssignedNotificationTest.php` — `sends E6 to the new assignee when TicketAssigned is dispatched`.
- File/componente applicativo rilevante: `App\Domain\Mail\Listeners\SendTicketAssignedNotification`, `App\Domain\Mail\Actions\SendTicketAssignedMail`, `App\Domain\Mail\Mailables\TicketAssignedMail`.
- Test correlato: F3-67, F3-68.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Critica

**Ruolo del tester**
Manager (per assegnare), Developer (destinatario)

**Prerequisiti**
- Un ticket `COLL-F3-66-...` non ancora assegnato.
- Accesso a Mailpit UAT.

**Dati di test**
- Ticket `COLL-F3-66 — Notifica assegnazione`.

**Stato iniziale**
Ticket senza assegnatario.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Manager e apri il ticket `COLL-F3-66...` | — | Dettaglio visibile |
| 2 | Assegna il ticket a `lorena.sava@montagnaservizi.com` | — | Il campo assegnatario mostra il developer scelto |
| 3 | Apri Mailpit UAT | — | È presente un'email E6 indirizzata a `lorena.sava@montagnaservizi.com` |

**Risultato finale atteso**
Il nuovo assegnatario riceve E6.

**Controlli negativi**
Nessuno applicabile (vedi F3-67 per l'auto-assegnazione).

**Evidenze da acquisire**
- Screenshot dell'assegnazione effettuata.
- Screenshot dell'email E6 in Mailpit.

**Criterio di superamento**

PASS: E6 arriva su Mailpit al nuovo assegnatario.
FAIL: nessuna E6, o inviata a un destinatario errato.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-67 — Nessuna notifica se il nuovo assegnatario è l'attore che ha eseguito l'azione

**Obiettivo**
Verificare che, quando un membro dello staff assegna un ticket a se stesso, non riceva nessuna email E6 (nessuno viene notificato di un'azione eseguita su se stesso).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-315, AC "solo se l'assegnatario è diverso da chi esegue l'azione".
- Test automatico: `tests/Feature/Domain/Mail/Actions/SendTicketAssignedMailTest.php` — `does not notify anyone when the new assignee performed the action themselves`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\SendTicketAssignedMail::run()` (confronto diretto `$newUserId === $actor->id`).
- Test correlato: F3-66.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Media

**Ruolo del tester**
Developer

**Prerequisiti**
- Un ticket `COLL-F3-67-...` non assegnato, a cui il Developer ha accesso per auto-assegnarsi.

**Dati di test**
- Ticket `COLL-F3-67 — Nessuna auto-notifica assegnazione`.

**Stato iniziale**
Ticket senza assegnatario.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer (`lorena.sava@montagnaservizi.com`) e apri il ticket | `COLL-F3-67...` | Dettaglio visibile |
| 2 | Assegna il ticket a se stesso | — | Il campo assegnatario mostra il Developer stesso |
| 3 | Apri Mailpit UAT e cerca nuove email verso `lorena.sava@montagnaservizi.com` relative a questo ticket | — | Nessuna nuova email E6 presente |

**Risultato finale atteso**
Nessuna email E6 viene inviata quando l'assegnatario coincide con l'attore.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot dell'auto-assegnazione.
- Screenshot della ricerca in Mailpit priva di nuove email.

**Criterio di superamento**

PASS: nessuna E6 viene inviata per un'auto-assegnazione.
FAIL: E6 viene comunque inviata.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-68 — E6 viene inviata anche al nuovo tester quando TicketTesterAssigned viene dispatchato

**Obiettivo**
Verificare che, oltre all'assegnatario, anche l'assegnazione del campo "tester" generi l'email E6, riusando lo stesso Mailable con `asTester = true`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-315.
- Test automatico: `tests/Feature/Domain/Mail/Listeners/SendTicketTesterAssignedNotificationTest.php` — `sends E6 to the new tester when TicketTesterAssigned is dispatched`.
- File/componente applicativo rilevante: `App\Domain\Mail\Listeners\SendTicketTesterAssignedNotification`, `App\Domain\Mail\Mailables\TicketAssignedMail` (proprietà `asTester`).
- Test correlato: F3-66.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Manager (o Developer con permesso di assegnazione tester)

**Prerequisiti**
- Un ticket in stato "In lavorazione" pronto per la transizione verso "In test" (che nella macchina a stati può valorizzare contestualmente il tester), oppure un campo tester editabile direttamente.
- Un secondo account Developer disponibile come tester (se non disponibile, l'Admin ne crea uno temporaneo).

**Dati di test**
- Ticket `COLL-F3-68 — Notifica assegnazione tester`.

**Stato iniziale**
Ticket senza tester assegnato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Manager e apri il ticket `COLL-F3-68...` | — | Dettaglio visibile |
| 2 | Assegna un tester al ticket (direttamente o transitando lo stato in modo da valorizzare il campo tester) | Account Developer scelto come tester | Il campo tester mostra l'utente scelto |
| 3 | Apri Mailpit UAT | — | È presente un'email E6 indirizzata al nuovo tester, con un testo/contesto relativo all'assegnazione come tester |

**Risultato finale atteso**
Il nuovo tester riceve E6.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot dell'assegnazione del tester.
- Screenshot dell'email E6 in Mailpit.

**Criterio di superamento**

PASS: E6 arriva su Mailpit al nuovo tester.
FAIL: nessuna E6, o inviata a un destinatario errato.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Se creato, rimuovere l'utente Developer temporaneo usato come tester.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---
## Mailable E7 — reminder ticket in attesa + scheduling (US-316)

### F3-69 — Il comando invia il reminder al richiedente di un ticket waiting fermo da almeno la soglia

**Obiettivo**
Verificare che `tickets:remind-waiting` invii `TicketWaitingReminderMail` al richiedente di un ticket in stato "In attesa" senza attività rilevante da almeno 3 giorni lavorativi, e che il comando sia effettivamente schedulato (correggendo il gap del v1, dove il comando esisteva ma non girava mai).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-316, §7.5.2 E7.
- Test automatico: `tests/Feature/Console/TicketsRemindWaitingCommandTest.php` — `reminds the requester of a waiting ticket idle for at least the threshold` (lunedì 2026-08-10 → giovedì 2026-08-13 = 3 giorni lavorativi; i test correlati nello stesso file confermano che l'ultima attività rilevante è il massimo tra `ticket_logs.occurred_at` e `ticket_views.last_viewed_at`, non solo la data di creazione).
- File/componente applicativo rilevante: `App\Console\Commands\TicketsRemindWaitingCommand`, `App\Domain\Ticketing\Support\WorkingDaysCalculator::haveElapsed()`, `routes/console.php` (registrazione schedulata).
- Test correlato: F3-70.

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore (accesso SSH/Docker al container `app` di UAT)

**Prerequisiti**
- Un ticket reale in stato "In attesa" da almeno 3 giorni lavorativi (verificabile sui dati reali importati, oppure creato ad hoc e retrodatato via `tinker` per non dover attendere realmente i giorni — attendere 3 giorni lavorativi reali non è praticabile in una sessione di collaudo).
- Accesso al container applicativo UAT e a Mailpit UAT.

**Dati di test**
- Ticket `COLL-F3-69 — Reminder ticket in attesa`, richiedente Customer, stato "In attesa" con `status_changed_at`/ultimo log retrodatato di almeno 3 giorni lavorativi.

**Stato iniziale**
Ticket in stato "In attesa", nessun reminder già inviato per esso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Crea (o individua) un ticket in stato "In attesa" con ultima attività di almeno 3 giorni lavorativi fa | `COLL-F3-69...` | Ticket pronto |
| 2 | Da un developer con accesso al container, esegui manualmente il comando | `docker compose exec app php artisan tickets:remind-waiting` | Il comando termina con exit code 0 |
| 3 | Apri Mailpit UAT | — | È presente un'email `TicketWaitingReminderMail` indirizzata al richiedente del ticket |

**Risultato finale atteso**
Il richiedente riceve il reminder quando il ticket è fermo da almeno la soglia configurata.

**Controlli negativi**
Ripetere il comando su un ticket in attesa da meno di 3 giorni lavorativi non deve generare alcuna email.

**Evidenze da acquisire**
- Output del comando eseguito.
- Screenshot dell'email di reminder in Mailpit.

**Criterio di superamento**

PASS: il reminder arriva su Mailpit al richiedente del ticket fermo da almeno la soglia.
FAIL: nessun reminder, o inviato a un ticket non idoneo.
BLOCKED: nessun accesso al container UAT per eseguire il comando.
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

### F3-70 — Un ticket già ricordato di recente non riceve un secondo reminder nella finestra di cooldown

**Obiettivo**
Verificare che un ticket per cui è già stato inviato un reminder recente non ne riceva un secondo nella stessa finestra di cooldown, evitando promemoria duplicati ravvicinati.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-316, AC "un ticket già ricordato di recente non riceve un secondo reminder duplicato".
- Test automatico: `tests/Feature/Console/TicketsRemindWaitingCommandTest.php` — `skips a ticket already reminded within the cooldown window` (verifica interrogando `email_messages` per `mailable_class = TicketWaitingReminderMail::class` nella finestra di cooldown, nessuna nuova colonna dedicata).
- File/componente applicativo rilevante: `App\Console\Commands\TicketsRemindWaitingCommand`.
- Test correlato: F3-69.

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Lo stesso ticket usato in F3-69, per cui un reminder è già stato inviato (o un `email_messages` con `mailable_class = TicketWaitingReminderMail` creato ad hoc per lo stesso ticket con `created_at` recente).

**Dati di test**
- Ticket `COLL-F3-69...` (o `COLL-F3-70-...` equivalente), già con un reminder recente registrato.

**Stato iniziale**
Il ticket ha già un `email_messages` con `mailable_class = TicketWaitingReminderMail` creato entro la finestra di cooldown configurata.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Esegui nuovamente il comando dal container | `docker compose exec app php artisan tickets:remind-waiting` | Il comando termina con exit code 0 |
| 2 | Apri Mailpit UAT | — | Nessuna nuova email di reminder per questo ticket rispetto all'esecuzione precedente |

**Risultato finale atteso**
Nessun secondo reminder viene inviato entro la finestra di cooldown.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output del comando.
- Screenshot di Mailpit che conferma l'assenza di un secondo reminder.

**Criterio di superamento**

PASS: nessun secondo reminder viene inviato entro il cooldown.
FAIL: un secondo reminder viene comunque inviato.
BLOCKED: nessun accesso al container UAT.
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

## Preferenze di notifica — applicazione effettiva (US-317)

### F3-71 — Un tipo di notifica senza nessuna riga preferenza è consentito per default

**Obiettivo**
Verificare che un utente senza alcuna riga in `notification_preferences` per un dato tipo di notifica continui a riceverla normalmente (default "abilitato"), coerente con lo schema (`enabled` default `true`).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-317, AC "un utente senza righe in notification_preferences per un dato tipo riceve la notifica".
- Test automatico: `tests/Unit/Domain/Mail/Support/NotificationGateTest.php` — `allows a notification type with no preference row at all (default enabled)`.
- File/componente applicativo rilevante: `App\Domain\Mail\Support\NotificationGate::allows()`.
- Test correlato: F3-72.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Manager

**Prerequisiti**
- Un ticket con richiedente Customer che non ha mai impostato alcuna preferenza di notifica (caso normale per la quasi totalità degli utenti reali importati dal v1, dato che la UI di gestione preferenze è fuori scope fino alla Fase 6).

**Dati di test**
- Ticket `COLL-F3-71 — Notifica di default abilitata`, richiedente `infosentieroitalia@cai.it`.

**Stato iniziale**
Nessuna riga `notification_preferences` per il richiedente e il tipo `TicketStatusChanged`.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Manager e apri il ticket `COLL-F3-71...` | — | Dettaglio visibile |
| 2 | Transisci il ticket in una transizione che notifica il richiedente (es. Nuovo → Rifiutato) | — | Badge aggiornato |
| 3 | Apri Mailpit UAT | — | È presente l'email E4 per il richiedente |

**Risultato finale atteso**
La notifica arriva regolarmente in assenza di qualunque preferenza esplicita.

**Controlli negativi**
Nessuno applicabile (vedi F3-72 per il caso disabilitato).

**Evidenze da acquisire**
- Screenshot dell'email ricevuta in Mailpit.

**Criterio di superamento**

PASS: la notifica arriva regolarmente senza alcuna riga di preferenza.
FAIL: la notifica non arriva.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-72 — Un tipo di notifica esplicitamente disabilitato nelle preferenze viene bloccato

**Obiettivo**
Verificare che, se esiste una riga `notification_preferences` con `enabled = false` per un utente e un tipo di notifica, quella notifica non venga effettivamente inviata — anche se l'evento che la genererebbe si verifica normalmente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-317.
- Test automatico: `tests/Unit/Domain/Mail/Support/NotificationGateTest.php` — `blocks a notification type explicitly disabled in preferences` (il test correlato conferma che una riga disabilitata per un tipo/utente diverso non influisce su altri invii).
- File/componente applicativo rilevante: `App\Domain\Mail\Support\NotificationGate::allows()`, chiamato da `SendOutboundTicketMail::blockedReason()`.
- Test correlato: F3-71.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore (per l'impostazione tecnica della preferenza, non gestibile da UI in questa fase), Manager (per il trigger)

**Prerequisiti**
- Nessuna UI per gestire le preferenze di notifica esiste ancora in questa fase (Fase 6): la riga va inserita da un developer via `tinker`/query diretta, come previsto esplicitamente dal PRD ("se serve un modo minimo per popolare righe di test, un comando artisan/tinker basta").
- Accesso al container UAT e a Mailpit UAT.

**Dati di test**
- Ticket `COLL-F3-72 — Notifica disabilitata`, richiedente Customer con una riga `notification_preferences` (`notification_type = ticket_status_changed`, `channel = email`, `enabled = false`).

**Stato iniziale**
Riga di preferenza disabilitata già inserita per il richiedente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da un developer, inserisci la riga di preferenza disabilitata per il richiedente | `tinker`: `NotificationPreference::create([...'enabled' => false])` | La riga è presente in DB |
| 2 | Come Manager, transisci il ticket in una transizione che notificherebbe normalmente il richiedente | — | Badge aggiornato |
| 3 | Apri Mailpit UAT e cerca nuove email verso il richiedente relative a questo ticket | — | Nessuna nuova email presente |

**Risultato finale atteso**
Nessuna email viene inviata per un tipo di notifica esplicitamente disabilitato.

**Controlli negativi**
Rimuovere la riga di preferenza e ripetere la stessa transizione su un ticket gemello conferma che, senza la preferenza, l'email torna ad arrivare (vedi F3-71).

**Evidenze da acquisire**
- Output del comando tinker.
- Screenshot di Mailpit privo di nuove email.

**Criterio di superamento**

PASS: nessuna email viene inviata quando la preferenza è disabilitata.
FAIL: l'email viene comunque inviata.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello, o nessun accesso al container per il passo 1.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere la riga `notification_preferences` inserita per il test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

## Regole di destinazione — attore × transizione → destinatari (US-318)

### F3-73 — La transizione "new to rejected" risolve al solo richiedente

**Obiettivo**
Verificare che la transizione "Nuovo → Rifiutato" notifichi esclusivamente il richiedente del ticket, nessun altro ruolo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-318, §6.1.3 (colonna "Effetti" della macchina a stati).
- Test automatico: `tests/Unit/Domain/Mail/Support/NotificationRecipientResolverTest.php` — `new to rejected resolves to the requester only`.
- File/componente applicativo rilevante: `App\Domain\Mail\Support\NotificationRecipientResolver::resolve()`.
- Test correlato: F3-59, F3-74.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Manager

**Prerequisiti**
- Un ticket con richiedente Customer e assegnatario Developer entrambi valorizzati, in stato "Nuovo".

**Dati di test**
- Ticket `COLL-F3-73 — Solo richiedente`, richiedente `infosentieroitalia@cai.it`, assegnatario `lorena.sava@montagnaservizi.com`.

**Stato iniziale**
Ticket in stato "Nuovo".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Manager e apri il ticket `COLL-F3-73...` | — | Dettaglio visibile |
| 2 | Transisci il ticket da "Nuovo" a "Rifiutato" | — | Badge "Rifiutato" |
| 3 | Apri Mailpit UAT | — | È presente una sola email E4, indirizzata al richiedente; nessuna email verso l'assegnatario |

**Risultato finale atteso**
Solo il richiedente riceve la notifica per questa transizione.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot dell'unica email E4 in Mailpit, con destinatario visibile.

**Criterio di superamento**

PASS: solo il richiedente riceve l'email.
FAIL: anche l'assegnatario (o altri) riceve l'email, o il richiedente non la riceve.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-74 — Il richiedente è escluso quando è anche lui l'attore dell'azione

**Obiettivo**
Verificare che, se il richiedente stesso risultasse l'attore di una transizione che lo includerebbe come destinatario, non riceva comunque nessuna notifica (principio generale "nessuno riceve la notifica di un'azione che ha eseguito lui stesso", applicato qui al caso limite in cui l'attore coincide col richiedente).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-318.
- Test automatico: `tests/Unit/Domain/Mail/Support/NotificationRecipientResolverTest.php` — `the requester is excluded when they are also the actor`.
- File/componente applicativo rilevante: `App\Domain\Mail\Support\NotificationRecipientResolver::resolve()` (esclusione `reject(fn ($u) => $u->is($actor))`).
- Test correlato: F3-61, F3-73.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Non applicabile: il caso "il richiedente esegue lui stesso una transizione di stato" non è raggiungibile dall'interfaccia in UAT (le transizioni di stato richiedono il permesso `ticket.transition-any`, che nel catalogo ruoli reale — vedi `RolePermissionSeeder` — non è mai assegnato al ruolo Customer): è un invariante dell'algoritmo del resolver, verificabile solo a livello di unit test.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the requester is excluded when they are also the actor"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa.

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

### F3-75 — "problem" risolve a ogni manager attivo, escludendo l'attore se è lui stesso un manager

**Obiettivo**
Verificare che marcare un ticket come "Problema" notifichi ogni manager attivo del sistema (non solo un manager fisso), escludendo il manager che ha eseguito personalmente l'azione, e senza notificare manager disattivati.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-318, §6.1.3 (transizione verso "problem": "notifica X" = ogni Manager).
- Test automatico: `tests/Unit/Domain/Mail/Support/NotificationRecipientResolverTest.php` — `problem resolves to every active manager, excluding the actor if they are a manager` (verifica anche che un manager disattivato non riceva nulla).
- File/componente applicativo rilevante: `App\Domain\Mail\Support\NotificationRecipientResolver`, ruolo astratto `NotificationRecipientRole::Manager` risolto contro `User::query()->active()->whereHas('roles', ...)`.
- Test correlato: F3-62.

**Modalità di esecuzione**
MISTO

**Priorità**
Media

**Ruolo del tester**
Admin (per creare un secondo Manager temporaneo), Manager

**Prerequisiti**
- L'unico Manager reale noto in UAT è `manager@oc.test`: per dimostrare "ogni manager attivo, escluso l'attore" serve un secondo Manager. Come prerequisito, l'Admin crea un utente temporaneo con ruolo Manager (es. `collaudo-f3-75@example.com`).

**Dati di test**
- Ticket `COLL-F3-75 — Notifica a tutti i manager`, in stato "In lavorazione".
- Manager attore: `manager@oc.test`.
- Secondo Manager (destinatario atteso): utente temporaneo creato dall'Admin.

**Stato iniziale**
Due utenti con ruolo Manager attivi, ticket in stato "In lavorazione".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Come Admin, crea un utente temporaneo con ruolo Manager | `collaudo-f3-75@example.com` | Utente creato e attivo |
| 2 | Come `manager@oc.test`, apri il ticket `COLL-F3-75...` e transiscilo a "Problema" (con motivo problema valorizzato) | — | Badge "Problema" |
| 3 | Apri Mailpit UAT | — | È presente un'email E4 verso il Manager temporaneo; nessuna email verso `manager@oc.test` (l'attore) |

**Risultato finale atteso**
Ogni manager attivo diverso dall'attore riceve la notifica.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot dell'email ricevuta dal secondo manager.
- Screenshot della ricerca in Mailpit priva di email verso l'attore.

**Criterio di superamento**

PASS: il secondo manager riceve l'email, l'attore no.
FAIL: l'attore riceve comunque l'email, oppure il secondo manager non la riceve.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere l'utente Manager temporaneo creato per il test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---
## Bounce, DSN e soppressioni (US-319)

### F3-76 — Il DSN è correlato all'email/ticket originale via Message-ID citato nel report

**Obiettivo**
Verificare che, ricevendo un DSN (delivery status notification) di mancato recapito, il sistema lo correli all'email outbound originale e al ticket collegato, leggendo il `Message-ID` citato nella parte `message/rfc822` del report — non negli header del DSN stesso.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-319, §7.5.5.
- Test automatico: `tests/Feature/Domain/Mail/Actions/ProcessDeliveryStatusNotificationTest.php` — `il DSN è correlato al ticket dell'email originale via Message-ID citato nel report`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ProcessDeliveryStatusNotification::run()`.
- Test correlato: F3-77, F3-80, F3-110.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante. Un vero DSN non è generabile a comando in una sessione di collaudo (richiede un vero fallimento SMTP su un vero MTA): la verifica realistica end-to-end su un DSN vero è coperta da F3-110 tramite la fixture `.eml` dedicata.

**Dati di test**
DSN costruito nel test (RFC 3464, tre parti multipart/report) con `Message-ID` originale citato nella parte `message/rfc822`, correlato a un `email_messages` outbound esistente collegato a un ticket.

**Stato iniziale**
Non applicabile: dati costruiti dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "il DSN è correlato al ticket"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa, dimostrando la correlazione corretta tra DSN, email originale e ticket.

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

### F3-77 — Un hard bounce (Action: failed) sospende permanentemente il destinatario originale

**Obiettivo**
Verificare che un DSN con `Action: failed` (hard bounce) crei una riga `email_suppressions` con `reason = hard_bounce` e `expires_at = null` (sospensione permanente, non a tempo), per il destinatario originale (`Final-Recipient`).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-319, AC "hard bounce → riga in email_suppressions con reason=hard_bounce".
- Test automatico: `tests/Feature/Domain/Mail/Actions/ProcessDeliveryStatusNotificationTest.php` — `un hard bounce (Action: failed) sospende permanentemente il destinatario originale` (il test correlato conferma che un hard bounce è riconosciuto anche dal solo codice di stato `5.x.x`, senza header `Action`).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ProcessDeliveryStatusNotification::run()`.
- Test correlato: F3-76, F3-110.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante. Un vero hard bounce non è generabile a comando in sessione di collaudo (richiede un vero rifiuto SMTP): la controparte end-to-end su fixture reale è F3-110.

**Dati di test**
DSN con `Action: failed`, `Status: 5.1.1`, `Final-Recipient: rfc822; cliente@example.test`.

**Stato iniziale**
Non applicabile: dati costruiti dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "un hard bounce \(Action: failed\) sospende permanentemente il destinatario originale"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa.

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

### F3-78 — Un soft bounce sotto soglia incrementa bounce_count senza attivare la sospensione

**Obiettivo**
Verificare che un DSN con `Action: delayed` (soft bounce) incrementi `email_suppressions.bounce_count` senza attivare subito la sospensione (`expires_at` resta "già scaduto", non blocca l'invio) finché non si raggiunge la soglia configurata.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-319, AC "soft bounce → bounce_count incrementato; la soppressione scatta solo dopo N occorrenze consecutive".
- Test automatico: `tests/Feature/Domain/Mail/Actions/ProcessDeliveryStatusNotificationTest.php` — `un soft bounce sotto soglia incrementa bounce_count senza attivare la sospensione`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ProcessDeliveryStatusNotification`, `config('mail_pipeline.bounce.soft_bounce_threshold')`.
- Test correlato: F3-79.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante. La soglia esatta (default 3, da confermare col committente, vedi Open Questions del PRD) e i soft bounce ravvicinati non sono riproducibili in una sessione di collaudo reale.

**Dati di test**
DSN con `Action: delayed`, `Status: 4.2.1`, soglia configurata a 3 nel test.

**Stato iniziale**
Non applicabile: dati costruiti dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "un soft bounce sotto soglia incrementa bounce_count senza attivare la sospensione"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa.

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
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note: la soglia di default (3 occorrenze) è DA VERIFICARE CON IL PRODUCT OWNER (Open Questions del PRD di Fase 3).

---

### F3-79 — Un soft bounce che raggiunge la soglia configurata attiva la sospensione

**Obiettivo**
Verificare che, al raggiungimento della soglia configurata di soft bounce consecutivi, la sospensione diventi effettiva (`expires_at = null`, come un hard bounce), bloccando ulteriori invii verso quell'indirizzo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-319.
- Test automatico: `tests/Feature/Domain/Mail/Actions/ProcessDeliveryStatusNotificationTest.php` — `un soft bounce che raggiunge la soglia configurata attiva la sospensione` (il test correlato `un soft bounce successivo non retrocede una sospensione già hard bounce` conferma che un hard bounce precedente non viene mai "declassato" da un soft bounce arrivato dopo).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ProcessDeliveryStatusNotification::registerSoftBounce()`.
- Test correlato: F3-78, F3-77.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Una riga `email_suppressions` preesistente con `bounce_count = 2` e soglia configurata a 3; un nuovo DSN soft bounce la porta a 3.

**Stato iniziale**
Non applicabile: dati costruiti dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "un soft bounce che raggiunge la soglia configurata attiva la sospensione"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa.

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
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note: la soglia di default (3 occorrenze) è DA VERIFICARE CON IL PRODUCT OWNER (Open Questions del PRD di Fase 3).

---

### F3-80 — Un hard bounce correlato aggiorna anche lo stato dell'email originale a bounced

**Obiettivo**
Verificare che, oltre a sospendere il destinatario, un hard bounce correlato a un `email_messages` outbound esistente ne aggiorni lo stato a `bounced`, rendendo visibile in amministrazione che quella specifica email non è stata recapitata.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-319, §7.7 (visibilità in amministrazione).
- Test automatico: `tests/Feature/Domain/Mail/Actions/ProcessDeliveryStatusNotificationTest.php` — `un hard bounce correlato aggiorna anche lo stato dell'email originale a bounced`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ProcessDeliveryStatusNotification::run()`.
- Test correlato: F3-76, F3-110.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un `email_messages` outbound esistente con `message_id` noto, correlato da un DSN hard bounce che lo cita nella parte `message/rfc822`.

**Stato iniziale**
Non applicabile: dati costruiti dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "un hard bounce correlato aggiorna anche lo stato"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa.

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

### F3-81 — Le soppressioni sono rimovibili da amministrazione, riabilitando l'invio

**Obiettivo**
Verificare che un admin possa rimuovere manualmente una soppressione dalla pagina "Soppressioni", riabilitando l'invio verso quell'indirizzo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-319/US-323, AC "le soppressioni sono rimovibili da amministrazione: un admin può far ripartire l'invio verso un indirizzo tornato valido".
- Test automatico: `tests/Feature/Filament/Mail/EmailSuppressionsTest.php` — `a user with email.manage can remove a suppression, re-enabling delivery`.
- File/componente applicativo rilevante: `App\Filament\Pages\EmailSuppressions` (azione `remove`).
- Test correlato: F3-99.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore (per creare la soppressione di prova, non generabile con un bounce reale in sessione), Admin (per l'azione di rimozione)

**Prerequisiti**
- Nota permessi: verificato in `RolePermissionSeeder`, solo il ruolo Admin possiede `email.manage` tra i ruoli realmente seedati in UAT (Manager/Developer/Customer/Fundraising non lo hanno) — usare quindi l'utente Admin, non Manager, per questo test.
- Una riga `email_suppressions` di prova, inserita da un developer via `tinker` (un vero hard/soft bounce non è generabile a comando in sessione di collaudo).

**Dati di test**
- Indirizzo soppresso: `collaudo-f3-81@example.com`, `reason = hard_bounce`.

**Stato iniziale**
Riga `email_suppressions` presente per l'indirizzo di test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da un developer, inserisci una riga di soppressione di prova | `tinker`: `EmailSuppression::create(['email' => 'collaudo-f3-81@example.com', 'reason' => SuppressionReason::HardBounce])` | La riga compare nella pagina "Soppressioni" |
| 2 | Accedi come Admin alla pagina "Soppressioni" del pannello | — | La riga di test è visibile nell'elenco |
| 3 | Esegui l'azione "Rimuovi" sulla riga di test | — | La riga scompare dall'elenco |
| 4 | (Verifica tecnica facoltativa) Interroga `email_suppressions` per l'indirizzo di test | Query sull'email | Nessuna riga presente |

**Risultato finale atteso**
La soppressione viene rimossa e l'indirizzo torna eleggibile per l'invio.

**Controlli negativi**
Un utente con solo `email.view` (senza `email.manage`) non deve vedere l'azione "Rimuovi" sulla riga.

**Evidenze da acquisire**
- Screenshot della pagina "Soppressioni" prima e dopo la rimozione.

**Criterio di superamento**

PASS: la riga viene rimossa e l'indirizzo torna eleggibile.
FAIL: l'azione fallisce o la riga resta presente.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello, o nessun accesso al container per il passo 1.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno (la riga è già stata rimossa dal test stesso).

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

## Localizzazione reale delle comunicazioni (US-320)

### F3-82 — La lingua risolve a users.locale quando è impostato

**Obiettivo**
Verificare che una comunicazione email venga inviata nella lingua impostata su `users.locale` del destinatario.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-320, §7.6.
- Test automatico: `tests/Unit/Domain/Mail/Support/RecipientLocaleTest.php` — `resolves to users.locale when it is set` (il test correlato `prefers users.locale over an organization locale when both are set` conferma la precedenza su un'eventuale locale di organizzazione).
- File/componente applicativo rilevante: `App\Domain\Mail\Support\RecipientLocale::resolve()`.
- Test correlato: F3-86, F3-113.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Manager (per il trigger), Admin (per verificare/impostare la locale dell'utente)

**Prerequisiti**
- Un utente con `locale = en` (o impostabile a `en` dall'Admin sul proprio profilo, se il campo è editabile da `UserResource`).
- Accesso a Mailpit UAT.

**Dati di test**
- Ticket `COLL-F3-82 — Locale inglese`, assegnatario/richiedente con `locale = en`.

**Stato iniziale**
Utente destinatario con `locale = en`.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Verifica (o imposta) `locale = en` per l'utente destinatario tramite `UserResource` | — | Campo locale = `en` |
| 2 | Genera una notifica verso quell'utente (es. assegnazione ticket, vedi F3-66) | `COLL-F3-82...` | Notifica generata |
| 3 | Apri Mailpit UAT e apri l'email ricevuta | — | Il testo dell'email è in inglese |

**Risultato finale atteso**
L'email è in inglese per un destinatario con `locale = en`.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot dell'email in inglese in Mailpit.

**Criterio di superamento**

PASS: l'email è in inglese per il destinatario con quella locale.
FAIL: l'email è in italiano (o altra lingua) nonostante `locale = en`.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Ripristinare la locale originale dell'utente se modificata per il test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F3-83 — Fallback alla locale della prima organizzazione quando users.locale è vuoto

**Obiettivo**
Verificare che, quando `users.locale` è vuoto, la lingua della comunicazione ricada sulla locale della prima organizzazione collegata all'utente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-320.
- Test automatico: `tests/Unit/Domain/Mail/Support/RecipientLocaleTest.php` — `falls back to the first organization locale when users.locale is empty`.
- File/componente applicativo rilevante: `App\Domain\Mail\Support\RecipientLocale::resolve()`.
- Test correlato: F3-82, F3-84.

**Modalità di esecuzione**
MISTO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore (per forzare `users.locale` vuoto, non impostabile a stringa vuota da UI), Manager (per il trigger)

**Prerequisiti**
- Un utente collegato a un'organizzazione con `locale = en`, con `users.locale` forzato a stringa vuota via `tinker` (il form utente in UI normalmente non permette un valore vuoto, essendo un campo con default `it`).
- Accesso a Mailpit UAT.

**Dati di test**
- Utente di test collegato a un'organizzazione con `locale = en`, `users.locale = ''`.

**Stato iniziale**
Utente con `locale` vuoto, organizzazione collegata con `locale = en`.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da un developer, forza `locale = ''` sull'utente di test | `tinker`: `$user->update(['locale' => ''])` | Campo aggiornato |
| 2 | Genera una notifica verso quell'utente | — | Notifica generata |
| 3 | Apri Mailpit UAT | — | L'email è in inglese (locale dell'organizzazione) |

**Risultato finale atteso**
La lingua ricade sulla locale dell'organizzazione quando `users.locale` è vuoto.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot dell'email in inglese in Mailpit.

**Criterio di superamento**

PASS: l'email è in inglese, coerente con la locale dell'organizzazione.
FAIL: l'email è in italiano o in un'altra lingua inattesa.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello, o nessun accesso al container per il passo 1.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Ripristinare `users.locale` all'utente di test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F3-84 — Fallback a config app.locale quando né users.locale né una locale organizzazione sono impostati

**Obiettivo**
Verificare che, in assenza sia di `users.locale` sia di una locale di organizzazione, la lingua ricada sul valore di `config('app.locale')`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-320.
- Test automatico: `tests/Unit/Domain/Mail/Support/RecipientLocaleTest.php` — `falls back to config app.locale when neither users.locale nor an organization locale is set`.
- File/componente applicativo rilevante: `App\Domain\Mail\Support\RecipientLocale::resolve()`.
- Test correlato: F3-82, F3-83.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Bassa

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante. Il caso "nessuna locale impostata da nessuna parte" è un caso limite non presente nei dati reali importati (ogni utente v1 ha `activity_report_language` → `locale` valorizzato) e non riproducibile in modo pulito su UAT senza alterare dati reali.

**Dati di test**
Utente con `locale = ''`, nessuna organizzazione collegata, `config('app.locale') = 'en'` impostato nel test.

**Stato iniziale**
Non applicabile: dati costruiti dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "falls back to config app.locale when neither users.locale nor an organization locale is set"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa.

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

### F3-85 — Ogni chiave di traduzione usata dalla pipeline di Fase 3 esiste, non vuota, in italiano e inglese

**Obiettivo**
Verificare che ogni chiave `__()`/`trans()` effettivamente usata dal codice della pipeline email (Action/Listener/Mailable/viste Blade E1-E9) esista con un valore non vuoto sia in `lang/it.json` sia in `lang/en.json`, evitando che una chiave grezza venga mai mostrata a un destinatario reale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-320, AC "test dedicato che itera ogni chiave... un fallimento qui blocca la CI, non solo un warning".
- Test automatico: `tests/Feature/Domain/Mail/LocalizationCompletenessTest.php` — `every translation key used by the Fase 3 mail pipeline exists, non-empty, in both it.json and en.json` (scansiona `app/Domain/Mail`, `resources/views/emails`, `resources/views/components/emails` con una regex, non un elenco statico).
- File/componente applicativo rilevante: `lang/it.json`, `lang/en.json`.
- Test correlato: F3-82, F3-83, F3-84.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Nessuno: il test scansiona i file sorgente reali del repository.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "every translation key used by the Fase 3 mail pipeline exists, non-empty, in both it.json and en.json"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa, senza alcuna chiave mancante o vuota.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce, elencando le chiavi mancanti.
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

### F3-86 — Il subject viene costruito nella locale dell'assegnatario, non sempre in italiano

**Obiettivo**
Verificare che l'oggetto (subject) dell'email di assegnazione (E6) sia costruito nella lingua del destinatario (l'assegnatario), non sempre in italiano — punto delicato perché il subject è una stringa costruita PRIMA dell'invio, non tradotta lazy come il corpo Blade.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-320, §7.6.
- Test automatico: `tests/Feature/Domain/Mail/Actions/SendTicketAssignedMailTest.php` — `builds the subject in the assignee locale, not always Italian (§7.6, US-320)` (verifica `outbound->subject === "[#{id}] Ticket assigned: {title}"` per un assegnatario con `locale = en`).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\SendTicketAssignedMail`, `App\Domain\Mail\Support\RecipientLocale::resolve()`.
- Test correlato: F3-66, F3-82.

**Modalità di esecuzione**
MANUALE UI + MAILPIT

**Priorità**
Alta

**Ruolo del tester**
Manager (per assegnare), Admin (per impostare la locale dell'assegnatario)

**Prerequisiti**
- Un utente Developer con `locale = en` (impostato dall'Admin su `UserResource`, se disponibile, o creato ad hoc).
- Accesso a Mailpit UAT.

**Dati di test**
- Ticket `COLL-F3-86 — Subject in inglese`.
- Assegnatario: utente Developer con `locale = en`.

**Stato iniziale**
Ticket senza assegnatario, utente destinatario con `locale = en`.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Come Manager, apri il ticket `COLL-F3-86...` | — | Dettaglio visibile |
| 2 | Assegna il ticket all'utente con `locale = en` | — | Campo assegnatario aggiornato |
| 3 | Apri Mailpit UAT e osserva l'oggetto dell'email E6 ricevuta | — | L'oggetto è in inglese, es. `[#<id>] Ticket assigned: <titolo>`, non `[#<id>] Ticket assegnato: <titolo>` |

**Risultato finale atteso**
L'oggetto dell'email è tradotto nella locale del destinatario.

**Controlli negativi**
Assegnare lo stesso ticket a un utente con `locale = it` conferma che l'oggetto torna in italiano.

**Evidenze da acquisire**
- Screenshot dell'oggetto email in Mailpit.

**Criterio di superamento**

PASS: l'oggetto è in inglese per un destinatario con quella locale.
FAIL: l'oggetto resta in italiano nonostante `locale = en`.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Ripristinare la locale originale dell'utente se modificata per il test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---
## Amministrazione email — Registro e dettaglio (US-321)

### F3-87 — Un utente senza email.view non accede al registro email

**Obiettivo**
Verificare che un utente privo del permesso `email.view` non possa accedere al registro email (`EmailMessageResource`), né dal menu né navigando direttamente all'URL.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-321, AC "gated su un nuovo permesso email.view/email.manage... visibile solo ad admin". Verificato in `RolePermissionSeeder`: solo Admin possiede `email.view`/`email.manage` tra i ruoli realmente seedati (Manager, Developer, Customer, Fundraising non li hanno).
- Test automatico: `tests/Feature/Filament/Mail/EmailMessageResourceTest.php` — `a user without email.view is denied access to the email messages resource`.
- File/componente applicativo rilevante: `App\Filament\Resources\EmailMessages\EmailMessageResource`, `App\Domain\Mail\Policies\EmailMessagePolicy`.
- Test correlato: F3-88, F3-89.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Developer

**Prerequisiti**
- Utente Developer `lorena.sava@montagnaservizi.com` (privo di `email.view` per catalogo permessi reale).

**Dati di test**
Nessuno specifico.

**Stato iniziale**
Nessuno.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer | `lorena.sava@montagnaservizi.com` | Login riuscito |
| 2 | Verifica che la voce "Registro" (gruppo "Email") non compaia nel menu | — | Voce assente dal menu |
| 3 | Naviga direttamente all'URL del registro email | `/admin/email-messages` | Accesso negato (403) |

**Risultato finale atteso**
Un Developer non può accedere al registro email in nessun modo.

**Controlli negativi**
Nessuno applicabile (è già il controllo negativo di F3-88/89).

**Evidenze da acquisire**
- Screenshot del menu senza la voce "Registro".
- Screenshot della pagina 403.

**Criterio di superamento**

PASS: l'accesso è negato sia da menu sia da URL diretto.
FAIL: l'utente riesce ad accedere al registro.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura di qualunque pagina autenticata del pannello.
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

### F3-88 — La tabella è filtrabile per direzione

**Obiettivo**
Verificare che il registro email permetta di filtrare i messaggi per direzione (inbound/outbound).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-321, AC "tabella filtrabile per: direzione, stato, mittente, destinatario, ticket collegato, periodo".
- Test automatico: `tests/Feature/Filament/Mail/EmailMessageResourceTest.php` — `the table is filterable by direction`.
- File/componente applicativo rilevante: `App\Filament\Resources\EmailMessages\Pages\ListEmailMessages`.
- Test correlato: F3-87, F3-89.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Almeno un'email inbound e una outbound già presenti nel registro (es. dai test precedenti E1-E9).

**Dati di test**
Nessuno specifico: si usano le email già presenti nel registro UAT.

**Stato iniziale**
Registro con email di entrambe le direzioni.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin e apri "Registro" (gruppo "Email") | — | Elenco email visibile |
| 2 | Applica il filtro "Direzione" = Outbound | — | Solo le email outbound sono visibili in tabella |
| 3 | Cambia il filtro a Inbound | — | Solo le email inbound sono visibili |

**Risultato finale atteso**
Il filtro per direzione funziona correttamente in entrambi i sensi.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della tabella filtrata per ciascuna direzione.

**Criterio di superamento**

PASS: il filtro mostra esclusivamente le email della direzione selezionata.
FAIL: il filtro non funziona o mostra righe della direzione opposta.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-89 — La tabella è filtrabile per stato

**Obiettivo**
Verificare che il registro email permetta di filtrare i messaggi per stato (es. `quarantined`, `applied`).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-321.
- Test automatico: `tests/Feature/Filament/Mail/EmailMessageResourceTest.php` — `the table is filterable by status`.
- File/componente applicativo rilevante: `App\Filament\Resources\EmailMessages\Pages\ListEmailMessages`.
- Test correlato: F3-87, F3-88.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Almeno un'email in stato "Quarantena" e una in stato "Applicata" già presenti nel registro (es. dai test F3-57/F3-112).

**Dati di test**
Nessuno specifico.

**Stato iniziale**
Registro con email in stati diversi.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin e apri "Registro" | — | Elenco email visibile |
| 2 | Applica il filtro "Stato" = Quarantena | — | Solo le email in quarantena sono visibili |

**Risultato finale atteso**
Il filtro per stato funziona correttamente.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della tabella filtrata per stato.

**Criterio di superamento**

PASS: il filtro mostra esclusivamente le email nello stato selezionato.
FAIL: il filtro non funziona correttamente.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-90 — La vista di dettaglio mostra header, corpo, allegati e diagnostica

**Obiettivo**
Verificare che la vista di dettaglio di un'email nel registro mostri tutte le informazioni utili a capire cosa è successo senza consultare i log del server: mittente, destinatari, oggetto, message-id, corpo (plain e HTML sanitizzato), allegati, ticket collegato, numero tentativi ed eventuale ultimo errore.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-321, AC "vista di dettaglio: header email..., corpo..., allegati, ticket/thread collegato, numero tentativi, ultimo errore".
- Test automatico: `tests/Feature/Filament/Mail/EmailMessageResourceTest.php` — `viewing a message shows headers, body, attachments and diagnostics`.
- File/componente applicativo rilevante: `App\Filament\Resources\EmailMessages\Pages\ViewEmailMessage`, infolist con header/corpo/allegati/registro azioni.
- Test correlato: F3-88, F3-89.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Un'email collegata a un ticket, con almeno un allegato e (se disponibile) un tentativo fallito registrato — puoi usare un'email inviata via test precedente con un allegato reale (es. inviando un'email di prova con un PDF allegato alla casella di supporto UAT).

**Dati di test**
- Email di prova con oggetto `COLL-F3-90 — Dettaglio email con allegato`, corpo HTML e un allegato PDF.

**Stato iniziale**
Email presente nel registro, con ticket collegato e allegato importato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin e apri "Registro" | — | Elenco visibile |
| 2 | Apri il dettaglio dell'email di prova | `COLL-F3-90...` | Vista di dettaglio caricata |
| 3 | Verifica la presenza di tutte le sezioni | — | Mittente, destinatari, oggetto, message-id, corpo (plain e HTML), allegato, ticket collegato sono tutti visibili |

**Risultato finale atteso**
Tutte le informazioni sono consultabili dalla sola UI, senza log server.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot completo della vista di dettaglio.

**Criterio di superamento**

PASS: tutte le sezioni attese sono presenti e coerenti coi dati reali dell'email.
FAIL: manca una sezione, o i dati mostrati non corrispondono all'email reale.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-91 — La risorsa non ha pagina di creazione né di modifica manuale

**Obiettivo**
Verificare che il registro email sia strettamente di sola visualizzazione: nessuna pagina di creazione o modifica manuale del contenuto di un'email è raggiungibile, nemmeno da Admin.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-321, AC "Nuova Filament Resource EmailMessageResource (sola visualizzazione, nessuna creazione/modifica manuale del contenuto)".
- Test automatico: `tests/Feature/Filament/Mail/EmailMessageResourceTest.php` — `the email messages resource has no create or edit page`.
- File/componente applicativo rilevante: `App\Filament\Resources\EmailMessages\EmailMessageResource::getPages()` (solo `index`/`view`).
- Test correlato: F3-87.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso al registro email come Admin.

**Dati di test**
Nessuno specifico.

**Stato iniziale**
Nessuno.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin e apri "Registro" | — | Elenco visibile, nessun pulsante "Nuovo"/"Crea" in testa alla tabella |
| 2 | Prova a navigare direttamente all'URL di creazione | `/admin/email-messages/create` | Pagina non trovata / route inesistente |
| 3 | Apri il dettaglio di un'email e verifica l'assenza di un pulsante "Modifica" | — | Nessuna azione di modifica del contenuto disponibile |

**Risultato finale atteso**
Nessuna via di creazione/modifica manuale del contenuto di un'email è disponibile.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot dell'elenco senza pulsante "Nuovo".
- Screenshot dell'errore di route inesistente al passo 2.

**Criterio di superamento**

PASS: nessuna pagina di creazione/modifica è raggiungibile.
FAIL: è possibile creare o modificare manualmente un'email.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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
## Amministrazione email — Azioni e quarantena (US-322)

### F3-92 — Un admin può riprocessare un messaggio tramite l'azione dedicata

**Obiettivo**
Verificare che l'azione "Riprocessa" sulla vista di dettaglio rilanci la pipeline da `classified` in poi su un messaggio che non è stato applicato correttamente (es. per un errore transitorio), portandolo a `applied` con un ticket collegato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-322, AC "riprocessa (rilancia la pipeline da classified in poi)".
- Test automatico: `tests/Feature/Filament/Mail/EmailMessageResourceTest.php` — `an admin can reprocess a message via the action`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ReprocessInboundEmailMessage`.
- Test correlato: F3-97.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore (per predisporre il messaggio in stato Failed, non riproducibile organicamente in sessione), Admin (per l'azione)

**Prerequisiti**
- Un `email_messages` in stato `Failed` con un mittente identificabile (`from_email` corrispondente a un utente esistente) — normalmente prodotto da un errore reale di parsing/rete; per il collaudo un developer lo predispone via `tinker` forzando lo stato di un messaggio di prova.

**Dati di test**
- Email di test in stato `Failed`, `from_email` = un cliente reale noto.

**Stato iniziale**
Messaggio in stato "Non riuscito" (Failed).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da un developer, predisponi il messaggio di test in stato Failed | `tinker` | Il messaggio compare nel registro come "Non riuscito" |
| 2 | Accedi come Admin e apri il dettaglio del messaggio | — | Vista di dettaglio caricata, azione "Riprocessa" visibile |
| 3 | Esegui l'azione "Riprocessa" | — | Nessun errore mostrato |
| 4 | Verifica lo stato del messaggio dopo l'azione | — | Stato "Applicata", con un ticket collegato |

**Risultato finale atteso**
Il messaggio viene riprocessato con successo e collegato a un ticket.

**Controlli negativi**
Un utente con solo `email.view` non deve vedere l'azione "Riprocessa" (vedi anche nota permessi generale).

**Evidenze da acquisire**
- Screenshot prima e dopo l'azione "Riprocessa".

**Criterio di superamento**

PASS: il messaggio passa a "Applicata" con un ticket collegato.
FAIL: l'azione fallisce o lo stato non cambia.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello, o nessun accesso al container per il passo 1.
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

### F3-93 — Un admin può assegnare un mittente a un messaggio in quarantena tramite l'azione dedicata

**Obiettivo**
Verificare che, dalla vista di dettaglio di un messaggio in quarantena, un admin possa assegnare manualmente un utente esistente come mittente, riprocessando il messaggio e generando il ticket.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-322, AC "assegna a utente (per un mittente sconosciuto: crea/aggiorna requester_id)".
- Test automatico: `tests/Feature/Filament/Mail/EmailMessageResourceTest.php` — `an admin can assign a sender to a quarantined message via the action`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\AssignEmailMessageSender` (usa `ApplyInboundEmail::runForResolvedSender()`, mai `run()`, per non far ri-derivare il mittente da `from_email`).
- Test correlato: F3-57, F3-95.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Admin

**Prerequisiti**
- Un messaggio in quarantena (es. quello prodotto da F3-57, mittente `estraneo@example.com`/simile).
- Un utente esistente a cui assegnarlo (diverso da `from_email` del messaggio: è proprio per questo che era finito in quarantena).

**Dati di test**
- Messaggio in quarantena da F3-57.
- Utente destinatario dell'assegnazione: un cliente reale esistente.

**Stato iniziale**
Messaggio in stato "Quarantena".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin e apri il dettaglio del messaggio in quarantena | — | Azione "Assegna a utente" visibile |
| 2 | Esegui l'azione scegliendo un utente esistente | Utente cliente reale | Nessun errore mostrato |
| 3 | Verifica lo stato del messaggio | — | Stato "Applicata", `user_id` valorizzato con l'utente scelto, ticket collegato creato |

**Risultato finale atteso**
Il messaggio viene applicato all'utente scelto, con ticket generato.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot dell'azione "Assegna a utente" e del risultato.

**Criterio di superamento**

PASS: il messaggio passa ad "Applicata" con l'utente e il ticket corretti.
FAIL: l'azione fallisce, o il messaggio torna in quarantena (bug noto se si chiamasse `run()` invece di `runForResolvedSender()`).
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-94 — Un admin può collegare un messaggio a un altro ticket tramite l'azione dedicata

**Obiettivo**
Verificare che un admin possa spostare manualmente un messaggio email già classificato (o già applicato) su un ticket diverso da quello risolto automaticamente dal thread, tramite l'azione "Collega a ticket".

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-322, AC "collega a ticket (override manuale del thread risolto da US-306)".
- Test automatico: `tests/Feature/Filament/Mail/EmailMessageResourceTest.php` — `an admin can link a message to a different ticket via the action`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\LinkInboundEmailToTicket`, `ticket_messages.email_message_id`.
- Test correlato: F3-97.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Un messaggio email con mittente risolto (`user_id` valorizzato) e un ticket di destinazione diverso da quello collegato, entrambi appartenenti allo stesso richiedente.

**Dati di test**
- Messaggio email di test, `user_id` valorizzato.
- Ticket di destinazione: un secondo ticket dello stesso richiedente.

**Stato iniziale**
Messaggio collegato (o collegabile) a un ticket diverso da quello di destinazione.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin e apri il dettaglio del messaggio | — | Azione "Collega a ticket" visibile |
| 2 | Esegui l'azione scegliendo il ticket di destinazione | Ticket di destinazione | Nessun errore mostrato |
| 3 | Verifica il ticket collegato al messaggio | — | Il messaggio risulta collegato al nuovo ticket, stato "Applicata" |

**Risultato finale atteso**
Il messaggio viene spostato sul ticket scelto manualmente.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot dell'azione "Collega a ticket" e del risultato.

**Criterio di superamento**

PASS: il messaggio risulta collegato al nuovo ticket.
FAIL: l'azione fallisce o il messaggio resta collegato al ticket originale.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-95 — La pagina Quarantena può associare un utente esistente e riprocessare il messaggio

**Obiettivo**
Verificare che, dalla pagina dedicata "Quarantena" (non dal dettaglio del singolo messaggio), un admin possa associare un utente esistente a un messaggio in quarantena e vederlo riprocessato automaticamente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-322/US-308, AC "pagina/tab dedicato Quarantena con le azioni specifiche di US-308: associa a utente esistente (poi riprocessa)".
- Test automatico: `tests/Feature/Filament/Mail/EmailMessageResourceTest.php` — `the quarantine page can associate an existing user and reprocess the message`.
- File/componente applicativo rilevante: `App\Filament\Pages\EmailQuarantine` (azione di tabella `assign_existing`).
- Test correlato: F3-93.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Admin

**Prerequisiti**
- Un messaggio in quarantena presente nella pagina dedicata (es. da un nuovo invio di prova da mittente sconosciuto).
- Un utente esistente a cui associarlo.

**Dati di test**
- Messaggio in quarantena, mittente `collaudo-f3-95@example.com`.

**Stato iniziale**
Messaggio in quarantena, visibile nella pagina "Quarantena".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin e apri "Quarantena" (gruppo "Email") | — | L'elenco mostra solo messaggi inbound in quarantena |
| 2 | Sulla riga del messaggio di test, esegui l'azione "Associa a utente esistente" | Utente esistente scelto | Nessun errore mostrato |
| 3 | Verifica lo stato del messaggio | — | Stato "Applicata", `user_id` valorizzato, il messaggio non compare più tra i quarantinati |

**Risultato finale atteso**
Il messaggio viene associato e riprocessato direttamente dalla pagina Quarantena.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della pagina Quarantena prima e dopo l'azione.

**Criterio di superamento**

PASS: il messaggio viene associato e riprocessato con successo.
FAIL: l'azione fallisce o il messaggio resta in quarantena.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-96 — La pagina Quarantena può creare un nuovo utente e riprocessare il messaggio

**Obiettivo**
Verificare che, per un mittente mai visto prima, un admin possa creare direttamente dalla pagina Quarantena un nuovo utente con ruolo Customer e vedere il messaggio riprocessato in un nuovo ticket.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-322/US-308, AC "crea nuovo utente e ticket (crea l'utente, poi riprocessa)".
- Test automatico: `tests/Feature/Filament/Mail/EmailMessageResourceTest.php` — `the quarantine page can create a new user and reprocess the message` (verifica anche che il nuovo utente abbia il ruolo Customer).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\CreateEmailSenderAndAssign`.
- Test correlato: F3-95.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Un messaggio in quarantena da un mittente reale mai registrato prima.

**Dati di test**
- Messaggio in quarantena, mittente `collaudo-f3-96@example.com`, nome `Cliente Collaudo F3-96`.

**Stato iniziale**
Messaggio in quarantena, nessun utente con quell'email.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin e apri "Quarantena" | — | Il messaggio di test è visibile |
| 2 | Esegui l'azione "Crea nuovo utente e ticket" | Nome `Cliente Collaudo F3-96`, email `collaudo-f3-96@example.com` | Nessun errore mostrato |
| 3 | Verifica il nuovo utente in `UserResource` | — | Utente creato con ruolo Customer |
| 4 | Verifica lo stato del messaggio | — | Stato "Applicata", ticket generato per il nuovo utente |

**Risultato finale atteso**
Un nuovo utente Customer viene creato e il messaggio applicato a un nuovo ticket.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot dell'azione, del nuovo utente creato e del ticket generato.

**Criterio di superamento**

PASS: nuovo utente Customer creato, messaggio applicato con ticket generato.
FAIL: l'azione fallisce, o l'utente non ha il ruolo corretto.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere l'utente e il ticket di prova creati, se non si vogliono mantenere nel dataset UAT.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F3-97 — Ogni azione amministrativa è tracciata (chi, quando) in email_message_logs

**Obiettivo**
Verificare che ciascuna azione amministrativa eseguita su un'email (riprocessa, assegna, collega, scarta, reinvia) scriva una riga di audit in `email_message_logs` con l'azione, l'utente che l'ha eseguita e il timestamp — stesso ruolo di `ticket_logs` ma per il dominio Mail.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-322, AC "ogni azione è tracciata (chi l'ha eseguita, quando)".
- Test automatico: `tests/Feature/Domain/Mail/Actions/EmailAdministrationActionsTest.php` — `riprocessa rilancia la pipeline da classified e traccia l'azione` (i test correlati nello stesso file confermano la tracciatura anche per assegna/crea-utente/collega/scarta/reinvia, ciascuno con l'`EmailMessageLogEvent` atteso).
- File/componente applicativo rilevante: `App\Domain\Mail\Models\EmailMessageLog`, enum `EmailMessageLogEvent`, sezione "Registro azioni" nell'infolist di dettaglio.
- Test correlato: F3-92, F3-93, F3-94.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Un messaggio su cui eseguire una delle azioni amministrative (es. riprocessa, come in F3-92).

**Dati di test**
- Stesso messaggio usato in F3-92, F3-93 o F3-94.

**Stato iniziale**
Nessuna riga in "Registro azioni" per il messaggio scelto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Esegui una delle azioni amministrative (es. "Riprocessa") sul messaggio di test | — | Azione completata senza errori |
| 2 | Apri il dettaglio del messaggio e consulta la sezione "Registro azioni" | — | È presente una riga con l'azione eseguita, l'utente Admin e l'orario |
| 3 | (Verifica tecnica facoltativa) Interroga `email_message_logs` per il messaggio | Query sull'`email_message_id` | La riga esiste con `action` coerente e `user_id` dell'Admin che ha eseguito l'azione |

**Risultato finale atteso**
Ogni azione amministrativa lascia una traccia visibile e verificabile.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della sezione "Registro azioni" con la riga tracciata.

**Criterio di superamento**

PASS: la riga di audit è presente con azione e autore corretti.
FAIL: la riga manca o riporta un autore/azione errati.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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
## Amministrazione email — Soppressioni e metriche (US-323)

### F3-98 — L'elenco soppressioni è filtrabile per motivo

**Obiettivo**
Verificare che la pagina "Soppressioni" permetta di filtrare l'elenco per motivo (`hard_bounce`, `loop_protection`, ecc.).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-323, AC "elenco email_suppressions filtrabile per motivo".
- Test automatico: `tests/Feature/Filament/Mail/EmailSuppressionsTest.php` — `the reason filter narrows the suppressions list`.
- File/componente applicativo rilevante: `App\Filament\Pages\EmailSuppressions`.
- Test correlato: F3-81, F3-99.

**Modalità di esecuzione**
MISTO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore (per creare righe di prova con motivi diversi), Admin (per il filtro)

**Prerequisiti**
- Almeno due righe `email_suppressions` con motivi diversi (`hard_bounce`, `loop_protection`), create via `tinker` — un vero bounce non è generabile a comando in sessione di collaudo, ma una riga di loop_protection può derivare organicamente da F3-111.

**Dati di test**
- Riga 1: `hard@collaudo-f3-98.test`, motivo `hard_bounce`.
- Riga 2: `loop@collaudo-f3-98.test`, motivo `loop_protection`.

**Stato iniziale**
Entrambe le righe presenti.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin e apri "Soppressioni" | — | Entrambe le righe visibili |
| 2 | Applica il filtro "Motivo" = Hard bounce | — | Solo la riga con quel motivo è visibile |

**Risultato finale atteso**
Il filtro per motivo funziona correttamente.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della tabella filtrata.

**Criterio di superamento**

PASS: il filtro mostra esclusivamente le righe con il motivo selezionato.
FAIL: il filtro non funziona correttamente.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello, o nessun accesso al container per il passo 1.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere le righe di prova create per il test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F3-99 — Un admin con email.manage può rimuovere una soppressione, riabilitando l'invio

**Obiettivo**
Verificare che un admin con `email.manage` possa rimuovere una riga di soppressione dalla pagina "Soppressioni", eliminandola definitivamente e riabilitando l'invio verso quell'indirizzo (stesso scenario di F3-81, qui verificato dal punto di vista del permesso specifico).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-323, AC "azione di rimozione (elimina la riga, riabilita l'invio)".
- Test automatico: `tests/Feature/Filament/Mail/EmailSuppressionsTest.php` — `a user with email.manage can remove a suppression, re-enabling delivery` (il test correlato `a user with only email.view does not see the remove action` conferma il gating separato dell'azione rispetto al solo accesso alla pagina).
- File/componente applicativo rilevante: `App\Filament\Pages\EmailSuppressions` (azione `remove`).
- Test correlato: F3-81, F3-98.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore (per creare la riga di prova), Admin (per l'azione)

**Prerequisiti**
- Una riga `email_suppressions` di prova, creata via `tinker`.

**Dati di test**
- Indirizzo soppresso: `collaudo-f3-99@example.com`.

**Stato iniziale**
Riga presente nell'elenco.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin e apri "Soppressioni" | — | Riga di test visibile, azione "Rimuovi" disponibile |
| 2 | Esegui l'azione "Rimuovi" | — | Nessun errore mostrato |
| 3 | Verifica che la riga sia scomparsa | — | La riga non è più presente nell'elenco |

**Risultato finale atteso**
La soppressione viene rimossa definitivamente.

**Controlli negativi**
Un utente con solo `email.view` non deve vedere l'azione "Rimuovi".

**Evidenze da acquisire**
- Screenshot prima e dopo la rimozione.

**Criterio di superamento**

PASS: la riga viene rimossa con successo.
FAIL: l'azione fallisce o la riga resta presente.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello, o nessun accesso al container per il passo 1.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno (la riga è già stata rimossa dal test).

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F3-100 — Le metriche contano elaborati/scartati/falliti nelle ultime 24h

**Obiettivo**
Verificare che il widget di metriche in testa alla pagina "Soppressioni" mostri conteggi coerenti di messaggi elaborati, scartati e falliti nelle ultime 24 ore, aggiornandosi con l'attività reale della pipeline.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-323, AC "widget/pagina con metriche essenziali: messaggi elaborati/scartati/falliti nelle ultime 24h, tempo medio di elaborazione, bounce rate".
- Test automatico: `tests/Unit/Domain/Mail/Support/EmailPipelineMetricsTest.php` — `counts processed, discarded and failed messages updated in the last 24h` (il test correlato conferma che un messaggio più vecchio di 24h non viene conteggiato).
- File/componente applicativo rilevante: `App\Domain\Mail\Support\EmailPipelineMetrics::snapshot()`, `App\Filament\Widgets\EmailPipelineMetricsOverview`.
- Test correlato: F3-101.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Admin

**Prerequisiti**
- Alcune email transitate nella pipeline durante la sessione di collaudo odierna (es. dai test E3/E9/checkpoint precedenti).

**Dati di test**
Nessuno specifico: il widget riflette lo stato reale del sistema nelle ultime 24h.

**Stato iniziale**
Nessuno.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin e apri "Soppressioni" | — | Il widget di metriche è visibile in testa alla pagina |
| 2 | Confronta i tre contatori (elaborati/scartati/falliti) con l'attività registrata nel Registro filtrato per data odierna | — | I numeri sono coerenti (stesso ordine di grandezza) con le email realmente transitate nelle ultime 24h |

**Risultato finale atteso**
Il widget mostra conteggi coerenti con l'attività reale recente.

**Controlli negativi**
Nessuno applicabile: nessun target numerico esatto è imposto dal PRD per questa fase.

**Evidenze da acquisire**
- Screenshot del widget di metriche.

**Criterio di superamento**

PASS: i tre contatori sono coerenti con l'attività osservabile nel Registro.
FAIL: i contatori sono palesemente incoerenti (es. sempre zero nonostante attività recente).
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-101 — Il bounce rate è calcolato su invii tentati (bounced + queued), mai su sent

**Obiettivo**
Verificare che il bounce rate mostrato nel widget metriche sia calcolato come `bounced / (bounced + queued)` — proxy di "tentativi di invio" — mai su `sent` (stato mai popolato in questa fase, nessun listener transita un outbound da `queued` a `sent`).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-323, §7.5.5.
- Test automatico: `tests/Unit/Domain/Mail/Support/EmailPipelineMetricsTest.php` — `computes bounce rate over attempted outbound sends (bounced + queued), never sent` (il test correlato conferma che il bounce rate è `null` quando nessun invio outbound è mai stato tentato).
- File/componente applicativo rilevante: `App\Domain\Mail\Support\EmailPipelineMetrics::bounceRate()`.
- Test correlato: F3-100.

**Modalità di esecuzione**
MISTO

**Priorità**
Media

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso alla pagina "Soppressioni".

**Dati di test**
Nessuno specifico: si osserva il valore reale del sistema.

**Stato iniziale**
Nessuno.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin e apri "Soppressioni" | — | Il widget mostra un valore di bounce rate (o "n/d" se nessun invio è mai stato tentato) |
| 2 | (Verifica tecnica facoltativa) Esegui il test automatico che dimostra la formula esatta | `vendor/bin/pest --filter "computes bounce rate over attempted outbound sends"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il valore mostrato è coerente con la formula documentata, verificabile in modo esatto dal test automatico.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot del widget con il valore di bounce rate.
- Output del comando Pest del passo 2.

**Criterio di superamento**

PASS: il valore mostrato è coerente con la formula, confermata dal test automatico.
FAIL: il valore è palesemente incoerente, o il test automatico fallisce.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello, o l'ambiente CI/locale non è disponibile per il passo 2.
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

## Voce di menu Email con Mailpit come prima sotto-voce (US-324)

### F3-102 — La voce Mailpit è visibile in locale con l'URL configurato

**Obiettivo**
Verificare che, in un ambiente con `APP_ENV` tra `local`/`staging` e `MAILPIT_URL` configurato, la voce "Mailpit" compaia come prima sotto-voce del gruppo "Email" nel menu del pannello.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-324, AC "la voce Mailpit è visibile solo quando app()->environment(['local', 'staging']) e config('mail_pipeline.mailpit_url') non è vuoto".
- Test automatico: `tests/Feature/Filament/Mail/MailpitNavigationItemTest.php` — `the Mailpit item is visible in local with the URL configured` (il test correlato `the Mailpit item is visible in staging with the URL configured` copre esattamente il caso reale di UAT, il cui `APP_ENV` è `staging`).
- File/componente applicativo rilevante: `App\Filament\Navigation\MailpitNavigationItem`.
- Test correlato: F3-103, F3-104.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Ambiente UAT con `APP_ENV=staging` e `MAILPIT_URL=https://mailpit-ticket-uat.montagnaservizi.com` già configurati (vedi `.env.uat.example`).

**Dati di test**
Nessuno specifico.

**Stato iniziale**
Nessuno.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin al pannello UAT | — | Login riuscito |
| 2 | Osserva il gruppo di navigazione "Email" nel menu laterale | — | La voce "Mailpit" è presente come prima sotto-voce |
| 3 | Clicca su "Mailpit" | — | Si apre `https://mailpit-ticket-uat.montagnaservizi.com` in una nuova scheda del browser |

**Risultato finale atteso**
La voce "Mailpit" è visibile e funzionante in UAT.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot del menu con la voce "Mailpit" visibile.
- Screenshot della nuova scheda aperta su Mailpit.

**Criterio di superamento**

PASS: la voce è visibile e apre Mailpit in una nuova scheda.
FAIL: la voce è assente o non apre l'URL corretto.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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

### F3-103 — La voce Mailpit è nascosta in produzione anche con l'URL configurato

**Obiettivo**
Verificare che, in un ambiente con `APP_ENV=production`, la voce "Mailpit" non compaia mai nel menu, anche se `MAILPIT_URL` risultasse configurato.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-324, AC "in un ambiente con APP_ENV=production..., la sotto-voce Mailpit specificamente non compare".
- Test automatico: `tests/Feature/Filament/Mail/MailpitNavigationItemTest.php` — `the Mailpit item is hidden in production even with the URL configured`.
- File/componente applicativo rilevante: `App\Filament\Navigation\MailpitNavigationItem::isVisible()`.
- Test correlato: F3-102.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante. L'ambiente UAT disponibile ha `APP_ENV=staging` fisso: non è possibile commutarlo a `production` per una verifica interattiva senza un secondo ambiente dedicato, quindi questo caso specifico è verificato dal solo test automatico.

**Dati di test**
`app()->instance('env', 'production')`, `mail_pipeline.mailpit_url` configurato a un valore non vuoto.

**Stato iniziale**
Non applicabile: dati costruiti dal test stesso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the Mailpit item is hidden in production even with the URL configured"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa.

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

### F3-104 — La voce Mailpit è la prima voce di navigazione nel gruppo Email

**Obiettivo**
Verificare che, all'interno del gruppo di navigazione "Email", la voce "Mailpit" compaia sempre come prima, prima di "Registro", "Quarantena" e "Soppressioni".

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-324, AC "raggruppa: la voce Mailpit (prima sotto-voce) seguita da Registro, Quarantena, Soppressioni".
- Test automatico: `tests/Feature/Filament/Mail/MailpitNavigationItemTest.php` — `the Mailpit item is registered in the Email group as the first navigation item`.
- File/componente applicativo rilevante: `App\Filament\Providers\AdminPanelProvider` (`->navigationItems()`, registrato prima delle Resource/Page nel costruttore di `NavigationManager`).
- Test correlato: F3-102.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso al pannello UAT.

**Dati di test**
Nessuno specifico.

**Stato iniziale**
Nessuno.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin al pannello UAT | — | Login riuscito |
| 2 | Espandi il gruppo di navigazione "Email" nel menu laterale | — | L'ordine delle voci è: "Mailpit", poi "Registro", "Quarantena", "Soppressioni" |

**Risultato finale atteso**
"Mailpit" è sempre la prima voce del gruppo.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot del gruppo "Email" espanso con l'ordine delle voci visibile.

**Criterio di superamento**

PASS: "Mailpit" è la prima voce del gruppo.
FAIL: "Mailpit" non è la prima voce, o è assente.
BLOCKED: bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
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
## Comando mail:retry-failed (US-325)

### F3-105 — Il comando riaccoda tutti i messaggi outbound falliti

**Obiettivo**
Verificare che `mail:retry-failed`, eseguito senza opzioni, riaccodi tutti i messaggi outbound in stato `Failed` (per i quali il Mailable è ricostruibile), rispettando comunque soppressioni e preferenze come un invio normale.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-325, §7.3.3.
- Test automatico: `tests/Feature/Console/MailRetryFailedCommandTest.php` — `riaccoda tutti i messaggi outbound falliti` (il test correlato `registra User::system() come attore nel log` conferma che l'attore usato per l'audit trail è l'utente di sistema, non un utente autenticato, essendo un comando CLI/scheduler).
- File/componente applicativo rilevante: `App\Console\Commands\MailRetryFailedCommand`, `App\Domain\Mail\Actions\RetryOutboundEmailMessage`.
- Test correlato: F3-92 (riuso dello stesso principio di riprocessamento), F3-106, F3-107.

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso al container applicativo UAT.
- Un `email_messages` outbound in stato `Failed`, con Mailable nella whitelist `RESENDABLE_MAILABLES` (es. `NewCustomerTicketStaffMail`, `TicketOpenedFromWebMail`, `TicketReceivedByEmailMail`, `TicketWaitingReminderMail`) — predisposto da un developer via `tinker`, un fallimento SMTP reale non essendo riproducibile a comando.

**Dati di test**
- Email outbound di test, `mailable_class = NewCustomerTicketStaffMail`, `status = Failed`.

**Stato iniziale**
Messaggio in stato "Non riuscito".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Predisponi il messaggio di test in stato Failed | `tinker` | Messaggio presente nel registro come "Non riuscito" |
| 2 | Esegui il comando dal container | `docker compose exec app php artisan mail:retry-failed` | Il comando termina con exit code 0 |
| 3 | Verifica lo stato del messaggio nel Registro | — | Stato "In coda" (Queued) |
| 4 | Apri Mailpit UAT | — | L'email viene effettivamente recapitata su Mailpit |

**Risultato finale atteso**
Il messaggio fallito viene riaccodato e recapitato con successo.

**Controlli negativi**
Nessuno applicabile (vedi F3-106 per il caso soppresso).

**Evidenze da acquisire**
- Output del comando.
- Screenshot dell'email in Mailpit.

**Criterio di superamento**

PASS: il messaggio viene riaccodato e recapitato.
FAIL: il messaggio resta in stato Failed dopo l'esecuzione.
BLOCKED: nessun accesso al container UAT.
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

### F3-106 — Un destinatario finito in soppressione blocca il reinvio ma il comando prosegue con gli altri

**Obiettivo**
Verificare che, se il destinatario di un messaggio outbound fallito è nel frattempo finito in `email_suppressions`, il comando `mail:retry-failed` non lo reinvii (lo segnala e passa oltre), continuando comunque a processare gli altri messaggi idonei senza fermarsi.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-325, AC "un messaggio il cui destinatario è nel frattempo finito in soppressione non viene reinviato: il comando lo segnala e passa al successivo, non si ferma".
- Test automatico: `tests/Feature/Console/MailRetryFailedCommandTest.php` — `un destinatario in soppressione blocca il reinvio ma il comando prosegue con gli altri`.
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\RetryOutboundEmailMessage` (log `EmailMessageLogEvent::ResendBlocked`).
- Test correlato: F3-105.

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso al container applicativo UAT.
- Due messaggi outbound in stato `Failed`: uno verso un indirizzo soppresso (riga `email_suppressions` esistente), uno verso un indirizzo normale.

**Dati di test**
- Messaggio A: destinatario soppresso (`reason = hard_bounce`).
- Messaggio B: destinatario normale.

**Stato iniziale**
Entrambi i messaggi in stato "Non riuscito".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Predisponi i due messaggi di test | `tinker` | Entrambi presenti nel registro come "Non riuscito" |
| 2 | Esegui il comando dal container | `docker compose exec app php artisan mail:retry-failed` | Il comando termina con exit code 0 |
| 3 | Verifica lo stato dei due messaggi | — | Messaggio A resta "Non riuscito"; Messaggio B passa a "In coda" |

**Risultato finale atteso**
Il messaggio verso l'indirizzo soppresso non viene reinviato, l'altro sì; il comando non si interrompe.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output del comando.
- Screenshot degli stati finali dei due messaggi nel registro.

**Criterio di superamento**

PASS: il messaggio soppresso resta Failed, l'altro viene riaccodato.
FAIL: il messaggio soppresso viene comunque inviato, o il comando si interrompe senza processare l'altro.
BLOCKED: nessun accesso al container UAT.
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

### F3-107 — --email-message reinvia solo il messaggio indicato

**Obiettivo**
Verificare che l'opzione `--email-message` limiti il reinvio al solo messaggio identificato dal suo ULID, lasciando invariati gli altri messaggi falliti presenti nel sistema.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-325, firma comando `mail:retry-failed {--limit=} {--email-message=}`.
- Test automatico: `tests/Feature/Console/MailRetryFailedCommandTest.php` — `--email-message reinvia solo il messaggio indicato` (il test correlato `--email-message con ulid inesistente fallisce esplicitamente` conferma l'exit code 1 per un ULID non valido).
- File/componente applicativo rilevante: `App\Console\Commands\MailRetryFailedCommand`.
- Test correlato: F3-105.

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Accesso al container applicativo UAT.
- Due messaggi outbound in stato `Failed`.

**Dati di test**
- Messaggio target: da reinviare esplicitamente tramite il suo ULID.
- Messaggio non-target: deve restare invariato.

**Stato iniziale**
Entrambi i messaggi in stato "Non riuscito".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Predisponi i due messaggi di test e annota l'ULID del messaggio target | `tinker` | Entrambi presenti come "Non riuscito" |
| 2 | Esegui il comando specificando solo il messaggio target | `docker compose exec app php artisan mail:retry-failed --email-message=<ulid>` | Il comando termina con exit code 0 |
| 3 | Verifica lo stato dei due messaggi | — | Il messaggio target passa a "In coda"; l'altro resta "Non riuscito" |

**Risultato finale atteso**
Solo il messaggio indicato viene reinviato.

**Controlli negativi**
Eseguire il comando con un ULID inesistente deve terminare con un errore esplicito (exit code diverso da 0), non un successo silenzioso.

**Evidenze da acquisire**
- Output del comando per entrambi i casi (ULID valido e inesistente).
- Screenshot degli stati finali dei due messaggi.

**Criterio di superamento**

PASS: solo il messaggio target viene reinviato; un ULID inesistente termina con errore esplicito.
FAIL: viene reinviato anche l'altro messaggio, o un ULID inesistente termina senza errore.
BLOCKED: nessun accesso al container UAT.
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
## Checkpoint di fine fase — verifica end-to-end su dati reali (US-326)

### F3-108 — Una risposta via VERP a una notifica accoda un messaggio sul ticket esistente invece di crearne uno nuovo

**Obiettivo**
Verificare, su un caso end-to-end realistico, che rispondere a una notifica ricevuta (il cui `Reply-To` è nella forma VERP `ticket+<ulid>@dominio`) aggiorni il ticket esistente con un nuovo messaggio, senza mai creare un secondo ticket duplicato — il criterio di accettazione esplicito più importante della Fase 3.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-326, criterio di accettazione esplicito "rispondere a un'email di notifica aggiorna il ticket esistente invece di crearne uno nuovo".
- Test automatico: `tests/Feature/Console/MailFetchInboundPipelineTest.php` — `una risposta via VERP a una notifica accoda un messaggio sul ticket esistente invece di crearne uno nuovo` (usa la fixture `tests/Fixtures/emails/checkpoint-risposta-notifica-verp.eml`, con `Reply-To`/`To` nella forma `ticket+<ulid del messaggio staff>@support.example.test`).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ResolveEmailThread` (livello 1, VERP), `App\Domain\Mail\Actions\ApplyInboundEmail`.
- Test correlato: F3-25, F3-32, F3-109.

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Customer (per la risposta email reale), Admin/Developer (per la verifica)

**Prerequisiti**
- Un ticket esistente con almeno una notifica email già inviata al richiedente (es. da F3-59 o F3-66), il cui `Reply-To` reale è visibile su Mailpit UAT (formato `ticket+<ulid>@<dominio reale di Montagna Servizi>`).
- Accesso a un client email reale del richiedente per inviare la risposta verso la casella di supporto UAT.

**Dati di test**
- Ticket esistente `COLL-F3-108-...`.
- Risposta con oggetto `Re: <oggetto originale>`, corpo "Confermo che il problema persiste, resto in attesa."

**Stato iniziale**
Ticket esistente con un messaggio, nessuna risposta ancora ricevuta.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Apri su Mailpit UAT la notifica precedentemente inviata al richiedente e annota l'indirizzo `Reply-To` (`ticket+<ulid>@...`) | — | Indirizzo VERP individuato |
| 2 | Dal client email reale del richiedente, rispondi a quella notifica (o invia una nuova email verso l'indirizzo VERP annotato, verso la casella di supporto UAT) | Corpo indicato sopra | Email inviata |
| 3 | Attendi il fetch (o fai eseguire manualmente `mail:fetch-inbound` a un developer) | — | Nessun nuovo ticket creato |
| 4 | Apri il ticket `COLL-F3-108-...` | — | Il nuovo messaggio compare nella conversazione dello stesso ticket |

**Risultato finale atteso**
Il ticket esistente riceve il nuovo messaggio, nessun duplicato viene creato.

**Controlli negativi**
Il numero totale di ticket nel sistema prima e dopo l'operazione deve restare invariato.

**Evidenze da acquisire**
- Screenshot del `Reply-To` individuato su Mailpit.
- Screenshot del ticket con il nuovo messaggio in coda.

**Criterio di superamento**

PASS: il messaggio si aggancia al ticket esistente, nessun duplicato creato.
FAIL: viene creato un nuovo ticket invece di aggiornare quello esistente.
BLOCKED: `mail:fetch-inbound` non raggiungibile/schedulato, o bug noto `->databaseNotifications()`.
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

### F3-109 — Una risposta su un ticket importato dal v1 risolve via token subject anche senza VERP disponibile

**Obiettivo**
Verificare che, per un ticket storico importato dal v1 (per il quale non esiste alcun VERP, essendo una conversazione pregressa mai passata dalla pipeline di questa fase), una risposta email si agganci comunque al ticket corretto tramite il token `[#<id>]` nel subject normalizzato — livello 3 della risoluzione del thread.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-326, criterio di accettazione esplicito "anche su un ticket importato dal v1".
- Test automatico: `tests/Feature/Console/MailFetchInboundPipelineTest.php` — `una risposta su un ticket importato dal v1 risolve via token subject anche senza VERP disponibile` (usa la fixture `tests/Fixtures/emails/checkpoint-risposta-ticket-v1-subject-token.eml`, subject `Re: [#<id>] Vecchio ticket migrato dal v1`).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ResolveEmailThread` (livello 3, token subject).
- Test correlato: F3-27, F3-108.

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Customer, Admin/Developer

**Prerequisiti**
- Un ticket reale importato dal v1 (con conversazione storica), individuato tra i dati reali di collaudo, di cui è noto l'id numerico.
- Accesso a un client email reale del richiedente di quel ticket storico.

**Dati di test**
- Ticket storico `#<id>` (id reale, da individuare tra i ticket importati con `ticket_messages.is_legacy_import = true`).
- Oggetto della risposta: `Re: [#<id>] <titolo originale del ticket>`.

**Stato iniziale**
Ticket storico esistente, nessun messaggio recente via email.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Individua un ticket storico reale e il suo id numerico | — | Id annotato |
| 2 | Dal client email reale del richiedente, invia una risposta alla casella di supporto UAT con oggetto contenente `[#<id>]` | Oggetto indicato sopra | Email inviata |
| 3 | Attendi il fetch (o fallo eseguire manualmente) | — | Nessun nuovo ticket creato |
| 4 | Apri il ticket storico `#<id>` | — | Il nuovo messaggio compare nella conversazione dello stesso ticket storico |

**Risultato finale atteso**
Il messaggio si aggancia correttamente al ticket storico tramite il solo token subject.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot dell'oggetto dell'email inviata.
- Screenshot del ticket storico con il nuovo messaggio.

**Criterio di superamento**

PASS: il messaggio si aggancia al ticket storico corretto.
FAIL: viene creato un nuovo ticket, o il messaggio si aggancia a un ticket sbagliato.
BLOCKED: `mail:fetch-inbound` non raggiungibile/schedulato, o bug noto `->databaseNotifications()`.
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

### F3-110 — Un hard bounce sospende permanentemente il destinatario originale, non crea ticket e non genera auto-reply

**Obiettivo**
Verificare, su uno scenario end-to-end realistico, che un DSN di hard bounce ricevuto dalla pipeline sospenda permanentemente il destinatario originale, aggiorni lo stato dell'email outbound originale a `bounced`, e non generi né un nuovo ticket né alcun auto-reply.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-326, criterio di accettazione esplicito "un DSN non genera auto-reply".
- Test automatico: `tests/Feature/Console/MailFetchInboundPipelineTest.php` — `un hard bounce sospende permanentemente il destinatario originale, non crea ticket e non genera auto-reply` (usa la fixture reale `tests/Fixtures/emails/checkpoint-dsn-hard-bounce.eml`, un DSN RFC 3464 con `Action: failed`/`Status: 5.1.1` verso `destinatario.rimbalzato@example.test`, correlato a un `email_messages` outbound con `Message-ID: checkpoint-outbound-hard-bounce@example.test`).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ProcessDeliveryStatusNotification`, wiring in `App\Console\Commands\MailFetchInboundCommand` (US-326: DSN scartati da `ClassifyInboundEmail` sono instradati a questa Action, mai al ticketing).
- Test correlato: F3-76, F3-77, F3-80.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante. Un vero DSN (bounce reale) non è generabile a comando in una sessione di collaudo UAT: richiede un vero rifiuto SMTP da un vero MTA esterno, non riproducibile su richiesta. La fixture `.eml` reale già presente nel repository (`tests/Fixtures/emails/checkpoint-dsn-hard-bounce.eml`) e il test end-to-end dedicato sono la verifica accettata per questo scenario, stesso principio già usato per gli analizzatori ETL di Fase 0.

**Dati di test**
Fixture `tests/Fixtures/emails/checkpoint-dsn-hard-bounce.eml`, iniettata nella pipeline tramite un `InboundMailTransport` fittizio (`FakeInboundMailTransport`).

**Stato iniziale**
Non applicabile: dati costruiti dal test stesso (un `email_messages` outbound preesistente con lo stesso `Message-ID` citato nel DSN).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "un hard bounce sospende permanentemente il destinatario originale, non crea ticket e non genera auto-reply"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il test Pest referenziato passa: sospensione permanente creata, email originale marcata `bounced`, nessun ticket creato, nessuna email accodata o inviata.

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

### F3-111 — Un mittente già in blacklist anti-loop viene scartato e riprocessare lo stesso messaggio non duplica nulla

**Obiettivo**
Verificare, su uno scenario end-to-end realistico, che un'email proveniente da un mittente già soppresso per `loop_protection` venga scartata (non produce ticket), e che rieseguire il fetch sullo stesso stato IMAP non produca un secondo record né alcuna duplicazione.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-326, criterio di accettazione esplicito "riprocessare lo stesso messaggio non duplica nulla".
- Test automatico: `tests/Feature/Console/MailFetchInboundPipelineTest.php` — `un mittente già in blacklist anti-loop viene scartato e riprocessare lo stesso messaggio non duplica nulla` (usa la fixture `tests/Fixtures/emails/checkpoint-mittente-in-blacklist-anti-loop.eml`, mittente `bounce@dominio-bloccato.test` già soppresso con `reason = loop_protection`).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ClassifyInboundEmail`, vincolo unique `(imap_folder, imap_uid)` su `email_messages`.
- Test correlato: F3-15, F3-20, F3-06.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore (per predisporre la soppressione, non ottenibile organicamente senza inviare decine di email ravvicinate), Admin/Developer (per la verifica)

**Prerequisiti**
- Un indirizzo email reale disponibile per il test, con una riga `email_suppressions` predisposta via `tinker` (`reason = loop_protection`, `expires_at` futuro) — riprodurre organicamente la soglia di rate limit (3/ora, 10/giorno) richiederebbe inviare molte email ravvicinate, non praticabile in una sessione di collaudo.
- Accesso a Mailpit UAT (per confermare l'assenza di qualunque risposta/auto-reply).

**Dati di test**
- Mittente di test già soppresso per `loop_protection`.
- Oggetto email: `COLL-F3-111 — Risposta automatica ripetuta`.

**Stato iniziale**
Riga `email_suppressions` con `reason = loop_protection` già presente per il mittente di test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Predisponi la soppressione `loop_protection` per l'indirizzo di test | `tinker` | Riga presente |
| 2 | Dall'indirizzo soppresso, invia un'email alla casella di supporto UAT | Oggetto indicato sopra | Email inviata |
| 3 | Attendi il fetch (o fallo eseguire manualmente) | — | Il messaggio compare nel Registro come "Scartato"; nessun ticket creato; nessuna email in Mailpit verso il mittente |
| 4 | Fai eseguire nuovamente `mail:fetch-inbound` (stesso stato IMAP, il messaggio è già stato scaricato) | — | Nessun secondo record creato per lo stesso messaggio |

**Risultato finale atteso**
Il messaggio viene scartato, senza ticket né auto-reply, e senza duplicazioni a una rilettura successiva.

**Controlli negativi**
Il conteggio di `email_messages` per quel mittente deve restare a 1 anche dopo il secondo fetch.

**Evidenze da acquisire**
- Screenshot del messaggio scartato nel Registro.
- Screenshot di Mailpit privo di auto-reply.

**Criterio di superamento**

PASS: il messaggio è scartato, nessun ticket/auto-reply, nessuna duplicazione a una rilettura.
FAIL: viene creato un ticket, o parte un auto-reply, o si crea un secondo record.
BLOCKED: `mail:fetch-inbound` non raggiungibile/schedulato, o bug noto `->databaseNotifications()`.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere la riga di soppressione di prova se non si vuole mantenerla nel dataset UAT.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F3-112 — Un mittente sconosciuto va in quarantena e resta ispezionabile in amministrazione (US-321) insieme a un messaggio scartato

**Obiettivo**
Verificare, su uno scenario end-to-end con più email elaborate nello stesso fetch, che un mittente mai visto prima vada in quarantena (mai scartato) e resti visibile e apribile in amministrazione, insieme a un secondo messaggio scartato per blacklist anti-loop — dimostrando che entrambi gli esiti convivono correttamente nello stesso Registro.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-326, criterio di accettazione esplicito "ogni email è ispezionabile dall'amministrazione (US-321)".
- Test automatico: `tests/Feature/Console/MailFetchInboundPipelineTest.php` — `un mittente sconosciuto va in quarantena, resta ispezionabile in amministrazione (US-321) insieme a un messaggio scartato` (usa le fixture `checkpoint-mittente-sconosciuto.eml` e `checkpoint-mittente-in-blacklist-anti-loop.eml` nello stesso fetch, poi verifica entrambi i record da `ListEmailMessages`/`ViewEmailMessage`).
- File/componente applicativo rilevante: `App\Domain\Mail\Actions\ResolveEmailSender`, `App\Filament\Resources\EmailMessages\EmailMessageResource`.
- Test correlato: F3-57, F3-87..91.

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Anonimo (per il mittente sconosciuto), Sviluppatore (per il mittente in blacklist), Admin (per la verifica in amministrazione)

**Prerequisiti**
- Un indirizzo email reale mai registrato prima nel sistema.
- Un secondo indirizzo con una soppressione `loop_protection` predisposta via `tinker` (vedi F3-111).
- Accesso Admin al pannello UAT.

**Dati di test**
- Email 1: da mittente mai visto, oggetto `COLL-F3-112a — Informazioni sui servizi`.
- Email 2: da mittente in blacklist, oggetto `COLL-F3-112b — Risposta automatica ripetuta`.

**Stato iniziale**
Nessun `email_messages` per i due mittenti di test.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Invia entrambe le email alla casella di supporto UAT (mittenti diversi) | Oggetti indicati sopra | Entrambe le email inviate |
| 2 | Attendi il fetch (o fallo eseguire manualmente) | — | Email 1 in stato "Quarantena"; Email 2 in stato "Scartato" |
| 3 | Accedi come Admin e apri "Registro" | — | Entrambi i messaggi sono visibili nell'elenco |
| 4 | Apri il dettaglio di ciascuno dei due messaggi | — | Entrambe le viste di dettaglio si aprono correttamente, senza errori |

**Risultato finale atteso**
Entrambi gli esiti (quarantena e scarto) sono ispezionabili correttamente dallo stesso Registro.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot del Registro con entrambi i messaggi.
- Screenshot del dettaglio di ciascuno dei due messaggi.

**Criterio di superamento**

PASS: entrambi i messaggi sono visibili e apribili in amministrazione con lo stato corretto.
FAIL: uno dei due messaggi manca, ha uno stato errato, o la sua vista di dettaglio genera un errore.
BLOCKED: `mail:fetch-inbound` non raggiungibile/schedulato, o bug noto `->databaseNotifications()` impedisce l'apertura del pannello.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere la soppressione di prova se non si vuole mantenerla nel dataset UAT.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F3-113 — La conferma di apertura ticket via email arriva nella lingua del richiedente (US-320) attraverso tutta la pipeline end-to-end

**Obiettivo**
Verificare, sull'intera pipeline end-to-end (fetch → parse → classify → apply → notifica), che un cliente con `locale = en` che apre un nuovo ticket via email riceva la conferma di ricezione (E1) in inglese, e non riceva mai un'inutile E9 (essendo un mittente perfettamente identificato).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 3 US-326, criterio di accettazione esplicito "le comunicazioni arrivano nella lingua dell'utente (US-320)".
- Test automatico: `tests/Feature/Console/MailFetchInboundPipelineTest.php` — `la conferma di apertura ticket via email arriva nella lingua del richiedente (US-320) attraverso tutta la pipeline` (verifica `TicketReceivedByEmailMail::$locale === 'en'` e l'assenza di `UnknownSenderStaffMail`).
- File/componente applicativo rilevante: `App\Domain\Mail\Support\RecipientLocale`, `App\Domain\Mail\Mailables\TicketReceivedByEmailMail` (E1).
- Test correlato: F3-82, F3-86.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Customer (o utente di test con locale inglese), Admin (per impostare la locale se necessario)

**Prerequisiti**
- Un utente cliente reale con `locale = en` (o un utente di test creato/aggiornato dall'Admin con quella locale).
- Accesso a Mailpit UAT.

**Dati di test**
- Mittente: cliente con `locale = en`.
- Oggetto email: `I cannot log in — COLL-F3-113`.
- Corpo: "Hello, I cannot log in to the portal anymore, could you please help?"

**Stato iniziale**
Nessun ticket precedente con questo oggetto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Verifica (o imposta tramite Admin) `locale = en` per l'utente cliente di test | — | Campo locale = `en` |
| 2 | Dall'indirizzo di quell'utente, invia l'email alla casella di supporto UAT | Oggetto e corpo indicati sopra | Email inviata |
| 3 | Attendi il fetch (o fallo eseguire manualmente) | — | Un nuovo ticket viene creato, collegato al richiedente corretto |
| 4 | Apri Mailpit UAT | — | È presente l'email di conferma (E1) in inglese; nessuna email E9 presente per questo mittente |

**Risultato finale atteso**
La conferma arriva in inglese, coerente con la locale del richiedente, attraverso l'intera pipeline.

**Controlli negativi**
Nessuna E9 deve essere generata: il mittente è identificato correttamente, non è un caso di quarantena.

**Evidenze da acquisire**
- Screenshot del ticket creato.
- Screenshot dell'email di conferma in inglese su Mailpit.

**Criterio di superamento**

PASS: la conferma arriva in inglese e nessuna E9 viene generata.
FAIL: la conferma arriva in italiano nonostante `locale = en`, o viene generata un'E9 inattesa.
BLOCKED: `mail:fetch-inbound` non raggiungibile/schedulato, o bug noto `->databaseNotifications()`.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Ripristinare la locale originale dell'utente se modificata per il test.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---
