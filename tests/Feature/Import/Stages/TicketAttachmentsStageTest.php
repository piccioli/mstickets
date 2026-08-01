<?php

declare(strict_types=1);

use App\Domain\Ticketing\Models\TicketMessage;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportMapping;
use App\Import\Models\ImportRun;
use App\Import\Stages\ImportContext;
use App\Import\Stages\TicketAttachmentsStage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function ticketAttachmentsStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
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

    Schema::connection('legacy')->create('media', function (Blueprint $table): void {
        $table->id();
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
        $table->uuid('uuid')->nullable();
        $table->string('name');
        $table->string('file_name');
        $table->string('mime_type')->nullable();
    });

    Storage::fake('legacy-media');
    Storage::fake('ticket-attachments');
});

function insertTicketForAttachments(int $id, ?string $createdAt = null): void
{
    DB::table('tickets')->insert([
        'id' => $id,
        'title' => "Ticket {$id}",
        'status' => 'new',
        'status_changed_at' => now(),
        'type' => 'helpdesk',
        'priority' => 'low',
        'worked_minutes' => 0,
        'created_at' => $createdAt ?? now(),
        'updated_at' => $createdAt ?? now(),
    ]);
}

function insertLegacyTicketMessage(int $ticketId, string $postedAt): int
{
    return DB::table('ticket_messages')->insertGetId([
        'ulid' => strtolower((string) Str::ulid()),
        'ticket_id' => $ticketId,
        'channel' => 'system',
        'visibility' => 'public',
        'body_html' => 'Corpo',
        'body_text' => 'Corpo',
        'is_legacy_import' => true,
        'posted_at' => $postedAt,
        'created_at' => $postedAt,
        'updated_at' => $postedAt,
    ]);
}

function insertLegacyMedia(int $modelId, string $uuid, string $fileName, ?string $modelType = null): void
{
    DB::connection('legacy')->table('media')->insert([
        'model_type' => $modelType ?? 'App\\Models\\Story',
        'model_id' => $modelId,
        'uuid' => $uuid,
        'name' => 'Documento di test',
        'file_name' => $fileName,
    ]);
}

test('a media with its file present on disk is attached to the first legacy message of its ticket', function (): void {
    insertTicketForAttachments(100);
    $older = insertLegacyTicketMessage(100, '2026-01-01 09:00:00');
    insertLegacyTicketMessage(100, '2026-01-02 09:00:00');

    Storage::disk('legacy-media')->put('report.txt', 'Contenuto vero del file.');
    insertLegacyMedia(100, '372a3c0f-72bd-4b12-8629-1196a0c15cc0', 'report.txt');

    $result = (new TicketAttachmentsStage)->run(ticketAttachmentsStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1)
        ->and($result->skipped)->toBe(0);

    expect(Media::query()->count())->toBe(1);

    $media = Media::query()->first();

    expect($media->model_type)->toBe(TicketMessage::class)
        ->and($media->model_id)->toBe($older)
        ->and($media->collection_name)->toBe('attachments')
        ->and($media->file_name)->toBe('report.txt');

    expect(ImportMapping::query()->where('source_table', 'media')->where('target_table', 'media')->count())->toBe(1);

    // Il file sorgente non va rimosso dal disco v1 (preservingOriginal).
    Storage::disk('legacy-media')->assertExists('report.txt');
});

test('a media whose physical file is missing is reported as orphan, not attached', function (): void {
    insertTicketForAttachments(200);
    insertLegacyTicketMessage(200, '2026-01-01 09:00:00');
    insertLegacyMedia(200, 'c22e145b-2d95-46c7-99ff-1cc73480cb2f', 'missing.pdf');

    $result = (new TicketAttachmentsStage)->run(ticketAttachmentsStageContext());

    expect($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings)->toContain('1 media orfani: riga presente nel dump ma file assente su disco.')
        ->and(Media::query()->count())->toBe(0);
});

