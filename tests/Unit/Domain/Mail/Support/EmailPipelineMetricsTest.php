<?php

declare(strict_types=1);

use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Support\EmailPipelineMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function inboundMessageFixture(array $attributes = []): EmailMessage
{
    return EmailMessage::create(array_merge([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Applied,
        'from_email' => 'mittente@example.com',
    ], $attributes))->fresh();
}

function outboundMessageFixture(array $attributes = []): EmailMessage
{
    return EmailMessage::create(array_merge([
        'direction' => EmailDirection::Outbound,
        'status' => EmailStatus::Queued,
        'from_email' => 'support@example.com',
    ], $attributes))->fresh();
}

test('an empty inbox reports zero counts and no averages/rates', function (): void {
    $snapshot = EmailPipelineMetrics::snapshot();

    expect($snapshot->processedLast24h)->toBe(0)
        ->and($snapshot->discardedLast24h)->toBe(0)
        ->and($snapshot->failedLast24h)->toBe(0)
        ->and($snapshot->avgProcessingSeconds)->toBeNull()
        ->and($snapshot->bounceRate)->toBeNull();
});

test('counts processed, discarded and failed messages updated in the last 24h', function (): void {
    $this->travelTo('2026-08-18 12:00:00');

    inboundMessageFixture(['status' => EmailStatus::Applied]);
    inboundMessageFixture(['status' => EmailStatus::Discarded]);
    inboundMessageFixture(['status' => EmailStatus::Failed]);
    outboundMessageFixture(['status' => EmailStatus::Failed]);

    // Outside the 24h window: must not be counted.
    $this->travelTo('2026-08-16 12:00:00');
    inboundMessageFixture(['status' => EmailStatus::Applied]);

    $this->travelTo('2026-08-18 12:00:00');

    $snapshot = EmailPipelineMetrics::snapshot();

    expect($snapshot->processedLast24h)->toBe(1)
        ->and($snapshot->discardedLast24h)->toBe(1)
        ->and($snapshot->failedLast24h)->toBe(2);
});

test('computes the average processing time from received_at to updated_at for applied inbound messages', function (): void {
    $this->travelTo('2026-08-18 12:00:00');

    inboundMessageFixture([
        'status' => EmailStatus::Applied,
        'received_at' => now()->subMinutes(10),
    ]);
    inboundMessageFixture([
        'status' => EmailStatus::Applied,
        'received_at' => now()->subMinutes(20),
    ]);

    $snapshot = EmailPipelineMetrics::snapshot();

    expect($snapshot->avgProcessingSeconds)->toBe(900.0);
});

test('does not let an applied message without received_at skew the average', function (): void {
    $this->travelTo('2026-08-18 12:00:00');

    inboundMessageFixture(['status' => EmailStatus::Applied, 'received_at' => now()->subMinutes(10)]);
    inboundMessageFixture(['status' => EmailStatus::Applied, 'received_at' => null]);

    expect(EmailPipelineMetrics::snapshot()->avgProcessingSeconds)->toBe(600.0);
});

test('computes bounce rate over attempted outbound sends (bounced + queued), never sent', function (): void {
    outboundMessageFixture(['status' => EmailStatus::Bounced]);
    outboundMessageFixture(['status' => EmailStatus::Queued]);
    outboundMessageFixture(['status' => EmailStatus::Queued]);
    outboundMessageFixture(['status' => EmailStatus::Queued]);
    // Suppressed sends were never attempted: excluded from both terms.
    outboundMessageFixture(['status' => EmailStatus::Suppressed]);

    expect(EmailPipelineMetrics::snapshot()->bounceRate)->toBe(0.25);
});

test('bounce rate is null when no outbound send has ever been attempted', function (): void {
    outboundMessageFixture(['status' => EmailStatus::Suppressed]);

    expect(EmailPipelineMetrics::snapshot()->bounceRate)->toBeNull();
});
