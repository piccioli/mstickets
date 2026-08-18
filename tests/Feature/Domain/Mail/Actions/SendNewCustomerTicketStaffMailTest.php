<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\SendNewCustomerTicketStaffMail;
use App\Domain\Mail\Mailables\NewCustomerTicketStaffMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('notifies every resolved staff recipient by email and in-app when the requester is a customer', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $ticket = ticket(['title' => 'Non riesco ad accedere', 'requester_id' => $requester->id]);

    $staffOne = User::factory()->create(['email' => 'staff-one@example.test']);
    $staffTwo = User::factory()->create(['email' => 'staff-two@example.test']);
    config(['mail_pipeline.staff_notification_group' => ['staff-one@example.test', 'staff-two@example.test']]);

    SendNewCustomerTicketStaffMail::run($ticket);

    Mail::assertQueued(NewCustomerTicketStaffMail::class, 2);
    Mail::assertQueued(NewCustomerTicketStaffMail::class, fn (NewCustomerTicketStaffMail $mail): bool => $mail->ticket->is($ticket));

    expect($staffOne->fresh()->notifications)->toHaveCount(1)
        ->and($staffTwo->fresh()->notifications)->toHaveCount(1);
});

test('does not notify anyone when the requester does not have the customer role', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket(['title' => 'Task interno', 'requester_id' => $requester->id]);

    $staff = User::factory()->create(['email' => 'staff@example.test']);
    config(['mail_pipeline.staff_notification_group' => ['staff@example.test']]);

    SendNewCustomerTicketStaffMail::run($ticket);

    Mail::assertNothingQueued();
    expect($staff->fresh()->notifications)->toBeEmpty();
});

test('does not notify anyone when the ticket has no requester', function (): void {
    Mail::fake();

    $ticket = ticket(['title' => 'Senza richiedente']);

    $staff = User::factory()->create(['email' => 'staff@example.test']);
    config(['mail_pipeline.staff_notification_group' => ['staff@example.test']]);

    SendNewCustomerTicketStaffMail::run($ticket);

    Mail::assertNothingQueued();
    expect($staff->fresh()->notifications)->toBeEmpty();
});

test('changing the staff group in config changes the recipients without touching the mailable or listener', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $ticket = ticket(['title' => 'Non riesco ad accedere', 'requester_id' => $requester->id]);

    User::factory()->create(['email' => 'staff-a@example.test']);
    config(['mail_pipeline.staff_notification_group' => ['staff-a@example.test']]);
    SendNewCustomerTicketStaffMail::run($ticket);
    Mail::assertQueued(NewCustomerTicketStaffMail::class, 1);

    Mail::fake();
    User::factory()->create(['email' => 'staff-b@example.test']);
    User::factory()->create(['email' => 'staff-c@example.test']);
    config(['mail_pipeline.staff_notification_group' => ['staff-b@example.test', 'staff-c@example.test']]);
    SendNewCustomerTicketStaffMail::run($ticket);
    Mail::assertQueued(NewCustomerTicketStaffMail::class, 2);
});
