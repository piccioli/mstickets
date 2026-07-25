<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketLogEvent;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 8 values of §6.2.1', function (): void {
    expect(array_map(fn (TicketLogEvent $event): string => $event->value, TicketLogEvent::cases()))
        ->toBe([
            'created', 'status_changed', 'assigned', 'updated', 'message_posted',
            'attachment_added', 'attachment_removed', 'system',
        ]);
});

test('every case has a non-empty label and icon', function (): void {
    foreach (TicketLogEvent::cases() as $event) {
        expect($event)->toBeInstanceOf(HasLabel::class)->toBeInstanceOf(HasIcon::class);
        expect($event->getLabel())->not->toBeEmpty();
        expect($event->getIcon())->not->toBeEmpty();
    }
});
