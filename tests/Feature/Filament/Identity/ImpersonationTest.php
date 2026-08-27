<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use STS\FilamentImpersonate\Facades\Impersonation;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

/**
 * Assegna un ruolo applicativo "vuoto" (nessun permesso derivato) solo per superare il
 * gate d'accesso al pannello (§9.1, US-020), stesso idioma di RoleAndPermissionManagementTest:
 * isola il test sui SOLI permessi diretti concessi da userWithPermissions().
 */
function grantImpersonationPanelAccess(User $user): User
{
    Role::query()->firstOrCreate(['name' => UserRole::Developer->value, 'guard_name' => 'web']);
    $user->assignRole(UserRole::Developer->value);

    return $user->fresh();
}

test('an admin with user.impersonate sees the Impersona action on the users table', function (): void {
    $admin = grantImpersonationPanelAccess(userWithPermissions(PermissionEnum::UserView, PermissionEnum::UserImpersonate));
    $target = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionVisible('impersonate', $target);
});

test('an admin with user.impersonate sees the Impersona action on the user view page', function (): void {
    $admin = grantImpersonationPanelAccess(userWithPermissions(PermissionEnum::UserView, PermissionEnum::UserImpersonate));
    $target = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ViewUser::class, ['record' => $target->getKey()])
        ->assertActionVisible('impersonate');
});

test('a user without user.impersonate does not see the Impersona action', function (): void {
    $staff = grantImpersonationPanelAccess(userWithPermissions(PermissionEnum::UserView));
    $target = User::factory()->create();

    $this->actingAs($staff);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('impersonate', $target);

    Livewire::test(ViewUser::class, ['record' => $target->getKey()])
        ->assertActionHidden('impersonate');
});

test('an admin can impersonate a user, the switch is logged, and leaving restores the original session', function (): void {
    // Log::spy()->shouldHaveReceived(...)->once() risulta inaffidabile quando invocato più
    // volte nello stesso test per lo stesso metodo (le expectation successive sul metodo
    // 'info' finiscono per contare le invocazioni cumulate, non solo quelle che matchano la
    // propria closure) — si cattura quindi la cronologia reale via Log::listen(), verificata
    // poi con asserzioni dirette.
    $logs = [];
    Log::listen(function ($event) use (&$logs): void {
        $logs[] = ['level' => $event->level, 'message' => $event->message, 'context' => $event->context];
    });

    $admin = grantImpersonationPanelAccess(userWithPermissions(PermissionEnum::UserView, PermissionEnum::UserImpersonate));
    $target = grantImpersonationPanelAccess(User::factory()->create());

    $this->actingAs($admin);

    expect(Auth::id())->toBe($admin->id)
        ->and(Impersonation::isImpersonating())->toBeFalse();

    Livewire::test(ListUsers::class)
        ->callTableAction('impersonate', $target)
        ->assertRedirect();

    expect(Auth::id())->toBe($target->id)
        ->and(Impersonation::isImpersonating())->toBeTrue()
        ->and(Impersonation::getImpersonatorId())->toBe($admin->id);

    $startedLogs = array_values(array_filter($logs, fn (array $log): bool => $log['message'] === 'identity.impersonation.started'));

    expect($startedLogs)->toHaveCount(1)
        ->and($startedLogs[0]['context']['impersonator_id'])->toBe($admin->id)
        ->and($startedLogs[0]['context']['impersonated_id'])->toBe($target->id);

    // Banner: sempre visibile mentre l'impersonation è attiva, con azione esplicita per uscire.
    $this->get(Filament::getCurrentOrDefaultPanel()->getUrl())
        ->assertOk()
        ->assertSee(__('filament-impersonate::banner.impersonating'))
        ->assertSee(__('filament-impersonate::banner.leave'))
        ->assertSee(route('filament-impersonate.leave'), escape: false);

    $this->get(route('filament-impersonate.leave'))->assertRedirect();

    expect(Auth::id())->toBe($admin->id)
        ->and(Impersonation::isImpersonating())->toBeFalse();

    $stoppedLogs = array_values(array_filter($logs, fn (array $log): bool => $log['message'] === 'identity.impersonation.stopped'));

    expect($stoppedLogs)->toHaveCount(1)
        ->and($stoppedLogs[0]['context']['impersonator_id'])->toBe($admin->id)
        ->and($stoppedLogs[0]['context']['impersonated_id'])->toBe($target->id);
});

test('a deactivated user cannot be impersonated', function (): void {
    $admin = grantImpersonationPanelAccess(userWithPermissions(PermissionEnum::UserView, PermissionEnum::UserImpersonate));
    $target = User::factory()->create(['deactivated_at' => now()]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('impersonate', $target);
});
