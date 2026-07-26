<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

function staffUser(): User
{
    return grantTicketPanelRole(
        userWithPermissions(PermissionEnum::TicketViewAny, PermissionEnum::TicketManageInternalFields),
        UserRole::Admin,
    );
}

test('status filter accepts multiple values', function (): void {
    $staff = staffUser();
    $new = ticket(['status' => TicketStatus::New]);
    $backlog = ticket(['status' => TicketStatus::Backlog]);
    $done = ticket(['status' => TicketStatus::Done]);

    Livewire::actingAs($staff)->test(ListTickets::class)
        ->filterTable('status', [TicketStatus::New, TicketStatus::Backlog])
        ->assertCanSeeTableRecords([$new, $backlog])
        ->assertCanNotSeeTableRecords([$done]);
});

test('type filter is hidden from customers and filters by type for staff', function (): void {
    $staff = staffUser();
    $bug = ticket(['type' => TicketType::Bug]);
    $feature = ticket(['type' => TicketType::Feature]);

    Livewire::actingAs($staff)->test(ListTickets::class)
        ->assertTableFilterVisible('type')
        ->filterTable('type', TicketType::Bug)
        ->assertCanSeeTableRecords([$bug])
        ->assertCanNotSeeTableRecords([$feature]);

    $customer = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewOwn), UserRole::Customer);

    Livewire::actingAs($customer)->test(ListTickets::class)
        ->assertTableFilterHidden('type');
});

test('priority filter narrows the list by priority', function (): void {
    $staff = staffUser();
    $high = ticket(['priority' => TicketPriority::High]);
    $low = ticket(['priority' => TicketPriority::Low]);

    Livewire::actingAs($staff)->test(ListTickets::class)
        ->filterTable('priority', TicketPriority::High)
        ->assertCanSeeTableRecords([$high])
        ->assertCanNotSeeTableRecords([$low]);
});

test('assignee filter narrows the list by assignee', function (): void {
    $staff = staffUser();
    $developer = User::factory()->create();
    $otherDeveloper = User::factory()->create();
    $assigned = ticket(['assignee_id' => $developer->id]);
    $otherAssigned = ticket(['assignee_id' => $otherDeveloper->id]);

    Livewire::actingAs($staff)->test(ListTickets::class)
        ->filterTable('assignee_id', $developer)
        ->assertCanSeeTableRecords([$assigned])
        ->assertCanNotSeeTableRecords([$otherAssigned]);
});

test('tester filter narrows the list by tester', function (): void {
    $staff = staffUser();
    $tester = User::factory()->create();
    $otherTester = User::factory()->create();
    $tested = ticket(['tester_id' => $tester->id]);
    $otherTested = ticket(['tester_id' => $otherTester->id]);

    Livewire::actingAs($staff)->test(ListTickets::class)
        ->filterTable('tester_id', $tester)
        ->assertCanSeeTableRecords([$tested])
        ->assertCanNotSeeTableRecords([$otherTested]);
});

test('requester filter narrows the list by requester and is visible to everyone', function (): void {
    $staff = staffUser();
    $requester = User::factory()->create();
    $otherRequester = User::factory()->create();
    $ownTicket = ticket(['requester_id' => $requester->id]);
    $otherTicket = ticket(['requester_id' => $otherRequester->id]);

    Livewire::actingAs($staff)->test(ListTickets::class)
        ->assertTableFilterVisible('requester_id')
        ->filterTable('requester_id', $requester)
        ->assertCanSeeTableRecords([$ownTicket])
        ->assertCanNotSeeTableRecords([$otherTicket]);
});

test('organization filter narrows the list by the requester organization', function (): void {
    $staff = staffUser();
    $organization = Organization::create(['name' => 'Comune di Test']);
    $otherOrganization = Organization::create(['name' => 'Altro Comune']);
    $requester = User::factory()->create();
    $otherRequester = User::factory()->create();
    $requester->organizations()->attach($organization);
    $otherRequester->organizations()->attach($otherOrganization);
    $ticketInOrg = ticket(['requester_id' => $requester->id]);
    $ticketInOtherOrg = ticket(['requester_id' => $otherRequester->id]);

    Livewire::actingAs($staff)->test(ListTickets::class)
        ->filterTable('organization_id', $organization)
        ->assertCanSeeTableRecords([$ticketInOrg])
        ->assertCanNotSeeTableRecords([$ticketInOtherOrg]);
});

