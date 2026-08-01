<?php

declare(strict_types=1);

use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\ImportContext;
use App\Import\Stages\UsersStage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function usersStageContext(bool $dryRun = false, ?int $limit = null, bool $anonymize = false): ImportContext
{
    $importRun = ImportRun::create([
        'started_at' => now(),
        'dump_label' => 'test-dump',
        'stages' => [],
        'status' => ImportRunStatus::Running,
        'is_dry_run' => $dryRun,
    ]);

    return new ImportContext(importRun: $importRun, dryRun: $dryRun, limit: $limit, anonymize: $anonymize);
}

beforeEach(function (): void {
    $this->useSqliteLegacyConnection();

    Schema::connection('legacy')->create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('remember_token')->nullable();
        $table->string('activity_report_language', 2)->default('it');
        $table->string('google_drive_url')->nullable();
        $table->string('google_drive_budget_url')->nullable();
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
});

function insertLegacyUser(array $attributes = []): void
{
    DB::connection('legacy')->table('users')->insert(array_merge([
        'name' => 'Mario Rossi',
        'email' => 'mario@example.test',
        'email_verified_at' => null,
        'password' => 'bcrypt-hash',
        'remember_token' => null,
        'activity_report_language' => 'it',
        'google_drive_url' => null,
        'google_drive_budget_url' => null,
        'created_at' => '2026-01-01 10:00:00',
        'updated_at' => '2026-01-01 10:00:00',
    ], $attributes));
}

test('imports v1 users into v2 with the id preserved and columns mapped', function (): void {
    insertLegacyUser([
        'id' => 42,
        'name' => 'Mario Rossi',
        'email' => 'mario@example.test',
        'activity_report_language' => 'en',
        'google_drive_url' => 'https://drive.example/mario',
        'google_drive_budget_url' => 'https://drive.example/mario-budget',
    ]);

    $result = (new UsersStage)->run(usersStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1)
        ->and($result->updated)->toBe(0)
        ->and($result->skipped)->toBe(0);

    $user = DB::table('users')->where('id', 42)->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Mario Rossi')
        ->and($user->email)->toBe('mario@example.test')
        ->and($user->locale)->toBe('en')
        ->and($user->drive_url)->toBe('https://drive.example/mario')
        ->and($user->drive_budget_url)->toBe('https://drive.example/mario-budget');
});

test('dry-run does not write any row to the destination users table', function (): void {
    insertLegacyUser(['id' => 1]);

    $before = DB::table('users')->count();

    $result = (new UsersStage)->run(usersStageContext(dryRun: true));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and(DB::table('users')->count())->toBe($before);
});

test('re-running the stage on the same dump is idempotent: second run only skips', function (): void {
    insertLegacyUser(['id' => 1]);
    insertLegacyUser(['id' => 2, 'email' => 'giulia@example.test']);

    $stage = new UsersStage;
    $first = $stage->run(usersStageContext());
    $second = $stage->run(usersStageContext());

    expect($first->created)->toBe(2)
        ->and($first->updated)->toBe(0)
        ->and($second->created)->toBe(0)
        ->and($second->updated)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('users')->count())->toBe(2);
});

test('a changed v1 row is applied as an update, not a duplicate insert', function (): void {
    insertLegacyUser(['id' => 1, 'name' => 'Mario Rossi']);
    (new UsersStage)->run(usersStageContext());

    DB::connection('legacy')->table('users')->where('id', 1)->update(['name' => 'Mario Rossi Jr.']);
    $result = (new UsersStage)->run(usersStageContext());

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(1)
        ->and($result->skipped)->toBe(0)
        ->and(DB::table('users')->count())->toBe(1)
        ->and(DB::table('users')->where('id', 1)->value('name'))->toBe('Mario Rossi Jr.');
});

test('reports case-insensitive duplicate emails without failing the stage', function (): void {
    insertLegacyUser(['id' => 1, 'email' => 'Mario@Example.test']);
    insertLegacyUser(['id' => 2, 'email' => 'mario@example.test']);

    $result = (new UsersStage)->run(usersStageContext());

    expect($result->created)->toBe(2)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('mario@example.test');
});

test('--limit caps the number of v1 rows read', function (): void {
    insertLegacyUser(['id' => 1]);
    insertLegacyUser(['id' => 2, 'email' => 'giulia@example.test']);

    $result = (new UsersStage)->run(usersStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});

test('--anonymize replaces name and email with a deterministic fake identity on a test domain', function (): void {
    config(['orchestrator.anonymization.mail_test_domains' => ['test.orchestrator.invalid']]);

    insertLegacyUser(['id' => 42, 'name' => 'Mario Rossi', 'email' => 'mario.rossi@clientedavvero.it']);

    (new UsersStage)->run(usersStageContext(anonymize: true));

    $user = DB::table('users')->where('id', 42)->first();

    expect($user->name)->not->toBe('Mario Rossi')
        ->and($user->email)->not->toBe('mario.rossi@clientedavvero.it')
        ->and($user->email)->toEndWith('@test.orchestrator.invalid');
});

test('--anonymize produces the same fake identity for the same v1 id across separate runs, and a different one for a different id', function (): void {
    insertLegacyUser(['id' => 1, 'email' => 'uno@clientedavvero.it']);
    insertLegacyUser(['id' => 2, 'email' => 'due@clientedavvero.it']);

    (new UsersStage)->run(usersStageContext(anonymize: true));
    $firstRunUser1 = DB::table('users')->where('id', 1)->first();
    $firstRunUser2 = DB::table('users')->where('id', 2)->first();

    DB::table('users')->truncate();

    (new UsersStage)->run(usersStageContext(anonymize: true));
    $secondRunUser1 = DB::table('users')->where('id', 1)->first();

    expect($secondRunUser1->name)->toBe($firstRunUser1->name)
        ->and($secondRunUser1->email)->toBe($firstRunUser1->email)
        ->and($firstRunUser1->email)->not->toBe($firstRunUser2->email)
        ->and($firstRunUser1->name)->not->toBe($firstRunUser2->name);
});

test('--anonymize keeps re-running the stage idempotent (relations untouched, only surface values replaced)', function (): void {
    insertLegacyUser(['id' => 1]);

    $stage = new UsersStage;
    $first = $stage->run(usersStageContext(anonymize: true));
    $second = $stage->run(usersStageContext(anonymize: true));

    expect($first->created)->toBe(1)
        ->and($second->created)->toBe(0)
        ->and($second->updated)->toBe(0)
        ->and($second->skipped)->toBe(1);
});
