<?php

declare(strict_types=1);

use App\Domain\Ticketing\Rules\TicketTesterRequiredRule;

test('a valorized tester_id passes the rule', function (): void {
    expect(ruleFails(new TicketTesterRequiredRule, 42))->toBeFalse();
});

test('a null tester_id fails the rule with the italian message', function (): void {
    $message = null;

    (new TicketTesterRequiredRule)->validate('tester_id', null, function (string $m) use (&$message): void {
        $message = $m;
    });

    expect($message)->toBe(TicketTesterRequiredRule::MESSAGE);
});
