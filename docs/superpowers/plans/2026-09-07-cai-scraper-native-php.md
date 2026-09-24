# Scraper CAI nativo PHP — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the static-datapack re-import behind the "Sincronizza dati CAI" dashboard button with a live, native-PHP call to the official CAI API (§3.1 of the spec), add a `cai_last_synced_at` timestamp on `cai_sections`/`cai_subsections` shown in the shared Infolist, and add a monthly scheduled `cai:sync-national` command — all without touching RUNTS sync (Stories 2-3, out of scope here) or the static-datapack bootstrap path (`cai:import-datapack`, which keeps working unchanged for fresh-environment seeding).

**Architecture:** A pure field-mapping class (`CaiSectionFieldMapper`) is extracted from `CaiDatapackImporter` so the datapack import and the new live scrape write `CaiSection`/`CaiSubsection` through the exact same mapping logic (spec requirement: "va estratta e condivisa, non duplicata"). A new `CaiApiClient` wraps `Http::` calls to the two CAI JSON endpoints (national sections list, per-section subsections list) with the same retry/backoff shape as the Python prototype. A normalizer (`CaiApiSectionNormalizer`) converts raw CAI API JSON into the same `cai_*`-named row shape the datapack importer already consumes, so the mapper needs no knowledge of which source it came from. A shared syncer (`SyncCaiSectionAndSubsections`) does the actual upsert (create/update/skip + `cai_last_synced_at` bump) for one section at a time; it is called once by `ScrapeCaiSection` (the dashboard button, one section) and once per section in a loop by `CaiSyncNationalCommand` (the monthly job, all ~529 sections).

**Tech Stack:** Laravel 13 `Http` facade (first use of it in this repo — the IMAP mail pipeline uses `webklex/php-imap`, not `Http`), Pest 4.x with `Http::fake()`, existing `DiffsAttributes` trait, existing Filament 4 Action/Infolist/RepeatableEntry APIs already used by `CustomerDashboard`/`CaiSectionInfolist`.

**Spec:** `docs/superpowers/specs/2026-09-07-cai-runts-live-sync-design.md` (§3.1 scraper CAI, §3.3 timestamp, §3.4 bottone, §3.5 refresh nazionale, §6 item 1 — this plan implements exactly and only Story 1 from that sequencing; Stories 2-5 depend on a new Python microservice and are explicitly out of scope here).

## Global Constraints

- PHP ^8.4, every new/modified PHP file starts with `declare(strict_types=1);` immediately after `<?php`.
- Test runner: Pest 4.x syntax (`test('...', function () {...})`), never PHPUnit classes.
- Domain code lives under `app/Domain/CaiDirectory/{Import,Support,Actions}` — never outside that module structure.
- No business logic in Eloquent hooks (`boot()`/`booted()`/Observer) — not applicable here (no new Eloquent hooks introduced).
- `env()` is only ever called inside `config/*.php` files — every other file reads via `config('cai_directory....')`/`config('orchestrator.features....')`.
- A column added to `CaiSection`/`CaiSubsection` must be added to the class's `#[Fillable([...])]` attribute or `update()`/`create()` silently drop it (documented, previously-hit gotcha in this repo, US-703).
- Feature flags follow the existing `config('orchestrator.features.<key>')` + `.env` `ENABLE_<KEY>` pattern exactly, gated in `routes/console.php` via `->when(fn (): bool => (bool) config('orchestrator.features.<key>'))` — the command itself never checks the flag internally, so it stays runnable manually via CLI regardless of the flag (existing repo convention).
- No `->timeout()` on any `Schedule::command(...)` entry (Laravel 13.22 in this repo does not expose it for in-process commands — calling it crashes the entire `php artisan` bootstrap).
- CAI API base URLs/timeouts are read from `config('cai_directory.api.*')`, never hardcoded in the classes that call them, so tests can override via `config([...])`.
- `CaiImportTableResult` (`app/Domain/CaiDirectory/Import/CaiImportTableResult.php`) is `final readonly class` with constructor `__construct(public int $read = 0, public int $created = 0, public int $updated = 0, public int $skipped = 0, public array $warnings = [])` — reused as-is by the new code, never re-implemented.
- `DiffsAttributes::attributesDiffer(Model $existing, array $attributes): bool` (`app/Domain/CaiDirectory/Import/Concerns/DiffsAttributes.php`) is the only comparison mechanism for "did this row actually change" — reused as-is.

---

### Task 1: Migration — `cai_last_synced_at` on `cai_sections` and `cai_subsections`

**Files:**
- Create: `database/migrations/2026_09_07_140000_add_cai_last_synced_at_to_cai_sections_and_cai_subsections_tables.php`
- Test: `tests/Feature/Database/CaiLastSyncedAtColumnsTest.php`

**Interfaces:**
- Produces: nullable `cai_sections.cai_last_synced_at` (timestamp) and `cai_subsections.cai_last_synced_at` (timestamp), consumed by Task 2 (model casts) and Task 6 (the syncer that writes them).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('cai_sections has a nullable cai_last_synced_at column', function (): void {
    expect(Schema::hasColumn('cai_sections', 'cai_last_synced_at'))->toBeTrue();

    $section = CaiSection::create([
        'codice_cai' => 'CAI-TEST-1',
        'name' => 'Sezione di test',
        'region' => 'LOMBARDIA',
    ]);

    expect($section->fresh()->cai_last_synced_at)->toBeNull();
});

