<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Listeners\NotifyStaffOfNewCustomerTicketFromWeb;
use App\Domain\Mail\Mailables\NewCustomerTicketStaffMail;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Events\TicketCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('sends E3 when a customer opens a ticket from the web', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $ticket = ticket(['title' => 'Non riesco ad accedere', 'requester_id' => $requester->id]);
    User::factory()->create(['email' => 'staff@example.test']);
    config(['mail_pipeline.staff_notification_group' => ['staff@example.test']]);

    (new NotifyStaffOfNewCustomerTicketFromWeb)->handle(new TicketCreated($ticket, TicketMessageChannel::Web));

    Mail::assertQueued(NewCustomerTicketStaffMail::class);
});

test('does not send E3 for a ticket created via the email channel', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $ticket = ticket(['title' => 'Non riesco ad accedere', 'requester_id' => $requester->id]);
    User::factory()->create(['email' => 'staff@example.test']);
    config(['mail_pipeline.staff_notification_group' => ['staff@example.test']]);

    (new NotifyStaffOfNewCustomerTicketFromWeb)->handle(new TicketCreated($ticket, TicketMessageChannel::Email));

    Mail::assertNothingQueued();
});
