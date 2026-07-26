<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Actions\CreateTicket;
use App\Domain\Ticketing\Actions\PostTicketMessage;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\TicketWorkLog;
use App\Domain\TimeTracking\Actions\RecalculateWorkedTime;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/**
 * Verifica end-to-end di Fase 1 (§14 del PRD, US-114): a differenza dei test per
 * singola Action/regola già presenti nelle altre story, qui un ticket percorre
 * l'intero ciclo di vita con Action reali in sequenza (mai stato seminato
 * direttamente nel DB), così da coprire i criteri di accettazione complessivi della
 * fase e non solo le singole unità.
 */
uses(RefreshDatabase::class);

test('the main path takes a ticket from new to done through every state with coherent worked minutes', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $tester = userWithPermissions(PermissionEnum::TicketUpdateAssigned);

    $this->travelTo(CarbonImmutable::parse('2026-01-05 09:00:00')); // Monday
    $t = CreateTicket::run(['title' => 'Percorso principale'], $admin);
    expect($t->status)->toBe(TicketStatus::New);

    ChangeTicketStatus::run($t, TicketStatus::Assigned, $admin, ['assignee_id' => $developer->id]);
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Todo, $admin);

    $this->travelTo(CarbonImmutable::parse('2026-01-05 10:00:00'));
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Progress, $developer);

    $this->travelTo(CarbonImmutable::parse('2026-01-05 12:00:00'));
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Testing, $developer, ['tester_id' => $tester->id]);

    $this->travelTo(CarbonImmutable::parse('2026-01-05 12:30:00'));
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Tested, $tester);

    $this->travelTo(CarbonImmutable::parse('2026-01-05 12:35:00'));
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Released, $developer);

    $this->travelTo(CarbonImmutable::parse('2026-01-05 12:40:00'));
    $final = ChangeTicketStatus::run($t->fresh(), TicketStatus::Done, $developer);

    expect($final->status)->toBe(TicketStatus::Done)
        ->and($final->previous_status)->toBeNull()
        ->and($final->released_at)->not->toBeNull()
        ->and($final->done_at)->not->toBeNull();

    // 1 `created` + 7 `status_changed` reali, nessuna demozione in questo percorso.
    expect($final->logs()->count())->toBe(8)
        ->and($final->logs()->where('event', TicketLogEvent::StatusChanged)->count())->toBe(7);

    RecalculateWorkedTime::run($final);
    $final->refresh();

    // Unico intervallo chiuso: progress (10:00) -> testing (12:00) = 120'.
    expect($final->worked_minutes)->toBe(120);

    $workLog = TicketWorkLog::query()->where('ticket_id', $final->id)->sole();
    expect($workLog->work_date->toDateString())->toBe('2026-01-05')
        ->and($workLog->minutes)->toBe(120)
        ->and($workLog->user_id)->toBe($developer->id);
});

test('the path without testing takes a ticket from new to done skipping testing and tested', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);

    $this->travelTo(CarbonImmutable::parse('2026-01-06 09:00:00')); // Tuesday
    $t = CreateTicket::run(['title' => 'Percorso senza testing'], $admin);

    ChangeTicketStatus::run($t, TicketStatus::Assigned, $admin, ['assignee_id' => $developer->id]);
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Todo, $admin);

    $this->travelTo(CarbonImmutable::parse('2026-01-06 10:00:00'));
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Progress, $developer);

    $this->travelTo(CarbonImmutable::parse('2026-01-06 11:30:00'));
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Released, $developer);

    $this->travelTo(CarbonImmutable::parse('2026-01-06 11:35:00'));
    $final = ChangeTicketStatus::run($t->fresh(), TicketStatus::Done, $developer);

    expect($final->status)->toBe(TicketStatus::Done)
        ->and($final->logs()->where('to_status', TicketStatus::Testing)->count())->toBe(0)
        ->and($final->logs()->where('to_status', TicketStatus::Tested)->count())->toBe(0);

    RecalculateWorkedTime::run($final);

    // progress (10:00) -> released (11:30), from_status=progress chiude comunque l'intervallo.
    expect($final->fresh()->worked_minutes)->toBe(90);
});

test('the single-progress-per-assignee rule demotes another in-progress ticket during a real lifecycle', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);

    $ticketA = CreateTicket::run(['title' => 'Ticket A'], $admin);
    $ticketB = CreateTicket::run(['title' => 'Ticket B'], $admin);

    foreach ([$ticketA, $ticketB] as $t) {
        ChangeTicketStatus::run($t->fresh(), TicketStatus::Assigned, $admin, ['assignee_id' => $developer->id]);
        ChangeTicketStatus::run($t->fresh(), TicketStatus::Todo, $admin);
    }

    ChangeTicketStatus::run($ticketA->fresh(), TicketStatus::Progress, $developer);
    expect($ticketA->fresh()->status)->toBe(TicketStatus::Progress);

    ChangeTicketStatus::run($ticketB->fresh(), TicketStatus::Progress, $developer);

    expect($ticketB->fresh()->status)->toBe(TicketStatus::Progress)
        ->and($ticketA->fresh()->status)->toBe(TicketStatus::Todo);

    $demotionLog = $ticketA->fresh()->logs()
        ->where('from_status', TicketStatus::Progress)
        ->where('to_status', TicketStatus::Todo)
        ->sole();
    expect($demotionLog->from_status)->toBe(TicketStatus::Progress);
});