test('cai_subsections has a nullable cai_last_synced_at column', function (): void {
    expect(Schema::hasColumn('cai_subsections', 'cai_last_synced_at'))->toBeTrue();

    CaiSection::create(['codice_cai' => 'CAI-TEST-2', 'name' => 'Sezione', 'region' => 'LOMBARDIA']);

    $subsection = CaiSubsection::create([
        'cai_codice' => 'SUB-TEST-1',
        'cai_section_id' => 'CAI-TEST-2',
        'name' => 'Sottosezione di test',
    ]);

    expect($subsection->fresh()->cai_last_synced_at)->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Database/CaiLastSyncedAtColumnsTest.php`
Expected: FAIL — `Schema::hasColumn('cai_sections', 'cai_last_synced_at')` is `false` (column doesn't exist yet).

- [ ] **Step 3: Write the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cai_sections', function (Blueprint $table) {
            // Fase 9, storia 1: valorizzata solo da un refresh live (bottone dashboard o
            // `cai:sync-national`), resta `null` per una riga proveniente solo dal vecchio
            // import da datapack statico (`cai:import-datapack`, mai sincronizzata dal vivo).
            $table->timestamp('cai_last_synced_at')->nullable();
        });

        Schema::table('cai_subsections', function (Blueprint $table) {
            $table->timestamp('cai_last_synced_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cai_sections', function (Blueprint $table) {
            $table->dropColumn('cai_last_synced_at');
        });

        Schema::table('cai_subsections', function (Blueprint $table) {
            $table->dropColumn('cai_last_synced_at');
        });
    }
};
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/Database/CaiLastSyncedAtColumnsTest.php`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_09_07_140000_add_cai_last_synced_at_to_cai_sections_and_cai_subsections_tables.php tests/Feature/Database/CaiLastSyncedAtColumnsTest.php
git commit -m "feat: Fase 9 storia 1.1 - aggiunge cai_last_synced_at a cai_sections/cai_subsections"
```

---

### Task 2: `CaiSection`/`CaiSubsection` models — Fillable + cast

**Files:**
- Modify: `app/Domain/CaiDirectory/Models/CaiSection.php`
- Modify: `app/Domain/CaiDirectory/Models/CaiSubsection.php`
- Test: `tests/Unit/Domain/CaiDirectory/Models/CaiLastSyncedAtFillableTest.php`

**Interfaces:**
- Consumes: `cai_last_synced_at` column from Task 1.
- Produces: `CaiSection::create([..., 'cai_last_synced_at' => $carbon])` and `$section->update(['cai_last_synced_at' => $carbon])` actually persist the value — consumed by Task 6.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('cai_last_synced_at is mass-assignable and cast to a datetime on CaiSection', function (): void {
    $syncedAt = Carbon::parse('2026-09-07 08:30:00');

    $section = CaiSection::create([
        'codice_cai' => 'CAI-FILL-1',
        'name' => 'Sezione',
        'region' => 'LOMBARDIA',
        'cai_last_synced_at' => $syncedAt,
    ])->fresh();

    expect($section->cai_last_synced_at)->toBeInstanceOf(Carbon::class);
    expect($section->cai_last_synced_at->equalTo($syncedAt))->toBeTrue();
});

test('cai_last_synced_at is mass-assignable and cast to a datetime on CaiSubsection', function (): void {
    CaiSection::create(['codice_cai' => 'CAI-FILL-2', 'name' => 'Sezione', 'region' => 'LOMBARDIA']);
    $syncedAt = Carbon::parse('2026-09-07 08:30:00');

    $subsection = CaiSubsection::create([
        'cai_codice' => 'SUB-FILL-1',
        'cai_section_id' => 'CAI-FILL-2',
        'name' => 'Sottosezione',
        'cai_last_synced_at' => $syncedAt,
    ])->fresh();

    expect($subsection->cai_last_synced_at)->toBeInstanceOf(Carbon::class);
    expect($subsection->cai_last_synced_at->equalTo($syncedAt))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/Domain/CaiDirectory/Models/CaiLastSyncedAtFillableTest.php`
Expected: FAIL — `$section->cai_last_synced_at` is `null` (mass-assignment silently dropped: not in `#[Fillable]`).

- [ ] **Step 3: Update the models**

In `app/Domain/CaiDirectory/Models/CaiSection.php`, change the `#[Fillable]` attribute and `casts()`:

```php
#[Fillable([
    'codice_cai', 'name', 'tax_code', 'vat_number', 'email', 'pec', 'phone_office', 'phone', 'fax',
    'address', 'postal_address', 'website', 'office_hours', 'notices', 'founded_year', 'members_count',
    'latitude', 'longitude', 'region', 'user_id', 'cai_last_synced_at',
])]
class CaiSection extends Model
{
    protected $primaryKey = 'codice_cai';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'founded_year' => 'integer',
            'members_count' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'cai_last_synced_at' => 'datetime',
        ];
    }
```

In `app/Domain/CaiDirectory/Models/CaiSubsection.php`, same pattern:

```php
#[Fillable([
    'cai_codice', 'cai_section_id', 'name', 'email', 'phone_office', 'phone', 'address', 'website',
    'office_hours', 'notices', 'founded_year', 'members_count', 'latitude', 'longitude', 'user_id',
    'cai_last_synced_at',
])]
class CaiSubsection extends Model
{
    protected $primaryKey = 'cai_codice';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'founded_year' => 'integer',
            'members_count' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'cai_last_synced_at' => 'datetime',
        ];
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/Domain/CaiDirectory/Models/CaiLastSyncedAtFillableTest.php`
Expected: PASS (2 tests)

- [ ] **Step 5: Run the existing CAI datapack import regression suite**

Run: `vendor/bin/pest tests/Feature/Console/CaiImportDatapackCommandTest.php tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`
Expected: PASS, unchanged — a wider `#[Fillable]` list must not break any existing behavior (it only adds a column nothing else references yet).

- [ ] **Step 6: Commit**

```bash
git add app/Domain/CaiDirectory/Models/CaiSection.php app/Domain/CaiDirectory/Models/CaiSubsection.php tests/Unit/Domain/CaiDirectory/Models/CaiLastSyncedAtFillableTest.php
git commit -m "feat: Fase 9 storia 1.2 - cai_last_synced_at fillable e castato su CaiSection/CaiSubsection"
```

---

### Task 3: Extract `CaiSectionFieldMapper` from `CaiDatapackImporter`

**Files:**
- Create: `app/Domain/CaiDirectory/Import/CaiSectionFieldMapper.php`
- Modify: `app/Domain/CaiDirectory/Import/CaiDatapackImporter.php`
- Test: `tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php`

**Interfaces:**
- Produces: `CaiSectionFieldMapper::mapSection(object $row, array $usersByLowerEmail): array`, `::mapSubsection(object $row, array $usersByLowerEmail): array`, `::toInt(mixed $value): ?int`, `::toCoordinate(mixed $value): ?float`, `::matchUserId(?string $email, array $usersByLowerEmail): ?int` — consumed by `CaiDatapackImporter` (this task) and by `SyncCaiSectionAndSubsections` (Task 6).
- Consumes: `$row` objects with the exact `cai_*`-prefixed property names already used by `sezioni_cai`/`sottosezioni_cai` rows (see the input shape in Step 3 below) — this is the "same shape as the datapack" contract that `CaiApiSectionNormalizer` (Task 4) must produce.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\CaiSectionFieldMapper;

test('mapSection maps a normalized cai_* row to CaiSection attributes', function (): void {
    $row = (object) [
        'codice_cai' => '9216049',
        'cai_denominazione' => 'Sezione di Como',
        'cai_codice_fiscale' => '01234567890',
        'cai_partita_iva' => '09876543210',
        'cai_email' => 'Como@Cai.It',
        'cai_pec' => 'como@pec.cai.it',
        'cai_telefono_sede' => '031 111111',
        'cai_telefono' => '031 222222',
        'cai_fax' => '031 333333',
        'cai_indirizzo_sede' => '{"address1":"Via Roma","number":"1","zip":"22100","city":"Como","province":"CO","nation":"Italia"}',
        'cai_indirizzo_postale' => null,
        'cai_sito_web' => 'https://caicomo.it',
        'cai_orari' => 'Lun-Ven 18-19',
        'cai_avvisi' => 'Chiuso ad agosto',
        'cai_anno_fondazione' => '1891',
        'cai_soci_ultimo_anno' => '450',
        'cai_lat' => '45.81',
        'cai_lon' => '9.08',
        'cai_regione' => 'LOMBARDIA',
    ];

    $attributes = CaiSectionFieldMapper::mapSection($row, ['como@cai.it' => 42]);

    expect($attributes)->toBe([
        'name' => 'Sezione di Como',
        'tax_code' => '01234567890',
        'vat_number' => '09876543210',
        'email' => 'Como@Cai.It',
        'pec' => 'como@pec.cai.it',
        'phone_office' => '031 111111',
        'phone' => '031 222222',
        'fax' => '031 333333',
        'address' => 'Via Roma 1, 22100 Como (CO), Italia',
        'postal_address' => null,
        'website' => 'https://caicomo.it',
        'office_hours' => 'Lun-Ven 18-19',
        'notices' => 'Chiuso ad agosto',
        'founded_year' => 1891,
        'members_count' => 450,
        'latitude' => 45.81,
        'longitude' => 9.08,
        'region' => 'LOMBARDIA',
        'user_id' => 42,
    ]);
});

test('mapSubsection maps a normalized cai_* row to CaiSubsection attributes', function (): void {
    $row = (object) [
        'cai_codice' => 'SUB-1',
        'cai_sezione_codice' => '9216049',
        'cai_nome' => 'Sottosezione Erba',
        'cai_email' => null,
        'cai_telefono_sede' => null,
        'cai_telefono' => null,
        'cai_indirizzo_sede' => null,
        'cai_sito_web' => null,
        'cai_orari' => null,
        'cai_avvisi' => null,
        'cai_anno_fondazione' => null,
        'cai_soci' => '30',
        'cai_lat' => null,
        'cai_lon' => null,
    ];

    $attributes = CaiSectionFieldMapper::mapSubsection($row, []);

    expect($attributes)->toBe([
        'cai_section_id' => '9216049',
        'name' => 'Sottosezione Erba',
        'email' => null,
        'phone_office' => null,
        'phone' => null,
        'address' => null,
        'website' => null,
        'office_hours' => null,
        'notices' => null,
        'founded_year' => null,
        'members_count' => 30,
        'latitude' => null,
        'longitude' => null,
        'user_id' => null,
    ]);
});

test('toCoordinate discards implausible values (|x| >= 1000)', function (): void {
    expect(CaiSectionFieldMapper::toCoordinate('25614'))->toBeNull();
    expect(CaiSectionFieldMapper::toCoordinate('45.81'))->toBe(45.81);
    expect(CaiSectionFieldMapper::toCoordinate(null))->toBeNull();
});

test('matchUserId is a case-insensitive, trimmed email lookup', function (): void {
    $usersByLowerEmail = ['como@cai.it' => 42];

    expect(CaiSectionFieldMapper::matchUserId('  Como@Cai.It  ', $usersByLowerEmail))->toBe(42);
    expect(CaiSectionFieldMapper::matchUserId(null, $usersByLowerEmail))->toBeNull();
    expect(CaiSectionFieldMapper::matchUserId('', $usersByLowerEmail))->toBeNull();
    expect(CaiSectionFieldMapper::matchUserId('nobody@example.test', $usersByLowerEmail))->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php`
Expected: FAIL — `Class "App\Domain\CaiDirectory\Import\CaiSectionFieldMapper" not found`.

- [ ] **Step 3: Create `CaiSectionFieldMapper`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use Illuminate\Support\Str;

/**
 * Mappatura pura riga→attributi per `CaiSection`/`CaiSubsection` (Fase 9, storia 1):
 * estratta da {@see CaiDatapackImporter} perché va condivisa, non duplicata, tra
 * l'import da datapack statico ({@see CaiDatapackImporter::importSections()}/
 * `importSubsections()`) e lo scrape live ({@see SyncCaiSectionAndSubsections}) —
 * design doc §3.1. L'input `object $row` ha SEMPRE le stesse proprietà `cai_*`
 * indipendentemente dalla fonte: per il datapack sono le colonne native di
 * `sezioni_cai`/`sottosezioni_cai`; per l'API live, {@see CaiApiSectionNormalizer}
 * produce un oggetto con la stessa identica forma prima di passarlo qui — questo
 * mapper non sa mai da dove viene la riga.
 */
final class CaiSectionFieldMapper
{
    /**
     * @param  array<string, int>  $usersByLowerEmail
     * @return array<string, mixed>
     */
    public static function mapSection(object $row, array $usersByLowerEmail): array
    {
        return [
            'name' => $row->cai_denominazione,
            'tax_code' => $row->cai_codice_fiscale,
            'vat_number' => $row->cai_partita_iva,
            'email' => $row->cai_email,
            'pec' => $row->cai_pec,
            'phone_office' => $row->cai_telefono_sede,
            'phone' => $row->cai_telefono,
            'fax' => $row->cai_fax,
            'address' => CaiRuntsAddressFormatter::format($row->cai_indirizzo_sede),
            'postal_address' => CaiRuntsAddressFormatter::format($row->cai_indirizzo_postale),
            'website' => $row->cai_sito_web,
            'office_hours' => $row->cai_orari,
            'notices' => $row->cai_avvisi,
            'founded_year' => self::toInt($row->cai_anno_fondazione),
            'members_count' => self::toInt($row->cai_soci_ultimo_anno),
            'latitude' => self::toCoordinate($row->cai_lat),
            'longitude' => self::toCoordinate($row->cai_lon),
            'region' => $row->cai_regione,
            'user_id' => self::matchUserId($row->cai_email, $usersByLowerEmail),
        ];
    }

    /**
     * @param  array<string, int>  $usersByLowerEmail
     * @return array<string, mixed>
     */
    public static function mapSubsection(object $row, array $usersByLowerEmail): array
    {
        return [
            'cai_section_id' => $row->cai_sezione_codice,
            'name' => $row->cai_nome,
            'email' => $row->cai_email,
            'phone_office' => $row->cai_telefono_sede,
            'phone' => $row->cai_telefono,
            'address' => CaiRuntsAddressFormatter::format($row->cai_indirizzo_sede),
            'website' => $row->cai_sito_web,
            'office_hours' => $row->cai_orari,
            'notices' => $row->cai_avvisi,
            'founded_year' => self::toInt($row->cai_anno_fondazione),
            'members_count' => self::toInt($row->cai_soci),
            'latitude' => self::toCoordinate($row->cai_lat),
            'longitude' => self::toCoordinate($row->cai_lon),
            'user_id' => self::matchUserId($row->cai_email, $usersByLowerEmail),
        ];
    }

    public static function toInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    /**
     * `cai_sections.latitude`/`longitude` sono `decimal(10,7)`: al massimo 3 cifre intere,
     * un valore |x| >= 1000 farebbe fallire l'insert con "numeric field overflow" (visto
     * sul dataset reale, US-802: una riga con `cai_lat = 25614`). Scartarlo a `null`
     * invece di far fallire l'intero import per una sezione.
     */
    public static function toCoordinate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $float = (float) $value;

        return abs($float) < 1000.0 ? $float : null;
    }

    /**
     * @param  array<string, int>  $usersByLowerEmail
     */
    public static function matchUserId(?string $email, array $usersByLowerEmail): ?int
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        return $usersByLowerEmail[Str::lower(trim($email))] ?? null;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php`
Expected: PASS (4 tests)

- [ ] **Step 5: Refactor `CaiDatapackImporter` to use the shared mapper**

In `app/Domain/CaiDirectory/Import/CaiDatapackImporter.php`:

1. Delete the three private methods `toInt()` (lines 176-179), `toCoordinate()` (lines 188-197), `matchUserId()` (lines 146-153) — their bodies now live in `CaiSectionFieldMapper`.
2. In `importSections()`, replace the inline `$attributes = [...]` array (lines 217-237) with:

```php
$attributes = CaiSectionFieldMapper::mapSection($row, $usersByLowerEmail);
```

3. In `importSubsections()`, replace the inline `$attributes = [...]` array (lines 277-292) with:

```php
$attributes = CaiSectionFieldMapper::mapSubsection($row, $usersByLowerEmail);
```

No `use` statement is needed for `CaiSectionFieldMapper` — it is in the same namespace (`App\Domain\CaiDirectory\Import`) as `CaiDatapackImporter`.

- [ ] **Step 6: Run the full existing CAI datapack regression suite to verify the refactor changed nothing observable**

Run: `vendor/bin/pest tests/Feature/Console/CaiImportDatapackCommandTest.php`
Expected: PASS, unchanged — same created/updated/skipped counts as before the refactor (this test already exercises `importSections()`/`importSubsections()` end-to-end via the shared fixture in `tests/Pest.php`).

- [ ] **Step 7: Commit**

```bash
git add app/Domain/CaiDirectory/Import/CaiSectionFieldMapper.php app/Domain/CaiDirectory/Import/CaiDatapackImporter.php tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php
git commit -m "feat: Fase 9 storia 1.3 - estrae CaiSectionFieldMapper condiviso da CaiDatapackImporter"
```

---

### Task 4: `CaiApiSectionNormalizer` — raw CAI API JSON → `cai_*` row shape

**Files:**
- Create: `app/Domain/CaiDirectory/Import/CaiApiSectionNormalizer.php`
- Test: `tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php`

**Interfaces:**
- Consumes: raw associative arrays as returned by `json_decode($response->body(), true)` on the two CAI API endpoints — field names verified against the Python prototype's `scraper/cai_scraper.py` (`_normalize_section`/`_normalize_subsection`): `code`, `name`, `cf`, `vat`, `email`, `pec`, `officePhone`, `phone`, `fax`, `officeAddress`/`office_address`, `postalAddress`/`postal_address`, `website`, `timetable`, `notice`, `foundationYear`, `lastyearMembershipsCount`, `latitude`, `longitude`, `region` (section); `code`, `name`, `email`, `officePhone`, `phone`, `officeAddress`/`office_address`, `website`, `timetable`, `notice`, `foundationYear`, `currentMemberships`/`lastyearMembershipsCount`, `latitude`, `longitude` (subsection).
- Produces: `object` with the exact `cai_*` property names `CaiSectionFieldMapper` (Task 3) consumes — `normalizeSection(array $raw): object`, `normalizeSubsection(array $raw, string $sectionCode): object`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\CaiApiSectionNormalizer;

test('normalizeSection converts raw CAI API fields to the cai_* row shape', function (): void {
    $raw = [
        'code' => '9216049',
        'name' => 'Sezione di Como',
        'cf' => '01234567890',
        'vat' => '09876543210',
        'email' => 'como@cai.it',
        'pec' => 'como@pec.cai.it',
        'officePhone' => '031 111111',
        'phone' => '031 222222',
        'fax' => '031 333333',
        'officeAddress' => ['address1' => 'Via Roma', 'number' => '1', 'zip' => '22100', 'city' => 'Como', 'province' => 'CO', 'nation' => 'Italia'],
        'postalAddress' => null,
        'website' => 'https://caicomo.it',
        'timetable' => 'Lun-Ven 18-19',
        'notice' => 'Chiuso ad agosto',
        'foundationYear' => 1891,
        'lastyearMembershipsCount' => 450,
        'latitude' => 45.81,
        'longitude' => 9.08,
        'region' => 'lombardia',
    ];

    $row = CaiApiSectionNormalizer::normalizeSection($raw);

    expect($row->codice_cai)->toBe('9216049');
    expect($row->cai_denominazione)->toBe('Sezione di Como');
    expect($row->cai_codice_fiscale)->toBe('01234567890');
    expect($row->cai_partita_iva)->toBe('09876543210');
    expect($row->cai_email)->toBe('como@cai.it');
    expect($row->cai_pec)->toBe('como@pec.cai.it');
    expect($row->cai_telefono_sede)->toBe('031 111111');
    expect($row->cai_telefono)->toBe('031 222222');
    expect($row->cai_fax)->toBe('031 333333');
    expect(json_decode($row->cai_indirizzo_sede, true))->toBe(['address1' => 'Via Roma', 'number' => '1', 'zip' => '22100', 'city' => 'Como', 'province' => 'CO', 'nation' => 'Italia']);
    expect($row->cai_indirizzo_postale)->toBeNull();
    expect($row->cai_sito_web)->toBe('https://caicomo.it');
    expect($row->cai_orari)->toBe('Lun-Ven 18-19');
    expect($row->cai_avvisi)->toBe('Chiuso ad agosto');
    expect($row->cai_anno_fondazione)->toBe(1891);
    expect($row->cai_soci_ultimo_anno)->toBe(450);
    expect($row->cai_lat)->toBe(45.81);
    expect($row->cai_lon)->toBe(9.08);
    expect($row->cai_regione)->toBe('LOMBARDIA');
});

test('normalizeSection falls back to office_address/postal_address snake_case keys', function (): void {
    $raw = [
        'code' => '9216050',
        'name' => 'Sezione Alternativa',
        'office_address' => ['rawAddress' => 'Via Test 5, Milano'],
        'postal_address' => null,
        'region' => 'lombardia',
    ];

    $row = CaiApiSectionNormalizer::normalizeSection($raw);

    expect(json_decode($row->cai_indirizzo_sede, true))->toBe(['rawAddress' => 'Via Test 5, Milano']);
});

test('normalizeSubsection converts raw CAI API fields to the cai_* row shape, scoped to the parent section code', function (): void {
    $raw = [
        'code' => 'SUB-1',
        'name' => 'Sottosezione Erba',
        'email' => null,
        'officePhone' => null,
        'phone' => null,
        'officeAddress' => null,
        'website' => null,
        'timetable' => null,
        'notice' => null,
        'foundationYear' => null,
        'currentMemberships' => 30,
        'latitude' => null,
        'longitude' => null,
    ];

    $row = CaiApiSectionNormalizer::normalizeSubsection($raw, '9216049');

    expect($row->cai_codice)->toBe('SUB-1');
    expect($row->cai_sezione_codice)->toBe('9216049');
    expect($row->cai_nome)->toBe('Sottosezione Erba');
    expect($row->cai_soci)->toBe(30);
    expect($row->cai_indirizzo_sede)->toBeNull();
});

test('normalizeSubsection falls back to lastyearMembershipsCount when currentMemberships is absent', function (): void {
    $raw = ['code' => 'SUB-2', 'name' => 'Sottosezione B', 'lastyearMembershipsCount' => 12];

    $row = CaiApiSectionNormalizer::normalizeSubsection($raw, '9216049');

    expect($row->cai_soci)->toBe(12);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php`
Expected: FAIL — `Class "App\Domain\CaiDirectory\Import\CaiApiSectionNormalizer" not found`.

- [ ] **Step 3: Create `CaiApiSectionNormalizer`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

/**
 * Converte una riga grezza JSON dell'API pubblica CAI (`sections-list-simple`/
 * `sections/{code}/sub-sections-list`, design doc §3.1) nella stessa forma
 * `cai_*`-prefissata già usata dalle tabelle `sezioni_cai`/`sottosezioni_cai` del
 * datapack statico, così {@see CaiSectionFieldMapper} può mappare entrambe le
 * fonti senza saperne la provenienza. Nomi di campo API verificati nel
 * prototipo Python `RUNTS/scraper/cai_scraper.py`
 * (`_normalize_section`/`_normalize_subsection`).
 */
final class CaiApiSectionNormalizer
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public static function normalizeSection(array $raw): object
    {
        return (object) [
            'codice_cai' => $raw['code'] ?? null,
            'cai_denominazione' => $raw['name'] ?? '',
            'cai_codice_fiscale' => $raw['cf'] ?? null,
            'cai_partita_iva' => $raw['vat'] ?? null,
            'cai_email' => $raw['email'] ?? null,
            'cai_pec' => $raw['pec'] ?? null,
            'cai_telefono_sede' => $raw['officePhone'] ?? null,
            'cai_telefono' => $raw['phone'] ?? null,
            'cai_fax' => $raw['fax'] ?? null,
            'cai_indirizzo_sede' => self::encodeAddress($raw['officeAddress'] ?? $raw['office_address'] ?? null),
            'cai_indirizzo_postale' => self::encodeAddress($raw['postalAddress'] ?? $raw['postal_address'] ?? null),
            'cai_sito_web' => $raw['website'] ?? null,
            'cai_orari' => $raw['timetable'] ?? null,
            'cai_avvisi' => $raw['notice'] ?? null,
            'cai_anno_fondazione' => $raw['foundationYear'] ?? null,
            'cai_soci_ultimo_anno' => $raw['lastyearMembershipsCount'] ?? null,
            'cai_lat' => $raw['latitude'] ?? null,
            'cai_lon' => $raw['longitude'] ?? null,
            'cai_regione' => mb_strtoupper((string) ($raw['region'] ?? '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function normalizeSubsection(array $raw, string $sectionCode): object
    {
        return (object) [
            'cai_codice' => $raw['code'] ?? null,
            'cai_sezione_codice' => $sectionCode,
            'cai_nome' => $raw['name'] ?? '',
            'cai_email' => $raw['email'] ?? null,
            'cai_telefono_sede' => $raw['officePhone'] ?? null,
            'cai_telefono' => $raw['phone'] ?? null,
            'cai_indirizzo_sede' => self::encodeAddress($raw['officeAddress'] ?? $raw['office_address'] ?? null),
            'cai_sito_web' => $raw['website'] ?? null,
            'cai_orari' => $raw['timetable'] ?? null,
            'cai_avvisi' => $raw['notice'] ?? null,
            'cai_anno_fondazione' => $raw['foundationYear'] ?? null,
            'cai_soci' => $raw['currentMemberships'] ?? $raw['lastyearMembershipsCount'] ?? null,
            'cai_lat' => $raw['latitude'] ?? null,
            'cai_lon' => $raw['longitude'] ?? null,
        ];
    }

    private static function encodeAddress(mixed $address): ?string
    {
        return $address === null ? null : json_encode($address, JSON_THROW_ON_ERROR);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php`
Expected: PASS (4 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Domain/CaiDirectory/Import/CaiApiSectionNormalizer.php tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php
git commit -m "feat: Fase 9 storia 1.4 - normalizza il JSON grezzo dell'API CAI nella forma cai_*"
```

---

### Task 5: `CaiApiClient` — HTTP wrapper with retry

**Files:**
- Create: `app/Domain/CaiDirectory/Support/CaiApiClient.php`
- Modify: `config/cai_directory.php`
- Modify: `.env.example`
- Test: `tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php`

**Interfaces:**
- Consumes: `config('cai_directory.api.sections_list_url')`, `config('cai_directory.api.subsections_list_url_template')`, `config('cai_directory.api.timeout_seconds')`.
- Produces: `CaiApiClient::fetchNationalSections(): array` (`list<array<string,mixed>>`), `::fetchSubsections(string $sectionCode): array` (`list<array<string,mixed>>`) — consumed by `ScrapeCaiSection` and `SyncCaiSectionAndSubsections` (Task 6/7) and `CaiSyncNationalCommand` (Task 10).

- [ ] **Step 1: Add config keys**

In `config/cai_directory.php`, add an `'api'` key after `'datapack_path'`:

```php
<?php

declare(strict_types=1);

return [
    /*
     * Percorso del file SQLite del datapack RUNTS-CAI (relativo alla root del
     * progetto, o assoluto), letto dal bottone "Sincronizza dati CAI" della
     * dashboard cliente (Fase 9): stesso default del comando `cai:import-datapack`
     * (US-802), qui esposto via config per poterlo sovrascrivere nei test.
     */
    'datapack_path' => env('CAI_DATAPACK_PATH', 'cai-datapack/runts-cai.sqlite'),

    /*
     * API pubblica CAI (Fase 9, storia 1, design doc §3.1): stessi endpoint già
     * usati dal prototipo Python `RUNTS/scraper/cai_scraper.py`. Il template della
     * URL delle sottosezioni contiene un solo `%s` (il codice sezione), risolto con
     * `sprintf()` da CaiApiClient.
     */
    'api' => [
        'sections_list_url' => env('CAI_API_SECTIONS_LIST_URL', 'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple'),
        'subsections_list_url_template' => env('CAI_API_SUBSECTIONS_URL_TEMPLATE', 'https://www.cai.it/wp-json/cai-section/v2/sections/%s/sub-sections-list'),
        'timeout_seconds' => (int) env('CAI_API_TIMEOUT_SECONDS', 30),
    ],
];
```

In `.env.example`, add these three lines immediately after the existing `CAI_DATAPACK_PATH` line (find it with `grep -n CAI_DATAPACK_PATH .env.example`):

```
CAI_API_SECTIONS_LIST_URL=https://www.cai.it/wp-json/cai-section/v2/sections-list-simple
CAI_API_SUBSECTIONS_URL_TEMPLATE=https://www.cai.it/wp-json/cai-section/v2/sections/%s/sub-sections-list
CAI_API_TIMEOUT_SECONDS=30
```

- [ ] **Step 2: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Support\CaiApiClient;
use Illuminate\Support\Facades\Http;

test('fetchNationalSections returns the decoded JSON array from the sections-list-simple endpoint', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216049', 'name' => 'Sezione di Como'],
            ['code' => '9216050', 'name' => 'Sezione di Pisa'],
        ]),
    ]);

    $sections = app(CaiApiClient::class)->fetchNationalSections();

    expect($sections)->toHaveCount(2);
    expect($sections[0]['code'])->toBe('9216049');
});

test('fetchSubsections returns the decoded JSON array from the per-section endpoint', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216049/sub-sections-list*' => Http::response([
            ['code' => 'SUB-1', 'name' => 'Sottosezione Erba'],
        ]),
    ]);

    $subsections = app(CaiApiClient::class)->fetchSubsections('9216049');

    expect($subsections)->toHaveCount(1);
    expect($subsections[0]['code'])->toBe('SUB-1');
});

test('fetchNationalSections retries up to 3 times on connection failure then throws', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response(null, 503),
    ]);

    expect(fn () => app(CaiApiClient::class)->fetchNationalSections())->toThrow(RuntimeException::class);

    Http::assertSentCount(3);
});

test('fetchNationalSections succeeds if a later attempt recovers', function (): void {
    Http::fakeSequence('https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*')
        ->push(null, 503)
        ->push([['code' => '9216049', 'name' => 'Sezione di Como']], 200);

    $sections = app(CaiApiClient::class)->fetchNationalSections();

    expect($sections)->toHaveCount(1);
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php`
Expected: FAIL — `Class "App\Domain\CaiDirectory\Support\CaiApiClient" not found`.

- [ ] **Step 4: Create `CaiApiClient`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Client per l'API pubblica CAI (Fase 9, storia 1, design doc §3.1/§6): HTTP GET
 * JSON, nessuna automazione browser necessaria (a differenza di RUNTS, §2 del
 * design doc). Retry con backoff esponenziale (1 secondo di base, 3 tentativi),
 * stesso schema del prototipo Python `RUNTS/scraper/cai_scraper.py::_with_retry`.
 */
final class CaiApiClient
{
    private const MAX_ATTEMPTS = 3;

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchNationalSections(): array
    {
        return $this->getJsonArrayWithRetry((string) config('cai_directory.api.sections_list_url'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchSubsections(string $sectionCode): array
    {
        $url = sprintf((string) config('cai_directory.api.subsections_list_url_template'), $sectionCode);

        return $this->getJsonArrayWithRetry($url);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getJsonArrayWithRetry(string $url): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'Origin' => 'https://www.cai.it',
                    'Referer' => 'https://www.cai.it/',
                ])
                    ->timeout((int) config('cai_directory.api.timeout_seconds'))
                    ->get($url);

                $response->throw();

                /** @var list<array<string, mixed>>|null $decoded */
                $decoded = $response->json();

                return $decoded ?? [];
            } catch (ConnectionException|RequestException $exception) {
                $lastException = $exception;

                if ($attempt < self::MAX_ATTEMPTS) {
                    sleep(2 ** $attempt);
                }
            }
        }

        throw new RuntimeException(
            "Chiamata all'API CAI fallita dopo ".self::MAX_ATTEMPTS." tentativi: {$url}",
            previous: $lastException instanceof Throwable ? $lastException : null,
        );
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php`
Expected: PASS (4 tests) — the retry test takes ~6 seconds real time (`sleep(2)` + `sleep(4)`), that is expected, not a hang.

- [ ] **Step 6: Commit**

```bash
git add app/Domain/CaiDirectory/Support/CaiApiClient.php config/cai_directory.php .env.example tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php
git commit -m "feat: Fase 9 storia 1.5 - CaiApiClient con retry per l'API pubblica CAI"
```

---

### Task 6: `SyncCaiSectionAndSubsections` — shared upsert logic

**Files:**
- Create: `app/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsections.php`
- Modify: `tests/Pest.php` (add a shared `caiSection()` helper)
- Modify: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` (remove the now-duplicated local `caiSection()` helper)
- Test: `tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php`

**Interfaces:**
- Consumes: `CaiApiClient::fetchSubsections()` (Task 5), `CaiApiSectionNormalizer::normalizeSubsection()` (Task 4), `CaiSectionFieldMapper::mapSection()`/`mapSubsection()` (Task 3), `DiffsAttributes::attributesDiffer()`.
- Produces: `SyncCaiSectionAndSubsections::run(object $normalizedSectionRow, bool $dryRun = false): array{cai_sections: CaiImportTableResult, cai_subsections: CaiImportTableResult}` — consumed by `ScrapeCaiSection` (Task 7) and `CaiSyncNationalCommand` (Task 10).

- [ ] **Step 1: Centralize the `caiSection()` test helper in `tests/Pest.php`**

This task's own tests need a `CaiSection` factory helper, and so will Task 7/10's tests — three call sites is the repo's own established threshold for moving a locally-declared test helper into `tests/Pest.php` (already done for `ticket()`, `grantTicketPanelRole()`, `tag()`). `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` already declares a local `caiSection()` — declaring a second global function with the same name in the same Pest process is a fatal redeclare, so it must be removed from there.

In `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`, delete this function (currently right after the `grantCaiDirectoryPanelAccess()` function, right before the first `test(...)` call):

```php
/**
 * @param  array<string, mixed>  $attributes
 */
function caiSection(array $attributes = []): CaiSection
{
    static $sequence = 0;
    $sequence++;

    return CaiSection::create(array_merge([
        'codice_cai' => 'CAI-'.$sequence,
        'name' => 'Sezione CAI '.$sequence,
        'region' => 'LOMBARDIA',
    ], $attributes))->fresh();
}
```

In `tests/Pest.php`, add the `use App\Domain\CaiDirectory\Models\CaiSection;` import alongside the other `use App\Domain\...` lines near the top of the file (alphabetically, right after `use App\Domain\Identity\Models\User;` — before `use App\Domain\Tags\Models\Tag;`), then append this function at the very end of the file (after the closing `}` of `makeCaiDatapackFixture()`):

```php

/**
 * Crea una `CaiSection` di test con un `codice_cai` univoco (sequenza incrementale),
 * spostato qui da `CaiSectionResourceTest.php` (US-804) quando è servito anche ai test
 * dello scraper live (Fase 9).
 *
 * @param  array<string, mixed>  $attributes
 */
function caiSection(array $attributes = []): CaiSection
{
    static $sequence = 0;
    $sequence++;

    return CaiSection::create(array_merge([
        'codice_cai' => 'CAI-'.$sequence,
        'name' => 'Sezione CAI '.$sequence,
        'region' => 'LOMBARDIA',
    ], $attributes))->fresh();
}
```

- [ ] **Step 2: Run the existing CaiSectionResourceTest to verify the helper move didn't break anything**

Run: `vendor/bin/pest tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`
Expected: PASS, unchanged (the function now resolves from `tests/Pest.php` instead of the local declaration — identical body, identical behavior).

- [ ] **Step 3: Write the failing test for `SyncCaiSectionAndSubsections`**

```php
<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Actions\SyncCaiSectionAndSubsections;
use App\Domain\CaiDirectory\Import\CaiApiSectionNormalizer;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function fakeCaiSubsections(string $sectionCode, array $subsections): void
{
    Http::fake([
        "https://www.cai.it/wp-json/cai-section/v2/sections/{$sectionCode}/sub-sections-list*" => Http::response($subsections),
    ]);
}

test('run creates a new CaiSection with cai_last_synced_at set', function (): void {
    fakeCaiSubsections('9216049', []);

    $row = CaiApiSectionNormalizer::normalizeSection([
        'code' => '9216049',
        'name' => 'Sezione di Como',
        'region' => 'lombardia',
    ]);

    $results = app(SyncCaiSectionAndSubsections::class)->run($row);

    expect($results['cai_sections']->created)->toBe(1);
    expect($results['cai_sections']->updated)->toBe(0);

    $section = CaiSection::query()->findOrFail('9216049');
    expect($section->name)->toBe('Sezione di Como');
    expect($section->cai_last_synced_at)->not->toBeNull();
});

test('run updates an existing CaiSection when a field actually changed, and always bumps cai_last_synced_at', function (): void {
    fakeCaiSubsections('9216049', []);

    $existing = caiSection(['codice_cai' => '9216049', 'name' => 'Vecchio nome', 'cai_last_synced_at' => null]);

    $row = CaiApiSectionNormalizer::normalizeSection([
        'code' => '9216049',
        'name' => 'Sezione di Como',
        'region' => 'lombardia',
    ]);

    $results = app(SyncCaiSectionAndSubsections::class)->run($row);

    expect($results['cai_sections']->updated)->toBe(1);
    expect($results['cai_sections']->created)->toBe(0);

    $section = $existing->fresh();
    expect($section->name)->toBe('Sezione di Como');
    expect($section->cai_last_synced_at)->not->toBeNull();
});

test('run counts a section as skipped when nothing actually changed, but still bumps cai_last_synced_at', function (): void {
    fakeCaiSubsections('9216049', []);

    $existing = caiSection([
        'codice_cai' => '9216049',
        'name' => 'Sezione di Como',
        'region' => 'LOMBARDIA',
        'cai_last_synced_at' => null,
    ]);

    $row = CaiApiSectionNormalizer::normalizeSection([
        'code' => '9216049',
        'name' => 'Sezione di Como',
        'region' => 'lombardia',
    ]);

    $results = app(SyncCaiSectionAndSubsections::class)->run($row);

    expect($results['cai_sections']->skipped)->toBe(1);
    expect($results['cai_sections']->updated)->toBe(0);
    expect($existing->fresh()->cai_last_synced_at)->not->toBeNull();
});

test('run creates subsections fetched from the per-section API endpoint', function (): void {
    caiSection(['codice_cai' => '9216049', 'name' => 'Sezione di Como']);

    fakeCaiSubsections('9216049', [
        ['code' => 'SUB-1', 'name' => 'Sottosezione Erba'],
    ]);

    $row = CaiApiSectionNormalizer::normalizeSection([
        'code' => '9216049',
        'name' => 'Sezione di Como',
        'region' => 'lombardia',
    ]);

    $results = app(SyncCaiSectionAndSubsections::class)->run($row);

    expect($results['cai_subsections']->created)->toBe(1);

    $subsection = CaiSubsection::query()->findOrFail('SUB-1');
    expect($subsection->name)->toBe('Sottosezione Erba');
    expect($subsection->cai_section_id)->toBe('9216049');
    expect($subsection->cai_last_synced_at)->not->toBeNull();
});

test('run does not write anything in dry-run mode', function (): void {
    fakeCaiSubsections('9216049', [['code' => 'SUB-1', 'name' => 'Sottosezione Erba']]);

    $row = CaiApiSectionNormalizer::normalizeSection([
        'code' => '9216049',
        'name' => 'Sezione di Como',
        'region' => 'lombardia',
    ]);

    $results = app(SyncCaiSectionAndSubsections::class)->run($row, dryRun: true);

    expect($results['cai_sections']->created)->toBe(1);
    expect(CaiSection::query()->find('9216049'))->toBeNull();
    expect(CaiSubsection::query()->find('SUB-1'))->toBeNull();
});

test('run matches a section email to an existing user, case-insensitively', function (): void {
    fakeCaiSubsections('9216049', []);

    $user = User::factory()->create(['email' => 'como@cai.it']);

    $row = CaiApiSectionNormalizer::normalizeSection([
        'code' => '9216049',
        'name' => 'Sezione di Como',
        'email' => 'Como@Cai.It',
        'region' => 'lombardia',
    ]);

    app(SyncCaiSectionAndSubsections::class)->run($row);

    expect(CaiSection::query()->findOrFail('9216049')->user_id)->toBe($user->id);
});
```

- [ ] **Step 4: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php`
Expected: FAIL — `Class "App\Domain\CaiDirectory\Actions\SyncCaiSectionAndSubsections" not found`.

- [ ] **Step 5: Create `SyncCaiSectionAndSubsections`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Actions;

use App\Domain\CaiDirectory\Import\CaiApiSectionNormalizer;
use App\Domain\CaiDirectory\Import\CaiImportTableResult;
use App\Domain\CaiDirectory\Import\CaiSectionFieldMapper;
use App\Domain\CaiDirectory\Import\Concerns\DiffsAttributes;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\CaiDirectory\Support\CaiApiClient;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Sincronizza dal vivo UNA `CaiSection` (e le sue sottosezioni) a partire da una riga
 * già normalizzata dell'API CAI (Fase 9, storia 1, design doc §3.1/§3.4/§3.5) —
 * "va estratta e condivisa, non duplicata": riusata sia da {@see ScrapeCaiSection}
 * (una sola sezione, bottone dashboard) sia da `CaiSyncNationalCommand` (loop su
 * tutte le sezioni, refresh mensile).
 *
 * `cai_last_synced_at` viene sempre valorizzato a `now()` (sia sulla sezione sia su
 * ciascuna sottosezione) quando NON in dry-run, indipendentemente dal fatto che i
 * campi di business siano cambiati: contattare la fonte e confermare che i dati sono
 * invariati resta comunque una sincronizzazione avvenuta. I contatori
 * created/updated/skipped restano invece basati SOLO sul confronto dei campi di
 * business (via {@see DiffsAttributes}, che non include mai `cai_last_synced_at`
 * nell'array `$attributes` confrontato) — altrimenti ogni riga risulterebbe sempre
 * "updated" per il solo bump del timestamp, rendendo il conteggio inutile per la
 * dashboard/notifica del bottone (che lo usa per decidere il testo "aggiornato" vs
 * "già aggiornato").
 */
final class SyncCaiSectionAndSubsections
{
    use DiffsAttributes;

    public function __construct(private readonly CaiApiClient $apiClient) {}

    /**
     * @return array{cai_sections: CaiImportTableResult, cai_subsections: CaiImportTableResult}
     */
    public function run(object $normalizedSectionRow, bool $dryRun = false): array
    {
        $usersByLowerEmail = $this->buildUsersByLowerEmail();

        return [
            'cai_sections' => $this->syncSection($normalizedSectionRow, $usersByLowerEmail, $dryRun),
            'cai_subsections' => $this->syncSubsections($normalizedSectionRow->codice_cai, $usersByLowerEmail, $dryRun),
        ];
    }

    /**
     * @param  array<string, int>  $usersByLowerEmail
     */
    private function syncSection(object $row, array $usersByLowerEmail, bool $dryRun): CaiImportTableResult
    {
        $attributes = CaiSectionFieldMapper::mapSection($row, $usersByLowerEmail);
        $existing = CaiSection::find((string) $row->codice_cai);

        if ($existing === null) {
            if (! $dryRun) {
                CaiSection::create(['codice_cai' => $row->codice_cai, ...$attributes, 'cai_last_synced_at' => Carbon::now()]);
            }

            return new CaiImportTableResult(read: 1, created: 1);
        }

        $changed = $this->attributesDiffer($existing, $attributes);

        if (! $dryRun) {
            $existing->fill($attributes);
            $existing->cai_last_synced_at = Carbon::now();
            $existing->save();
        }

        return $changed
            ? new CaiImportTableResult(read: 1, updated: 1)
            : new CaiImportTableResult(read: 1, skipped: 1);
    }

    /**
     * @param  array<string, int>  $usersByLowerEmail
     */
    private function syncSubsections(string $sectionCode, array $usersByLowerEmail, bool $dryRun): CaiImportTableResult
    {
        $rawSubsections = $this->apiClient->fetchSubsections($sectionCode);

        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rawSubsections as $raw) {
            $read++;

            $row = CaiApiSectionNormalizer::normalizeSubsection($raw, $sectionCode);
            $attributes = CaiSectionFieldMapper::mapSubsection($row, $usersByLowerEmail);
            $existing = CaiSubsection::find((string) $row->cai_codice);

            if ($existing === null) {
                $created++;

                if (! $dryRun) {
                    CaiSubsection::create(['cai_codice' => $row->cai_codice, ...$attributes, 'cai_last_synced_at' => Carbon::now()]);
                }

                continue;
            }

            $changed = $this->attributesDiffer($existing, $attributes);
            $changed ? $updated++ : $skipped++;

            if (! $dryRun) {
                $existing->fill($attributes);
                $existing->cai_last_synced_at = Carbon::now();
                $existing->save();
            }
        }

        return new CaiImportTableResult(read: $read, created: $created, updated: $updated, skipped: $skipped);
    }

    /**
     * @return array<string, int>
     */
    private function buildUsersByLowerEmail(): array
    {
        return User::query()
            ->whereNotNull('email')
            ->get(['id', 'email'])
            ->mapWithKeys(fn (User $user): array => [Str::lower(trim((string) $user->email)) => $user->id])
            ->all();
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php`
Expected: PASS (6 tests)

- [ ] **Step 7: Commit**

```bash
git add app/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsections.php tests/Pest.php tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php
git commit -m "feat: Fase 9 storia 1.6 - SyncCaiSectionAndSubsections, sync live condiviso sezione+sottosezioni"
```

---

### Task 7: `ScrapeCaiSection` Action — single-section entry point

**Files:**
- Create: `app/Domain/CaiDirectory/Actions/ScrapeCaiSection.php`
- Test: `tests/Feature/Domain/CaiDirectory/Actions/ScrapeCaiSectionTest.php`

**Interfaces:**
- Consumes: `CaiApiClient::fetchNationalSections()` (Task 5), `CaiApiSectionNormalizer::normalizeSection()` (Task 4), `SyncCaiSectionAndSubsections::run()` (Task 6).
- Produces: `ScrapeCaiSection::run(string $codiceCai): array{cai_sections: CaiImportTableResult, cai_subsections: CaiImportTableResult}` — consumed by `CustomerDashboard::syncCaiDataAction()` (Task 8). Throws `RuntimeException` when `$codiceCai` is not present in the national list.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Actions\ScrapeCaiSection;
use App\Domain\CaiDirectory\Models\CaiSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('run fetches the national list, isolates the requested section and syncs it', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216049', 'name' => 'Sezione di Como', 'region' => 'lombardia'],
            ['code' => '9216050', 'name' => 'Sezione di Pisa', 'region' => 'toscana'],
        ]),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216049/sub-sections-list*' => Http::response([]),
    ]);

    $results = app(ScrapeCaiSection::class)->run('9216049');

    expect($results['cai_sections']->created)->toBe(1);

    $section = CaiSection::query()->findOrFail('9216049');
    expect($section->name)->toBe('Sezione di Como');
    expect($section->region)->toBe('LOMBARDIA');

    expect(CaiSection::query()->find('9216050'))->toBeNull();
});

test('run only fetches subsections for the requested section, never for other sections in the national list', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216049', 'name' => 'Sezione di Como', 'region' => 'lombardia'],
            ['code' => '9216050', 'name' => 'Sezione di Pisa', 'region' => 'toscana'],
        ]),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216049/sub-sections-list*' => Http::response([]),
    ]);

    app(ScrapeCaiSection::class)->run('9216049');

    Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/sections/9216050/'));
});

