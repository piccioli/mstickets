# Dominio Mail — pipeline email inbound/outbound (Fase 3, §7 del PRD)

Si carica quando lavori sotto `app/Domain/Mail/*` (o sui Mailable/view sotto `resources/views/emails/`,
`resources/views/components/emails/` se apri anche un file PHP di questo dominio nella stessa sessione — se
lavori SOLO sulle view email, tienilo comunque a portata di mano: contiene tutte le regole del layout/E1-E11).
Vedi anche `app/Domain/CLAUDE.md` per i pattern condivisi e la radice del repo per i gotcha trasversali
(`->having()` su alias Postgres, `Schedule::command()->timeout()` inesistente in Laravel 13, notifiche
database `text ->> unknown`).

## Pipeline email inbound — configurazione IMAP (US-301)

- `App\Domain\Mail\Contracts\InboundMailTransport` (§7.4) ha due metodi: `fetch(int $limit, ?DateTimeInterface
  $since = null): list<RawInboundEmail>` (limit sempre esplicito, mai un default nell'interfaccia) e
  `move(string $imapFolder, int $imapUid, ImapFolderRole $targetFolder): void`.
  `App\Domain\Mail\Support\RawInboundEmail` (readonly DTO) e `App\Domain\Mail\Enums\ImapFolderRole`
  (`Inbox`/`Processed`/`Errors`/`Quarantine`) vivono accanto al contratto.
  `App\Domain\Mail\Transports\WebklexImapTransport` è l'unica implementazione, legata all'interfaccia in
  `App\Providers\MailServiceProvider` con un semplice `$this->app->bind(...)`.
- **`webklex/php-imap` (libreria standalone, mai `webklex/laravel-imap`, vietato dal PRD)**:
  `Webklex\PHPIMAP\ClientManager::make($accountConfig)` costruisce un `Client` da un array di configurazione
  — poi `$client->connect()`. `$client->getFolder($nome)->query()->whereSince($data)->limit($n)->get()`:
  `Query::fetch()` esegue prima una SEARCH IMAP (solo UID) e applica `limit`/`page` alla lista di UID
  risultante PRIMA di scaricare header/body — è già di per sé la protezione OOM richiesta dal PRD.
- **Ricostruzione del `.eml` grezzo**: `webklex/php-imap` non espone un metodo "raw message" diretto, ma
  `$message->getHeader()->raw."\r\n\r\n".$message->getRawBody()` (header raw + CRLF doppio + body raw) è
  l'`.eml` completo. Riusare questa stessa concatenazione ovunque serva il contenuto grezzo.
- **Nomi di cartella IMAP mai come stringa letterale**: `App\Domain\Mail\Enums\ImapFolderRole` astrae il
  "ruolo" dal nome reale sul server; `WebklexImapTransport::folderName(ImapFolderRole)` risolve il nome reale
  da `config('mail_pipeline.folders')` e lancia `RuntimeException` se il ruolo non ha una voce configurata —
  risoluzione SEMPRE PRIMA di toccare la connessione IMAP (testabile senza server IMAP reale).
- `config/mail_pipeline.php` (distinto da `config/mail.php`, invio SMTP nativo) è la SOLA fonte di `env()`
  per questo modulo (§13.3): account IMAP, cartelle, `fetch.default_limit` (50), `rate_limit.max_per_hour`/
  `max_per_day`, `staff_notification_group` (array da CSV), `support_address`.

## Scheduler — niente `->timeout()` su `Schedule::command()` (Laravel 13)

`Illuminate\Console\Scheduling\Event` in questo Laravel (v13.22) **non ha un metodo `timeout()`**: esiste
solo per un vero sottoprocesso in background (`->runInBackground()`). Chiamarlo su una entry
`Schedule::command(...)` qualsiasi fa fallire l'intero bootstrap di Laravel con `BadMethodCallException`
(anche `php artisan`/Larastan smettono di funzionare). Per un timeout esplicito, imporlo dentro `handle()`
del comando con `set_time_limit((int) config(...))` (vedi `MailFetchInboundCommand::handle()`);
`->withoutOverlapping()` invece esiste ed è il modo corretto per l'anti-overlap. Rilevante per qualunque
comando schedulato in QUALUNQUE modulo, non solo Mail (US-316/US-325/US-610).

## Parsing del `.eml` grezzo — subject/corpo/citazioni (US-303)

- `Webklex\PHPIMAP\Message::fromString($blob)` (nessuna connessione IMAP) riapre un `.eml` già archiviato:
  `$message->hasTextBody()`/`getTextBody()`, `hasHTMLBody()`/`getHTMLBody()`. La conversione di charset del
  corpo funziona correttamente anche senza l'estensione PECL `imap`.
- **Gotcha reale, verificato empiricamente**: `Message::fromString()`/`getSubject()->toString()` NON
  decodifica gli header MIME "encoded-word" (`=?UTF-8?Q?...?=`, RFC 2047) quando l'estensione PECL `imap` non
  è caricata (mai il caso in questo progetto). Qualunque codice che legge un header testuale da un `Message`
  costruito con `fromString()` deve decodificare da sé con `mb_decode_mimeheader()`.
  `App\Domain\Mail\Parsers\SubjectNormalizer` lo fa per il subject; per un altro header MIME-encoded futuro,
  applicare lo stesso pattern.
- `App\Domain\Mail\Parsers\{SubjectNormalizer,EmailBodyParser,QuotedTextRemover}` sono classi pure orchestrate
  da `App\Domain\Mail\Actions\ParseInboundEmail::run(EmailMessage $m): EmailMessage`, l'unico punto che tocca
  disco/DB. **Qualunque eccezione** è catturata dentro l'Action stessa: mai propagata, sempre loggata
  (`Log::warning`), il messaggio passa a `status=failed` con `failure_reason`.
- `SubjectNormalizer::normalize()` **non rimuove** il token `[#<id>]` dal subject restituito (US-306 lo cerca
  direttamente dentro il subject normalizzato già salvato).
- `EmailBodyParser` riusa direttamente `App\Domain\Ticketing\Support\TicketMessageSanitizer` (cross-domain,
  sanzionato dal PRD) sia per sanitizzare `body_html` sia per derivare `body_text` da HTML quando il
  `text/plain` manca.
- `QuotedTextRemover::stripHtml()` rimuove i nodi `<blockquote>` via `DOMDocument`/`DOMXPath`, non con una
  regex sui tag. `strip()` (plain text) taglia al primo indizio di citazione/firma trovato riga per riga
  (`>`, `On ... wrote:`/`Il ... ha scritto:`, `-----Original Message-----`, delimitatore RFC 3676, blocco
  header Outlook `From:`/`Da:` verificato guardando le 4 righe successive).
- **Pest**: `->throwsNoExceptions()` in coda a un test che ha già `expect()` reali va in conflitto — usarlo
  SOLO su test senza asserzioni esplicite.

## Classificazione anti-loop — header di controllo e rate limit (US-304)

- Gli header di controllo (`Auto-Submitted`, `Precedence`, `List-Id`, `List-Unsubscribe`,
  `X-Auto-Response-Suppress`, `Content-Type`) non sono estratti da `ParseInboundEmail`:
  `App\Domain\Mail\Actions\ClassifyInboundEmail` rilegge il `.eml` e chiama di nuovo `Message::fromString()
  ->getHeader()`, stesso pattern I/O try/catch totale.
- **Gotcha verificato empiricamente**: `Attribute::toString()` concatena TUTTI i valori se l'header appare
  più volte anche solo semanticamente — usare sempre `Attribute::first()` per un singolo valore da
  confrontare. `Attribute::first()` restituisce `false` (non `null`) se assente — normalizzare a `null`.
- Un DSN si riconosce SOLO da `Content-Type: multipart/report; report-type=delivery-status`.
- Il "mittente della piattaforma stessa" è `config('mail_pipeline.support_address')`.
- `App\Domain\Mail\Models\EmailSuppression::scopeActive()` filtra le soppressioni senza `expires_at` o non
  ancora scadute — riusabile per qualunque controllo futuro su "il mittente è soppresso adesso".
- Il rate limit anti-loop NON scarta il messaggio (a differenza delle altre regole): prosegue a
  `status=classified`, ma il mittente va/rimane in `email_suppressions` con `reason=loop_protection`.
- `App\Domain\Mail\Enums\EmailDiscardReason` è il motivo salvato in `email_messages.failure_reason` quando
  `status=discarded`.

## Risoluzione del thread — VERP/In-Reply-To/subject/euristica (US-306)

- `email_messages.to`/`in_reply_to`/`references` non erano popolati prima di questa story. `references` è
  una colonna `text` (non jsonb): stringa singola con i message-id separati da spazio — sempre
  `explode(' ', ...)`, mai `json_decode`.
- **Gotcha ULID**: in questo progetto `HasUlids` genera ULID in MINUSCOLO — qualunque confronto (qui il
  local-part VERP `ticket+<ulid>`) deve essere case-insensitive (`whereRaw('lower(ulid) = ?', ...)`).
- `App\Domain\Mail\Parsers\SubjectNormalizer::normalizeForThreadMatching()` è ORA l'unica funzione di
  normalizzazione "per confronto" del subject: `App\Import\Stages\DeriveStage::normalizeSubject()` delega a
  questa stessa funzione — se cambia l'algoritmo, va cambiato SOLO qui. Diversa da `normalize()` (US-303) che
  preserva il token `[#<id>]` e il case.
- `App\Domain\Mail\Actions\ResolveEmailThread::run()` è puramente in lettura: restituisce un
  `App\Domain\Mail\Support\ThreadResolution` (`ticketId`/`matchLevel`/`isHeuristic()`). I quattro livelli
  (VERP → In-Reply-To/References → token subject → euristica) si fermano al primo match — l'ordine di
  chiamata garantisce la precedenza.

## Applicazione sul ticket — orchestrazione, notifiche post-commit (US-307)

- `App\Domain\Mail\Actions\ApplyInboundEmail::run(EmailMessage $m): EmailMessage` (§7.3.7) concatena
  `ResolveEmailSender` poi, solo se il mittente è identificato, `ResolveEmailThread`, e crea un ticket
  (`type=helpdesk`) o accoda un messaggio su uno esistente — SEMPRE seguito da `PostTicketMessage::run(...,
  TicketMessageChannel::Email)` e dall'update di `email_messages` (`status=applied`, `ticket_id`) nella
  STESSA `DB::transaction()`.
- `PostTicketMessage` accetta un 4° parametro opzionale `TicketMessageChannel $channel = Web`.
- **Notifiche post-commit senza Mailable ancora esistenti**: `App\Domain\Mail\Events\InboundEmailApplied`
  dispatchato DOPO che `DB::transaction()` è già tornato, avvolto nel proprio try/catch che loggasi soltanto
  — un fallimento nella notifica non deve mai disfare il ticket/messaggio già committati.
- **`mail:fetch-inbound` (US-302) NON chiama ancora questa Action fino a US-326** (wiring rimandato al
  checkpoint di fine fase).

## Quarantena mittente sconosciuto — riprocessamento e notifica (US-308)

- `ApplyInboundEmail::run()` accetta `status` iniziale `Classified` **o** `Quarantined`: è la precondizione
  per il riprocessamento (US-322, "associa a utente esistente"/"crea nuovo utente e ticket").
- **Gotcha reale**: `ResolveEmailSender` on-success DEVE riportare esplicitamente `status` a `Classified`
  (`forceFill(['user_id' => ..., 'status' => EmailStatus::Classified])`), non solo impostare `user_id`. Senza
  questo reset, un messaggio richiamato da `Quarantined` resterebbe tale nonostante `user_id` valorizzato —
  catturato solo da un test end-to-end del riprocessamento.
- `App\Domain\Mail\Events\EmailQuarantined(EmailMessage $emailMessage, bool $autoReplyAllowed)`: il flag è
  già calcolato al dispatch (nessuna soppressione attiva sul mittente PRIMA di questo messaggio) — un futuro
  listener deve solo rispettarlo, mai ricalcolare i controlli anti-loop da zero.

## Allegati inbound — sniffing MIME, ULID sul path, limiti dedicati (US-309)

- `App\Domain\Mail\Actions\ImportInboundEmailAttachments::run(EmailMessage, TicketMessage)` è chiamata da
  `ApplyInboundEmail` SUBITO DOPO `PostTicketMessage::run(...)`: se `raw_path` è vuoto o il file non esiste
  ritorna silenziosamente senza importare nulla.
- **Gotcha verificato empiricamente**: `Attachment::getSize()` restituisce i byte ANCORA CODIFICATI
  (base64), non decodificati — usare sempre `strlen($attachment->getContent())` per la dimensione reale.
- **Gotcha PHPStan**: `Attachment` non ha `getFilename()` nel blocco `@method` — usare l'accesso a proprietà
  `->filename` (magic `__get`), non il metodo.
- `Attachment::getMimeType()` fa già sniffing reale del contenuto (`finfo`). `getDisposition()`
  (`inline`/`attachment`) è il campo giusto per il filtro allegati-inline-esclusi-per-default.
- `App\Domain\Mail\Support\EmailAttachmentTypes` legge `config('mail_pipeline.attachments.*')`, distinta e
  più permissiva di `TicketAttachmentTypes` (Ticketing).
- **Path mai costruito dal nome originale** (path traversal): sempre `Str::ulid()` + estensione sniffata; il
  nome originale (slug della sola parte senza estensione) resta solo come metadato.
- Ogni allegato in try/catch indipendente: tipo non ammesso → `rejected_mime`, dimensione fuori soglia →
  `rejected_size`, eccezione → `failed` con `rejection_reason` — mai uno scarto silenzioso, mai un allegato
  che blocca gli altri.

## Layout email unico e componenti riusabili (US-310, §7.5.4)

- **Ogni Mailable futuro deve seguire questo pattern**: `@extends('emails.layouts.base')` +
  `@section('content')` con i componenti Blade riusabili sotto `resources/views/components/emails/`
  (`<x-emails.ticket-header>`, `<x-emails.status-badge>`, `<x-emails.message-block>`, `<x-emails.cta-button>`;
  il footer con dati societari è già nel layout). La vista testuale fa `@extends('emails.layouts.base-text')`
  — **nessuna vista Blade genera MAI solo l'HTML**: `Mailable::content()` valorizza sempre `view()` e
  `text()`. Esempio di riferimento: `resources/views/emails/examples/ticket-notification{,-text}.blade.php` +
  `Tests\Support\Mail\ExampleTicketNotificationMail` (solo dimostrazione, non fa parte del catalogo E1-E9).
- **Colori/font mai riscritti a mano**: leggere sempre `\App\Support\DesignTokens::get('ms-*')`. Il badge di
  stato riusa la categorizzazione di `TicketStatus::getColor()` via
  `App\Domain\Mail\Support\EmailStatusBadgePalette`.
- **Stili sempre inline + tabelle** (`role="presentation"`), mai `<style>`/`<link>` esterno.
- **Footer con dati societari hardcoded in italiano**: la localizzazione completa è responsabilità esplicita
  di US-320.
- **Link "preferenze di notifica" condizionale a `config('mail_pipeline.notification_preferences_url')`**
  (vuota di default finché la UI non esiste — mai un link rotto).
- **Verifica visiva senza SMTP**: `->render()` per l'HTML, render diretto della vista `-text` per il testo;
  per uno screenshot Chrome headless copiare temporaneamente l'HTML in `public/` e rimuoverlo subito dopo.

## Invio outbound — punto unico di invio, VERP, canale del ticket (US-311, §7.5.1/§7.5.2)

- **`App\Domain\Mail\Actions\SendOutboundTicketMail::run()` è il punto unico di invio per TUTTO il catalogo
  E1-E11**: genera Message-ID e Reply-To VERP dall'ULID della riga `email_messages` outbound che crea
  SEMPRE (anche quando bloccato: `status = Suppressed`, mai un salto silenzioso — §7.7), controlla
  `email_suppressions`/`notification_preferences`/`deactivated_at` (US-608) PRIMA di accodare. Ogni Mailable
  futuro deve passare da qui, mai `Mail::to()->queue()` diretto.
- **`App\Domain\Mail\Support\NotificationGate::allows(User $user, NotificationType $notificationType): bool`**
  (US-317): nessuna riga per quella combinazione → notifica consentita (default abilitato). Chiamato da
  `SendOutboundTicketMail::blockedReason()`.
- **`App\Domain\Mail\Mailables\OutboundMailable`** (base per Mailable senza `Ticket`, es. E9/E8) e
  **`TicketOutboundMailable extends OutboundMailable`** (aggiunge il `Ticket`): tries/backoff/retryUntil,
  `envelope()`/`headers()` letti dalla riga `email_messages` già decisa da `SendOutboundTicketMail`.
- **VERP non richiede più un `ticket_message` reale**: `ResolveEmailThread::byVerp()` risolve un token
  `ticket+<ulid>@dominio` sia contro `ticket_messages.ulid` sia (fallback) contro `email_messages.ulid` (la
  riga outbound stessa) — necessario per E2 (ticket aperto dal pannello, nessun `ticket_message` associato).
- **Gotcha reale**: `EmailMessage`/`TicketMessage` non hanno `ulid` nel proprio `#[Fillable]` — passare
  `'ulid' => $valore` a `::create()` viene IGNORATO silenziosamente e il modello ne genera comunque uno
  diverso (stesso prefisso monotonico, generato microsecondi dopo). Quando serve decidere l'ulid in anticipo,
  usare `EmailMessage::query()->forceCreate([...])`, mai `::create()`.
- `TicketCreated` porta un secondo parametro `TicketMessageChannel $channel = Web`: `ApplyInboundEmail` lo
  forza a `Email`.
- `App\Domain\Mail\Enums\NotificationType`: catalogo dei tipi verificati da `notification_preferences`
  (`channel` sempre `'email'`). Aggiungere un case solo quando la comunicazione è davvero implementata.
- **Non ancora implementato**: la transizione `Queued → Sent` via listener `MessageSending`/`MessageSent` — la
  riga outbound resta `status = Queued` dopo l'accodamento. `EmailPipelineMetrics::bounceRate()` (US-323) usa
  `bounced / (bounced + queued)` come proxy, mai `sent`.

## Notifiche staff E3/E9, prima notifica in-app Filament (US-312, §7.5.2)

- **`App\Domain\Mail\Support\StaffNotificationGroup::recipients()`** è l'UNICO punto che risolve
  `config('mail_pipeline.staff_notification_group')` in `User` reali (match case-insensitive,
  `scopeActive()`, un indirizzo senza utente è ignorato senza eccezione).
- **`SendOutboundTicketMail::run()` accetta `?Ticket $ticket`**: E9 non ha alcun ticket a cui riferirsi,
  `email_messages.ticket_id` è nullable.
- **Prima introduzione delle notifiche in-app Filament** (`Filament\Notifications\Notification::make()
  ->sendToDatabase($user)`): richiede la tabella `notifications` e `->databaseNotifications()` su
  `AdminPanelProvider`. Con `QUEUE_CONNECTION=sync` in test, `$user->notify(...)` è sincrona.
  `App\Domain\Mail\Support\StaffDatabaseNotification::send()` è il punto unico, riusato da E3 e E9. **Vedi
  gotcha Postgres `text ->> unknown` nella radice del repo — blocca ogni pagina Filament autenticata finché
  non corretto.**
- **Un ticket web NON implica un richiedente cliente**: E3 verifica esplicitamente
  `$ticket->requester?->hasRole(UserRole::Customer->value)` — mai dedurlo dal solo canale. `hasRole()` su
  un'istanza già caricata non lancia mai `RoleDoesNotExist`.
- **Un `Action` condiviso quando la stessa notifica ha più trigger**: E3 parte sia da `TicketCreated` (Web)
  sia da `InboundEmailApplied` (Email) — due Listener adattatori sottili delegano entrambi a
  `App\Domain\Mail\Actions\SendNewCustomerTicketStaffMail::run($ticket)`.
- **"Link diretto alla pagina di quarantena" (E9) quando la pagina non esiste ancora**:
  `config('mail_pipeline.quarantine_review_url')` resta vuota finché US-322 non costruisce
  `EmailMessageResource` — mai un link rotto.

## Cambio di stato E4, tabella destinatari attore×transizione (US-313, US-318, §7.5.2/§7.5.3)

- `TicketStatusChanged` porta anche `public User $actor` (4° parametro). `Ticket::messageRecipients(User
  $author)` (US-106) era usata inizialmente da E4 (US-313), ma **da US-318 E4 usa invece
  `NotificationRecipientResolver`** (sotto) — `messageRecipients()` resta corretto e usato SOLO da E5 (nuovo
  messaggio pubblico, notifica TUTTI i partecipanti).
- **`App\Domain\Mail\Support\NotificationRecipientResolver::resolve(Ticket, TicketStatus $from, TicketStatus
  $to, User $actor)` (US-318, sostituisce il blanket `messageRecipients()` per E4)**: la colonna "Effetti"
  della macchina a stati §6.1.3 (voce "notifica X") è la fonte di verità dei destinatari — solo le
  transizioni con un "notifica X" esplicito (`new→rejected`, `progress→testing`, `testing→tested`,
  `testing→todo`, `testing→rejected`, `{...}→waiting`, `{...}→problem`, catch-all `*→rejected`). Qualunque
  altra transizione (`todo→progress`, `progress→released`, `released→done`) → nessun destinatario, per
  design.
- Struttura: lista ORDINATA di righe `{from: ?list<TicketStatus>, to: TicketStatus, roles:
  list<NotificationRecipientRole>}` (mai `&&`/`||`, causa del bug v1 problema 12) — le righe specifiche
  vanno elencate PRIMA del catch-all generico. `NotificationRecipientRole` (`Requester`/`Assignee`/
  `Tester`/`Manager`) è astratto: `Manager` risolve contro l'intero pannello
  (`whereHas('roles', fn ($q) => $q->where('name', UserRole::Manager->value))`, mai lo scope Spatie `role()`).
- **L'esclusione "nessuno riceve la notifica di un'azione che ha eseguito lui stesso" vive in un unico
  punto**, alla fine di `resolve()` (`reject(fn ($u) => $u->is($actor))`), mai duplicata per singolo ruolo.
- Se una story futura del catalogo E1-E9 diventa "trigger-da-transizione", riusare
  `NotificationRecipientResolver` aggiungendo una riga alla tabella — mai una nuova query/condizione ad hoc.
- Test con `withRole()` + `Mail::assertQueued(...)` con closure sulle proprietà pubbliche del Mailable
  (`previousStatus`/`newStatus`/`recipientIsCustomer`) — senza ispezionare l'HTML.
- **Non esiste una vista cliente separata da quella staff**: un cliente vede la stessa URL
  `TicketResource::getUrl('view', ...)`, solo ristretta dalla policy. "Linka la pagina corretta per il ruolo"
  = contenuto/testo diverso nello stesso URL (`recipientIsCustomer`), mai un URL diverso.

## Nuovo messaggio pubblico E5 (US-314, §7.5.2)

- `App\Domain\Mail\Actions\SendNewTicketMessageMail::run(TicketMessagePosted $event)`: itera
  `$ticket->messageRecipients($author)` (esclude già l'autore) e chiama `SendOutboundTicketMail::run()`.
  L'autore è letto da `$event->message->author`.
- **Il guard "mai per un messaggio interno, nemmeno verso lo staff" vive nell'Action** (early return su
  `$message->visibility !== TicketMessageVisibility::Public`), non nel listener né in
  `messageRecipients()` — quest'ultima non ha nozione di visibilità.
- `NewTicketMessageMail` NON è nel dataset condiviso `TicketOutboundMailablesTest.php` (costruttore diverso a
  più argomenti) — ha il proprio file di test dedicato.

## Assegnazione E6 (US-315, §7.5.2)

- **`assignee_id`/`tester_id` cambiano da DUE Action distinte**: `AssignTicket` (US-110, solo `assignee_id`)
  e il `$context` di `ChangeTicketStatus` (una transizione può valorizzare entrambi). `TicketAssigned` esteso
  con `public User $actor`; `ChangeTicketStatus::run()` confronta `$context['assignee_id']`/`['tester_id']`
  con il valore PRIMA di `fill($context)` — dispatcha `TicketAssigned` (assegnatario) o
  `TicketTesterAssigned` (tester) — un solo evento indipendentemente da quale Action l'ha causato.
- **Nessun nuovo `ticket_log` per il cambio via `ChangeTicketStatus`** (gap preesistente, non da fixare senza
  richiesta esplicita).
- `SendTicketAssignedMail::run(Ticket, int $newUserId, bool $asTester, User $actor)`: il guard è
  `$newUserId === $actor->id` (non `messageRecipients()`, che restituisce TUTTI i destinatari — sbagliato
  qui: solo al nuovo assegnatario/tester).
- **Gotcha permessi nei test**: `Event::fake()` PRIMA di una seconda `userWithPermissions()` con un permesso
  DIVERSO nello stesso test rompe la cache Spatie (`created` di `Permission` intercettato da `Event::fake()`)
  — creare TUTTI gli utenti/permessi PRIMA di `Event::fake()`, poi fakare solo per l'azione da osservare.

## Reminder E7 (US-316, §7.5.2)

- **`WorkedTimeCalculator` NON è riusabile per "giorni lavorativi trascorsi senza attività"**: creato
  `App\Domain\Ticketing\Support\WorkingDaysCalculator::haveElapsed(...)`, riusa solo il principio
  (`Carbon::isWeekend()`), non la classe.
- **Un Mailable il cui trigger è un comando schedulato non ha bisogno di nessun listener**:
  `App\Domain\Mail\Actions\SendTicketWaitingReminderMail::run(Ticket $ticket)` chiamata direttamente da
  `TicketsRemindWaitingCommand::handle()` — nessun `AppServiceProvider::boot()` da toccare.
- **Cooldown senza colonna dedicata**: `EmailMessage::where('ticket_id', ...)->where('mailable_class',
  TicketWaitingReminderMail::class)->where('created_at', '>=', now()->subDays($cooldownDays))->exists()` —
  pattern riusabile per "non rimandare la stessa notifica due volte in una finestra X" (usato anche da
  E8/E11).
- **"Ultima attività rilevante" = `max()` fra due tabelle diverse**: `max(ticket_logs.occurred_at)` UNION
  `max(ticket_views.last_viewed_at)` (fallback a `tickets.created_at`) — wrappare in `Carbon::parse()`.
- Test che manipolano il tempo: `$this->travelTo('YYYY-MM-DD HH:MM:SS')`, mai `Carbon::setTestNow()` diretto.

## Bounce/DSN (US-319, §7.5.5)

- **`Webklex\PHPIMAP\Part::isAttachment()` tratta `message/delivery-status`/`message/rfc822` come
  "allegati"**: `ProcessDeliveryStatusNotification` itera `Message::getAttachments()` cercando per
  `getContentType()`.
- **Il Message-ID dell'email originale va estratto dal contenuto della parte `message/rfc822`** (regex sulla
  riga `Message-ID:`) — `Message::fromString()` non è riusabile lì. Confronto senza `<`/`>`.
