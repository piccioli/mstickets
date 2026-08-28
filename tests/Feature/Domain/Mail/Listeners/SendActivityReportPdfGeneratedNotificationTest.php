<?php

declare(strict_types=1);

use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Listeners\SendActivityReportPdfGeneratedNotification;
use App\Domain\Mail\Mailables\ActivityReportPdfGeneratedMail;
use App\Domain\Mail\Models\NotificationPreference;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Reporting\Events\ActivityReportPdfGenerated;
use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function userOwnedActivityReport(User $owner): ActivityReport
{
    return ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
        'locale' => 'it',
    ]);
}

test('sends E10 to the owner when a user-owned report pdf is generated', function (): void {
    Mail::fake();

    $owner = User::factory()->create();
    $report = userOwnedActivityReport($owner);

    (new SendActivityReportPdfGeneratedNotification)->handle(new ActivityReportPdfGenerated($report));

    Mail::assertQueued(
        ActivityReportPdfGeneratedMail::class,
        fn (ActivityReportPdfGeneratedMail $mail): bool => $mail->report->is($report),
    );
});

test('sends E10 to every member of an organization-owned report', function (): void {
    Mail::fake();

    $organization = Organization::create(['name' => 'CAI Sezione Test', 'locale' => 'it']);
    $memberA = User::factory()->create();
    $memberB = User::factory()->create();
    $organization->users()->attach([$memberA->id, $memberB->id]);

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::Organization,
        'owner_organization_id' => $organization->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
        'locale' => 'it',
    ]);

    (new SendActivityReportPdfGeneratedNotification)->handle(new ActivityReportPdfGenerated($report));

    Mail::assertQueued(ActivityReportPdfGeneratedMail::class, 2);
});

test('does not send to a user who disabled this notification type', function (): void {
    Mail::fake();

    $owner = User::factory()->create();
    NotificationPreference::create([
        'user_id' => $owner->id,
        'notification_type' => NotificationType::ActivityReportPdfGenerated->value,
        'channel' => 'email',
        'enabled' => false,
    ]);
    $report = userOwnedActivityReport($owner);

    (new SendActivityReportPdfGeneratedNotification)->handle(new ActivityReportPdfGenerated($report));

    Mail::assertNotQueued(ActivityReportPdfGeneratedMail::class);
});

test('implements ShouldQueue so the send happens asynchronously', function (): void {
    expect(new SendActivityReportPdfGeneratedNotification)->toBeInstanceOf(ShouldQueue::class);
});
