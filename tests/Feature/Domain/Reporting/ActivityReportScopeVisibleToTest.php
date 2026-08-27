<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Actions\CreateActivityReport;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('view.any sees every report regardless of owner', function (): void {
    $viewer = userWithPermissions(PermissionEnum::ActivityReportViewAny);
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $report = CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
    ]);
    $otherReport = CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $other->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
    ]);

    $visible = ActivityReport::query()->visibleTo($viewer)->pluck('id')->all();

    expect($visible)->toEqualCanonicalizing([$report->id, $otherReport->id]);
});

test('view.own sees only its own report as a direct user owner, never another owner', function (): void {
    $owner = userWithPermissions(PermissionEnum::ActivityReportViewOwn);
    $other = User::factory()->create();

    $ownReport = CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
    ]);
    CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $other->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
    ]);

    $visible = ActivityReport::query()->visibleTo($owner)->pluck('id')->all();

    expect($visible)->toBe([$ownReport->id]);
});

test('view.own sees the report of its own organization, never another organization', function (): void {
    $organization = Organization::create(['name' => 'CAI Sezione Test', 'locale' => 'it']);
    $otherOrganization = Organization::create(['name' => 'CAI Sezione Altra', 'locale' => 'it']);
    $member = userWithPermissions(PermissionEnum::ActivityReportViewOwn);
    $organization->users()->attach($member);

    $ownReport = CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::Organization,
        'owner_organization_id' => $organization->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
    ]);
    CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::Organization,
        'owner_organization_id' => $otherOrganization->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
    ]);

    $visible = ActivityReport::query()->visibleTo($member)->pluck('id')->all();

    expect($visible)->toBe([$ownReport->id]);
});

test('a user without any activity-report permission sees nothing', function (): void {
    $user = User::factory()->create();
    $owner = User::factory()->create();

    CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
    ]);

    expect(ActivityReport::query()->visibleTo($user)->count())->toBe(0);
});