test('a ticket restores to its previous status after waiting and after problem end-to-end', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $system = systemUser();

    $t = CreateTicket::run(['title' => 'Ripristino'], $admin);
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Assigned, $admin, ['assignee_id' => $developer->id]);
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Todo, $admin);
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Progress, $developer);

    ChangeTicketStatus::run($t->fresh(), TicketStatus::Waiting, $developer, ['waiting_reason' => 'In attesa del cliente']);
    expect($t->fresh()->status)->toBe(TicketStatus::Waiting)
        ->and($t->fresh()->previous_status)->toBe(TicketStatus::Progress);

    // L'attore "sistema" è ammesso su waiting -> previous_status (serve al comando
    // schedulato tickets:restore-waiting, T6, che arriva in Fase 6): verificato qui
    // end-to-end perché la riga di tabella esiste già.
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Progress, $system);
    expect($t->fresh()->status)->toBe(TicketStatus::Progress)
        ->and($t->fresh()->previous_status)->toBeNull();

    ChangeTicketStatus::run($t->fresh(), TicketStatus::Problem, $developer, ['problem_reason' => 'Dipendenza esterna bloccata']);
    expect($t->fresh()->status)->toBe(TicketStatus::Problem)
        ->and($t->fresh()->previous_status)->toBe(TicketStatus::Progress);

    ChangeTicketStatus::run($t->fresh(), TicketStatus::Progress, $admin);
    expect($t->fresh()->status)->toBe(TicketStatus::Progress)
        ->and($t->fresh()->previous_status)->toBeNull();
});

test('the requester posting a message while waiting restores the ticket to its previous status end-to-end (rule T7)', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $customer = userWithPermissions(PermissionEnum::TicketMessageCreate);

    $t = CreateTicket::run(['title' => 'Richiesta cliente', 'requester_id' => $customer->id], $admin);
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Assigned, $admin, ['assignee_id' => $developer->id]);
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Todo, $admin);
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Progress, $developer);
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Waiting, $developer, ['waiting_reason' => 'In attesa di un chiarimento']);

    PostTicketMessage::run($t->fresh(), $customer, '<p>Ecco il chiarimento richiesto.</p>');

    $fresh = $t->fresh();
    expect($fresh->status)->toBe(TicketStatus::Progress)
        ->and($fresh->previous_status)->toBeNull();

    $restoreLog = $fresh->logs()
        ->where('from_status', TicketStatus::Waiting)
        ->where('to_status', TicketStatus::Progress)
        ->sole();
    expect($restoreLog->is_system)->toBeTrue();
});

test('tampering with the hidden assignee_id of a self-assigning transition action still self-assigns, never the injected user', function (): void {
    Filament::setCurrentPanel('admin');

    $developer = grantTicketPanelRole(userWithPermissions(PermissionEnum::TicketUpdateAssigned, PermissionEnum::TicketViewAny));
    $otherUser = User::factory()->create();
    $t = ticket(['status' => TicketStatus::New]);

    $this->actingAs($developer->fresh());

    // Il campo assignee_id non è nello schema dell'action quando l'attore si
    // auto-assegna silenziosamente (US-110): `setActionData` qui simula un
    // attaccante che inietta comunque `assignee_id` (di un altro utente) nel
    // payload Livewire. Filament risolve `$data` dallo stato dello SCHEMA
    // dell'action, non dall'array grezzo: il campo iniettato ma non dichiarato
    // viene ignorato, quindi il contesto passato a `ChangeTicketStatus` resta
    // quello di auto-assegnazione. Il test successivo verifica il livello di
    // difesa più a fondo (la macchina a stati stessa), indipendente da questo
    // comportamento di Filament.
    Livewire::test(ViewTicket::class, ['record' => $t->getKey()])
        ->mountAction('transition_assigned')
        ->assertSchemaComponentDoesNotExist('assignee_id')
        ->setActionData(['assignee_id' => $otherUser->id, 'apply_to_children' => false])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($t->fresh()->status)->toBe(TicketStatus::Assigned)
        ->and($t->fresh()->assignee_id)->toBe($developer->id)
        ->and($t->fresh()->assignee_id)->not->toBe($otherUser->id);
});

test('the state machine rejects an impersonated self-assignment context regardless of how it reaches ChangeTicketStatus', function (): void {
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $otherUser = User::factory()->create();
    $t = ticket(['status' => TicketStatus::New]);

    // Livello di difesa indipendente da Filament (§9.5, guard `AutoAssigningDeveloper`
    // di US-101): anche se un ipotetico punto di ingresso futuro (controller/API)
    // passasse un `context['assignee_id']` diverso dall'utente corrente, la macchina
    // a stati rifiuta la transizione con un errore di validazione localizzato, mai
    // un'eccezione generica, e non scrive alcun log.
    expect(fn () => ChangeTicketStatus::run($t, TicketStatus::Assigned, $developer, ['assignee_id' => $otherUser->id]))
        ->toThrow(ValidationException::class);

    expect($t->fresh()->status)->toBe(TicketStatus::New)
        ->and($t->fresh()->assignee_id)->toBeNull()
        ->and($t->fresh()->logs()->count())->toBe(0);
});

test('a forbidden transition attempted directly against the ChangeTicketStatus action is rejected and writes nothing', function (): void {
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket(['status' => TicketStatus::Done]);

    // "done" non ha alcuna riga verso "assigned" nella tabella (US-101): un
    // chiamante che bypassa del tutto Filament e invoca l'Action direttamente
    // (es. una richiesta manipolata verso un controller custom) deve comunque
    // ricevere l'errore di validazione localizzato, non un'eccezione generica, e
    // non deve scrivere alcun log.
    expect(fn () => ChangeTicketStatus::run($t, TicketStatus::Assigned, $developer))
        ->toThrow(ValidationException::class);

    expect($t->fresh()->status)->toBe(TicketStatus::Done)
        ->and($t->fresh()->logs()->count())->toBe(0);
});
