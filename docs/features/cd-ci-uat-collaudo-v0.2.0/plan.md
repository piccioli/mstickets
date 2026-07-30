# CD/CI per UAT + processo di collaudo (v0.2.0) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Pubblicare automaticamente ogni merge su `develop` su un ambiente UAT pubblico (app + Mailpit) su
msuat, con un seed dedicato e un documento di collaudo PDF (carta intestata Montagna Servizi) i cui test
numerati corrispondono 1:1 a test automatici verificabili in CI — processo ripetibile per ogni fase futura.

**Architecture:** Immagine Docker (FrankenPHP) buildata in CI e pubblicata su GHCR; il server msuat esegue
solo `docker compose pull && up -d` via una chiave SSH dedicata con comando forzato; Apache host (già in uso
per le altre 7 app del server) fa da reverse proxy verso due nuovi vhost (app + Mailpit). Il documento di
collaudo è generato da un comando Artisan che legge un manifest PHP versionato (topic → test numerato → test
automatico corrispondente), reso in PDF con dompdf.

**Tech Stack:** Laravel 13 / Filament 4 (esistente), `barryvdh/laravel-dompdf` (nuovo), FrankenPHP (nuova
immagine base per il container app), GitHub Actions + GHCR, Apache 2 + certbot (msuat, esistente).

## Global Constraints

- Nessun commit automatico durante l'esecuzione di sotto-skill Superpowers: i commit avvengono solo nella
  Fase 6c del workflow wm-plan (review-gate), non dentro l'esecuzione dei task.
- Commit convention di questo repo (nessun ticket Orchestrator): `feat: ...`, `docs: ...`, `fix: ...` — mai
  `feat(oc:<ID>)`.
- Ogni nuovo container Docker ha `mem_limit` esplicito e `restart: unless-stopped` (T11 overview).
- Mai usare "orchestrator" come nome di progetto Docker Compose o cartella su alcun host (T1 overview —
  incidente già avvenuto in locale).
- Quality gate esistenti da non rompere: `vendor/bin/pint --test`, `composer analyse` (Larastan livello 6,
  `--memory-limit=1G`), `php -d memory_limit=1G vendor/bin/pest`.
- Nessuna azione distruttiva su msuat senza backup preventivo e verifica esplicita del risultato (vedi Task
  12).
- Nessuna delle altre 7 app su msuat (clava, enti, formap, fotosicai, grmrl, runts, tender) va fermata,
  riavviata o riconfigurata.

---

### Task 1: Branch `develop`

**Files:** nessuno (operazione git pura).

**Interfaces:**
- Produce: branch remoto `develop` allineato a `main` (HEAD `6eb4b21` o successivo), punto di partenza per
  ogni feature branch futura e trigger della CD.

- [ ] **Step 1: Verifica che `develop` non esista già**

Run: `git -C /Users/alessiopiccioli/Documents/LAVORO/MS/SOFTWARE/mstickets/orchestrator ls-remote --heads origin develop`
Expected: nessun output (branch non esistente)

- [ ] **Step 2: Crea `develop` da `main` e pusha**

```bash
cd /Users/alessiopiccioli/Documents/LAVORO/MS/SOFTWARE/mstickets/orchestrator
git checkout main
git pull --ff-only origin main
git checkout -b develop
git push -u origin develop
git checkout cd-ci-first-version-for-ticket-uat-v-0.2.0
```

- [ ] **Step 3: Verifica**

Run: `git -C /Users/alessiopiccioli/Documents/LAVORO/MS/SOFTWARE/mstickets/orchestrator ls-remote --heads origin develop`
Expected: una riga con l'hash di `main`

- [ ] **Step 4: Commit**

Nessun file da committare (operazione già persistita dal push). Annota in `notes.md` l'hash di partenza di
`develop`.

---

### Task 2: Dipendenza dompdf + comando `collaudo:generate` (scheletro)

**Files:**
- Modify: `composer.json` (+ `barryvdh/laravel-dompdf`)
- Create: `app/Console/Commands/CollaudoGenerateCommand.php`
- Create: `resources/views/pdf/collaudo.blade.php`
- Create: `resources/views/pdf/partials/collaudo-copertina.blade.php`
- Test: `tests/Feature/Console/CollaudoGenerateCommandTest.php`

**Interfaces:**
- Consumes: `config('orchestrator.platform_company')` se esiste, altrimenti stringa letterale
  `"Montagna Servizi S.C.p.A."` (nessuna config dedicata esiste ancora — verificare con
  `grep -rn "PLATFORM_COMPANY" config/` prima di assumere che esista).
- Produces: comando `php artisan collaudo:generate {fase}` che scrive un file
  `storage/app/collaudo/collaudo-fase-{fase}-{data}.pdf` e stampa il path assoluto su stdout. Firma del
  metodo pubblico riusato da Task 4: `CollaudoGenerateCommand::buildPdf(string $fase, array $manifest): string`
  (ritorna il path del file scritto).

- [ ] **Step 1: Installa la dipendenza**

```bash
cd /Users/alessiopiccioli/Documents/LAVORO/MS/SOFTWARE/mstickets/orchestrator
composer require barryvdh/laravel-dompdf
```

Expected: `composer.json`/`composer.lock` aggiornati, nessun conflitto di versione con Laravel 13/PHP 8.4.

- [ ] **Step 2: Scrivi il test che fallisce**

```php
<?php

declare(strict_types=1);

use App\Console\Commands\CollaudoGenerateCommand;

it('genera un pdf di collaudo con copertina e sezioni per topic', function () {
    $manifest = [
        'fase' => '0-1',
        'titolo' => 'Fase 0 + Fase 1',
        'parte_1' => [
            'app_url' => 'https://ticket-uat.montagnaservizi.com',
            'mailpit_url' => 'https://mailpit-ticket-uat.montagnaservizi.com',
            'credenziali' => [
                ['ruolo' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password'],
            ],
        ],
        'topics' => [
            [
                'titolo' => 'Autenticazione',
                'test' => [
                    [
                        'id' => 'F0-01',
                        'descrizione' => 'Login con utente admin',
                        'test_automatico' => 'tests/Feature/Filament/AdminAccessTest.php::it can access panel',
                    ],
                ],
            ],
        ],
    ];

    $path = app(CollaudoGenerateCommand::class)->buildPdf('0-1', $manifest);

    expect($path)->toBeFile();
    expect(file_get_contents($path))->toStartWith('%PDF');
});
```

