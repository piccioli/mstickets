<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Mailables\ActivityReportPdfGeneratedMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function outboundActivityReportNotification(): EmailMessage
{
    $token = Str::random(8);

    return EmailMessage::create([
        'direction' => EmailDirection::Outbound,
        'status' => EmailStatus::Queued,
        'from_email' => 'noreply@example.test',
        'message_id' => "activity-report-test-{$token}@example.test",
        'reply_to' => "ticket+activity-report-test-{$token}@example.test",
        'subject' => 'Your activity report is ready',
    ]);
}

function annualActivityReportForMailTest(): ActivityReport
{
    $owner = User::factory()->create();

    return ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
        'locale' => 'it',
    ]);
}

test('renders well-formed HTML with the period and a working download link', function (): void {
    $report = annualActivityReportForMailTest();

    $mailable = new ActivityReportPdfGeneratedMail($report, outboundActivityReportNotification());
    $html = $mailable->render();

    libxml_use_internal_errors(true);
    $document = new DOMDocument;
    $document->loadHTML($html);
    $errors = libxml_get_errors();
    libxml_clear_errors();

    expect($errors)->toBeEmpty()
        ->and($html)->toContain('2026')
        ->and($html)->toContain(route('activity-reports.pdf-download', ['activityReport' => $report->id]));
});

test('sets the Message-Id header and the VERP Reply-To from the outbound email_messages row', function (): void {
    $report = annualActivityReportForMailTest();
    $outbound = outboundActivityReportNotification();

    $mailable = new ActivityReportPdfGeneratedMail($report, $outbound);

    expect($mailable->envelope()->replyTo[0]->address)->toBe($outbound->reply_to)
        ->and($mailable->headers()->messageId)->toBe($outbound->message_id);
});

test('generates a plain-text version alongside the HTML', function (): void {
    $report = annualActivityReportForMailTest();

    $mailable = new ActivityReportPdfGeneratedMail($report, outboundActivityReportNotification());
    $content = $mailable->content();
    $textOnly = view($content->text, $content->with)->render();

    expect($textOnly)
        ->not->toBeEmpty()
        ->toContain(route('activity-reports.pdf-download', ['activityReport' => $report->id]))
        ->not->toContain('<p>');
});

test('renders the body in the language set via ->locale(), never a raw untranslated key (§7.6, US-320)', function (): void {
    $report = annualActivityReportForMailTest();

    $italianHtml = (new ActivityReportPdfGeneratedMail($report, outboundActivityReportNotification()))->locale('it')->render();
    $englishHtml = (new ActivityReportPdfGeneratedMail($report, outboundActivityReportNotification()))->locale('en')->render();

    expect($italianHtml)->toContain('è pronto')
        ->and($englishHtml)->toContain('is ready')
        ->and($englishHtml)->not->toContain('è pronto');
});