test('tag filter narrows the list by tag', function (): void {
    $staff = staffUser();
    $tag = tag();
    $otherTag = tag();
    $tagged = ticket();
    $tagged->tags()->attach($tag);
    $taggedOther = ticket();
    $taggedOther->tags()->attach($otherTag);

    Livewire::actingAs($staff)->test(ListTickets::class)
        ->filterTable('tags', $tag)
        ->assertCanSeeTableRecords([$tagged])
        ->assertCanNotSeeTableRecords([$taggedOther]);
});

test('without tags filter shows only tickets with no tag', function (): void {
    $staff = staffUser();
    $untagged = ticket();
    $tagged = ticket();
    $tagged->tags()->attach(tag());

    Livewire::actingAs($staff)->test(ListTickets::class)
        ->filterTable('without_tags', true)
        ->assertCanSeeTableRecords([$untagged])
        ->assertCanNotSeeTableRecords([$tagged]);
});

test('multiple tags filter shows only tickets with more than one tag', function (): void {
    $staff = staffUser();
    $withOneTag = ticket();
    $withOneTag->tags()->attach(tag());
    $withTwoTags = ticket();
    $withTwoTags->tags()->attach([tag()->id, tag()->id]);

    Livewire::actingAs($staff)->test(ListTickets::class)
        ->filterTable('multiple_tags', true)
        ->assertCanSeeTableRecords([$withTwoTags])
        ->assertCanNotSeeTableRecords([$withOneTag]);
});

test('tag name pattern filter matches tickets whose tag name contains the pattern', function (): void {
    $staff = staffUser();
    $matching = ticket();
    $matching->tags()->attach(tag(['name' => 'Rifacimento sito - T1 2026']));
    $notMatching = ticket();
    $notMatching->tags()->attach(tag(['name' => 'Manutenzione - T2 2026']));

    Livewire::actingAs($staff)->test(ListTickets::class)
        ->filterTable('tag_name_pattern', ['pattern' => 'T1 2026'])
        ->assertCanSeeTableRecords([$matching])
        ->assertCanNotSeeTableRecords([$notMatching]);
});

test('period filter narrows the list by creation date range', function (): void {
    $staff = staffUser();
    $inRange = ticket();
    $inRange->forceFill(['created_at' => '2026-02-15'])->save();
    $outOfRange = ticket();
    $outOfRange->forceFill(['created_at' => '2026-05-01'])->save();

    Livewire::actingAs($staff)->test(ListTickets::class)
        ->filterTable('period', ['field' => 'created_at', 'from' => '2026-02-01', 'until' => '2026-02-28'])
        ->assertCanSeeTableRecords([$inRange])
        ->assertCanNotSeeTableRecords([$outOfRange]);
});

test('period filter narrows the list by completion date range', function (): void {
    $staff = staffUser();
    $completedInRange = ticket(['done_at' => '2026-03-10']);
    $completedOutOfRange = ticket(['done_at' => '2026-06-10']);

    Livewire::actingAs($staff)->test(ListTickets::class)
        ->filterTable('period', ['field' => 'done_at', 'from' => '2026-03-01', 'until' => '2026-03-31'])
        ->assertCanSeeTableRecords([$completedInRange])
        ->assertCanNotSeeTableRecords([$completedOutOfRange]);
});

test('filters compose with an existing view tab instead of replacing it', function (): void {
    $staff = staffUser();
    $developer = User::factory()->create();
    $matching = ticket(['status' => TicketStatus::Progress, 'requester_id' => User::factory()->create()->id, 'assignee_id' => $developer->id]);
    $wrongAssignee = ticket(['status' => TicketStatus::Progress, 'requester_id' => User::factory()->create()->id, 'assignee_id' => User::factory()->create()->id]);
    $wrongStatus = ticket(['status' => TicketStatus::Backlog, 'requester_id' => User::factory()->create()->id, 'assignee_id' => $developer->id]);

    Livewire::actingAs($staff)->test(ListTickets::class)
        ->set('activeTab', 'in_progress')
        ->filterTable('assignee_id', $developer)
        ->assertCanSeeTableRecords([$matching])
        ->assertCanNotSeeTableRecords([$wrongAssignee, $wrongStatus]);
});
