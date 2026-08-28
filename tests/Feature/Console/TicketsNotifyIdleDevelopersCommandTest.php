<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Mailables\IdleDeveloperNoticeMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Enums\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

test('sends a reminder to a developer with assigned tickets and none in progress, within the window', function (): void {
    Carbon::setTestNow('2026-08-28 10:00:00');
    Mail::fake();

    $developer = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket(['assignee_id' => $developer->id, 'status' => TicketStatus::Todo]);

    Artisan::call('tickets:notify-idle-developers');

    Mail::assertQueued(
        IdleDeveloperNoticeMail::class,
        fn (IdleDeveloperNoticeMail $mail): bool => $mail->tickets->count() === 1 && $mail->tickets->first()->is($ticket),
    );
    expect($developer->fresh()->notifications)->toHaveCount(1);
});

test('sends no reminder to a developer with a ticket in progress', function (): void {
    Carbon::setTestNow('2026-08-28 10:00:00');
    Mail::fake();

    $developer = withRole(User::factory()->create(), UserRole::Developer);
    ticket(['assignee_id' => $developer->id, 'status' => TicketStatus::Todo]);
    ticket(['assignee_id' => $developer->id, 'status' => TicketStatus::Progress]);

    Artisan::call('tickets:notify-idle-developers');

    Mail::assertNothingQueued();
    expect($developer->fresh()->notifications)->toBeEmpty();
});

test('sends no reminder to a developer whose only assigned ticket is already closed', function (): void {
    Carbon::setTestNow('2026-08-28 10:00:00');
    Mail::fake();

    $developer = withRole(User::factory()->create(), UserRole::Developer);
    ticket(['assignee_id' => $developer->id, 'status' => TicketStatus::Done]);

    Artisan::call('tickets:notify-idle-developers');

    Mail::assertNothingQueued();
});

test('sends no reminder outside the configured window', function (): void {
    Carbon::setTestNow('2026-08-28 20:00:00');
    Mail::fake();

    $developer = withRole(User::factory()->create(), UserRole::Developer);
    ticket(['assignee_id' => $developer->id, 'status' => TicketStatus::Todo]);

    Artisan::call('tickets:notify-idle-developers');

    Mail::assertNothingQueued();
});

test('does not send a second reminder the same day, even in a later run within the window', function (): void {
    Carbon::setTestNow('2026-08-28 09:00:00');
    Mail::fake();

    $developer = withRole(User::factory()->create(), UserRole::Developer);
    ticket(['assignee_id' => $developer->id, 'status' => TicketStatus::Todo]);

    Artisan::call('tickets:notify-idle-developers');
    Mail::assertQueued(IdleDeveloperNoticeMail::class, 1);

    Carbon::setTestNow('2026-08-28 09:30:00');
    Mail::fake();

    Artisan::call('tickets:notify-idle-developers');
    Mail::assertNothingQueued();
});

test('does not write or send anything in dry-run mode', function (): void {
    Carbon::setTestNow('2026-08-28 10:00:00');
    Mail::fake();

    $developer = withRole(User::factory()->create(), UserRole::Developer);
    ticket(['assignee_id' => $developer->id, 'status' => TicketStatus::Todo]);

    Artisan::call('tickets:notify-idle-developers', ['--dry-run' => true]);

    Mail::assertNothingQueued();
    expect(EmailMessage::query()->count())->toBe(0)
        ->and($developer->fresh()->notifications)->toBeEmpty();
});

test('does not fail and sends no mail when there are no developers', function (): void {
    Carbon::setTestNow('2026-08-28 10:00:00');
    Mail::fake();

    $exitCode = Artisan::call('tickets:notify-idle-developers');

    expect($exitCode)->toBe(0);
    Mail::assertNothingQueued();
});
