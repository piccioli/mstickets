<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Events\EmailQuarantined;
use App\Domain\Mail\Listeners\NotifyStaffOfUnknownSender;
use App\Domain\Mail\Mailables\UnknownSenderStaffMail;
use App\Domain\Mail\Models\EmailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function quarantinedEmailMessage(): EmailMessage
{
    return EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'from_email' => 'sconosciuto@example.test',
        'status' => EmailStatus::Quarantined,
        'subject' => 'Ho un problema',
        'body_text' => 'Non riesco ad accedere al portale, potete aiutarmi?',
    ]);
}

test('sends E9 and an in-app notification to every resolved staff recipient', function (): void {
    Mail::fake();

    $staff = User::factory()->create(['email' => 'staff@example.test']);
    config(['mail_pipeline.staff_notification_group' => ['staff@example.test']]);

    $quarantined = quarantinedEmailMessage();

    (new NotifyStaffOfUnknownSender)->handle(new EmailQuarantined($quarantined, autoReplyAllowed: true));

    Mail::assertQueued(
        UnknownSenderStaffMail::class,
        fn (UnknownSenderStaffMail $mail): bool => $mail->quarantinedMessage->is($quarantined),
    );

    expect($staff->fresh()->notifications)->toHaveCount(1);
});

test('does not notify anyone when the staff group is empty', function (): void {
    Mail::fake();

    config(['mail_pipeline.staff_notification_group' => []]);

    (new NotifyStaffOfUnknownSender)->handle(new EmailQuarantined(quarantinedEmailMessage(), autoReplyAllowed: false));

    Mail::assertNothingQueued();
});
