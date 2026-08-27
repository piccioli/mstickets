<?php

declare(strict_types=1);

use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('periodStart/periodEnd span the full month for a monthly report', function (): void {
    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => User::factory()->create()->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
        'locale' => 'it',
    ]);

    expect($report->periodStart()->toDateTimeString())->toBe('2026-02-01 00:00:00')
        ->and($report->periodEnd()->toDateTimeString())->toBe('2026-02-28 23:59:59');
});

test('periodStart/periodEnd span the full year for an annual report', function (): void {
    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => User::factory()->create()->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
        'locale' => 'it',
    ]);

    expect($report->periodStart()->toDateTimeString())->toBe('2026-01-01 00:00:00')
        ->and($report->periodEnd()->toDateTimeString())->toBe('2026-12-31 23:59:59');
});

test('ownerName resolves to the user name for a user-owned report', function (): void {
    $user = User::factory()->create(['name' => 'Mario Rossi']);

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $user->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
        'locale' => 'it',
    ]);

    expect($report->ownerName())->toBe('Mario Rossi');
});

test('ownerName resolves to the organization name for an organization-owned report', function (): void {
    $organization = Organization::create(['name' => 'CAI Sezione Test']);

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::Organization,
        'owner_organization_id' => $organization->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
        'locale' => 'it',
    ]);

    expect($report->ownerName())->toBe('CAI Sezione Test');
});

test('periodLabel is just the year for an annual report', function (): void {
    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => User::factory()->create()->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
        'locale' => 'it',
    ]);

    expect($report->periodLabel())->toBe('2026');
});

test('periodLabel is the localized capitalized month name and year for a monthly report', function (): void {
    $italian = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => User::factory()->create()->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
        'locale' => 'it',
    ]);

    $english = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => User::factory()->create()->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
        'locale' => 'en',
    ]);

    expect($italian->periodLabel())->toBe('Febbraio 2026')
        ->and($english->periodLabel())->toBe('February 2026');
});
