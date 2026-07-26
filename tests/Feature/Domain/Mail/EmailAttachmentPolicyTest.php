<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Mail\Models\EmailAttachment;
use App\Domain\Mail\Models\EmailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeEmailAttachment(): EmailAttachment
{
    $message = EmailMessage::create([
        'direction' => 'inbound',
        'from_email' => 'cliente@example.com',
        'status' => 'received',
    ]);

    return EmailAttachment::create([
        'email_message_id' => $message->id,
        'filename' => 'documento.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1024,
        'disk' => 'local',
        'path' => 'attachments/documento.pdf',
        'status' => 'stored',
    ]);
}

test('a user without any email.* permission is denied every EmailAttachmentPolicy ability', function (): void {
    $actor = userWithPermissions();
    $attachment = makeEmailAttachment();

    expect($actor->can('viewAny', EmailAttachment::class))->toBeFalse()
        ->and($actor->can('view', $attachment))->toBeFalse()
        ->and($actor->can('create', EmailAttachment::class))->toBeFalse()
        ->and($actor->can('update', $attachment))->toBeFalse()
        ->and($actor->can('delete', $attachment))->toBeFalse();
});

test('email.view grants read access, email.manage grants write access', function (): void {
    $attachment = makeEmailAttachment();

    $viewer = userWithPermissions(PermissionEnum::EmailView);
    expect($viewer->can('view', $attachment))->toBeTrue()
        ->and($viewer->can('delete', $attachment))->toBeFalse();

    $manager = userWithPermissions(PermissionEnum::EmailManage);
    expect($manager->can('create', EmailAttachment::class))->toBeTrue()
        ->and($manager->can('update', $attachment))->toBeTrue()
        ->and($manager->can('delete', $attachment))->toBeTrue();
});
