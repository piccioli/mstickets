# PRD: Fase 3 — Sottosistema email

> Riferimento completo: `PRD-ORCHESTRATOR-V2.md` §7 (M8 — Sottosistema email, riscrittura completa) e §14
> (Roadmap, Fase 3). Numerazione story `US-3xx` per restare nel range riservato a questa fase (vedi nota in
> `tasks/prd-etl-real-data-seeding.md`, che ha usato `US-R0x` proprio per non collidere con questa numerazione).

## 1. Introduzione/Overview

Il v1 ha un sottosistema email con 20 problemi noti e catalogati (§7.1), il più grave dei quali è l'assenza di
threading e idempotenza: ogni risposta cliente apre un ticket duplicato, e un fallimento SMTP può generare
ticket duplicati all'infinito. Non esiste nessuna tabella email nel DB, nessun test sull'inbound, e almeno tre
rotte/bug espongono dati o generano spam involontario.

Questa fase **riscrive interamente** il sottosistema (nessun codice v1 riusato) secondo l'architettura definita
in §7.2-§7.7: ogni email (in ingresso e in uscita) è persistita nel DB come sorgente di verità, il threading è
esplicito e multi-livello, ogni invio è asincrono e tracciato, e un'amministrazione dedicata rende il sistema
governabile invece che una scatola nera.

**Buona notizia per questa fase**: lo schema DB completo (`email_messages`, `email_threads`,
`email_attachments`, `email_suppressions`, `notification_preferences`, i 5 model in `App\Domain\Mail\Models`,
i relativi enum ed le Policy deny-by-default) è **già stato creato in Fase 0** (US-016) e già popolato/collegato
dall'ETL (Fase 2: `DeriveStage` crea `email_threads` per ogni ticket con conversazione importata). Questa fase
**non introduce nuove tabelle**: costruisce la pipeline (fetch → parse → classify → apply), i Mailable in
uscita, l'amministrazione, e collega tutto allo schema esistente.

## 2. Goals

- Nessuna email produce due volte lo stesso effetto (idempotenza reale, chiavi `(imap_folder, imap_uid)` /
  `message_id` / `content_hash`, già uniche a livello DB da Fase 0).
- Rispondere a una notifica aggiorna il ticket esistente (anche se importato dal v1), mai un duplicato.
- Ogni invio è in coda, mai una connessione SMTP dentro una request HTTP.
- Lo staff viene avvisato via email di ogni nuovo ticket cliente (corregge il problema 10 del v1).
- Nessun mail loop: controlli anti-loop obbligatori prima di qualunque auto-reply, con rate limit.
- Ogni comunicazione è nella lingua del destinatario, zero stringhe hard-coded.
- L'intero sottosistema è governabile da UI: un admin può vedere, riprocessare, collegare, scartare o reinviare
  qualunque messaggio senza toccare un log o una query SQL.
- Un admin ha accesso diretto a Mailpit dal pannello quando è in locale o in UAT, senza dover ricordare un URL.

## 3. User Stories

### US-301: Configurazione IMAP e interfaccia `InboundMailTransport`
**Description:** Come sviluppatore, ho bisogno di un punto di accesso alla casella email configurabile e
sostituibile, per poter aggiungere in futuro un provider a webhook senza toccare la pipeline (§7.4).

**Acceptance Criteria:**
- [ ] Interfaccia `App\Domain\Mail\Contracts\InboundMailTransport` con un metodo che restituisce i messaggi
      grezzi (`.eml` + metadati IMAP: folder, uid) di una query con `limit` **obbligatorio** (default 50 da
      config) e `since` opzionale, e un metodo per spostare un messaggio tra cartelle (`INBOX` →
      `Processed`/`Errors`/`Quarantine`).
- [ ] Implementazione `WebklexImapTransport` con `webklex/php-imap` (libreria standalone, non il wrapper
      Laravel), configurata **interamente da env** (host/porta/crittografia/utente/password/cartelle), tutte
      le variabili documentate in `.env.example` (nuova sezione `IMAP_*`) — nessuna oggi esiste (problema 20).
- [ ] Nuovo file `config/mail_pipeline.php` (distinto da `config/mail.php`, che resta la configurazione
      Laravel nativa di invio): account IMAP, cartelle, `limit`/`since` di default, soglie di rate limit
      anti-loop (§7.3.4), gruppo di notifica staff (`MAIL_STAFF_NOTIFICATION_GROUP`), indirizzo di supporto
      per gli auto-reply.
- [ ] Nessuna chiamata `env()` fuori da `config/mail_pipeline.php` (§13.3 del PRD principale, stessa regola
      già applicata a `config/orchestrator.php`).
- [ ] Typecheck/lint/test passano.

### US-302: Comando `mail:fetch-inbound` — fetch e archiviazione grezza
**Description:** Come sistema, devo scaricare le nuove email dalla casella e archiviarle prima di qualunque
elaborazione, per avere sempre una rete di sicurezza per riprocessare senza dipendere da IMAP (§7.3.3).

**Acceptance Criteria:**
- [ ] Comando `mail:fetch-inbound` che usa `InboundMailTransport` per leggere fino a `limit` messaggi da
      `INBOX` (mai tutti gli unseen, mai `fetch_body = true` indiscriminato: rischio OOM già noto del v1).
- [ ] Ogni messaggio grezzo è salvato come `.eml` su uno storage disco dedicato **prima** di qualunque parsing,
      e un record `email_messages` è creato con `direction = inbound`, `status = received`, `imap_folder`,
      `imap_uid`, `raw_path`.
- [ ] Un messaggio il cui `(imap_folder, imap_uid)` esiste già in `email_messages` viene **saltato** (vincolo
      unique già presente da Fase 0): rieseguire il comando sullo stesso stato IMAP non crea duplicati.
