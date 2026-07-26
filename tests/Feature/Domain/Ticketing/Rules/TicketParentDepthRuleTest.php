<?php

declare(strict_types=1);

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Rules\TicketParentDepthRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a null parent_id always passes the rule', function (): void {
    expect(ruleFails(new TicketParentDepthRule, null))->toBeFalse();
});

test('selecting a root ticket (no parent of its own) as parent passes the rule', function (): void {
    $root = Ticket::create(['title' => 'Root', 'status_changed_at' => now()]);

    expect(ruleFails(new TicketParentDepthRule, $root->id))->toBeFalse();
});

test('selecting a ticket that is already a child as parent fails the rule', function (): void {
    $root = Ticket::create(['title' => 'Root', 'status_changed_at' => now()]);
    $child = Ticket::create(['title' => 'Child', 'status_changed_at' => now(), 'parent_id' => $root->id]);

    $message = null;

    (new TicketParentDepthRule)->validate('parent_id', $child->id, function (string $m) use (&$message): void {
        $message = $m;
    });

    expect($message)->toBe(TicketParentDepthRule::MESSAGE);
});

test('a ticket that already has children cannot itself become a child', function (): void {
    $futureParent = Ticket::create(['title' => 'Futuro padre con figli', 'status_changed_at' => now()]);
    Ticket::create(['title' => 'Figlio esistente', 'status_changed_at' => now(), 'parent_id' => $futureParent->id]);

    $otherRoot = Ticket::create(['title' => 'Altro root', 'status_changed_at' => now()]);

    $rule = new TicketParentDepthRule($futureParent);

    expect(ruleFails($rule, $otherRoot->id))->toBeTrue();
});

test('a ticket with no children can become a child of a root ticket', function (): void {
    $leaf = Ticket::create(['title' => 'Ticket senza figli', 'status_changed_at' => now()]);
    $root = Ticket::create(['title' => 'Root', 'status_changed_at' => now()]);

    $rule = new TicketParentDepthRule($leaf);

    expect(ruleFails($rule, $root->id))->toBeFalse();
});