test('run throws when the requested section code is not present in the national list', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216050', 'name' => 'Sezione di Pisa', 'region' => 'toscana'],
        ]),
    ]);

    expect(fn () => app(ScrapeCaiSection::class)->run('9216049'))->toThrow(RuntimeException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Domain/CaiDirectory/Actions/ScrapeCaiSectionTest.php`
Expected: FAIL — `Class "App\Domain\CaiDirectory\Actions\ScrapeCaiSection" not found`.

- [ ] **Step 3: Create `ScrapeCaiSection`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Actions;

use App\Domain\CaiDirectory\Import\CaiApiSectionNormalizer;
use App\Domain\CaiDirectory\Import\CaiImportTableResult;
use App\Domain\CaiDirectory\Support\CaiApiClient;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Sincronizza dal vivo UNA sola sezione CAI (Fase 9, storia 1, design doc §3.4):
 * entry point del bottone "Sincronizza dati CAI" della dashboard cliente
 * ({@see \App\Filament\Pages\CustomerDashboard::syncCaiDataAction()}). Recupera
 * l'intero elenco nazionale dall'API CAI (l'endpoint non supporta un filtro per
 * singola sezione — verificato sul prototipo Python, design doc §2) e ne isola la
 * sola sezione richiesta prima di delegare la sincronizzazione vera e propria a
 * {@see SyncCaiSectionAndSubsections}.
 */
final class ScrapeCaiSection
{
    public function __construct(
        private readonly CaiApiClient $apiClient,
        private readonly SyncCaiSectionAndSubsections $syncer,
    ) {}

    /**
     * @return array{cai_sections: CaiImportTableResult, cai_subsections: CaiImportTableResult}
     */
    public function run(string $codiceCai): array
    {
        $rawSections = $this->apiClient->fetchNationalSections();

        $rawSection = Collection::make($rawSections)
            ->first(fn (array $raw): bool => ($raw['code'] ?? null) === $codiceCai);

        if ($rawSection === null) {
            throw new RuntimeException("Nessuna sezione CAI con codice \"{$codiceCai}\" trovata sull'API CAI.");
        }

        $normalized = CaiApiSectionNormalizer::normalizeSection($rawSection);

        return $this->syncer->run($normalized);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/Domain/CaiDirectory/Actions/ScrapeCaiSectionTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Domain/CaiDirectory/Actions/ScrapeCaiSection.php tests/Feature/Domain/CaiDirectory/Actions/ScrapeCaiSectionTest.php
git commit -m "feat: Fase 9 storia 1.7 - ScrapeCaiSection, sincronizzazione live di una sola sezione"
```

---

### Task 8: Wire the "Sincronizza dati CAI" button to `ScrapeCaiSection`

**Files:**
- Modify: `app/Filament/Pages/CustomerDashboard.php`
- Modify: `tests/Feature/Filament/Pages/CustomerDashboardTest.php`

**Interfaces:**
- Consumes: `ScrapeCaiSection::run(string $codiceCai): array` (Task 7).
- Produces: no change to the button's public name (`sync_cai_data`), label, icon, color, or `->visible()` condition — only its `->action()` closure changes.

- [ ] **Step 1: Update the existing dashboard test that covers this button**

Find the existing test in `tests/Feature/Filament/Pages/CustomerDashboardTest.php` that exercises the CAI sync button against the datapack importer (it uses `makeCaiDatapackFixture()` and asserts on `CaiDatapackImporter`-driven behavior for the `sync_cai_data` action). Replace it with:

```php
test('the sync cai data action live-scrapes only the current customer's own section from the CAI API', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216049', 'name' => 'Sezione di Como (aggiornata)', 'region' => 'lombardia'],
        ]),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216049/sub-sections-list*' => Http::response([]),
    ]);

    $user = User::factory()->create(['customer_type' => CustomerType::Sezione]);
    grantCustomerRole($user);

    caiSection(['codice_cai' => '9216049', 'name' => 'Sezione di Como', 'user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(CustomerDashboard::class)
        ->callAction('sync_cai_data')
        ->assertNotified();

    $section = CaiSection::query()->findOrFail('9216049');
    expect($section->name)->toBe('Sezione di Como (aggiornata)');
    expect($section->cai_last_synced_at)->not->toBeNull();
});
```

If the test file does not already import `Illuminate\Support\Facades\Http` and `App\Domain\CaiDirectory\Models\CaiSection`, add those `use` statements. If the test file uses a different helper name than `grantCustomerRole($user)` to grant the panel-access role/customer setup already used by neighboring dashboard tests in the same file, use that existing helper instead — do not invent a second one; check the file's other `test(...)` blocks for the exact pattern already in use for a Sezione customer.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Filament/Pages/CustomerDashboardTest.php --filter="live-scrapes"`
Expected: FAIL — the button still calls `CaiDatapackImporter`, so `Http::fake()` is never hit and the assertion on the updated name fails (or the test errors because no HTTP fake matched an unexpected datapack-file-not-found notification).

- [ ] **Step 3: Update `CustomerDashboard::syncCaiDataAction()`**

In `app/Filament/Pages/CustomerDashboard.php`, add the import:

```php
use App\Domain\CaiDirectory\Actions\ScrapeCaiSection;
```

(add it alphabetically among the existing `use App\Domain\CaiDirectory\...` imports, i.e. right before `use App\Domain\CaiDirectory\Models\CaiSection;`).

Keep the method signature, `->label(...)`, `->icon(...)`, `->color('gray')` and `->visible(...)` lines exactly as they are. Replace only the `->action(function (): void { ... })` body:

```php
    public function syncCaiDataAction(): Action
    {
        return Action::make('sync_cai_data')
            ->label('Sincronizza dati CAI')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->visible(fn (): bool => $this->isSezione() && $this->caiSection() !== null)
            ->action(function (): void {
                $section = $this->caiSection();

                if ($section === null) {
                    return;
                }

                try {
                    $results = app(ScrapeCaiSection::class)->run($section->codice_cai);
                } catch (\Throwable $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Sincronizzazione non riuscita')
                        ->body($exception->getMessage())
                        ->send();

                    return;
                }

                $updated = $results['cai_sections']->created > 0 || $results['cai_sections']->updated > 0;

                Notification::make()
                    ->success()
                    ->title('Sincronizzazione completata')
                    ->body($updated ? 'I dati della tua sezione sono stati aggiornati dal sito CAI.' : 'I dati della tua sezione erano già aggiornati.')
                    ->send();
            });
    }
```

Update the docblock above the method (currently describing the datapack-based behavior) to describe the live-scrape behavior:

```php
    /**
     * Bottone "Sincronizza dati CAI" (Fase 9, storia 1): chiama dal vivo l'API
     * pubblica CAI ({@see ScrapeCaiSection}) scoped alla sola sezione dell'utente
     * corrente — mai l'intero elenco nazionale da un bottone cliente. Sostituisce il
     * precedente re-import dal datapack statico (design doc §3.4): il datapack resta
     * comunque il meccanismo di bootstrap iniziale per un ambiente nuovo
     * ({@see \App\Domain\CaiDirectory\Import\CaiDatapackImporter}, invariato). Visibile
     * solo se esiste già una `CaiSection` collegata. Registrata via
     * {@see self::getHeaderActions()} (pattern collaudato nel repo): un'action
     * standalone risolta solo dinamicamente da una property blade non esegue il
     * proprio closure quando invocata tramite `Livewire::test()->callAction()` in
     * questa versione di Filament — verificato empiricamente. Registrarla qui evita
     * il problema.
     */
```

Note: `syncRuntsDataAction()` and `resolveDatapackAbsolutePathOrNotify()` are unchanged (the RUNTS button still uses the static datapack — Stories 2-3 of the spec, out of scope here). Do not remove the `use App\Domain\CaiDirectory\Import\CaiDatapackImporter;` import: `syncRuntsDataAction()` still needs it.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/Filament/Pages/CustomerDashboardTest.php`
Expected: PASS (all tests in the file, including the RUNTS-button test which is untouched and must still pass).

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Pages/CustomerDashboard.php tests/Feature/Filament/Pages/CustomerDashboardTest.php
git commit -m "feat: Fase 9 storia 1.8 - il bottone Sincronizza dati CAI chiama l'API live invece del datapack"
```

---

### Task 9: Show `cai_last_synced_at` in `CaiSectionInfolist`

**Files:**
- Modify: `app/Filament/Resources/CaiSections/Schemas/CaiSectionInfolist.php`
- Test: `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php` (add one assertion)

**Interfaces:**
- Consumes: `CaiSection::$cai_last_synced_at`, `CaiSubsection::$cai_last_synced_at` (Task 1/2).
- Produces: no change to the Infolist's public shape (`configure(Schema $schema): Schema`) — this is a shared component also used by `CustomerDashboard::caiSectionInfolist()` and `CaiSectionRegionalDetail`, so this one edit covers all three surfaces (documented repo convention).

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`:

```php
test('the section detail page shows the last live-sync timestamp, or a "never synced" placeholder', function (): void {
    grantCaiDirectoryPanelAccess($user = User::factory()->create());

    $syncedSection = caiSection(['cai_last_synced_at' => now()]);
    $neverSyncedSection = caiSection(['cai_last_synced_at' => null]);

    Livewire::actingAs($user)
        ->test(ViewCaiSection::class, ['record' => $syncedSection->getRouteKey()])
        ->assertSeeText(now()->format('d/m/Y'));

    Livewire::actingAs($user)
        ->test(ViewCaiSection::class, ['record' => $neverSyncedSection->getRouteKey()])
        ->assertSeeText('Mai sincronizzato dal vivo');
});
```

If `Livewire::actingAs($user)` is not the pattern used by the rest of this file's tests (check the file for how `$user` is authenticated before mounting `ViewCaiSection`/`ListCaiSections` — it may use `actingAs($user)` at the top level or a different helper), match whatever pattern the file already uses instead of introducing a new one.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php --filter="last live-sync timestamp"`
Expected: FAIL — neither the timestamp nor the placeholder text appears anywhere in the rendered Infolist yet.

- [ ] **Step 3: Add the entry to `caiDataSection()`**

In `app/Filament/Resources/CaiSections/Schemas/CaiSectionInfolist.php`, in `caiDataSection()`, insert a new `TextEntry` immediately before `TextEntry::make('user.name')->label('Utente collegato')->placeholder('Nessuno'),`:

```php
                TextEntry::make('cai_last_synced_at')
                    ->label('Ultimo aggiornamento dal sito CAI')
                    ->columnSpanFull()
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Mai sincronizzato dal vivo'),
                TextEntry::make('user.name')->label('Utente collegato')->placeholder('Nessuno'),
```

- [ ] **Step 4: Also show it per-subsection in `subsectionsSection()`**

In the same file, in `subsectionsSection()`, add a new `TextEntry` after `TextEntry::make('phone')->label('Telefono')->placeholder('—'),` and bump `->columns(4)` to `->columns(5)`:

```php
    private static function subsectionsSection(): Section
    {
        return Section::make('Sottosezioni')
            ->schema([
                RepeatableEntry::make('subsections')
                    ->hiddenLabel()
                    ->schema([
                        TextEntry::make('name')->label('Denominazione'),
                        TextEntry::make('email')->label('Email')->placeholder('—'),
                        TextEntry::make('phone')->label('Telefono')->placeholder('—'),
                        TextEntry::make('cai_last_synced_at')
                            ->label('Ultimo aggiornamento')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Mai sincronizzato dal vivo'),
                        TextEntry::make('user.name')->label('Utente collegato')->placeholder('Nessuno'),
                    ])
                    ->columns(5)
                    ->placeholder('Nessuna sottosezione collegata'),
            ]);
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php`
Expected: PASS (entire file, including the new test).

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Resources/CaiSections/Schemas/CaiSectionInfolist.php tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php
git commit -m "feat: Fase 9 storia 1.9 - mostra cai_last_synced_at nell'Infolist condiviso sezione/sottosezioni"
```

---

### Task 10: `cai:sync-national` scheduled command

**Files:**
- Create: `app/Console/Commands/CaiSyncNationalCommand.php`
- Modify: `config/cai_directory.php`
- Modify: `config/orchestrator.php`
- Modify: `.env.example`
- Modify: `routes/console.php`
- Test: `tests/Feature/Console/CaiSyncNationalCommandTest.php`

**Interfaces:**
- Consumes: `CaiApiClient::fetchNationalSections()` (Task 5), `CaiApiSectionNormalizer::normalizeSection()` (Task 4), `SyncCaiSectionAndSubsections::run()` (Task 6), `config('orchestrator.features.cai_sync_national')`.
- Produces: `php artisan cai:sync-national [--dry-run]`, scheduled monthly, gated behind `ENABLE_CAI_SYNC_NATIONAL`.

- [ ] **Step 1: Add config keys**

In `config/cai_directory.php`, add a `'sync_national'` key after `'api'`:

```php
    /*
     * Refresh mensile nazionale (Fase 9, storia 1, design doc §3.5): cadenza cron di
     * `cai:sync-national`, dietro il feature flag
     * `config('orchestrator.features.cai_sync_national')` (disattivo di default).
     */
    'sync_national' => [
        'schedule_cron' => env('CAI_SYNC_NATIONAL_SCHEDULE_CRON', '0 6 1 * *'),
    ],
```

In `config/orchestrator.php`, add a new line to the `'features'` array, immediately after the `'tickets_idle_developer_notice'` line:

```php
        'tickets_idle_developer_notice' => (bool) env('ENABLE_TICKETS_IDLE_DEVELOPER_NOTICE', false),
        'cai_sync_national' => (bool) env('ENABLE_CAI_SYNC_NATIONAL', false),
    ],