- [ ] `WithoutOverlapping` sul job/comando, `$timeout` esplicito, `$tries` con backoff, `finally` per il
      disconnect IMAP anche in caso di eccezione.
- [ ] Scheduling: il comando è registrato in `routes/console.php`/`bootstrap/app.php` (Laravel 13, no
      `app/Console/Kernel.php`) con una cadenza configurabile.
- [ ] Test con fixture `.eml` reali in `tests/Fixtures/emails/` (il v1 ha zero test qui, §R10) e con la
      connessione IMAP mockata dietro l'interfaccia (mai un vero server IMAP nei test).
- [ ] Typecheck/lint/test passano.

### US-303: Parsing del messaggio (subject, corpo, charset)
**Description:** Come sistema, devo estrarre in modo affidabile subject e corpo da un'email grezza, senza
regex distruttive né perdita di contenuto (§7.3.5).

**Acceptance Criteria:**
- [ ] `App\Domain\Mail\Parsers\SubjectNormalizer`: rimuove prefissi `Re:`/`RE:`/`R:`/`Fw:`/`Fwd:`/`AW:`/`I:`/
      `Rif:` anche ripetuti/in cascata, e restituisce separatamente l'eventuale token `[#<id>]` se presente.
- [ ] `App\Domain\Mail\Parsers\EmailBodyParser`: preferisce `text/plain`; se assente, converte l'HTML in testo
      (mai il contrario). Nessuna regex tipo `preg_replace('/---.*?---/s', ...)` (problema 8 del v1): ogni
      trasformazione è testata con fixture reali, non un pattern generico non verificato.
- [ ] `App\Domain\Mail\Parsers\QuotedTextRemover`: rimuove testo citato e firme riconoscendo `On ... wrote:`,
      `Il ... ha scritto:`, righe `>`, `-----Original Message-----`, separatore `--` di firma, blocchi Outlook
      `From:`/`Da:`. Il testo rimosso NON è perso: resta nel `.eml` archiviato da US-302, la rimozione riguarda
      solo `body_text`/`body_html` salvati sul `ticket_message`.
- [ ] `body_html` è sempre sanitizzato con lo stesso principio allowlist già usato per i messaggi web
      (`App\Domain\Ticketing\Support\TicketMessageSanitizer`, US-106): riusare quella classe o un allowlist
      equivalente, mai un output non sanitizzato salvato o mostrato (problema 9, XSS stored).
- [ ] Charset gestito esplicitamente con fallback documentato; un fallimento di decoding è loggato, mai
      un'eccezione non gestita che fa fallire l'intero fetch.
- [ ] Al termine del parsing, `email_messages.status` passa a `parsed` (subject normalizzato e corpo scritti
      sul record).
- [ ] Typecheck/lint/test passano.

### US-304: Classificazione anti-loop e scarti obbligatori
**Description:** Come sistema, devo scartare prima di ogni azione i messaggi che non devono generare ticket né
risposta, per non produrre mail loop, spam confermato, o duplicati (§7.3.4, problema 7).

**Acceptance Criteria:**
- [ ] `App\Domain\Mail\Actions\ClassifyInboundEmail` scarta (status `discarded`, motivo registrato in un campo
      dedicato, **nessun invio**) i messaggi con: header `Auto-Submitted` diverso da `no`; `Precedence: bulk`/
      `list`/`junk`; presenza di `List-Id`/`List-Unsubscribe`; `X-Auto-Response-Suppress`; mittente
      `MAILER-DAEMON`/`postmaster@`/`no-reply@`/`noreply@`/vuoto; mittente in `email_suppressions`; mittente
      uguale all'indirizzo della piattaforma stessa.
- [ ] `Content-Type: multipart/report; report-type=delivery-status` è riconosciuto come DSN e instradato alla
      gestione bounce (US-319), **non** al ticketing.
- [ ] Rate limit configurabile (default 3/ora, 10/giorno per indirizzo, da `config/mail_pipeline.php`): oltre
      soglia, il messaggio è comunque elaborato ma **nessun auto-reply** parte, e l'indirizzo va in
      `email_suppressions` con `reason = loop_protection` e `expires_at` valorizzato.
- [ ] Ogni regola è testata isolatamente con un caso reale (fixture `.eml` o header sintetico) che deve essere
      scartato e uno che non deve esserlo — un test per regola, non un test generico "classifica correttamente".
- [ ] Un messaggio non scartato passa a `status = classified`.
- [ ] Typecheck/lint/test passano.

### US-305: Identificazione del mittente
**Description:** Come sistema, devo risolvere l'utente `users` mittente di un'email in modo affidabile, senza
attribuzioni sbagliate (§7.3.6).

**Acceptance Criteria:**
- [ ] Match case-insensitive su `users.email` (riusa l'indice funzionale `lower(email)` già presente da
      Fase 0/US-010, nessuna nuova migrazione).
- [ ] Se non trovato, prova il sub-address (`nome+tag@dominio` → `nome@dominio`).
- [ ] **Non** inferisce l'utente dal solo dominio del mittente (rischio di attribuzione errata, esplicitamente
      vietato dal PRD).
- [ ] Se non identificato, il messaggio è marcato per la gestione di US-308 (quarantena), mai scartato.
- [ ] Typecheck/lint/test passano.

### US-306: Risoluzione del thread (VERP, In-Reply-To, subject, euristica)
**Description:** Come sistema, devo capire se un'email è la risposta a un ticket esistente o l'apertura di uno
nuovo, usando il metodo più affidabile disponibile (§7.3.6, decisione Q2: **catch-all disponibile**).

**Acceptance Criteria:**
- [ ] Ogni email in uscita generata da questa fase (US-311+) usa un `Reply-To` nella forma
      `ticket+<ulid>@dominio`, dove `<ulid>` è quello del `ticket_message` a cui la notifica si riferisce
      (plus-addressing/VERP) — il dominio email di Montagna Servizi instrada già qualunque prefisso `ticket+*`
      alla stessa casella (confermato disponibile).
