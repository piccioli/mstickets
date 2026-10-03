<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiRuntsPresenceStatus;
use App\Domain\CaiDirectory\Export\CaiSnapshotExporter;
use App\Domain\CaiDirectory\Import\CaiDatapackImporter;
use App\Domain\CaiDirectory\Models\CaiBoardMember;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function seedSnapshotImportRows(): void
{
    $section = CaiSection::create([
        'codice_cai' => '9226005', 'name' => 'CAI Carrara', 'email' => 'carrara@example.com', 'region' => 'TOSCANA',
        'latitude' => 44.0775, 'runts_presence_status' => CaiRuntsPresenceStatus::Registered,
        'runts_presence_checked_at' => '2026-09-10 08:30:00', 'cai_last_synced_at' => '2026-09-09 10:00:00',
    ]);
    CaiSection::create(['codice_cai' => '9258001', 'name' => 'CAI Roma', 'region' => 'LAZIO']);
    CaiSubsection::create(['cai_codice' => '9226105', 'cai_section_id' => $section->codice_cai, 'name' => 'Sottosezione', 'members_count' => 12]);
    CaiRuntsRegistration::create([
        'id_runts' => '1234', 'cai_section_id' => $section->codice_cai, 'name' => 'CAI Carrara ETS',
        'registration_date' => '2022-03-04', 'runts_last_synced_at' => '2026-09-08 12:00:00',
    ]);
    CaiFinancialStatement::create(['cai_runts_registration_id' => '1234', 'year' => 2024, 'total_expenses' => 1234.5, 'total_assets' => 99]);
    CaiFinancialStatement::create(['cai_section_id' => '9258001', 'year' => 2025, 'net_result' => -10]);
    CaiBoardMember::create(['cai_runts_registration_id' => '1234', 'role' => 'Presidente', 'full_name' => 'Mario Rossi', 'valid_from' => '2023-01-01']);
}

function wipeCaiRows(): void
{
    CaiBoardMember::query()->delete();
    CaiFinancialStatement::query()->delete();
    CaiRuntsRegistration::query()->delete();
    CaiSubsection::query()->delete();
    CaiSection::query()->delete();
}

/** Datapack con tabelle legacy vuote + snapshot esportato dal DB corrente (se `$withSnapshot`). */
function makeSnapshotImportDatapack(bool $withSnapshot = true): string
{
    $sqlite = sys_get_temp_dir().'/snap-import-'.bin2hex(random_bytes(4)).'.sqlite';
    $pdo = new PDO('sqlite:'.$sqlite);
    $pdo->exec('CREATE TABLE sezioni_cai (codice_cai TEXT)');
    $pdo->exec('CREATE TABLE sottosezioni_cai (cai_codice TEXT, cai_sezione_codice TEXT)');
    $pdo->exec('CREATE TABLE enti (id_runts TEXT)');
    $pdo->exec('CREATE TABLE bilanci (id INTEGER)');
    $pdo->exec('CREATE TABLE cariche_sociali (id INTEGER)');
    $pdo->exec('CREATE TABLE allegati (id INTEGER)');

    if ($withSnapshot) {
        app(CaiSnapshotExporter::class)->export($sqlite);
    }

    return $sqlite;
}

function snapshotImport(string $sqlite, bool $dryRun = false): array
{
    return app(CaiDatapackImporter::class)->import($sqlite, $dryRun);
}

test('restores every snapshot table on an empty database with count parity', function (): void {
    seedSnapshotImportRows();
    $sqlite = makeSnapshotImportDatapack();
    wipeCaiRows();

    $results = snapshotImport($sqlite);

    expect(CaiSection::query()->count())->toBe(2)
        ->and(CaiSubsection::query()->count())->toBe(1)
        ->and(CaiRuntsRegistration::query()->count())->toBe(1)
        ->and(CaiFinancialStatement::query()->count())->toBe(2)
        ->and(CaiBoardMember::query()->count())->toBe(1)
        ->and($results['snapshot_sezioni']->created)->toBe(3)
        ->and($results['snapshot_registrazioni']->created)->toBe(1)
        ->and($results['snapshot_bilanci']->created)->toBe(2)
        ->and($results['snapshot_cariche']->created)->toBe(1);

    $section = CaiSection::query()->findOrFail('9226005');
    expect($section->runts_presence_status)->toBe(CaiRuntsPresenceStatus::Registered)
        ->and($section->runts_presence_checked_at->format('Y-m-d H:i:s'))->toBe('2026-09-10 08:30:00')
        ->and($section->cai_last_synced_at->format('Y-m-d H:i:s'))->toBe('2026-09-09 10:00:00')
        ->and((float) $section->latitude)->toBe(44.0775);
    expect(CaiRuntsRegistration::query()->findOrFail('1234')->runts_last_synced_at->format('Y-m-d H:i:s'))->toBe('2026-09-08 12:00:00');
});

test('statements are restored by registration and by section parent, exactly one parent each', function (): void {
    seedSnapshotImportRows();
    $sqlite = makeSnapshotImportDatapack();
    wipeCaiRows();

    snapshotImport($sqlite);

    $byRegistration = CaiFinancialStatement::query()->where('cai_runts_registration_id', '1234')->sole();
    $bySection = CaiFinancialStatement::query()->where('cai_section_id', '9258001')->sole();

    expect($byRegistration->cai_section_id)->toBeNull()
        ->and((float) $byRegistration->total_expenses)->toBe(1234.5)
        ->and($bySection->cai_runts_registration_id)->toBeNull()
        ->and((float) $bySection->net_result)->toBe(-10.0);
});

