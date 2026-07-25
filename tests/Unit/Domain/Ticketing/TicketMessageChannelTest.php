<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketMessageChannel;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 3 values of §5.2', function (): void {
    expect(array_map(fn (TicketMessageChannel $channel): string => $channel->value, TicketMessageChannel::cases()))
        ->toBe(['web', 'email', 'system']);
});

test('every case has a non-empty label and icon', function (): void {
    foreach (TicketMessageChannel::cases() as $channel) {
        expect($channel)->toBeInstanceOf(HasLabel::class)->toBeInstanceOf(HasIcon::class);
        expect($channel->getLabel())->not->toBeEmpty();
        expect($channel->getIcon())->not->toBeEmpty();
    }
});
