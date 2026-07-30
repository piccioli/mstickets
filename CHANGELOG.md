# Changelog

Tutte le modifiche rilevanti a questo progetto sono documentate in questo file.

Il formato è basato su [Keep a Changelog](https://keepachangelog.com/it/1.1.0/),
e il progetto aderisce a [Semantic Versioning](https://semver.org/lang/it/).

## [0.3.2] - 2026-07-30

**Motore PDF di collaudo su LaTeX**, in sostituzione di dompdf/HTML, con la carta
intestata ufficiale Montagna Servizi (classe `montagnaservizi.cls`).

### Aggiunto

- `App\Support\Latex\{LatexEscaper,LatexPdfCompiler,MarkdownToLatexConverter}`: motore di
  generazione PDF via pdfLaTeX (TeX Live), con compilazione multi-passata a convergenza
  sul conteggio pagine e gestione errori con log di compilazione.
- Classe LaTeX brandizzata `resources/latex/montagnaservizi.cls` (copertina, tabelle,
  box nota/attenzione/requisito, elenchi numerati a fasi, firme, tabelle multi-pagina) —
  importata da un progetto Claude Design dedicato e corretta (6 costrutti non
  compilavano nella versione originale, vedi CLAUDE.md per i dettagli).
- TeX Live nell'immagine Docker di sviluppo (`docker/php/Dockerfile`) e in CI — non in
  produzione/UAT, dove `collaudo:generate` non viene mai eseguito.

### Rimosso

- Dipendenza `barryvdh/laravel-dompdf`, non più usata da nessuna parte dell'applicazione.

### Modificato

- Sia il PDF di collaudo sintetico sia quello dettagliato sono ora generati da sorgente
  LaTeX invece che da viste Blade/HTML renderizzate con dompdf.

## [0.3.1] - 2026-07-30

**Rifiniture pixel-perfect di login e recupero password** rispetto al mockup del committente, a
completamento della feature rilasciata in 0.3.0.

### Corretto

- Layout auth (login/recupero password) estratto in un componente Blade condiviso (`components/auth/panel`).
- Card flottante bianca del pannello form: contenitore e sfondo del pannello coerenti col mockup.
- Colore bianco del titolo hero.
- Caricamento del font Manrope diagnosticato e corretto.
- Respiro verticale logo → eyebrow → titolo nel pannello hero (desktop).
- Border-radius degli input coerente col design.
- Eyebrow "ACCEDI" in maiuscolo.
- Peso tipografico delle label Email/Password.
- Bottone "Accedi con l'account CAI" disattivato, con modale informativa.

## [0.3.0] - 2026-07-27

**Landing page, login e recupero password** con l'identità visiva pubblica "Montagna Servizi"
(verde pino/Manrope), distinta dal tema del pannello (teal/Nunito Sans, invariato).

### Aggiunto

- Landing page pubblica su `/`: identità visiva Montagna Servizi, una sola call to action verso il
  login del pannello; un utente già autenticato viene rimandato direttamente alla dashboard.
- Pagina di login del pannello (`/admin/login`) con il design fornito dal committente (layout split
  desktop/mobile, foto/gradiente, vantaggi), autenticazione nativa Filament invariata (rate
  limiting, remember-me, eventi).
- Flusso reale di recupero password (richiesta → email via Mailpit → nuova password), con le stesse
  regole reali (min 8 caratteri, maiuscola, numero) riflesse nell'indicatore di forza visivo.
- `lang/it.json`: traduzioni italiane delle stringhe core di Laravel usate dalla notifica di reset
  password (assenti di default nel framework).
- Sezione di collaudo Fase 1A (`docs/collaudo/04-fase-1a.md`, manifest dedicato
  `fase-1a.php`): 16 nuovi casi di test, stessa struttura rigorosa di Fase 0/Fase 1. Documento
  PDF completo aggiornato (146 test totali, 27 argomenti).

### Corretto

- `APP_LOCALE` non era mai stato impostato esplicitamente (default Laravel `en`): messaggi di
  errore nativi e email di sistema risultavano in inglese nonostante il progetto sia interamente in
  italiano.

## [0.2.0] - 2026-07-26

**Fase 1 — Ticketing core** (PRD-ORCHESTRATOR-V2.md, §14) e prima infrastruttura di **CD/CI verso UAT**.

### Aggiunto

- Macchina a stati dichiarativa del ticket (`TicketStateMachine`), con attori/guard/effetti tabellari
  (§6.1.3) — nessun `if` sparso per le transizioni di stato.
- Regole di validazione di dominio come `ValidationRule` esplicite (A3), non eccezioni generiche.
- Action di dominio (`CreateTicket`, `ChangeTicketStatus`, `AssignTicket`, ...) con eventi, `ticket_logs`
  e demozione automatica al singolo ticket "in lavorazione" per assegnatario (§6.1.4).
- Propagazione esplicita dello stato ai ticket figli (`ApplyStatusToChildren`, decisione Q5).
- Regole di record-ownership nella `TicketPolicy` (livello 2, §9.5): viste per ruolo/rapporto col ticket.
- Conversazione del ticket (`ticket_messages`), sanitizzazione HTML, regola T7 di riapertura automatica.
- Allegati sui messaggi con disco privato dedicato, sanitizzazione SVG, download autorizzato (§9.6/§17.2).
- Tracciamento visualizzazioni (`ticket_views`) e calcolo ore lavorate (`WorkedTimeCalculator`, §6.2.2,
  decisione Q15).
- `TicketResource` Filament completo: campi, viste come query object, filtri, azioni di transizione
  dinamiche, vista di lavoro essenziale (`WorkBoard`) e landing per ruolo.
- Verifica end-to-end di Fase 1 (US-114): ciclo di vita completo del ticket con Action reali in sequenza.
- Processo di collaudo obbligatorio per ogni fase futura: manifest di tracciabilità
  (`docs/collaudo/fase-*.php`) verificato automaticamente in CI, comando `collaudo:generate`, manuale
  operativo dettagliato in stile ISO/IEC/IEEE 29119 per Fase 0 e Fase 1 (130 casi di test).
- Ambiente UAT pubblico su infrastruttura condivisa (msuat): `https://ticket-uat.montagnaservizi.com`
  (app, FrankenPHP) e `https://mailpit-ticket-uat.montagnaservizi.com` (Mailpit, Basic Auth), con
  certificati Let's Encrypt reali, `UatSeeder` dedicato eseguito ad ogni deploy.
- Pipeline CD (`deploy-uat.yml`): build+push immagine su GHCR e deploy via chiave SSH dedicata con
  `command=` forzato, attivata automaticamente al push su `develop`.

### Corretto

- CI: build degli asset front-end mancante prima di Pest (`ViteManifestNotFoundException` su ogni test
  che renderizza una vera pagina Filament).
- Health check ereditato dall'immagine base FrankenPHP che controllava una porta amministrativa mai
  esposta, causando un falso stato "unhealthy" in produzione UAT.

### Verificato

- Primo deploy end-to-end reale su UAT tramite la pipeline CD: build, push, deploy via SSH, migrazione e
  seed, entrambi gli endpoint pubblici raggiungibili con certificato valido.

[0.3.0]: https://github.com/piccioli/mstickets/releases/tag/v0.3.0
[0.2.0]: https://github.com/piccioli/mstickets/releases/tag/v0.2.0

## [0.1.0] - 2026-07-26

Prima release: **Fase 0 — Fondazioni** della riscrittura di Orchestrator (PRD-ORCHESTRATOR-V2.md, §14).
Nessuna funzionalità utente in questa release: pone le basi tecniche su cui verrà costruito il Ticketing
core nella Fase 1.

### Aggiunto

- Scaffold del progetto Laravel 13 + Filament 4 con struttura a moduli di dominio (`Domain/`, `Import/`,
  `Filament/`, `Support/`), Pest 4, Pint, Larastan livello 6.
- Stack Docker Compose completo (`app`, `web`, `db`, `redis`, `queue`, `scheduler`, `mailpit`), con
  healthcheck su tutti i servizi, nessun container come root, build-time UID/GID.
- Pipeline CI di base (Pint, Larastan, Pest, build dei container).
- Importazione del design system Montagna Servizi (`docs/design-system.md`, `resources/css/theme.css`) e
  applicazione del tema al pannello Filament.
- `docs/design-inventory.md`: inventario completo di schermate/feature del mockup, classificate in-scope
  o fuori-scope per questa release.
- Servizio `db_legacy` (profilo Compose `etl`), `bin/load-v1-dump` e comando `v1:inspect` per l'ispezione
  del dump v1 prima di finalizzare lo schema.
- Schema di database v2 completo (PRD §5): Identità/Autenticazione, Ticketing, Tag/Documentazione,
  Rendicontazione, Fundraising, Sottosistema email, Infrastruttura di importazione.
- Enum `UserRole`/`Permission` e catalogo permessi completo (PRD §9.3).
- Seeder idempotente ruolo→permessi che materializza la matrice di autorizzazione (PRD §9.4).
- Policy deny-by-default per ogni modello di dominio.
- Gate di accesso al pannello per ruolo e UI minima di gestione ruoli/permessi.
- Comando diagnostico `orchestrator:doctor`.
- Seed di sviluppo con dati realistici su tutti i moduli in scope.
- Target `make setup` per il bootstrap completo dell'ambiente con un solo comando.

### Verificato

- `v1:inspect` eseguito su due dump di produzione reali (19/02/2026 e 26/07/2026): nessuna anomalia
  strutturale nuova, risultati coerenti su un volume dati quasi raddoppiato.
- Suite di qualità verde: Pint, Larastan (livello 6, 0 errori), Pest (257 test).

[0.1.0]: https://github.com/piccioli/mstickets/releases/tag/v0.1.0
