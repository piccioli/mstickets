<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

/**
 * Ricerca globale (US-603, §8.7): keyword-based su id/titolo/richiedente/corpo
 * messaggi, scoped secondo la Policy dell'utente corrente.
 */
test('global search finds a ticket by id', function (): void {
    $staff = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewAny));
    $matching = ticket(['title' => 'Errore login SSO']);
    $other = ticket(['title' => 'Altro ticket qualunque']);

    $this->actingAs($staff);

    $results = TicketResource::getGlobalSearchResults((string) $matching->id);

    expect($results->pluck('title')->implode(' | '))->toContain("#{$matching->id}")
        ->and($results->pluck('title')->implode(' | '))->not->toContain("#{$other->id} —");
});

test('global search finds a ticket by title', function (): void {
    $staff = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewAny));
    $matching = ticket(['title' => 'Errore login SSO']);
    $other = ticket(['title' => 'Fattura mancante']);

    $this->actingAs($staff);

    $results = TicketResource::getGlobalSearchResults('SSO');

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe("#{$matching->id} — {$matching->title}");
});

test('global search finds a ticket by requester name or email', function (): void {
    $staff = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewAny));
    $requester = User::factory()->create(['name' => 'Mario Rossoverdi', 'email' => 'mario.rossoverdi@example.com']);
    $matching = ticket(['requester_id' => $requester->id, 'title' => 'Richiesta generica']);
    $other = ticket(['title' => 'Altro ticket qualunque']);

    $this->actingAs($staff);

    $byName = TicketResource::getGlobalSearchResults('Rossoverdi');
    $byEmail = TicketResource::getGlobalSearchResults('mario.rossoverdi@example.com');

    expect($byName)->toHaveCount(1)
        ->and($byName->first()->title)->toBe("#{$matching->id} — {$matching->title}")
        ->and($byEmail)->toHaveCount(1)
        ->and($byEmail->first()->title)->toBe("#{$matching->id} — {$matching->title}");
});

test('global search finds a ticket by a term only present in a message body', function (): void {
    $staff = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewAny));
    $matching = ticket(['title' => 'Richiesta generica']);
    $other = ticket(['title' => 'Altro ticket qualunque']);
    ticketMessage(['ticket_id' => $matching->id, 'body_text' => 'Il problema riguarda la fatturazione elettronica']);

    $this->actingAs($staff);

    $results = TicketResource::getGlobalSearchResults('fatturazione elettronica');

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe("#{$matching->id} — {$matching->title}");
});

test('a customer does not find tickets belonging to other requesters in global search results', function (): void {
    $customer = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewOwn), UserRole::Customer);
    $otherCustomer = User::factory()->create();

    ticket(['requester_id' => $customer->id, 'title' => 'Il mio ticket sulla fatturazione']);
    $otherTicket = ticket(['requester_id' => $otherCustomer->id, 'title' => 'Ticket altrui sulla fatturazione']);

    $this->actingAs($customer);

    $results = TicketResource::getGlobalSearchResults('fatturazione');

    expect($results)->toHaveCount(1)
        ->and($results->pluck('title')->implode(' | '))->not->toContain("#{$otherTicket->id} —");
});
