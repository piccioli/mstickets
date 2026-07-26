<?php

declare(strict_types=1);

use App\Domain\Ticketing\Rules\TicketProblemReasonRequiredRule;

test('a non-empty problem_reason passes the rule', function (): void {
    expect(ruleFails(new TicketProblemReasonRequiredRule, 'Bloccato da dipendenza esterna'))->toBeFalse();
});

test('null, empty and blank problem_reason all fail the rule', function (?string $value): void {
    expect(ruleFails(new TicketProblemReasonRequiredRule, $value))->toBeTrue();
})->with([null, '', '   ']);

test('a failing problem_reason reports the italian message', function (): void {
    $message = null;

    (new TicketProblemReasonRequiredRule)->validate('problem_reason', '', function (string $m) use (&$message): void {
        $message = $m;
    });

    expect($message)->toBe(TicketProblemReasonRequiredRule::MESSAGE);
});
