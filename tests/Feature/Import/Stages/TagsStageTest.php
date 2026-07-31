<?php

declare(strict_types=1);

use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\ImportContext;
use App\Import\Stages\TagsStage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function tagsStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
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

    Schema::connection('legacy')->create('tags', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->unsignedBigInteger('taggable_id')->nullable();
        $table->string('taggable_type')->nullable();
        $table->text('description')->nullable();
        $table->decimal('estimate', 8, 2)->nullable();
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
});

function insertLegacyTag(array $attributes = []): void
{
    DB::connection('legacy')->table('tags')->insert(array_merge([
        'name' => 'MS/Amministrazione',
        'taggable_id' => null,
        'taggable_type' => null,
        'description' => null,
        'estimate' => null,
        'created_at' => '2026-01-01 10:00:00',
        'updated_at' => '2026-01-01 10:00:00',
    ], $attributes));
}

function createV2DocumentationPage(int $id): void
{
    DB::table('documentation_pages')->insert([
        'id' => $id,
        'title' => "Documentazione {$id}",
        'slug' => "documentazione-{$id}",
        'body' => 'Corpo',
        'category' => 'customer',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('imports a plain v1 tag (no taggable link) into v2 with the id preserved', function (): void {
    insertLegacyTag(['id' => 1, 'name' => 'MS/Amministrazione', 'estimate' => 12.5]);

    $result = (new TagsStage)->run(tagsStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1)
        ->and($result->updated)->toBe(0)
        ->and($result->skipped)->toBe(0)
        ->and($result->warnings)->toBe([]);

    $tag = DB::table('tags')->where('id', 1)->first();

    expect($tag)->not->toBeNull()
        ->and($tag->name)->toBe('MS/Amministrazione')
        ->and((float) $tag->estimated_hours)->toBe(12.5)
        ->and($tag->documentation_id)->toBeNull()
        ->and($tag->slug)->toBe(Str::slug('MS/Amministrazione'));
});

test('preserves the link to Documentation as an explicit documentation_id foreign key', function (): void {
    createV2DocumentationPage(17);
    insertLegacyTag([
        'id' => 40,
        'name' => 'Documentation: Procedura Operativa',
        'taggable_id' => 17,
        'taggable_type' => 'App\\Models\\Documentation',
    ]);

    $result = (new TagsStage)->run(tagsStageContext());

    expect($result->created)->toBe(1)
        ->and($result->warnings)->toBe([]);

    expect(DB::table('tags')->where('id', 40)->value('documentation_id'))->toBe(17);
});

test('a Documentation link to a non-existent v2 page is collapsed to a plain tag and reported, not crashed', function (): void {
    insertLegacyTag([
        'id' => 39,
        'name' => 'Documentation: cartelle CAI',
        'taggable_id' => 16,
        'taggable_type' => 'App\\Models\\Documentation',
    ]);

    $result = (new TagsStage)->run(tagsStageContext());

    expect($result->created)->toBe(1)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('documentazione #16 collegata inesistente');

    expect(DB::table('tags')->where('id', 39)->value('documentation_id'))->toBeNull();
});

test('a taggable_type other than Documentation is collapsed to a plain tag and counted in a single aggregated warning', function (): void {
    insertLegacyTag(['id' => 1, 'name' => 'Progetto A', 'taggable_id' => 5, 'taggable_type' => 'App\\Models\\Project']);
    insertLegacyTag(['id' => 2, 'name' => 'Cliente B', 'taggable_id' => 9, 'taggable_type' => 'App\\Models\\Customer']);

    $result = (new TagsStage)->run(tagsStageContext());

    expect($result->created)->toBe(2)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('2 tag v1 con taggable_type diverso da Documentation');

    expect(DB::table('tags')->where('id', 1)->value('documentation_id'))->toBeNull()
        ->and(DB::table('tags')->where('id', 2)->value('documentation_id'))->toBeNull();
});

test('generates a unique provisional slug when two v1 tags share the same name', function (): void {
    insertLegacyTag(['id' => 1, 'name' => 'Fundraising']);
    insertLegacyTag(['id' => 2, 'name' => 'Fundraising']);

    (new TagsStage)->run(tagsStageContext());

    expect(DB::table('tags')->where('id', 1)->value('slug'))->toBe('fundraising')
        ->and(DB::table('tags')->where('id', 2)->value('slug'))->toBe('fundraising-2');
});

test('dry-run does not write any row and does not skip the orphan/other-type checks', function (): void {
    insertLegacyTag(['id' => 39, 'taggable_id' => 16, 'taggable_type' => 'App\\Models\\Documentation']);

    $before = DB::table('tags')->count();

    $result = (new TagsStage)->run(tagsStageContext(dryRun: true));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and($result->warnings)->toHaveCount(1)
        ->and(DB::table('tags')->count())->toBe($before);
});

test('re-running the stage on the same dump is idempotent: second run only skips', function (): void {
    createV2DocumentationPage(17);
    insertLegacyTag(['id' => 1, 'name' => 'MS/Amministrazione']);
    insertLegacyTag(['id' => 40, 'name' => 'Documentation: Procedura', 'taggable_id' => 17, 'taggable_type' => 'App\\Models\\Documentation']);

    $stage = new TagsStage;
    $first = $stage->run(tagsStageContext());
    $second = $stage->run(tagsStageContext());

    expect($first->created)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and($second->updated)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('tags')->count())->toBe(2);
});

test('a changed v1 row is applied as an update, not a duplicate insert, and the slug is not regenerated', function (): void {
    insertLegacyTag(['id' => 1, 'name' => 'MS/Amministrazione', 'description' => null]);
    (new TagsStage)->run(tagsStageContext());
    $originalSlug = DB::table('tags')->where('id', 1)->value('slug');

    DB::connection('legacy')->table('tags')->where('id', 1)->update(['description' => 'Nota aggiunta']);
    $result = (new TagsStage)->run(tagsStageContext());

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(1)
        ->and($result->skipped)->toBe(0)
        ->and(DB::table('tags')->count())->toBe(1)
        ->and(DB::table('tags')->where('id', 1)->value('description'))->toBe('Nota aggiunta')
        ->and(DB::table('tags')->where('id', 1)->value('slug'))->toBe($originalSlug);
});

test('--limit caps the number of v1 rows read', function (): void {
    insertLegacyTag(['id' => 1, 'name' => 'Tag uno']);
    insertLegacyTag(['id' => 2, 'name' => 'Tag due']);

    $result = (new TagsStage)->run(tagsStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});
