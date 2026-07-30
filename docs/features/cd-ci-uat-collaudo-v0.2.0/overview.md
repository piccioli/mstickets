# CD/CI per UAT + processo di collaudo (v0.2.0)

Branch: `cd-ci-first-version-for-ticket-uat-v-0.2.0`. Nessun ticket Orchestrator (questo repo non usa
il sistema di ticketing Orchestrator/oc: — il lavoro è tracciato via `scripts/ralph/prd.json` per fase).

## Cosa cambia

Da questo punto in poi, ogni fase di sviluppo (a partire da questa e valido per tutte le successive):

1. Una pipeline CD pubblica automaticamente su un ambiente UAT quando una PR viene mergiata sul branch
   `develop` (nuovo, da creare).
2. L'ambiente UAT è raggiungibile pubblicamente (app + Mailpit) con un seed di dati realistico dedicato.
3. Ogni fase produce un **documento di collaudo PDF** (carta intestata Montagna Servizi) con i test da
   eseguire manualmente, numerati e organizzati per argomento, con istruzioni operative complete.
4. Ogni test del collaudo corrisponde ad almeno un test automatico già esistente nel codice: se il
   collaudo fallisce, il test automatico corrispondente va rivisto (indica che non copre il caso reale).

## Perché

Il committente deve poter validare il lavoro su un URL pubblico stabile, senza dipendere da un ambiente
locale o da una sessione di sviluppo, e con un documento formale che guidi il collaudo passo-passo.

## Decisioni del committente (già raccolte)

| # | Decisione | Scelta |
|---|---|---|
| D1 | Dominio UAT | **Solo** `ticket-uat.montagnaservizi.com` (nuovo vhost+SSL). Il vecchio vhost `ticketuat.montagnaservizi.it` (senza trattino, .it) viene rimosso insieme all'app v1 che serviva |
| D2 | Dimensionamento risorse | Stack **alleggerito**: nessun container nginx dedicato (Apache host fa da reverse proxy diretto, come per le altre app sul server), queue+scheduler uniti in un container, limiti di memoria su Postgres/Redis |
| D3 | Accesso Mailpit | Sottodominio dedicato con **Basic Auth** (credenziali nel documento di collaudo) |
| D4 | Branch `develop` | Creato ora da `main` |
| D5 | Meccanismo CD | **GitHub Actions + SSH** (pattern già in uso su questo server per altri progetti: chiavi `github-actions-deploy`, `github-actions-clava-deploy`, `runts-deploy` già presenti in `~/.ssh/authorized_keys` di msuat) |
| D6 | Seeder UAT | **Dedicato**, distinto da `DevelopmentSeeder` (Fase 0): dataset più realistico, pensato per il collaudo formale |
| D7 | Libreria PDF | **barryvdh/laravel-dompdf** (nessuna dipendenza Chromium/Browsershot su un server già stretto in RAM). Decide anche la scelta lasciata aperta dal PRD §6.4.3 per l'intero progetto (documentazione, report, collaudo) |
| D8 | Retroattività | Documento di collaudo prodotto ORA anche per Fase 0 + Fase 1 (già concluse), mappando i criteri di accettazione già implementati ai 498 test automatici esistenti |

## Ricognizione tecnica effettuata (fatti verificati su msuat, non assunzioni)

- Server `ms-uat` (135.181.25.33, alias SSH `msuat`): Ubuntu, **3.7GB RAM (~1GB disponibile)**, **38GB disco
  (~6GB liberi)**. Risorse strette, condivise con 7 altre app attive: clava, enti, formap, fotosicai, grmrl,
  runts, tender (nessuna di queste va toccata).
- Nessun reverse proxy centralizzato: **Apache** gira sull'host (non containerizzato) su :80/:443 e ogni app
  ha il proprio `VirtualHost` che fa `ProxyPass` verso il container su un port host dedicato (pattern da
  replicare identico per la UAT).
- App da rimuovere: `/root/orchestrator` (495MB, `docker-compose.yml` con servizi `phpfpm`/`db`/`redis`,
  porte host 8099/9199/5599/6379, `APP_URL=https://ticketuat.montagnaservizi.it`, `APP_ENV=local` — è
  esattamente la vecchia UAT v1 di test descritta dal committente).
