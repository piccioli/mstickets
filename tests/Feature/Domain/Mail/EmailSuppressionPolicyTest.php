<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Mail\Models\EmailSuppression;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeEmailSuppression(): EmailSuppression
{
    return EmailSuppression::create(['email' => 'bounced@example.com', 'reason' => 'hard_bounce']);
}

test('a user without any email.* permission is denied every EmailSuppressionPolicy ability', function (): void {
    $actor = userWithPermissions();
    $suppression = makeEmailSuppression();

    expect($actor->can('viewAny', EmailSuppression::class))->toBeFalse()
        ->and($actor->can('view', $suppression))->toBeFalse()
        ->and($actor->can('create', EmailSuppression::class))->toBeFalse()
        ->and($actor->can('update', $suppression))->toBeFalse()
        ->and($actor->can('delete', $suppression))->toBeFalse();
});

test('email.view grants read access, email.manage grants write access', function (): void {
    $suppression = makeEmailSuppression();

    $viewer = userWithPermissions(PermissionEnum::EmailView);
    expect($viewer->can('view', $suppression))->toBeTrue()
        ->and($viewer->can('delete', $suppression))->toBeFalse();

    $manager = userWithPermissions(PermissionEnum::EmailManage);
    expect($manager->can('create', EmailSuppression::class))->toBeTrue()
        ->and($manager->can('update', $suppression))->toBeTrue()
        ->and($manager->can('delete', $suppression))->toBeTrue();
});
