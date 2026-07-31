<?php

declare(strict_types=1);

use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\ImportContext;
use App\Import\Stages\TicketTagsStage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function ticketTagsStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
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

    Schema::connection('legacy')->create('taggables', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tag_id');
        $table->string('taggable_type');
        $table->unsignedBigInteger('taggable_id');
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
});

function insertLegacyTaggable(int $tagId, int $taggableId, string $taggableType = 'App\\Models\\Story'): void
{
    DB::connection('legacy')->table('taggables')->insert([
        'tag_id' => $tagId,
        'taggable_type' => $taggableType,
        'taggable_id' => $taggableId,
        'created_at' => '2026-01-01 10:00:00',
        'updated_at' => '2026-01-01 10:00:00',
    ]);
}

function createV2TicketForTags(int $id): void
{
    DB::table('tickets')->insert([
        'id' => $id,
        'title' => "Ticket {$id}",
        'status' => 'new',
        'status_changed_at' => now(),
        'type' => 'helpdesk',
        'priority' => 'low',
        'worked_minutes' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function createV2TagForTicketTags(int $id): void
{
    DB::table('tags')->insert([
        'id' => $id,
        'name' => "Tag {$id}",
        'slug' => "tag-{$id}",
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('imports the v1 ticket<->tag pivot into v2, ignoring the Documentation side', function (): void {
    createV2TicketForTags(1);
    createV2TagForTicketTags(16);
    insertLegacyTaggable(16, 1, 'App\\Models\\Story');
    insertLegacyTaggable(16, 99, 'App\\Models\\Documentation');

    $result = (new TicketTagsStage)->run(ticketTagsStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1)
        ->and($result->skipped)->toBe(0)
        ->and($result->warnings)->toBe([]);

    expect(DB::table('ticket_tag')->where('ticket_id', 1)->where('tag_id', 16)->exists())->toBeTrue();
});

test('dry-run does not write any pivot row', function (): void {
    createV2TicketForTags(1);
    createV2TagForTicketTags(16);
    insertLegacyTaggable(16, 1);

    $result = (new TicketTagsStage)->run(ticketTagsStageContext(dryRun: true));

    expect($result->created)->toBe(0)
        ->and(DB::table('ticket_tag')->count())->toBe(0);
});

test('re-running the stage on the same dump is idempotent: second run only skips', function (): void {
    createV2TicketForTags(1);
    createV2TagForTicketTags(16);
    createV2TagForTicketTags(2);
    insertLegacyTaggable(16, 1);
    insertLegacyTaggable(2, 1);

    $stage = new TicketTagsStage;
    $first = $stage->run(ticketTagsStageContext());
    $second = $stage->run(ticketTagsStageContext());

    expect($first->created)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('ticket_tag')->count())->toBe(2);
});

test('a tag link referencing a non-existent v2 ticket is reported, not crashed', function (): void {
    createV2TagForTicketTags(16);
    insertLegacyTaggable(16, 999);

    $result = (new TicketTagsStage)->run(ticketTagsStageContext());

    expect($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('ticket inesistente')
        ->and(DB::table('ticket_tag')->count())->toBe(0);
});

test('a tag link referencing a non-existent v2 tag is reported, not crashed', function (): void {
    createV2TicketForTags(1);
    insertLegacyTaggable(999, 1);

    $result = (new TicketTagsStage)->run(ticketTagsStageContext());

    expect($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('tag inesistente')
        ->and(DB::table('ticket_tag')->count())->toBe(0);
});

test('--limit caps the number of v1 rows read', function (): void {
    createV2TicketForTags(1);
    createV2TagForTicketTags(16);
    createV2TagForTicketTags(2);
    insertLegacyTaggable(16, 1);
    insertLegacyTaggable(2, 1);

    $result = (new TicketTagsStage)->run(ticketTagsStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});
