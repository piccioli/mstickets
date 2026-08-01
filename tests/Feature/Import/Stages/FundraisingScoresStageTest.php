<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\FundraisingScoresStage;
use App\Import\Stages\ImportContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function fundraisingScoresStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
{
    $importRun = ImportRun::create([
        'started_at' => now(),
        'dump_label' => 'test-dump',
        'stages' => [],
        'status' => ImportRunStatus::Running,
        'is_dry_run' => $dryRun,
    ]);

    return new ImportContext(importRun: $importRun, dryRun: $dryRun, limit: $limit);
}

function createV2FundraisingOpportunity(int $id): void
{
    DB::table('fundraising_opportunities')->insert([
        'id' => $id,
        'name' => "Bando #{$id}",
        'deadline' => '2026-12-31',
        'territorial_scope' => 'national',
        'created_by' => 1,
        'responsible_user_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

beforeEach(function (): void {
    $this->useSqliteLegacyConnection();

    // Users needed as FK targets for the v2 fundraising_opportunities fixture rows.
    User::factory()->create(['id' => 1]);
});

test('a v1 evaluation_*_score column with a value in range becomes a fundraising_evaluation_scores row', function (): void {
    Schema::connection('legacy')->create('fundraising_opportunities', function (Blueprint $table): void {
        $table->id();
        $table->smallInteger('evaluation_criterion_a_score')->nullable();
        $table->text('evaluation_criterion_a_description')->nullable();
    });
    DB::connection('legacy')->table('fundraising_opportunities')->insert([
        'id' => 1,
        'evaluation_criterion_a_score' => 4,
        'evaluation_criterion_a_description' => 'Coerente col bando',
    ]);
    DB::table('fundraising_opportunities')->insert([
        'id' => 1,
        'name' => 'Bando #1',
        'deadline' => '2026-12-31',
        'territorial_scope' => 'national',
        'created_by' => 1,
        'responsible_user_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $result = (new FundraisingScoresStage)->run(fundraisingScoresStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1)
        ->and($result->skipped)->toBe(0)
        ->and($result->warnings)->toBeEmpty();

    $score = DB::table('fundraising_evaluation_scores')->where('fundraising_opportunity_id', 1)->first();

    expect($score)->not->toBeNull()
        ->and($score->criterion_key)->toBe('criterion_a')
        ->and((int) $score->score)->toBe(4)
        ->and($score->notes)->toBe('Coerente col bando');
});

test('an out-of-range v1 score is clamped to the criterion catalog range and the clamp is reported', function (): void {
    Schema::connection('legacy')->create('fundraising_opportunities', function (Blueprint $table): void {
        $table->id();
        $table->smallInteger('evaluation_criterion_b_score')->nullable();
        $table->smallInteger('evaluation_risk_finanziari_score')->nullable();
    });
    DB::connection('legacy')->table('fundraising_opportunities')->insert([
        'id' => 1,
        'evaluation_criterion_b_score' => 9,
        'evaluation_risk_finanziari_score' => -9,
    ]);
    createV2FundraisingOpportunity(1);

    $result = (new FundraisingScoresStage)->run(fundraisingScoresStageContext());

    expect($result->read)->toBe(2)
        ->and($result->created)->toBe(2)
        ->and($result->warnings)->not->toBeEmpty();

    $criterionB = DB::table('fundraising_evaluation_scores')
        ->where('fundraising_opportunity_id', 1)->where('criterion_key', 'criterion_b')->first();
    $riskFinanziari = DB::table('fundraising_evaluation_scores')
        ->where('fundraising_opportunity_id', 1)->where('criterion_key', 'risk_finanziari')->first();

    expect((int) $criterionB->score)->toBe(5)
        ->and((int) $riskFinanziari->score)->toBe(-3);
});

test('a null v1 evaluation_* column produces no row', function (): void {
    Schema::connection('legacy')->create('fundraising_opportunities', function (Blueprint $table): void {
        $table->id();
        $table->smallInteger('evaluation_base_coerenza_bando_score')->nullable();
    });
    DB::connection('legacy')->table('fundraising_opportunities')->insert([
        'id' => 1,
        'evaluation_base_coerenza_bando_score' => null,
    ]);
    createV2FundraisingOpportunity(1);

    $result = (new FundraisingScoresStage)->run(fundraisingScoresStageContext());

    expect($result->read)->toBe(0)
        ->and($result->created)->toBe(0)
        ->and(DB::table('fundraising_evaluation_scores')->count())->toBe(0);
});

test('a v1 schema with no evaluation_* column at all produces zero rows and an explicit warning, matching the real production dump', function (): void {
    Schema::connection('legacy')->create('fundraising_opportunities', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
    });
    DB::connection('legacy')->table('fundraising_opportunities')->insert(['id' => 1, 'name' => 'Bando #1']);
    createV2FundraisingOpportunity(1);

    $result = (new FundraisingScoresStage)->run(fundraisingScoresStageContext());

    expect($result->read)->toBe(0)
        ->and($result->created)->toBe(0)
        ->and($result->warnings)->not->toBeEmpty()
        ->and(DB::table('fundraising_evaluation_scores')->count())->toBe(0);
});

test('an opportunity referenced by v1 evaluation columns but absent from v2 is skipped, not crashed', function (): void {
    Schema::connection('legacy')->create('fundraising_opportunities', function (Blueprint $table): void {
        $table->id();
        $table->smallInteger('evaluation_criterion_a_score')->nullable();
    });
    DB::connection('legacy')->table('fundraising_opportunities')->insert([
        'id' => 999,
        'evaluation_criterion_a_score' => 3,
    ]);

    $result = (new FundraisingScoresStage)->run(fundraisingScoresStageContext());

    expect($result->created)->toBe(0)
        ->and($result->warnings)->not->toBeEmpty()
        ->and(DB::table('fundraising_evaluation_scores')->count())->toBe(0);
});

test('dry-run does not write any row to the destination fundraising_evaluation_scores table', function (): void {
    Schema::connection('legacy')->create('fundraising_opportunities', function (Blueprint $table): void {
        $table->id();
        $table->smallInteger('evaluation_criterion_a_score')->nullable();
    });
    DB::connection('legacy')->table('fundraising_opportunities')->insert(['id' => 1, 'evaluation_criterion_a_score' => 3]);
    createV2FundraisingOpportunity(1);

    $result = (new FundraisingScoresStage)->run(fundraisingScoresStageContext(dryRun: true));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and(DB::table('fundraising_evaluation_scores')->count())->toBe(0);
});

test('re-running the stage on the same dump is idempotent: second run only skips', function (): void {
    Schema::connection('legacy')->create('fundraising_opportunities', function (Blueprint $table): void {
        $table->id();
        $table->smallInteger('evaluation_criterion_a_score')->nullable();
        $table->smallInteger('evaluation_criterion_b_score')->nullable();
    });
    DB::connection('legacy')->table('fundraising_opportunities')->insert([
        'id' => 1,
        'evaluation_criterion_a_score' => 3,
        'evaluation_criterion_b_score' => 2,
    ]);
    createV2FundraisingOpportunity(1);

    $stage = new FundraisingScoresStage;
    $first = $stage->run(fundraisingScoresStageContext());
    $second = $stage->run(fundraisingScoresStageContext());

    expect($first->created)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('fundraising_evaluation_scores')->count())->toBe(2);
});

test('--limit caps the number of v1 opportunities scanned', function (): void {
    Schema::connection('legacy')->create('fundraising_opportunities', function (Blueprint $table): void {
        $table->id();
        $table->smallInteger('evaluation_criterion_a_score')->nullable();
    });
    DB::connection('legacy')->table('fundraising_opportunities')->insert(['id' => 1, 'evaluation_criterion_a_score' => 3]);
    DB::connection('legacy')->table('fundraising_opportunities')->insert(['id' => 2, 'evaluation_criterion_a_score' => 4]);
    createV2FundraisingOpportunity(1);
    createV2FundraisingOpportunity(2);

    $result = (new FundraisingScoresStage)->run(fundraisingScoresStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});