- **Soglia soft bounce SENZA colonna aggiuntiva**: `email_suppressions.bounce_count` incrementato a ogni
  soft bounce, ma la riga sospende davvero l'invio solo quando `scopeActive()` la considera attiva. Sotto
  soglia, `expires_at = now()` (già scaduto per costruzione). Un hard bounce successivo sospende
  permanentemente (`expires_at = null`); un soft bounce dopo un hard bounce non deve mai retrocederlo.
- Nessun comando la richiama ancora — wiring reale è compito di US-326.

## Localizzazione reale delle comunicazioni (US-320, §7.6)

- **`App\Domain\Mail\Support\RecipientLocale::resolve(User $user): string`** è l'unico punto che decide la
  lingua: `users.locale` → prima `organizations.locale` dell'utente → `config('app.locale')`. Chiamato in due
  punti: (1) dentro `SendOutboundTicketMail::run()` (`Mail::to(...)->locale(RecipientLocale::resolve($recipient))
  ->queue(...)`); (2) in ogni Action/Listener che costruisce il subject come stringa PRIMA di chiamare
  `SendOutboundTicketMail::run()` — usare `__('chiave', [...], RecipientLocale::resolve($recipient))`.
- Chiavi in inglese (`lang/*.json`, la chiave è il fallback), `lang/it.json` traduce, `lang/en.json`
  (creato in questa story) ripete la chiave come valore.
