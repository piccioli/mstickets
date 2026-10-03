# Deploy UAT (`deploy/remote-deploy.sh`, `docker-compose.uat.yml`)

Si carica quando lavori sotto `deploy/*` o su `docker-compose.uat.yml`/`.env.uat.example`. Vedi anche la
radice del repo per il processo di collaudo (il deploy è lo step 4 del checklist) e `app/Import/CLAUDE.md`
per la pipeline ETL che gira ad ogni deploy.

## Gotcha `env_file:` vs `--env-file` (US-R05)

- `env_file: <file>` su un servizio e `docker compose --env-file <file>` sono due meccanismi distinti che
  capita puntino allo stesso file (`.env.uat`) — il primo inietta OGNI variabile del file nell'ambiente reale
  del container a runtime, il secondo risolve solo le interpolazioni `${VAR}` nel compose file al parse time.
  Una variabile usata SOLO per un bind-mount lato host (es. `LEGACY_MEDIA_HOST_PATH`, un path del filesystem
  di msuat) non va MAI chiamata come l'env var applicativa che il container legge per lo stesso concetto
  (`LEGACY_MEDIA_PATH`, letta da `config/filesystems.php` come path DENTRO al container): se condividessero
  il nome, `env_file:` la inietterebbe nel container con il valore host, un path inesistente dentro
  l'immagine, rompendo silenziosamente il disco. Nomi deliberatamente distinti quando lo stesso "concetto"
  serve sia all'host (interpolazione Compose) sia al container (env applicativa).
- Per la verifica (`docker compose config --quiet` con variabili `${VAR:?err}`) serve un vero file
  `.env.uat` temporaneo nella working directory (rimosso subito dopo), non un file con nome diverso passato
  a `--env-file` — `env_file:` dentro il servizio è un riferimento letterale al nome file e non risente di
  quel flag.

## Pipeline ETL ad ogni deploy (§14 del PRD, US-R06)

- Il deploy su UAT (automatico al merge su `develop`) riflette lo stato descritto nel manifest di collaudo:
  `migrate:fresh` → `RolePermissionSeeder` → `v1:import --anonymize` girano ad ogni deploy — sostituisce il
  seed fittizio usato prima del PRD.
- **CAI datapack sempre incondizionato nel deploy** (US-803), a differenza di `make setup` locale
  (best-effort, `if [ -f cai-datapack/runts-cai.sqlite ]`): il datapack arriva da un bind-mount
  (`CAI_DATAPACK_HOST_PATH` in `docker-compose.uat.yml`/`.env.uat.example`, stesso pattern di
  `LEGACY_MEDIA_HOST_PATH` ma in sola lettura, `:ro`) popolato in anticipo da un umano con
  `bin/push-cai-datapack` — se manca lì è un errore di processo (datapack non sincronizzato prima del
  deploy), non un caso normale da silenziare.
  Ordine operativo quando cambiano i bilanci manuali delle sezioni: `cai:build-manual-bilanci-datapack`
  → `bin/push-cai-datapack` → deploy. Il push esclude `bilanci-sezioni-2026/originals/` e
  `2026_Campagna_Sezioni.xlsx` (restano `normalized/` e `runts-cai.sqlite`). Il bind-mount è `:ro`:
  l'import non scrive mai nella cartella datapack, copia **da** lì verso `storage/app/private/cai-documents`.
