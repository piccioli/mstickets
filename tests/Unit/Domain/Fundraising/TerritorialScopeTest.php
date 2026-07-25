<?php

declare(strict_types=1);

use App\Domain\Fundraising\Enums\TerritorialScope;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 6 values of §5.2', function (): void {
    expect(array_map(fn (TerritorialScope $scope): string => $scope->value, TerritorialScope::cases()))
        ->toBe(['cooperation', 'european', 'national', 'regional', 'territorial', 'municipalities']);
});

test('every case has a non-empty label', function (): void {
    foreach (TerritorialScope::cases() as $scope) {
        expect($scope)->toBeInstanceOf(HasLabel::class);
        expect($scope->getLabel())->not->toBeEmpty();
    }
});