- [ ] In ingresso, `App\Domain\Mail\Actions\ResolveEmailThread` prova, **in quest'ordine**, e si ferma al primo
      che produce un match: (1) token `ticket+<ulid>` nel destinatario `To`; (2) `In-Reply-To`/`References`
      confrontati con `email_messages.message_id`; (3) token `[#<id ticket>]` nel subject normalizzato (US-303),
      cercato con regex ancorata — funziona sui ticket importati dal v1 perché l'ETL conserva gli id originali
      (§5.1); (4) euristica: stesso mittente + subject normalizzato identico + thread aperto negli ultimi N
      giorni (default 30, da config), **registrando esplicitamente** che il match è euristico (campo/flag sul
      risultato, visibile poi in amministrazione).
- [ ] Nessun match su nessuno dei quattro livelli → il messaggio genera un nuovo ticket (US-307).
- [ ] Un test per livello che dimostra che un match al livello N non viene mai scavalcato da un livello
      successivo meno affidabile (es. un messaggio con token VERP valido non deve mai finire sull'euristica).
- [ ] Typecheck/lint/test passano.

### US-307: Applicazione — creazione ticket o nuovo messaggio, notifiche post-commit
**Description:** Come cliente, quando scrivo una nuova richiesta o rispondo a una notifica, il sistema deve
creare/aggiornare il ticket corretto e avvisare chi deve saperlo, in modo transazionale (§7.3.7, R4).

**Acceptance Criteria:**
- [ ] Nuovo ticket: riusa `App\Domain\Ticketing\Actions\CreateTicket` (US-103) con `title` = subject
      normalizzato, primo `ticket_message` `channel = email` col corpo parsato (US-303), `type = helpdesk`,
      `requester_id` = utente identificato (US-305) o `null` se non identificato (→ US-308).
- [ ] Risposta a ticket esistente: riusa `App\Domain\Ticketing\Actions\PostTicketMessage`-equivalente per
      canale email (nuovo `ticket_message` con `channel = email`) e applica la transizione T7 già esistente
      (`RestoreTicketStatusOnRequesterMessage`, US-106) quando l'autore è il richiedente.
- [ ] La creazione/aggiornamento del ticket e l'aggiornamento di `email_messages` (`status = applied`,
      `ticket_id` collegato) avvengono nella **stessa transazione** (R4): un fallimento in una delle due annulla
      entrambe.
- [ ] **Dopo il commit**, in coda: conferma di ricezione al mittente (E1, US-311) e notifica allo staff (E3,
      US-312) — mai dentro la transazione, mai in modo sincrono.
- [ ] Test che verifica esplicitamente: se l'invio della notifica fallisce (coda che lancia un'eccezione), il
      ticket/messaggio **restano comunque creati** (a differenza del v1, dove un fallimento SMTP impediva di
      marcare l'email come elaborata, causando duplicati infiniti — problema 2).
- [ ] Typecheck/lint/test passano.

### US-308: Mittente non riconosciuto — quarantena
**Description:** Come membro dello staff, voglio essere avvisato quando scrive un mittente mai visto prima, per
poterlo associare a un cliente esistente o crearne uno nuovo, senza perdere il messaggio (§7.3.8).

**Acceptance Criteria:**
- [ ] Un messaggio classificato ma con mittente non identificato (US-305) va in `status = quarantined`, mai
      scartato.
- [ ] Notifica E9 allo staff (US-312) con mittente, subject, estratto del corpo, e un link diretto alla pagina
      di quarantena in amministrazione (US-322).
- [ ] Auto-reply al mittente **solo se** passa tutti i controlli anti-loop di US-304, con l'indirizzo di
      supporto letto da `config/mail_pipeline.php` (mai hard-coded come nel v1, problema 18).
- [ ] Le azioni "associa a utente esistente" / "crea nuovo utente e ticket" vivono nell'amministrazione
      (US-322): applicandole, il messaggio viene **riprocessato** dalla pipeline (da US-305 in poi) e genera il
      ticket.
- [ ] Typecheck/lint/test passano.

### US-309: Allegati inbound
**Description:** Come sistema, devo importare gli allegati di un'email in modo sicuro, senza path traversal né
perdite silenziose (§7.3.9, problema 15).

**Acceptance Criteria:**
- [ ] Nome file sanitizzato (slug + validazione estensione) con path su disco costruito da un ULID, **mai** il
      nome file originale usato come path.
- [ ] Limiti (dimensione per allegato, totale per messaggio, numero massimo) letti da `config/mail_pipeline.php`
      — stesso principio già stabilito per gli allegati sui ticket web (`TicketAttachmentTypes`, US-107), ma
      configurazione propria perché il contesto (email arbitrarie) è più permissivo/diverso da quello dei
      messaggi web.
- [ ] Allegati `inline` (loghi, firme) esclusi per default, con flag esplicito per includerli.
- [ ] Validazione MIME reale (sniffing del contenuto, non l'header dichiarato) — stesso gotcha già documentato
      per `spatie/laravel-medialibrary` in US-107.
- [ ] Un allegato scartato produce comunque un record `email_attachments` con `status` che inizia con
      `rejected_` e il motivo — **mai** uno scarto silenzioso (il campo `status`/`rejection_reason` esiste già
      nello schema di Fase 0).
- [ ] Un errore su un singolo allegato non fa fallire l'elaborazione dell'intero messaggio.
- [ ] L'allegato importato con successo è collegato al `ticket_message` creato/aggiornato in US-307 (stesso
      meccanismo medialibrary già usato per gli allegati web, riusare la collection `attachments` su
      `TicketMessage`, US-107 — `email_attachments.media_id` è già la colonna pensata per questo collegamento).
- [ ] Typecheck/lint/test passano.

### US-310: Layout email unico e componenti riusabili
**Description:** Come utente che riceve una notifica, voglio un'email leggibile, coerente col design della
piattaforma, e utilizzabile anche senza client HTML (§7.5.4, problema 14 in parte).

**Acceptance Criteria:**
- [ ] Un solo layout `resources/views/emails/layouts/base.blade.php` con logo/palette/tipografia dal design
      system esistente (§8, `docs/design-system.md`/`theme.css`, US-004) — **zero CSS duplicato** tra template
      (nel v1 lo stesso blocco `<style>` è copiato in 5 file).
- [ ] Ogni email ha una versione plain-text generata insieme all'HTML (Laravel Mailable `text()` oltre a
      `view()`/Markdown mailable), mai solo HTML.
