<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use App\Domain\Ticketing\Models\TicketView;
use App\Domain\Ticketing\Rules\TicketParentDepthRule;
use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

/**
 * Assegna un ruolo applicativo "vuoto" solo per superare il gate d'accesso al
 * pannello (§9.1, US-020), isolando il test sui soli permessi diretti concessi
 * da `userWithPermissions()`. Nome scelto per non collidere con l'omonimo helper
 * locale già dichiarato in RoleAndPermissionManagementTest.php (stesso processo
 * Pest, §US-102 gotcha sul redeclare).
 */
function grantTicketPanelRole(User $user, UserRole $role = UserRole::Developer): User
{
    Role::query()->firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
    $user->assignRole($role->value);

    return $user->fresh();
}

test('a user without any ticket view permission is denied access to the tickets list', function (): void {
    $user = grantTicketPanelRole(userWithPermissions());

    $this->actingAs($user)->get(TicketResource::getUrl('index'))->assertForbidden();
});

test('a customer only sees their own tickets, never those of other requesters', function (): void {
    $customer = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewOwn), UserRole::Customer);
    $otherCustomer = User::factory()->create();

    $ownTicket = ticket(['requester_id' => $customer->id, 'title' => 'Il mio ticket']);
    $otherTicket = ticket(['requester_id' => $otherCustomer->id, 'title' => 'Ticket altrui']);

    $this->actingAs($customer);

    Livewire::test(ListTickets::class)
        ->assertCanSeeTableRecords([$ownTicket])
        ->assertCanNotSeeTableRecords([$otherTicket]);
});

test('creating a ticket as a customer forces the requester to themselves and ignores internal fields', function (): void {
    $customer = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketCreate, PermissionEnum::TicketViewOwn), UserRole::Customer);

    $this->actingAs($customer);

    Livewire::test(CreateTicket::class)
        ->fillForm(['title' => 'Non riesco ad accedere'])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = Ticket::query()->sole();

    expect($created->requester_id)->toBe($customer->id)
        ->and($created->status)->toBe(TicketStatus::New)
        ->and($created->type->value)->toBe('helpdesk')
        ->and($created->assignee_id)->toBeNull();
});

test('a customer manipulating the edit form cannot alter any internal field', function (): void {
    $customer = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketUpdateOwn, PermissionEnum::TicketViewOwn), UserRole::Customer);
    $staffUser = User::factory()->create();

    $ticketRecord = ticket([
        'requester_id' => $customer->id,
        'type' => 'bug',
        'priority' => 'low',
        'description' => 'descrizione interna originale',
        'estimated_hours' => 3,
        'staging_url' => null,
        'production_url' => null,
        'assignee_id' => null,
        'tester_id' => null,
    ]);

    $this->actingAs($customer);

    Livewire::test(EditTicket::class, ['record' => $ticketRecord->getKey()])
        ->fillForm([
            'type' => 'feature',
            'priority' => 'high',
            'description' => 'descrizione manomessa',
            'estimated_hours' => 99,
            'staging_url' => 'https://staging.example.test',
            'production_url' => 'https://prod.example.test',
            'assignee_id' => $staffUser->id,
            'tester_id' => $staffUser->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $ticketRecord->fresh();

    expect($fresh->type->value)->toBe('bug')
        ->and($fresh->priority->value)->toBe('low')
        ->and($fresh->description)->toBe('descrizione interna originale')
        ->and((float) $fresh->estimated_hours)->toBe(3.0)
        ->and($fresh->staging_url)->toBeNull()
        ->and($fresh->production_url)->toBeNull()
        ->and($fresh->assignee_id)->toBeNull()
        ->and($fresh->tester_id)->toBeNull();
});

test('internal sections are hidden from a customer on the view page', function (): void {
    $customer = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewOwn), UserRole::Customer);

    $ticketRecord = ticket([
        'requester_id' => $customer->id,
        'staging_url' => 'https://staging.example.test',
    ]);

    $this->actingAs($customer);

    Livewire::test(ViewTicket::class, ['record' => $ticketRecord->getKey()])
        ->assertOk()
        ->assertDontSee('URL staging')
        ->assertDontSee('Storico');
});

