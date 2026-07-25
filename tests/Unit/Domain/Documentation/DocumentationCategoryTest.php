<?php

declare(strict_types=1);

use App\Domain\Documentation\Enums\DocumentationCategory;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 2 values of §5.3', function (): void {
    expect(array_map(fn (DocumentationCategory $category): string => $category->value, DocumentationCategory::cases()))
        ->toBe(['internal', 'customer']);
});

test('every case has a non-empty label', function (): void {
    foreach (DocumentationCategory::cases() as $category) {
        expect($category)->toBeInstanceOf(HasLabel::class);
        expect($category->getLabel())->not->toBeEmpty();
    }
});
