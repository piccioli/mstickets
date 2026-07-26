<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Mail\Models\EmailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeEmailMessageForPolicyTest(): EmailMessage
{
    return EmailMessage::create([
        'direction' => 'inbound',
        'from_email' => 'cliente@example.com',
        'status' => 'received',
    ]);
}

test('a user without any email.* permission is denied every EmailMessagePolicy ability', function (): void {
    $actor = userWithPermissions();
    $message = makeEmailMessageForPolicyTest();

    expect($actor->can('viewAny', EmailMessage::class))->toBeFalse()
        ->and($actor->can('view', $message))->toBeFalse()
        ->and($actor->can('create', EmailMessage::class))->toBeFalse()
        ->and($actor->can('update', $message))->toBeFalse()
        ->and($actor->can('delete', $message))->toBeFalse();
});

test('email.view grants read access, email.manage grants write access', function (): void {
    $message = makeEmailMessageForPolicyTest();

    $viewer = userWithPermissions(PermissionEnum::EmailView);
    expect($viewer->can('view', $message))->toBeTrue()
        ->and($viewer->can('update', $message))->toBeFalse();

    $manager = userWithPermissions(PermissionEnum::EmailManage);
    expect($manager->can('create', EmailMessage::class))->toBeTrue()
        ->and($manager->can('update', $message))->toBeTrue()
        ->and($manager->can('delete', $message))->toBeTrue();
});