- **Placeholder HTML in una traduzione**: la stringa resta puro testo con placeholder `:nome`, il VALORE
  porta l'HTML già escapato a mano (`'<strong>'.e($valore).'</strong>'`), output con `{!! __(...) !!}` — mai
  interpolare HTML dentro la CHIAVE. `Translator::makeReplacements()` non fa escaping automatico: un valore
  con dati utente deve sempre passare da `e()` prima.
- **Scope deliberatamente escluso**: `TicketStatus::getLabel()` (e altri label di enum di dominio, usati
  ovunque nel pannello) NON tradotto — impatto trasversale oltre "le comunicazioni" di §7.6. Stesso per le
  notifiche in-app Filament (titoli hard-coded).
- Test di completezza vero (non lista statica): `tests/Feature/Domain/Mail/LocalizationCompletenessTest.php`
  scansiona i file con una regex che estrae OGNI chiave `__()`/`trans()` e verifica esistenza+non-vuoto in
  ENTRAMBI `lang/it.json` e `lang/en.json`.

## Registro email — prima Filament Resource di Fase 3 (US-321, §7.7)

- `App\Filament\Resources\EmailMessages\EmailMessageResource` (index/view soltanto): `EmailMessage` ha
  Policy propria (`EmailMessagePolicy`, `Permission::EmailView`/`EmailManage`) risolta per convenzione —
  vedi `app/Filament/CLAUDE.md` per il pattern "Resource Policy-backed" vs "Resource senza Policy" (`RoleResource`).
