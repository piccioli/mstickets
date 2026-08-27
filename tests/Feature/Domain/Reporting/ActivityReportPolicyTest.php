<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeActivityReport(): ActivityReport
{
    return makeActivityReportForOwner(User::factory()->create());
}

function makeActivityReportForOwner(User $owner): ActivityReport
{
    return ActivityReport::create([
        'owner_kind' => 'user',
        'owner_user_id' => $owner->id,
        'period_type' => 'monthly',
        'year' => 2026,
        'month' => 7,
        'locale' => 'it',
    ]);
}

test('a user without any activity-report.* permission is denied every ActivityReportPolicy ability', function (): void {
    $actor = userWithPermissions();
    $report = makeActivityReport();

    expect($actor->can('viewAny', ActivityReport::class))->toBeFalse()
        ->and($actor->can('view', $report))->toBeFalse()
        ->and($actor->can('create', ActivityReport::class))->toBeFalse()
        ->and($actor->can('update', $report))->toBeFalse()
        ->and($actor->can('delete', $report))->toBeFalse()
        ->and($actor->can('generatePdf', $report))->toBeFalse();
});

test('a user with the matching activity-report.* permission is authorized', function (): void {
    $report = makeActivityReport();

    expect(userWithPermissions(PermissionEnum::ActivityReportViewAny)->can('view', $report))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::ActivityReportCreate)->can('create', ActivityReport::class))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::ActivityReportUpdate)->can('update', $report))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::ActivityReportDelete)->can('delete', $report))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::ActivityReportGeneratePdf)->can('generatePdf', $report))->toBeTrue();
});

test('activity-report.view.own authorizes only the actor\'s own report, never another owner\'s (US-409)', function (): void {
    $owner = userWithPermissions(PermissionEnum::ActivityReportViewOwn);
    $ownReport = makeActivityReportForOwner($owner);

    $otherCustomer = userWithPermissions(PermissionEnum::ActivityReportViewOwn);
    $othersReport = makeActivityReport();

    expect($owner->can('view', $ownReport))->toBeTrue()
        ->and($owner->can('view', $othersReport))->toBeFalse()
        ->and($otherCustomer->can('view', $ownReport))->toBeFalse();
});

test('activity-report.view.own authorizes a member of the owner organization but not a non-member (US-409)', function (): void {
    $organization = Organization::create(['name' => 'CAI Sezione Test', 'locale' => 'it']);
    $member = userWithPermissions(PermissionEnum::ActivityReportViewOwn);
    $organization->users()->attach($member);
    $outsider = userWithPermissions(PermissionEnum::ActivityReportViewOwn);

    $report = ActivityReport::create([
        'owner_kind' => 'organization',
        'owner_organization_id' => $organization->id,
        'period_type' => 'annual',
        'year' => 2026,
        'locale' => 'it',
    ]);

    expect($member->can('view', $report))->toBeTrue()
        ->and($outsider->can('view', $report))->toBeFalse();
});