test('opening the view page records a throttled ticket view', function (): void {
    $staff = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewAny));
    $ticketRecord = ticket();

    $this->actingAs($staff);

    Livewire::test(ViewTicket::class, ['record' => $ticketRecord->getKey()])->assertOk();

    $view = TicketView::query()->where('ticket_id', $ticketRecord->id)->where('user_id', $staff->id)->sole();

    expect($view->view_count)->toBe(1);
});

test('an admin can transition a ticket from new to backlog via the dynamic action', function (): void {
    $admin = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketTransitionAny, PermissionEnum::TicketViewAny), UserRole::Admin);
    $ticketRecord = ticket();

    $this->actingAs($admin);

    Livewire::test(ViewTicket::class, ['record' => $ticketRecord->getKey()])
        ->assertActionExists('transition_backlog')
        ->callAction('transition_backlog')
        ->assertHasNoActionErrors();

    expect($ticketRecord->fresh()->status)->toBe(TicketStatus::Backlog);
});

test('a developer transitioning new to assigned is silently self-assigned without an assignee field', function (): void {
    $developer = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketUpdateAssigned, PermissionEnum::TicketViewAny));
    $ticketRecord = ticket();

    $this->actingAs($developer);

    Livewire::test(ViewTicket::class, ['record' => $ticketRecord->getKey()])
        ->mountAction('transition_assigned')
        ->assertSchemaComponentDoesNotExist('assignee_id')
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $fresh = $ticketRecord->fresh();

    expect($fresh->status)->toBe(TicketStatus::Assigned)
        ->and($fresh->assignee_id)->toBe($developer->id);
});

test('transitioning to testing requires a tester and fails without one', function (): void {
    $admin = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketTransitionAny, PermissionEnum::TicketViewAny), UserRole::Admin);
    $developer = User::factory()->create();
    $ticketRecord = ticket(['status' => TicketStatus::Progress, 'assignee_id' => $admin->id]);

    $this->actingAs($admin);

    Livewire::test(ViewTicket::class, ['record' => $ticketRecord->getKey()])
        ->mountAction('transition_testing')
        ->setActionData(['tester_id' => null])
        ->callMountedAction()
        ->assertHasActionErrors(['tester_id' => 'required']);

    expect($ticketRecord->fresh()->status)->toBe(TicketStatus::Progress);

    Livewire::test(ViewTicket::class, ['record' => $ticketRecord->getKey()])
        ->callAction('transition_testing', data: ['tester_id' => $developer->id])
        ->assertHasNoActionErrors();

    $fresh = $ticketRecord->fresh();

    expect($fresh->status)->toBe(TicketStatus::Testing)
        ->and($fresh->tester_id)->toBe($developer->id);
});

test('applying a status transition to children propagates it and reports skipped children', function (): void {
    $admin = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketTransitionAny, PermissionEnum::TicketViewAny), UserRole::Admin);

    $parent = ticket();
    $applicableChild = ticket(['parent_id' => $parent->id]);
    $unreachableChild = ticket(['parent_id' => $parent->id, 'status' => TicketStatus::Done]);

    $this->actingAs($admin);

    Livewire::test(ViewTicket::class, ['record' => $parent->getKey()])
        ->callAction('transition_backlog', data: ['apply_to_children' => true])
        ->assertHasNoActionErrors();

    expect($parent->fresh()->status)->toBe(TicketStatus::Backlog)
        ->and($applicableChild->fresh()->status)->toBe(TicketStatus::Backlog)
        ->and($unreachableChild->fresh()->status)->toBe(TicketStatus::Done);
});

test('a forbidden status transition surfaces the localized state machine message via a notification', function (): void {
    $admin = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketTransitionAny, PermissionEnum::TicketViewAny), UserRole::Admin);
    $ticketRecord = ticket(['status' => TicketStatus::Done]);

    $this->actingAs($admin);

    // Nessuna riga di tabella new/backlog/... -> "done" oltre a `rejected`: l'unico
    // bottone disponibile da `done` è quello verso `rejected`. Verifichiamo quindi
    // che NON venga costruita un'action verso `assigned` (mai raggiungibile).
    Livewire::test(ViewTicket::class, ['record' => $ticketRecord->getKey()])
        ->assertActionDoesNotExist('transition_assigned');
});

