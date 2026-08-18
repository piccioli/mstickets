<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Events\InboundEmailApplied;
use App\Domain\Mail\Listeners\NotifyStaffOfNewCustomerTicketFromEmail;
use App\Domain\Mail\Mailables\NewCustomerTicketStaffMail;
use App\Domain\Mail\Models\EmailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function newTicketInboundEmail(): EmailMessage
{
    return EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'from_email' => 'cliente@example.test',
        'status' => EmailStatus::Applied,
        'subject' => 'Non riesco ad accedere',
    ]);
}

test('sends E3 when the inbound email applied a new ticket for a customer', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(['email' => 'cliente@example.test']), UserRole::Customer);
    $ticket = ticket(['title' => 'Non riesco ad accedere', 'requester_id' => $requester->id]);
    User::factory()->create(['email' => 'staff@example.test']);
    config(['mail_pipeline.staff_notification_group' => ['staff@example.test']]);

    (new NotifyStaffOfNewCustomerTicketFromEmail)->handle(new InboundEmailApplied($ticket, newTicketInboundEmail(), isNewTicket: true));

    Mail::assertQueued(NewCustomerTicketStaffMail::class);
});

test('does not send E3 when the inbound email applied to an existing ticket', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(['email' => 'cliente@example.test']), UserRole::Customer);
    $ticket = ticket(['title' => 'Non riesco ad accedere', 'requester_id' => $requester->id]);
    User::factory()->create(['email' => 'staff@example.test']);
    config(['mail_pipeline.staff_notification_group' => ['staff@example.test']]);

    (new NotifyStaffOfNewCustomerTicketFromEmail)->handle(new InboundEmailApplied($ticket, newTicketInboundEmail(), isNewTicket: false));

    Mail::assertNothingQueued();
});
