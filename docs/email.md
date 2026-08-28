# Sottosistema email

Fonte: PRD-ORCHESTRATOR-V2.md §7 (M8 — Sottosistema email), `CLAUDE.md` (sezioni US-301...US-326,
US-614, US-615, US-616). Riscritto da zero rispetto al v1 (D7): persistenza su DB, threading reale,
idempotenza. Vedi `docs/differences-from-v1.md` per l'elenco dei 20 problemi del v1 corretti.

## Architettura generale

Registro unico `email_messages` (`direction = inbound|outbound`): ogni email, in ingresso o in
uscita, è persistita **prima** di qualunque effetto collaterale — il DB è la sorgente di verità, mai
il server IMAP.

```
Inbound:  Fetch → Store raw (.eml) → Parse → Classify → Resolve sender → Resolve thread
          → Apply (crea ticket | aggiunge ticket_message | quarantena) → Import allegati
          → Notify (in coda, dopo commit)

Outbound: Action di dominio → SendOutboundTicketMail::run() → email_messages (status=queued)
          → Mailable ShouldQueue → invio SMTP
```

### Inbound — Action per step

| Step | Classe | Note |
|---|---|---|
| Fetch | `App\Domain\Mail\Transports\WebklexImapTransport` (implementa `InboundMailTransport`) | `limit` sempre esplicito, mai "tutti gli unseen" |
| Store raw | `App\Domain\Mail\Actions\StoreRawInboundEmail` | `.eml` completo salvato su disco `raw-emails` PRIMA di qualunque parsing |
| Parse | `App\Domain\Mail\Actions\ParseInboundEmail` (+ `Parsers\{SubjectNormalizer,EmailBodyParser,QuotedTextRemover}`) | qualunque eccezione è catturata: il messaggio passa a `status=failed`, mai propagata |
| Classify | `App\Domain\Mail\Actions\ClassifyInboundEmail` | header di controllo (`Auto-Submitted`, `Precedence`, `List-Id`, `List-Unsubscribe`, `X-Auto-Response-Suppress`), rilevamento DSN, rate limit anti-loop |
| Resolve sender | `App\Domain\Mail\Actions\ResolveEmailSender` | match case-insensitive su `users.email`, poi sub-address; se non identificato → quarantena |
| Resolve thread | `App\Domain\Mail\Actions\ResolveEmailThread` | 4 livelli in ordine di priorità (sotto) |
| Apply | `App\Domain\Mail\Actions\ApplyInboundEmail` | crea ticket o accoda `ticket_message`, tutto nella stessa transazione |
| Allegati | `App\Domain\Mail\Actions\ImportInboundEmailAttachments` | sniffing MIME reale, ULID sul path |
| DSN/bounce | `App\Domain\Mail\Actions\ProcessDeliveryStatusNotification` | correlazione via `Message-ID` estratto dal corpo del DSN |

