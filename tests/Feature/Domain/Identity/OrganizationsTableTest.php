<?php

declare(strict_types=1);

use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('organizations table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('organizations', ['id', 'name', 'locale', 'created_at', 'updated_at']))->toBeTrue();
});

test('organization_user links an organization to a user', function (): void {
    $organization = Organization::create(['name' => 'CAI Sezione Test']);
    $user = User::factory()->create();

    $organization->users()->attach($user);

    expect($organization->users()->count())->toBe(1)
        ->and($user->organizations()->first()->id)->toBe($organization->id);
});

test('the organization/user pair is unique', function (): void {
    $organization = Organization::create(['name' => 'CAI Sezione Test']);
    $user = User::factory()->create();

    $organization->users()->attach($user);

    expect(fn () => $organization->users()->attach($user))->toThrow(QueryException::class);
});
