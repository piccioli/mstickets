<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailAttachmentStatus;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Mailables\NewCustomerTicketStaffMail;
use App\Domain\Mail\Models\EmailAttachment;
use App\Domain\Mail\Models\EmailMessage;
use App\Filament\Pages\EmailQuarantine;
use App\Filament\Resources\EmailMessages\EmailMessageResource;
use App\Filament\Resources\EmailMessages\Pages\ListEmailMessages;
use App\Filament\Resources\EmailMessages\Pages\ViewEmailMessage;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

/**
 * Assegna un ruolo applicativo "vuoto" solo per superare il gate d'accesso al pannello
 * (§9.1, US-020): isola i test sui soli permessi diretti concessi da userWithPermissions(),
 * riprendendo la stessa convenzione di RoleAndPermissionManagementTest.php (US-021).
 */
function grantEmailPanelAccess(User $user): User
{
    Role::query()->firstOrCreate(['name' => UserRole::Developer->value, 'guard_name' => 'web']);
    $user->assignRole(UserRole::Developer->value);

    return $user->fresh();
}

function emailMessageFixture(array $attributes = []): EmailMessage
{
    return EmailMessage::create(array_merge([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Received,
        'from_email' => 'mittente@example.com',
    ], $attributes))->fresh();
}

test('a user without email.view is denied access to the email messages resource', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions());

    expect(EmailMessageResource::canViewAny())->toBeFalse();

    $this->actingAs($user)->get(EmailMessageResource::getUrl('index'))->assertForbidden();
});

test('a user with email.view can access the email messages registry', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailView));

    $this->actingAs($user);

    expect(EmailMessageResource::canViewAny())->toBeTrue();

    $this->get(EmailMessageResource::getUrl('index'))->assertOk();
});

test('the email messages resource has no create or edit page', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailView));

    expect(Route::has('filament.admin.resources.email-messages.create'))->toBeFalse()
        ->and(Route::has('filament.admin.resources.email-messages.edit'))->toBeFalse();

    $this->actingAs($user);

    Livewire::test(ListEmailMessages::class)->assertOk();
});

test('the registry lists a message and its direction and status', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailView));
    $message = emailMessageFixture(['subject' => 'Richiesta di assistenza']);

    $this->actingAs($user);

    Livewire::test(ListEmailMessages::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$message])
        ->assertSee('Richiesta di assistenza');
});

test('the table is filterable by direction', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailView));
    $inbound = emailMessageFixture(['direction' => EmailDirection::Inbound]);
    $outbound = emailMessageFixture(['direction' => EmailDirection::Outbound, 'status' => EmailStatus::Queued]);

    $this->actingAs($user);

    Livewire::test(ListEmailMessages::class)
        ->filterTable('direction', EmailDirection::Outbound->value)
        ->assertCanSeeTableRecords([$outbound])
        ->assertCanNotSeeTableRecords([$inbound]);
});

test('the table is filterable by status', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailView));
    $quarantined = emailMessageFixture(['status' => EmailStatus::Quarantined]);
    $applied = emailMessageFixture(['status' => EmailStatus::Applied]);

    $this->actingAs($user);

    Livewire::test(ListEmailMessages::class)
        ->filterTable('status', [EmailStatus::Quarantined->value])
        ->assertCanSeeTableRecords([$quarantined])
        ->assertCanNotSeeTableRecords([$applied]);
});

test('the table is filterable by linked ticket', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailView));
    $ticketA = ticket(['title' => 'Errore stampante']);
    $ticketB = ticket(['title' => 'Richiesta accesso']);
    $linkedToA = emailMessageFixture(['ticket_id' => $ticketA->id]);
    $linkedToB = emailMessageFixture(['ticket_id' => $ticketB->id]);

    $this->actingAs($user);

    Livewire::test(ListEmailMessages::class)
        ->filterTable('ticket_id', $ticketA->id)
        ->assertCanSeeTableRecords([$linkedToA])
        ->assertCanNotSeeTableRecords([$linkedToB]);
});

