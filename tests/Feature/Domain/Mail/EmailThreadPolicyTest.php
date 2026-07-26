<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Mail\Models\EmailThread;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeEmailThread(): EmailThread
{
    return EmailThread::create(['subject_normalized' => 'richiesta supporto']);
}

test('a user without any email.* permission is denied every EmailThreadPolicy ability', function (): void {
    $actor = userWithPermissions();
    $thread = makeEmailThread();

    expect($actor->can('viewAny', EmailThread::class))->toBeFalse()
        ->and($actor->can('view', $thread))->toBeFalse()
        ->and($actor->can('create', EmailThread::class))->toBeFalse()
        ->and($actor->can('update', $thread))->toBeFalse()
        ->and($actor->can('delete', $thread))->toBeFalse();
});

test('email.view grants read access, email.manage grants write access', function (): void {
    $thread = makeEmailThread();

    $viewer = userWithPermissions(PermissionEnum::EmailView);
    expect($viewer->can('view', $thread))->toBeTrue()
        ->and($viewer->can('delete', $thread))->toBeFalse();

    $manager = userWithPermissions(PermissionEnum::EmailManage);
    expect($manager->can('create', EmailThread::class))->toBeTrue()
        ->and($manager->can('update', $thread))->toBeTrue()
        ->and($manager->can('delete', $thread))->toBeTrue();
});
