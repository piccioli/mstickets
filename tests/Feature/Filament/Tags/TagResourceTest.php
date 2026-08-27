<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Filament\Resources\Tags\Pages\ListTags;
use App\Filament\Resources\Tags\TagResource;
use Database\Seeders\RolePermissionSeeder;
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
 * (§9.1, US-020), isolando il test sui soli permessi diretti concessi da
 * userWithPermissions() — stessa convenzione di EmailMessageResourceTest.php (US-113).
 */
function grantTagPanelAccess(User $user, UserRole $role = UserRole::Developer): User
{
    Role::query()->firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
    $user->assignRole($role->value);

    return $user->fresh();
}

test('a user without tag.view is denied access to the tags resource', function (): void {
    $user = grantTagPanelAccess(userWithPermissions());

    expect(TagResource::canViewAny())->toBeFalse();

    $this->actingAs($user)->get(TagResource::getUrl('index'))->assertForbidden();
});

test('a user with tag.view can access the tags registry', function (): void {
    $user = grantTagPanelAccess(userWithPermissions(PermissionEnum::TagView));

    $this->actingAs($user);

    expect(TagResource::canViewAny())->toBeTrue();

    $this->get(TagResource::getUrl('index'))->assertOk();
});

test('the tags resource has no create or edit page', function (): void {
    expect(Route::has('filament.admin.resources.tags.create'))->toBeFalse()
        ->and(Route::has('filament.admin.resources.tags.edit'))->toBeFalse();
});

test('the list shows estimated/worked hours, the SAL bar and the open/closed ticket counts', function (): void {
    $user = grantTagPanelAccess(userWithPermissions(PermissionEnum::TagView));
    $tag = tag(['name' => 'Commessa in corso', 'estimated_hours' => 10]);
    $openTicket = ticket(['status' => TicketStatus::Progress, 'worked_minutes' => 300]);
    $closedTicket = ticket(['status' => TicketStatus::Done, 'worked_minutes' => 300]);
    $tag->tickets()->attach([$openTicket->id, $closedTicket->id]);

    $this->actingAs($user);

    Livewire::test(ListTags::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$tag])
        ->assertTableColumnStateSet('estimated_hours', '10.00', record: $tag)
        ->assertTableColumnStateSet('worked_hours', 10.0, record: $tag)
        ->assertTableColumnStateSet('sal', 100.0, record: $tag)
        ->assertTableColumnStateSet('tickets_open_count', 1, record: $tag)
        ->assertTableColumnStateSet('tickets_closed_count', 1, record: $tag)
        ->assertTableColumnStateSet('is_closed', 'Aperta', record: $tag);
});

test('a tag with all tickets closed shows the closed badge', function (): void {
    $user = grantTagPanelAccess(userWithPermissions(PermissionEnum::TagView));
    $tag = tag(['name' => 'Commessa chiusa']);
    $tag->tickets()->attach(ticket(['status' => TicketStatus::Released]));

    $this->actingAs($user);

    Livewire::test(ListTags::class)
        ->assertTableColumnStateSet('is_closed', 'Chiusa', record: $tag);
});

test('a tag with no estimated hours shows a SAL placeholder instead of a division error', function (): void {
    $user = grantTagPanelAccess(userWithPermissions(PermissionEnum::TagView));
    $tag = tag(['name' => 'Commessa senza stima', 'estimated_hours' => null]);

    $this->actingAs($user);

    Livewire::test(ListTags::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$tag]);

    expect($tag->sal())->toBeNull();
});

test('TagResource per ruolo, riga per riga (§9.4): manager vede l\'elenco ma non ha l\'azione di cancellazione', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $manager = grantTagPanelAccess(User::factory()->create(), UserRole::Manager);
    $tag = tag();

    $this->actingAs($manager);

    expect(TagResource::canViewAny())->toBeTrue()
        ->and($manager->can('delete', $tag))->toBeFalse();
});
