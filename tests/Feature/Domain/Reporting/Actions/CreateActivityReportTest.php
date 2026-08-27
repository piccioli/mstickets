<?php

declare(strict_types=1);

use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Actions\CreateActivityReport;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creates the report and syncs its tickets in one call', function (): void {
    $owner = User::factory()->create();
    $inPeriod = ticket(['requester_id' => $owner->id, 'done_at' => '2026-02-15 10:00:00']);

    $report = CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
    ]);

    expect($report)->toBeInstanceOf(ActivityReport::class)
        ->and($report->exists)->toBeTrue()
        ->and($report->tickets()->pluck('tickets.id')->all())->toBe([$inPeriod->id]);
});

test('derives locale from the owner user, ignoring any locale passed by the caller', function (): void {
    $owner = User::factory()->create(['locale' => 'en']);

    $report = CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
        'locale' => 'it',
    ]);

    expect($report->locale)->toBe('en');
});

test('derives locale from the owner organization', function (): void {
    $organization = Organization::create(['name' => 'CAI Sezione Test', 'locale' => 'de']);

    $report = CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::Organization,
        'owner_organization_id' => $organization->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
    ]);

    expect($report->locale)->toBe('de');
});

test('rejects a duplicate owner/period with a readable error instead of the raw QueryException', function (): void {
    $owner = User::factory()->create();

    CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
    ]);

    expect(fn () => CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
    ]))->toThrow(RuntimeException::class, 'Esiste già un report attività per questo owner e questo periodo.');

    expect(ActivityReport::count())->toBe(1);
});

test('does not treat a different period for the same owner as a duplicate', function (): void {
    $owner = User::factory()->create();

    CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
    ]);

    $second = CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 3,
    ]);

    expect($second->exists)->toBeTrue()
        ->and(ActivityReport::count())->toBe(2);
});
