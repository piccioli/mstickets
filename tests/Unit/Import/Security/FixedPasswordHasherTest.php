<?php

declare(strict_types=1);

use App\Import\Security\FixedPasswordHasher;
use Illuminate\Support\Facades\Hash;

test('hash returns a Laravel hash of the fixed known password, never the raw string', function (): void {
    $hash = FixedPasswordHasher::hash();

    expect($hash)->not->toBe('uat')
        ->and(Hash::check('uat', $hash))->toBeTrue();
});

test('hash is not deterministic byte-per-byte (bcrypt salts randomly on every call)', function (): void {
    expect(FixedPasswordHasher::hash())->not->toBe(FixedPasswordHasher::hash());
});
