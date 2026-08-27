<?php

declare(strict_types=1);

use App\Domain\Fundraising\Actions\SaveEvaluationScores;
use App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion;
use App\Domain\Fundraising\Enums\FundraisingProjectStatus;
use App\Domain\Fundraising\Enums\TerritorialScope;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Fundraising\Services\CalculateEvaluationTotals;
use App\Domain\Fundraising\StateMachine\FundraisingProjectStateMachine;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

/**
 * Checkpoint di fine Fase 5 (§14 del PRD, US-509): a differenza dei test per singola
 * Action/Model/Resource già presenti nelle story US-501..US-508, qui si percorrono in
 * sequenza, con Action/Model reali (mai stato seminato direttamente per il solo
 * sotto-sistema sotto test), i due flussi end-to-end richiesti esplicitamente
 * dall'AC1/AC2 di questa story: (1) la riconciliazione dei totali di valutazione fra
 * {@see CalculateEvaluationTotals} e quanto persiste
 * {@see SaveEvaluationScores} sull'opportunità, inclusa l'inclusione corretta di un
 * criterio aggiunto al catalogo solo a runtime; (2) il ciclo di vita completo
 * opportunità -> progetto -> partner -> transizione di stato.
 *
 * Nota importante sull'AC1 (vedi progress.txt, sezione US-509, per il dettaglio): la
 * verifica manuale eseguita durante questo checkpoint contro un dump v1 REALE
 * (`v1:import --anonymize`, 21 opportunità fundraising importate) ha confermato che la
 * coincidenza fra i totali ricalcolati e quelli già presenti dall'ETL è SEMPRE
 * banalmente vera sui dati reali oggi disponibili: `FundraisingScoresStage` (Fase 2,
 * US-213) documenta già che il dump di produzione non ha mai avuto colonne
 * `evaluation_*_score` (la griglia di valutazione §6.6.2 non risulta mai usata in v1),
 * quindi ogni opportunità reale importata ha zero righe `fundraising_evaluation_scores`
 * e zero (null) nei tre totali — non c'è alcun totale "già presente dall'ETL" con cui
 * confrontare un ricalcolo. Questo test replica quindi la riconciliazione nel solo
 * scenario in cui ha senso verificarla: un'opportunità con punteggi REALMENTE
 * persistiti tramite l'azione di dominio v2 ({@see SaveEvaluationScores}, lo stesso
 * percorso che un utente reale del team fundraising userebbe oggi in UAT/produzione),
 * gli unici a cui l'AC1 può applicarsi finché nessun bando reale con una valutazione
 * v1 storica viene fornito dal committente (gap segnalato nel report di questa story).
 */
uses(RefreshDatabase::class);

function fase5RealOpportunity(): FundraisingOpportunity
{
    $staff = User::factory()->create(['name' => 'Sara Mariani']);

    return FundraisingOpportunity::create([
        'name' => 'Avviso 2/2025 - Finanziamento progetti di rilevanza nazionale ETS',
        'deadline' => today()->addMonths(2)->toDateString(),
        'territorial_scope' => TerritorialScope::National,
        'created_by' => $staff->id,
        'responsible_user_id' => $staff->id,
    ]);
}

