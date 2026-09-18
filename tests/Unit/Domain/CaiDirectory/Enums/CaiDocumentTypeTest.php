<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiDocumentType;
use Filament\Support\Contracts\HasLabel;

test('every case has a non-empty label', function (): void {
    foreach (CaiDocumentType::cases() as $type) {
        expect($type)->toBeInstanceOf(HasLabel::class)
            ->and($type->getLabel())->not->toBe('');
    }
});

test('only Mod A/B/D and the combinations that contain one trigger financial analysis', function (): void {
    $expectedToTrigger = [
        CaiDocumentType::ModA, CaiDocumentType::ModB, CaiDocumentType::ModD,
        CaiDocumentType::ModAB, CaiDocumentType::CompletoABC, CaiDocumentType::CompletoDRevisori,
    ];

    foreach (CaiDocumentType::cases() as $type) {
        expect($type->triggersFinancialAnalysis())->toBe(in_array($type, $expectedToTrigger, true));
    }
});

test('the fallback case exists with the expected value', function (): void {
    expect(CaiDocumentType::Altro->value)->toBe('altro');
});
