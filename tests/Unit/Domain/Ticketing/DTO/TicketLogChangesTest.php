<?php

declare(strict_types=1);

use App\Domain\Ticketing\DTO\TicketLogChanges;

test('assigneeChanged serializes the previous and new assignee id', function (): void {
    expect(TicketLogChanges::assigneeChanged(1, 2)->toArray())->toBe([
        'assignee_id' => ['from' => 1, 'to' => 2],
    ]);
});

test('assigneeChanged supports a null previous assignee', function (): void {
    expect(TicketLogChanges::assigneeChanged(null, 2)->toArray())->toBe([
        'assignee_id' => ['from' => null, 'to' => 2],
    ]);
});

test('descriptionChanged never records the field value, only the changed marker', function (): void {
    expect(TicketLogChanges::descriptionChanged()->toArray())->toBe([
        'description' => 'changed',
    ]);
});
