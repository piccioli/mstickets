<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Actions\CreateActivityReport;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Filament\Resources\ActivityReports\ActivityReportResource;
use App\Filament\Resources\ActivityReports\Pages\ListActivityReports;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    Queue::fake();
});

test('a customer with activity-report.view.own sees only its own report in the list', function (): void {
    $customer = grantTicketPanelRole(userWithPermissions(PermissionEnum::ActivityReportViewOwn), UserRole::Customer);
    $other = User::factory()->create();

    $ownReport = CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $customer->id,
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

    $this->actingAs($customer);

    Livewire::test(ListActivityReports::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$ownReport])
        ->assertCanNotSeeTableRecords([$otherReport]);
});

test('a user without any activity-report permission is denied access to the resource', function (): void {
    $user = grantTicketPanelRole(userWithPermissions(), UserRole::Customer);

    expect(ActivityReportResource::canViewAny())->toBeFalse();

    $this->actingAs($user)->get(ActivityReportResource::getUrl('index'))->assertForbidden();
});