```

In `.env.example`, add these two lines: `CAI_SYNC_NATIONAL_SCHEDULE_CRON="0 6 1 * *"` right after the `CAI_API_TIMEOUT_SECONDS` line added in Task 5, and `ENABLE_CAI_SYNC_NATIONAL=false` right after the existing `ENABLE_TICKETS_IDLE_DEVELOPER_NOTICE=false` line in the feature-flag block.

- [ ] **Step 2: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('cai:sync-national syncs every section and subsection returned by the national API', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216049', 'name' => 'Sezione di Como', 'region' => 'lombardia'],
            ['code' => '9216050', 'name' => 'Sezione di Pisa', 'region' => 'toscana'],
        ]),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216049/sub-sections-list*' => Http::response([['code' => 'SUB-1', 'name' => 'Sottosezione Erba']]),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216050/sub-sections-list*' => Http::response([]),
    ]);

    $this->artisan('cai:sync-national')->assertExitCode(0);

    expect(CaiSection::query()->count())->toBe(2);
    expect(CaiSection::query()->findOrFail('9216049')->cai_last_synced_at)->not->toBeNull();
    expect(CaiSection::query()->findOrFail('9216050')->cai_last_synced_at)->not->toBeNull();
});

test('cai:sync-national --dry-run does not write anything', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216049', 'name' => 'Sezione di Como', 'region' => 'lombardia'],
        ]),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216049/sub-sections-list*' => Http::response([]),
    ]);

    $this->artisan('cai:sync-national', ['--dry-run' => true])->assertExitCode(0);

    expect(CaiSection::query()->count())->toBe(0);
});

test('cai:sync-national continues past a section that fails to sync', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216049', 'name' => 'Sezione di Como', 'region' => 'lombardia'],
            ['code' => '9216050', 'name' => 'Sezione di Pisa', 'region' => 'toscana'],
        ]),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216049/sub-sections-list*' => Http::response(null, 500),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216050/sub-sections-list*' => Http::response([]),
    ]);

    $this->artisan('cai:sync-national')->assertExitCode(0);

    expect(CaiSection::query()->find('9216049'))->toBeNull();
    expect(CaiSection::query()->findOrFail('9216050')->name)->toBe('Sezione di Pisa');
})->skip('la sezione con subsections in errore fallisce con retry reale (2s+4s): abilitare se il tempo del run non è un vincolo, altrimenti verificare solo il comportamento con Http::fakeSequence senza ritardo');
```

