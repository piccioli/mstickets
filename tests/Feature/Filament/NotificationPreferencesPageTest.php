<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Models\NotificationPreference;
use App\Filament\Pages\NotificationPreferences;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('any authenticated user can access the page, regardless of role', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $manager = withRole(User::factory()->create(), UserRole::Manager);

    $this->actingAs($customer);
    expect(NotificationPreferences::canAccess())->toBeTrue();

    $this->actingAs($manager);
    expect(NotificationPreferences::canAccess())->toBeTrue();
});

test('a customer never sees a notification type that only applies to staff (e.g. TicketAssigned)', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);

    $this->actingAs($customer);

    $types = Livewire::test(NotificationPreferences::class)->instance()->applicableTypes();

    expect($types)->not->toContain(NotificationType::TicketAssigned)
        ->and($types)->toContain(NotificationType::TicketReceivedByEmail);
});

test('a staff member never sees a notification type that only applies to customers (e.g. TicketReceivedByEmail)', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $manager = withRole(User::factory()->create(), UserRole::Manager);

    $this->actingAs($manager);

    $types = Livewire::test(NotificationPreferences::class)->instance()->applicableTypes();

    expect($types)->not->toContain(NotificationType::TicketReceivedByEmail)
        ->and($types)->toContain(NotificationType::TicketAssigned);
});

test('a type with no existing preference row defaults to enabled when the page loads', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);

    $this->actingAs($customer);

    Livewire::test(NotificationPreferences::class)
        ->assertSet('enabled.'.NotificationType::TicketReceivedByEmail->value, true);
});

test('a type with an existing disabled preference row loads as disabled', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    NotificationPreference::create([
        'user_id' => $customer->id,
        'notification_type' => NotificationType::TicketReceivedByEmail->value,
        'channel' => 'email',
        'enabled' => false,
    ]);

    $this->actingAs($customer);

    Livewire::test(NotificationPreferences::class)
        ->assertSet('enabled.'.NotificationType::TicketReceivedByEmail->value, false);
});

test('saving persists an updateOrCreate row scoped to the current user only, never another user', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $otherCustomer = withRole(User::factory()->create(), UserRole::Customer);

    $this->actingAs($customer);

    Livewire::test(NotificationPreferences::class)
        ->set('enabled.'.NotificationType::TicketReceivedByEmail->value, false)
        ->call('save');

    $ownPreference = NotificationPreference::query()
        ->where('user_id', $customer->id)
        ->where('notification_type', NotificationType::TicketReceivedByEmail->value)
        ->where('channel', 'email')
        ->first();

    expect($ownPreference)->not->toBeNull()
        ->and($ownPreference->enabled)->toBeFalse();

    expect(NotificationPreference::query()->where('user_id', $otherCustomer->id)->exists())->toBeFalse();
});

test('saving does not write rows for notification types that do not apply to the current role', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);

    $this->actingAs($customer);

    Livewire::test(NotificationPreferences::class)->call('save');

    expect(NotificationPreference::query()
        ->where('user_id', $customer->id)
        ->where('notification_type', NotificationType::TicketAssigned->value)
        ->exists())->toBeFalse();
});
