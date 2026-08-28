# Integrazione dati RUNTS-CAI (Sezioni/Sottosezioni) — Design

**Data**: 2026-08-28
**Contesto**: Fase 8 del roadmap (PRD-ORCHESTRATOR-V2.md §14, dopo la chiusura di Fase 7 — Tipologia di
cliente CAI). Richiesta del committente: integrare in Orchestrator le funzionalità del prototipo
`/Users/alessiopiccioli/Documents/LAVORO/MS/SOFTWARE/RUNTS` (RUNTS-CAI), limitatamente a Sezioni e
Sottosezioni (non i Gruppi Regionali RUNTS).

## 1. Cos'è RUNTS-CAI (prototipo sorgente)

Applicazione Python (FastAPI + SQLite + scraper Playwright) che acquisisce dati sulle sezioni CAI dal RUNTS
(Registro Unico Nazionale del Terzo Settore, registro pubblico del Ministero del Lavoro) e li arricchisce con
un directory ufficiale CAI proprio. **I `docs/` del prototipo sono superati rispetto al codice reale** — la
verifica di questo design è stata fatta leggendo `scraper/db.py`/`web/app.py` direttamente, non i `docs/`.

Tabelle rilevanti in `runts.db` (SQLite):

| Tabella | Righe (dataset corrente) | Contenuto |
|---|---|---|
| `sezioni_cai` | 529 | Directory ufficiale CAI: contatti, indirizzo, anno fondazione, soci, coordinate, regione |
| `sottosezioni_cai` | 224 | Come sopra, FK a `sezioni_cai` |
| `enti` | 226 | Dati RUNTS grezzi (natura giuridica, data iscrizione, PEC, rappresentante legale, url scheda ufficiale) |
| `bilanci` | 74 | Bilanci estratti dai PDF RUNTS (oneri/proventi per categoria, risultato d'esercizio) |
| `cariche_sociali` | 0 | Cariche sociali (struttura pronta, dati non ancora estratti dal prototipo) |
| `allegati` | 235 (~224MB su disco in `attachments/`) | Metadati + file scaricati (bilanci/statuti PDF) |
| `gruppi_regionali_cai` | 21 | **Fuori scope per questa fase** |
| `geocoding_cache` | — | Cache interna dello scraper Python, non serve in Orchestrator |

**Aggancio verificato fra i due sistemi**: `sezioni_cai.cai_email`/`sottosezioni_cai.cai_email` coincidono
esattamente con `users.email` degli utenti clienti reali di Orchestrator (stessa fonte CAI). Verificato sul
dataset reale: **505 sezioni su 529 hanno un match diretto per email** con un utente Orchestrator esistente
(contro 184/226 per codice fiscale, molto meno affidabile). Nessuna nuova colonna su `users` necessaria per
il collegamento: si usa l'email già presente su entrambi i lati, case-insensitive.

## 2. Obiettivo

1. Importare in Orchestrator, da un **datapack** preparato una tantum, i dati di Sezioni e Sottosezioni CAI
   (directory ufficiale + registrazione RUNTS + bilanci + allegati), collegati per email agli utenti clienti
   esistenti dove un match esiste.
2. Esporre questi dati in sola consultazione:
   - **Staff**: nuova Filament Resource con lista/filtri/dettaglio/mappa/export su tutte le sezioni.
   - **Cliente Sezione** (Fase 7): i propri dati sulla propria `CustomerDashboard`.
   - **Cliente Gruppo Regionale** (Fase 7, card "Sezioni del gruppo regionale"): dettaglio completo di ogni
     sezione della propria regione, mai di altre regioni.
3. Rendere il datapack disponibile su UAT tramite una cartella non versionata sincronizzata via rsync, letta
   da un nuovo passo di import wired nel deploy esistente (che oggi fa sempre `migrate:fresh` ad ogni push su
   `develop` — senza questo passo i dati CAI andrebbero persi ad ogni deploy).

**Non obiettivi** (confermati col committente in brainstorming):
- `gruppi_regionali_cai` — nessuna tabella, nessuna UI.
- Refresh automatico/periodico del datapack — solo import iniziale, ricaricabile a mano quando serve (stesso
  modello di `v1dumps/latest.sql`).
- Report PDF per singola sezione (route `/ente/{id}/pdf` del prototipo) — introdurrebbe un secondo motore di
  generazione PDF oltre a `LatexPdfCompiler`, rimandato.
- Editing dei dati CAI/RUNTS da UI — sola consultazione, come il prototipo sorgente.
- Scraper/geocoder Python — restano nel prototipo RUNTS-CAI, non vengono portati/rieseguiti da Orchestrator.

## 3. Schema dati

Nuovo dominio `App\Domain\CaiDirectory`. Migrazioni additive, nuove tabelle:

| Tabella | Da | Chiave | Note |
|---|---|---|---|
| `cai_sections` | `sezioni_cai` | `codice_cai` (string, PK naturale) | `user_id` nullable FK `users` (match per email, case-insensitive) |
| `cai_subsections` | `sottosezioni_cai` | `cai_codice` (string, PK naturale) | FK `cai_section_id`; `user_id` nullable FK `users` (stesso match per email — Fase 7 non distingue Sezione/Sottosezione come customer_type, quindi una sottosezione con propria utenza è comunque `customer_type = Sezione`) |
| `cai_runts_registrations` | `enti` | `id_runts` (string) | FK `cai_section_id` nullable (solo righe con match CF↔sezione); righe senza match su nessuna sezione **non vengono importate** (fuori scope: sono altri enti RUNTS, non necessariamente CAI Sezioni) |
| `cai_financial_statements` | `bilanci` | — | FK `cai_runts_registration_id` |
| `cai_board_members` | `cariche_sociali` | — | FK `cai_runts_registration_id`; tabella vuota all'import (dataset sorgente non ha ancora questi dati) — struttura pronta per un futuro arricchimento del datapack, non un dato fittizio |
| `cai_documents` | `allegati` | — | FK `cai_runts_registration_id`; il file viene copiato nello storage privato esistente (stesso disco/pattern degli allegati ticket, Fase 1, download autorizzato) |

Nessuna tabella per `gruppi_regionali_cai`/`geocoding_cache`.

## 4. Datapack

- **Percorso**: `orchestrator/cai-datapack/` (nuova cartella, aggiunta a `.gitignore` — stesso principio già
  in uso per `v1dumps/`: dato esterno di grosse dimensioni, mai versionato con git).
- **Contenuto**: `runts-cai.sqlite` (copia potata di `runts.db` — solo le tabelle/righe pertinenti a
  Sezioni/Sottosezioni, `enti` filtrata alle sole righe con match CF su una `sezioni_cai`, niente
  `gruppi_regionali_cai`/`geocoding_cache`) + `attachments/` (potata ai soli file referenziati dalle righe
  `allegati` mantenute).
- **Preparazione**: script una tantum (vive nel repo RUNTS-CAI, non in Orchestrator — è quel progetto a
  conoscere lo schema sorgente) che produce il datapack potato da `runts.db`/`attachments/`. Eseguito a mano
  da un operatore quando si vuole preparare/rinfrescare il datapack — nessuna automazione ricorrente (§2).
- **Locale**: lo sviluppatore copia il datapack in `orchestrator/cai-datapack/` a mano (stesso principio di
  `v1dumps/latest.sql`).
- **UAT**: il datapack viene sincronizzato con un nuovo script locale `bin/push-cai-datapack` (rsync verso
  una cartella non versionata su msuat, es. `/root/ticket-uat/cai-datapack/`), eseguito da un operatore con
  accesso SSH **prima** di un deploy che deve riflettere un datapack nuovo/aggiornato — stesso principio
  "solo un umano tocca msuat" già stabilito per `v1dumps/latest.sql` e per `remote-deploy.sh` stesso.

## 5. Import in Orchestrator

- Nuovo comando `php artisan cai:import-datapack {--path=cai-datapack/runts-cai.sqlite}`: apre il file
  SQLite tramite una connessione DB dedicata in sola lettura (estensione `pdo_sqlite`/`sqlite3` già presente
  nell'immagine PHP, verificato), importa/upserta le righe nelle tabelle di §3, collega a `users` per email
  (case-insensitive), copia i file de `allegati` nello storage privato. Conforme a §10.1 del PRD principale:
  idempotente, `--dry-run`, log strutturato — stesso stile dei comandi già esistenti (`v1:import`,
  `tickets:*`).
- **Se il file datapack non esiste**: il comando termina con un messaggio esplicito (nessun errore criptico),
  stesso principio di `bin/load-v1-dump`.
- **Wiring**:
  - `make setup` (locale): eseguito **best-effort** dopo `v1:import` — se il datapack manca, logga un avviso
    e prosegue (stesso principio già stabilito per gli allegati ticket in
    `docs/superpowers/specs/2026-08-02-etl-real-data-seeding-design.md`).
  - `deploy/remote-deploy.sh` (UAT): eseguito **sempre** dopo `v1:import --anonymize`, leggendo dalla cartella
    rsync-ata (bind-mount in `docker-compose.uat.yml`, stesso pattern già in uso per `LEGACY_MEDIA_PATH`) —
    necessario perché ogni deploy fa `migrate:fresh` e altrimenti i dati CAI andrebbero persi ad ogni push su
    `develop`. **Nota operativa**: `remote-deploy.sh` è copiato a mano su msuat da un umano (mai sincronizzato
    automaticamente, per design esistente) — questa modifica richiede lo stesso passo manuale quando la PR
    viene mergiata.

## 6. UI Staff

- Nuova Filament Resource `CaiSectionResource` (namespace `App\Filament\Resources\CaiSections`), **sola
  consultazione** (nessuna azione Create/Edit/Delete): lista con filtri (regione, presenza bilanci/utente
  collegato), dettaglio con tab equivalenti al prototipo (dati CAI, dati RUNTS, bilanci, allegati scaricabili,
  sottosezioni collegate, mappa).
- Nuova pagina "Mappa sezioni" (Leaflet via CDN, stesso principio di dipendenza esterna già accettato nel
  pannello — verificare se già in uso altrove nel progetto prima di introdurre una nuova dipendenza CDN).
- Export CSV/XLSX/GeoJSON come azioni sulla tabella della resource.

## 7. UI Cliente

- **Cliente Sezione** (Fase 7, `CustomerDashboard`): nuova card con i propri dati CAI/RUNTS (contatti
  ufficiali, anno fondazione, soci, bilanci/allegati scaricabili, sottosezioni proprie) — visibile solo se
  esiste un `CaiSection`/`CaiSubsection` con `user_id` = utente corrente; altrimenti stato vuoto esplicito
  ("nessun dato CAI/RUNTS disponibile per la tua sezione", mai una card assente silenziosa).
- **Cliente Gruppo Regionale** (Fase 7, card "Sezioni del gruppo regionale"): ogni riga della card diventa
  cliccabile e apre una **pagina di dettaglio completa** per quella sezione (stesso contenuto che vede la
  Sezione per sé) — **scoped esclusivamente** alle sezioni della propria `region` (mai di un'altra regione,
  verifica esplicita lato server, non solo assenza di link in UI). I dati sono di fonte pubblica (RUNTS è un
  registro pubblico), quindi nessun campo va nascosto per sensibilità — la restrizione riguarda solo lo scope
  (propria regione), non i singoli campi.
- **Implementazione**: un solo componente/vista di dettaglio sezione riusato da tutti e tre i punti di
  accesso (Filament Resource per staff, pagina cliente Sezione, pagina cliente Gruppo Regionale) —
  l'autorizzazione (chi può vedere quale sezione) è responsabilità di ciascun punto di accesso, non del
  componente di presentazione.

## 8. Test previsti

- Unit: matching per email (case-insensitive, nessun match → `user_id = null`), idempotenza dell'import,
  `--dry-run` non scrive.
- Feature: Filament Resource (sola consultazione, filtri, dettaglio); dashboard Sezione (propria card, stato
  vuoto se nessun match); dashboard Gruppo Regionale (dettaglio sezione raggiungibile SOLO per sezioni della
  propria regione — un tentativo di accesso diretto a una sezione di un'altra regione via URL manipolato deve
  fallire, non solo essere assente dal link).
- Verifica in browser (screenshot Chrome headless) per staff, cliente Sezione con match, cliente Sezione
  senza match, cliente Gruppo Regionale.

## 9. Story previste (bozza, da rifinire in `tasks/prd-fase-8-*.md`)

1. Schema dati (`App\Domain\CaiDirectory`, 6 tabelle).
2. Comando `cai:import-datapack` (matching email, allegati su storage privato, idempotente).
3. Wiring in `make setup` e `deploy/remote-deploy.sh`/`docker-compose.uat.yml` + `bin/push-cai-datapack`.
4. Filament Resource staff (lista/filtri/dettaglio/allegati).
5. Pagina mappa + export CSV/XLSX/GeoJSON (staff).
6. Card + dettaglio su `CustomerDashboard` per cliente Sezione.
7. Card cliccabile + dettaglio scoped su `CustomerDashboard` per cliente Gruppo Regionale.
8. Checkpoint di fine fase — collaudo.
