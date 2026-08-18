<?php

declare(strict_types=1);

use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Support\RecipientLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('resolves to users.locale when it is set', function (): void {
    $user = User::factory()->create(['locale' => 'en']);

    expect(RecipientLocale::resolve($user))->toBe('en');
});

test('falls back to the first organization locale when users.locale is empty', function (): void {
    $user = User::factory()->create(['locale' => '']);
    $organization = Organization::create(['name' => 'Acme', 'locale' => 'en']);
    $user->organizations()->attach($organization);

    expect(RecipientLocale::resolve($user->refresh()))->toBe('en');
});

test('falls back to config app.locale when neither users.locale nor an organization locale is set', function (): void {
    config(['app.locale' => 'en']);

    $user = User::factory()->create(['locale' => '']);

    expect(RecipientLocale::resolve($user))->toBe('en');
});

test('prefers users.locale over an organization locale when both are set', function (): void {
    $user = User::factory()->create(['locale' => 'it']);
    $organization = Organization::create(['name' => 'Acme', 'locale' => 'en']);
    $user->organizations()->attach($organization);

    expect(RecipientLocale::resolve($user->refresh()))->toBe('it');
});
