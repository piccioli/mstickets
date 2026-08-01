<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

beforeEach(function (): void {
    $this->useSqliteLegacyConnection();
    Storage::fake('import-reports');
});

function insertLegacyValidateUser(int $id): void
{
    DB::connection('legacy')->table('users')->insert(['id' => $id]);
}

function insertLegacyValidateStory(int $id, ?float $hours = null): void
{
    DB::connection('legacy')->table('stories')->insert(['id' => $id, 'hours' => $hours]);
}

function insertTicket(array $attributes = []): int
{
    return DB::table('tickets')->insertGetId(array_merge([
        'title' => 'Ticket di test',
        'status' => 'new',
        'status_changed_at' => now(),
        'type' => 'helpdesk',
        'priority' => 'low',
        'worked_minutes' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ], $attributes));
}

function createLegacyUsersTable(): void
{
    Schema::connection('legacy')->create('users', function (Blueprint $table): void {
        $table->id();
    });
}

function createLegacyStoriesTable(): void
{
    Schema::connection('legacy')->create('stories', function (Blueprint $table): void {
        $table->id();
        $table->float('hours')->nullable();
    });
}

test('succeeds and reports OK when v1/v2 counts and integrity checks match', function (): void {
    createLegacyUsersTable();
    createLegacyStoriesTable();

    insertLegacyValidateUser(1);
    insertLegacyValidateUser(2);
    $user = User::factory()->create();
    User::factory()->create();

    insertLegacyValidateStory(1, hours: 10.0);
    insertTicket(['requester_id' => $user->id, 'worked_minutes' => 600]);

    $this->artisan('v1:validate')
        ->expectsOutputToContain('Validazione superata.')
        ->assertSuccessful()
        ->run();

    $files = Storage::disk('import-reports')->files('import');
    expect($files)->toHaveCount(1);

    $report = Storage::disk('import-reports')->get($files[0]);

    expect($report)->toContain('Report v1:validate')
        ->and($report)->toContain('| users | 2 | 2 | 0 | OK |')
        ->and($report)->toContain('0 valori fuori catalogo')
        ->and($report)->toContain('Nessuna esecuzione di v1:import trovata');
});

test('fails when an id-preserved entity count does not match', function (): void {
    createLegacyUsersTable();

    insertLegacyValidateUser(1);
    insertLegacyValidateUser(2);
    insertLegacyValidateUser(3);
    User::factory()->count(2)->create();

    $this->artisan('v1:validate')
        ->expectsOutputToContain('Validazione FALLITA')
        ->assertFailed()
        ->run();

    $files = Storage::disk('import-reports')->files('import');
    $report = Storage::disk('import-reports')->get($files[0]);

    expect($report)->toContain('| users | 3 | 2 | 1 | MISMATCH |');
});

test('fails when a ticket has an out-of-catalog enum value', function (): void {
    $user = User::factory()->create();
    $ticketId = insertTicket(['requester_id' => $user->id]);
    DB::table('tickets')->where('id', $ticketId)->update(['type' => 'not-a-real-type']);

    $this->artisan('v1:validate')
        ->expectsOutputToContain('Validazione FALLITA')
        ->assertFailed()
        ->run();

    $files = Storage::disk('import-reports')->files('import');
    $report = Storage::disk('import-reports')->get($files[0]);

    expect($report)->toContain('1 valori fuori catalogo (not-a-real-type)');
});

test('fails when a ticket has no requester', function (): void {
    insertTicket(['requester_id' => null]);

    $this->artisan('v1:validate')->assertFailed()->run();

    $files = Storage::disk('import-reports')->files('import');
    $report = Storage::disk('import-reports')->get($files[0]);

    expect($report)->toContain('Ticket senza richiedente (requester_id null): 1');
});

test('lists the warnings collected by the latest v1:import run as compromessi applicati', function (): void {
    ImportRun::create([
        'started_at' => now()->subMinute(),
        'finished_at' => now(),
        'dump_label' => 'test-dump',
        'stages' => [
            'users' => ['read' => 2, 'created' => 2, 'updated' => 0, 'skipped' => 0, 'warnings' => ['Email duplicata a meno del case "a@example.test": 2 utenti v1 con id [1, 2].']],
            'tags' => ['read' => 1, 'created' => 1, 'updated' => 0, 'skipped' => 0, 'warnings' => []],
        ],
        'status' => ImportRunStatus::Completed,
        'is_dry_run' => false,
    ]);

    $this->artisan('v1:validate')->assertSuccessful()->run();

    $files = Storage::disk('import-reports')->files('import');
    $report = Storage::disk('import-reports')->get($files[0]);

    expect($report)->toContain('**users**:')
        ->and($report)->toContain('Email duplicata a meno del case')
        ->and($report)->not->toContain('**tags**:');
});

test('reports the worked hours deviation beyond the 5% tolerance without failing the command', function (): void {
    createLegacyStoriesTable();

    $user = User::factory()->create();
    insertLegacyValidateStory(1, hours: 10.0);
    insertTicket(['id' => 1, 'requester_id' => $user->id, 'worked_minutes' => 12 * 60]);

    $this->artisan('v1:validate')->assertSuccessful()->run();

    $files = Storage::disk('import-reports')->files('import');
    $report = Storage::disk('import-reports')->get($files[0]);

    expect($report)->toContain('Oltre tolleranza: 1')
        ->and($report)->toContain('ticket #1: v1 10h, v2 12h, scostamento 20%');
});
