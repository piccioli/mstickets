# Wiring RUNTS live-sync lato Orchestrator (Fase 9, Storia 3) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Wire the PHP side of RUNTS live sync: the "Sincronizza dati RUNTS" dashboard button synchronously
writes registration metadata + board members + newly-downloaded bilancio documents, then asynchronously
queues financial-figure extraction for each new bilancio document on a dedicated Horizon queue.

**Architecture:** `CaiRuntsScraperClient` (thin `Http::` wrapper, no retry logic of its own — the Python
service already retries internally per its design) talks to the `cai-runts-scraper` service built in
`docs/superpowers/plans/2026-09-08-cai-runts-scraper-service.md`. Field mapping from the live JSON response
to `CaiRuntsRegistration`/`CaiBoardMember`/`CaiFinancialStatement` columns is extracted into two new shared
mapper classes (`CaiRuntsRegistrationFieldMapper`, `CaiFinancialStatementFieldMapper`), refactored out of
`CaiDatapackImporter` exactly as `CaiSectionFieldMapper` already was in Story 1 — the datapack import and the
live sync always write through the same mapping logic. `SyncCaiRuntsRegistration` does the synchronous
metadata+document work; `AnalyzeCaiFinancialStatementDocument` (queued, `cai-runts-analysis` queue) does the
asynchronous financial-figure extraction.

**Tech Stack:** Laravel 13 `Http` facade, Laravel Horizon (new dedicated queue supervisor — the first
non-`default` queue in this repo), Pest 4.x with `Http::fake()` (never a real `cai-runts-scraper` HTTP call
in automated tests).

**Spec:** `docs/superpowers/specs/2026-09-07-cai-runts-scraper-service-design.md` (§4-§6). Depends on
`docs/superpowers/plans/2026-09-08-cai-runts-scraper-service.md` being implemented first (a reachable
`cai-runts-scraper` service is needed for the manual end-to-end verification in Task 10, though every
automated test in this plan mocks the service via `Http::fake()` and does not require it to be running).

## Global Constraints

- PHP ^8.4, every new/modified PHP file starts with `declare(strict_types=1);`.
- Domain code under `app/Domain/CaiDirectory/{Import,Support,Actions,Jobs}` — never outside that structure.
- `env()` only inside `config/*.php` — everywhere else reads `config('cai_directory....')`.
- A column added to an Eloquent model must be added to its `#[Fillable([...])]` attribute or writes are
  silently dropped (repeat of the Story 1/US-703 gotcha, applies again here to `runts_last_synced_at`).
- `CaiImportTableResult`/`DiffsAttributes` (from Story 1, `app/Domain/CaiDirectory/Import/`) are reused
  as-is where they fit — never re-implemented.
- Every automated PHP test in this plan uses `Http::fake([...])` to stand in for the `cai-runts-scraper`
  service — never a real HTTP call to it, matching how `CaiApiClientTest`/`ScrapeCaiSectionTest` (Story 1)
  test the CAI API boundary.
- `AnalyzeCaiFinancialStatementDocument`'s constructor takes an **id** (`int $caiDocumentId`), never a
  serialized `CaiDocument` model instance — same pattern already established by
  `GenerateDocumentationPagePdfJob` in this repo.
- Only `tipo === 'bilancio_esercizio'` triggers the financial-analysis job — `situazione_patrimoniale`/
  `bilancio_sociale` and every other document type are stored as `CaiDocument` rows but never analyzed (the
  PDF-parsing regexes in the Python service's `analyzer.py` are built specifically for the
  "Rendiconto Gestionale"/"Rendiconto di Cassa" layout used by `bilancio_esercizio` filings).

---

### Task 1: Migrazione `runts_last_synced_at` + aggiornamento modello

**Files:**
- Create: `database/migrations/2026_09_08_100000_add_runts_last_synced_at_to_cai_runts_registrations_table.php`
- Modify: `app/Domain/CaiDirectory/Models/CaiRuntsRegistration.php`
- Test: `tests/Feature/Database/CaiRuntsRegistrationLastSyncedAtTest.php`