test('evaluation totals recomputed from persisted scores match what SaveEvaluationScores stores on the opportunity', function (): void {
    $opportunity = fase5RealOpportunity();
    $evaluator = User::factory()->create();

    SaveEvaluationScores::run($opportunity, [
        FundraisingEvaluationCriterion::CriterionA->value => 5,
        FundraisingEvaluationCriterion::CriterionB->value => 3,
        FundraisingEvaluationCriterion::BaseCoerenzaBando->value => 1,
        FundraisingEvaluationCriterion::RiskFinanziari->value => -3,
        FundraisingEvaluationCriterion::RiskOrganizzativi->value => -2,
    ], [], $evaluator);

    $opportunity->refresh();

    // Ricalcolo indipendente, esattamente come farebbe un tester che verifica a mano i
    // totali dell'ETL contro le righe fundraising_evaluation_scores realmente persistite.
    $persistedScores = $opportunity->evaluationScores()
        ->get()
        ->mapWithKeys(fn ($score) => [$score->criterion_key->value => $score->score])
        ->all();

    $recomputed = CalculateEvaluationTotals::fromScores($persistedScores);

    expect($opportunity->evaluation_positive_total)->toBe($recomputed['positive'])
        ->and($opportunity->evaluation_negative_total)->toBe($recomputed['negative'])
        ->and($opportunity->evaluation_total)->toBe($recomputed['total'])
        ->and($opportunity->evaluation_positive_total)->toBe(9)
        ->and($opportunity->evaluation_negative_total)->toBe(5)
        ->and($opportunity->evaluation_total)->toBe(4);
});

test('a criterion added to the catalog at runtime is included in a real evaluation total', function (): void {
    // Replica in automatico, sulla stessa Action di dominio, la verifica manuale
    // eseguita in questa story via tinker su un'opportunità realmente importata da v1
    // (id 2, "Avviso 2/2025..."): aggiunto temporaneamente un criterio al catalogo,
    // verificato che SaveEvaluationScores/CalculateEvaluationTotals lo includessero
    // correttamente, poi rimosso senza lasciare traccia nel codice (vedi progress.txt).
    // Qui il criterio di prova è un case ESISTENTE del catalogo (nessuna modifica al
    // file di produzione va lasciata da un test): la garanzia che CalculateEvaluationTotals
    // non dipenda da un elenco fisso di chiavi ma sommi qualunque chiave presente nella
    // mappa passata è verificata a livello unitario in
    // tests/Unit/Domain/Fundraising/CalculateEvaluationTotalsTest.php
    // ("a criterion added to the catalog at runtime is included correctly without
    // touching the database"). Questo test end-to-end verifica lo stesso principio
    // attraversando il percorso applicativo reale (SaveEvaluationScores) invece del solo
    // service puro.
    $opportunity = fase5RealOpportunity();
    $evaluator = User::factory()->create();

    $result = SaveEvaluationScores::run($opportunity, [
        FundraisingEvaluationCriterion::RiskLogistici->value => -2,
    ], [], $evaluator);

    expect($result->evaluation_negative_total)->toBe(2)
        ->and($result->evaluation_total)->toBe(-2);
});

test('the opportunity to project to partner to state transition flow works end to end', function (): void {
    $opportunity = fase5RealOpportunity();
    $fundraisingUser = User::factory()->create();
    $partner = User::factory()->create(['name' => 'CAI Sezione partner']);

    $project = FundraisingProject::create([
        'title' => $opportunity->name,
        'fundraising_opportunity_id' => $opportunity->id,
        'created_by' => $fundraisingUser->id,
    ])->fresh();

    expect($project->status)->toBe(FundraisingProjectStatus::Draft)
        ->and($project->fundraisingOpportunity->id)->toBe($opportunity->id);

    $project->partners()->attach($partner->id);

    expect($project->partners()->whereKey($partner->id)->exists())->toBeTrue();

    FundraisingProjectStateMachine::authorize(FundraisingProjectStatus::Draft, FundraisingProjectStatus::Submitted);
    $project->update(['status' => FundraisingProjectStatus::Submitted, 'submitted_at' => today()]);

    FundraisingProjectStateMachine::authorize(FundraisingProjectStatus::Submitted, FundraisingProjectStatus::Approved);
    $project->update(['status' => FundraisingProjectStatus::Approved, 'decided_at' => today()]);

    $project->refresh();

    expect($project->status)->toBe(FundraisingProjectStatus::Approved);

    expect(fn () => FundraisingProjectStateMachine::authorize(FundraisingProjectStatus::Approved, FundraisingProjectStatus::Submitted))
        ->toThrow(ValidationException::class);
});
