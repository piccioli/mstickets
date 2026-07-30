# PRD — Landing page, Login e Recupero password (v0.3.0)

> Riferimento: `PRD-ORCHESTRATOR-V2.md` (§9 Ruoli/permessi, §17 vincoli). Questo documento è un
> addendum contenuto ("F1A" nella numerazione del collaudo), non una nuova fase del roadmap §14.

## 1. Contesto

Ad oggi (v0.2.0):

- `GET /` restituisce la view Laravel di default `welcome.blade.php` (scaffold dell'installer, mai
  personalizzata).
- Il login è la pagina Filament di default (`Filament\Auth\Pages\Login`), tema teal del pannello
  (`resources/css/theme.css`, font Nunito Sans — design system "Piattaforma Montagna Servizi", §Design
  system in `CLAUDE.md`).
- Il recupero password **non è abilitato** sul pannello (`->login()` senza `->passwordReset()` in
  `AdminPanelProvider`).

Il committente ha fornito un secondo progetto di design (Claude Design,
`b41c13f4-8321-4716-be35-295d0bdd9d1e`, file `Login Montagna Servizi.dc.html`) che usa un **design
system diverso** da quello del pannello: bundle `montagna-servizi-design-system-5cc04e95-...`, verde
pino `#1D574B` (brand reale, campionato dal logo) e font **Manrope**, non Nunito Sans/teal. Nello stesso
bundle sono presenti componenti `Navbar`/`Hero`/`Footer` pensati per il sito pubblico/marketing.

**Decisione**: le pagine pubbliche (landing `/` e login/recupero password) adottano questo secondo
design system (verde pino, Manrope). Il pannello Filament post-login **non cambia**: resta il tema teal
già in produzione (US-004/US-005). Sono due superfici visivamente distinte per scelta del committente
(pubblico/marketing vs. applicativo interno), non un errore da uniformare.

## 2. Obiettivo

1. `GET /` diventa una landing page con lo stile Montagna Servizi (verde pino/Manrope), **una sola CTA**
   verso `/admin/login`. Se l'utente ha già una sessione attiva, viene rimandato alla pagina principale
   dell'applicativo (dashboard Filament), non vede la landing.
2. La pagina di login del pannello (`/admin/login`) adotta la grafica del file
   `Login Montagna Servizi.dc.html` (layout split desktop, stack mobile), con autenticazione reale
   (Laravel/Filament, non il mock JS del design tool).
3. Flusso di recupero password reale e funzionante, con la stessa identità visiva del mockup
   (richiesta email → email inviata → nuova password), verificabile in UAT via Mailpit.
4. Sezione di collaudo **F1A** (nuovi casi di test, stessa struttura rigorosa di F0/F1) per landing,
   login e recupero password, con aggiornamento del PDF di collaudo completo.

## 3. Fuori scope (esplicito)

- **Bottone "Accedi con l'account CAI"** presente nel mockup: nessuna integrazione SSO CAI esiste nel
  PRD né altrove nel progetto. **Omesso** dalla pagina reale (decisione presa con il committente):
  costruire un elemento che non fa nulla violerebbe il principio "mai comportamento non verificabile"
  già applicato al codice applicativo in questo progetto.
- Nessuna modifica al tema/branding del pannello Filament post-login (resta teal, US-004/US-005).
- Nessuna modifica alle regole di autorizzazione/ruoli esistenti (§9): il login autentica, non introduce
  nuovi permessi.
- Multi-factor authentication: fuori scope (il mockup non lo prevede, Filament lo supporterebbe ma non è
  richiesto).
- Registrazione self-service: fuori scope (`->registration()` non abilitata, coerente con §9.2 — utenti
  creati solo da seeder/admin).

## 4. Landing page `/`

**Route**: `GET /`, nessun middleware `auth` — deve essere raggiungibile da anonimo. Se
`Auth::guard('web')->check()`, redirect 302 a `Filament::getUrl()` (dashboard del pannello, che a sua
volta redirige per ruolo — US-113, già esistente, non toccato).

**Contenuto** (copy in italiano, brand voice consistente con l'hero del login già fornito dal
committente — "Piattaforma Servizi CAI"):

