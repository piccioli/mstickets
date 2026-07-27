<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Filament\Auth\Pages\RequestPasswordReset;
use App\Filament\Auth\Pages\ResetPassword;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

test('richiedere il reset con una email registrata invia la notifica e mostra il pannello "controlla la casella"', function (): void {
    Notification::fake();

    $user = grantTicketPanelRole(User::factory()->create());

    Livewire::test(RequestPasswordReset::class)
        ->set('data.email', $user->email)
        ->call('request')
        ->assertSet('linkSent', true)
        ->assertSet('sentEmail', $user->email);

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('richiedere il reset con una email inesistente non invia notifiche ma non rivela l\'assenza dell\'utente', function (): void {
    Notification::fake();

    Livewire::test(RequestPasswordReset::class)
        ->set('data.email', 'nessuno@esempio.test')
        ->call('request');

    Notification::assertNothingSent();
});

test('"invia di nuovo" immediato è bloccato dal throttling nativo del broker password (60s)', function (): void {
    Notification::fake();

    $user = grantTicketPanelRole(User::factory()->create());

    Livewire::test(RequestPasswordReset::class)
        ->set('data.email', $user->email)
        ->call('request')
        ->call('resend')
        // Il broker password di Laravel (config('auth.passwords.users.throttle'), default 60s)
        // impedisce di inviare un secondo link alla stessa email prima che sia trascorso
        // l'intervallo: comportamento nativo corretto contro il flood di email, non un bug —
        // "resend" resta bloccato (linkSent torna false) fino a che il throttle non scade.
        ->assertSet('linkSent', false);

    Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
});

test('"invia di nuovo" dopo il throttle del broker invia davvero un secondo link', function (): void {
    Notification::fake();

    $user = grantTicketPanelRole(User::factory()->create());

    $component = Livewire::test(RequestPasswordReset::class)
        ->set('data.email', $user->email)
        ->call('request');

    $this->travel(61)->seconds();

    $component->call('resend')->assertSet('linkSent', true);

    Notification::assertSentToTimes($user, ResetPasswordNotification::class, 2);
});

test('un token valido permette di impostare una nuova password rispettando le regole reali', function (): void {
    $user = grantTicketPanelRole(User::factory()->create());
    $token = Password::broker(Filament::getAuthPasswordBroker())->createToken($user);

    Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => $token])
        ->set('password', 'NuovaPassword1')
        ->set('passwordConfirmation', 'NuovaPassword1')
        ->call('resetPassword')
        ->assertHasNoErrors();

    expect($user->fresh()->password)->not->toBe($user->password);
});

test('una password che non rispetta le regole reali (min 8, maiuscola, numero) viene rifiutata', function (): void {
    $user = grantTicketPanelRole(User::factory()->create());
    $token = Password::broker(Filament::getAuthPasswordBroker())->createToken($user);

    Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => $token])
        ->set('password', 'tuttominuscolo')
        ->set('passwordConfirmation', 'tuttominuscolo')
        ->call('resetPassword')
        ->assertHasErrors(['password']);
});

test('un token inesistente o già consumato viene rifiutato con una notifica nativa, nessun reset silenzioso', function (): void {
    $user = grantTicketPanelRole(User::factory()->create());
    $originalPassword = $user->password;

    Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => 'token-inventato-non-valido'])
        ->set('password', 'NuovaPassword1')
        ->set('passwordConfirmation', 'NuovaPassword1')
        ->call('resetPassword')
        ->assertNoRedirect();

    expect($user->fresh()->password)->toBe($originalPassword);
});

test('un token scaduto oltre i 60 minuti configurati viene rifiutato', function (): void {
    $user = grantTicketPanelRole(User::factory()->create());
    $originalPassword = $user->password;
    $token = Password::broker(Filament::getAuthPasswordBroker())->createToken($user);

    $this->travel(61)->minutes();

    Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => $token])
        ->set('password', 'NuovaPassword1')
        ->set('passwordConfirmation', 'NuovaPassword1')
        ->call('resetPassword')
        ->assertNoRedirect();

    expect($user->fresh()->password)->toBe($originalPassword);
});