- DNS: sia `ticketuat.montagnaservizi.it` (esistente, cert valido) sia `ticket-uat.montagnaservizi.com`
  (richiesto ora, DNS già configurato) puntano a 135.181.25.33. Nessun vhost/cert esiste ancora per il
  secondo.
- Porte host già occupate su tutto il server: 22, 53, 80, 443, 3001, 5432, 5599, 6379, 8000, 8080, 8081,
  8082, 8083, 8088, 9000, 9199, 15432. Per la UAT userò porte libere e **non esporrò affatto** Postgres/Redis
  della UAT sull'host (solo rete Docker interna): riduce sia il rischio di conflitto sia la superficie
  esposta.
- Crontab esistente sul server: un solo job (`5 0 * * * cd /root/tender && ./scripts/backup_db.sh`), non
  correlato, da NON toccare.
- Chiavi SSH dedicate già presenti per altri progetti (`github-actions-deploy`,
  `github-actions-clava-deploy`, `runts-deploy`): ne aggiungo una nuova dedicata, non riuso quelle esistenti
  né una chiave personale.

## Decisioni tecniche aggiuntive (mie, validate dalla Fase: challenge sotto)

| # | Decisione | Motivazione |
|---|---|---|
| T1 | Directory sul server: **`/root/ticket-uat`** (non `/root/orchestrator`) | Il nome "orchestrator" ha già causato un incidente reale in locale (Fase 0): un `docker compose down` ha fermato un progetto Docker non correlato per collisione di project name derivato dal nome cartella. Uso un nome diverso ovunque (cartella + `name:` esplicito in docker-compose) per eliminare il rischio alla radice |
| T2 | Stack UAT: un solo container applicativo basato su **FrankenPHP** (non `php artisan serve`: single-thread, non pensato per servire un ambiente pubblico anche solo di collaudo — vedi Fase: challenge, asse 2), un container `worker` che esegue sia `queue:work` sia `schedule:work` sotto un piccolo supervisore che riavvia il sotto-processo se muore (non un semplice `&&`/background silenzioso), `db` (Postgres, solo rete interna, con `mem_limit`), `redis` (solo rete interna, con `mem_limit`). Mailpit gira come container a parte con porta esposta solo per il vhost dedicato |
| T3 | Deploy come utente **root** via SSH, ma con la nuova chiave deploy **ristretta** (`command=".../deploy.sh"` in `authorized_keys`, non una shell libera) | Mantiene la convenzione esistente sul server (tutte le altre chiavi deploy sono root) senza allargare il modello di sicurezza, ma limita il raggio d'azione della chiave al solo script di deploy previsto (mitigazione emersa dalla Fase: challenge, asse 3) |
| T4 | Prima di rimuovere `/root/orchestrator`: **backup completo** (`tar.gz` dell'intera cartella, che include già `docker/volumes/postgresql/data` — bind mount interno alla cartella stessa, verificato) scaricato in locale prima di `rm -rf` | Passo reversibile, e include davvero anche i dati Postgres di v1 (non solo i file applicativi: gap segnalato dalla Fase: challenge, asse 5, e verificato non applicarsi qui) |
| T5 | Generazione PDF: nuovo comando artisan `php artisan collaudo:generate {fase}` nell'app, che legge un manifest versionato (`docs/collaudo/fase-<N>.php`, elenco topic→test con id numerato + riferimento al test automatico corrispondente) e produce il PDF con dompdf in `storage/app/collaudo/`. Un comando/test dedicato verifica che ogni riferimento del manifest risolva davvero a un test esistente (fallisce la CI se un refactor futuro rompe un riferimento) | Rende il processo ripetibile e verificato nel tempo, non un documento scritto a mano che può scollegarsi silenziosamente dal codice (rischio di rollback emerso dalla Fase: challenge, asse 5) |
| T6 | **RISOLTO con l'utente**: "più parti" = sezioni distinte in un unico PDF per fase (Parte 1 — Come eseguire il collaudo: link/URL/account/Mailpit; Parte 2+ — un capitolo per modulo, test numerati progressivamente es. `F0-01`, `F1-01`), con indice/sommario iniziale | — |
| T7 | Porte UAT sul server: app `8090` (proxata da Apache), Mailpit UI `8091` (proxata dal vhost Mailpit dedicato), nessuna porta esposta per Postgres/Redis | Prime porte libere fuori dall'elenco già occupato |
| T8 | Nome vhost Mailpit: `mailpit-ticket-uat.montagnaservizi.com` | Segue lo schema del dominio principale UAT |
| T9 | Immagine applicativa **buildata in CI (GitHub Actions runner) e pubblicata su GHCR** (`ghcr.io/piccioli/mstickets-uat`), il server esegue solo `docker pull` + `up -d`, mai una build locale | Evita di consumare CPU/RAM/disco del server condiviso durante il build (rischio concreto emerso dalla Fase: challenge, asse 3 — "build diretta sul server... da valutare") |
| T10 | Certificato Let's Encrypt richiesto prima in modalità `--staging` (verifica che l'HTTP challenge funzioni sul nuovo vhost), poi in modalità reale solo dopo conferma | Evita di bruciare i tentativi reali (rate limit 5/ora per host di Let's Encrypt) su un vhost non ancora verificato (rischio concreto emerso dalla Fase: challenge, asse 1) |
| T11 | Ogni container nuovo ha `mem_limit` esplicito (dimensionati sommando entro ~1.5GB totali, con margine sul ~1GB disponibile misurato) e `restart: unless-stopped`; step di deploy include `docker image prune -f` per non accumulare layer su un disco da 6GB liberi; Mailpit configurato con un tetto al numero di messaggi conservati | Mitiga OOM su app altrui, esaurimento disco progressivo, e crescita illimitata dei dati di test (rischi concreti emersi dalla Fase: challenge, assi 1/3/4) |
| T12 | Workflow GitHub Actions con `concurrency: group: deploy-uat, cancel-in-progress: false` (le esecuzioni si accodano, non si sovrappongono mai) | Evita due deploy paralleli sullo stesso target SSH (blind spot concreto emerso dalla Fase: challenge, asse 3) |
| T13 | **RISOLTO con l'utente**: il seed UAT gira ad ogni deploy (`migrate:fresh` + `UatSeeder`), l'ambiente torna sempre allo stato descritto nel PDF di collaudo | Coerenza garantita tra documento di collaudo e stato effettivo dell'ambiente ad ogni deploy; il tester sa che eventuali dati creati a mano non sopravvivono al deploy successivo (va scritto esplicitamente nella Parte 1 del PDF) |

## Fase: challenge — esito

Eseguita un'analisi adversariale isolata (subagent senza contesto della conversazione) sull'overview.
Punti critici confermati e relative mitigazioni, integrate sopra come T9-T13 e nelle correzioni a T2-T5:

- **OOM su altre app per pressione di memoria condivisa** → T11 (mem_limit espliciti, dimensionamento conservativo)
- **`php artisan serve` inadatto anche solo a un uso di collaudo pubblico** → T2 rivisto (FrankenPHP)
- **Rate limit certbot su un vhost non ancora verificato** → T10 (staging prima del certificato reale)
- **Build immagine sul server già a corto di risorse** → T9 (build in CI, pull sul server)
- **Chiave SSH root senza restrizioni** → T3 rivisto (comando ristretto in authorized_keys)
- **Backup pre-rimozione che potrebbe non includere i dati DB di v1** → T4 rivisto (verificato: il bind mount Postgres è dentro la cartella, il backup lo include già)
- **Manifest di tracciabilità che può scollegarsi silenziosamente dal codice** → T5 rivisto (verifica automatica dei riferimenti)
- **Deploy concorrenti sullo stesso target** → T12 (concurrency group)
- **Ambiguità "più parti" del PDF** → T6 risolto con l'utente (sezioni in un unico PDF)
- **Politica di reseed non definita** → T13 risolto con l'utente (reset ad ogni deploy)

Non affrontati esplicitamente in questo ciclo (accettati come rischio noto, coerente con l'Out of scope):
queue+scheduler nello stesso container restano un accoppiamento di failure-domain accettato per limiti di
risorse (mitigato solo con un supervisore che riavvia il sotto-processo, non risolto architetturalmente);
Apache host come reverse proxy singolo resta un single point of failure strutturale preesistente al di
fuori dello scope di questa feature; la rotazione della chiave SSH deploy resta manuale, come per le chiavi
già esistenti sul server.

## Requisiti

- [ ] Branch `develop` creato da `main` e pushato
- [ ] Workflow GitHub Actions (`deploy-uat.yml`) che builda/testa e poi esegue il deploy via SSH su push/merge verso `develop`
- [ ] Chiave SSH dedicata generata, pubblica installata su msuat, privata salvata come GitHub Secret
- [ ] `docker-compose.uat.yml` alleggerito (T2) + `.env.uat` di riferimento (nessun segreto reale committato)
- [ ] `UatSeeder` dedicato (D6), guardia esplicita ambiente non-prod
- [ ] Rimozione sicura dell'app v1 `orchestrator` da msuat (T4: backup prima di eliminare) + rimozione vhost/cert `.it`
- [ ] Nuovo vhost + certificato Let's Encrypt per `ticket-uat.montagnaservizi.com` → stack UAT
- [ ] Nuovo vhost + Basic Auth per `mailpit-ticket-uat.montagnaservizi.com` → Mailpit UAT
- [ ] Comando `collaudo:generate {fase}` + template Blade con carta intestata Montagna Servizi + dompdf
- [ ] Manifest di tracciabilità test-collaudo↔test-automatico per Fase 0 e Fase 1 (retroattivo, D8)
- [ ] Documento di collaudo PDF generato per Fase 0+1 e verificato (contenuti coerenti coi test reali)
- [ ] Primo deploy end-to-end verificato: `https://ticket-uat.montagnaservizi.com` e Mailpit raggiungibili, login funzionante
- [ ] Convenzione documentata in `CLAUDE.md` (repo) e in `scripts/ralph/CLAUDE.md` (o equivalente) perché si applichi automaticamente alle fasi successive (Fase 2+): ogni nuova fase deve generare il proprio collaudo PDF con manifest di tracciabilità

## Rischi

- Server con risorse molto strette (RAM/disco): un deploy che tira su troppi container in parallelo
  potrebbe saturare la macchina condivisa con altre 7 app. Mitigazione: stack alleggerito (D2/T2), niente
  build pesanti sul server (le immagini si costruiscono... da valutare in plan.md: build locale in CI e
  push di un'immagine, oppure build diretta sul server — impatta RAM/CPU disponibili durante il deploy).
- Azione distruttiva su server condiviso (rimozione app v1, vhost, eventualmente certificati): mitigata da
  T4 (backup) e dal fatto che ogni comando sul server verrà eseguito ed esito verificato passo-passo, non in
  blocco.
- Certificato Let's Encrypt per un nuovo dominio richiede che l'HTTP challenge sia raggiungibile: se il
  firewall o Apache non sono già pronti a rispondere su `ticket-uat.montagnaservizi.com` prima di richiedere
  il certificato, `certbot` può fallire. Va verificato con un vhost HTTP-only funzionante prima di richiedere
  il certificato.
- Basic Auth su Mailpit protegge l'accesso ma le credenziali finiranno stampate nel PDF di collaudo:
  documento da trattare come sensibile (non pubblicarlo online senza controllo accessi).

## Out of scope

- Nessuna modifica al modello di sicurezza del server (utenti dedicati, sudo, ecc. — T3).
- Nessun deploy automatico verso `main`/produzione: questa CD copre **solo** `develop` → UAT.
- Nessuna integrazione con l'ETL reale (Fase 2 non ancora iniziata): il seed UAT resta dati fittizi.
- Nessuna modifica alle altre 7 app sul server msuat.

## Moduli toccati

- **Repo `mstickets/orchestrator`** (unico repo coinvolto, nessun submodule):
  - `.github/workflows/deploy-uat.yml` (nuovo)
  - `docker-compose.uat.yml`, `.env.uat.example` (nuovi)
  - `database/seeders/UatSeeder.php` (nuovo)
  - `app/Console/Commands/CollaudoGenerateCommand.php` (nuovo)
  - `resources/views/pdf/collaudo.blade.php` (nuovo)
  - `docs/collaudo/fase-0-1.php` (nuovo, manifest di tracciabilità)
  - `composer.json` (+ barryvdh/laravel-dompdf)
  - `CLAUDE.md` (nuova sezione: processo di collaudo obbligatorio per ogni fase)
  - `scripts/ralph/CLAUDE.md` (istruzione per le fasi Ralph future: generare il collaudo a fine fase)
- **Server `msuat`** (fuori dal repo, configurazione infrastrutturale):
  - Rimozione `/root/orchestrator` (con backup) + vhost/cert `.it`
  - Nuova directory `/root/ticket-uat` + vhost/cert `.com` + vhost Mailpit
  - Nuova chiave SSH deploy in `~/.ssh/authorized_keys`
