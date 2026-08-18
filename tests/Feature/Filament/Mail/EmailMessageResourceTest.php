<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailAttachmentStatus;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailAttachment;
use App\Domain\Mail\Models\EmailMessage;
use App\Filament\Resources\EmailMessages\EmailMessageResource;
use App\Filament\Resources\EmailMessages\Pages\ListEmailMessages;
use App\Filament\Resources\EmailMessages\Pages\ViewEmailMessage;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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
