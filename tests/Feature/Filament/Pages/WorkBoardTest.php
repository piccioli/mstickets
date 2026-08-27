<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Filament\Pages\WorkBoard;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

test('a customer without ticket view any/assigned permissions cannot access the work board', function (): void {
    $customer = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewOwn), UserRole::Customer);

    $this->actingAs($customer)->get(WorkBoard::getUrl())->assertForbidden();
});

test('a developer with the internal fields permission can access the work board', function (): void {
    $developer = grantTicketPanelRole(
        userWithPermissions(PermissionEnum::TicketManageInternalFields, PermissionEnum::TicketViewAssigned),
        UserRole::Developer,
    );

    $this->actingAs($developer)->get(WorkBoard::getUrl())->assertSuccessful();
});

test('columns group visible tickets by status and hide tickets outside the visibility scope', function (): void {
    $developer = grantTicketPanelRole(
        userWithPermissions(PermissionEnum::TicketManageInternalFields, PermissionEnum::TicketViewAssigned),
        UserRole::Developer,
    );

    $ownTicket = ticket(['title' => 'Il mio ticket', 'status' => TicketStatus::Progress, 'assignee_id' => $developer->id]);
    $otherTicket = ticket(['title' => 'Ticket altrui', 'status' => TicketStatus::Progress]);

    $this->actingAs($developer);

    $columns = Livewire::test(WorkBoard::class)->instance()->columns();

    expect($columns[TicketStatus::Progress->value]->pluck('id'))
        ->toContain($ownTicket->id)
        ->not->toContain($otherTicket->id);

    expect($columns)->toHaveKey(TicketStatus::New->value);
    expect($columns[TicketStatus::New->value])->toHaveCount(0);
});

test('the assignee selector narrows the board to a single colleague', function (): void {
    $manager = grantTicketPanelRole(
        userWithPermissions(PermissionEnum::TicketManageInternalFields, PermissionEnum::TicketViewAny),
        UserRole::Manager,
    );
    $developer = grantTicketPanelRole(User::factory()->create(), UserRole::Developer);
    $otherDeveloper = grantTicketPanelRole(User::factory()->create(['name' => 'Altro Sviluppatore']), UserRole::Developer);

    $assignedToDeveloper = ticket(['status' => TicketStatus::Todo, 'assignee_id' => $developer->id]);
    $assignedToOther = ticket(['status' => TicketStatus::Todo, 'assignee_id' => $otherDeveloper->id]);

    $this->actingAs($manager);

    $columns = Livewire::test(WorkBoard::class)
        ->set('assigneeId', $developer->id)
        ->instance()
        ->columns();

    expect($columns[TicketStatus::Todo->value]->pluck('id'))
        ->toContain($assignedToDeveloper->id)
        ->not->toContain($assignedToOther->id);
});

test('assignee options only list staff members (admin/manager/developer), never customers', function (): void {
    $manager = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketManageInternalFields), UserRole::Manager);
    $developer = grantTicketPanelRole(User::factory()->create(['name' => 'Sviluppatore Test']), UserRole::Developer);
    $customer = grantTicketPanelRole(User::factory()->create(['name' => 'Cliente Test']), UserRole::Customer);

    $this->actingAs($manager);

    $options = Livewire::test(WorkBoard::class)->instance()->assigneeOptions();

    expect($options)->toHaveKey($developer->id)
        ->not->toHaveKey($customer->id);
});

test('client name resolves from the requester organization, falling back to the requester name', function (): void {
    $manager = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketManageInternalFields), UserRole::Manager);

    $organization = Organization::create(['name' => 'Comune di Test']);
    $requesterWithOrg = User::factory()->create(['name' => 'Richiedente Org']);
    $requesterWithOrg->organizations()->attach($organization);
    $requesterWithoutOrg = User::factory()->create(['name' => 'Richiedente Senza Org']);

    $ticketWithOrg = ticket(['requester_id' => $requesterWithOrg->id]);
    $ticketWithoutOrg = ticket(['requester_id' => $requesterWithoutOrg->id]);
    $ticketWithoutRequester = ticket();

    $this->actingAs($manager);

    $page = Livewire::test(WorkBoard::class)->instance();

    expect($page->clientName($ticketWithOrg->fresh()))->toBe('Comune di Test')
        ->and($page->clientName($ticketWithoutOrg->fresh()))->toBe('Richiedente Senza Org')
        ->and($page->clientName($ticketWithoutRequester->fresh()))->toBe('—');
});

test('columns run a constant number of queries regardless of ticket volume (no N+1 per card)', function (): void {
    $developer = grantTicketPanelRole(
        userWithPermissions(PermissionEnum::TicketManageInternalFields, PermissionEnum::TicketViewAny),
        UserRole::Developer,
    );

    $this->actingAs($developer);

    $seedTicketsAcrossStatuses = function (int $perStatus): void {
        foreach (TicketStatus::cases() as $status) {
            foreach (range(1, $perStatus) as $i) {
                $requester = User::factory()->create();
                $organization = Organization::create(['name' => "Org {$status->value}-{$i}"]);
                $requester->organizations()->attach($organization);
                ticket(['status' => $status, 'requester_id' => $requester->id]);
            }
        }
    };

    $page = new WorkBoard;

    // Dataset piccolo: un ticket per stato.
    $seedTicketsAcrossStatuses(1);

    // Chiamata di riscaldamento: la prima invocazione carica/cachea sul model
    // dell'utente autenticato i permessi verificati da `Ticket::scopeVisibleTo()`,
    // che altrimenti sballerebbe il conteggio della prima misura.
    $page->columns();

    DB::enableQueryLog();
    $page->columns();
    $smallDatasetQueryCount = count(DB::getQueryLog());
    DB::flushQueryLog();
    DB::disableQueryLog();

    // Dataset grande: altri 3 ticket per stato (4 per colonna in totale). Log
    // disattivato durante il seeding: qui interessano solo le query di `columns()`.
    $seedTicketsAcrossStatuses(3);

    DB::enableQueryLog();
    $page->columns();
    $largeDatasetQueryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Numero di query costante indipendentemente dal volume di ticket: nessuna query
    // eseguita per singola card (eager loading di requester.organizations/tags).
    expect($largeDatasetQueryCount)->toBe($smallDatasetQueryCount)
        ->and($largeDatasetQueryCount)->toBeLessThanOrEqual(5);
});

test('recent activity only includes logs of tickets visible to the current user', function (): void {
    $developer = grantTicketPanelRole(
        userWithPermissions(PermissionEnum::TicketManageInternalFields, PermissionEnum::TicketViewAssigned),
        UserRole::Developer,
    );

    $ownTicket = ticket(['assignee_id' => $developer->id]);
    $otherTicket = ticket();

    $ownLog = ticketLog($ownTicket);
    $otherLog = ticketLog($otherTicket);

    $this->actingAs($developer);

    $logs = Livewire::test(WorkBoard::class)->instance()->recentActivity();

    expect($logs->pluck('id'))->toContain($ownLog->id)->not->toContain($otherLog->id);
});