**Interfaces:**
- Produces: nullable `cai_runts_registrations.runts_last_synced_at` (timestamp), mass-assignable and cast to
  `datetime` on `CaiRuntsRegistration` — consumed by Task 5 (write) and Task 9 (display).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('cai_runts_registrations has a nullable runts_last_synced_at column, mass-assignable and cast to datetime', function (): void {
    expect(Schema::hasColumn('cai_runts_registrations', 'runts_last_synced_at'))->toBeTrue();

    $syncedAt = Carbon::parse('2026-09-08 09:00:00');

    $registration = CaiRuntsRegistration::create([
        'id_runts' => 'RUNTS-TEST-1',
        'runts_last_synced_at' => $syncedAt,
    ])->fresh();

    expect($registration->runts_last_synced_at)->toBeInstanceOf(Carbon::class);
    expect($registration->runts_last_synced_at->equalTo($syncedAt))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Database/CaiRuntsRegistrationLastSyncedAtTest.php`
Expected: FAIL — column doesn't exist yet.

- [ ] **Step 3: Write the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cai_runts_registrations', function (Blueprint $table) {
            // Fase 9, storia 3: valorizzata solo da SyncCaiRuntsRegistration (scrape live),
            // resta null per una registrazione importata solo dal datapack statico.
            $table->timestamp('runts_last_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cai_runts_registrations', function (Blueprint $table) {
            $table->dropColumn('runts_last_synced_at');
        });
    }
};
```

- [ ] **Step 4: Update the model**

In `app/Domain/CaiDirectory/Models/CaiRuntsRegistration.php`, add `'runts_last_synced_at'` to the
`#[Fillable([...])]` list and add `'runts_last_synced_at' => 'datetime'` to `casts()`:

```php
#[Fillable([
    'id_runts', 'cai_section_id', 'tax_code', 'name', 'legal_form', 'legal_nature', 'address',
    'street_number', 'municipality', 'province', 'region', 'postal_code', 'latitude', 'longitude',
    'registration_date', 'register_section', 'activity_sectors', 'legal_representative', 'website',
    'pec', 'official_page_url', 'runts_last_synced_at',
])]
class CaiRuntsRegistration extends Model
{
    protected $primaryKey = 'id_runts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'registration_date' => 'date',
            'runts_last_synced_at' => 'datetime',
        ];
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/Database/CaiRuntsRegistrationLastSyncedAtTest.php`
Expected: PASS (1 test).

- [ ] **Step 6: Run the existing CAI datapack regression suite**

Run: `vendor/bin/pest tests/Feature/Console/CaiImportDatapackCommandTest.php`
Expected: PASS, unchanged.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_09_08_100000_add_runts_last_synced_at_to_cai_runts_registrations_table.php app/Domain/CaiDirectory/Models/CaiRuntsRegistration.php tests/Feature/Database/CaiRuntsRegistrationLastSyncedAtTest.php
git commit -m "feat: Fase 9 storia 3.1 - aggiunge runts_last_synced_at a cai_runts_registrations"
```

---

### Task 2: `CaiRuntsRegistrationFieldMapper` condiviso

**Files:**
- Create: `app/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapper.php`
- Modify: `app/Domain/CaiDirectory/Import/CaiDatapackImporter.php`
- Test: `tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php`

**Interfaces:**
- Produces: `CaiRuntsRegistrationFieldMapper::mapRegistration(object $row, string $sectionCode): array`,
  `::mapBoardMember(object $row, string $idRunts): array` — consumed by `CaiDatapackImporter` (this task) and
  `SyncCaiRuntsRegistration` (Task 5).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\CaiRuntsRegistrationFieldMapper;

test('mapRegistration maps a row to CaiRuntsRegistration attributes', function (): void {
    $row = (object) [
        'codice_fiscale' => '01234567890',
        'denominazione' => 'Sezione di Como',
        'forma_giuridica' => 'Associazione',
        'natura_giuridica' => null,
        'sede_indirizzo' => 'Via Roma',
        'sede_civico' => '1',
        'sede_comune' => 'Como',
        'sede_provincia' => 'CO',
        'sede_regione' => 'LOMBARDIA',
        'sede_cap' => '22100',
        'data_iscrizione' => 'Iscritto il 24/02/2023',
        'sezione_registro' => 'APS',
        'settori_attivita' => null,
        'rappresentante_legale' => 'Mario Rossi',
        'sito_web' => 'https://caicomo.it',
        'pec' => 'como@pec.cai.it',
        'url_dettaglio' => 'https://servizi.lavoro.gov.it/detail/12345',
    ];

    $attributes = CaiRuntsRegistrationFieldMapper::mapRegistration($row, '9216049');

    expect($attributes)->toBe([
        'cai_section_id' => '9216049',
        'tax_code' => '01234567890',
        'name' => 'Sezione di Como',
        'legal_form' => 'Associazione',
        'legal_nature' => null,
        'address' => 'Via Roma',
        'street_number' => '1',
        'municipality' => 'Como',
        'province' => 'CO',
        'region' => 'LOMBARDIA',
        'postal_code' => '22100',
        'latitude' => null,
        'longitude' => null,
        'registration_date' => '2023-02-24',
        'register_section' => 'APS',
        'activity_sectors' => null,
        'legal_representative' => 'Mario Rossi',
        'website' => 'https://caicomo.it',
        'pec' => 'como@pec.cai.it',
        'official_page_url' => 'https://servizi.lavoro.gov.it/detail/12345',
    ]);
});

test('mapRegistration reads lat/lon when present (datapack source)', function (): void {
    $row = (object) [
        'codice_fiscale' => null, 'denominazione' => 'X', 'forma_giuridica' => null,
        'natura_giuridica' => null, 'sede_indirizzo' => null, 'sede_civico' => null,
        'sede_comune' => null, 'sede_provincia' => null, 'sede_regione' => null, 'sede_cap' => null,
        'data_iscrizione' => null, 'sezione_registro' => null, 'settori_attivita' => null,
        'rappresentante_legale' => null, 'sito_web' => null, 'pec' => null, 'url_dettaglio' => null,
        'lat' => '45.81', 'lon' => '9.08',
    ];

    $attributes = CaiRuntsRegistrationFieldMapper::mapRegistration($row, '9216049');

    expect($attributes['latitude'])->toBe(45.81);
    expect($attributes['longitude'])->toBe(9.08);
});

test('mapBoardMember concatenates nome+cognome into full_name and parses dates', function (): void {
    $row = (object) [
        'ruolo' => 'presidente',
        'nome' => 'Mario',
        'cognome' => 'Rossi',
        'codice_fiscale' => 'RSSMRA80A01H501X',
        'valid_from' => '24/02/2023',
        'valid_to' => null,
    ];

    $attributes = CaiRuntsRegistrationFieldMapper::mapBoardMember($row, '12345');

    expect($attributes)->toBe([
        'cai_runts_registration_id' => '12345',
        'role' => 'presidente',
        'full_name' => 'Mario Rossi',
        'tax_code' => 'RSSMRA80A01H501X',
        'valid_from' => '2023-02-24',
        'valid_to' => null,
    ]);
});

test('mapBoardMember tolerates missing nome/cognome and produces a null full_name', function (): void {
    $row = (object) ['ruolo' => 'consigliere', 'nome' => null, 'cognome' => null, 'codice_fiscale' => null, 'valid_from' => null, 'valid_to' => null];

    $attributes = CaiRuntsRegistrationFieldMapper::mapBoardMember($row, '12345');

    expect($attributes['full_name'])->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Create `CaiRuntsRegistrationFieldMapper`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

/**
 * Mappatura pura riga→attributi per `CaiRuntsRegistration`/`CaiBoardMember` (Fase 9, storia 3): estratta da
 * {@see CaiDatapackImporter} perché va condivisa, non duplicata, tra l'import da datapack statico
 * (`importRegistrations()`/`importBoardMembers()`) e lo scrape live
 * ({@see \App\Domain\CaiDirectory\Actions\SyncCaiRuntsRegistration}) — stesso principio già applicato a
 * {@see CaiSectionFieldMapper} in Fase 9 storia 1. `lat`/`lon` sono presenti solo sulla riga del datapack
 * (mai geocodificati dallo scrape RUNTS live): letti con `?? null`, mai un accesso diretto a proprietà che
 * potrebbe non esistere sull'oggetto costruito dalla risposta JSON del servizio live.
 */
final class CaiRuntsRegistrationFieldMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function mapRegistration(object $row, string $sectionCode): array
    {
        return [
            'cai_section_id' => $sectionCode,
            'tax_code' => $row->codice_fiscale,
            'name' => $row->denominazione,
            'legal_form' => $row->forma_giuridica,
            'legal_nature' => $row->natura_giuridica,
            'address' => $row->sede_indirizzo,
            'street_number' => $row->sede_civico,
            'municipality' => $row->sede_comune,
            'province' => $row->sede_provincia,
            'region' => $row->sede_regione,
            'postal_code' => $row->sede_cap,
            'latitude' => CaiSectionFieldMapper::toCoordinate($row->lat ?? null),
            'longitude' => CaiSectionFieldMapper::toCoordinate($row->lon ?? null),
            'registration_date' => CaiRuntsDateParser::parse($row->data_iscrizione),
            'register_section' => $row->sezione_registro,
            'activity_sectors' => $row->settori_attivita,
            'legal_representative' => $row->rappresentante_legale,
            'website' => $row->sito_web,
            'pec' => $row->pec,
            'official_page_url' => $row->url_dettaglio,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapBoardMember(object $row, string $idRunts): array
    {
        $fullName = trim(implode(' ', array_filter(
            [$row->nome ?? null, $row->cognome ?? null],
            fn (mixed $part): bool => $part !== null && trim((string) $part) !== '',
        )));
        $fullName = $fullName === '' ? null : $fullName;

        return [
            'cai_runts_registration_id' => $idRunts,
            'role' => $row->ruolo,
            'full_name' => $fullName,
            'tax_code' => $row->codice_fiscale ?? null,
            'valid_from' => CaiRuntsDateParser::parse($row->valid_from ?? null),
            'valid_to' => CaiRuntsDateParser::parse($row->valid_to ?? null),
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php`
Expected: PASS (4 tests).

- [ ] **Step 5: Refactor `CaiDatapackImporter::importRegistrations()`**

Replace the inline `$attributes = [...]` array (from `'cai_section_id' => $sectionCode,` through
`'official_page_url' => $row->url_dettaglio,`) with:

```php
$attributes = CaiRuntsRegistrationFieldMapper::mapRegistration($row, $sectionCode);
```

- [ ] **Step 6: Refactor `CaiDatapackImporter::importBoardMembers()`**

Replace the `$fullName`/`$validFrom`/`$validTo`/`$attributes` block with:

```php
$attributes = CaiRuntsRegistrationFieldMapper::mapBoardMember($row, (string) $row->id_runts);
```

Then update the dedup query immediately below it to read from `$attributes` instead of the now-removed local
variables:

```php
$existing = CaiBoardMember::query()
    ->where('cai_runts_registration_id', $attributes['cai_runts_registration_id'])
    ->where('role', $attributes['role'])
    ->when(
        $attributes['tax_code'] === null,
        fn ($query) => $query->whereNull('tax_code'),
        fn ($query) => $query->where('tax_code', $attributes['tax_code']),
    )
    ->when(
        $attributes['valid_from'] === null,
        fn ($query) => $query->whereNull('valid_from'),
        fn ($query) => $query->where('valid_from', $attributes['valid_from']),
    )
    ->first();
```

- [ ] **Step 7: Run the full existing CAI datapack regression suite**

Run: `vendor/bin/pest tests/Feature/Console/CaiImportDatapackCommandTest.php`
Expected: PASS, unchanged counts.

- [ ] **Step 8: Commit**

```bash
git add app/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapper.php app/Domain/CaiDirectory/Import/CaiDatapackImporter.php tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php
git commit -m "feat: Fase 9 storia 3.2 - estrae CaiRuntsRegistrationFieldMapper condiviso"
```

---

### Task 3: `CaiFinancialStatementFieldMapper` condiviso

**Files:**
- Create: `app/Domain/CaiDirectory/Import/CaiFinancialStatementFieldMapper.php`
- Modify: `app/Domain/CaiDirectory/Import/CaiDatapackImporter.php`
- Test: `tests/Unit/Domain/CaiDirectory/Import/CaiFinancialStatementFieldMapperTest.php`

**Interfaces:**
- Produces: `CaiFinancialStatementFieldMapper::mapFinancialStatement(object $row): array` (the 15
  English-named `CaiFinancialStatement` columns only — never `cai_runts_registration_id`/`year`, added
  separately by each caller) — consumed by `CaiDatapackImporter` (this task) and
  `AnalyzeCaiFinancialStatementDocument` (Task 6).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\CaiFinancialStatementFieldMapper;

test('mapFinancialStatement maps the 15 Italian-named analyzer fields to CaiFinancialStatement columns', function (): void {
    $row = (object) [
        'oneri_a_interesse_generale' => 1000.0,
        'oneri_b_attivita_diverse' => null,
        'oneri_c_raccolta_fondi' => null,
        'oneri_d_finanziarie_patrimoniali' => null,
        'oneri_e_supporto_generale' => 200.0,
        'totale_oneri' => 1200.0,
        'proventi_a_interesse_generale' => 1500.0,
        'proventi_b_attivita_diverse' => null,
        'proventi_c_raccolta_fondi' => null,
        'proventi_d_finanziarie_patrimoniali' => null,
        'proventi_e_supporto_generale' => null,
        'totale_proventi' => 1500.0,
        'risultato_ante_imposte' => 300.0,
        'imposte' => 50.0,
        'risultato_esercizio' => 250.0,
    ];

    expect(CaiFinancialStatementFieldMapper::mapFinancialStatement($row))->toBe([
        'general_interest_expenses' => 1000.0,
        'other_activities_expenses' => null,
        'fundraising_expenses' => null,
        'financial_expenses' => null,
        'overhead_expenses' => 200.0,
        'total_expenses' => 1200.0,
        'general_interest_revenues' => 1500.0,
        'other_activities_revenues' => null,
        'fundraising_revenues' => null,
        'financial_revenues' => null,
        'overhead_revenues' => null,
        'total_revenues' => 1500.0,
        'pre_tax_result' => 300.0,
        'taxes' => 50.0,
        'net_result' => 250.0,
    ]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/Domain/CaiDirectory/Import/CaiFinancialStatementFieldMapperTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Create `CaiFinancialStatementFieldMapper`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

/**
 * Mappatura pura dei 15 campi finanziari (Fase 9, storia 3): stessi nomi italiani già usati dalla tabella
 * `bilanci` del datapack statico E dalla risposta di `POST /analyze/bilancio` del servizio live
 * (`extract_bilancio_pdf()` nel prototipo Python restituisce esattamente queste chiavi, verificato — design
 * doc §3.4/§4.4: nessuna traduzione lato Python, il mapper condiviso vive solo qui). Non include mai
 * `cai_runts_registration_id`/`year`: quei due campi hanno una fonte diversa a seconda del chiamante
 * (colonne dirette della riga datapack per l'import statico, il `CaiDocument` già noto per il job di
 * analisi live) e restano responsabilità del chiamante.
 */
final class CaiFinancialStatementFieldMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function mapFinancialStatement(object $row): array
    {
        return [
            'general_interest_expenses' => $row->oneri_a_interesse_generale,
            'other_activities_expenses' => $row->oneri_b_attivita_diverse,
            'fundraising_expenses' => $row->oneri_c_raccolta_fondi,
            'financial_expenses' => $row->oneri_d_finanziarie_patrimoniali,
            'overhead_expenses' => $row->oneri_e_supporto_generale,
            'total_expenses' => $row->totale_oneri,
            'general_interest_revenues' => $row->proventi_a_interesse_generale,
            'other_activities_revenues' => $row->proventi_b_attivita_diverse,
            'fundraising_revenues' => $row->proventi_c_raccolta_fondi,
            'financial_revenues' => $row->proventi_d_finanziarie_patrimoniali,
            'overhead_revenues' => $row->proventi_e_supporto_generale,
            'total_revenues' => $row->totale_proventi,
            'pre_tax_result' => $row->risultato_ante_imposte,
            'taxes' => $row->imposte,
            'net_result' => $row->risultato_esercizio,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/Domain/CaiDirectory/Import/CaiFinancialStatementFieldMapperTest.php`
Expected: PASS (1 test).

- [ ] **Step 5: Refactor `CaiDatapackImporter::importFinancialStatements()`**

Replace the inline `$attributes = [...]` array with:

```php
$attributes = [
    'cai_runts_registration_id' => $row->id_runts,
    'year' => CaiSectionFieldMapper::toInt($row->anno),
    ...CaiFinancialStatementFieldMapper::mapFinancialStatement($row),
];
```

- [ ] **Step 6: Run the full existing CAI datapack regression suite**

Run: `vendor/bin/pest tests/Feature/Console/CaiImportDatapackCommandTest.php`
Expected: PASS, unchanged counts.

- [ ] **Step 7: Commit**

```bash
git add app/Domain/CaiDirectory/Import/CaiFinancialStatementFieldMapper.php app/Domain/CaiDirectory/Import/CaiDatapackImporter.php tests/Unit/Domain/CaiDirectory/Import/CaiFinancialStatementFieldMapperTest.php
git commit -m "feat: Fase 9 storia 3.3 - estrae CaiFinancialStatementFieldMapper condiviso"
```

---

### Task 4: `CaiRuntsScraperClient` + configurazione

**Files:**
- Create: `app/Domain/CaiDirectory/Support/CaiRuntsScraperClient.php`
- Modify: `config/cai_directory.php`
- Modify: `.env.example`
- Test: `tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php`

**Interfaces:**
- Consumes: `config('cai_directory.runts_scraper.base_url')`.
- Produces: `CaiRuntsScraperClient::scrapeEntity(string $codiceFiscale): array`,
  `::analyzeBilancio(string $pdfContent): array` — consumed by `SyncCaiRuntsRegistration` (Task 5/7) and
  `AnalyzeCaiFinancialStatementDocument` (Task 6).

- [ ] **Step 1: Add config + env**

In `config/cai_directory.php`, add a `'runts_scraper'` key after the existing `'sync_national'` key:

```php
    /*
     * Servizio Python cai-runts-scraper (Fase 9, storia 2/3, design doc
     * `2026-09-07-cai-runts-scraper-service-design.md`): raggiunto via la rete Docker Compose interna dal
     * nome del servizio, mai una porta pubblicata verso l'host in produzione/UAT.
     */
    'runts_scraper' => [
        'base_url' => env('CAI_RUNTS_SCRAPER_BASE_URL', 'http://cai-runts-scraper:8000'),
        'scrape_timeout_seconds' => (int) env('CAI_RUNTS_SCRAPER_SCRAPE_TIMEOUT_SECONDS', 150),
        'analyze_timeout_seconds' => (int) env('CAI_RUNTS_SCRAPER_ANALYZE_TIMEOUT_SECONDS', 60),
    ],
```

In `.env.example`, add after the existing `CAI_SYNC_NATIONAL_SCHEDULE_CRON`/`ENABLE_CAI_SYNC_NATIONAL` lines:

```
CAI_RUNTS_SCRAPER_BASE_URL=http://cai-runts-scraper:8000
CAI_RUNTS_SCRAPER_SCRAPE_TIMEOUT_SECONDS=150
CAI_RUNTS_SCRAPER_ANALYZE_TIMEOUT_SECONDS=60
```

- [ ] **Step 2: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Support\CaiRuntsScraperClient;
use Illuminate\Support\Facades\Http;

test('scrapeEntity sends codice_fiscale as a query parameter and returns the decoded JSON', function (): void {
    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(['found' => false]),
    ]);

    $result = app(CaiRuntsScraperClient::class)->scrapeEntity('01234567890');

    expect($result)->toBe(['found' => false]);
    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'codice_fiscale=01234567890'));
});

test('analyzeBilancio sends the PDF as a multipart file upload and returns the decoded JSON', function (): void {
    Http::fake([
        'http://cai-runts-scraper:8000/analyze/bilancio' => Http::response(['totale_oneri' => 1200.0]),
    ]);

    $result = app(CaiRuntsScraperClient::class)->analyzeBilancio('%PDF-1.4 fixture');

    expect($result)->toBe(['totale_oneri' => 1200.0]);
    Http::assertSent(fn ($request): bool => $request->hasFile('file'));
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php`
Expected: FAIL — class not found.

- [ ] **Step 4: Create `CaiRuntsScraperClient`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use Illuminate\Support\Facades\Http;

/**
 * Client verso il servizio Python `cai-runts-scraper` (Fase 9, storia 3, design doc §4.2). A differenza di
 * {@see CaiApiClient} (Storia 1, API pubblica CAI), NON implementa retry proprio: il servizio Python già
 * ritenta internamente lo scrape (3 tentativi, backoff esponenziale — design doc §1), un secondo livello di
 * retry qui raddoppierebbe inutilmente il tempo di attesa in caso di fallimento reale.
 */
final class CaiRuntsScraperClient
{
    /**
     * @return array<string, mixed>
     */
    public function scrapeEntity(string $codiceFiscale): array
    {
        $response = Http::timeout((int) config('cai_directory.runts_scraper.scrape_timeout_seconds'))
            ->withOptions(['query' => ['codice_fiscale' => $codiceFiscale]])
            ->post($this->baseUrl().'/scrape/runts-entity');

        $response->throw();

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function analyzeBilancio(string $pdfContent): array
    {
        $response = Http::timeout((int) config('cai_directory.runts_scraper.analyze_timeout_seconds'))
            ->attach('file', $pdfContent, 'bilancio.pdf')
            ->post($this->baseUrl().'/analyze/bilancio');

        $response->throw();

        return $response->json();
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('cai_directory.runts_scraper.base_url'), '/');
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Domain/CaiDirectory/Support/CaiRuntsScraperClient.php config/cai_directory.php .env.example tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php
git commit -m "feat: Fase 9 storia 3.4 - CaiRuntsScraperClient verso il servizio cai-runts-scraper"
```

---

### Task 5: `SyncCaiRuntsRegistrationResult` + `SyncCaiRuntsRegistration` (metadati + cariche sociali)

**Files:**
- Create: `app/Domain/CaiDirectory/Support/SyncCaiRuntsRegistrationResult.php`
- Create: `app/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistration.php`
- Modify: `tests/Pest.php` (add `caiRuntsRegistration()` fixture helper)
- Test: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php`

**Interfaces:**
- Consumes: `CaiRuntsScraperClient::scrapeEntity()` (Task 4), `CaiRuntsRegistrationFieldMapper` (Task 2).
- Produces: `SyncCaiRuntsRegistration::run(CaiSection $section): SyncCaiRuntsRegistrationResult` — this task
  implements steps 1-3 of the design doc §4.3 (not-found handling, registration upsert, board members); Task
  7 adds step 4 (documents) to the same class.

- [ ] **Step 1: Add the `caiRuntsRegistration()` test fixture helper**

Append to `tests/Pest.php` (after the existing `caiSection()` function, end of file):

```php

/**
 * Crea una `CaiRuntsRegistration` di test con un `id_runts` univoco (sequenza incrementale), collegata a una
 * `CaiSection` fresca se `cai_section_id` non è passato esplicitamente.
 *
 * @param  array<string, mixed>  $attributes
 */
function caiRuntsRegistration(array $attributes = []): CaiRuntsRegistration
{
    static $sequence = 0;
    $sequence++;

    if (! array_key_exists('cai_section_id', $attributes)) {
        $attributes['cai_section_id'] = caiSection()->codice_cai;
    }

    return CaiRuntsRegistration::create(array_merge([
        'id_runts' => 'RUNTS-'.$sequence,
        'name' => 'Ente RUNTS '.$sequence,
    ], $attributes))->fresh();
}
```

Add `use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;` to the `use` block at the top of `tests/Pest.php`
(alphabetically, right after `use App\Domain\CaiDirectory\Models\CaiSection;` if Story 1 already added that
import there, otherwise in the same alphabetical position among the `App\Domain\CaiDirectory\...` imports).

- [ ] **Step 2: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Actions\SyncCaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiBoardMember;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function fakeRuntsEntityFound(string $codiceFiscale, array $overrides = []): void
{
    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(array_merge([
            'found' => true,
            'entity' => [
                'id_runts' => '12345',
                'codice_fiscale' => $codiceFiscale,
                'denominazione' => 'Sezione di Como',
                'forma_giuridica' => null, 'natura_giuridica' => null,
                'sede_indirizzo' => 'Via Roma', 'sede_civico' => '1', 'sede_comune' => 'Como',
                'sede_provincia' => 'CO', 'sede_regione' => 'LOMBARDIA', 'sede_cap' => '22100',
                'data_iscrizione' => '24/02/2023', 'sezione_registro' => 'APS',
                'settori_attivita' => null, 'rappresentante_legale' => 'Mario Rossi',
                'sito_web' => 'https://caicomo.it', 'pec' => 'como@pec.cai.it',
                'url_dettaglio' => 'https://servizi.lavoro.gov.it/detail/12345',
            ],
            'board_members' => [
                ['ruolo' => 'presidente', 'nome' => 'Mario', 'cognome' => 'Rossi', 'codice_fiscale' => 'RSSMRA80A01H501X', 'valid_from' => '24/02/2023', 'valid_to' => null],
            ],
            'documents' => [],
        ], $overrides)),
    ]);
}

test('run returns a not-found result and writes nothing when the scraper reports found: false', function (): void {
    $section = caiSection(['tax_code' => '01234567890']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(['found' => false]),
    ]);

    $result = app(SyncCaiRuntsRegistration::class)->run($section);

    expect($result->found)->toBeFalse();
    expect($result->registration)->toBeNull();
    expect(CaiRuntsRegistration::query()->count())->toBe(0);
});

test('run returns a not-found result without calling the scraper when the section has no tax_code', function (): void {
    $section = caiSection(['tax_code' => null]);

    Http::fake();

    $result = app(SyncCaiRuntsRegistration::class)->run($section);

    expect($result->found)->toBeFalse();
    Http::assertNothingSent();
});

test('run creates a new CaiRuntsRegistration and its board members, and bumps runts_last_synced_at', function (): void {
    $section = caiSection(['tax_code' => '01234567890']);
    fakeRuntsEntityFound('01234567890');

    $result = app(SyncCaiRuntsRegistration::class)->run($section);

    expect($result->found)->toBeTrue();

    $registration = CaiRuntsRegistration::query()->findOrFail('12345');
    expect($registration->name)->toBe('Sezione di Como');
    expect($registration->cai_section_id)->toBe($section->codice_cai);
    expect($registration->runts_last_synced_at)->not->toBeNull();

    $boardMember = CaiBoardMember::query()->where('cai_runts_registration_id', '12345')->sole();
    expect($boardMember->full_name)->toBe('Mario Rossi');
});

test('run updates an existing CaiRuntsRegistration and always bumps runts_last_synced_at', function (): void {
    $section = caiSection(['tax_code' => '01234567890']);
    $existing = caiRuntsRegistration(['id_runts' => '12345', 'cai_section_id' => $section->codice_cai, 'name' => 'Vecchio nome', 'runts_last_synced_at' => null]);
    fakeRuntsEntityFound('01234567890');

    app(SyncCaiRuntsRegistration::class)->run($section);

    $registration = $existing->fresh();
    expect($registration->name)->toBe('Sezione di Como');
    expect($registration->runts_last_synced_at)->not->toBeNull();
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php`
Expected: FAIL — classes not found.

- [ ] **Step 4: Create `SyncCaiRuntsRegistrationResult`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;

/**
 * Esito di {@see \App\Domain\CaiDirectory\Actions\SyncCaiRuntsRegistration} (Fase 9, storia 3): un semplice
 * DTO readonly, stesso ruolo di `CaiImportTableResult` (Storia 1/US-802) ma per un'Action che sincronizza
 * un'unica entità invece di un batch — `queuedAnalysisCount` è il numero di documenti di bilancio nuovi per
 * cui è stata accodata l'analisi asincrona (Task 7), usato dalla notifica del bottone dashboard.
 */
final readonly class SyncCaiRuntsRegistrationResult
{
    private function __construct(
        public bool $found,
        public ?CaiRuntsRegistration $registration,
        public int $queuedAnalysisCount,
    ) {}

    public static function notFound(): self
    {
        return new self(found: false, registration: null, queuedAnalysisCount: 0);
    }

    public static function synced(CaiRuntsRegistration $registration, int $queuedAnalysisCount): self
    {
        return new self(found: true, registration: $registration, queuedAnalysisCount: $queuedAnalysisCount);
    }
}
```

- [ ] **Step 5: Create `SyncCaiRuntsRegistration` (steps 1-3 only, documents deferred to Task 7)**

```php
<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Actions;

use App\Domain\CaiDirectory\Import\CaiRuntsRegistrationFieldMapper;
use App\Domain\CaiDirectory\Import\Concerns\DiffsAttributes;
use App\Domain\CaiDirectory\Models\CaiBoardMember;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Support\CaiRuntsScraperClient;
use App\Domain\CaiDirectory\Support\SyncCaiRuntsRegistrationResult;
use Illuminate\Support\Carbon;

/**
 * Sincronizza dal vivo la registrazione RUNTS (+ cariche sociali + documenti, Task 7) di UNA `CaiSection`
 * (Fase 9, storia 3, design doc §4.3): entry point del bottone "Sincronizza dati RUNTS" della dashboard
 * cliente ({@see \App\Filament\Pages\CustomerDashboard::syncRuntsDataAction()}, wiring in Task 8).
 */
final class SyncCaiRuntsRegistration
{
    use DiffsAttributes;

    public function __construct(private readonly CaiRuntsScraperClient $client) {}

    public function run(CaiSection $section): SyncCaiRuntsRegistrationResult
    {
        if ($section->tax_code === null || trim($section->tax_code) === '') {
            return SyncCaiRuntsRegistrationResult::notFound();
        }

        $response = $this->client->scrapeEntity($section->tax_code);

        if (($response['found'] ?? false) !== true) {
            return SyncCaiRuntsRegistrationResult::notFound();
        }

        $registration = $this->syncRegistration($section->codice_cai, (object) $response['entity']);
        $this->syncBoardMembers($registration->id_runts, $response['board_members'] ?? []);

        return SyncCaiRuntsRegistrationResult::synced($registration, queuedAnalysisCount: 0);
    }

    private function syncRegistration(string $sectionCode, object $row): CaiRuntsRegistration
    {
        $attributes = CaiRuntsRegistrationFieldMapper::mapRegistration($row, $sectionCode);
        $idRunts = (string) $row->id_runts;
        $registration = CaiRuntsRegistration::find($idRunts);

        if ($registration === null) {
            $registration = CaiRuntsRegistration::create(['id_runts' => $idRunts, ...$attributes]);
        } else {
            $registration->fill($attributes);
        }

        $registration->runts_last_synced_at = Carbon::now();
        $registration->save();

        return $registration;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncBoardMembers(string $idRunts, array $rows): void
    {
        foreach ($rows as $raw) {
            $attributes = CaiRuntsRegistrationFieldMapper::mapBoardMember((object) $raw, $idRunts);

            $existing = CaiBoardMember::query()
                ->where('cai_runts_registration_id', $attributes['cai_runts_registration_id'])
                ->where('role', $attributes['role'])
                ->when(
                    $attributes['tax_code'] === null,
                    fn ($query) => $query->whereNull('tax_code'),
                    fn ($query) => $query->where('tax_code', $attributes['tax_code']),
                )
                ->when(
                    $attributes['valid_from'] === null,
                    fn ($query) => $query->whereNull('valid_from'),
                    fn ($query) => $query->where('valid_from', $attributes['valid_from']),
                )
                ->first();

            if ($existing === null) {
                CaiBoardMember::create($attributes);
            } elseif ($this->attributesDiffer($existing, $attributes)) {
                $existing->update($attributes);
            }
        }
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php`
Expected: PASS (4 tests).

- [ ] **Step 7: Commit**

```bash
git add app/Domain/CaiDirectory/Support/SyncCaiRuntsRegistrationResult.php app/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistration.php tests/Pest.php tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php
git commit -m "feat: Fase 9 storia 3.5 - SyncCaiRuntsRegistration, sync live metadati+cariche sociali"
```

---

### Task 6: `AnalyzeCaiFinancialStatementDocument` job + coda Horizon dedicata

**Files:**
- Create: `app/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocument.php`
- Modify: `config/horizon.php`
- Modify: `tests/Pest.php` (add `caiDocument()` fixture helper)
- Test: `tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php`

**Interfaces:**
- Consumes: `CaiRuntsScraperClient::analyzeBilancio()` (Task 4), `CaiFinancialStatementFieldMapper` (Task 3).
- Produces: `AnalyzeCaiFinancialStatementDocument implements ShouldQueue`, constructor
  `(int $caiDocumentId)` — dispatched by `SyncCaiRuntsRegistration` in Task 7.

- [ ] **Step 1: Add the `caiDocument()` test fixture helper**

Append to `tests/Pest.php`:

```php

/**
 * Crea un `CaiDocument` di test collegato a una `CaiRuntsRegistration` fresca se `cai_runts_registration_id`
 * non è passato esplicitamente. Il file su `Storage::disk('cai-documents')` NON viene scritto qui: i test
 * che ne hanno bisogno lo fanno esplicitamente con `Storage::fake('cai-documents')` + `Storage::disk(...)->put(...)`.
 *
 * @param  array<string, mixed>  $attributes
 */
function caiDocument(array $attributes = []): CaiDocument
{
    static $sequence = 0;
    $sequence++;

    if (! array_key_exists('cai_runts_registration_id', $attributes)) {
        $attributes['cai_runts_registration_id'] = caiRuntsRegistration()->id_runts;
    }

    return CaiDocument::create(array_merge([
        'document_type' => 'bilancio_esercizio',
        'year' => 2024,
        'title' => 'Bilancio di esercizio 2024',
        'file_path' => 'test/bilancio-'.$sequence.'.pdf',
        'file_name' => 'bilancio-'.$sequence.'.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'hash' => 'hash-'.$sequence,
    ], $attributes))->fresh();
}
```

Add `use App\Domain\CaiDirectory\Models\CaiDocument;` to the imports.

- [ ] **Step 2: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('handle downloads the stored PDF, analyzes it, and creates a CaiFinancialStatement', function (): void {
    Storage::fake('cai-documents');
    Storage::disk('cai-documents')->put('12345/bilancio-2024.pdf', '%PDF-1.4 fixture');

    $document = caiDocument(['file_path' => '12345/bilancio-2024.pdf', 'year' => 2024]);

    Http::fake([
        'http://cai-runts-scraper:8000/analyze/bilancio' => Http::response([
            'oneri_a_interesse_generale' => 1000.0, 'oneri_b_attivita_diverse' => null,
            'oneri_c_raccolta_fondi' => null, 'oneri_d_finanziarie_patrimoniali' => null,
            'oneri_e_supporto_generale' => 200.0, 'totale_oneri' => 1200.0,
            'proventi_a_interesse_generale' => 1500.0, 'proventi_b_attivita_diverse' => null,
            'proventi_c_raccolta_fondi' => null, 'proventi_d_finanziarie_patrimoniali' => null,
            'proventi_e_supporto_generale' => null, 'totale_proventi' => 1500.0,
            'risultato_ante_imposte' => 300.0, 'imposte' => 50.0, 'risultato_esercizio' => 250.0,
            'raw_text' => '...', 'ocr' => false,
        ]),
    ]);

    (new AnalyzeCaiFinancialStatementDocument($document->id))->handle(app(\App\Domain\CaiDirectory\Support\CaiRuntsScraperClient::class));

    $statement = CaiFinancialStatement::query()
        ->where('cai_runts_registration_id', $document->cai_runts_registration_id)
        ->where('year', 2024)
        ->sole();

    expect($statement->total_expenses)->toEqual(1200.0);
    expect($statement->net_result)->toEqual(250.0);

    Http::assertSent(fn ($request): bool => $request->hasFile('file'));
});

test('handle updates an existing CaiFinancialStatement for the same registration+year', function (): void {
    Storage::fake('cai-documents');
    Storage::disk('cai-documents')->put('12345/bilancio-2024.pdf', '%PDF-1.4 fixture');

    $document = caiDocument(['file_path' => '12345/bilancio-2024.pdf', 'year' => 2024]);
    CaiFinancialStatement::create([
        'cai_runts_registration_id' => $document->cai_runts_registration_id,
        'year' => 2024,
        'total_expenses' => 1.0,
    ]);

    Http::fake([
        'http://cai-runts-scraper:8000/analyze/bilancio' => Http::response([
            'oneri_a_interesse_generale' => null, 'oneri_b_attivita_diverse' => null,
            'oneri_c_raccolta_fondi' => null, 'oneri_d_finanziarie_patrimoniali' => null,
            'oneri_e_supporto_generale' => null, 'totale_oneri' => 999.0,
            'proventi_a_interesse_generale' => null, 'proventi_b_attivita_diverse' => null,
            'proventi_c_raccolta_fondi' => null, 'proventi_d_finanziarie_patrimoniali' => null,
            'proventi_e_supporto_generale' => null, 'totale_proventi' => null,
            'risultato_ante_imposte' => null, 'imposte' => null, 'risultato_esercizio' => null,
        ]),
    ]);

    (new AnalyzeCaiFinancialStatementDocument($document->id))->handle(app(\App\Domain\CaiDirectory\Support\CaiRuntsScraperClient::class));

    expect(CaiFinancialStatement::query()->where('cai_runts_registration_id', $document->cai_runts_registration_id)->where('year', 2024)->count())->toBe(1);
});

test('handle does nothing when the CaiDocument no longer exists', function (): void {
    Http::fake();

    (new AnalyzeCaiFinancialStatementDocument(999999))->handle(app(\App\Domain\CaiDirectory\Support\CaiRuntsScraperClient::class));

    Http::assertNothingSent();
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php`
Expected: FAIL — class not found.

- [ ] **Step 4: Create the job**

```php
<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Jobs;

use App\Domain\CaiDirectory\Import\CaiFinancialStatementFieldMapper;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Support\CaiRuntsScraperClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Estrae le cifre finanziarie da un `CaiDocument` di bilancio già scaricato/allegato (Fase 9, storia 3,
 * design doc §4.4) chiamando il servizio Python `cai-runts-scraper`. Prende un id (mai il modello
 * serializzato) e ri-legge tutto a runtime, stesso pattern già in uso da
 * `App\Domain\Documentation\Jobs\GenerateDocumentationPagePdfJob`. `->onQueue('cai-runts-analysis')` è
 * applicato al sito di dispatch (Task 7), non su questa classe.
 */
final class AnalyzeCaiFinancialStatementDocument implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $caiDocumentId) {}

    public function handle(CaiRuntsScraperClient $client): void
    {
        $document = CaiDocument::query()->find($this->caiDocumentId);

        if ($document === null) {
            return;
        }

        $pdfContent = Storage::disk('cai-documents')->get((string) $document->file_path);

        if ($pdfContent === null) {
            return;
        }

        $result = $client->analyzeBilancio($pdfContent);
        $attributes = CaiFinancialStatementFieldMapper::mapFinancialStatement((object) $result);

        $existing = CaiFinancialStatement::query()
            ->where('cai_runts_registration_id', $document->cai_runts_registration_id)
            ->where('year', $document->year)
            ->first();

        if ($existing === null) {
            CaiFinancialStatement::create([
                'cai_runts_registration_id' => $document->cai_runts_registration_id,
                'year' => $document->year,
                ...$attributes,
            ]);

            return;
        }

        $existing->update($attributes);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php`
Expected: PASS (3 tests).

- [ ] **Step 6: Add the dedicated Horizon supervisor**

In `config/horizon.php`, add a new key to the `'defaults'` array, after `'supervisor-1'`:

```php
    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 1,
            'timeout' => 60,
            'nice' => 0,
        ],

        // Fase 9, storia 3: prima coda dedicata del repo (mai 'default'), per non far competere
        // l'estrazione PDF di bilancio con la posta/generazione PDF dei report attività. Timeout più ampio
        // del default: l'estrazione testo/regex su un PDF multi-pagina è più lenta di un job tipico.
        'cai-runts-analysis' => [
            'connection' => 'redis',
            'queue' => ['cai-runts-analysis'],
            'balance' => 'simple',
            'maxProcesses' => 2,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 120,
            'nice' => 0,
        ],
    ],
```

- [ ] **Step 7: Verify Horizon boots with the new supervisor, in the dev container**

Run:
```bash
docker compose exec -T app php artisan horizon:list 2>&1 | head -20
```
Expected: no fatal config error. If `queue` (Horizon) is already running via `docker compose ps`, restart it
to pick up the config change: `docker compose restart queue`, then `docker compose logs --tail=30 queue`
should show both `supervisor-1` and `cai-runts-analysis` starting without error.

- [ ] **Step 8: Commit**

```bash
git add app/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocument.php config/horizon.php tests/Pest.php tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php
git commit -m "feat: Fase 9 storia 3.6 - AnalyzeCaiFinancialStatementDocument + coda Horizon dedicata"
```

---

### Task 7: Documenti di bilancio — sync sincrono + dispatch dell'analisi

**Files:**
- Modify: `app/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistration.php`
- Modify: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php`

**Interfaces:**
- Consumes: `AnalyzeCaiFinancialStatementDocument` (Task 6).
- Produces: `SyncCaiRuntsRegistration::run()` now also syncs `CaiDocument` rows and dispatches analysis jobs
  for new `bilancio_esercizio` documents — completes design doc §4.3 steps 4-5.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php`:

```php
test('run downloads and stores a new document, and dispatches analysis only for bilancio_esercizio', function (): void {
    Illuminate\Support\Facades\Storage::fake('cai-documents');
    Illuminate\Support\Facades\Queue::fake();

    $section = caiSection(['tax_code' => '01234567890']);
    fakeRuntsEntityFound('01234567890', [
        'documents' => [
            [
                'documento' => 'Bilancio di esercizio 2024', 'codice_pratica' => 'B00',
                'tipo' => 'bilancio_esercizio', 'anno' => 2024, 'filename' => 'B00_2024.pdf',
                'mime' => 'application/pdf', 'size' => 33, 'hash_sha256' => 'abc123',
                'skip_reason' => null, 'content_base64' => base64_encode('%PDF-1.4 fixture bilancio'),
            ],
            [
                'documento' => 'Statuto', 'codice_pratica' => 'C02', 'tipo' => 'statuto',
                'anno' => null, 'filename' => 'C02_statuto.pdf', 'mime' => 'application/pdf',
                'size' => 10, 'hash_sha256' => 'def456', 'skip_reason' => null,
                'content_base64' => base64_encode('%PDF-1.4 statuto'),
            ],
            [
                'documento' => 'Non scaricato', 'codice_pratica' => 'D00', 'tipo' => 'altro',
                'anno' => null, 'filename' => null, 'mime' => null, 'size' => null,
                'hash_sha256' => null, 'skip_reason' => 'no_button', 'content_base64' => null,
            ],
        ],
    ]);

    $result = app(App\Domain\CaiDirectory\Actions\SyncCaiRuntsRegistration::class)->run($section);

    expect(App\Domain\CaiDirectory\Models\CaiDocument::query()->count())->toBe(2);
    expect(Illuminate\Support\Facades\Storage::disk('cai-documents')->get('12345/B00_2024.pdf'))->toBe('%PDF-1.4 fixture bilancio');

    $bilancio = App\Domain\CaiDirectory\Models\CaiDocument::query()->where('file_name', 'B00_2024.pdf')->sole();
    expect($bilancio->title)->toBe('Bilancio di esercizio 2024');
    expect($bilancio->year)->toBe(2024);

    expect($result->queuedAnalysisCount)->toBe(1);
    Illuminate\Support\Facades\Queue::assertPushed(
        App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument::class,
        fn ($job): bool => $job->caiDocumentId === $bilancio->id,
    );
});

test('run does not re-download or re-queue a document that already exists', function (): void {
    Illuminate\Support\Facades\Storage::fake('cai-documents');
    Illuminate\Support\Facades\Queue::fake();

    $section = caiSection(['tax_code' => '01234567890']);
    $registration = caiRuntsRegistration(['id_runts' => '12345', 'cai_section_id' => $section->codice_cai]);
    caiDocument(['cai_runts_registration_id' => $registration->id_runts, 'file_name' => 'B00_2024.pdf', 'document_type' => 'bilancio_esercizio']);

    fakeRuntsEntityFound('01234567890', [
        'documents' => [
            [
                'documento' => 'Bilancio di esercizio 2024', 'codice_pratica' => 'B00',
                'tipo' => 'bilancio_esercizio', 'anno' => 2024, 'filename' => 'B00_2024.pdf',
                'mime' => 'application/pdf', 'size' => 33, 'hash_sha256' => 'abc123',
                'skip_reason' => null, 'content_base64' => base64_encode('%PDF-1.4 fixture bilancio'),
            ],
        ],
    ]);

    app(App\Domain\CaiDirectory\Actions\SyncCaiRuntsRegistration::class)->run($section);

    expect(App\Domain\CaiDirectory\Models\CaiDocument::query()->count())->toBe(1);
    Illuminate\Support\Facades\Queue::assertNothingPushed();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php --filter="downloads and stores|does not re-download"`
Expected: FAIL — `queuedAnalysisCount` always 0, no `CaiDocument` rows created (step 4/5 not implemented yet).

- [ ] **Step 3: Implement `syncDocuments()` and wire it into `run()`**

In `app/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistration.php`, add imports:

```php
use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use App\Domain\CaiDirectory\Models\CaiDocument;
use Illuminate\Support\Facades\Storage;
```

Add the class constant and change `run()`'s return statement:

```php
    private const DOCUMENTS_DISK = 'cai-documents';

    private const BILANCIO_DOCUMENT_TYPES = ['bilancio_esercizio'];

    public function run(CaiSection $section): SyncCaiRuntsRegistrationResult
    {
        if ($section->tax_code === null || trim($section->tax_code) === '') {
            return SyncCaiRuntsRegistrationResult::notFound();
        }

        $response = $this->client->scrapeEntity($section->tax_code);

        if (($response['found'] ?? false) !== true) {
            return SyncCaiRuntsRegistrationResult::notFound();
        }

        $registration = $this->syncRegistration($section->codice_cai, (object) $response['entity']);
        $this->syncBoardMembers($registration->id_runts, $response['board_members'] ?? []);
        $newBilancioDocuments = $this->syncDocuments($registration->id_runts, $response['documents'] ?? []);

        foreach ($newBilancioDocuments as $document) {
            AnalyzeCaiFinancialStatementDocument::dispatch($document->id)->onQueue('cai-runts-analysis');
        }

        return SyncCaiRuntsRegistrationResult::synced($registration, queuedAnalysisCount: count($newBilancioDocuments));
    }
```

Add the new private method after `syncBoardMembers()`:

```php
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<CaiDocument> i documenti di bilancio NUOVI (mai quelli già presenti né gli altri tipi)
     */
    private function syncDocuments(string $idRunts, array $rows): array
    {
        $newBilancioDocuments = [];

        foreach ($rows as $raw) {
            if (($raw['skip_reason'] ?? null) !== null) {
                continue;
            }

            $contentBase64 = $raw['content_base64'] ?? null;
            $fileName = $raw['filename'] ?? null;

            if ($contentBase64 === null || $fileName === null) {
                continue;
            }

            $existing = CaiDocument::query()
                ->where('cai_runts_registration_id', $idRunts)
                ->where('file_name', $fileName)
                ->first();

            if ($existing !== null) {
                continue;
            }

            $decoded = base64_decode((string) $contentBase64, true);

            if ($decoded === false) {
                continue;
            }

            $destinationPath = "{$idRunts}/{$fileName}";
            Storage::disk(self::DOCUMENTS_DISK)->put($destinationPath, $decoded);

            $document = CaiDocument::create([
                'cai_runts_registration_id' => $idRunts,
                'document_type' => $raw['tipo'] ?? null,
                'year' => $raw['anno'] ?? null,
                'title' => $raw['documento'] ?? null,
                'file_path' => $destinationPath,
                'file_name' => $fileName,
                'mime_type' => $raw['mime'] ?? null,
                'size' => $raw['size'] ?? null,
                'hash' => $raw['hash_sha256'] ?? null,
            ]);

            if (in_array($raw['tipo'] ?? null, self::BILANCIO_DOCUMENT_TYPES, true)) {
                $newBilancioDocuments[] = $document;
            }
        }

        return $newBilancioDocuments;
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php`
Expected: PASS (all 6 tests in the file).

- [ ] **Step 5: Commit**

```bash
git add app/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistration.php tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php
git commit -m "feat: Fase 9 storia 3.7 - SyncCaiRuntsRegistration, download documenti + dispatch analisi"
```

---

### Task 8: Wiring del bottone "Sincronizza dati RUNTS"

**Files:**
- Modify: `app/Filament/Pages/CustomerDashboard.php`
- Modify: `tests/Feature/Filament/Pages/CustomerDashboardTest.php`

**Interfaces:**
- Consumes: `SyncCaiRuntsRegistration::run(CaiSection $section): SyncCaiRuntsRegistrationResult` (Task 7).
- Produces: no change to the button's public name (`sync_runts_data`), label, icon, color, or `->visible()`
  condition — only its `->action()` closure changes, matching Story 1's pattern for the CAI button.

- [ ] **Step 1: Update the existing dashboard test that covers this button**

Find the existing test in `tests/Feature/Filament/Pages/CustomerDashboardTest.php` that exercises
`sync_runts_data` (currently asserting datapack-importer-driven behavior). Replace it with:

```php
test('the sync runts data action live-scrapes the current customer\'s section via the cai-runts-scraper service', function (): void {
    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response([
            'found' => true,
            'entity' => [
                'id_runts' => '12345', 'codice_fiscale' => '01234567890',
                'denominazione' => 'Sezione di Como (RUNTS)', 'forma_giuridica' => null,
                'natura_giuridica' => null, 'sede_indirizzo' => null, 'sede_civico' => null,
                'sede_comune' => null, 'sede_provincia' => null, 'sede_regione' => null,
                'sede_cap' => null, 'data_iscrizione' => null, 'sezione_registro' => null,
                'settori_attivita' => null, 'rappresentante_legale' => null, 'sito_web' => null,
                'pec' => null, 'url_dettaglio' => null,
            ],
            'board_members' => [],
            'documents' => [],
        ]),
    ]);

    $user = User::factory()->create(['customer_type' => CustomerType::Sezione]);
    grantCustomerRole($user);

    caiSection(['codice_cai' => '9216049', 'tax_code' => '01234567890', 'user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(CustomerDashboard::class)
        ->callAction('sync_runts_data')
        ->assertNotified();

    $registration = App\Domain\CaiDirectory\Models\CaiRuntsRegistration::query()->findOrFail('12345');
    expect($registration->name)->toBe('Sezione di Como (RUNTS)');
    expect($registration->runts_last_synced_at)->not->toBeNull();
});

test('the sync runts data action shows an informative notification when no RUNTS registration is found', function (): void {
    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(['found' => false]),
    ]);

    $user = User::factory()->create(['customer_type' => CustomerType::Sezione]);
    grantCustomerRole($user);

    caiSection(['codice_cai' => '9216049', 'tax_code' => '01234567890', 'user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(CustomerDashboard::class)
        ->callAction('sync_runts_data')
        ->assertNotified();

    expect(App\Domain\CaiDirectory\Models\CaiRuntsRegistration::query()->count())->toBe(0);
});
```

If the file uses a different helper name than `grantCustomerRole($user)` for a Sezione customer's panel
access (check other tests in the same file for the exact pattern already used, e.g. by Story 1's
`sync_cai_data` test), use that existing helper instead of inventing a new one.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Filament/Pages/CustomerDashboardTest.php --filter="sync runts data action live-scrapes|no RUNTS registration is found"`
Expected: FAIL — the button still calls `CaiDatapackImporter`, so the `Http::fake()` for the new URL is never
hit.

- [ ] **Step 3: Update `CustomerDashboard::syncRuntsDataAction()`**

In `app/Filament/Pages/CustomerDashboard.php`, add the import (alphabetically among the existing
`App\Domain\CaiDirectory\...` imports, right after `use App\Domain\CaiDirectory\Actions\ScrapeCaiSection;` if
Story 1 already added that one):

```php
use App\Domain\CaiDirectory\Actions\SyncCaiRuntsRegistration;
```

Keep the method signature, `->label(...)`, `->icon(...)`, `->color('gray')` and `->visible(...)` lines
exactly as they are. Replace only the `->action(function (): void { ... })` body:

```php
    public function syncRuntsDataAction(): Action
    {
        return Action::make('sync_runts_data')
            ->label('Sincronizza dati RUNTS')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->visible(fn (): bool => $this->isSezione() && $this->caiSection() !== null)
            ->action(function (): void {
                $section = $this->caiSection();

                if ($section === null) {
                    return;
                }

                try {
                    $result = app(SyncCaiRuntsRegistration::class)->run($section);
                } catch (\Throwable $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Sincronizzazione RUNTS non riuscita')
                        ->body($exception->getMessage())
                        ->send();

                    return;
                }

                if (! $result->found) {
                    Notification::make()
                        ->warning()
                        ->title('Nessuna registrazione RUNTS trovata')
                        ->body('Non è stata trovata alcuna registrazione RUNTS per il codice fiscale della tua sezione.')
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Sincronizzazione RUNTS completata')
                    ->body($result->queuedAnalysisCount > 0
                        ? "Dati aggiornati. L'analisi di {$result->queuedAnalysisCount} bilancio/i è stata avviata in background."
                        : 'Dati RUNTS aggiornati.')
                    ->send();
            });
    }
```

Update the docblock above the method to describe the live-scrape behavior (mirroring the docblock update
already applied to `syncCaiDataAction()` in Story 1).

**Remove now-dead code**: after this change, `resolveDatapackAbsolutePathOrNotify()` and the
`use App\Domain\CaiDirectory\Import\CaiDatapackImporter;` import are no longer referenced by anything in this
file (Story 1 already removed the CAI button's dependency on them; this task removes the RUNTS button's).
Verify with `grep -n "resolveDatapackAbsolutePathOrNotify\|CaiDatapackImporter" app/Filament/Pages/CustomerDashboard.php`
— if the only remaining hits are the method's own definition and the now-unused `use` line, delete both the
private method and the `use` statement.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/Filament/Pages/CustomerDashboardTest.php`
Expected: PASS (entire file, including the untouched `sync_cai_data` tests from Story 1).

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Pages/CustomerDashboard.php tests/Feature/Filament/Pages/CustomerDashboardTest.php
git commit -m "feat: Fase 9 storia 3.8 - il bottone Sincronizza dati RUNTS chiama il servizio live"
```

---

### Task 9: `runts_last_synced_at` nell'Infolist condiviso

**Files:**
- Modify: `app/Filament/Resources/CaiSections/Schemas/CaiSectionInfolist.php`
- Test: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`

**Interfaces:**
- Consumes: `CaiRuntsRegistration::$runts_last_synced_at` (Task 1).
- Produces: no change to `configure(Schema $schema): Schema`'s public shape — same shared-component
  guarantee already established for `cai_last_synced_at` in Story 1 (staff Resource, dashboard cliente
  Sezione, dettaglio Gruppo Regionale all pick this up automatically).

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`:

```php
test('the section detail page shows the last RUNTS live-sync timestamp, or a "never synced" placeholder', function (): void {
    grantCaiDirectoryPanelAccess($user = User::factory()->create());

    $section = caiSection();
    $synced = caiRuntsRegistration(['cai_section_id' => $section->codice_cai, 'runts_last_synced_at' => now()]);
    caiRuntsRegistration(['cai_section_id' => $section->codice_cai, 'runts_last_synced_at' => null]);

    Livewire::actingAs($user)
        ->test(ViewCaiSection::class, ['record' => $section->getRouteKey()])
        ->assertSeeText(now()->format('d/m/Y'))
        ->assertSeeText('Mai sincronizzato dal vivo');
});
```

Match whatever authentication pattern (`Livewire::actingAs($user)` vs. a different helper) the rest of the
file already uses for `ViewCaiSection` tests, per the same note as Story 1's equivalent task.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php --filter="RUNTS live-sync timestamp"`
Expected: FAIL — neither string appears in the rendered `runtsSection()` schema yet.

- [ ] **Step 3: Add the entry to `runtsSection()`**

In `app/Filament/Resources/CaiSections/Schemas/CaiSectionInfolist.php`, in `runtsSection()`, add a new
`TextEntry` inside the `RepeatableEntry::make('runtsRegistrations')`'s schema array (right after
`TextEntry::make('official_page_url')...->placeholder('—'),`), and bump `->columns(3)` to `->columns(4)`:

```php
                    TextEntry::make('official_page_url')
                        ->label('Scheda ufficiale RUNTS')
                        ->url(fn (CaiRuntsRegistration $record): ?string => $record->official_page_url)
                        ->openUrlInNewTab()
                        ->placeholder('—'),
                    TextEntry::make('runts_last_synced_at')
                        ->label('Ultimo aggiornamento dal vivo')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('Mai sincronizzato dal vivo'),
                ])
                ->columns(4)
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`
Expected: PASS (entire file).

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/CaiSections/Schemas/CaiSectionInfolist.php tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php
git commit -m "feat: Fase 9 storia 3.9 - mostra runts_last_synced_at nell'Infolist condiviso"
```

---

### Task 10: Regressione end-to-end

**Files:**
- None created — runs the full regression surface touched by this plan.

**Interfaces:**
- Consumes: everything from Tasks 1-9.
- Produces: confidence that the `CaiDatapackImporter` refactors (Tasks 2-3) and the new Horizon supervisor
  (Task 6) did not regress Fase 8/Story 1 behavior.

- [ ] **Step 1: Run every test file touched or depended on by this plan together**

Run:
```bash
vendor/bin/pest \
  tests/Feature/Database/CaiRuntsRegistrationLastSyncedAtTest.php \
  tests/Unit/Domain/CaiDirectory/Import/CaiRuntsRegistrationFieldMapperTest.php \
  tests/Unit/Domain/CaiDirectory/Import/CaiFinancialStatementFieldMapperTest.php \
  tests/Feature/Domain/CaiDirectory/Support/CaiRuntsScraperClientTest.php \
  tests/Feature/Domain/CaiDirectory/Actions/SyncCaiRuntsRegistrationTest.php \
  tests/Feature/Domain/CaiDirectory/Jobs/AnalyzeCaiFinancialStatementDocumentTest.php \
  tests/Feature/Filament/Pages/CustomerDashboardTest.php \
  tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php \
  tests/Feature/Console/CaiImportDatapackCommandTest.php
```
Expected: all PASS. The last file is the pre-existing datapack-import regression suite (refactored twice by
this plan, Tasks 2-3) — it must show identical created/updated/skipped counts to before this plan started.

- [ ] **Step 2: Run static analysis and linting**

Run: `composer run analyse` then `composer run lint`
Expected: no new errors from any file created/modified by this plan.

- [ ] **Step 3: Manual end-to-end smoke test against the real `cai-runts-scraper` service**

Requires the service from `docs/superpowers/plans/2026-09-08-cai-runts-scraper-service.md` running
(`docker compose up -d cai-runts-scraper`). Click "Sincronizza dati RUNTS" in a real browser session for a
`CaiSection` with a known real `tax_code`, and confirm: (a) the button completes within the expected ~1
minute window without timing out, (b) `cai_runts_registrations`/`cai_board_members`/`cai_documents` rows
appear, (c) `docker compose logs queue` shows `AnalyzeCaiFinancialStatementDocument` picked up on the
`cai-runts-analysis` queue shortly after, (d) `cai_financial_statements` gets populated once that job
finishes. Record the outcome in `progress.txt` (or this plan's execution notes) — this step cannot be
automated (real network dependency on RUNTS) and is the actual proof the two plans compose correctly
end-to-end.

- [ ] **Step 4: Fix any regression found in Steps 1-3**

Use `superpowers:systematic-debugging` for any pre-existing test that fails unexpectedly — find root cause
before patching.

- [ ] **Step 5: Commit (only if Step 4 produced changes)**

```bash
git add -A
git commit -m "fix: Fase 9 storia 3.10 - correzioni emerse dalla verifica di regressione end-to-end"
```