test('a second run creates and updates nothing', function (): void {
    seedSnapshotImportRows();
    $sqlite = makeSnapshotImportDatapack();
    wipeCaiRows();

    snapshotImport($sqlite);
    $second = snapshotImport($sqlite);

    foreach (['snapshot_sezioni', 'snapshot_registrazioni', 'snapshot_bilanci', 'snapshot_cariche'] as $key) {
        expect($second[$key]->created)->toBe(0, $key)->and($second[$key]->updated)->toBe(0, $key);
    }

    expect(CaiSection::query()->count())->toBe(2)->and(CaiFinancialStatement::query()->count())->toBe(2);
});

test('changed snapshot values are updated, user_id is never overwritten', function (): void {
    $user = User::factory()->create(['email' => 'legacy@example.com']);
    seedSnapshotImportRows();
    $sqlite = makeSnapshotImportDatapack();
    wipeCaiRows();

    // Sezione già collegata dall'import legacy a un utente, con un nome vecchio.
    CaiSection::create(['codice_cai' => '9226005', 'name' => 'Vecchio nome', 'region' => 'TOSCANA', 'user_id' => $user->id]);

    $results = snapshotImport($sqlite);

    $section = CaiSection::query()->findOrFail('9226005');
    expect($section->name)->toBe('CAI Carrara')
        ->and($section->user_id)->toBe($user->id)
        ->and($results['snapshot_sezioni']->updated)->toBe(1)
        ->and($results['snapshot_sezioni']->created)->toBe(2);
});

test('new sections get user_id rebuilt by email', function (): void {
    seedSnapshotImportRows();
    $sqlite = makeSnapshotImportDatapack();
    wipeCaiRows();
    $user = User::factory()->create(['email' => 'Carrara@Example.com']);

    snapshotImport($sqlite);

    expect(CaiSection::query()->findOrFail('9226005')->user_id)->toBe($user->id)
        ->and(CaiSection::query()->findOrFail('9258001')->user_id)->toBeNull();
});

test('rows whose parent does not exist are skipped with a warning and never violate a foreign key', function (): void {
    seedSnapshotImportRows();
    $sqlite = makeSnapshotImportDatapack();
    wipeCaiRows();

    $pdo = new PDO('sqlite:'.$sqlite);
    $pdo->exec("INSERT INTO snap_cai_subsections (cai_codice, cai_section_id, name) VALUES ('X1', 'NOPE', 'Orfana')");
    $pdo->exec("INSERT INTO snap_cai_runts_registrations (id_runts, cai_section_id, name) VALUES ('777', 'NOPE', 'Orfano')");
    $pdo->exec("INSERT INTO snap_cai_financial_statements (cai_runts_registration_id, year) VALUES ('777', 2024)");
    $pdo->exec("INSERT INTO snap_cai_financial_statements (cai_section_id, year) VALUES ('NOPE', 2024)");
    $pdo->exec("INSERT INTO snap_cai_financial_statements (cai_section_id, cai_runts_registration_id, year) VALUES ('9226005', '1234', 2023)");
    $pdo->exec("INSERT INTO snap_cai_board_members (cai_runts_registration_id, role, full_name) VALUES ('777', 'Presidente', 'Orfano')");

    $results = snapshotImport($sqlite);

    expect(CaiSubsection::query()->find('X1'))->toBeNull()
        ->and(CaiRuntsRegistration::query()->find('777'))->toBeNull()
        ->and(CaiFinancialStatement::query()->count())->toBe(2)
        ->and(CaiBoardMember::query()->count())->toBe(1)
        ->and($results['snapshot_sezioni']->warnings)->toHaveCount(1)
        ->and($results['snapshot_bilanci']->warnings[0])->toContain('3 righe');
});

test('dry-run writes nothing and reports the same counts as the real run', function (): void {
    seedSnapshotImportRows();
    $sqlite = makeSnapshotImportDatapack();
    wipeCaiRows();

    $dry = snapshotImport($sqlite, dryRun: true);

    expect(CaiSection::query()->count())->toBe(0)->and(CaiFinancialStatement::query()->count())->toBe(0);

    $real = snapshotImport($sqlite);

    foreach (['snapshot_sezioni', 'snapshot_registrazioni', 'snapshot_bilanci', 'snapshot_cariche'] as $key) {
        expect([$dry[$key]->read, $dry[$key]->created, $dry[$key]->updated, $dry[$key]->skipped])
            ->toBe([$real[$key]->read, $real[$key]->created, $real[$key]->updated, $real[$key]->skipped], $key);
    }
});

test('a datapack without snap tables is unaffected and a scoped import skips the snapshot', function (): void {
    seedSnapshotImportRows();
    $legacyOnly = makeSnapshotImportDatapack(withSnapshot: false);
    $withSnapshot = makeSnapshotImportDatapack();
    wipeCaiRows();

    expect(array_keys(snapshotImport($legacyOnly)))->not->toContain('snapshot_sezioni')
        ->and(CaiSection::query()->count())->toBe(0);

    $scoped = app(CaiDatapackImporter::class)->import($withSnapshot, false, onlyCaiSectionCode: '9226005');
    expect(array_keys($scoped))->not->toContain('snapshot_sezioni')->and(CaiSection::query()->count())->toBe(0);
});

test('the import command prints the snapshot summary rows and queues nothing', function (): void {
    Queue::fake();
    seedSnapshotImportRows();
    $sqlite = makeSnapshotImportDatapack();
    wipeCaiRows();

    $this->artisan('cai:import-datapack', ['--path' => $sqlite])
        ->expectsOutputToContain('snapshot_sezioni')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});
