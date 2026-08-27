<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

test('an admin with user.deactivate sees the toggle action on the users table and the view page', function (): void {
    $admin = grantTicketPanelRole(userWithPermissions(PermissionEnum::UserView, PermissionEnum::UserDeactivate));
    $target = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionVisible('toggleDeactivation', $target);

    Livewire::test(ViewUser::class, ['record' => $target->getKey()])
        ->assertActionVisible('toggleDeactivation');
});

test('a user without user.deactivate does not see the toggle action', function (): void {
    $staff = grantTicketPanelRole(userWithPermissions(PermissionEnum::UserView));
    $target = User::factory()->create();

    $this->actingAs($staff);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('toggleDeactivation', $target);

    Livewire::test(ViewUser::class, ['record' => $target->getKey()])
        ->assertActionHidden('toggleDeactivation');
});

test('the toggle action deactivates an active user and reactivates a deactivated one', function (): void {
    $admin = grantTicketPanelRole(userWithPermissions(PermissionEnum::UserView, PermissionEnum::UserDeactivate));
    $target = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('toggleDeactivation', $target)
        ->assertHasNoTableActionErrors();

    expect($target->fresh()->deactivated_at)->not->toBeNull();

    Livewire::test(ListUsers::class)
        ->callTableAction('toggleDeactivation', $target)
        ->assertHasNoTableActionErrors();

    expect($target->fresh()->deactivated_at)->toBeNull();
});

test('deactivating a user does not touch the historical assignee/requester/tester relation on an existing ticket', function (): void {
    $assignee = User::factory()->create();
    $existingTicket = ticket(['assignee_id' => $assignee->id]);

    $assignee->deactivated_at = now();
    $assignee->save();

    expect($existingTicket->fresh()->assignee?->id)->toBe($assignee->id);
});
