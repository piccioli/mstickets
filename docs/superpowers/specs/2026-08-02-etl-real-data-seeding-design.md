# Seeding con dati reali (ETL) per locale e UAT — Design

**Data**: 2026-08-02
**Contesto**: fine Fase 2 (ETL, PRD-ORCHESTRATOR-V2.md §11/§14), prima di iniziare la Fase 3. Richiesta del
committente: locale e UAT devono riflettere la situazione dati dell'ultimo dump reale (via ETL), non più un
seed fittizio.

## 1. Obiettivo

Sostituire `DevelopmentSeeder` (locale, Fase 0/US-023) e `UatSeeder` (UAT, Fase 1/CD-CI) con l'esecuzione
reale di `v1:import --anonymize` (già esistente, Fase 2) su entrambi gli ambienti. Nessun dato fittizio
residuo: chi lavora in locale o guarda UAT vede sempre dati reali (anonimizzati) del dump più recente
disponibile.

**Non obiettivi**: aggiornamento automatico del dump dalla pipeline (resta un passo umano, deciso
esplicitamente — vedi §4); riscrittura dei manifest di collaudo (`docs/collaudo/*`), che il committente
aggiorna direttamente; qualunque cambiamento allo schema v2 o agli stage ETL esistenti.

## 2. Convenzione del dump corrente: `v1dumps/latest.sql`

Oggi si sceglie il dump più recente a mano confrontando le date nei nomi file
(`production_dump_YYYYMMDD_HHMMSS.sql`, vedi nota in `CLAUDE.md` sezione ETL). Introduco un puntatore fisso
`v1dumps/latest.sql` (symlink al dump reale più recente, o copia — a scelta di chi lo prepara), stessa
convenzione riusata identica sia in locale sia su UAT: qualunque script che deve "usare l'ultimo dump" legge
sempre questo path fisso, mai un pattern di data.

Aggiornare `v1dumps/latest.sql` resta un passo **manuale**: un umano con accesso SSH a produzione esegue
`bin/load-v1-dump` (locale) / lo stesso comando sull'host msuat (UAT) quando vuole rinfrescare i dati. Nessuno
script automatico (né `make setup` né il deploy UAT) recupera da solo un dump nuovo da produzione.

## 3. Locale — `make setup`

Nuovo comportamento del target `setup` in `Makefile`:

1. **Prerequisito bloccante**: se `v1dumps/latest.sql` non esiste, il target si ferma con un messaggio
   esplicito che spiega come ottenerlo (`bin/load-v1-dump path/to/dump.sql` dopo aver scaricato il dump da
   produzione via `scp`, come già documentato in `CLAUDE.md`).
2. Porta su anche `db_legacy` (oggi richiede `--profile etl` a parte via `make etl-up`): incorporato nel
   target `setup`, non più un passo separato per questo flusso.
3. Carica `v1dumps/latest.sql` in `db_legacy` con `bin/load-v1-dump` (operazione locale, nessun accesso a
   produzione: il file è già sulla macchina dello sviluppatore).
4. `migrate --force` → `db:seed --class=RolePermissionSeeder --force` → `php artisan v1:import --anonymize`.
5. Gli allegati restano **best-effort**, non un prerequisito bloccante: se `storage/app/v1-media/` è vuota
   (lo sviluppatore non ha ancora lanciato `bin/fetch-legacy-media`), l'ETL prosegue e segnala i media come
   compromesso (comportamento già esistente di `TicketAttachmentsStage`, US-211/US-219) — non fallisce il
   setup.
6. Stampa a fine setup l'email anonimizzata + password dell'account di collaudo per ruolo (§5), stesso
   principio di "credenziali stampate a fine setup" già in `CLAUDE.md`.

**Rimozione**: `DevelopmentSeeder` (classe, test, riferimento in `DatabaseSeeder`) viene eliminato per
intero. Nessun fallback fittizio se il dump manca: il target fallisce esplicitamente (già deciso, punto 1).

## 4. UAT — infrastruttura e deploy

- **Nuovo servizio `db_legacy` in `docker-compose.uat.yml`**, sempre attivo (a differenza di locale, dove è
  dietro un profilo: qui serve a *ogni* deploy, non a una sessione ETL occasionale). Stesso pattern del
  servizio locale (`postgres:16-alpine`, volume dedicato `db_legacy_data`), nuove variabili
  `DB_LEGACY_*` in `.env.uat`/`.env.uat.example`.