- [ ] Componenti Blade riusabili: intestazione ticket (numero + titolo), badge di stato (riusa la stessa
      logica colore/icona di `TicketStatus::getColor()`, non una seconda palette), blocco messaggio, bottone
      call-to-action, footer con dati societari.
- [ ] Footer con link alle preferenze di notifica (US-317) e, dove pertinente (comunicazioni non essenziali),
      header `List-Unsubscribe`.
- [ ] Un test che verifica che nessun HTML malformato viene prodotto (es. tag non chiusi) per almeno un
      Mailable reale per tipo di comunicazione.
- [ ] Typecheck/lint/test passano.
- [ ] Verifica visiva di almeno una email renderizzata (screenshot Chrome headless o Mailpit, stesso approccio
      già usato per il login in US-004+) prima di considerare la story completa.

### US-311: Mailable E1/E2 — conferme di ricezione/apertura ticket
**Description:** Come richiedente, voglio una conferma quando il mio ticket è stato ricevuto, sia che l'abbia
aperto via email sia via portale (§7.5.2 E1/E2).

**Acceptance Criteria:**
- [ ] `TicketReceivedByEmailMail` (E1): inviata al mittente quando un ticket è creato via email (da US-307),
      contiene numero ticket e link al portale.
- [ ] `TicketOpenedFromWebMail` (E2, **nuovo**: il v1 non la manda): inviata al richiedente quando crea un
      ticket dal pannello web.
- [ ] Entrambe `implements ShouldQueue` con `$tries`/`$backoff`/`retryUntil` (R5), producono un record
      `email_messages` `direction = outbound` con `message_id` generato e `Reply-To` = indirizzo VERP del
      ticket (US-306).
- [ ] Subject con token `[#<id>]` (fallback di threading, US-306 punto 3).
- [ ] Nessun invio se il destinatario è in `email_suppressions` o ha disattivato questo tipo di notifica in
      `notification_preferences` (schema già presente da Fase 0).
- [ ] Notifica in-app (Filament database notification) generata in aggiunta quando il destinatario è interno
      (qui non applicabile: il destinatario è sempre il richiedente/cliente).
- [ ] Typecheck/lint/test passano (incluso: nessun invio reale a un indirizzo reale nei test, riusa il guard
      `BlockRealRecipientsOutsideProduction` già esistente da US-R08).

### US-312: Mailable E3/E9 — notifica staff (nuovo ticket cliente, mittente sconosciuto)
**Description:** Come membro dello staff, voglio essere avvisato quando un cliente apre un nuovo ticket o
scrive un mittente mai visto, per non lasciare nulla senza risposta (§7.5.2 E3/E9, **corregge il problema 10**).

**Acceptance Criteria:**
- [ ] `NewCustomerTicketStaffMail` (E3): inviata al gruppo staff configurabile
      (`MAIL_STAFF_NOTIFICATION_GROUP`, US-301) quando un ticket è creato da un cliente, sia via web sia via
      email — **non** un `foreach` sincrono su "tutti i developer" come nel v1.
- [ ] `UnknownSenderStaffMail` (E9): inviata allo stesso gruppo quando un messaggio va in quarantena (US-308),
      con link diretto alla riga di quarantena.
- [ ] Entrambe in coda (R5), con notifica in-app Filament per ciascun destinatario interno.
- [ ] Test che verifica: il gruppo destinatari è letto da configurazione, non hard-coded; un cambiamento del
      gruppo in config cambia i destinatari senza toccare il codice del Mailable.
- [ ] Typecheck/lint/test passano.

### US-313: Mailable E4 — cambio di stato
**Description:** Come richiedente o membro dello staff, voglio sapere quando lo stato del mio ticket cambia, con
un messaggio che indica chiaramente il nuovo stato e mi porta alla pagina giusta per il mio ruolo (§7.5.2 E4,
**corregge il problema 11**: `$recipient->role` non esiste, il v1 manda sempre lo stesso template).

**Acceptance Criteria:**
- [ ] `TicketStatusChangedMail` ascolta `TicketStatusChanged` (evento già esistente da US-103) e determina il
      contenuto/link in base al **ruolo reale** del destinatario (`$user->hasRole(...)`/permessi, mai un
      attributo inesistente).
- [ ] Il template mostra esplicitamente il nuovo stato (label localizzata) e linka `TicketResource::getUrl()`
      per lo staff o la vista cliente per un customer.
- [ ] Applica la regola di destinazione di US-318 (nessuno riceve la notifica di un'azione che ha eseguito lui
      stesso).
- [ ] Un test per ciascuna transizione "rilevante" (da definire in US-318) che verifica destinatario e
      contenuto del template.
- [ ] Typecheck/lint/test passano.

### US-314: Mailable E5 — nuovo messaggio sul ticket
**Description:** Come partecipante di un ticket, voglio essere avvisato quando arriva un nuovo messaggio
pubblico, ma mai di quelli interni (§7.5.2 E5).

**Acceptance Criteria:**
- [ ] `NewTicketMessageMail` inviata quando un `ticket_message` con `visibility = public` viene creato (evento
      `TicketMessagePosted`, US-106), a partecipanti + richiedente + assegnatario + tester, **meno l'autore**
      (riusa `Ticket::messageRecipients()`, già esposto da US-106 per questo scopo esatto).
