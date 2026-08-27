<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Tags\Models\Tag;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

test('a user with tag.create can turn a ticket into a commessa from the view page', function (): void {
    $staff = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewAny, PermissionEnum::TagCreate));
    $ticketRecord = ticket(['title' => 'Bug login', 'estimated_hours' => 8]);

    $this->actingAs($staff);

    Livewire::test(ViewTicket::class, ['record' => $ticketRecord->getKey()])
        ->assertActionExists('create_commessa')
        ->callAction('create_commessa', data: ['name' => 'Commessa bug login', 'estimated_hours' => 8])
        ->assertHasNoActionErrors();

    $newTag = Tag::query()->sole();

    expect($newTag->name)->toBe('Commessa bug login')
        ->and((float) $newTag->estimated_hours)->toBe(8.0)
        ->and($newTag->tickets()->pluck('tickets.id')->all())->toBe([$ticketRecord->id]);
});

test('a user without tag.create cannot see the create commessa action', function (): void {
    $staff = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewAny));
    $ticketRecord = ticket();

    $this->actingAs($staff);

    Livewire::test(ViewTicket::class, ['record' => $ticketRecord->getKey()])
        ->assertActionDoesNotExist('create_commessa');
});

test('the action is also available on the edit page and defaults name/hours from the ticket', function (): void {
    $staff = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketUpdateAny, PermissionEnum::TicketViewAny, PermissionEnum::TagCreate));
    $ticketRecord = ticket(['title' => 'Rifattorizzare export', 'estimated_hours' => 5]);

    $this->actingAs($staff);

    Livewire::test(EditTicket::class, ['record' => $ticketRecord->getKey()])
        ->mountAction('create_commessa')
        ->assertSchemaStateSet(['name' => 'Rifattorizzare export', 'estimated_hours' => 5.0])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $newTag = Tag::query()->sole();

    expect($newTag->name)->toBe('Rifattorizzare export');
});
