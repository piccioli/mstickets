# PRD: Allineamento pixel-perfect del design di login/recupero password

## Riferimenti

- `reference-online.png` — screenshot della pagina `/admin/login` così com'è oggi in produzione/UAT.
- `reference-design.png` — screenshot del mockup Claude Design di riferimento (fonte di verità visiva).
- Confronto pixel-per-pixel completo fatto in sessione (crop e color-sampling con Pillow) prima di
  scrivere questo PRD: tutti i punti sotto derivano da quel confronto, non da un giudizio soggettivo.
- Feature precedente correlata: `docs/features/landing-login-recupero-password-v0.3.0/PRD.md` (§3
  "Fuori scope esplicito") — quella PRD aveva **deliberatamente omesso** il bottone "Accedi con
  l'account CAI" perché "costruire un elemento che non fa nulla viola il principio 'mai comportamento
  non verificabile'". Questo PRD **reintroduce** quel bottone, ma in una forma che rispetta comunque
  quel principio: il bottone è visivamente disattivato e il suo click produce un comportamento reale,
  deterministico e verificabile (una modale), non un no-op silenzioso.
- Sorgente HTML statica del mockup (solo riferimento testuale, non parte della build):
  `docs/features/landing-login-recupero-password-v0.3.0/Login-Montagna-Servizi-source.html`.

## Introduzione

La Fase 0 (v0.3.0) ha implementato login e recupero password con il design "Montagna Servizi", ma un
confronto pixel-perfect tra l'ambiente online e il mockup di riferimento ha rilevato diverse
discrepanze visive — alcune strutturali (manca il trattamento "card flottante" dell'intera schermata),
altre di dettaglio (colori, spaziature, tipografia). Questo PRD copre la correzione di quelle
discrepanze e la reintroduzione controllata del bottone "Accedi con l'account CAI", che nel mockup è
presente ma non è mai stato implementato.

Questo è un lavoro di **rifinitura visiva** su una feature già funzionante: nessuna logica di
autenticazione cambia. L'unico comportamento nuovo è il bottone CAI (disattivato + modale
informativa).

## Contesto tecnico (già verificato nel codebase, non va ri-scoperto)

- Stack: Filament 4 (non Livewire Volt/Vue), Blade + Alpine.js inline, CSS Tailwind v4 config-less +
  custom properties proprietarie in `resources/css/marketing.css` (token `--green-*`, `--stone-*`,
  alias `--text-*`/`--surface-*`/`--border-*`, `--radius-sm`(10px)/`--radius-md`(14px)/
  `--radius-card`(20px)/`--radius-pill`(999px), `--space-*`, font `--mkt-font-sans` = Manrope).
  **Non esiste** `tailwind.config.js`: i "token" sono queste custom property CSS, non classi Tailwind
  custom.
- Il layout a due pannelli **non è un componente Blade riusabile**: è markup duplicato in 3 view che
  condividono solo lo stesso `resources/views/filament/auth/layout.blade.php` e le stesse classi CSS:
  - `resources/views/filament/auth/login.blade.php`
  - `resources/views/filament/auth/request-password-reset.blade.php` (usa una variante
    `.mkt-auth__panel--compact` + uno step indicator)
  - `resources/views/filament/auth/reset-password.blade.php` (stesso schema + checklist forza password)
  Le pagine PHP corrispondenti sono in `app/Filament/Auth/Pages/{Login,RequestPasswordReset,
  ResetPassword}.php`.
- Nessun componente modale Blade/Alpine riusabile esiste nel repo. L'unico pattern di modale esistente
  è il sistema `Filament\Actions\Action::make(...)->modalHeading(...)` usato nelle Resource (es.
  `TicketTransitionActions`), pensato per un contesto Livewire/Resource — pesante e non necessario qui.
  La pagina di login usa già Alpine.js inline per il toggle mostra/nascondi password: un dialog Alpine
  leggero è coerente con lo stack già in uso in questa vista.
- `.mkt-auth { background: var(--stone-100); min-height: 100vh; ... }` è sia il contenitore dei due
  pannelli sia lo sfondo di pagina fusi in un solo elemento, senza margine né `border-radius`: è la
  causa strutturale dei punti 1 e 2 sotto.
