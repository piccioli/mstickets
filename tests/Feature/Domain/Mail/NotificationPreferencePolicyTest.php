<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Models\NotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeNotificationPreference(): NotificationPreference
{
    return NotificationPreference::create([
        'user_id' => User::factory()->create()->id,
        'notification_type' => 'ticket.status_changed',
        'channel' => 'email',
    ]);
}

test('a user without any email.* permission is denied every NotificationPreferencePolicy ability', function (): void {
    $actor = userWithPermissions();
    $preference = makeNotificationPreference();

    expect($actor->can('viewAny', NotificationPreference::class))->toBeFalse()
        ->and($actor->can('view', $preference))->toBeFalse()
        ->and($actor->can('create', NotificationPreference::class))->toBeFalse()
        ->and($actor->can('update', $preference))->toBeFalse()
        ->and($actor->can('delete', $preference))->toBeFalse();
});

test('email.view grants read access, email.manage grants write access', function (): void {
    $preference = makeNotificationPreference();

    $viewer = userWithPermissions(PermissionEnum::EmailView);
    expect($viewer->can('view', $preference))->toBeTrue()
        ->and($viewer->can('update', $preference))->toBeFalse();

    $manager = userWithPermissions(PermissionEnum::EmailManage);
    expect($manager->can('create', NotificationPreference::class))->toBeTrue()
        ->and($manager->can('update', $preference))->toBeTrue()
        ->and($manager->can('delete', $preference))->toBeTrue();
});
