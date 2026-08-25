<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Support\StaffNotificationGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('resolves users whose email matches the configured group, case-insensitively', function (): void {
    $staffer = User::factory()->create(['email' => 'staff@example.test']);
    User::factory()->create(['email' => 'someone-else@example.test']);

    config(['mail_pipeline.staff_notification_group' => ['STAFF@example.test']]);

    $recipients = StaffNotificationGroup::recipients();

    expect($recipients)->toHaveCount(1)
        ->and($recipients->first()->is($staffer))->toBeTrue();
});

test('ignores a configured address that matches no user', function (): void {
    config(['mail_pipeline.staff_notification_group' => ['ghost@example.test']]);

    expect(StaffNotificationGroup::recipients())->toBeEmpty();
});

test('returns an empty collection when the group is not configured', function (): void {
    User::factory()->create(['email' => 'staff@example.test']);

    config(['mail_pipeline.staff_notification_group' => []]);

    expect(StaffNotificationGroup::recipients())->toBeEmpty();
});

test('excludes a deactivated user even if their address is in the configured group', function (): void {
    User::factory()->create(['email' => 'staff@example.test', 'deactivated_at' => now()]);

    config(['mail_pipeline.staff_notification_group' => ['staff@example.test']]);

    expect(StaffNotificationGroup::recipients())->toBeEmpty();
});

test('changing the configured group changes the resolved recipients without any code change', function (): void {
    $first = User::factory()->create(['email' => 'first@example.test']);
    $second = User::factory()->create(['email' => 'second@example.test']);

    config(['mail_pipeline.staff_notification_group' => ['first@example.test']]);
    expect(StaffNotificationGroup::recipients()->pluck('id')->all())->toBe([$first->id]);

    config(['mail_pipeline.staff_notification_group' => ['second@example.test']]);
    expect(StaffNotificationGroup::recipients()->pluck('id')->all())->toBe([$second->id]);
});