- `.mkt-auth__title` ha **già** `color: var(--text-inverse)` (bianco) dichiarato in CSS — eppure online
  il titolo appare scuro. Non presumere che serva cambiare quel colore: investigare prima perché la
  regola esistente non vince (specificità/ordine di cascata con altri stili caricati, es. base
  Filament/reset che questa vista NON dovrebbe caricare — vedi commento in `layout.blade.php` sul perché
  `@filamentStyles` è escluso di proposito).
- `.mkt-auth__title` ha **già** `font-size: 52px` identico al mockup sorgente (`font-size:52px` in
  `Login-Montagna-Servizi-source.html`, stessa `max-width:520px`, stesso `line-height:1.1`). La
  differenza di a-capo osservata nello screenshot online (vedi punto 5) **non è quindi spiegabile con
  una differenza di font-size dichiarato**: l'ipotesi più probabile è un problema di caricamento del
  font Manrope (fallback a un font di sistema con metriche diverse, che altera la larghezza del testo e
  quindi gli a-capo). Va diagnosticata la causa reale, non "aggiustato" il font-size per far coincidere
  visivamente gli a-capo.
- Il bottone primario ha già una regola `.mkt-btn--primary:disabled { opacity: 0.5; cursor: not-allowed;
  }`, ma il bottone "Accedi" del form usa `wire:loading.attr="disabled"` — cioè si disabilita SOLO
  durante l'invio (richiesta Livewire in corso), mai per "campi vuoti". Il tono smorzato del bottone nel
  mockup è quindi probabilmente solo un artefatto del render statico del mockup, non uno stato
  interattivo intenzionale da replicare: non introdurre una logica di validazione client-side per
  disabilitare il bottone finché i campi sono vuoti (non esiste altrove nel form, che valida lato
  server al submit).

## Obiettivi

- Allineare pixel-perfect le pagine di login e recupero password al mockup di riferimento, per le
  discrepanze elencate nella sezione "Requisiti funzionali".
- Estrarre il markup del layout a due pannelli in un componente Blade condiviso, così che le correzioni
  si applichino una sola volta e restino automaticamente in sync tra login/recupero password/reset
  password.
- Reintrodurre il bottone "Accedi con l'account CAI" in forma visivamente disattivata, con un
  comportamento di click reale e verificabile (modale informativa), senza introdurre alcuna finta
  integrazione SSO.

## User Stories

### US-D01: Estrazione del layout auth in un componente Blade condiviso
**Descrizione:** Come sviluppatore, voglio che il markup del layout a due pannelli (pannello hero +
pannello form) viva in un unico componente Blade riusabile, così che le correzioni di stile richieste
da questo PRD si applichino una volta sola e restino sincronizzate tra login, richiesta reset password
e reset password, invece di dover essere duplicate in 3 file.

**Acceptance Criteria:**
- [ ] Nuovo componente Blade (es. `resources/views/components/auth/panel.blade.php` o equivalente,
      nome a discrezione dell'implementazione, coerente con le convenzioni Blade del progetto) che
      espone il markup strutturale condiviso (wrapper `.mkt-auth`, pannello hero con logo/eyebrow/
      headline/checklist/tagline, wrapper del pannello form)
- [ ] Il componente supporta, tramite prop/slot, sia la variante "estesa" del pannello hero (login: logo
      + eyebrow + headline lunga + checklist 3 benefit + tagline) sia la variante `--compact` già usata
      da `request-password-reset.blade.php`/`reset-password.blade.php` (pannello più basso, con step
      indicator al posto della checklist) — nessuna delle due varianti perde funzionalità esistente
      dopo l'estrazione
- [ ] `login.blade.php`, `request-password-reset.blade.php`, `reset-password.blade.php` usano tutti e
      tre il nuovo componente per la parte strutturale condivisa; il contenuto specifico di ciascuna
      pagina (form, step, testi) resta nella view della singola pagina
- [ ] Nessuna regressione visiva o funzionale sulle 3 pagine rispetto allo stato pre-refactor (a parte
      le correzioni intenzionali delle story successive)
- [ ] `composer run lint` (Pint) passa
- [ ] Verificare in browser (skill dev-browser o equivalente) tutte e 3 le pagine: login, richiesta
      reset password, reset password

### US-D02: Card flottante bianca (contenitore + sfondo pannello form)
**Descrizione:** Come utente, voglio che la schermata di login appaia come una card bianca con angoli
arrotondati su uno sfondo pagina grigio chiaro, coerente col mockup, invece che a piena pagina senza
margine né arrotondamento.

**Acceptance Criteria:**
- [ ] Lo sfondo di pagina (fuori dalla card) usa il token grigio chiaro già esistente (`--stone-100`,
      lo stesso oggi erroneamente applicato al pannello form)
- [ ] Il blocco `.mkt-auth` (pannello hero + pannello form insieme) è racchiuso in un contenitore con
      `border-radius: var(--radius-card)` su tutti e 4 gli angoli, un margine esterno rispetto ai bordi
      del viewport e un'ombra leggera (verificare se esiste già un token `--shadow-*` da riusare in
      `marketing.css`, altrimenti sceglierne uno coerente con gli altri `box-shadow` già presenti nel
      file)