- **Un `TextColumn`/`TextEntry::make('campo_array_cast')` NON riceve mai l'intero array in
  `formatStateUsing()`**: Filament invoca la closure una volta per ELEMENTO, mai una volta con l'array
  intero — un parametro `?array $state` va in `TypeError` non appena la riga ha più di un elemento. Fix:
  sempre `->state(fn (EmailMessage $record): string => implode(', ', $record->to ?? []))`.
- `to`/`cc`/`bcc` sono `array<int, string>` di soli indirizzi email: un filtro può usare
  `whereJsonContains('to', $email)`, portabile su Postgres e sqlite — mai una `LIKE`/cast Postgres-only.
- **Bug pre-esistente scoperto qui, NON del sottosistema email, resta APERTO**: `notifications.data` è
  `text` (non `json`/`jsonb`) — la query del badge di Filament usa `->>'format'`, Postgres risponde
  `SQLSTATE[42883]`. Rompe OGNI pagina autenticata su Postgres reale. **Vedi la nota completa nella radice
  del repo** (§ gotcha trasversali) — riverificato in ogni story successiva che tocca il pannello (US-322,
  US-323, US-324, US-326), MAI ancora corretto.

## Amministrazione email — azioni e quarantena (US-322, §7.3.8/§7.7)

- **`ApplyInboundEmail` ha DUE punti di ingresso**: `run()` (pipeline automatica) e
  `runForResolvedSender(EmailMessage, User)`. Qualunque azione amministrativa che assegna il mittente A MANO
  **deve** chiamare `runForResolvedSender()`, mai `run()` (che ri-deriva il mittente da `from_email` e
  vanifica l'assegnazione manuale — bug reale trovato scrivendo il primo test end-to-end).
- `PostTicketMessage` ha un 5° parametro opzionale `?EmailMessage $emailMessage = null` (mai scritto prima di
  questa story) — necessario per "collega a ticket" (risalire dal `TicketMessage` all'`EmailMessage`).
- **Reinvio di un outbound `failed`/`bounced` limitato, non generico**: `RetryOutboundEmailMessage` riaccoda
  solo i Mailable la cui unica dipendenza oltre a `$outbound` è il `Ticket` (whitelist esplicita
  `RESENDABLE_MAILABLES`) — per gli altri fallisce esplicitamente (`RuntimeException`).
- Nuova tabella `email_message_logs` (`EmailMessageLog`, `EmailMessageLogEvent`): audit trail per azioni
  amministrative sulle email, ruolo parallelo a `ticket_logs` — un'azione che tocca sia email sia ticket
  scrive solo su `email_message_logs`.
- `App\Filament\Pages\EmailQuarantine`: prima pagina Filament con tabella ma senza Resource (`Tables\Contracts
  \HasTable` + `InteractsWithTable`), `protected string $view = 'filament-panels::pages.page';` + un metodo
  `content(Schema $schema): Schema` con `[EmbeddedTable::make()]` — riusabile per qualunque pagina "solo
  tabella" senza Resource.

## Pagina Soppressioni/metriche (US-323, §7.5.5/§7.7)

- `App\Filament\Pages\EmailSuppressions`: `getHeaderWidgets()` (metodo nativo di `Filament\Pages\Page`,
  indipendente da `content()`) per un widget `StatsOverviewWidget` in testa, discoverato via
  `->discoverWidgets(...)`.
- **Gotcha per qualunque test Livewire su una custom `Page`**: `CanAuthorizeAccess::mount()` chiama
  `abort_unless(static::canAccess(), 403)` sempre — un test con `canAccess() === false` fallisce con 403 e un
  `->callTableAction()` successivo esplode con `Attempt to read property "mountedActions" on null`. Se
  `canAccess()` richiede solo un permesso ma un'azione di riga ne richiede un secondo, il test deve concedere
  ENTRAMBI.
- Bounce rate mai su `EmailStatus::Sent` (mai popolato, vedi US-311): `bounced / (bounced + queued)`.

## Voce di menu Mailpit (US-324, §7.7)

- **`phpdotenv` NON supporta `${VAR:-default}`** (sintassi bash): il resolver matcha solo `\A\${([a-zA-Z0-9_.]
  +)}`, nessun `:-`. Usare `${VAR}` senza default, garantendo che quella variabile sia già definita PRIMA
  nello stesso file.
- `App\Filament\Navigation\MailpitNavigationItem::isVisible()`/`::url()`: classe pura testabile con
  `app()->instance('env', ...)` + `config([...])`, senza toccare autenticazione — evita di iterare
  `Filament::getNavigation()` (fragile, richiederebbe permessi per ogni Resource esistente).
- Un `NavigationItem` senza `->sort()` ha `getSort() === -1`, identico al default di Resource/Page — con un
  pareggio, l'ordine finale rispecchia l'ordine di INSERIMENTO: registrare via `->navigationItems()` (nel
  costruttore di `NavigationManager`) basta per farlo comparire per primo, senza `->sort()` esplicito.

## Comando `mail:retry-failed` (US-325, §7.3.3)

- **Nessuna nuova logica di reinvio**: thin wrapper su `RetryOutboundEmailMessage::run()` (già esistente da
  US-322). Attore: `User::system()` (vedi `app/Domain/CLAUDE.md`).
- **Verificare SEMPRE se un feature flag/env è già stato provisionato speculativamente** prima di
  aggiungerne uno nuovo: `ENABLE_MAIL_RETRY_FAILED` esisteva già da Fase 0, mai wired fino a questa story.
  `ENABLE_MAIL_DIGEST` è ancora orfano (usato poi da US-614).
- **Il comando NON si ferma su un singolo messaggio bloccato/non ricostruibile**: try/catch per-messaggio
  dentro il `foreach`, mai attorno all'intero batch.

## Checkpoint di fine Fase 3 (US-326, §14)

- **`mail:fetch-inbound` ora orchestra l'INTERA pipeline sincrona**: dopo `StoreRawInboundEmail::run()`, ogni
  messaggio appena archiviato passa da `ParseInboundEmail` → `ClassifyInboundEmail` → (in base all'esito)
  `ProcessDeliveryStatusNotification` o `ApplyInboundEmail`. `email_messages.status` alla fine NON è più
  sempre `received` (un mittente senza corrispondenza finisce `quarantined`).
- **Gotcha del manifest di collaudo con apostrofo nel nome del test** — vedi `docs/collaudo/CLAUDE.md`.
- **`collaudo:generate`/`collaudo:verify-manifest` richiedono `pdflatex`+`csquotes.sty`**, assenti sull'host
  locale — eseguire dentro il container Docker `app` (bind mount, nessun rebuild necessario). Vedi
  `docs/collaudo/CLAUDE.md`.

## Digest periodico E8 (`mail:send-digest`, US-614, §7.5.2/§10.2)

- Estende `OutboundMailable` direttamente (nessun `Ticket` singolo, come E9): `ticket: null` accettato da
  `SendOutboundTicketMail::run()`. Contenuto aggregato in un DTO immutabile dedicato
  (`App\Domain\Mail\Support\TicketDigestEntry`: ticket + conteggio nuovi messaggi + eventuale cambio stato).
- **Query "tutti gli utenti con un dato ruolo" mai con lo scope `role()` di Spatie** (lancia
  `RoleDoesNotExist` se il ruolo non esiste ancora) — `whereHas('roles', fn ($q) => $q->where('name',
  UserRole::Customer->value))`.
- Idempotenza "un digest al giorno per cliente": stesso pattern del cooldown E7, ma su `today()` (giorno di
  calendario) — distinto dalla finestra di contenuto "24h scorrevoli" (`now()->subHours(24)`), le due
  finestre non vanno confuse.
- Soppressioni/preferenze/deactivated_at restano un'unica responsabilità di
  `SendOutboundTicketMail::blockedReason()` — nessun controllo aggiuntivo nel comando.

## Report attività pronto E10 (`ActivityReportPdfGenerated`, US-615, §7.5.2)

- Evento che scatta SOLO alla PRIMA valorizzazione di `ActivityReport.pdf_generated_at`: catturare
  `$isFirstGeneration = $report->pdf_generated_at === null;` PRIMA dell'`update([...])`, dispatch dopo — mai
  `wasChanged()`/`getOriginal()` post-update. Arriva gratis su entrambi i percorsi (rigenerazione manuale e
  `reports:generate-monthly`) perché passano dall'unico punto `GenerateActivityReportPdf::run()` — vedi
  `app/Domain/Reporting/CLAUDE.md` per il resto della pipeline PDF.
- Mailable verso TUTTI i membri di un'organizzazione owner (`ActivityReportOwnerKind::Organization`): iterare
  `$report->ownerOrganization->users` e chiamare `SendOutboundTicketMail::run()` una volta per destinatario —
  nessuna infrastruttura di invio multiplo dedicata. `Mail::assertQueued(Class::class, $count)` (secondo
  argomento intero) verifica il numero di invii.
- Gotcha Larastan: `$report->ownerOrganization?->users` (nullsafe su una `BelongsTo` annotata senza `|null`)
  produce `nullsafe.neverNull` — verificare il tipo annotato prima di aggiungere un nullsafe "difensivo".

## Promemoria interno E11 (`tickets:notify-idle-developers`, US-616, §7.5.2/§10.2)

- Vedi `app/Domain/Ticketing/CLAUDE.md` §US-616 per la query "developer idle" e la fascia oraria applicativa.
- Idempotenza "un promemoria al giorno per developer": stesso pattern di E7/E8 (`EmailMessage::where(...)
  ->where('created_at', '>=', $todayStart)->exists()`).