Il comando `mail:fetch-inbound` (§10.2, ogni 5 minuti, `ENABLE_MAIL_FETCH_INBOUND`) orchestra
l'intera pipeline sopra in sequenza (wiring completato in US-326): per ogni messaggio appena
archiviato, `ParseInboundEmail → ClassifyInboundEmail →` (in base all'esito) `ProcessDeliveryStatusNotification`
oppure `ApplyInboundEmail`.

### Risoluzione del thread (4 livelli, §7.3.6)

1. **VERP**: token `ticket+<ulid>@dominio` nell'indirizzo di risposta, risolto sia contro
   `ticket_messages.ulid` sia contro `email_messages.ulid` (necessario per E2, ticket aperto da web
   senza un `ticket_message` associato). Confronto sempre case-insensitive: `HasUlids` genera ULID
   in minuscolo in questo progetto.
2. **In-Reply-To / References** confrontati con i `message_id` in `email_messages`.
3. **Token nel subject** (`[#<id ticket>]`).
4. **Euristica**: stesso mittente + subject normalizzato identico + thread aperto negli ultimi
   `mail_pipeline.threading.heuristic_window_days` giorni (default 30). Ultima risorsa, esplicitamente
   marcata come match euristico.

Nessun match a nessun livello → nuovo ticket.

### Outbound — punto unico di invio

`App\Domain\Mail\Actions\SendOutboundTicketMail::run()` è il **punto unico** di invio per l'intero
catalogo E1-E11: genera Message-ID e Reply-To VERP, crea sempre una riga `email_messages` outbound
(anche quando l'invio è bloccato: `status = suppressed`), controlla `email_suppressions` e
`notification_preferences` (via `App\Domain\Mail\Support\NotificationGate`) PRIMA di accodare, e
nega l'invio a un destinatario disattivato (`deactivated_at`). Ogni Mailable estende
`App\Domain\Mail\Mailables\OutboundMailable` (senza ticket, es. E8/E9) o
`TicketOutboundMailable extends OutboundMailable` (con un `Ticket` associato).

I destinatari di un cambio di stato (E4) sono risolti da una tabella dichiarativa
(`App\Domain\Mail\Support\NotificationRecipientResolver`, righe `{from, to, roles}`, ordinate come
la macchina a stati), non da un'espressione booleana — corregge il "problema 12" del v1 (precedenza
operatori errata). Un nuovo messaggio pubblico (E5) usa invece `Ticket::messageRecipients()`
(partecipanti + richiedente + assegnatario + tester). In entrambi i casi: **nessuno riceve la
notifica di un'azione che ha eseguito lui stesso**.

## Configurazione

`config/mail_pipeline.php` è la sola fonte di `env()` per questo modulo (distinto da
`config/mail.php`, che resta l'invio SMTP nativo Laravel).

| Chiave | Env | Default | Uso |
|---|---|---|---|
| `imap.*` | `IMAP_HOST`/`PORT`/`ENCRYPTION`/`VALIDATE_CERT`/`USERNAME`/`PASSWORD` | `localhost`/993/`ssl`/true | account IMAP inbound |
| `folders.*` | `IMAP_FOLDER_{INBOX,PROCESSED,ERRORS,QUARANTINE}` | `INBOX`/`Processed`/`Errors`/`Quarantine` | nomi reali delle cartelle sul server |
| `fetch.default_limit` | `IMAP_FETCH_DEFAULT_LIMIT` | 50 | limite per esecuzione di `mail:fetch-inbound` |
| `fetch.schedule_cron` | `MAIL_FETCH_SCHEDULE_CRON` | `*/5 * * * *` | cadenza scheduler |
| `fetch.timeout` | `MAIL_FETCH_TIMEOUT` | 300s | timeout applicativo (`set_time_limit()`, non `->timeout()` — vedi sotto) |
| `fetch.tries` | `MAIL_FETCH_TRIES` | 3 | retry sulla sola chiamata di rete IMAP |
| `fetch.lock_seconds` | `MAIL_FETCH_LOCK_SECONDS` | 280 | durata lock `withoutOverlapping()` |
| `storage.raw_disk` | `MAIL_RAW_STORAGE_DISK` | `raw-emails` | disco Laravel nominato per gli `.eml` grezzi |
| `rate_limit.max_per_hour`/`max_per_day` | `MAIL_RATE_LIMIT_PER_{HOUR,DAY}` | 3 / 10 | soglie anti-loop |
| `rate_limit.suppression_hours` | `MAIL_RATE_LIMIT_SUPPRESSION_HOURS` | 24 | durata soppressione `loop_protection` |
| `bounce.soft_bounce_threshold` | `MAIL_BOUNCE_SOFT_THRESHOLD` | 3 | occorrenze prima che un soft bounce sospenda davvero l'invio |
| `staff_notification_group` | `MAIL_STAFF_NOTIFICATION_GROUP` | vuoto | CSV di email risolte in `User` da `StaffNotificationGroup::recipients()` (E3/E9) |
| `support_address` | `MAIL_SUPPORT_ADDRESS` | vuoto | mittente degli auto-reply e "self-sender" anti-loop |
| `threading.heuristic_window_days` | `MAIL_THREAD_HEURISTIC_WINDOW_DAYS` | 30 | finestra euristica livello 4 |
| `attachments.*` | `MAIL_ATTACHMENT_MAX_FILE_SIZE`/`MAX_TOTAL_SIZE`/`MAX_COUNT`/`INCLUDE_INLINE`/`ALLOWED_EXTENSIONS`/`ALLOWED_MIMES` | 25MB / 50MB / 20 / false | config distinta e più permissiva di `config/ticketing.php` (upload da UI) |
| `quarantine_review_url` | `MAIL_QUARANTINE_REVIEW_URL` | vuoto | link diretto da E9; vuoto = nessun link (mai un link rotto) |
| `notification_preferences_url` | `MAIL_NOTIFICATION_PREFERENCES_URL` | vuoto (valorizzata da Fase 6) | footer email → pagina `NotificationPreferences` |
| `mailpit_url` | `MAILPIT_URL` | vuoto | voce di menu "Mailpit", solo locale/staging |
| `retry.default_limit`/`schedule_cron` | `MAIL_RETRY_DEFAULT_LIMIT`/`MAIL_RETRY_SCHEDULE_CRON` | 50 / `0 * * * *` | `mail:retry-failed` |
| `digest.schedule_cron` | `MAIL_DIGEST_SCHEDULE_CRON` | `0 7 * * *` | `mail:send-digest` (E8) |

Ogni comando schedulato di questo modulo è dietro un feature flag in `config('orchestrator.features.*')`
(default `false`, vedi `docs/operations.md`).

## Troubleshooting

- **Un comando schedulato non parte mai**: verificare il flag `config('orchestrator.features.*')`
  (env `ENABLE_*`) — tutti `false` di default, è una scelta di deploy.
- **`Illuminate\Console\Scheduling\Event` non ha un metodo `timeout()`** in questo Laravel (13.22):
  il timeout di `mail:fetch-inbound` è imposto dentro `handle()` con `set_time_limit()`, non da
  `->timeout()` sullo `Schedule::command()`. Aggiungerlo romperebbe l'intero bootstrap di `php artisan`.
- **Header MIME encoded-word non decodificati** (`=?UTF-8?Q?...?=`): `webklex/php-imap` senza
  l'estensione PECL `imap` non li decodifica da solo. `SubjectNormalizer` applica
  `mb_decode_mimeheader()` esplicitamente; qualunque nuovo codice che legga un header testuale
  (`From`/`To` display name) deve fare lo stesso.
- **`Attachment::getSize()`** restituisce i byte della parte MIME ancora codificata (base64), non i
  byte reali: per la dimensione vera usare sempre `strlen($attachment->getContent())`.
- **Un DSN si riconosce solo da** `Content-Type: multipart/report; report-type=delivery-status`; il
  Message-ID dell'email originale non è mai negli header del DSN stesso, va estratto dal corpo della
  parte `message/rfc822`/`text/rfc822-headers`.
- **`email_messages.status` non arriva mai a `sent`** in questa release: nessun listener transita da
  `queued` a `sent` via gli eventi Laravel `MessageSending`/`MessageSent` (gap noto, lasciato
  esplicitamente per una story futura). `EmailPipelineMetrics::bounceRate()` usa quindi
  `bounced / (bounced + queued)` come proxy, non `sent`.
- **Interpolazione `${VAR}` in `.env`**: `phpdotenv` risolve solo `${VAR}` semplice (nessun
  `${VAR:-default}` bash-style) e solo se `VAR` è definita PRIMA nello stesso file. Un valore
  riferito più in basso produce un'interpolazione silenziosamente vuota.
- **Bug Postgres storico (corretto)**: la colonna `notifications.data` era `text`; Filament genera
  una query con l'operatore JSON `->>`, incompatibile — corretto con la migrazione
  `2026_08_26_120000_change_notifications_data_column_to_json.php` (colonna ora `json`).
- **Amministrazione email** (`Permission::EmailView`/`EmailManage`, gruppo "Amministrazione" per
  admin): registro filtrable per direzione/stato/mittente/destinatario/ticket/periodo, azioni
  riprocessa/assegna a utente/collega a ticket/scarta/reinvia, quarantena, elenco soppressioni con
  rimozione, metriche (24h). Pagine: `EmailMessageResource` (index/view), `EmailQuarantine`,
  `EmailSuppressions`.

## Localizzazione

`App\Domain\Mail\Support\RecipientLocale::resolve(User $user)`: `users.locale` → prima
`organizations.locale` dell'utente → `config('app.locale')`. Applicato sia al Mailable
(`Mail::to(...)->locale(...)`) sia al subject (generato con `__('chiave', [...], $locale)` esplicito,
perché il locale globale dell'app in quel momento è quello della request/comando in corso, non
quello del destinatario). Tutte le chiavi passano da `lang/it.json`/`lang/en.json`: un test di
completezza (`tests/Feature/Domain/Mail/LocalizationCompletenessTest.php`) scansiona il codice per
ogni chiave usata e verifica che esista, non vuota, in entrambe le lingue.

## Catalogo delle comunicazioni (E1-E11)

| # | Comunicazione | Trigger | Destinatari | Notifica in-app | Note |
|---|---|---|---|---|---|
| E1 | Conferma di ricezione ticket | ticket creato via email | mittente | no | numero ticket + link al portale |
| E2 | Conferma apertura ticket da web | ticket creato dal cliente in UI | richiedente | no | nuovo rispetto al v1 |
| E3 | Nuovo ticket da cliente | ticket creato da un cliente (web o email) | gruppo staff (`staff_notification_group`) | sì | corregge il "problema 10" del v1 |
| E4 | Cambio di stato | transizione rilevante (tabella `NotificationRecipientResolver`) | destinatario pertinente per ruolo/transizione | sì (se interno) | corregge il "problema 11" (bug `$recipient->role`) |
| E5 | Nuovo messaggio sul ticket | `ticket_message` con `visibility = public` | partecipanti + richiedente + assegnatario + tester, meno l'autore | sì (se interno) | mai per un messaggio `internal` |
| E6 | Assegnazione | assegnazione a un developer/tester (via `AssignTicket` o context di `ChangeTicketStatus`) | il nuovo assegnatario/tester | sì | solo se diverso da chi esegue l'azione |
| E7 | Reminder ticket in attesa | `waiting` senza attività rilevante da ≥ 3 giorni lavorativi | richiedente | no | ora schedulato (`tickets:remind-waiting`, era orfano nel v1) |
| E8 | Digest periodico | riepilogo attività ticket nelle 24h | clienti che l'hanno abilitato | no | **Fase 6, US-614**: riscritto da zero (nel v1 dead code con 4 bug) |
| E9 | Mittente non riconosciuto | email da mittente sconosciuto | gruppo staff (+ mittente, se passa i controlli anti-loop) | sì | §7.3.8 |
| E10 | Report di attività disponibile | `ActivityReport.pdf_generated_at` valorizzato la prima volta | owner del report (utente, o tutti i membri se organizzazione) | sì | **Fase 6, US-615**, nuovo |
| E11 | Developer senza ticket in lavorazione | developer con ticket assegnati ma nessuno `progress`, 09:00-15:30 | il developer | sì | **Fase 6, US-616**: comando schedulato, non più job ritardato da observer (correzione esplicita v1→v2) |

Ogni comunicazione è verificata da `notification_preferences` (gestibile da UI in
`NotificationPreferences`, Fase 6 US-605) e da `email_suppressions`, entrambe controllate
esclusivamente dentro `SendOutboundTicketMail::blockedReason()` — un solo punto, mai duplicato per
Mailable. `NotificationType::appliesToUser()` filtra quali tipi sono mostrati a un cliente
(E1/E2/E7/E8/E10 + E4/E5 condivisi) rispetto a uno staff (E3/E4/E5/E6/E9/E11).

Idempotenza "una comunicazione al giorno" (E7/E8/E11): interrogare `email_messages` esistenti
(`mailable_class` + `created_at >= inizio giornata`) invece di aggiungere una colonna dedicata.