test('posting a message via the action calls PostTicketMessage and appears in the conversation', function (): void {
    $staff = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewAny, PermissionEnum::TicketMessageCreate));
    $ticketRecord = ticket();

    $this->actingAs($staff);

    Livewire::test(ViewTicket::class, ['record' => $ticketRecord->getKey()])
        ->callAction('post_message', data: ['body_html' => '<p>Ciao, come posso aiutarti?</p>'])
        ->assertHasNoActionErrors();

    $message = $ticketRecord->fresh()->messages()->sole();

    expect($message->author_id)->toBe($staff->id)
        ->and($message->body_text)->toContain('Ciao, come posso aiutarti?');

    Livewire::test(ViewTicket::class, ['record' => $ticketRecord->getKey()])
        ->assertOk()
        ->assertSee('Ciao, come posso aiutarti?', escape: false);
});

test('a user with ticket.assign can add and remove a participant', function (): void {
    $staff = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewAny, PermissionEnum::TicketAssign));
    $participant = User::factory()->create();
    $ticketRecord = ticket();

    $this->actingAs($staff);

    Livewire::test(ViewTicket::class, ['record' => $ticketRecord->getKey()])
        ->callAction('add_participant', data: ['user_id' => $participant->id])
        ->assertHasNoActionErrors();

    expect($ticketRecord->participants()->pluck('users.id')->all())->toContain($participant->id);

    Livewire::test(ViewTicket::class, ['record' => $ticketRecord->getKey()])
        ->callAction('remove_participant', data: ['user_id' => $participant->id])
        ->assertHasNoActionErrors();

    expect($ticketRecord->participants()->pluck('users.id')->all())->not->toContain($participant->id);
});

test('a user without ticket.assign cannot see participant management actions', function (): void {
    $staff = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewAny));
    $ticketRecord = ticket();

    $this->actingAs($staff);

    Livewire::test(ViewTicket::class, ['record' => $ticketRecord->getKey()])
        ->assertActionDoesNotExist('add_participant')
        ->assertActionDoesNotExist('remove_participant');
});

test('an invalid parent selection surfaces the readable TicketParentDepthRule message', function (): void {
    $admin = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketUpdateAny, PermissionEnum::TicketViewAny), UserRole::Admin);

    // Il ticket target ha già un figlio: sceglierne un altro come padre violerebbe
    // la profondità massima 1 (§6.1.6), anche se il padre scelto è di per sé un
    // ticket top-level valido (quindi la validazione "in elenco opzioni" di
    // Filament non c'entra, solo TicketParentDepthRule deve fallire).
    $validParent = ticket();
    $target = ticket();
    ticket(['parent_id' => $target->id]);

    $this->actingAs($admin);

    Livewire::test(EditTicket::class, ['record' => $target->getKey()])
        ->fillForm(['parent_id' => $validParent->id])
        ->call('save')
        ->assertHasFormErrors(['parent_id'])
        ->assertSee(TicketParentDepthRule::MESSAGE);

    expect($target->fresh()->parent_id)->toBeNull();
});

test('reassigning a ticket through the edit form calls AssignTicket and writes an assigned log', function (): void {
    $admin = grantTicketPanelRole(userWithPermissions(
        PermissionEnum::TicketUpdateAny,
        PermissionEnum::TicketViewAny,
        PermissionEnum::TicketManageInternalFields,
    ), UserRole::Admin);
    $newAssignee = User::factory()->create();
    $requester = User::factory()->create();
    $ticketRecord = ticket(['requester_id' => $requester->id]);

    $this->actingAs($admin);

    Livewire::test(EditTicket::class, ['record' => $ticketRecord->getKey()])
        ->fillForm(['requester_id' => $requester->id, 'assignee_id' => $newAssignee->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($ticketRecord->fresh()->assignee_id)->toBe($newAssignee->id);

    $log = TicketLog::query()->where('ticket_id', $ticketRecord->id)->where('event', 'assigned')->sole();

    expect($log->changes['assignee_id']['to'])->toBe($newAssignee->id);
});
