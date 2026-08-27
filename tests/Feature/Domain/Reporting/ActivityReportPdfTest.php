<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Actions\GenerateActivityReportPdf;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Reporting\Models\ActivityReport;
use App\Domain\Reporting\Services\ActivityReportSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Coda "sync" in test (phpunit.xml): il job gira per davvero, con Chromium
 * reale — stesso principio di DocumentationPagePdfTest (US-406): niente
 * `Pdf::fake()` qui, questo file verifica che il contenuto generato sia un PDF
 * reale con i dati corretti (US-409 AC: "contenuto/non-vuoto, non il rendering
 * pixel-per-pixel", "ticket e totali corretti per un caso noto").
 */
beforeEach(fn () => Storage::fake('activity-report-pdfs'));

test('generates a non-empty PDF and stamps pdf_path/pdf_generated_at', function (): void {
    $owner = User::factory()->create(['name' => 'Mario Rossi']);
    ticket(['requester_id' => $owner->id, 'done_at' => '2026-02-10 09:00:00', 'worked_minutes' => 90]);
    ticket(['requester_id' => $owner->id, 'done_at' => '2026-02-20 09:00:00', 'worked_minutes' => 30]);

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
        'locale' => 'it',
    ]);
    (new ActivityReportSyncService)->syncTickets($report);

    GenerateActivityReportPdf::run($report);
    $report->refresh();

    expect($report->pdf_path)->toBe("activity-reports/{$report->id}.pdf")
        ->and($report->pdf_generated_at)->not->toBeNull();

    Storage::disk('activity-report-pdfs')->assertExists($report->pdf_path);

    $contents = Storage::disk('activity-report-pdfs')->get($report->pdf_path);

    expect($contents)->not->toBeEmpty()
        ->and(substr($contents, 0, 4))->toBe('%PDF');
});

test('rendering does not leak the report locale into the surrounding application locale', function (): void {
    $owner = User::factory()->create(['locale' => 'en']);

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
        'locale' => 'en',
    ]);

    app()->setLocale('it');

    GenerateActivityReportPdf::run($report);

    expect(app()->getLocale())->toBe('it');
});

test('deleting the report removes its generated PDF from storage', function (): void {
    $owner = User::factory()->create();

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
        'locale' => 'it',
    ]);

    GenerateActivityReportPdf::run($report);
    $report->refresh();
    $path = $report->pdf_path;

    Storage::disk('activity-report-pdfs')->assertExists($path);

    $report->delete();

    Storage::disk('activity-report-pdfs')->assertMissing($path);
});

test('deleting a report without a generated PDF does not error', function (): void {
    $owner = User::factory()->create();

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
        'locale' => 'it',
    ]);

    $report->delete();

    expect(ActivityReport::count())->toBe(0);
});
