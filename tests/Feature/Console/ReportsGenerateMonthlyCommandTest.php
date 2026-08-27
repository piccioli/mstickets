<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Jobs\GenerateActivityReportPdfJob;
use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Queue::fake();
    $this->travelTo('2026-03-01 12:00:00');
});

test('--dry-run examines active owners without creating any report or queuing any pdf', function (): void {
    $customer = grantTicketPanelRole(User::factory()->create(), UserRole::Customer);
    ticket(['requester_id' => $customer->id, 'done_at' => '2026-02-15 10:00:00']);

    $this->artisan('reports:generate-monthly', ['--dry-run' => true])->assertSuccessful();

    expect(ActivityReport::count())->toBe(0);
    Queue::assertNothingPushed();
});

test('creates a monthly report for a customer with a ticket done in the previous month and queues its pdf', function (): void {
    $customer = grantTicketPanelRole(User::factory()->create(), UserRole::Customer);
    ticket(['requester_id' => $customer->id, 'done_at' => '2026-02-15 10:00:00']);

    $this->artisan('reports:generate-monthly')->assertSuccessful();

    $report = ActivityReport::query()->sole();

    expect($report->owner_kind)->toBe(ActivityReportOwnerKind::User)
        ->and($report->owner_user_id)->toBe($customer->id)
        ->and($report->year)->toBe(2026)
        ->and($report->month)->toBe(2);

    Queue::assertPushed(GenerateActivityReportPdfJob::class, fn (GenerateActivityReportPdfJob $job): bool => $job->activityReportId === $report->id);
});

test('creates a monthly report for an organization whose member has a ticket done in the previous month', function (): void {
    $organization = Organization::create(['name' => 'CAI Sezione Test', 'locale' => 'it']);
    $member = grantTicketPanelRole(User::factory()->create(), UserRole::Customer);
    $organization->users()->attach($member);
    ticket(['requester_id' => $member->id, 'done_at' => '2026-02-20 10:00:00']);

    $this->artisan('reports:generate-monthly')->assertSuccessful();

    // Il membro è a sua volta un owner attivo (cliente con un ticket nel periodo):
    // il comando genera correttamente sia il report dell'utente sia quello
    // dell'organizzazione, due owner distinti per lo stesso ticket sottostante.
    expect(ActivityReport::count())->toBe(2);

    $organizationReport = ActivityReport::query()
        ->where('owner_kind', ActivityReportOwnerKind::Organization)
        ->sole();

    expect($organizationReport->owner_organization_id)->toBe($organization->id)
        ->and($organizationReport->month)->toBe(2);
});

test('a customer without any ticket done in the previous month is not considered an active owner', function (): void {
    grantTicketPanelRole(User::factory()->create(), UserRole::Customer);

    $this->artisan('reports:generate-monthly')->assertSuccessful();

    expect(ActivityReport::count())->toBe(0);
    Queue::assertNothingPushed();
});

test('re-running the command does not duplicate a report already created for the same owner and period', function (): void {
    $customer = grantTicketPanelRole(User::factory()->create(), UserRole::Customer);
    ticket(['requester_id' => $customer->id, 'done_at' => '2026-02-15 10:00:00']);

    $this->artisan('reports:generate-monthly')->assertSuccessful();
    $this->artisan('reports:generate-monthly')->assertSuccessful();

    expect(ActivityReport::count())->toBe(1);
    Queue::assertPushed(GenerateActivityReportPdfJob::class, 1);
});
