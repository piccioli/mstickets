<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('users table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('users', [
        'id', 'name', 'email', 'email_verified_at', 'password', 'remember_token',
        'locale', 'drive_url', 'drive_budget_url', 'deactivated_at',
        'created_at', 'updated_at', 'deleted_at',
    ]))->toBeTrue();
});

test('users table has the customer_type/region columns of Fase 7 (US-701)', function (): void {
    expect(Schema::hasColumns('users', ['customer_type', 'region']))->toBeTrue();
});

test('a user without customer_type/region stays null without errors', function (): void {
    $user = User::factory()->create();

    expect($user->fresh()->customer_type)->toBeNull()
        ->and($user->fresh()->region)->toBeNull();
});

test('customer_type/region are cast to their backed enum in both directions', function (): void {
    $user = User::factory()->create([
        'customer_type' => CustomerType::Sezione,
        'region' => Region::Lombardia,
    ]);

    expect($user->fresh()->customer_type)->toBe(CustomerType::Sezione)
        ->and($user->fresh()->region)->toBe(Region::Lombardia);
});

test('locale defaults to it and password is nullable', function (): void {
    $user = User::create(['name' => 'Senza Password', 'email' => 'senza-password@example.test']);

    expect($user->fresh()->locale)->toBe('it')
        ->and($user->fresh()->password)->toBeNull();
});

test('email is unique at the database level', function (): void {
    User::factory()->create(['email' => 'duplicate@example.test']);

    expect(fn () => User::factory()->create(['email' => 'duplicate@example.test']))
        ->toThrow(QueryException::class);
});

test('email can be looked up case-insensitively via the functional index', function (): void {
    User::factory()->create(['email' => 'Mixed.Case@Example.test']);

    $found = User::query()
        ->whereRaw('lower(email) = ?', [mb_strtolower('mixed.CASE@example.TEST')])
        ->first();

    expect($found)->not->toBeNull();
});

test('users support soft deletes', function (): void {
    $user = User::factory()->create();

    $user->delete();

    expect(User::find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id))->not->toBeNull();
});