- [ ] I messaggi `visibility = internal` non generano **mai** questa email, nemmeno verso lo staff (verificato
      con un test che crea un messaggio interno e asserisce zero mail accodate).
- [ ] Typecheck/lint/test passano.

### US-315: Mailable E6 — assegnazione
**Description:** Come developer/tester, voglio sapere quando mi viene assegnato un ticket (§7.5.2 E6).

**Acceptance Criteria:**
- [ ] `TicketAssignedMail` inviata al nuovo assegnatario quando `AssignTicket`/il cambio di `assignee_id` o
      `tester_id` (US-110) avviene, **solo se** l'assegnatario è diverso da chi esegue l'azione.
- [ ] Typecheck/lint/test passano.

### US-316: Mailable E7 — reminder ticket in attesa + scheduling
**Description:** Come richiedente, voglio un promemoria se il mio ticket è in attesa da troppo tempo senza
attività, per non doverlo controllare manualmente (§7.5.2 E7, **da schedulare**: nel v1 il comando esiste ma
non gira mai).

**Acceptance Criteria:**
- [ ] Comando `tickets:remind-waiting` che seleziona i ticket `status = waiting` senza attività rilevante
      (letta da `ticket_views`/`ticket_logs`) da almeno 3 giorni **lavorativi** (esclusi sabato/domenica, stesso
      principio già usato da `WorkedTimeCalculator`, US-109) e invia `TicketWaitingReminderMail` al richiedente.
- [ ] Il comando è **effettivamente registrato nello scheduler** (`routes/console.php`, cadenza giornaliera) —
      questo è esattamente il gap del v1 da correggere, non solo scrivere il comando.
- [ ] Un ticket già ricordato di recente (finestra configurabile) non riceve un secondo reminder duplicato nello
      stesso periodo.
- [ ] Typecheck/lint/test passano.

### US-317: Preferenze di notifica — applicazione effettiva
**Description:** Come utente, voglio poter disattivare un tipo di notifica e non riceverla più, con la
piattaforma che rispetta davvero questa scelta ad ogni invio (§7.5.1, richiamato da §14 Fase 3).

**Acceptance Criteria:**
- [ ] Un helper/trait condiviso (es. `App\Domain\Mail\Support\NotificationGate::allows(User $user, string
      $notificationType): bool`) legge `notification_preferences` (schema esistente) ed è chiamato da **ogni**
      Mailable di questa fase prima dell'invio — un solo punto di verità, non un controllo duplicato in ognuno.
- [ ] Un utente senza righe in `notification_preferences` per un dato tipo riceve la notifica (default
      "abilitato", coerente con `enabled` default `true` nello schema).
- [ ] La UI per **gestire** le proprie preferenze (schermata cliente) resta esplicitamente **fuori scope**: è
      assegnata alla Fase 6 (§14, "la schermata di gestione è in Fase 6") — questa story implementa solo il
      **rispetto** delle preferenze already-stored, non l'interfaccia per modificarle. Se serve un modo minimo
      per popolare righe di test, un comando artisan/tinker basta, non una UI.
- [ ] Typecheck/lint/test passano.

### US-318: Regole di destinazione (tabella attore × transizione → destinatari)
**Description:** Come sviluppatore, ho bisogno di un'unica fonte di verità testata per "chi riceve cosa", per
non ripetere il bug di precedenza operatori del v1 (§7.5.3, problema 12).

**Acceptance Criteria:**
- [ ] `App\Domain\Mail\Support\NotificationRecipientResolver` (o struttura equivalente) con una tabella
      esplicita "attore che ha eseguito l'azione × transizione → destinatari", **non** un'espressione booleana
      con `&&`/`||` misti come nel v1.
- [ ] Principio applicato ovunque: **nessuno riceve la notifica di un'azione che ha eseguito lui stesso** — un
      test per ogni combinazione rilevante che lo dimostra esplicitamente.
- [ ] Riusata da E4 (US-313) e da qualunque altra comunicazione trigger-da-transizione di questa fase.
- [ ] Typecheck/lint/test passano.

### US-319: Bounce, DSN e soppressioni
**Description:** Come sistema, devo riconoscere quando un'email non è arrivata a destinazione e smettere di
inviarne altre a quell'indirizzo, in modo visibile e reversibile (§7.5.5).

**Acceptance Criteria:**
- [ ] I DSN (riconosciuti in US-304) sono correlati all'email originale via `Message-ID` citato nel corpo del
      report (`App\Domain\Mail\Actions\ProcessDeliveryStatusNotification`).
- [ ] Hard bounce → riga in `email_suppressions` con `reason = hard_bounce`; l'utente collegato è segnalato in
      UI (US-322/US-323) come "email non recapitabile".
- [ ] Soft bounce → `bounce_count` incrementato sulla riga esistente (o creata se assente); la soppressione
      scatta solo dopo N occorrenze consecutive (soglia da config).
- [ ] Le soppressioni sono **rimuovibili** da amministrazione (collegato a US-323): un admin può far ripartire
      l'invio verso un indirizzo tornato valido.
- [ ] Typecheck/lint/test passano.

### US-320: Localizzazione reale delle comunicazioni
**Description:** Come utente non italiano, voglio ricevere le comunicazioni nella mia lingua, con un testo
sempre completo (mai una chiave grezza, §7.6, problema 14).

**Acceptance Criteria:**
- [ ] Lingua di ogni comunicazione = `users.locale` del destinatario, fallback `organizations.locale`, poi
      `APP_LOCALE`.
- [ ] `lang/it.json` e `lang/en.json` presenti e **completi** per tutte le chiavi usate da questa fase
      (Mailable + subject).