- [ ] Il pannello destro (form) ha sfondo bianco (`var(--surface-page)` o `var(--surface-card)`, quello
      semanticamente corretto tra i due), non più il grigio ereditato da `.mkt-auth`
- [ ] Su viewport stretti (mobile, sotto il breakpoint 900px già esistente in `marketing.css`) il
      comportamento resta accettabile: non è richiesto che la card flottante sia visibile identica al
      desktop sotto quel breakpoint, ma non deve rompersi (nessun overflow orizzontale, nessun contenuto
      tagliato) — usare buon senso guardando come il mockup gestisce mobile, se non lo mostra esplicitamente
      mantenere la card a piena larghezza sotto 900px è accettabile
- [ ] Verificare in browser sia la vista login sia recupero password (il componente condiviso di US-D01
      deve riflettere la correzione su entrambe automaticamente)

### US-D03: Colore bianco del titolo hero
**Descrizione:** Come utente, voglio che il titolo "Concentrati su ciò che conta: la montagna e la
comunità." sia leggibile in bianco sullo sfondo verde scuro, come nel mockup, invece di apparire in un
verde scurissimo a basso contrasto.

**Acceptance Criteria:**
- [ ] Diagnosticare perché `color: var(--text-inverse)` già dichiarato su `.mkt-auth__title` non viene
      applicato nel rendering reale (ispezionare gli stili computati nel browser, verificare l'ordine di
      caricamento dei CSS e se qualche altra regola con specificità maggiore o dichiarata dopo sta
      vincendo la cascata)
- [ ] Il fix risolve la causa reale (es. ordine/specificità CSS), non un `!important` aggiunto alla
      cieca senza aver capito il motivo del conflitto
- [ ] Il titolo appare bianco (o quasi bianco, secondo il token `--text-inverse`) su schermo, con
      contrasto sufficiente sullo sfondo verde (verificare rapporto di contrasto minimo leggibilità,
      indicativamente AA su testo grande)
- [ ] Verificare in browser

### US-D04: Diagnosi e correzione del caricamento del font Manrope
**Descrizione:** Come utente, voglio che tutto il testo del pannello marketing (login/recupero
password) sia renderizzato col font Manrope previsto dal design system, così che dimensioni/a-capo del
testo coincidano col mockup invece di dipendere da un font di fallback del browser.

**Acceptance Criteria:**
- [ ] Verificare negli strumenti di sviluppo del browser (tab Network + Computed styles) se il font
      Manrope viene effettivamente scaricato e applicato agli elementi con `font-family:
      var(--mkt-font-sans)`, o se sta silenziosamente cadendo su un fallback di sistema
