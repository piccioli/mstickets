<?php

declare(strict_types=1);

use App\Domain\Ticketing\Rules\TicketWaitingReasonRequiredRule;

test('a non-empty waiting_reason passes the rule', function (): void {
    expect(ruleFails(new TicketWaitingReasonRequiredRule, 'In attesa di risposta del cliente'))->toBeFalse();
});

test('null, empty and blank waiting_reason all fail the rule', function (?string $value): void {
    expect(ruleFails(new TicketWaitingReasonRequiredRule, $value))->toBeTrue();
})->with([null, '', '   ']);

test('a failing waiting_reason reports the italian message', function (): void {
    $message = null;

    (new TicketWaitingReasonRequiredRule)->validate('waiting_reason', '', function (string $m) use (&$message): void {
        $message = $m;
    });

    expect($message)->toBe(TicketWaitingReasonRequiredRule::MESSAGE);
});
