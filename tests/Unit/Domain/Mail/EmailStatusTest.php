<?php

declare(strict_types=1);

use App\Domain\Mail\Enums\EmailStatus;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

test('contains the inbound and outbound value sets of §7.3.2', function (): void {
    expect(array_map(fn (EmailStatus $status): string => $status->value, EmailStatus::cases()))
        ->toBe([
            'received', 'parsed', 'classified', 'applied', 'quarantined', 'discarded', 'failed',
            'queued', 'sent', 'bounced', 'suppressed',
        ]);
});

test('implements the Filament label/color contracts with non-empty values per case', function (): void {
    foreach (EmailStatus::cases() as $status) {
        expect($status)->toBeInstanceOf(HasLabel::class)->toBeInstanceOf(HasColor::class);
        expect($status->getLabel())->not->toBeEmpty();
        expect($status->getColor())->not->toBeEmpty();
    }
});
