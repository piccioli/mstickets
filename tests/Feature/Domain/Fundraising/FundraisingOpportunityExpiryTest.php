<?php

declare(strict_types=1);

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeFundraisingOpportunityWithDeadline(string $deadline): FundraisingOpportunity
{
    $user = User::factory()->create();

    return FundraisingOpportunity::create([
        'name' => 'Bando test',
        'deadline' => $deadline,
        'created_by' => $user->id,
        'responsible_user_id' => $user->id,
    ]);
}

test('isExpired is false when the deadline is today', function (): void {
    expect(makeFundraisingOpportunityWithDeadline(today()->toDateString())->isExpired())->toBeFalse();
});

test('isExpired is false when the deadline is tomorrow', function (): void {
    expect(makeFundraisingOpportunityWithDeadline(today()->addDay()->toDateString())->isExpired())->toBeFalse();
});

test('isExpired is true when the deadline is yesterday', function (): void {
    expect(makeFundraisingOpportunityWithDeadline(today()->subDay()->toDateString())->isExpired())->toBeTrue();
});

test('scope active returns opportunities whose deadline is today or later', function (): void {
    $active = makeFundraisingOpportunityWithDeadline(today()->toDateString());
    $alsoActive = makeFundraisingOpportunityWithDeadline(today()->addDay()->toDateString());
    $expired = makeFundraisingOpportunityWithDeadline(today()->subDay()->toDateString());

    $ids = FundraisingOpportunity::query()->active()->pluck('id')->all();

    expect($ids)->toEqualCanonicalizing([$active->id, $alsoActive->id])
        ->and($ids)->not->toContain($expired->id);
});

test('scope expired returns opportunities whose deadline is before today', function (): void {
    $active = makeFundraisingOpportunityWithDeadline(today()->toDateString());
    $expired = makeFundraisingOpportunityWithDeadline(today()->subDay()->toDateString());

    $ids = FundraisingOpportunity::query()->expired()->pluck('id')->all();

    expect($ids)->toEqualCanonicalizing([$expired->id])
        ->and($ids)->not->toContain($active->id);
});