- [ ] Test dedicato che itera ogni chiave `__()`/`trans()` usata dal codice di questa fase e verifica che
      esista in **entrambe** le lingue supportate — un fallimento qui blocca la CI, non solo un warning.
- [ ] Nessuna stringa hard-coded in italiano in nessun Mailable/template di questa fase.
- [ ] Typecheck/lint/test passano.

### US-321: Amministrazione email — Registro e dettaglio
**Description:** Come admin, voglio vedere tutte le email transitate nel sistema, con un dettaglio completo per
ognuna, per capire cosa è successo senza consultare i log del server (§7.7).

**Acceptance Criteria:**
- [ ] Nuova Filament Resource `EmailMessageResource` (sola visualizzazione, nessuna creazione/modifica manuale
      del contenuto), gated su un nuovo permesso `email.view`/`email.manage` (già previsto nel catalogo enum
      `Permission`, §9.3 del PRD principale — verificare che esista, altrimenti aggiungerlo qui) e visibile solo
      ad `admin`.
- [ ] Tabella filtrabile per: direzione, stato, mittente, destinatario, ticket collegato, periodo.
- [ ] Vista di dettaglio: header email (from/to/cc/bcc/subject/message-id/in-reply-to), corpo (plain + html
      sanitizzato), allegati, ticket/thread collegato, numero tentativi, ultimo errore.
- [ ] Typecheck/lint/test passano.
- [ ] Verifica in browser (screenshot) del registro con almeno un'email reale/di fixture visibile.

### US-322: Amministrazione email — Azioni e quarantena
**Description:** Come admin, voglio poter intervenire manualmente su un messaggio (riprocessarlo, collegarlo,
scartarlo, reinviarlo) e risolvere la quarantena senza toccare il DB a mano (§7.3.8, §7.7).

**Acceptance Criteria:**
- [ ] Azioni sulla vista di dettaglio (US-321): **riprocessa** (rilancia la pipeline da `classified` in poi),
      **assegna a utente** (per un mittente sconosciuto: crea/aggiorna `requester_id`), **collega a ticket**
      (override manuale del thread risolto da US-306), **scarta** (forza `status = discarded` con motivo
      manuale), **reinvia** (per un outbound `failed`/`bounced`, dopo eventuale correzione).
