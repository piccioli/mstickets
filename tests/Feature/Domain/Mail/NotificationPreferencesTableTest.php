<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Models\NotificationPreference;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('notification_preferences table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('notification_preferences', [
        'id', 'user_id', 'notification_type', 'channel', 'enabled', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('enabled is cast to boolean and defaults to true', function (): void {
    $preference = NotificationPreference::create([
        'user_id' => User::factory()->create()->id,
        'notification_type' => 'ticket.assigned',
        'channel' => 'mail',
    ]);

    expect($preference->fresh()->enabled)->toBeTrue();
});

test('deleting the user cascades to its notification preferences', function (): void {
    $user = User::factory()->create();
    $preference = NotificationPreference::create([
        'user_id' => $user->id, 'notification_type' => 'ticket.assigned', 'channel' => 'mail',
    ]);

    $user->forceDelete();

    expect(NotificationPreference::find($preference->id))->toBeNull();
});

test('unique on the user/notification_type/channel triple', function (): void {
    $user = User::factory()->create();
    NotificationPreference::create([
        'user_id' => $user->id, 'notification_type' => 'ticket.assigned', 'channel' => 'mail',
    ]);

    expect(fn () => NotificationPreference::create([
        'user_id' => $user->id, 'notification_type' => 'ticket.assigned', 'channel' => 'mail',
    ]))->toThrow(QueryException::class);
});