- Navbar (componente `Navbar` del design system: logo, nessun link di navigazione — non esiste un sito
  multi-pagina da linkare — solo la CTA "Accedi" a destra).
- Hero full-bleed (componente `Hero`): immagine di apertura (`assets/hero-alpine.svg`, lo stesso
  placeholder usato dal mockup di login — nessuna foto reale alpina è mai stata fornita, stesso gap già
  documentato in `docs/design-system.md` per i loghi), eyebrow "PIATTAFORMA SERVIZI CAI", titolo
  "Concentrati su ciò che conta: la montagna e la comunità." (stesso headline del login, per coerenza di
  brand tra le due pagine pubbliche), sottotitolo breve, **una sola CTA primaria** "Accedi" → `/admin/login`.
  Nessuna CTA secondaria (il mockup ne prevede una in `Hero`, ma il requisito esplicito è "una sola CTA").
- Footer (componente `Footer`: ragione sociale, sede legale, email/PEC — dati reali già presenti nel
  bundle design, verificati contro il footer del sito pubblico montagnaservizi.com).

**Nessun form, nessuna newsletter, nessun link di navigazione multiplo**: il requisito è "una sola CTA",
non una landing marketing completa. `NewsletterSignup` del design system non viene usato qui.

## 5. Login (`/admin/login`)

Estende `Filament\Auth\Pages\Login` (namespace `App\Filament\Auth\Pages\Login`) **solo per la view**:
tutta la logica reale di autenticazione (rate limiting, `remember`, redirect `intended`, eventi
`Attempting`/`Failed`, hook multi-factor anche se non usati) resta quella nativa Filament — non viene
duplicata a mano. Si sovrascrive `protected static string $view` con una view Blade dedicata che
riproduce il layout del mockup (split desktop 1.05fr/1fr, stack mobile) attorno al form reale
(`{{ $this->form }}` / `<x-filament-panels::form wire:submit="authenticate">`), con CSS scoped alla
sola pagina di login (non tocca il tema del pannello).

**Pannello sinistro (hero, nascosto sotto un certo breakpoint)**: stessa immagine/gradiente/copy del
mockup — logo bianco, eyebrow, titolo, sottotitolo, lista di 3 vantaggi (icona check + testo,
`Icon` component del design system, via lucide via CDN — coerente con `docs/design-system.md` §ICONOGRAPHY
già documentato per il pannello).

**Pannello destro (form)**: campi email/password del form reale di Filament (`TextInput` con
`->email()`/`->password()`/`->revealable()` — il toggle mostra/nascondi password del mockup è
esattamente `->revealable()`, nativo, non va reimplementato via JS), checkbox "Salva per le prossime
sessioni" (= `remember`, già nel form nativo), link "Recupera password" (`filament()->getRequestPasswordResetUrl()`,
già nativo — richiede `->passwordReset()` abilitato sul panel, vedi §6), CTA primaria "Accedi", link di
contatto verso `https://www.montagnaservizi.com/contatti` (testo statico, nessuna logica).

**Non incluso dal mockup**: il bottone SSO CAI (§3) e il divider "oppure" che lo introduce (non ha senso
un divider prima di un solo bottone alternativo che non esiste).

**Errore di credenziali**: gestito dalla `ValidationException` nativa di Filament
(`throwFailureValidationException()`), che il form già mostra sul campo email — nessun testo custom da
implementare, il messaggio è quello di Filament (localizzazione IT già presente in Laravel/Filament).

## 6. Recupero password

Richiede `->passwordReset()` in `AdminPanelProvider` (oggi assente). Due pagine reali Filament,
entrambe con view custom per la grafica del mockup — stessa logica: **estendere solo la view**, mai la
logica di dominio (broker `Password::`, notifica, hashing, rate limiting restano quelli nativi).

### 6.1 Richiesta (`App\Filament\Auth\Pages\RequestPasswordReset`)

Estende `Filament\Auth\Pages\PasswordReset\RequestPasswordReset`. Il mockup ha 3 "step" in una sola
schermata (1 richiesta, 2 email inviata, 3 nuova password) — il passo 3 è in realtà **un'altra pagina**
raggiunta dal link nell'email (non uno step della stessa sessione: l'utente può chiudere il browser e
aprire l'email su un altro dispositivo). Si riproduce quindi:

