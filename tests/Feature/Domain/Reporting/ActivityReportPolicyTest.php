<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeActivityReport(): ActivityReport
{
    return ActivityReport::create([
        'owner_kind' => 'user',
        'owner_user_id' => User::factory()->create()->id,
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

    expect(userWithPermissions(PermissionEnum::ActivityReportViewOwn)->can('view', $report))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::ActivityReportCreate)->can('create', ActivityReport::class))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::ActivityReportUpdate)->can('update', $report))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::ActivityReportDelete)->can('delete', $report))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::ActivityReportGeneratePdf)->can('generatePdf', $report))->toBeTrue();
});