- **Nuovo volume/percorso per gli allegati** (`LEGACY_MEDIA_PATH`) montato nel container `app` di UAT.
  L'immagine UAT oggi non monta l'intero repo (a differenza di locale): serve un bind-mount dedicato su un
  percorso del disco del server msuat, popolato da un umano con `bin/fetch-legacy-media` — eseguito dal
  proprio laptop (che ha già l'accesso SSH a produzione) seguito da un `scp`/`rsync` verso msuat, **mai**
  eseguito direttamente da msuat verso il server di produzione (eviterebbe un secondo salto SSH/una seconda
  relazione di fiducia da configurare).
- **`v1dumps/latest.sql`** va depositato anche sul server msuat da un umano con `bin/load-v1-dump` (stessa
  convenzione della §2), quando si vuole rinfrescare i dati di UAT.
- **`remote-deploy.sh` cambia**, da:
  ```bash
  docker compose ... exec -T app php artisan migrate --force
  docker compose ... exec -T app php artisan db:seed --class=UatSeeder --force
  ```
  a:
  ```bash
  docker compose ... exec -T app php artisan migrate:fresh --force
  docker compose ... exec -T app php artisan db:seed --class=RolePermissionSeeder --force
  docker compose ... exec -T app php artisan v1:import --anonymize
  ```
  con `db_legacy` sano (healthcheck) prima di procedere.
- **Ogni push su `develop` reimporta tutto da zero** (scelta esplicita, non dati persistenti tra un deploy e
  l'altro): più lento (l'ETL completo su ~4000 ticket richiede qualche minuto, non pochi secondi come il
  seed fittizio) ma uno stato sempre pulito e ripetibile identico al dump depositato. Un vero refresh dei
  dati (dump più recente) resta comunque un passo manuale separato (§2).
- **Rimozione**: `UatSeeder` (classe, test) viene eliminato per intero. I manifest di collaudo
  (`docs/collaudo/*`) che oggi referenziano le sue credenziali fisse restano **fuori da questo lavoro**: il
  committente li aggiorna direttamente.

## 5. Password e login di collaudo su dati anonimizzati

- **`Anonymizer` guadagna il reset della password**: quando `--anonymize` è attivo, la password di ogni
  utente importato viene sovrascritta con l'hash Laravel di una password nota e fissa (`password`, stessa
  convenzione già usata da `UatSeeder` oggi) — mai l'hash v1 reale copiato fuori produzione. Senza
  `--anonymize` (solo il cutover reale in produzione, Fase 7) la password resta quella v1 originale,
  invariata. Deterministico e idempotente come il resto dell'anonimizzazione (nessuna sorpresa a una
  seconda esecuzione).
- **Individuazione degli account di collaudo** (uno per ruolo: admin/developer/manager/customer/
  fundraising): l'anonimizzazione è deterministica per id v1 (`stesso utente v1 → stessa identità fittizia
  v2 in tutta l'esecuzione`, già documentato in `CLAUDE.md`). Il committente individua utenti reali già noti
  (es. il proprio account admin) e ne documenta l'email anonimizzata risultante nei manifest di collaudo —
  nessun utente sintetico aggiunto. Dato che `users.id` è conservato dall'ETL (§5.1 del PRD principale),
  recuperare l'email anonimizzata di un utente reale noto è una query diretta (`id` noto → riga v2).

## 6. Verifica

- Test unitari su `Anonymizer` (reset password **solo** con `--anonymize` attivo) e su `UsersStage`/stage
  interessati (password sovrascritta verificabile con `Hash::check('password', ...)`).
- Rimozione dei test di `DevelopmentSeederTest`/`UatSeederTest` insieme alle classi.
- Verifica manuale end-to-end **locale** (`make setup` da zero, dump già presente) prima di toccare
  l'infrastruttura UAT reale.
- Il deploy UAT vero e proprio (nuovo `db_legacy`, dump reale depositato dal committente) si verifica
  insieme al primo push su `develop` dopo il merge di questo lavoro — non eseguito autonomamente in questa
  sessione, tocca un server condiviso reale.

## 7. Rischi / assunzioni esplicite

- **Tempo di deploy UAT aumenta sensibilmente** (da pochi secondi a qualche minuto per push su `develop`):
  accettato esplicitamente dal committente come conseguenza della scelta "sempre da zero".
- **`v1dumps/latest.sql` mai aggiornato automaticamente**: se nessuno lo rinfresca, sia locale sia UAT
  continuano a riflettere lo stesso dump per un tempo indefinito. Non è un difetto di questo design (scelta
  esplicita: solo un umano con accesso SSH tocca la produzione), ma va comunicato al team come processo
  operativo, non solo come codice.
- **Media**: la disponibilità di allegati reali dipende dall'esecuzione manuale di `bin/fetch-legacy-media`
  su ciascun ambiente (locale e msuat) — non c'è automazione che lo forzi, coerente con la stessa scelta
  "solo un umano" della §2/§4.