- **Step 1 → questa pagina, stato iniziale**: form email + CTA "Invia il link di recupero".
- **Step 2 → questa stessa pagina, dopo l'invio**: la view espone una proprietà pubblica
  `public bool $linkSent = false;`, impostata a `true` dopo una chiamata riuscita a `request()` (il
  metodo nativo resta quello di Filament — invocato via `parent::request()` o replicato 1:1 se il
  metodo nativo non è estendibile via override pulito; **mai** duplicare la chiamata a
  `Password::broker()->sendResetLink()`); la view Blade mostra condizionalmente il pannello "Controlla
  la casella" invece del form quando `$linkSent` è vero — stesso pattern del mockup (`rpIs1`/`rpIs2`),
  ma con stato reale lato server, non finto lato client.
- Il pulsante "Invia di nuovo" del mockup richiama di nuovo `request()` (stesso rate limiting nativo:
  `WithRateLimiting`, 2 tentativi — se l'utente supera il limite vede la notifica nativa di throttling,
  non un errore custom).

### 6.2 Nuova password (`App\Filament\Auth\Pages\ResetPassword`)

Estende `Filament\Auth\Pages\PasswordReset\ResetPassword` (raggiunta dal link nell'email, con
`email`+`token` in query string, già gestito nativamente da `mount()`). View custom con lo step 3 del
mockup: password + conferma password, indicatore di forza **puramente visivo lato client** (barra a 3
livelli — replica JS minimale equivalente al mockup, nessuna regola di sicurezza reale aggiuntiva: la
regola reale resta `PasswordRule::default()` già applicata dal campo nativo `password` — l'indicatore
visivo è un aiuto UX, non un secondo controllo di validazione), checklist regole (8+ caratteri,
maiuscola, numero — stesso calcolo JS lato client per il solo feedback visivo, la validazione reale
resta server-side su `PasswordRule::default()`).

**Nota tecnica esplicita**: `PasswordRule::default()` (Laravel) è più permissiva del set di regole
mostrato nel mockup (min 8, 1 maiuscola, 1 numero) — di default richiede solo 8 caratteri. Per far
coincidere il feedback visivo con la regola reale, `PasswordRule::default()` viene configurato in
`AppServiceProvider::boot()` (pattern standard Laravel, `Password::defaults(...)`) con
`->min(8)->mixedCase()->numbers()`, così l'indicatore di forza del mockup smette di essere solo
decorativo e riflette davvero cosa il server accetterà.

Dopo un reset riuscito: notifica nativa di successo + redirect alla pagina di login (comportamento
nativo di `PasswordResetResponse`, coerente col bottone "Vai al login" del mockup).

## 7. Design system e asset

- Font **Manrope** (Google Fonts, `@import` — stesso pattern già visto in `tokens/fonts.css` del
  bundle scaricato) caricato **solo** su `/`, `/admin/login`, le due pagine di recupero password — non
  sovrascrive il font Nunito Sans del pannello (rischio di regressione visiva sulle pagine già in
  produzione, esplicitamente da evitare).
- Nuovo file `resources/css/marketing.css` (o equivalente compilato via Vite, entry point separato dal
  tema Filament) con i token verde pino (`--green-*`, `--stone-*`, `--text-*`, ecc. — valori esatti
  presi da `tokens/colors.css`/`typography.css`/`spacing.css`/`effects.css` del bundle) e le classi di
  layout per landing/login/recupero — **non** tramite `DesignTokens`/`theme.css` (quello resta
  esclusivamente la fonte per il pannello, come già documentato in `CLAUDE.md`).
- Loghi: **riuso** di `assets/montagna-servizi-logo.png`/`-white.png`/`-mark.png` già presenti nel
  repository (`public/images/branding/`) — verificato byte-per-byte identici a quelli del nuovo bundle
  design, nessun nuovo download necessario.
- Nuovo asset `public/images/marketing/hero-alpine.svg` (placeholder illustrativo, stesso file del
  mockup — nessuna foto alpina reale è mai stata fornita dal committente, stesso gap già segnalato per
  altri asset in `docs/design-system.md`; se in futuro arriva una foto reale, sostituire solo questo
  file).
