<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\CustomerType;

test('contains exactly the 4 tipi cliente CAI di PRD §14 (Fase 7)', function (): void {
    expect(array_map(fn (CustomerType $type): string => $type->value, CustomerType::cases()))
        ->toBe(['sezione', 'gruppo_regionale', 'organo_tecnico_struttura_operativa', 'generico']);
});