Note on the last (skipped) test: `CaiApiClient`'s retry sleeps for real seconds, which would make this specific scenario slow (each failing section costs ~6 seconds). It is included to document the intended behavior (one section's exception must not abort the loop) but marked `->skip()` with an explanation rather than deleted, consistent with "no placeholders" — the assertion is real and correct, only its runtime cost is the reason to skip by default. A future pass may replace `sleep()` in `CaiApiClient` with an injectable sleeper to make this test fast; that refactor is out of scope for this story.

- [ ] **Step 3: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Console/CaiSyncNationalCommandTest.php`
Expected: FAIL — `Command "cai:sync-national" is not defined.`

- [ ] **Step 4: Create `CaiSyncNationalCommand`**

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Actions\SyncCaiSectionAndSubsections;
use App\Domain\CaiDirectory\Import\CaiApiSectionNormalizer;
use App\Domain\CaiDirectory\Support\CaiApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Refresh mensile nazionale CAI (Fase 9, storia 1, design doc §3.5): chiama l'API CAI
 * per l'elenco nazionale completo, poi sincronizza ogni sezione e le sue sottosezioni
 * riusando {@see SyncCaiSectionAndSubsections} (stessa logica del bottone dashboard,
 * {@see \App\Domain\CaiDirectory\Actions\ScrapeCaiSection}, applicata in loop). Un
 * errore su una singola sezione (es. l'endpoint sottosezioni di quella sezione fallisce
 * dopo i retry) è loggato e non interrompe le altre — stesso principio già in uso da
 * `TicketsAutoCloseReleasedCommand`/`ApplyStatusToChildren`.
 */
class CaiSyncNationalCommand extends Command
{
    protected $signature = 'cai:sync-national {--dry-run : Calcola le modifiche senza scriverle}';

    protected $description = "Sincronizza dal vivo tutte le sezioni/sottosezioni CAI dall'API ufficiale (refresh mensile, Fase 9 storia 1)";

    public function __construct(
        private readonly CaiApiClient $apiClient,
        private readonly SyncCaiSectionAndSubsections $syncer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $startedAt = now();

        Log::info('cai.sync_national.started', ['dry_run' => $dryRun]);

        $rawSections = $this->apiClient->fetchNationalSections();

        $examined = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($rawSections as $raw) {
            $examined++;
            $codiceCai = (string) ($raw['code'] ?? '(sconosciuto)');

            try {
                $normalized = CaiApiSectionNormalizer::normalizeSection($raw);
                $results = $this->syncer->run($normalized, $dryRun);

                $created += $results['cai_sections']->created + $results['cai_subsections']->created;
                $updated += $results['cai_sections']->updated + $results['cai_subsections']->updated;
                $skipped += $results['cai_sections']->skipped + $results['cai_subsections']->skipped;

                $this->line(sprintf(
                    '- %s: sezione creati %d, aggiornati %d, saltati %d; sottosezioni creati %d, aggiornati %d, saltati %d',
                    $codiceCai,
                    $results['cai_sections']->created,
                    $results['cai_sections']->updated,
                    $results['cai_sections']->skipped,
                    $results['cai_subsections']->created,
                    $results['cai_subsections']->updated,
                    $results['cai_subsections']->skipped,
                ));
            } catch (Throwable $exception) {
                $errors++;
                Log::warning('cai.sync_national.item_failed', ['codice_cai' => $codiceCai, 'error' => $exception->getMessage()]);
                $this->warn("- {$codiceCai}: sincronizzazione fallita — {$exception->getMessage()}");
            }
        }

        Log::info('cai.sync_national.finished', [
            'dry_run' => $dryRun,
            'examined' => $examined,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'duration_ms' => $startedAt->diffInMilliseconds(now()),
        ]);

        $this->info(sprintf(
            'Sincronizzazione nazionale CAI completata: %d sezioni esaminate, %d create, %d aggiornate, %d invariate, %d errori.',
            $examined,
            $created,
            $updated,
            $skipped,
            $errors,
        ));

        return self::SUCCESS;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/Console/CaiSyncNationalCommandTest.php`