- Icone: Lucide via CDN (`unpkg.com/lucide`), stesso meccanismo già usato dal mockup e già documentato
  come sostituzione accettata in `docs/design-system.md` §ICONOGRAPHY.

## 8. Collaudo — sezione F1A

Nuovo file `docs/collaudo/04-fase-1a.md` (stessa struttura a template rigoroso di `02-fase-0.md`/
`03-fase-1.md` — Obiettivo, Riferimenti, Modalità di esecuzione, Priorità, Ruolo del tester,
Prerequisiti, Dati di test, Stato iniziale, Procedura di esecuzione, Risultato finale atteso, Controlli
negativi, Evidenze da acquisire, Criterio di superamento, Ripristino, Campi di consuntivazione), ID
`F1A-01` … `F1A-NN`. Argomenti minimi da coprire (elenco non esaustivo, il conteggio esatto emerge in
fase di stesura):

- Landing `/` raggiungibile da anonimo, CTA unica visibile, redirect a `/admin` se già autenticato.
- Login: aspetto conforme al design (manuale UI), credenziali corrette → accesso, credenziali errate →
  messaggio di errore, campo password `revealable`, checkbox "ricorda" mantiene la sessione dopo
  chiusura browser (**MANUALE UI + TECNICO DATABASE** — verifica `remember_token`).
- Rate limiting sul login (5 tentativi, **TECNICO CLI** o **MISTO** — dipende da come è verificabile in
  UAT senza bloccare l'account per il resto del collaudo).
- Recupero password: richiesta con email esistente → email ricevuta in Mailpit (**MANUALE UI +
  MAILPIT**), richiesta con email inesistente → stesso messaggio (nessuna enumerazione utenti — comportamento
  nativo di Filament, da verificare che non sia stato inavvertitamente rotto dalla view custom), link
  scaduto/già usato → errore gestito, nuova password rispetta le regole reali → reset riuscito e login
  con la nuova password.
- Aggiornamento manifest `docs/collaudo/fase-0-1.php` → **rinominare in `fase-0-1-1a.php`** o aggiungere
  un manifest dedicato (decisione implementativa: valutare in fase di stesura se estendere il file
  esistente o crearne uno nuovo, mantenendo `collaudo:verify-manifest` funzionante per entrambe le fasi).

**Aggiornamento del comando `collaudo:generate`**: la costante `DETAILED_FILES` in
`CollaudoGenerateCommand` guadagna la nuova sezione `04-fase-1a.md` (rinumerando i file successivi
esistenti — `04-registro-esiti.md` → `05-...`, `05-verbale-collaudo.md` → `06-...`, aggiornando i
riferimenti incrociati nei file `.md` esistenti). Nuova generazione del PDF completo
(`collaudo:generate 0-1a` o mantenendo l'argomento `0-1` se si decide di non introdurre un nuovo
identificativo di fase — da confermare in fase di stesura in base a cosa risulta più chiaro per il
tester) che includa **tutte** le sezioni esistenti più F1A, non solo la sezione nuova.

**Regola invariata** (dalla sessione precedente, vale per tutte le fasi future): ID esistenti (F0-*,
F1-*) non si toccano; comportamento non verificabile dal codice → "DA VERIFICARE CON IL PRODUCT
OWNER"; nessuna modifica al codice applicativo per far quadrare un test — se un test scopre un bug
reale, si segnala e si corregge il bug, non il test.

## 9. Versionamento

`v0.2.0` (Fase 1 + CD/CI) è già stato formalizzato retroattivamente (changelog + tag) prima di questo
lavoro. Questa feature chiude come **v0.3.0**: changelog dedicato, tag, PR verso `develop`, merge,
deploy automatico su UAT, verifica finale (inclusi i CSS — la landing/login non devono restare non
stilizzate per un manifest Vite mancante o un asset non copiato nell'immagine Docker, stesso tipo di bug
già incontrato durante il deploy del CD/CI).

## 10. Modalità di esecuzione

Implementazione diretta in questa sessione (subagent-driven-development: implementer + reviewer per
task, commit incrementali), nessun nuovo ciclo Ralph — coerente con la dimensione contenuta della
feature. Piano dei task in `plan.md` nella stessa cartella.
