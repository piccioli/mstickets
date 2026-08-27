<?php

declare(strict_types=1);

use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Reporting\Models\ActivityReport;
use App\Domain\Reporting\Services\ActivityReportSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('syncTickets selects only the owner user tickets done within the period', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $inPeriod = ticket(['requester_id' => $owner->id, 'done_at' => '2026-02-15 10:00:00']);
    ticket(['requester_id' => $owner->id, 'done_at' => '2026-01-31 23:59:58']);
    ticket(['requester_id' => $owner->id, 'done_at' => '2026-03-01 00:00:01']);
    ticket(['requester_id' => $owner->id, 'done_at' => null]);
    ticket(['requester_id' => $otherUser->id, 'done_at' => '2026-02-10 10:00:00']);

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
        'locale' => 'it',
    ]);

    (new ActivityReportSyncService)->syncTickets($report);

    expect($report->tickets()->pluck('tickets.id')->all())->toBe([$inPeriod->id]);
});

test('syncTickets selects tickets requested by any member of the owner organization', function (): void {
    $organization = Organization::create(['name' => 'CAI Sezione Test']);
    $member = User::factory()->create();
    $organization->users()->attach($member);
    $nonMember = User::factory()->create();

    $inPeriod = ticket(['requester_id' => $member->id, 'done_at' => '2026-05-10 08:00:00']);
    ticket(['requester_id' => $nonMember->id, 'done_at' => '2026-05-10 08:00:00']);
    ticket(['requester_id' => $member->id, 'done_at' => '2026-06-01 00:00:00']);

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::Organization,
        'owner_organization_id' => $organization->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 5,
        'locale' => 'it',
    ]);

    (new ActivityReportSyncService)->syncTickets($report);

    expect($report->tickets()->pluck('tickets.id')->all())->toBe([$inPeriod->id]);
});

test('syncTickets is idempotent when invoked twice in a row', function (): void {
    $owner = User::factory()->create();
    $inPeriod = ticket(['requester_id' => $owner->id, 'done_at' => '2026-02-15 10:00:00']);

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
        'locale' => 'it',
    ]);

    $service = new ActivityReportSyncService;
    $service->syncTickets($report);
    $service->syncTickets($report);

    expect($report->tickets()->pluck('tickets.id')->all())->toBe([$inPeriod->id]);
});

test('syncTickets reflects a ticket leaving the period on re-sync (no stale rows)', function (): void {
    $owner = User::factory()->create();
    $ticket = ticket(['requester_id' => $owner->id, 'done_at' => '2026-02-15 10:00:00']);

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
        'locale' => 'it',
    ]);

    $service = new ActivityReportSyncService;
    $service->syncTickets($report);

    expect($report->tickets()->pluck('tickets.id')->all())->toBe([$ticket->id]);

    $ticket->update(['done_at' => '2026-03-01 00:00:00']);
    $service->syncTickets($report);

    expect($report->tickets()->pluck('tickets.id')->all())->toBe([]);
});

test('syncTickets detaches every ticket when the owner is unresolvable', function (): void {
    $owner = User::factory()->create();
    ticket(['requester_id' => $owner->id, 'done_at' => '2026-02-15 10:00:00']);

    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 2,
        'locale' => 'it',
    ]);

    (new ActivityReportSyncService)->syncTickets($report);
    expect($report->tickets()->count())->toBe(1);

    $owner->delete();
    $report->refresh();

    (new ActivityReportSyncService)->syncTickets($report);

    expect($report->tickets()->count())->toBe(0);
});
