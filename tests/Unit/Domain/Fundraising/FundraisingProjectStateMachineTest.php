<?php

declare(strict_types=1);

use App\Domain\Fundraising\Enums\FundraisingProjectStatus;
use App\Domain\Fundraising\StateMachine\FundraisingProjectStateMachine;
use Illuminate\Validation\ValidationException;

test('allowed transitions can be performed', function (FundraisingProjectStatus $from, FundraisingProjectStatus $to): void {
    expect(FundraisingProjectStateMachine::canTransitionTo($from, $to))->toBeTrue();
})->with([
    'draft -> submitted' => [FundraisingProjectStatus::Draft, FundraisingProjectStatus::Submitted],
    'submitted -> approved' => [FundraisingProjectStatus::Submitted, FundraisingProjectStatus::Approved],
    'submitted -> rejected' => [FundraisingProjectStatus::Submitted, FundraisingProjectStatus::Rejected],
    'approved -> completed' => [FundraisingProjectStatus::Approved, FundraisingProjectStatus::Completed],
]);

test('every other transition is forbidden', function (FundraisingProjectStatus $from, FundraisingProjectStatus $to): void {
    expect(FundraisingProjectStateMachine::canTransitionTo($from, $to))->toBeFalse();
})->with([
    'draft -> approved (salta submitted)' => [FundraisingProjectStatus::Draft, FundraisingProjectStatus::Approved],
    'draft -> rejected (salta submitted)' => [FundraisingProjectStatus::Draft, FundraisingProjectStatus::Rejected],
    'draft -> completed' => [FundraisingProjectStatus::Draft, FundraisingProjectStatus::Completed],
    'submitted -> draft (indietro)' => [FundraisingProjectStatus::Submitted, FundraisingProjectStatus::Draft],
    'submitted -> completed (salta approved)' => [FundraisingProjectStatus::Submitted, FundraisingProjectStatus::Completed],
    'approved -> submitted (indietro)' => [FundraisingProjectStatus::Approved, FundraisingProjectStatus::Submitted],
    'approved -> rejected' => [FundraisingProjectStatus::Approved, FundraisingProjectStatus::Rejected],
    'rejected -> qualunque altro stato (terminale)' => [FundraisingProjectStatus::Rejected, FundraisingProjectStatus::Draft],
    'completed -> qualunque altro stato (terminale)' => [FundraisingProjectStatus::Completed, FundraisingProjectStatus::Draft],
]);

test('rejected and completed have no outgoing transition to any other status', function (): void {
    foreach ([FundraisingProjectStatus::Rejected, FundraisingProjectStatus::Completed] as $terminal) {
        foreach (FundraisingProjectStatus::cases() as $to) {
            expect(FundraisingProjectStateMachine::canTransitionTo($terminal, $to))->toBeFalse();
        }
    }
});

test('authorize() does not throw for an allowed transition', function (): void {
    FundraisingProjectStateMachine::authorize(FundraisingProjectStatus::Draft, FundraisingProjectStatus::Submitted);
})->throwsNoExceptions();

test('authorize() throws a localized ValidationException for a forbidden transition', function (): void {
    expect(fn () => FundraisingProjectStateMachine::authorize(FundraisingProjectStatus::Draft, FundraisingProjectStatus::Completed))
        ->toThrow(ValidationException::class, 'La transizione da "Bozza" a "Concluso" non è ammessa.');
});