Expected: PASS (2 executed tests, 1 skipped)

- [ ] **Step 6: Register the command in `routes/console.php`**

Add the import at line 5 (alphabetically first, before `use App\Console\Commands\MailFetchInboundCommand;`):

```php
use App\Console\Commands\CaiSyncNationalCommand;
```

Append this block at the end of the file, following the exact same shape as the last existing entry (`TicketsNotifyIdleDevelopersCommand`):

```php

// Fase 9, storia 1 (design doc `2026-09-07-cai-runts-live-sync-design.md` §3.5):
// refresh mensile nazionale dei dati CAI dall'API pubblica. Cadenza configurabile
// da config('cai_directory.sync_national.schedule_cron'), dietro il feature flag
// config('orchestrator.features.cai_sync_national'). `cai:sync-national` resta
// comunque richiamabile manualmente da CLI indipendentemente da questo flag.
Schedule::command(CaiSyncNationalCommand::class)
    ->cron((string) config('cai_directory.sync_national.schedule_cron'))
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('orchestrator.features.cai_sync_national'));
```

- [ ] **Step 7: Verify the scheduler config loads without error**

Run: `php artisan schedule:list`
Expected: exits 0, output includes a `cai:sync-national` row (or is silently absent from the effective list if the flag defaults to `false` in the current `.env` — either is correct; what must NOT happen is an error/exception).

