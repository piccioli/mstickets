<?php

declare(strict_types=1);

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use App\Filament\Resources\FundraisingOpportunities\Pages\EditFundraisingOpportunity;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

function makeOpportunityForProjectTicketActions(): FundraisingOpportunity
{
    $user = User::factory()->create();

    return FundraisingOpportunity::create([
        'name' => 'Bando test azioni',
        'deadline' => today()->addMonth()->toDateString(),
        'created_by' => $user->id,
        'responsible_user_id' => $user->id,
    ]);
}

test('un utente con fundraising.create può creare un progetto da un\'opportunità', function (): void {
    $opportunity = makeOpportunityForProjectTicketActions();
    $staff = userWithPermissions(PermissionEnum::FundraisingViewAny, PermissionEnum::FundraisingUpdate, PermissionEnum::FundraisingCreate);
    $this->actingAs($staff);

    Livewire::test(EditFundraisingOpportunity::class, ['record' => $opportunity->getKey()])
        ->assertActionExists('create_project')
        ->mountAction('create_project')
        ->assertSchemaStateSet(['title' => 'Bando test azioni'])
        ->setActionData(['title' => 'Progetto candidatura bando'])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $project = FundraisingProject::query()->sole();

    expect($project->title)->toBe('Progetto candidatura bando')
        ->and($project->fundraising_opportunity_id)->toBe($opportunity->id)
        ->and($project->created_by)->toBe($staff->id);
});

test('un utente senza fundraising.create non vede l\'azione crea progetto', function (): void {
    $opportunity = makeOpportunityForProjectTicketActions();
    $staff = userWithPermissions(PermissionEnum::FundraisingViewAny, PermissionEnum::FundraisingUpdate);
    $this->actingAs($staff);

    Livewire::test(EditFundraisingOpportunity::class, ['record' => $opportunity->getKey()])
        ->assertActionDoesNotExist('create_project');
});

test('un utente con ticket.create può creare un ticket da un\'opportunità senza progetto collegato', function (): void {
    $opportunity = makeOpportunityForProjectTicketActions();
    $staff = userWithPermissions(PermissionEnum::FundraisingViewAny, PermissionEnum::FundraisingUpdate, PermissionEnum::TicketCreate);
    $this->actingAs($staff);

    Livewire::test(EditFundraisingOpportunity::class, ['record' => $opportunity->getKey()])
        ->assertActionExists('create_ticket')
        ->mountAction('create_ticket')
        ->assertSchemaStateSet(['title' => 'Bando test azioni'])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $newTicket = Ticket::query()->sole();

    expect($newTicket->title)->toBe('Bando test azioni')
        ->and($newTicket->fundraising_project_id)->toBeNull();
});

test('creare un ticket da un\'opportunità con un progetto già collegato valorizza fundraising_project_id', function (): void {
    $opportunity = makeOpportunityForProjectTicketActions();
    $staff = userWithPermissions(PermissionEnum::FundraisingViewAny, PermissionEnum::FundraisingUpdate, PermissionEnum::TicketCreate);

    $project = FundraisingProject::create([
        'title' => 'Progetto già avviato',
        'fundraising_opportunity_id' => $opportunity->id,
        'created_by' => $staff->id,
    ]);

    $this->actingAs($staff);

    Livewire::test(EditFundraisingOpportunity::class, ['record' => $opportunity->getKey()])
        ->mountAction('create_ticket')
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $newTicket = Ticket::query()->sole();

    expect($newTicket->fundraising_project_id)->toBe($project->id);
});

test('un utente senza ticket.create non vede l\'azione crea ticket', function (): void {
    $opportunity = makeOpportunityForProjectTicketActions();
    $staff = userWithPermissions(PermissionEnum::FundraisingViewAny, PermissionEnum::FundraisingUpdate);
    $this->actingAs($staff);

    Livewire::test(EditFundraisingOpportunity::class, ['record' => $opportunity->getKey()])
        ->assertActionDoesNotExist('create_ticket');
});
