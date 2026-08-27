<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Filament\Pages\CustomerDashboard;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\WorkBoard;
use App\Filament\Resources\FundraisingOpportunities\FundraisingOpportunityResource;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

test('staff (admin/manager/developer) landing on the dashboard is redirected to the work board', function (UserRole $role): void {
    $user = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketManageInternalFields), $role);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)->assertRedirect(WorkBoard::getUrl());
})->with([
    'admin' => [UserRole::Admin],
    'manager' => [UserRole::Manager],
    'developer' => [UserRole::Developer],
]);

test('a customer landing on the dashboard is redirected to the customer dashboard', function (): void {
    $customer = grantTicketPanelRole(userWithPermissions(), UserRole::Customer);

    $this->actingAs($customer);

    Livewire::test(Dashboard::class)->assertRedirect(CustomerDashboard::getUrl());
});

test('a fundraising team member landing on the dashboard is redirected to the opportunity list', function (): void {
    $fundraising = grantTicketPanelRole(userWithPermissions(), UserRole::Fundraising);

    $this->actingAs($fundraising);

    Livewire::test(Dashboard::class)->assertRedirect(FundraisingOpportunityResource::getUrl('index'));
});
