<?php

declare(strict_types=1);

use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\DocumentationStage;
use App\Import\Stages\ImportContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function documentationStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
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

    Schema::connection('legacy')->create('documentations', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->text('description');
        $table->string('category')->default('customer');
        $table->string('pdf_url')->nullable();
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
});

function insertLegacyDocumentation(array $attributes = []): void
{
    DB::connection('legacy')->table('documentations')->insert(array_merge([
        'name' => 'Servizio di Ticketing',
        'description' => '<p>Informazioni sul servizio</p>',
        'category' => 'customer',
        'pdf_url' => 'https://legacy.example.com/storage/documentations/doc.pdf',
        'created_at' => '2026-01-01 10:00:00',
        'updated_at' => '2026-01-01 10:00:00',
    ], $attributes));
}

test('imports v1 documentations into v2 documentation_pages with the id preserved and columns mapped', function (): void {
    insertLegacyDocumentation([
        'id' => 15,
        'name' => 'Servizio di Ticketing',
        'description' => '<h2>Corpo della pagina</h2>',
        'category' => 'internal',
    ]);

    $result = (new DocumentationStage)->run(documentationStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1)
        ->and($result->updated)->toBe(0)
        ->and($result->skipped)->toBe(0);

    $page = DB::table('documentation_pages')->where('id', 15)->first();

    expect($page)->not->toBeNull()
        ->and($page->title)->toBe('Servizio di Ticketing')
        ->and($page->body)->toBe('<h2>Corpo della pagina</h2>')
        ->and($page->category)->toBe('internal')
        ->and($page->slug)->toBe('servizio-di-ticketing')
        ->and($page->pdf_path)->toBeNull();
});

test('generates a unique provisional slug when two v1 documentations share the same name', function (): void {
    insertLegacyDocumentation(['id' => 11, 'name' => 'Procedura']);
    insertLegacyDocumentation(['id' => 12, 'name' => 'Procedura']);

    (new DocumentationStage)->run(documentationStageContext());

    expect(DB::table('documentation_pages')->where('id', 11)->value('slug'))->toBe('procedura')
        ->and(DB::table('documentation_pages')->where('id', 12)->value('slug'))->toBe('procedura-2');
});

test('dry-run does not write any row to the destination documentation_pages table', function (): void {
    insertLegacyDocumentation(['id' => 15]);

    $before = DB::table('documentation_pages')->count();

    $result = (new DocumentationStage)->run(documentationStageContext(dryRun: true));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and(DB::table('documentation_pages')->count())->toBe($before);
});

test('re-running the stage on the same dump is idempotent: second run only skips', function (): void {
    insertLegacyDocumentation(['id' => 11]);
    insertLegacyDocumentation(['id' => 12, 'name' => 'Altra pagina']);

    $stage = new DocumentationStage;
    $first = $stage->run(documentationStageContext());
    $second = $stage->run(documentationStageContext());

    expect($first->created)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and($second->updated)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('documentation_pages')->count())->toBe(2);
});

test('a changed v1 row is applied as an update, not a duplicate insert, and the slug is not regenerated', function (): void {
    insertLegacyDocumentation(['id' => 15, 'description' => 'Corpo originale']);
    (new DocumentationStage)->run(documentationStageContext());
    $originalSlug = DB::table('documentation_pages')->where('id', 15)->value('slug');

    DB::connection('legacy')->table('documentations')->where('id', 15)->update(['description' => 'Corpo aggiornato']);
    $result = (new DocumentationStage)->run(documentationStageContext());

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(1)
        ->and($result->skipped)->toBe(0)
        ->and(DB::table('documentation_pages')->count())->toBe(1)
        ->and(DB::table('documentation_pages')->where('id', 15)->value('body'))->toBe('Corpo aggiornato')
        ->and(DB::table('documentation_pages')->where('id', 15)->value('slug'))->toBe($originalSlug);
});

test('--limit caps the number of v1 rows read', function (): void {
    insertLegacyDocumentation(['id' => 11]);
    insertLegacyDocumentation(['id' => 12, 'name' => 'Altra pagina']);

    $result = (new DocumentationStage)->run(documentationStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});
