<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Filament\Resources\Tickets\TicketResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Badge di navigazione (US-604, §8.4): un unico badge combinato ("In attesa" +
 * "Problemi" + "Da testare") sull'unica voce di menu Ticket, con cache per evitare
 * una query sincrona a ogni caricamento di pagina.
 */
test('navigation badge shows the correct combined count and tooltip breakdown', function (): void {
    $user = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewAny));
    $requester = User::factory()->create();

    ticket(['status' => TicketStatus::Waiting, 'requester_id' => $requester->id]);
    ticket(['status' => TicketStatus::Waiting, 'requester_id' => $requester->id]);
    ticket(['status' => TicketStatus::Problem, 'requester_id' => $requester->id]);
    ticket(['status' => TicketStatus::Testing, 'tester_id' => $user->id]);
    // Non deve contare: testing ma con un altro tester.
    $otherTester = userWithPermissions(PermissionEnum::TicketViewAny);
    ticket(['status' => TicketStatus::Testing, 'tester_id' => $otherTester->id]);

    $this->actingAs($user);

    expect(TicketResource::getNavigationBadge())->toBe('4')
        ->and(TicketResource::getNavigationBadgeColor())->toBe('danger')
        ->and(TicketResource::getNavigationBadgeTooltip())->toBe('2 in attesa · 1 in problema · 1 da testare');
});

test('navigation badge is null when nothing needs attention', function (): void {
    $user = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewAny));
    ticket(['status' => TicketStatus::Todo]);

    $this->actingAs($user);

    expect(TicketResource::getNavigationBadge())->toBeNull()
        ->and(TicketResource::getNavigationBadgeColor())->toBeNull();
});

test('navigation badge counts are cached across requests within the ttl', function (): void {
    $user = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewAny));
    ticket(['status' => TicketStatus::Waiting, 'requester_id' => User::factory()->create()->id]);

    $this->actingAs($user);

    // Prima "richiesta": popola la cache.
    expect(TicketResource::getNavigationBadge())->toBe('1');

    // Seconda "richiesta" entro il TTL: nessuna nuova query sui ticket, il conteggio
    // arriva dalla cache.
    DB::enableQueryLog();
    $badge = TicketResource::getNavigationBadge();
    $tooltip = TicketResource::getNavigationBadgeTooltip();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($queries)->toBeEmpty()
        ->and($badge)->toBe('1')
        ->and($tooltip)->toBe('1 in attesa · 0 in problema · 0 da testare');
});

test('navigation badge counts are scoped per user and do not leak across cache keys', function (): void {
    $tester = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewAny));
    $otherStaff = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewAny));
    ticket(['status' => TicketStatus::Testing, 'tester_id' => $tester->id]);

    $this->actingAs($tester);
    expect(TicketResource::getNavigationBadge())->toBe('1');

    $this->actingAs($otherStaff);
    expect(TicketResource::getNavigationBadge())->toBeNull();
});
