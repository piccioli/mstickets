<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

test('la pagina di login renderizza il layout custom', function (): void {
    Livewire::test(Login::class)
        ->assertSuccessful()
        ->assertSee('Bentornato')
        ->assertSee('Accedi');
});

test('credenziali corrette autenticano e reindirizzano alla dashboard', function (): void {
    $user = grantTicketPanelRole(User::factory()->create(['password' => bcrypt('password')]));

    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertRedirect();

    expect(auth()->check())->toBeTrue();
    expect(auth()->id())->toBe($user->id);
});

test('credenziali errate mostrano un errore e non autenticano', function (): void {
    $user = grantTicketPanelRole(User::factory()->create(['password' => bcrypt('password')]));

    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors(['data.email']);

    expect(auth()->check())->toBeFalse();
});

test('la vista contiene il toggle Alpine per mostrare/nascondere la password (nessuna reimplementazione JS del campo)', function (): void {
    Livewire::test(Login::class)
        ->assertSuccessful()
        ->assertSeeHtml('x-data="{ show: false }"')
        ->assertSeeHtml(":type=\"show ? 'text' : 'password'\"");
});

test('"salva per le prossime sessioni" valorizza il remember token e mantiene la sessione', function (): void {
    $user = grantTicketPanelRole(User::factory()->create(['password' => bcrypt('password'), 'remember_token' => null]));

    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->set('data.remember', true)
        ->call('authenticate')
        ->assertRedirect();

    expect($user->fresh()->remember_token)->not->toBeNull();
});

test('il sesto tentativo di login consecutivo viene bloccato dal rate limiting nativo', function (): void {
    $user = grantTicketPanelRole(User::factory()->create(['password' => bcrypt('password')]));

    $component = Livewire::test(Login::class);

    for ($i = 0; $i < 5; $i++) {
        $component->set('data.email', $user->email)
            ->set('data.password', 'wrong-password')
            ->call('authenticate');
    }

    // Il sesto tentativo è rate-limited (5 tentativi ammessi, WithRateLimiting::rateLimit(5)
    // nella pagina nativa Filament\Auth\Pages\Login): niente ValidationException stavolta,
    // il metodo ritorna prima di rivalutare le credenziali.
    $component->set('data.password', 'password')->call('authenticate');

    expect(auth()->check())->toBeFalse();
});