- [ ] Se il font non carica: identificare e correggere la causa reale (percorso asset errato, direttiva
      `@fonts('manrope')` non risolta, problema di build Vite, font non presente in `public/`, ecc. —
      dipende da cosa emerge dall'investigazione, non presumere la causa a priori)
- [ ] Dopo il fix, verificare che l'a-capo del titolo hero a risoluzione desktop standard corrisponda
      a quello del mockup ("Concentrati su ciò / che conta: la / montagna e la / comunità." con
      `font-size:52px`/`max-width:520px` già corretti in CSS — se dopo il fix del font l'a-capo ancora
      non corrisponde, allora e solo allora rivalutare font-size/line-height, non prima)
- [ ] Verificare in browser che titoli, label ("Email"/"Password") e testo del form usino visibilmente
      lo stesso font in tutta la pagina (nessun elemento con un font visibilmente diverso dagli altri)

### US-D05: Respiro verticale logo → eyebrow → titolo nel pannello hero (desktop)
**Descrizione:** Come utente, voglio che nel pannello sinistro il logo sia visivamente separato
dall'etichetta "Piattaforma Servizi CAI" e dal titolo sottostante, con lo stesso respiro del mockup,
invece di apparire incollato al logo.

**Acceptance Criteria:**
- [ ] A desktop (≥900px), il logo (`img.mkt-logo` dentro `.mkt-auth__panel-content`) ha un margine
      inferiore che crea distanza visibile prima del blocco eyebrow/titolo (oggi
      `margin-bottom: 0` a quel breakpoint elimina lo spazio) — il markup che mette l'eyebrow "Piattaforma
      Servizi CAI" subito prima del titolo H1 è già corretto e non va spostato, è solo la spaziatura che
      va corretta
- [ ] Il risultato visivo a desktop corrisponde al mockup: logo isolato in alto, poi spazio, poi il
      blocco eyebrow+titolo+sottotitolo+checklist+tagline
- [ ] Nessuna regressione sulla variante mobile (`.mkt-auth__panel-mobile-only`, non toccata da questo
      breakpoint)
- [ ] Verificare in browser a una risoluzione desktop reale (non solo devtools ridimensionati)

### US-D06: Border-radius degli input coerente col design
**Descrizione:** Come utente, voglio che i campi Email/Password abbiano lo stesso arrotondamento degli
angoli previsto dal design, per coerenza visiva con gli altri elementi della UI.

**Acceptance Criteria:**
- [ ] Confrontare il border-radius attualmente applicato a `.mkt-field .fi-input-wrp` (oggi
      `var(--radius-sm)`, 10px) con quanto risulta dal confronto visivo col mockup (`reference-design.png`)
      e scegliere il token `--radius-*` più vicino tra quelli già definiti in `marketing.css`
      (`--radius-sm`/`--radius-md`/`--radius-card`/`--radius-pill`) — non introdurre un nuovo valore hardcoded
- [ ] Il nuovo valore si applica a tutti i campi input del form (Email, Password, e i campi equivalenti
      nelle pagine di recupero/reset password se condividono la stessa classe `.mkt-field`)
- [ ] Verificare in browser

### US-D07: Eyebrow "ACCEDI" in maiuscolo
**Descrizione:** Come utente, voglio che l'etichetta sopra "Bentornato" sia in maiuscolo come nel
mockup ("ACCEDI"), non in stile Title Case ("Accedi").

**Acceptance Criteria:**
- [ ] `.mkt-auth__form-eyebrow` (o la classe equivalente) applica `text-transform: uppercase` (il
      lettering nel markup può restare "Accedi" nel Blade se già gestito altrove via CSS transform,
      oppure va scritto in maiuscolo nel testo — scegliere l'approccio già usato per l'eyebrow "PIATTAFORMA
      SERVIZI CAI" del pannello sinistro, che nel mockup è già scritta interamente in maiuscolo nel
      testo sorgente, per restare coerenti tra le due eyebrow della pagina)
- [ ] Verificare in browser

### US-D08: Peso tipografico delle label Email/Password
**Descrizione:** Come utente, voglio che le etichette "Email" e "Password" sopra i rispettivi campi
siano in grassetto pieno come nel mockup, per maggiore leggibilità e coerenza visiva.

**Acceptance Criteria:**
- [ ] Le label `.mkt-field label` usano un peso più marcato (verificare il token `--fw-*` più vicino al
      mockup tra quelli già usati altrove in `marketing.css`, es. `--fw-bold`/`--fw-extrabold`, invece
      dell'attuale `--fw-semibold`)
- [ ] Verificare in browser che il cambiamento non renda le label sproporzionate rispetto al resto del
      form

### US-D09: Bottone "Accedi con l'account CAI" disattivato + modale informativa
**Descrizione:** Come utente, voglio vedere l'opzione "Accedi con l'account CAI" nella pagina di login
(coerente col mockup), chiaramente disattivata visivamente, e voglio che cliccandola mi venga spiegato
che la funzionalità non è ancora disponibile, invece di non vedere alcuna opzione o di ottenere un
bottone che non fa nulla senza spiegazioni.

**Acceptance Criteria:**
- [ ] Sotto il bottone "Accedi" primario, aggiunto un separatore visivo con testo "oppure" (coerente col
      mockup) e un bottone secondario "Accedi con l'account CAI" con icona busta/email, stile outline
      pill (riusa `.mkt-btn--outline` come base)
- [ ] Il bottone CAI è **visivamente** disattivato: colori spenti/grigi (testo e bordo), chiaramente
      distinguibile dal bottone primario attivo — riusare per coerenza la stessa palette di grigi
      (`--stone-*`) già usata altrove nel file, non inventare nuovi colori
- [ ] **Importante**: il bottone NON deve avere l'attributo HTML `disabled` (che bloccherebbe
      nativamente il click e impedirebbe di aprire la modale) — deve restare un elemento realmente
      cliccabile e raggiungibile da tastiera. Usare `aria-disabled="true"` per comunicare lo stato agli
      screen reader mantenendo il click funzionante, e `cursor: not-allowed` via CSS per il feedback
      visivo del mouse
- [ ] Al click, si apre una modale con: titolo "Funzionalità non disponibile", corpo "L'accesso con
      l'account CAI non è ancora disponibile. Continua ad utilizzare email e password per accedere.",
      un bottone "Chiudi"
- [ ] La modale si chiude cliccando "Chiudi", cliccando fuori dalla modale (backdrop) o premendo Esc; al
      chiudersi il focus torna sul bottone che l'ha aperta
- [ ] Implementazione con Alpine.js inline (stesso pattern già usato in questa vista per il toggle
      mostra/nascondi password), stile con le classi/token già esistenti in `marketing.css`
      (`--surface-card`, `--radius-card`, uno `--shadow-*` esistente) — non introdurre una libreria di
      terze parti per un singolo dialog
- [ ] Markup accessibile minimo: `role="dialog"`, `aria-modal="true"`, la modale ha un heading collegato
      via `aria-labelledby`
- [ ] Il bottone e la modale sono presenti solo nella pagina di login (non in recupero/reset password,
      dove il mockup non li prevede)
- [ ] Verificare in browser: aspetto del bottone disattivato, apertura/chiusura modale con mouse,
      chiusura con Esc, chiusura cliccando il backdrop

## Requisiti funzionali (riepilogo)

- FR-1: Il layout a due pannelli di login/recupero password/reset password vive in un componente Blade
  condiviso (US-D01).
- FR-2: L'intera schermata di login/recupero password è racchiusa in una card bianca con angoli
  arrotondati (`--radius-card`), margine esterno su sfondo `--stone-100`, ombra leggera (US-D02).
- FR-3: Il pannello form ha sfondo bianco, non grigio (US-D02).
- FR-4: Il titolo hero "Concentrati su ciò che conta..." è renderizzato in bianco con contrasto
  sufficiente (US-D03).
- FR-5: Il font Manrope è verificato/corretto nel caricamento; l'a-capo del titolo hero coincide col
  mockup dopo il fix (US-D04).
- FR-6: A desktop, il logo del pannello sinistro ha respiro visivo rispetto al blocco eyebrow/titolo
  sottostante (US-D05).
- FR-7: Gli input hanno un border-radius allineato al mockup, scelto tra i token esistenti (US-D06).
- FR-8: L'eyebrow "Accedi" del form è in maiuscolo (US-D07).
- FR-9: Le label "Email"/"Password" hanno un peso tipografico più marcato (US-D08).
- FR-10: Un bottone "Accedi con l'account CAI" visivamente disattivato ma cliccabile appare sotto il
  bottone "Accedi" primario nella sola pagina di login; il click apre una modale informativa "funzionalità
  non disponibile" (US-D09).

## Non-Goals (fuori scope esplicito)

- Nessuna integrazione SSO reale con l'account CAI: il bottone non autentica nessuno, in nessuna forma.
- Nessuna nuova logica di validazione client-side che disabiliti il bottone "Accedi" primario quando i
  campi sono vuoti: il tono smorzato visto nel mockup è trattato come artefatto del render statico (vedi
  "Contesto tecnico"), non come requisito funzionale.
- Nessuna modifica alla logica di autenticazione, rate limiting, o alle regole di validazione esistenti
  di Filament: questo PRD è solo visivo, salvo il nuovo bottone CAI (che non autentica nulla).
- Nessun redesign del comportamento responsive/mobile oltre a "non deve rompersi": il mockup di
  riferimento è solo desktop (1440×900), non è richiesta una card flottante identica sotto il breakpoint
  mobile.
- Nessuna modifica alla pagina "reset-password" (step 3) oltre a quanto eredita automaticamente
  dall'estrazione del componente condiviso (US-D01): non aggiungere il bottone CAI né altri elementi
  specifici del login a quella pagina.

## Considerazioni tecniche

- Tutte le story che toccano CSS devono riusare i token già definiti in `resources/css/marketing.css`
  (colori, radius, spaziature, font-weight): nessun valore hardcoded nuovo se un token esistente è
  sufficientemente vicino. Se davvero manca un token necessario, aggiungerlo alla sezione dei token in
  cima al file (non in mezzo alle regole), seguendo la convenzione di naming già in uso.
- `resources/css/theme.css` (token `--ms-*`, tema del pannello Filament admin) è un sistema di design
  **completamente separato** e non va toccato né confuso con `marketing.css`: questa pagina carica solo
  `marketing.css` (vedi commento in `layout.blade.php`).
- Le pagine coinvolte non caricano `@filamentStyles` (deliberatamente, vedi commento in
  `layout.blade.php`): qualunque ipotesi di "conflitto di specificità CSS" (US-D03) deve partire da cosa
  è effettivamente caricato in `<head>` per queste view, non da un'assunzione sul tema Filament di
  default.
- Ordine di lavoro consigliato: US-D01 (estrazione componente) prima di tutte le altre, così le
  correzioni successive si scrivono una sola volta nel componente condiviso invece che 3 volte.
  US-D02/US-D03/US-D04/US-D05 sono tutte nel pannello/contenitore condiviso e possono seguire in
  qualunque ordine tra loro. US-D09 (bottone CAI) è indipendente e può essere fatta in qualunque momento
  dopo US-D01 (visto che va solo nella view di login, non nel componente condiviso).

## Success Metrics

- Le pagine di login e recupero password, confrontate visivamente con `reference-design.png` a
  risoluzione desktop, non presentano più nessuna delle 9 discrepanze elencate sopra.
- Nessuna regressione: i test Pest esistenti su autenticazione/reset password continuano a passare
  invariati (questo PRD non tocca la logica, solo la vista).
- Il bottone CAI è visibile, chiaramente non confondibile con un'azione realmente disponibile, e il suo
  click produce sempre la modale informativa (mai un errore silenzioso o un redirect).

## Open Questions

- Il valore esatto di `--shadow-*` da usare per l'ombra della card flottante (US-D02) non è stato
  determinato a priori: va scelto tra quelli già dichiarati in `marketing.css` confrontando il risultato
  visivo con `reference-design.png`, non introdotto come nuovo valore salvo necessità reale.
- Se, dopo il fix del font Manrope (US-D04), l'a-capo del titolo hero continuasse a non coincidere col
  mockup, valutare se la causa è invece il fatto che il mockup è un frame statico a larghezza fissa
  1440px mentre il pannello reale è fluido: in tal caso l'a-capo può legittimamente differire a
  risoluzioni diverse dai 1440px del mockup, e non va forzato un `max-width` che comprometta la resa a
  risoluzioni desktop più larghe (già oggetto di un commento esplicito in `marketing.css` sulla colonna
  form).
