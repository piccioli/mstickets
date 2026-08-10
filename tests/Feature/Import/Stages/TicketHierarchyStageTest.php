<?php

declare(strict_types=1);

use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\ImportContext;
use App\Import\Stages\TicketHierarchyStage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function ticketHierarchyStageContext(bool $dryRun = false): ImportContext
{
    $importRun = ImportRun::create([
        'started_at' => now(),
        'dump_label' => 'test-dump',
        'stages' => [],
        'status' => ImportRunStatus::Running,
        'is_dry_run' => $dryRun,
    ]);

    return new ImportContext(importRun: $importRun, dryRun: $dryRun);
}

beforeEach(function (): void {
    $this->useSqliteLegacyConnection();

    Schema::connection('legacy')->create('stories', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('parent_id')->nullable();
    });

    Schema::connection('legacy')->create('story_story', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('parent_id');
        $table->unsignedBigInteger('child_id');
    });
});

function insertLegacyStoryForHierarchy(int $id, ?int $parentId = null): void
{
    DB::connection('legacy')->table('stories')->insert(['id' => $id, 'parent_id' => $parentId]);
}

function insertLegacyStoryStory(int $parentId, int $childId): void
{
    DB::connection('legacy')->table('story_story')->insert(['parent_id' => $parentId, 'child_id' => $childId]);
}

function insertTicketForHierarchy(int $id): void
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

test('a coherent one-level hierarchy from stories.parent_id is applied as-is', function (): void {
    insertLegacyStoryForHierarchy(1);
    insertLegacyStoryForHierarchy(2, 1);
    insertTicketForHierarchy(1);
    insertTicketForHierarchy(2);

    $result = (new TicketHierarchyStage)->run(ticketHierarchyStageContext());

    expect($result->read)->toBe(1)
        ->and($result->updated)->toBe(1)
        ->and($result->warnings)->toBe([]);

    expect(DB::table('tickets')->where('id', 2)->value('parent_id'))->toBe(1)
        ->and(DB::table('tickets')->where('id', 1)->value('parent_id'))->toBeNull();
});

test('a story_story row not reflected in stories.parent_id is applied when there is no conflict', function (): void {
    insertLegacyStoryForHierarchy(1);
    insertLegacyStoryForHierarchy(7);
    insertLegacyStoryStory(1, 7);
    insertTicketForHierarchy(1);
    insertTicketForHierarchy(7);

    $result = (new TicketHierarchyStage)->run(ticketHierarchyStageContext());

    expect(DB::table('tickets')->where('id', 7)->value('parent_id'))->toBe(1)
        ->and($result->warnings)->toBe([]);
});

test('a child with a different parent between stories.parent_id and story_story keeps parent_id and is reported', function (): void {
    insertLegacyStoryForHierarchy(10);
    insertLegacyStoryForHierarchy(20);
    insertLegacyStoryForHierarchy(5, 10);
    insertLegacyStoryStory(20, 5);
    insertTicketForHierarchy(10);
    insertTicketForHierarchy(20);
    insertTicketForHierarchy(5);

    $result = (new TicketHierarchyStage)->run(ticketHierarchyStageContext());

    expect(DB::table('tickets')->where('id', 5)->value('parent_id'))->toBe(10)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('1 ticket con padre diverso tra stories.parent_id e story_story');
});

test('a 2+ level hierarchy is flattened onto the topmost ancestor and reported', function (): void {
    insertLegacyStoryForHierarchy(1);
    insertLegacyStoryForHierarchy(2, 1);
    insertLegacyStoryForHierarchy(3, 2);
    insertTicketForHierarchy(1);
    insertTicketForHierarchy(2);
    insertTicketForHierarchy(3);

    $result = (new TicketHierarchyStage)->run(ticketHierarchyStageContext());

    expect(DB::table('tickets')->where('id', 2)->value('parent_id'))->toBe(1)
        ->and(DB::table('tickets')->where('id', 3)->value('parent_id'))->toBe(1)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('1 ticket con una gerarchia a più di un livello');
});

test('a parent reference to a non-existent v2 ticket is nulled out and reported, not crashed', function (): void {
    insertLegacyStoryForHierarchy(1, 999);
    insertTicketForHierarchy(1);

    $result = (new TicketHierarchyStage)->run(ticketHierarchyStageContext());

    expect(DB::table('tickets')->where('id', 1)->value('parent_id'))->toBeNull()
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('1 riferimenti a un ticket padre inesistente in v2');
});

test('dry-run does not write any parent_id', function (): void {
    insertLegacyStoryForHierarchy(1);
    insertLegacyStoryForHierarchy(2, 1);
    insertTicketForHierarchy(1);
    insertTicketForHierarchy(2);

    $result = (new TicketHierarchyStage)->run(ticketHierarchyStageContext(dryRun: true));

    expect($result->read)->toBe(1)
        ->and($result->updated)->toBe(0)
        ->and(DB::table('tickets')->where('id', 2)->value('parent_id'))->toBeNull();
});

test('re-running the stage on the same dump is idempotent: second run only skips', function (): void {
    insertLegacyStoryForHierarchy(1);
    insertLegacyStoryForHierarchy(2, 1);
    insertTicketForHierarchy(1);
    insertTicketForHierarchy(2);

    $stage = new TicketHierarchyStage;
    $first = $stage->run(ticketHierarchyStageContext());
    $second = $stage->run(ticketHierarchyStageContext());

    expect($first->updated)->toBe(1)
        ->and($second->updated)->toBe(0)
        ->and($second->skipped)->toBe(1)
        ->and(DB::table('tickets')->where('id', 2)->value('parent_id'))->toBe(1);
});
