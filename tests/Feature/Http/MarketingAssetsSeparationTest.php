<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('la landing pubblica carica il css marketing, non il tema teal del pannello', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('marketing', escape: false);
    $response->assertDontSee('filament/admin/theme', escape: false);
});

test('il login carica il css marketing, non il tema teal del pannello', function (): void {
    $response = $this->get('/admin/login');

    $response->assertSuccessful();
    $response->assertSee('marketing', escape: false);
    $response->assertDontSee('filament/admin/theme', escape: false);
});

test('la dashboard del pannello (post-login) resta sul tema teal esistente, non sul css marketing', function (): void {
    $user = grantTicketPanelRole(User::factory()->create());

    $response = $this->actingAs($user)->get('/admin');

    $response->assertSuccessful();
    $response->assertDontSee('resources/css/marketing', escape: false);
});