test('viewing a message shows headers, body, attachments and diagnostics', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailView));
    $ticket = ticket(['title' => 'Problema di accesso']);
    $message = emailMessageFixture([
        'from_name' => 'Mario Rossi',
        'from_email' => 'mario.rossi@example.com',
        'to' => ['supporto@example.com'],
        'subject' => 'Non riesco ad accedere',
        'message_id' => '<abc123@example.com>',
        'body_text' => 'Testo semplice del messaggio',
        'body_html' => '<p>Corpo <strong>HTML</strong></p>',
        'ticket_id' => $ticket->id,
        'attempts' => 2,
        'failure_reason' => 'Timeout SMTP',
    ]);

    EmailAttachment::create([
        'email_message_id' => $message->id,
        'filename' => 'documento.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 2048,
        'disk' => 'local',
        'path' => 'attachments/documento.pdf',
        'status' => EmailAttachmentStatus::Stored,
    ]);

    $this->actingAs($user);

    Livewire::test(ViewEmailMessage::class, ['record' => $message->getKey()])
        ->assertOk()
        ->assertSee('Mario Rossi')
        ->assertSee('mario.rossi@example.com')
        ->assertSee('supporto@example.com')
        ->assertSee('Non riesco ad accedere')
        ->assertSee('<abc123@example.com>')
        ->assertSee('Testo semplice del messaggio')
        ->assertSee('Corpo')
        ->assertSee('documento.pdf')
        ->assertSee('Problema di accesso')
        ->assertSee('Timeout SMTP');
});

test('a user with only email.view cannot see the administrative actions', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailView));
    $message = emailMessageFixture(['status' => EmailStatus::Failed]);

    $this->actingAs($user);

    Livewire::test(ViewEmailMessage::class, ['record' => $message->getKey()])
        ->assertActionHidden('reprocess')
        ->assertActionHidden('assign_sender')
        ->assertActionHidden('link_to_ticket')
        ->assertActionHidden('discard')
        ->assertActionHidden('resend');
});

test('an admin can reprocess a message via the action', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailView, PermissionEnum::EmailManage));
    User::factory()->create(['email' => 'mittente@example.com']);
    $message = emailMessageFixture(['status' => EmailStatus::Failed, 'subject' => 'Richiesta']);

    $this->actingAs($user);

    Livewire::test(ViewEmailMessage::class, ['record' => $message->getKey()])
        ->assertActionExists('reprocess')
        ->callAction('reprocess')
        ->assertHasNoActionErrors();

    expect($message->fresh()->status)->toBe(EmailStatus::Applied);
});

test('an admin can assign a sender to a quarantined message via the action', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailView, PermissionEnum::EmailManage));
    $sender = User::factory()->create();
    $message = emailMessageFixture(['status' => EmailStatus::Quarantined]);

    $this->actingAs($user);

    Livewire::test(ViewEmailMessage::class, ['record' => $message->getKey()])
        ->callAction('assign_sender', data: ['user_id' => $sender->id])
        ->assertHasNoActionErrors();

    expect($message->fresh()->status)->toBe(EmailStatus::Applied)
        ->and($message->fresh()->user_id)->toBe($sender->id);
});

test('an admin can link a message to a different ticket via the action', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailView, PermissionEnum::EmailManage));
    $sender = User::factory()->create();
    $targetTicket = ticket(['requester_id' => $sender->id]);
    $message = emailMessageFixture(['status' => EmailStatus::Classified, 'user_id' => $sender->id, 'body_html' => '<p>Corpo</p>']);

    $this->actingAs($user);

    Livewire::test(ViewEmailMessage::class, ['record' => $message->getKey()])
        ->callAction('link_to_ticket', data: ['ticket_id' => $targetTicket->id])
        ->assertHasNoActionErrors();

    expect($message->fresh()->ticket_id)->toBe($targetTicket->id)
        ->and($message->fresh()->status)->toBe(EmailStatus::Applied);
});