test('a ticket without any legacy message gets a system message created to host its attachments', function (): void {
    insertTicketForAttachments(300, createdAt: '2026-02-01 10:00:00');
    Storage::disk('legacy-media')->put('bilancio.txt', 'Bilancio.');
    insertLegacyMedia(300, 'a33a8778-2496-4fd8-b8a8-3d7a48237bf9', 'bilancio.txt');

    $result = (new TicketAttachmentsStage)->run(ticketAttachmentsStageContext());

    expect($result->created)->toBe(1)
        ->and($result->warnings)->toContain('1 ticket senza messaggi: creato un messaggio di sistema "Allegati importati" per ospitare gli allegati.');

    $message = DB::table('ticket_messages')->where('ticket_id', 300)->first();

    expect($message)->not->toBeNull()
        ->and((bool) $message->is_legacy_import)->toBeTrue()
        ->and($message->body_text)->toBe('Allegati importati')
        ->and($message->posted_at)->toBe('2026-02-01 10:00:00');

    expect(Media::query()->where('model_id', $message->id)->count())->toBe(1);
});

test('a media referencing a non-existent v2 ticket is discarded and reported, not crashed', function (): void {
    Storage::disk('legacy-media')->put('orfano.pdf', 'Contenuto.');
    insertLegacyMedia(999, 'ad3d2321-a79d-40da-8f83-c6be2475c88c', 'orfano.pdf');

    $result = (new TicketAttachmentsStage)->run(ticketAttachmentsStageContext());

    expect($result->skipped)->toBe(1)
        ->and($result->warnings)->toContain('1 media scartati: ticket v1 inesistente in v2.')
        ->and(Media::query()->count())->toBe(0);
});

test('a media on a model_type other than Story is out of scope and reported, not crashed', function (): void {
    insertTicketForAttachments(400);
    Storage::disk('legacy-media')->put('doc.pdf', 'Contenuto.');
    insertLegacyMedia(400, '199cc074-2408-400c-a6bd-d561be1bc19b', 'doc.pdf', modelType: 'App\\Models\\Documentation');

    $result = (new TicketAttachmentsStage)->run(ticketAttachmentsStageContext());

    expect($result->skipped)->toBe(1)
        ->and($result->warnings)->toContain('1 media scartati: model_type v1 diverso da Story (fuori scope di questo stage).')
        ->and(Media::query()->count())->toBe(0);
});

test('dry-run does not attach media, create system messages nor write import_mappings', function (): void {
    insertTicketForAttachments(500);
    insertLegacyTicketMessage(500, '2026-01-01 09:00:00');
    Storage::disk('legacy-media')->put('report.txt', 'Contenuto.');
    insertLegacyMedia(500, 'd36105fb-8eb5-4a3e-af21-4ec68f96fa53', 'report.txt');

    $result = (new TicketAttachmentsStage)->run(ticketAttachmentsStageContext(dryRun: true));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and(Media::query()->count())->toBe(0)
        ->and(ImportMapping::query()->count())->toBe(0);
});

test('--limit caps the number of source rows read', function (): void {
    insertTicketForAttachments(600);
    insertLegacyTicketMessage(600, '2026-01-01 09:00:00');
    Storage::disk('legacy-media')->put('uno.txt', 'Uno.');
    Storage::disk('legacy-media')->put('due.txt', 'Due.');
    insertLegacyMedia(600, '6cdbf949-2b07-4150-ab9a-439378969d9c', 'uno.txt');
    insertLegacyMedia(600, 'c978cb3c-b07f-412a-b084-7069bd152a29', 'due.txt');

    $result = (new TicketAttachmentsStage)->run(ticketAttachmentsStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});

test('re-running the stage on the same dump is idempotent via import_mappings on media.uuid: second run only skips', function (): void {
    insertTicketForAttachments(700);
    insertLegacyTicketMessage(700, '2026-01-01 09:00:00');
    Storage::disk('legacy-media')->put('report.txt', 'Contenuto.');
    insertLegacyMedia(700, '538eec50-ddfa-4798-b842-c62e65ae17ea', 'report.txt');

    $stage = new TicketAttachmentsStage;
    $first = $stage->run(ticketAttachmentsStageContext());
    $second = $stage->run(ticketAttachmentsStageContext());

    expect($first->created)->toBe(1)
        ->and($second->created)->toBe(0)
        ->and($second->skipped)->toBe(1)
        ->and(Media::query()->count())->toBe(1)
        ->and(ImportMapping::query()->where('target_table', 'media')->count())->toBe(1);
});
