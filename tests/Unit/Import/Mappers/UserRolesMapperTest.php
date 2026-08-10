<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Import\Mappers\UserRolesMapper;

test('parses a JSON array with multiple recognized roles', function (): void {
    $mapped = UserRolesMapper::parse('["developer","fundraising"]');

    expect($mapped->roles)->toBe([UserRole::Developer, UserRole::Fundraising])
        ->and($mapped->hadEditor)->toBeFalse()
        ->and($mapped->unrecognized)->toBe([])
        ->and($mapped->parseFailed)->toBeFalse();
});

test('editor is not a role: flagged separately, never in roles', function (): void {
    $mapped = UserRolesMapper::parse('["editor"]');

    expect($mapped->roles)->toBe([])
        ->and($mapped->hadEditor)->toBeTrue()
        ->and($mapped->unrecognized)->toBe([]);
});

test('editor mixed with a recognized role keeps both signals', function (): void {
    $mapped = UserRolesMapper::parse('["developer","editor"]');

    expect($mapped->roles)->toBe([UserRole::Developer])
        ->and($mapped->hadEditor)->toBeTrue();
});

test('unrecognized role tokens are discarded and reported', function (): void {
    $mapped = UserRolesMapper::parse('["developer","ghost"]');

    expect($mapped->roles)->toBe([UserRole::Developer])
        ->and($mapped->unrecognized)->toBe(['ghost'])
        ->and($mapped->parseFailed)->toBeFalse();
});

test('a non-JSON scalar value fails parsing with no roles assigned', function (): void {
    $mapped = UserRolesMapper::parse('not-json');

    expect($mapped->roles)->toBe([])
        ->and($mapped->hadEditor)->toBeFalse()
        ->and($mapped->unrecognized)->toBe([])
        ->and($mapped->parseFailed)->toBeTrue();
});

test('null or empty values produce no roles without a parse failure', function (?string $raw): void {
    $mapped = UserRolesMapper::parse($raw);

    expect($mapped->roles)->toBe([])
        ->and($mapped->hadEditor)->toBeFalse()
        ->and($mapped->parseFailed)->toBeFalse();
})->with([null, '', '   ']);

test('role tokens are matched case-insensitively and trimmed', function (): void {
    $mapped = UserRolesMapper::parse('[" Developer ","EDITOR"]');

    expect($mapped->roles)->toBe([UserRole::Developer])
        ->and($mapped->hadEditor)->toBeTrue();
});
