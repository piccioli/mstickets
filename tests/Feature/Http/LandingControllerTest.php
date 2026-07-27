<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un visitatore anonimo vede la landing pubblica', function (): void {
    $this->get('/')
        ->assertSuccessful()
        ->assertViewIs('marketing.landing')
        ->assertSee('Accedi');
});

test('un utente con sessione attiva viene rimandato alla dashboard del pannello', function (): void {
    $user = grantTicketPanelRole(User::factory()->create());

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect('/admin');
});
