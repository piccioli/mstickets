<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Models\NotificationPreference;
use App\Domain\Mail\Support\NotificationGate;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('allows a notification type with no preference row at all (default enabled)', function (): void {
    $user = User::factory()->create();

    expect(NotificationGate::allows($user, NotificationType::TicketStatusChanged))->toBeTrue();
});

test('allows a notification type explicitly enabled in preferences', function (): void {
    $user = User::factory()->create();

    NotificationPreference::create([
        'user_id' => $user->id,
        'notification_type' => NotificationType::TicketStatusChanged->value,
        'channel' => 'email',
        'enabled' => true,
    ]);

    expect(NotificationGate::allows($user, NotificationType::TicketStatusChanged))->toBeTrue();
});

test('blocks a notification type explicitly disabled in preferences', function (): void {
    $user = User::factory()->create();

    NotificationPreference::create([
        'user_id' => $user->id,
        'notification_type' => NotificationType::TicketStatusChanged->value,
        'channel' => 'email',
        'enabled' => false,
    ]);

    expect(NotificationGate::allows($user, NotificationType::TicketStatusChanged))->toBeFalse();
});

test('a disabled preference for a different notification type does not block this one', function (): void {
    $user = User::factory()->create();

    NotificationPreference::create([
        'user_id' => $user->id,
        'notification_type' => NotificationType::TicketAssigned->value,
        'channel' => 'email',
        'enabled' => false,
    ]);

    expect(NotificationGate::allows($user, NotificationType::TicketStatusChanged))->toBeTrue();
});

test('a disabled preference for a different user does not block this one', function (): void {
    $other = User::factory()->create();
    $user = User::factory()->create();

    NotificationPreference::create([
        'user_id' => $other->id,
        'notification_type' => NotificationType::TicketStatusChanged->value,
        'channel' => 'email',
        'enabled' => false,
    ]);

    expect(NotificationGate::allows($user, NotificationType::TicketStatusChanged))->toBeTrue();
});