- [ ] Pagina/tab dedicato "Quarantena" con le azioni specifiche di US-308: "associa a utente esistente" (poi
      riprocessa), "crea nuovo utente e ticket" (crea l'utente, poi riprocessa).
- [ ] Ogni azione è tracciata (chi l'ha eseguita, quando) — riusa lo stesso principio di `ticket_logs` se
      applicabile, o un campo dedicato sul record email.
- [ ] Typecheck/lint/test passano.
- [ ] Verifica in browser di almeno un'azione (es. "riprocessa" su un messaggio di fixture in quarantena).

### US-323: Amministrazione email — Soppressioni e metriche
**Description:** Come admin, voglio vedere quali indirizzi non ricevono più email e perché, rimuoverli se non
serve più, e avere un polso generale della salute del sottosistema (§7.5.5, §7.7).

**Acceptance Criteria:**
- [ ] Elenco `email_suppressions` filtrabile per motivo (`hard_bounce`/`loop_protection`/altro), con azione di
      rimozione (elimina la riga, riabilita l'invio).
- [ ] Widget/pagina con metriche essenziali: messaggi elaborati/scartati/falliti nelle ultime 24h, tempo medio
      di elaborazione (fetch → applied), bounce rate.
- [ ] Typecheck/lint/test passano.

### US-324: Voce di menu "Email" con Mailpit come prima sotto-voce
**Description:** Come admin che lavora in locale o su UAT, voglio un link diretto a Mailpit dal pannello, per
ispezionare le email inviate durante lo sviluppo/collaudo senza dover ricordare un URL a parte — richiesto
esplicitamente per questa fase.

**Acceptance Criteria:**
- [ ] Nuovo gruppo di navigazione Filament **"Email"** in `AdminPanelProvider`, che raggruppa: la voce
      **"Mailpit"** (prima sotto-voce) seguita da "Registro" (US-321), "Quarantena" (US-322), "Soppressioni"
      (US-323) — stesso gruppo unico, non due gruppi separati.
- [ ] "Mailpit" è un `Filament\Navigation\NavigationItem` con `url()` letto da una nuova config
      `config('mail_pipeline.mailpit_url')` (nuova chiave, popolata da env `MAILPIT_URL`), **non** una Resource:
      apre l'URL configurato in una nuova scheda del browser (`shouldOpenInNewTab: true`), mai nella stessa
      finestra del pannello.
- [ ] Nuove variabili d'ambiente aggiunte (mai sovrascrivendo quelle esistenti):
      - `.env.example`: `MAILPIT_URL=http://localhost:${MAILPIT_UI_PORT:-8025}` (coerente con la UI già esposta
        dal servizio `mailpit` del `docker-compose.yml` locale, US-024/§ETL).
      - `.env.uat.example`: `MAILPIT_URL=https://mailpit-ticket-uat.montagnaservizi.com` (stesso host già
        documentato in `docs/collaudo/00-istruzioni-generali.md` §10 e protetto da Basic Auth a livello di
        reverse proxy — questa story non tocca l'autenticazione di Mailpit, solo il link verso di essa).
- [ ] La voce **"Mailpit" è visibile solo quando `app()->environment(['local', 'staging'])`** (query esplicita
      dell'ambiente Laravel, non un flag separato: `staging` è il valore di `APP_ENV` già usato da
      `.env.uat.example` per l'ambiente UAT) **e** `config('mail_pipeline.mailpit_url')` non è vuoto — se la
      variabile non è configurata, la voce è **nascosta**, mai un link rotto.
- [ ] In un ambiente con `APP_ENV=production` (o qualunque valore diverso da `local`/`staging`), l'intero
      gruppo "Email" resta visibile (registro/quarantena/soppressioni servono anche in produzione), ma la
      sotto-voce "Mailpit" specificamente **non compare**.
- [ ] Test Filament che verifica la visibilità condizionale della voce nei tre casi: locale con URL configurato
      (visibile), staging con URL configurato (visibile), produzione o URL assente (non visibile).
- [ ] Typecheck/lint/test passano.
- [ ] Verifica in browser: screenshot del pannello in ambiente locale con la voce "Mailpit" visibile nel menu e
      funzionante (click → apre Mailpit in una nuova scheda).

### US-325: Comando `mail:retry-failed`
**Description:** Come admin/sistema, voglio poter far ripartire l'invio dei messaggi outbound falliti senza
doverli reinviare uno per uno da UI (§7.3.3, coerente con l'elenco comandi di §14 Fase 3).

**Acceptance Criteria:**
- [ ] Comando `mail:retry-failed {--limit=} {--email-message=}` che riaccoda i Mailable con `status = failed`
      (rispettando comunque `email_suppressions`/`notification_preferences` come un invio normale, US-317/
      US-311+).
- [ ] Un messaggio il cui destinatario è nel frattempo finito in soppressione **non** viene reinviato: il
      comando lo segnala e passa al successivo, non si ferma.
- [ ] Typecheck/lint/test passano.

### US-326: Checkpoint di fine fase — verifica end-to-end su dati reali
**Description:** Come team, prima di chiudere la Fase 3 dobbiamo dimostrare che il sottosistema funziona con
email reali, non solo con la suite unitaria (§14, criteri di accettazione Fase 3).

**Acceptance Criteria:**
- [ ] Fixture `.eml` reali in `tests/Fixtures/emails/` che coprono almeno: risposta a una notifica (thread via
      VERP), risposta su un ticket importato dal v1 (thread via token subject `[#id]`, nessun VERP disponibile
      per la storia pregressa), un bounce hard, un mittente sconosciuto, un'email da un dominio in blacklist
      anti-loop.
- [ ] Test di integrazione end-to-end (`Artisan::call('mail:fetch-inbound')` con transport mockato sulle
      fixture sopra) che verifica l'intera pipeline fetch→parse→classify→apply per ciascun caso.
- [ ] **Criterio di accettazione esplicito dal PRD principale**: rispondere a un'email di notifica aggiorna il
      ticket esistente invece di crearne uno nuovo, **anche su un ticket importato dal v1**; riprocessare lo
      stesso messaggio non duplica nulla; un DSN non genera auto-reply; ogni email è ispezionabile
      dall'amministrazione (US-321); le comunicazioni arrivano nella lingua dell'utente (US-320) — ciascuno di
      questi punti ha un test dedicato che lo dimostra, non solo un'affermazione nel PR.
- [ ] Manifest di collaudo aggiornato (`docs/collaudo/fase-3.php`, stesso principio già stabilito per Fase 2:
      `php artisan collaudo:verify-manifest 3` deve passare) e PDF di collaudo rigenerato
      (`collaudo:generate 3`).
- [ ] Sessione di collaudo reale con l'utente `alessio.piccioli@montagnaservizi.com` (o altro referente reale
      già verificato su UAT) che invia/riceve email reali via Mailpit UAT (US-324 rende questo passo diretto
      dal pannello) prima di considerare la fase chiusa.
- [ ] Typecheck/lint/l'intera suite di test passano.

## 4. Requisiti funzionali

- FR-1: Ogni email in ingresso e in uscita è persistita in `email_messages` **prima** di qualunque effetto
  collaterale (schema già pronto da Fase 0).
- FR-2: Il sistema genera e conserva un `Message-ID` per ogni email in uscita; legge `In-Reply-To`/`References`
  in ingresso; risolve il thread con l'algoritmo a 4 livelli di US-306.
- FR-3: Nessuna email produce due volte lo stesso effetto — idempotenza garantita dai vincoli unique già
  presenti su `(imap_folder, imap_uid)` e `(direction, message_id)`, più `content_hash` per i casi residui.
- FR-4: La creazione/aggiornamento di un ticket e la registrazione della relativa email avvengono nella stessa
  transazione; le notifiche partono solo dopo il commit, in coda.
- FR-5: Ogni Mailable implementa `ShouldQueue` con `$tries`/`$backoff`/`retryUntil` — nessun invio sincrono.
- FR-6: Prima di ogni auto-reply, il sistema applica tutti i controlli anti-loop di US-304 e il rate limit
  configurato.
- FR-7: Ogni comunicazione (subject incluso) è localizzata secondo `users.locale`/`organizations.locale`/
  `APP_LOCALE`, con copertura verificata da test per tutte le lingue supportate.
- FR-8: Nessuna email è inviata a un indirizzo in `email_suppressions` o a un utente che ha disattivato quel
  tipo di notifica in `notification_preferences`.
- FR-9: L'amministrazione email (visibile solo ad `admin`) espone registro, dettaglio, azioni (riprocessa/
  assegna/collega/scarta/reinvia), quarantena con le sue azioni dedicate, soppressioni con rimozione, e
  metriche essenziali delle ultime 24h.
- FR-10: Il pannello admin mostra una voce "Mailpit" (prima sotto-voce del gruppo "Email") che apre in una
  nuova scheda l'URL configurato via `MAILPIT_URL`, visibile **solo** quando `APP_ENV` è `local` o `staging` e
  l'URL è configurato.
- FR-11: Gli allegati email sono importati con nome sanitizzato, path basato su ULID, validazione MIME reale, e
  ogni scarto produce un record visibile in UI con motivo esplicito.
- FR-12: Un DSN è sempre riconosciuto e instradato alla gestione bounce, mai al ticketing; un hard bounce
  sopprime l'indirizzo, un soft bounce lo conta fino a soglia.

## 5. Non-Goals (fuori scope)

- **E8 (digest periodico)**, **E10 (report di attività disponibile)**, **E11 (developer senza ticket in
  lavorazione)**: assegnate alla Fase 6 "se approvate" (§7.5.2) — questa fase implementa solo E1-E7 e E9.
- **UI cliente per gestire le proprie preferenze di notifica**: questa fase applica le preferenze già presenti
  a DB (US-317), non costruisce lo schermo per modificarle (Fase 6, §14).
- **OAuth2 per l'account Gmail in ingresso**: fuori scope per questa fase (decisione presa: IMAP con
  user+password/app password). L'interfaccia `InboundMailTransport` (US-301) è comunque pensata per poter
  aggiungere un'implementazione OAuth2 in futuro senza toccare la pipeline.
- **Passaggio a un provider a webhook** (Mailgun/Postmark/SES+SNS): non deciso per questa fase (si resta su
  IMAP polling); l'interfaccia comune permette di aggiungerlo in seguito.
- **Autenticazione/hardening di Mailpit stesso**: la Basic Auth su Mailpit UAT è già gestita a livello di
  reverse proxy (fuori dal codice applicativo) — US-324 collega solo un link, non tocca quella configurazione.
- **Automazioni schedulate complete** (oltre al reminder E7 e al fetch inbound): il resto di §10.2 resta in
  Fase 6.

## 6. Design Considerations

- Layout email (US-310) deve derivare da `docs/design-system.md`/`resources/css/theme.css`, stessa fonte di
  verità già stabilita per il pannello (US-004/US-005) — niente hex duplicati.
- Le pagine di amministrazione email (US-321/322/323) seguono lo stesso pattern Filament già consolidato nel
  repo: Resource per il registro, componenti custom (`Infolists`, `RepeatableEntry`) per header/corpo/allegati
  come già fatto per la conversazione ticket in US-110.
- Voce "Mailpit" (US-324): un `NavigationItem` puro, non una pagina Filament — nessun contenuto da renderizzare
  lato applicazione, solo un link esterno.

## 7. Technical Considerations

- **Nessuna nuova migrazione prevista**: lo schema (`email_messages`, `email_threads`, `email_attachments`,
  `email_suppressions`, `notification_preferences`) esiste già da Fase 0 (US-016) ed è già popolato/collegato
  dall'ETL di Fase 2 per i ticket con conversazione importata. Se durante l'implementazione emergesse un
  bisogno reale di una colonna aggiuntiva, aggiungerla con una migrazione dedicata (mai alterare a mano lo
  schema esistente) e documentarlo, ma non è un lavoro atteso da questa fase.
- Riuso esplicito di componenti già esistenti: `TicketMessageSanitizer` (US-106) per l'HTML in ingresso,
  `Ticket::messageRecipients()` (US-106) per E5, `RestoreTicketStatusOnRequesterMessage`/T7 (US-106) per la
  riapertura via risposta email, `WorkedTimeCalculator`-style esclusione weekend (US-109) per il reminder E7,
  `BlockRealRecipientsOutsideProduction` (US-R08) per bloccare invii reali fuori produzione nei test/locale.
- `webklex/php-imap` va aggiunto come nuova dipendenza Composer (non presente oggi nel `composer.json`).
- Il gruppo destinatari staff (`MAIL_STAFF_NOTIFICATION_GROUP`) e l'indirizzo di supporto per gli auto-reply
  vanno documentati in `.env.example` con lo stesso livello di dettaglio già usato per le altre variabili
  (commento sopra ogni riga, come da convenzione del file).
- Il dominio email di Montagna Servizi supporta il plus-addressing (`ticket+<ulid>@dominio`) verso la stessa
  casella IMAP monitorata da `mail:fetch-inbound` — nessuna configurazione DNS aggiuntiva prevista lato
  applicazione, ma va verificato concretamente con un invio di prova reale prima di considerare US-306 chiusa
  (non solo assunto dalla risposta a questa domanda).
- `config/mail_pipeline.php` è un file nuovo, distinto da `config/mail.php` (nativo Laravel, invio SMTP) per
  non mescolare configurazione di trasporto con configurazione di dominio (soglie anti-loop, gruppo staff,
  cartelle IMAP, URL Mailpit).

## 8. Success Metrics

- Zero ticket duplicati generati da risposte email durante l'intero collaudo di fine fase (US-326).
- 100% delle email in uscita di questa fase passano da coda (nessun invio sincrono rilevato in nessun test).
- 0 chiavi di traduzione mancanti rilevate dal test di copertura lingua (US-320).
- Tempo medio di elaborazione di un'email inbound (fetch → applied) misurabile dalla dashboard metriche
  (US-323), nessun target numerico imposto in questa fase (da osservare sui dati reali di collaudo).

## 9. Open Questions

- Verifica concreta del plus-addressing (`ticket+*@dominio`) con un invio di prova reale contro la casella
  IMAP di produzione/collaudo, prima della chiusura di US-306 — la disponibilità è stata confermata a livello
  di risposta ma non ancora testata end-to-end contro il dominio reale.
- Soglia esatta di rate limit anti-loop (default proposto 3/ora, 10/giorno, US-304) e soglia soft-bounce prima
  della soppressione (US-319, "N occorrenze consecutive"): valori di default ragionevoli da confermare col
  committente durante il collaudo, non bloccanti per iniziare l'implementazione.
- Elenco esatto delle "transizioni rilevanti" che generano E4 (US-313): il PRD principale non le enumera una
  per una — da definire come parte di US-318 con una tabella esplicita e testata, non assunto implicitamente.
- Se in futuro (Fase 6 o oltre) si volesse esporre Mailpit anche in produzione per un motivo operativo, la
  condizione di visibilità di US-324 andrebbe rivista esplicitamente: oggi è intenzionalmente
  `local`/`staging` soltanto, coerente con la richiesta che ha originato questa story.
