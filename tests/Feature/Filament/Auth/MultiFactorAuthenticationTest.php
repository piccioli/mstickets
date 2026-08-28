<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Auth\Pages\Login;
use App\Filament\Pages\WorkBoard;
use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PragmaRX\Google2FAQRCode\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

function workBoardAccessUser(UserRole $role): User
{
    return grantTicketPanelRole(
        userWithPermissions(PermissionEnum::TicketManageInternalFields, PermissionEnum::TicketViewAssigned),
        $role,
    );
}

test('un ruolo per cui la MFA è obbligatoria non può accedere al pannello senza averla configurata', function (): void {
    config(['mfa.required_roles' => [UserRole::Admin->value]]);

    $admin = workBoardAccessUser(UserRole::Admin);

    $this->actingAs($admin)
        ->get(WorkBoard::getUrl())
        ->assertRedirect(route('filament.admin.auth.multi-factor-authentication.set-up-required'));
});

test('un ruolo per cui la MFA è facoltativa accede normalmente senza averla configurata', function (): void {
    config(['mfa.required_roles' => [UserRole::Admin->value]]);

    $developer = workBoardAccessUser(UserRole::Developer);

    $this->actingAs($developer)
        ->get(WorkBoard::getUrl())
        ->assertSuccessful();
});

test('un ruolo per cui la MFA è obbligatoria accede normalmente una volta configurata', function (): void {
    config(['mfa.required_roles' => [UserRole::Admin->value]]);

    $admin = workBoardAccessUser(UserRole::Admin);
    $admin->saveAppAuthenticationSecret('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567');

    $this->actingAs($admin)
        ->get(WorkBoard::getUrl())
        ->assertSuccessful();
});

test('senza ruoli configurati come obbligatori nessun utente è forzato alla MFA', function (): void {
    config(['mfa.required_roles' => []]);

    $admin = workBoardAccessUser(UserRole::Admin);

    $this->actingAs($admin)
        ->get(WorkBoard::getUrl())
        ->assertSuccessful();
});

test('la pagina profilo espone la gestione della MFA per l\'utente autenticato', function (): void {
    $user = workBoardAccessUser(UserRole::Developer);

    $this->actingAs($user)
        ->get(EditProfile::getUrl())
        ->assertSuccessful();
});

test('un login con MFA attiva mostra la sfida e si completa solo con un codice valido', function (): void {
    $user = workBoardAccessUser(UserRole::Developer);

    $google2fa = app(Google2FA::class);
    $secret = $google2fa->generateSecretKey();
    $user->saveAppAuthenticationSecret($secret);

    $component = Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate');

    expect(auth()->check())->toBeFalse();
    $component->assertSee('Verifica la tua identità');

    $component
        ->set('data.multiFactor.app.code', $google2fa->getCurrentOtp($secret))
        ->call('authenticate')
        ->assertRedirect();

    expect(auth()->check())->toBeTrue();
    expect(auth()->id())->toBe($user->id);
});

test('un login con MFA attiva e un codice errato non completa l\'accesso', function (): void {
    $user = workBoardAccessUser(UserRole::Developer);

    $google2fa = app(Google2FA::class);
    $secret = $google2fa->generateSecretKey();
    $user->saveAppAuthenticationSecret($secret);

    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->set('data.multiFactor.app.code', '000000')
        ->call('authenticate')
        ->assertHasErrors();

    expect(auth()->check())->toBeFalse();
});
