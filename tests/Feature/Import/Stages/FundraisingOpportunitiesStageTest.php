<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\FundraisingOpportunitiesStage;
use App\Import\Stages\ImportContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function fundraisingOpportunitiesStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
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

beforeEach(function (): void {
    $this->useSqliteLegacyConnection();

    Schema::connection('legacy')->create('fundraising_opportunities', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('official_url')->nullable();
        $table->decimal('endowment_fund', 15, 2)->nullable();
        $table->date('deadline');
        $table->string('program_name')->nullable();
        $table->string('sponsor')->nullable();
        $table->decimal('cofinancing_quota', 5, 2)->nullable();
        $table->decimal('max_contribution', 15, 2)->nullable();
        $table->string('territorial_scope')->default('national');
        $table->text('beneficiary_requirements')->nullable();
        $table->text('lead_requirements')->nullable();
        $table->unsignedBigInteger('created_by');
        $table->unsignedBigInteger('responsible_user_id');
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
});

function insertLegacyFundraisingOpportunity(array $attributes = []): void
{
    DB::connection('legacy')->table('fundraising_opportunities')->insert(array_merge([
        'id' => 1,
        'name' => 'Bando montagna 2026',
        'official_url' => 'https://example.org/bando',
        'endowment_fund' => '150000.00',
        'deadline' => '2026-12-31',
        'program_name' => 'Programma Alpi',
        'sponsor' => 'Regione',
        'cofinancing_quota' => '20.00',
        'max_contribution' => '50000.00',
        'territorial_scope' => 'regional',
        'beneficiary_requirements' => 'Requisiti beneficiario',
        'lead_requirements' => 'Requisiti capofila',
        'created_by' => 1,
        'responsible_user_id' => 1,
        'created_at' => '2026-01-01 10:00:00',
        'updated_at' => '2026-01-01 10:00:00',
    ], $attributes));
}

test('imports a v1 fundraising opportunity into v2 with the id preserved and columns mapped', function (): void {
    User::factory()->create(['id' => 1]);
    insertLegacyFundraisingOpportunity(['id' => 5, 'name' => 'Bando montagna 2026', 'territorial_scope' => 'regional']);

    $result = (new FundraisingOpportunitiesStage)->run(fundraisingOpportunitiesStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1)
        ->and($result->updated)->toBe(0)
        ->and($result->skipped)->toBe(0);

    $opportunity = DB::table('fundraising_opportunities')->where('id', 5)->first();

    expect($opportunity)->not->toBeNull()
        ->and($opportunity->name)->toBe('Bando montagna 2026')
        ->and($opportunity->territorial_scope)->toBe('regional')
        ->and((int) $opportunity->created_by)->toBe(1)
        ->and((int) $opportunity->responsible_user_id)->toBe(1)
        ->and($opportunity->evaluated_by)->toBeNull()
        ->and($opportunity->evaluated_at)->toBeNull()
        ->and($opportunity->evaluation_positive_total)->toBeNull()
        ->and($opportunity->evaluation_negative_total)->toBeNull()
        ->and($opportunity->evaluation_total)->toBeNull();
});

test('an opportunity whose created_by/responsible_user_id does not exist in v2 is skipped and reported, not crashed', function (): void {
    insertLegacyFundraisingOpportunity(['id' => 1, 'created_by' => 999, 'responsible_user_id' => 999]);

    $result = (new FundraisingOpportunitiesStage)->run(fundraisingOpportunitiesStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings)->not->toBeEmpty()
        ->and(DB::table('fundraising_opportunities')->count())->toBe(0);
});

test('dry-run does not write any row to the destination fundraising_opportunities table', function (): void {
    User::factory()->create(['id' => 1]);
    insertLegacyFundraisingOpportunity(['id' => 1]);

    $before = DB::table('fundraising_opportunities')->count();

    $result = (new FundraisingOpportunitiesStage)->run(fundraisingOpportunitiesStageContext(dryRun: true));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and(DB::table('fundraising_opportunities')->count())->toBe($before);
});

test('re-running the stage on the same dump is idempotent: second run only skips', function (): void {
    User::factory()->create(['id' => 1]);
    insertLegacyFundraisingOpportunity(['id' => 1]);
    insertLegacyFundraisingOpportunity(['id' => 2, 'name' => 'Bando europeo']);

    $stage = new FundraisingOpportunitiesStage;
    $first = $stage->run(fundraisingOpportunitiesStageContext());
    $second = $stage->run(fundraisingOpportunitiesStageContext());

    expect($first->created)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and($second->updated)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('fundraising_opportunities')->count())->toBe(2);
});

test('a changed v1 row is applied as an update, not a duplicate insert', function (): void {
    User::factory()->create(['id' => 1]);
    insertLegacyFundraisingOpportunity(['id' => 1, 'name' => 'Bando montagna 2026']);
    (new FundraisingOpportunitiesStage)->run(fundraisingOpportunitiesStageContext());

    DB::connection('legacy')->table('fundraising_opportunities')->where('id', 1)->update(['name' => 'Bando montagna 2027']);
    $result = (new FundraisingOpportunitiesStage)->run(fundraisingOpportunitiesStageContext());

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(1)
        ->and($result->skipped)->toBe(0)
        ->and(DB::table('fundraising_opportunities')->count())->toBe(1)
        ->and(DB::table('fundraising_opportunities')->where('id', 1)->value('name'))->toBe('Bando montagna 2027');
});

test('a re-run never overwrites evaluated_by/evaluated_at/evaluation totals set by real v2 usage after import', function (): void {
    User::factory()->create(['id' => 1]);
    User::factory()->create(['id' => 2]);
    insertLegacyFundraisingOpportunity(['id' => 1, 'name' => 'Bando montagna 2026']);
    (new FundraisingOpportunitiesStage)->run(fundraisingOpportunitiesStageContext());

    DB::table('fundraising_opportunities')->where('id', 1)->update([
        'evaluated_by' => 2,
        'evaluated_at' => now(),
        'evaluation_positive_total' => 10,
        'evaluation_negative_total' => 2,
        'evaluation_total' => 8,
    ]);

    DB::connection('legacy')->table('fundraising_opportunities')->where('id', 1)->update(['name' => 'Bando montagna 2027 (rivisto)']);
    $result = (new FundraisingOpportunitiesStage)->run(fundraisingOpportunitiesStageContext());

    expect($result->updated)->toBe(1);

    $opportunity = DB::table('fundraising_opportunities')->where('id', 1)->first();

    expect($opportunity->name)->toBe('Bando montagna 2027 (rivisto)')
        ->and((int) $opportunity->evaluated_by)->toBe(2)
        ->and($opportunity->evaluated_at)->not->toBeNull()
        ->and((int) $opportunity->evaluation_positive_total)->toBe(10)
        ->and((int) $opportunity->evaluation_negative_total)->toBe(2)
        ->and((int) $opportunity->evaluation_total)->toBe(8);
});

test('--limit caps the number of v1 rows read', function (): void {
    User::factory()->create(['id' => 1]);
    insertLegacyFundraisingOpportunity(['id' => 1]);
    insertLegacyFundraisingOpportunity(['id' => 2, 'name' => 'Bando europeo']);

    $result = (new FundraisingOpportunitiesStage)->run(fundraisingOpportunitiesStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});
