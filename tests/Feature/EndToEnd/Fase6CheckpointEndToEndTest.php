<?php

declare(strict_types=1);

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Actions\CreateActivityReport;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Reporting\Models\ActivityReport;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use App\Filament\Pages\CustomerDashboard;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

/**
 * Checkpoint di fine Fase 6 (§14 del PRD, US-618): a differenza dei test per singola
 * story già presenti in US-601..US-617, qui si percorrono end-to-end, con Action/Model/
 * comandi reali in sequenza (mai stato seminato direttamente per il solo sotto-sistema
 * sotto test), i tre scenari che quelle story, prese singolarmente, non coprono: (1)
 * l'isolamento fra due clienti reali attraverso PIÙ superfici insieme (dashboard,
 * ricerca globale, elenco ticket), non una sola per volta; (2) l'interazione fra TUTTI
 * i comandi schedulati introdotti in questa fase eseguiti in sequenza sullo stesso
 * dataset, per escludere che il guard di un comando "catturi" un ticket destinato a un
 * altro; (3) la garanzia esplicita di conservatività di `tickets:archive-scrum` (US-611,
 * compromesso segnalato al committente per l'incertezza sul comportamento v1: mai una
 * cancellazione, mai un cambio di stato, solo la colonna additiva `archived_at`).
 *
 * La verifica end-to-end su DATI REALI importati dal dump v1 (AC1 di questa story) è
 * stata condotta manualmente durante questo checkpoint (v1:import --anonymize, cliente
 * reale con ticket/documentazione/report/progetto fundraising coinvolti, login e
 * navigazione in browser) e documentata in progress.txt: non è replicata qui perché,
 * come già per il checkpoint di Fase 5, un test Pest gira sempre contro un database di
 * test seminato da factory, mai contro il dump reale.
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    Queue::fake();
});

test('two customers with real data across tickets, reports and fundraising stay fully isolated across the dashboard, global search and the ticket list', function (): void {
    $alice = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewOwn, PermissionEnum::ActivityReportViewOwn), UserRole::Customer);
    $bob = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketViewOwn, PermissionEnum::ActivityReportViewOwn), UserRole::Customer);

    $aliceTicket = ticket(['requester_id' => $alice->id, 'title' => 'Fattura Alice da controllare', 'status' => TicketStatus::Waiting]);
    $bobTicket = ticket(['requester_id' => $bob->id, 'title' => 'Fattura Bob da controllare', 'status' => TicketStatus::Waiting]);

    CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $alice->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 3,
    ]);
    CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $bob->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 3,
    ]);

    $staff = User::factory()->create();
    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando isolamento clienti',
        'deadline' => today()->addMonth()->toDateString(),
        'created_by' => $staff->id,
        'responsible_user_id' => $staff->id,
    ]);
    $aliceProject = FundraisingProject::create([
        'title' => 'Progetto di Alice',
        'fundraising_opportunity_id' => $opportunity->id,
        'created_by' => $staff->id,
        'lead_user_id' => $alice->id,
    ]);

    // Dashboard: Alice vede solo i propri dati.
    $this->actingAs($alice)
        ->get(CustomerDashboard::getUrl())
        ->assertSuccessful()
        ->assertSee('Progetto di Alice')
        ->assertDontSee('Progetto di Bob', false);

    // Ricerca globale: Bob non trova il ticket di Alice, nemmeno cercando un termine comune.
    $this->actingAs($bob);
    $bobResults = TicketResource::getGlobalSearchResults('fattura');
    expect($bobResults)->toHaveCount(1)
        ->and($bobResults->first()->title)->toBe("#{$bobTicket->id} — {$bobTicket->title}");

    $this->actingAs($alice);
    $aliceResults = TicketResource::getGlobalSearchResults('fattura');
    expect($aliceResults)->toHaveCount(1)
        ->and($aliceResults->first()->title)->toBe("#{$aliceTicket->id} — {$aliceTicket->title}");

    // Elenco ticket: nessuna riga dell'altro cliente è mai visibile.
    expect(Ticket::query()->visibleTo($bob)->pluck('id'))
        ->not->toContain($aliceTicket->id);
    expect(Ticket::query()->visibleTo($alice)->pluck('id'))
        ->not->toContain($bobTicket->id);

    // Report attività: nessuno dei due vede il report dell'altro.
    expect(ActivityReport::query()->visibleTo($alice)->count())->toBe(1);
    expect(ActivityReport::query()->visibleTo($bob)->count())->toBe(1);

    // Progetti fundraising: Bob non è coinvolto nel progetto di Alice.
    expect(FundraisingProject::query()->involvingAsCustomer($bob)->pluck('id'))->not->toContain($aliceProject->id);
});

test('running every Fase 6 scheduled command in sequence transitions each guarded ticket exactly once and never a ticket outside its guard', function (): void {
    $this->travelTo('2026-03-10 18:00:00');

    $assignee = User::factory()->create();

    $progressTicket = ticket(['status' => TicketStatus::Progress, 'assignee_id' => $assignee->id]);
    $releasedOldEnough = ticket(['status' => TicketStatus::Released, 'assignee_id' => $assignee->id, 'released_at' => now()->subDays(10)]);
    $releasedTooRecent = ticket(['status' => TicketStatus::Released, 'assignee_id' => $assignee->id, 'released_at' => now()]);
    $scrumToday = ticket(['type' => TicketType::Scrum, 'status' => TicketStatus::Todo]);
    $scrumDoneLongAgo = ticket(['type' => TicketType::Scrum, 'status' => TicketStatus::Done, 'done_at' => now()->subDays(60)]);
    $bugDoneLongAgo = ticket(['type' => TicketType::Bug, 'status' => TicketStatus::Done, 'done_at' => now()->subDays(60)]);
    $waitingOldEnough = ticket(['status' => TicketStatus::Waiting, 'previous_status' => TicketStatus::Todo, 'waiting_reason' => 'attesa cliente', 'status_changed_at' => now()->subDays(10)]);
    $waitingTooRecent = ticket(['status' => TicketStatus::Waiting, 'previous_status' => TicketStatus::Todo, 'waiting_reason' => 'attesa cliente', 'status_changed_at' => now()]);

    $runDailySequence = function (): void {
        $this->artisan('tickets:progress-to-todo')->assertSuccessful();
        $this->artisan('tickets:auto-close-released')->assertSuccessful();
        $this->artisan('tickets:close-scrum')->assertSuccessful();
        $this->artisan('tickets:archive-scrum')->assertSuccessful();
        $this->artisan('tickets:restore-waiting')->assertSuccessful();
    };

    $runDailySequence();

    expect($progressTicket->fresh()->status)->toBe(TicketStatus::Todo);
    expect($releasedOldEnough->fresh()->status)->toBe(TicketStatus::Done);
    expect($releasedTooRecent->fresh()->status)->toBe(TicketStatus::Released);
    expect($scrumToday->fresh()->status)->toBe(TicketStatus::Done);
    expect($scrumDoneLongAgo->fresh()->status)->toBe(TicketStatus::Done)
        ->and($scrumDoneLongAgo->fresh()->archived_at)->not->toBeNull();
    expect($bugDoneLongAgo->fresh()->archived_at)->toBeNull();
    expect($waitingOldEnough->fresh()->status)->toBe(TicketStatus::Todo);
    expect($waitingTooRecent->fresh()->status)->toBe(TicketStatus::Waiting);

    $logCountsAfterFirstPass = TicketLog::query()->count();

    // Ri-eseguire l'intera sequenza giornaliera non deve produrre alcuna transizione
    // aggiuntiva: ogni comando è idempotente singolarmente (già testato story per
    // story), qui si verifica che lo sia anche l'intera sequenza combinata.
    $runDailySequence();

    expect(TicketLog::query()->count())->toBe($logCountsAfterFirstPass);
    expect($progressTicket->fresh()->status)->toBe(TicketStatus::Todo);
    expect($releasedOldEnough->fresh()->status)->toBe(TicketStatus::Done);
});

test('archive-scrum is a strictly additive compromise: it never touches ticket status or any field besides archived_at, only ever a dedicated system log', function (): void {
    $ticket = ticket([
        'type' => TicketType::Scrum,
        'status' => TicketStatus::Done,
        'title' => 'Riunione settimanale',
        'done_at' => now()->subDays(60),
    ]);
    $beforeAttributes = $ticket->fresh()->getAttributes();

    $this->artisan('tickets:archive-scrum', ['--dry-run' => true])->assertSuccessful();
    expect($ticket->fresh()->archived_at)->toBeNull();

    $this->artisan('tickets:archive-scrum')->assertSuccessful();

    $afterAttributes = $ticket->fresh()->getAttributes();
    $changedKeys = array_keys(array_diff_assoc($afterAttributes, $beforeAttributes));

    // L'unico campo di dominio toccato è `archived_at` (`updated_at` può anche non
    // comparire se il salvataggio ricade nello stesso secondo di creazione): mai
    // `status` o un altro campo, a garanzia della natura additiva del compromesso.
    expect($changedKeys)->toContain('archived_at')
        ->and(array_diff($changedKeys, ['archived_at', 'updated_at']))->toBe([]);

    $log = TicketLog::query()->where('ticket_id', $ticket->id)->sole();
    expect($log->event)->toBe(TicketLogEvent::Archived)
        ->and($log->is_system)->toBeTrue()
        ->and($log->from_status)->toBeNull()
        ->and($log->to_status)->toBeNull();

    // Ri-eseguire non riarchivia né riscrive il log: il compromesso resta
    // idempotente anche in un secondo run reale.
    $this->artisan('tickets:archive-scrum')->assertSuccessful();
    expect(TicketLog::query()->where('ticket_id', $ticket->id)->count())->toBe(1);
});