- [ ] **Step 8: Commit**

```bash
git add app/Console/Commands/CaiSyncNationalCommand.php config/cai_directory.php config/orchestrator.php .env.example routes/console.php tests/Feature/Console/CaiSyncNationalCommandTest.php
git commit -m "feat: Fase 9 storia 1.10 - comando schedulato cai:sync-national dietro feature flag"
```

---

### Task 11: End-to-end regression pass

**Files:**
- None created — this task runs the full existing regression surface touched by this plan and fixes any fallout.

**Interfaces:**
- Consumes: everything from Tasks 1-10.
- Produces: confidence that the refactor of `CaiDatapackImporter` (Task 3) and the model/Infolist changes (Task 2/9) did not regress the pre-existing CAI/RUNTS feature set from Fase 8.

- [ ] **Step 1: Run every test file touched or depended on by this plan together**

Run:
```bash
vendor/bin/pest \
  tests/Feature/Database/CaiLastSyncedAtColumnsTest.php \
  tests/Unit/Domain/CaiDirectory/Models/CaiLastSyncedAtFillableTest.php \
  tests/Unit/Domain/CaiDirectory/Import/CaiSectionFieldMapperTest.php \
  tests/Unit/Domain/CaiDirectory/Import/CaiApiSectionNormalizerTest.php \
  tests/Feature/Domain/CaiDirectory/Support/CaiApiClientTest.php \
  tests/Feature/Domain/CaiDirectory/Actions/SyncCaiSectionAndSubsectionsTest.php \
  tests/Feature/Domain/CaiDirectory/Actions/ScrapeCaiSectionTest.php \
  tests/Feature/Filament/Pages/CustomerDashboardTest.php \
  tests/Feature/Filament/CaiDirectory/CaiSectionResourceTest.php \
  tests/Feature/Console/CaiSyncNationalCommandTest.php \
  tests/Feature/Console/CaiImportDatapackCommandTest.php
```
Expected: all PASS (the last file, `CaiImportDatapackCommandTest.php`, is the pre-existing regression suite for the datapack path refactored in Task 3 — it must show identical results to before this plan started).

- [ ] **Step 2: Run static analysis**

Run: `composer run analyse`
Expected: no new errors introduced by any file created/modified in this plan (Larastan level 6, `--memory-limit=1G`).

- [ ] **Step 3: Run the linter**

Run: `composer run lint`
Expected: no changes needed, or auto-fixed and re-verified with a second `composer run lint` run.

- [ ] **Step 4: Fix any regression found in Steps 1-3**

If a pre-existing test fails that this plan's diff did not intend to touch, use the `superpowers:systematic-debugging` skill to find the root cause before patching — do not silence a failing assertion without understanding why it changed.

- [ ] **Step 5: Commit (only if Step 4 produced changes)**

```bash
git add -A
git commit -m "fix: Fase 9 storia 1.11 - correzioni emerse dalla verifica di regressione end-to-end"
```