test('an admin can discard a message via the action', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailView, PermissionEnum::EmailManage));
    $message = emailMessageFixture(['status' => EmailStatus::Classified]);

    $this->actingAs($user);

    Livewire::test(ViewEmailMessage::class, ['record' => $message->getKey()])
        ->callAction('discard', data: ['reason' => 'Spam confermato'])
        ->assertHasNoActionErrors();

    expect($message->fresh()->status)->toBe(EmailStatus::Discarded)
        ->and($message->fresh()->failure_reason)->toBe('Spam confermato');
});

test('an admin can resend a failed outbound message via the action', function (): void {
    Mail::fake();

    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailView, PermissionEnum::EmailManage));
    $recipient = User::factory()->create();
    $recipientTicket = ticket(['requester_id' => $recipient->id]);

    $outbound = EmailMessage::query()->forceCreate([
        'ulid' => strtolower((string) Str::ulid()),
        'direction' => EmailDirection::Outbound,
        'message_id' => 'resend-test@example.com',
        'ticket_id' => $recipientTicket->id,
        'user_id' => $recipient->id,
        'from_email' => 'staff@example.com',
        'to' => [$recipient->email],
        'subject' => 'Notifica',
        'status' => EmailStatus::Failed,
        'mailable_class' => NewCustomerTicketStaffMail::class,
    ]);

    $this->actingAs($user);

    Livewire::test(ViewEmailMessage::class, ['record' => $outbound->getKey()])
        ->callAction('resend')
        ->assertHasNoActionErrors();

    expect($outbound->fresh()->status)->toBe(EmailStatus::Queued);

    Mail::assertQueued(NewCustomerTicketStaffMail::class);
});

test('a user without email.manage is denied access to the quarantine page', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailView));

    expect(EmailQuarantine::canAccess())->toBeFalse();
});

test('the quarantine page lists only quarantined inbound messages', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailManage));
    $quarantined = emailMessageFixture(['status' => EmailStatus::Quarantined]);
    $applied = emailMessageFixture(['status' => EmailStatus::Applied]);

    $this->actingAs($user);

    expect(EmailQuarantine::canAccess())->toBeTrue();

    Livewire::test(EmailQuarantine::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$quarantined])
        ->assertCanNotSeeTableRecords([$applied]);
});

test('the quarantine page can associate an existing user and reprocess the message', function (): void {
    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailManage));
    $sender = User::factory()->create();
    $message = emailMessageFixture(['status' => EmailStatus::Quarantined]);

    $this->actingAs($user);

    Livewire::test(EmailQuarantine::class)
        ->callTableAction('assign_existing', $message, data: ['user_id' => $sender->id])
        ->assertHasNoTableActionErrors();

    expect($message->fresh()->status)->toBe(EmailStatus::Applied)
        ->and($message->fresh()->user_id)->toBe($sender->id);
});

test('the quarantine page can create a new user and reprocess the message', function (): void {
    Role::query()->firstOrCreate(['name' => UserRole::Customer->value, 'guard_name' => 'web']);

    $user = grantEmailPanelAccess(userWithPermissions(PermissionEnum::EmailManage));
    $message = emailMessageFixture([
        'status' => EmailStatus::Quarantined,
        'from_email' => 'nuovo.cliente@example.com',
        'from_name' => 'Nuovo Cliente',
    ]);

    $this->actingAs($user);

    Livewire::test(EmailQuarantine::class)
        ->callTableAction('create_and_assign', $message, data: ['name' => 'Nuovo Cliente', 'email' => 'nuovo.cliente@example.com'])
        ->assertHasNoTableActionErrors();

    expect($message->fresh()->status)->toBe(EmailStatus::Applied);

    $sender = User::query()->where('email', 'nuovo.cliente@example.com')->sole();
    expect($sender->hasRole(UserRole::Customer->value))->toBeTrue();
});
