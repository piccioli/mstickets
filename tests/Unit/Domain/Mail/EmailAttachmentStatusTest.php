<?php

declare(strict_types=1);

use App\Domain\Mail\Enums\EmailAttachmentStatus;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 4 values of the AC', function (): void {
    expect(array_map(fn (EmailAttachmentStatus $status): string => $status->value, EmailAttachmentStatus::cases()))
        ->toBe(['stored', 'rejected_mime', 'rejected_size', 'failed']);
});

test('implements the Filament label/color contracts with non-empty values per case', function (): void {
    foreach (EmailAttachmentStatus::cases() as $status) {
        expect($status)->toBeInstanceOf(HasLabel::class)->toBeInstanceOf(HasColor::class);
        expect($status->getLabel())->not->toBeEmpty();
        expect($status->getColor())->not->toBeEmpty();
    }
});
