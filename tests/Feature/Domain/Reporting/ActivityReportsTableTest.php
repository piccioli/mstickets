<?php

declare(strict_types=1);

use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('activity_reports table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('activity_reports', [
        'id', 'owner_kind', 'owner_user_id', 'owner_organization_id', 'period_type',
        'year', 'month', 'locale', 'pdf_path', 'pdf_generated_at', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('casts owner_kind and period_type to their backed enums', function (): void {
    $user = User::factory()->create();

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $user->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 7,
        'locale' => 'it',
    ]);

    expect($report->fresh()->owner_kind)->toBe(ActivityReportOwnerKind::User)
        ->and($report->fresh()->period_type)->toBe(ActivityReportPeriodType::Monthly);
});

test('belongs to an owner user', function (): void {
    $user = User::factory()->create();

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $user->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
        'locale' => 'it',
    ]);

    expect($report->ownerUser->is($user))->toBeTrue();
});

test('belongs to an owner organization, cascading on delete', function (): void {
    $organization = Organization::create(['name' => 'CAI Sezione Test']);

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::Organization,
        'owner_organization_id' => $organization->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
        'locale' => 'it',
    ]);

    expect($report->ownerOrganization->is($organization))->toBeTrue();

    $organization->delete();

    expect(ActivityReport::find($report->id))->toBeNull();
});

test('unique on the owner/period/year/month tuple', function (): void {
    $user = User::factory()->create();

    ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $user->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 7,
        'locale' => 'it',
    ]);

    expect(fn () => ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $user->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 7,
        'locale' => 'it',
    ]))->toThrow(QueryException::class);
});

test('the owner check constraint rejects a row with neither owner set', function (): void {
    expect(fn () => ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 7,
        'locale' => 'it',
    ]))->toThrow(QueryException::class);
});

test('the owner check constraint rejects a row with both owners set', function (): void {
    $user = User::factory()->create();
    $organization = Organization::create(['name' => 'CAI Sezione Test']);

    expect(fn () => ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $user->id,
        'owner_organization_id' => $organization->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 7,
        'locale' => 'it',
    ]))->toThrow(QueryException::class);
});

test('the owner check constraint rejects owner_kind inconsistent with the valorized owner', function (): void {
    $organization = Organization::create(['name' => 'CAI Sezione Test']);

    expect(fn () => ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_organization_id' => $organization->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 7,
        'locale' => 'it',
    ]))->toThrow(QueryException::class);
});

test('the owner check constraint also applies on update', function (): void {
    $user = User::factory()->create();

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $user->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 7,
        'locale' => 'it',
    ]);

    expect(fn () => $report->update(['owner_user_id' => null]))->toThrow(QueryException::class);
});