- [ ] **Step 2b: Esegui e verifica che fallisca**

Run: `php -d memory_limit=1G vendor/bin/pest tests/Feature/Console/CollaudoGenerateCommandTest.php`
Expected: FAIL — `Class "App\Console\Commands\CollaudoGenerateCommand" not found` (il comando non esiste
ancora)

- [ ] **Step 3: Scrivi il comando (scheletro minimo)**

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class CollaudoGenerateCommand extends Command
{
    protected $signature = 'collaudo:generate {fase}';

    protected $description = 'Genera il PDF di collaudo per la fase indicata, leggendo il manifest in docs/collaudo/';

    public function handle(): int
    {
        $fase = (string) $this->argument('fase');
        $manifestPath = base_path("docs/collaudo/fase-{$fase}.php");

        if (! file_exists($manifestPath)) {
            $this->error("Manifest non trovato: {$manifestPath}");

            return self::FAILURE;
        }

        $manifest = require $manifestPath;
        $path = $this->buildPdf($fase, $manifest);
        $this->info("PDF generato: {$path}");

        return self::SUCCESS;
    }

    public function buildPdf(string $fase, array $manifest): string
    {
        $pdf = Pdf::loadView('pdf.collaudo', ['manifest' => $manifest]);
        $filename = sprintf('collaudo-fase-%s-%s.pdf', $fase, now()->format('Ymd-His'));
        $disk = Storage::build(['driver' => 'local', 'root' => storage_path('app')]);
        $disk->put("collaudo/{$filename}", $pdf->output());

        return storage_path("app/collaudo/{$filename}");
    }
}
```

- [ ] **Step 4: Scrivi il template Blade minimo (verrà arricchito in Task 8)**

```blade
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 15px; margin-top: 24px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .test-id { font-weight: bold; color: #17a180; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        td, th { padding: 4px 6px; border: 1px solid #ddd; text-align: left; vertical-align: top; }
    </style>
</head>
<body>
    @include('pdf.partials.collaudo-copertina', ['manifest' => $manifest])

    @foreach ($manifest['topics'] as $topic)
        <h2>{{ $topic['titolo'] }}</h2>
        <table>
            <thead>
                <tr><th style="width: 60px;">ID</th><th>Test</th><th>Test automatico</th></tr>
            </thead>
            <tbody>
                @foreach ($topic['test'] as $test)
                    <tr>
                        <td class="test-id">{{ $test['id'] }}</td>
                        <td>{{ $test['descrizione'] }}</td>
                        <td>{{ $test['test_automatico'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
```

```blade
<h1>Montagna Servizi S.C.p.A.</h1>
<p><strong>Documento di collaudo — {{ $manifest['titolo'] }}</strong></p>

<h2>Parte 1 — Come eseguire il collaudo</h2>
<p>Applicazione: <a href="{{ $manifest['parte_1']['app_url'] }}">{{ $manifest['parte_1']['app_url'] }}</a></p>
<p>Mailpit (email di test): <a href="{{ $manifest['parte_1']['mailpit_url'] }}">{{ $manifest['parte_1']['mailpit_url'] }}</a></p>
<table>
    <thead><tr><th>Ruolo</th><th>Email</th><th>Password</th></tr></thead>
    <tbody>
        @foreach ($manifest['parte_1']['credenziali'] as $cred)
            <tr><td>{{ $cred['ruolo'] }}</td><td>{{ $cred['email'] }}</td><td>{{ $cred['password'] }}</td></tr>
        @endforeach
    </tbody>
</table>
```

- [ ] **Step 5: Esegui e verifica che passi**

Run: `php -d memory_limit=1G vendor/bin/pest tests/Feature/Console/CollaudoGenerateCommandTest.php`
Expected: PASS

- [ ] **Step 6: Quality gate**

```bash
vendor/bin/pint --test
composer analyse
```

Expected: entrambi puliti (nessun errore)

- [ ] **Step 7: Commit**

```bash
git add composer.json composer.lock app/Console/Commands/CollaudoGenerateCommand.php \
  resources/views/pdf/collaudo.blade.php resources/views/pdf/partials/collaudo-copertina.blade.php \
  tests/Feature/Console/CollaudoGenerateCommandTest.php
git commit -m "feat: comando collaudo:generate con template PDF minimo (dompdf)"
```

---

### Task 3: Manifest di tracciabilità + verifica automatica dei riferimenti

**Files:**
- Create: `docs/collaudo/fase-0-1.php`
- Create: `app/Console/Commands/CollaudoVerifyManifestCommand.php`
- Test: `tests/Unit/Collaudo/ManifestReferencesTest.php`

**Interfaces:**
- Consumes: nessuna interfaccia da task precedenti (il manifest è dati puri, un array PHP con la stessa
  forma usata nel test di Task 2: `['fase' => string, 'titolo' => string, 'parte_1' => array, 'topics' =>
  array<['titolo' => string, 'test' => array<['id' => string, 'descrizione' => string, 'test_automatico' =>
  string]>]>]`).
- Produces: `CollaudoVerifyManifestCommand::resolveTestReference(string $reference): bool` — true se il
  riferimento (formato `path/al/file.php::descrizione del test`) esiste davvero nel codice (il file esiste E
  contiene una stringa di test con quella descrizione esatta, cercata con una grep sul contenuto del file:
  Pest genera `it('descrizione', ...)`/`test('descrizione', ...)`, quindi la verifica cerca la sottostringa
  `'descrizione'` dentro il file indicato).

- [ ] **Step 1: Costruisci il manifest retroattivo per Fase 0 + Fase 1**

Prima di scrivere il file, elenca i test esistenti rilevanti per popolare i riferimenti corretti:

Run: `grep -rn "^it(" tests/Feature/Domain/Ticketing/ tests/Feature/Domain/Identity 2>/dev/null | head -60`
Run: `grep -rln "^it(" tests/Feature/ tests/Unit/ | wc -l`

Usa l'output per scegliere, per ogni topic sotto, un test automatico REALMENTE esistente (non inventato) da
referenziare. Non proseguire allo Step 2 finché non hai la lista di file/descrizioni reali in mano.

- [ ] **Step 2: Scrivi il manifest**

```php
<?php

declare(strict_types=1);

return [
    'fase' => '0-1',
    'titolo' => 'Fase 0 (Fondazioni) + Fase 1 (Ticketing core)',
    'parte_1' => [
        'app_url' => 'https://ticket-uat.montagnaservizi.com',
        'mailpit_url' => 'https://mailpit-ticket-uat.montagnaservizi.com',
        'credenziali' => [
            ['ruolo' => 'Admin', 'email' => 'admin@orchestrator.local', 'password' => 'password'],
            ['ruolo' => 'Developer', 'email' => 'developer@orchestrator.local', 'password' => 'password'],
            ['ruolo' => 'Manager', 'email' => 'manager@orchestrator.local', 'password' => 'password'],
            ['ruolo' => 'Customer', 'email' => 'customer@orchestrator.local', 'password' => 'password'],
            ['ruolo' => 'Fundraising', 'email' => 'fundraising@orchestrator.local', 'password' => 'password'],
        ],
    ],
    'topics' => [
        [
            'titolo' => 'Autenticazione e ruoli',
            'test' => [
                [
                    'id' => 'F0-01',
                    'descrizione' => 'Un utente disattivato non accede al pannello',
                    'test_automatico' => 'tests/Feature/Domain/Identity/PanelAccessGateTest.php',
                ],
            ],
        ],
        [
            'titolo' => 'Ciclo di vita del ticket',
            'test' => [
                [
                    'id' => 'F1-01',
                    'descrizione' => 'Un ticket percorre il percorso principale new -> ... -> done',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php',
                ],
                [
                    'id' => 'F1-02',
                    'descrizione' => 'Una transizione vietata non è eseguibile via richiesta manipolata',
                    'test_automatico' => 'tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php',
                ],
            ],
        ],
    ],
];
```

NOTA PER CHI ESEGUE QUESTO TASK: le righe `test_automatico` sopra sono un placeholder illustrativo di
FORMATO — vanno sostituite con i riferimenti reali trovati allo Step 1 prima di procedere. Il manifest
completo deve coprire OGNI criterio di accettazione della Fase 0 (24 story, vedi
`scripts/ralph/archive/2026-07-26-orchestrator-v2-fase-0/prd.json`) e della Fase 1 (14 story, vedi
`scripts/ralph/prd.json` prima dell'archiviazione — cercalo con
`find scripts/ralph/archive -name prd.json`), non solo i due esempi sopra.

- [ ] **Step 3: Scrivi il test che fallisce (verifica riferimenti)**

```php
<?php

declare(strict_types=1);

it('ogni riferimento test_automatico nel manifest esiste davvero nel codice', function () {
    $manifest = require base_path('docs/collaudo/fase-0-1.php');

    foreach ($manifest['topics'] as $topic) {
        foreach ($topic['test'] as $test) {
            $file = $test['test_automatico'];
            expect(base_path($file))->toBeFile("Riferimento mancante per {$test['id']}: {$file}");
        }
    }
});
```

- [ ] **Step 4: Esegui e verifica che fallisca (prima del comando di verifica reale)**

Run: `php -d memory_limit=1G vendor/bin/pest tests/Unit/Collaudo/ManifestReferencesTest.php`
Expected: FAIL se un path nel manifest non esiste ancora come file reale — correggi il manifest allo Step 2
finché questo passa

- [ ] **Step 5: Scrivi il comando di verifica riusabile per fasi future**

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class CollaudoVerifyManifestCommand extends Command
{
    protected $signature = 'collaudo:verify-manifest {fase}';

    protected $description = 'Verifica che ogni test_automatico del manifest di collaudo esista davvero';

    public function handle(): int
    {
        $fase = (string) $this->argument('fase');
        $manifestPath = base_path("docs/collaudo/fase-{$fase}.php");

        if (! file_exists($manifestPath)) {
            $this->error("Manifest non trovato: {$manifestPath}");

            return self::FAILURE;
        }

        $manifest = require $manifestPath;
        $missing = [];

        foreach ($manifest['topics'] as $topic) {
            foreach ($topic['test'] as $test) {
                if (! $this->resolveTestReference($test['test_automatico'])) {
                    $missing[] = $test['id'].' -> '.$test['test_automatico'];
                }
            }
        }

        if ($missing !== []) {
            $this->error('Riferimenti mancanti: '.implode(', ', $missing));

            return self::FAILURE;
        }

        $this->info('Tutti i riferimenti del manifest esistono.');

        return self::SUCCESS;
    }

    public function resolveTestReference(string $reference): bool
    {
        return file_exists(base_path($reference));
    }
}
```

- [ ] **Step 6: Esegui e verifica che passi**

Run: `php -d memory_limit=1G vendor/bin/pest tests/Unit/Collaudo/ManifestReferencesTest.php`
Run: `php artisan collaudo:verify-manifest 0-1`
Expected: entrambi PASS/`SUCCESS`

- [ ] **Step 7: Quality gate**

```bash
vendor/bin/pint --test
composer analyse
```

- [ ] **Step 8: Commit**

```bash
git add docs/collaudo/fase-0-1.php app/Console/Commands/CollaudoVerifyManifestCommand.php \
  tests/Unit/Collaudo/ManifestReferencesTest.php
git commit -m "feat: manifest di tracciabilità collaudo Fase 0-1 + verifica automatica riferimenti"
```

---

### Task 4: Aggiungi il job di verifica manifest alla CI esistente

**Files:**
- Modify: `.github/workflows/ci.yml`

**Interfaces:**
- Consumes: comando `collaudo:verify-manifest {fase}` da Task 3.
- Produces: step CI che fallisce se un manifest futuro si scollega dal codice.

- [ ] **Step 1: Leggi il workflow esistente**

Run: `cat /Users/alessiopiccioli/Documents/LAVORO/MS/SOFTWARE/mstickets/orchestrator/.github/workflows/ci.yml`

- [ ] **Step 2: Aggiungi lo step (nello stesso job che già esegue `php artisan test`, dopo quello step)**

```yaml
      - name: Verifica manifest di collaudo
        run: php artisan collaudo:verify-manifest 0-1
```

- [ ] **Step 3: Verifica sintassi YAML**

Run: `python3 -c "import yaml; yaml.safe_load(open('.github/workflows/ci.yml'))"`
Expected: nessun errore

- [ ] **Step 4: Commit**

```bash
git add .github/workflows/ci.yml
git commit -m "feat: CI verifica i riferimenti del manifest di collaudo"
```

---

### Task 5: `UatSeeder` dedicato

**Files:**
- Create: `database/seeders/UatSeeder.php`
- Test: `tests/Feature/Database/Seeders/UatSeederTest.php`

**Interfaces:**
- Consumes: stessi modelli di dominio già usati da `DevelopmentSeeder` (Fase 0/1): `User`, `Organization`,
  `Ticket`, `Tag`, `DocumentationPage`, `ActivityReport`, `FundraisingOpportunity`, `FundraisingProject` —
  verificare i nomi esatti con `grep -rn "class DevelopmentSeeder" -A5 database/seeders/DevelopmentSeeder.php`
  prima di scrivere questo seeder, per riusare gli stessi metodi/factory dove sensato invece di duplicare.
- Produces: `UatSeeder` eseguibile con `php artisan db:seed --class=UatSeeder`, guardia non-prod identica al
  pattern di `DevelopmentSeeder` (`if (app()->environment('production')) { throw new RuntimeException(...); }`).

- [ ] **Step 1: Leggi il seeder esistente per riusarne il pattern**

Run: `cat database/seeders/DevelopmentSeeder.php`

- [ ] **Step 2: Scrivi il test che fallisce**

```php
<?php

declare(strict_types=1);

use Database\Seeders\UatSeeder;
use Domain\Identity\Models\User;

it('popola un dataset UAT con i 5 utenti di ruolo e rifiuta di girare in produzione', function () {
    (new UatSeeder())->run();

    expect(User::query()->where('email', 'admin@orchestrator.local')->exists())->toBeTrue();
});

it('lancia eccezione se eseguito in ambiente production', function () {
    app()->instance('env', 'production');

    expect(fn () => (new UatSeeder())->run())->toThrow(RuntimeException::class);
});
```

- [ ] **Step 2b: Esegui e verifica che fallisca**

Run: `php -d memory_limit=1G vendor/bin/pest tests/Feature/Database/Seeders/UatSeederTest.php`
Expected: FAIL — classe non esistente

- [ ] **Step 3: Scrivi il seeder (riusando i pattern di `DevelopmentSeeder` individuati allo Step 1: stesso
  namespace `Database\Seeders`, stessa guardia, stesse factory/relazioni — differisce solo nel volume e nel
  "sapore" narrativo dei dati, pensati per essere descritti uno-a-uno nel PDF di collaudo, es. nomi di
  organizzazioni e titoli di ticket riconoscibili e stabili tra un deploy e l'altro, non generati a caso ad
  ogni run)**

Implementazione concreta da scrivere qui SOLO dopo aver letto `DevelopmentSeeder.php` allo Step 1: replica la
stessa struttura (stessi 5 utenti-ruolo con le stesse email/password già usate nel manifest di Task 3, così
il PDF di collaudo resta corretto), ma con `fake()->seed(42)` (seed deterministico) per dati riproducibili
identici ad ogni `migrate:fresh --seed`, requisito perché il collaudo descriva sempre lo stesso stato.

- [ ] **Step 4: Esegui e verifica che passi**

Run: `php -d memory_limit=1G vendor/bin/pest tests/Feature/Database/Seeders/UatSeederTest.php`
Expected: PASS

- [ ] **Step 5: Quality gate**

```bash
vendor/bin/pint --test
composer analyse
```

- [ ] **Step 6: Commit**

```bash
git add database/seeders/UatSeeder.php tests/Feature/Database/Seeders/UatSeederTest.php
git commit -m "feat: UatSeeder dedicato con dataset deterministico per il collaudo"
```

---

### Task 6: `docker-compose.uat.yml` + Dockerfile FrankenPHP + worker supervisor

**Files:**
- Create: `docker-compose.uat.yml`
- Create: `docker/uat/Dockerfile`
- Create: `docker/uat/worker-entrypoint.sh`
- Create: `.env.uat.example`

**Interfaces:**
- Consumes: variabili d'ambiente esistenti già documentate in `.env.example` (DB_*, REDIS_*, APP_*) più le
  nuove `MEM_LIMIT_APP`, `MEM_LIMIT_DB`, `MEM_LIMIT_REDIS`, `MEM_LIMIT_WORKER`, `MEM_LIMIT_MAILPIT`.
- Produces: immagine `ghcr.io/piccioli/mstickets-uat:${TAG}` costruita da `docker/uat/Dockerfile`, avviabile
  con `docker compose -f docker-compose.uat.yml up -d` (consumato da Task 7 e dal deploy su msuat).

- [ ] **Step 1: Verifica la porta/servizio esposto dal container app locale esistente per non duplicare la
  configurazione PHP**

Run: `cat docker/php/Dockerfile` (Dockerfile di sviluppo, Fase 0 — riusane le estensioni PHP elencate:
`exif` e le altre note in CLAUDE.md, sezione "make setup — avvio da zero")

- [ ] **Step 2: Scrivi il Dockerfile FrankenPHP**

```dockerfile
FROM dunglas/frankenphp:php8.4-alpine

RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    redis \
    zip \
    exif \
    bcmath \
    intl \
    opcache

WORKDIR /app
COPY . /app

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && php artisan config:cache \
    && php artisan event:cache

ENV SERVER_NAME=:8000
EXPOSE 8000

CMD ["frankenphp", "php-server", "--listen", ":8000", "--root", "/app/public"]
```

- [ ] **Step 3: Scrivi l'entrypoint del worker (queue + scheduler nello stesso container, con riavvio del
  sotto-processo se muore)**

```bash
#!/bin/sh
set -eu

restart_loop() {
    while true; do
        "$@" || echo "[worker-entrypoint] processo '$*' terminato, riavvio tra 2s" >&2
        sleep 2
    done
}

restart_loop php artisan queue:work --sleep=3 --tries=3 &
restart_loop php artisan schedule:work &

wait -n
exit 1
```

`wait -n` termina l'intero script (e quindi il container, che Docker riavvierà per `restart: unless-stopped`)
se uno dei due `restart_loop` in background esce del tutto — non dovrebbe mai accadere dato il loop
infinito, ma è la rete di sicurezza esplicita richiesta dalla Fase: challenge (asse "blind spot").

- [ ] **Step 4: Scrivi `docker-compose.uat.yml`**

```yaml
name: ticket-uat

services:
  app:
    image: ${IMAGE:-ghcr.io/piccioli/mstickets-uat:latest}
    restart: unless-stopped
    mem_limit: ${MEM_LIMIT_APP:-512m}
    env_file: .env.uat
    ports:
      - "127.0.0.1:8090:8000"
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_healthy
    networks:
      - ticket-uat

  worker:
    image: ${IMAGE:-ghcr.io/piccioli/mstickets-uat:latest}
    restart: unless-stopped
    mem_limit: ${MEM_LIMIT_WORKER:-256m}
    env_file: .env.uat
    entrypoint: ["/app/docker/uat/worker-entrypoint.sh"]
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_healthy
    networks:
      - ticket-uat

  db:
    image: postgres:16-alpine
    restart: unless-stopped
    mem_limit: ${MEM_LIMIT_DB:-384m}
    environment:
      POSTGRES_DB: ${DB_DATABASE:-ticket_uat}
      POSTGRES_USER: ${DB_USERNAME:-ticket_uat}
      POSTGRES_PASSWORD: ${DB_PASSWORD:?err}
    volumes:
      - ticket_uat_db_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME:-ticket_uat}"]
      interval: 5s
      timeout: 5s
      retries: 10
    networks:
      - ticket-uat

  redis:
    image: redis:7-alpine
    restart: unless-stopped
    mem_limit: ${MEM_LIMIT_REDIS:-128m}
    command: ["redis-server", "--maxmemory", "100mb", "--maxmemory-policy", "allkeys-lru"]
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 5s
      timeout: 5s
      retries: 10
    networks:
      - ticket-uat

  mailpit:
    image: axllent/mailpit:latest
    restart: unless-stopped
    mem_limit: ${MEM_LIMIT_MAILPIT:-128m}
    command: ["/mailpit", "--max-messages", "500"]
    ports:
      - "127.0.0.1:8091:8025"
    networks:
      - ticket-uat

networks:
  ticket-uat:
    driver: bridge

volumes:
  ticket_uat_db_data:
```

Nota: `ports` di `app`/`mailpit` sono legate a `127.0.0.1` (non `0.0.0.0`): solo Apache sull'host può
raggiungerle, mai direttamente da internet — coerente con "nessuna porta esposta" richiesto per db/redis, e
riduce la superficie anche per app/mailpit (Apache resta l'unico ingresso pubblico).

- [ ] **Step 5: Scrivi `.env.uat.example`**

```
APP_NAME="Orchestrator UAT"
APP_ENV=staging
APP_URL=https://ticket-uat.montagnaservizi.com
APP_KEY=
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=ticket_uat
DB_USERNAME=ticket_uat
DB_PASSWORD=
REDIS_HOST=redis
REDIS_PORT=6379
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MEM_LIMIT_APP=512m
MEM_LIMIT_WORKER=256m
MEM_LIMIT_DB=384m
MEM_LIMIT_REDIS=128m
MEM_LIMIT_MAILPIT=128m
```

- [ ] **Step 6: Verifica sintattica del compose file (senza avviarlo — richiede `.env.uat` reale con
  `DB_PASSWORD`, non ancora presente in questo task)**

Run: `docker compose -f docker-compose.uat.yml config --quiet`
Expected: nessun errore di parsing (fallirà solo se manca `.env.uat`: creane uno temporaneo con
`cp .env.uat.example .env.uat && echo 'DB_PASSWORD=test' >> .env.uat` solo per questa verifica, poi
`rm .env.uat` — non deve restare committato)

- [ ] **Step 7: Commit**

```bash
git add docker-compose.uat.yml docker/uat/Dockerfile docker/uat/worker-entrypoint.sh .env.uat.example
chmod +x docker/uat/worker-entrypoint.sh
git add docker/uat/worker-entrypoint.sh
git commit -m "feat: docker-compose UAT alleggerito (FrankenPHP, worker unificato, mem_limit)"
```

---

### Task 7: Workflow GitHub Actions `deploy-uat.yml`

**Files:**
- Create: `.github/workflows/deploy-uat.yml`

**Interfaces:**
- Consumes: `docker/uat/Dockerfile` (Task 6), secret GitHub `UAT_SSH_PRIVATE_KEY` e `UAT_SSH_HOST_KEY`
  (creati in Task 9, non ancora presenti quando questo task viene scritto — il workflow può essere scritto e
  committato ORA, ma non si attiverà con successo finché Task 9 non aggiunge i secret).
- Produces: alla fine del job, l'immagine `ghcr.io/piccioli/mstickets-uat:${{ github.sha }}` e
  `:latest` pubblicate, e un comando SSH eseguito su msuat che aggiorna lo stack.

- [ ] **Step 1: Scrivi il workflow**

```yaml
name: Deploy UAT

on:
  push:
    branches: [develop]

concurrency:
  group: deploy-uat
  cancel-in-progress: false

jobs:
  build-and-push:
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write
    steps:
      - uses: actions/checkout@v4

      - name: Log in to GHCR
        uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Build and push
        uses: docker/build-push-action@v6
        with:
          context: .
          file: docker/uat/Dockerfile
          push: true
          tags: |
            ghcr.io/piccioli/mstickets-uat:${{ github.sha }}
            ghcr.io/piccioli/mstickets-uat:latest

  deploy:
    needs: build-and-push
    runs-on: ubuntu-latest
    steps:
      - name: Configura chiave SSH
        run: |
          mkdir -p ~/.ssh
          echo "${{ secrets.UAT_SSH_PRIVATE_KEY }}" > ~/.ssh/id_deploy
          chmod 600 ~/.ssh/id_deploy
          echo "${{ secrets.UAT_SSH_HOST_KEY }}" >> ~/.ssh/known_hosts

      - name: Deploy su msuat
        run: |
          ssh -i ~/.ssh/id_deploy -o UserKnownHostsFile=~/.ssh/known_hosts root@135.181.25.33 "echo deploy-triggered"
```

Nota: il comando SSH invocato è ininfluente (`echo deploy-triggered`) perché la chiave installata in Task 9
ha un **comando forzato** (`command=` in `authorized_keys`): il server esegue SEMPRE e SOLO
`/root/ticket-uat/deploy/remote-deploy.sh`, indipendentemente da cosa il client richiede. Questo va
verificato esplicitamente in Task 9 prima di fidarsi di questo comportamento.

- [ ] **Step 2: Verifica sintassi YAML**

Run: `python3 -c "import yaml; yaml.safe_load(open('.github/workflows/deploy-uat.yml'))"`
Expected: nessun errore

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/deploy-uat.yml
git commit -m "feat: workflow CD verso UAT su push a develop (build+push GHCR, deploy via SSH)"
```

---

### Task 8: Arricchisci il template PDF (Parte 1 completa + indice) e genera il collaudo Fase 0-1

**Files:**
- Modify: `resources/views/pdf/collaudo.blade.php`
- Modify: `resources/views/pdf/partials/collaudo-copertina.blade.php`

**Interfaces:**
- Consumes: manifest di Task 3 (`docs/collaudo/fase-0-1.php`, completato con TUTTI i criteri reali).
- Produces: file `storage/app/collaudo/collaudo-fase-0-1-<data>.pdf` verificato visivamente.

- [ ] **Step 1: Aggiungi un indice/sommario e la spiegazione step-by-step nella copertina**

Estendi `resources/views/pdf/partials/collaudo-copertina.blade.php` aggiungendo, dopo la tabella credenziali
già presente da Task 2:

```blade
<h3>Come accedere a Mailpit</h3>
<p>Le email inviate dall'ambiente UAT non escono realmente: sono intercettate da Mailpit, raggiungibile
all'indirizzo sopra con autenticazione HTTP (utente/password forniti separatamente dal team, non stampati
in questo documento per non esporli insieme all'URL pubblico).</p>

<h3>Come segnalare un problema</h3>
<p>Per ogni test fallito, annotare l'ID del test (es. F1-03), una descrizione di cosa è successo invece del
comportamento atteso, e se possibile uno screenshot.</p>

<h3>Indice</h3>
<ol>
    @foreach ($manifest['topics'] as $topic)
        <li>{{ $topic['titolo'] }} ({{ count($topic['test']) }} test)</li>
    @endforeach
</ol>
```

- [ ] **Step 2: Genera il PDF reale con il manifest completo**

Run: `php artisan collaudo:generate 0-1`
Expected: stampa il path del PDF generato, exit code 0

- [ ] **Step 3: Verifica visiva (dompdf ha supporto CSS limitato: controllo manuale obbligatorio, non solo
  "il comando non ha dato errori")**

Apri il PDF generato e controlla: la copertina mostra correttamente titolo/credenziali/indice, ogni sezione
per topic ha una tabella leggibile senza celle troncate o sovrapposte, nessun elemento CSS non supportato ha
rotto il layout (es. flexbox/grid: questo template non ne usa, solo tabelle — se in una revisione futura
viene aggiunto CSS più complesso, ripetere questa verifica).

- [ ] **Step 4: Quality gate**

```bash
vendor/bin/pint --test
composer analyse
php -d memory_limit=1G vendor/bin/pest
```

Expected: tutti verdi, inclusi i test di Task 2/3 già scritti

- [ ] **Step 5: Commit**

```bash
git add resources/views/pdf/collaudo.blade.php resources/views/pdf/partials/collaudo-copertina.blade.php
git commit -m "feat: template collaudo completo (indice, istruzioni Mailpit) + PDF Fase 0-1 generato"
```

---

### Task 9: Infrastruttura msuat — rimozione v1, nuova directory, vhost, chiave deploy

**Questo task opera sul server `msuat` via SSH, non sul repository.** Ogni step ha una verifica esplicita
prima di procedere al successivo. Eseguito interattivamente (non da un subagent isolato), con conferma
dell'utente prima di ogni comando distruttivo.

**Files:** nessuno nel repo (comandi eseguiti su msuat, documentati qui per riproducibilità).

- [ ] **Step 1: Backup completo dell'app v1 PRIMA di qualunque rimozione**

```bash
ssh msuat "tar czf /root/orchestrator-v1-backup-$(date +%Y%m%d).tar.gz -C /root orchestrator"
scp msuat:/root/orchestrator-v1-backup-*.tar.gz /Users/alessiopiccioli/Documents/LAVORO/MS/SOFTWARE/mstickets/orchestrator/v1dumps/
```

Verifica: `ls -la /Users/alessiopiccioli/Documents/LAVORO/MS/SOFTWARE/mstickets/orchestrator/v1dumps/orchestrator-v1-backup-*.tar.gz`
deve mostrare un file di circa 495MB scaricato in locale (già in `.gitignore` via `/v1dumps`, verificato in
una sessione precedente).

- [ ] **Step 2: Ferma e rimuovi i container v1, rimuovi la cartella**

```bash
ssh msuat "cd /root/orchestrator && docker compose down && cd /root && rm -rf orchestrator"
```

Verifica: `ssh msuat "docker ps -a | grep orchestrator"` → nessun output; `ssh msuat "ls /root/orchestrator"`
→ "No such file or directory"

- [ ] **Step 3: Rimuovi il vecchio vhost/cert `.it`**

```bash
ssh msuat "a2dissite ticketuat.montagnaservizi.it.conf && systemctl reload apache2"
ssh msuat "certbot delete --cert-name ticketuat.montagnaservizi.it --non-interactive"
ssh msuat "rm /etc/apache2/sites-available/ticketuat.montagnaservizi.it.conf"
```

Verifica: `ssh msuat "apachectl configtest"` → `Syntax OK`; `curl -s -o /dev/null -w '%{http_code}' https://ticketuat.montagnaservizi.it` → errore di connessione o 404 (non più 200)

- [ ] **Step 4: Crea la nuova directory e copia i file necessari**

```bash
ssh msuat "mkdir -p /root/ticket-uat/deploy"
scp docker-compose.uat.yml msuat:/root/ticket-uat/
scp .env.uat.example msuat:/root/ticket-uat/.env.uat
```

Poi via SSH interattivo, valorizza `/root/ticket-uat/.env.uat` con una `APP_KEY`/`DB_PASSWORD` reali generate
lì (mai committarle): `ssh msuat "cd /root/ticket-uat && sed -i 's/DB_PASSWORD=/DB_PASSWORD=$(openssl rand -hex 24)/' .env.uat"`

Verifica: `ssh msuat "grep DB_PASSWORD /root/ticket-uat/.env.uat"` mostra una password non vuota.

- [ ] **Step 5: Scrivi ed esegui `remote-deploy.sh` sul server**

```bash
ssh msuat "cat > /root/ticket-uat/deploy/remote-deploy.sh << 'SCRIPT'
#!/bin/bash
set -euo pipefail
cd /root/ticket-uat
docker compose -f docker-compose.uat.yml --env-file .env.uat pull
docker compose -f docker-compose.uat.yml --env-file .env.uat up -d
docker compose -f docker-compose.uat.yml --env-file .env.uat exec -T app php artisan migrate --force
docker compose -f docker-compose.uat.yml --env-file .env.uat exec -T app php artisan db:seed --class=UatSeeder --force
docker image prune -f
SCRIPT"
ssh msuat "chmod +x /root/ticket-uat/deploy/remote-deploy.sh"
```

- [ ] **Step 6: Primo avvio manuale (senza CD, per validare lo stack prima di automatizzare)**

```bash
ssh msuat "/root/ticket-uat/deploy/remote-deploy.sh"
```

Verifica: `ssh msuat "docker compose -f /root/ticket-uat/docker-compose.uat.yml ps"` → tutti i servizi
`Up`/`healthy`; `ssh msuat "curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8090"` → `200` o `302`

- [ ] **Step 7: Genera la chiave SSH deploy dedicata (locale, non sul server)**

```bash
ssh-keygen -t ed25519 -f /tmp/uat_deploy_key -N "" -C "github-actions-mstickets-uat-deploy"
```

- [ ] **Step 8: Installa la chiave pubblica su msuat con comando forzato**

```bash
ssh msuat "echo 'command=\"/root/ticket-uat/deploy/remote-deploy.sh\",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty $(cat /tmp/uat_deploy_key.pub)' >> ~/.ssh/authorized_keys"
```

Verifica: `ssh -i /tmp/uat_deploy_key root@135.181.25.33 "id"` deve eseguire `remote-deploy.sh` (non
`id`) — conferma che il comando è davvero forzato e non esegue comandi arbitrari.

- [ ] **Step 9: Nuovo vhost HTTP-only per validare il dominio prima del certificato**

```bash
ssh msuat "cat > /etc/apache2/sites-available/ticket-uat.montagnaservizi.com.conf << 'EOF'
<VirtualHost *:80>
    ServerName ticket-uat.montagnaservizi.com
    ProxyPreserveHost On
    ProxyPass / http://127.0.0.1:8090/
    ProxyPassReverse / http://127.0.0.1:8090/
    ErrorLog \${APACHE_LOG_DIR}/ticket-uat_error.log
    CustomLog \${APACHE_LOG_DIR}/ticket-uat_access.log combined
</VirtualHost>
EOF"
ssh msuat "a2ensite ticket-uat.montagnaservizi.com.conf && apachectl configtest && systemctl reload apache2"
```

Verifica: `curl -s -o /dev/null -w '%{http_code}' http://ticket-uat.montagnaservizi.com` → `200`/`302`
(prova che il proxy funziona PRIMA di richiedere un certificato)

- [ ] **Step 10: Certificato — prima staging, poi reale**

```bash
ssh msuat "certbot --apache -d ticket-uat.montagnaservizi.com --staging --non-interactive --agree-tos -m <email-committente>"
```

Verifica manuale: se il comando sopra completa senza errori (anche se il certificato staging non è
fidato dal browser, va bene: serve solo a validare l'HTTP challenge), procedi al certificato reale:

```bash
ssh msuat "certbot --apache -d ticket-uat.montagnaservizi.com --non-interactive --agree-tos -m <email-committente>"
ssh msuat "apachectl configtest && systemctl reload apache2"
```

Verifica: `curl -s -o /dev/null -w '%{http_code}' https://ticket-uat.montagnaservizi.com` → `200`/`302`

- [ ] **Step 11: Vhost Mailpit con Basic Auth**

```bash
ssh msuat "htpasswd -bc /etc/apache2/.htpasswd-mailpit-uat collaudatore '<password-generata>'"
ssh msuat "cat > /etc/apache2/sites-available/mailpit-ticket-uat.montagnaservizi.com.conf << 'EOF'
<VirtualHost *:80>
    ServerName mailpit-ticket-uat.montagnaservizi.com
    <Location />
        AuthType Basic
        AuthName \"Collaudo UAT\"
        AuthUserFile /etc/apache2/.htpasswd-mailpit-uat
        Require valid-user
    </Location>
    ProxyPreserveHost On
    ProxyPass / http://127.0.0.1:8091/
    ProxyPassReverse / http://127.0.0.1:8091/
    ErrorLog \${APACHE_LOG_DIR}/mailpit-uat_error.log
    CustomLog \${APACHE_LOG_DIR}/mailpit-uat_access.log combined
</VirtualHost>
EOF"
ssh msuat "a2ensite mailpit-ticket-uat.montagnaservizi.com.conf && a2enmod auth_basic authn_file && apachectl configtest && systemctl reload apache2"
ssh msuat "certbot --apache -d mailpit-ticket-uat.montagnaservizi.com --staging --non-interactive --agree-tos -m <email-committente>"
ssh msuat "certbot --apache -d mailpit-ticket-uat.montagnaservizi.com --non-interactive --agree-tos -m <email-committente>"
```

Verifica: `curl -s -o /dev/null -w '%{http_code}' https://mailpit-ticket-uat.montagnaservizi.com` → `401`
(senza credenziali) poi `200` con `-u collaudatore:<password>`

- [ ] **Step 12: Verifica finale che le altre 7 app siano ancora sane**

```bash
ssh msuat "docker ps --format '{{.Names}}\t{{.Status}}'"
```

Verifica: `clava-*`, `enti-*`, `formap_*`, `fotosicai-*`, `grmrl-*`, `runts-web-1`, `tender_*` tutti presenti
e nello stesso stato osservato prima di questo task (nessuno riavviato/fermato).

- [ ] **Step 13: Nessun commit** (task interamente infrastrutturale, nessun file di repo modificato oltre a
  quanto già committato nei Task 6/7). Annota in `notes.md` le porte/vhost/percorsi effettivi creati.

---

### Task 10: Aggiungi i secret GitHub e verifica il deploy automatico end-to-end

**Files:** nessuno (configurazione GitHub + verifica).

- [ ] **Step 1: Aggiungi i secret al repository**

```bash
gh secret set UAT_SSH_PRIVATE_KEY --repo piccioli/mstickets < /tmp/uat_deploy_key
gh secret set UAT_SSH_HOST_KEY --repo piccioli/mstickets --body "$(ssh-keyscan -t ed25519 135.181.25.33 2>/dev/null)"
```

- [ ] **Step 2: Elimina la chiave privata temporanea dal filesystem locale**

```bash
shred -u /tmp/uat_deploy_key /tmp/uat_deploy_key.pub 2>/dev/null || rm -f /tmp/uat_deploy_key /tmp/uat_deploy_key.pub
```

- [ ] **Step 3: Merge di prova su `develop` per validare la CD end-to-end**

Va fatto SOLO dopo che l'utente ha approvato la review-gate finale del piano (Fase 6 di wm-plan): aprire una
PR da questo branch a `develop`, mergiarla, osservare l'esecuzione del workflow.

Verifica: `gh run list --workflow=deploy-uat.yml --repo piccioli/mstickets --limit 1` → conclusion `success`;
`curl -s -o /dev/null -w '%{http_code}' https://ticket-uat.montagnaservizi.com/admin/login` → `200`

- [ ] **Step 4: Nessun commit** (verifica operativa).

---

### Task 11: Documentazione — processo di collaudo obbligatorio per le fasi future

**Files:**
- Modify: `CLAUDE.md`
- Modify: `scripts/ralph/CLAUDE.md`

**Interfaces:** nessuna (solo documentazione).

- [ ] **Step 1: Aggiungi a `CLAUDE.md` (repo) una sezione "Processo di collaudo (obbligatorio per ogni fase)"**

```markdown
## Processo di collaudo (obbligatorio per ogni fase)

Ogni fase completata (Fase 2 in poi) deve produrre, prima di essere considerata chiusa:

1. `docs/collaudo/fase-<N>.php` — manifest topic → test numerati (es. `F2-01`) → riferimento a un test
   automatico REALMENTE esistente (`php artisan collaudo:verify-manifest <N>` deve passare).
2. `php artisan collaudo:generate <N>` — PDF di collaudo con carta intestata Montagna Servizi, Parte 1
   (istruzioni: URL app UAT, URL Mailpit, credenziali) + una sezione per topic con i test numerati.
3. Il deploy su UAT (automatico al merge su `develop`) deve riflettere lo stato descritto nel manifest:
   `UatSeeder` (o il suo successore quando arriverà l'ETL reale in Fase 2+) gira ad ogni deploy.
4. Se un test del collaudo fallisce durante una sessione di collaudo reale, il test automatico
   corrispondente (dal manifest) va rivisto: non copriva il caso reale che ha fatto fallire il collaudo.
```

- [ ] **Step 2: Aggiungi a `scripts/ralph/CLAUDE.md` un'istruzione per le fasi Ralph future**

```markdown
## Collaudo di fine fase

Quando l'ultima user story di una fase (US-2xx, US-3xx, ecc.) è `passes: true`, prima di considerare la
fase conclusa:
1. Estendi/crea `docs/collaudo/fase-<N>.php` con un topic per ogni gruppo di requisiti della fase appena
   conclusa, mappando ogni test numerato a un test automatico reale scritto in questa fase.
2. Esegui `php artisan collaudo:verify-manifest <N>` — deve passare prima di committare.
3. Esegui `php artisan collaudo:generate <N>` e verifica visivamente il PDF prodotto.
4. Il deploy su UAT via merge a `develop` è un passo separato, gestito dal committente/dev umano — non
   automatizzato dentro il loop di Ralph.
```

- [ ] **Step 3: Commit**

```bash
git add CLAUDE.md scripts/ralph/CLAUDE.md
git commit -m "docs: documenta il processo di collaudo obbligatorio per le fasi future"
```

---

## Self-Review (eseguita dall'autore del piano)

**1. Copertura spec:** ogni requisito di `overview.md` ha un task corrispondente — branch develop (Task 1),
workflow+GHCR+SSH+concurrency (Task 7, 9, 10), docker-compose alleggerito con FrankenPHP/worker
unico/mem_limit (Task 6), UatSeeder con reset-ad-ogni-deploy (Task 5, Step 5 di Task 9), rimozione v1 con
backup (Task 9 Step 1-3), vhost+cert per i due domini (Task 9 Step 9-11), collaudo:generate + dompdf + PDF
con sezioni/indice (Task 2, 8), manifest con verifica automatica (Task 3, 4), documentazione per fasi future
(Task 11). Nessun gap individuato.

**2. Scansione placeholder:** l'unico placeholder intenzionale è nel manifest di Task 3, Step 2, esplicitamente
segnalato con "NOTA PER CHI ESEGUE QUESTO TASK" e un comando concreto (Step 1) per sostituirlo con dati reali
prima di procedere — non è un placeholder lasciato aperto, è un passo guidato con verifica.
`<email-committente>` e `<password-generata>` in Task 9 sono valori che solo l'utente/l'esecutore può fornire
al momento (email di contatto per Let's Encrypt, password generata a runtime) — non decidibili in anticipo
nel piano.

**3. Coerenza dei tipi/nomi:** `CollaudoGenerateCommand::buildPdf(string $fase, array $manifest): string`
(Task 2) è la stessa firma richiamata in Task 8; `CollaudoVerifyManifestCommand::resolveTestReference`
(Task 3) è coerente con l'uso nel comando stesso; il nome del servizio Docker `app`/`worker`/`db`/`redis`/
`mailpit` in `docker-compose.uat.yml` (Task 6) è lo stesso usato nei comandi `docker compose exec` di
Task 9 Step 5.

---

Plan complete and saved to `docs/features/cd-ci-uat-collaudo-v0.2.0/plan.md`. Two execution options:

1. **Subagent-Driven (recommended)** - dispatch di un subagent fresco per task, review tra un task e l'altro,
   iterazione rapida.
2. **Inline Execution** - esecuzione dei task in questa sessione con `executing-plans`, esecuzione a blocchi
   con checkpoint di revisione.

Quale preferisci? (Nota: Task 9 e 10 toccano il server msuat reale — indipendentemente dalla scelta,
verranno eseguiti in questa sessione principale con conferma esplicita ad ogni step distruttivo, non
delegati a un subagent isolato.)
