<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

test('every case has a label and a color', function (): void {
    foreach (CaiDocumentSource::cases() as $source) {
        expect($source)->toBeInstanceOf(HasLabel::class)
            ->and($source)->toBeInstanceOf(HasColor::class)
            ->and($source->getLabel())->not->toBe('')
            ->and($source->getColor())->not->toBe('');
    }
});

test('the three expected cases exist with the expected string values', function (): void {
    expect(CaiDocumentSource::Runts->value)->toBe('runts');
    expect(CaiDocumentSource::Manual->value)->toBe('manual');
    expect(CaiDocumentSource::Veryfico->value)->toBe('veryfico');
});
