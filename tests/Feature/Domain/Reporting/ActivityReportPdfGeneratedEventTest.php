<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Actions\GenerateActivityReportPdf;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Reporting\Events\ActivityReportPdfGenerated;
use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('activity-report-pdfs'));

test('dispatches the domain event the first time the pdf is generated', function (): void {
    Event::fake([ActivityReportPdfGenerated::class]);

    $owner = User::factory()->create();
    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
        'locale' => 'it',
    ]);

    GenerateActivityReportPdf::run($report);

    Event::assertDispatched(ActivityReportPdfGenerated::class, fn (ActivityReportPdfGenerated $event): bool => $event->report->is($report));
});

test('does not dispatch the domain event again when the pdf is regenerated', function (): void {
    Event::fake([ActivityReportPdfGenerated::class]);

    $owner = User::factory()->create();
    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
        'locale' => 'it',
    ]);

    GenerateActivityReportPdf::run($report);
    GenerateActivityReportPdf::run($report);

    Event::assertDispatchedTimes(ActivityReportPdfGenerated::class, 1);
});
