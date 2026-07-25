<?php

declare(strict_types=1);

use App\Domain\Fundraising\Enums\TerritorialScope;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('fundraising_opportunities table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('fundraising_opportunities', [
        'id', 'name', 'official_url', 'endowment_fund', 'deadline', 'program_name', 'sponsor',
        'cofinancing_quota', 'max_contribution', 'territorial_scope', 'beneficiary_requirements',
        'lead_requirements', 'created_by', 'responsible_user_id', 'evaluated_by', 'evaluated_at',
        'evaluation_positive_total', 'evaluation_negative_total', 'evaluation_total',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('defaults territorial_scope to national', function (): void {
    $user = User::factory()->create();

    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando test',
        'deadline' => '2026-12-31',
        'created_by' => $user->id,
        'responsible_user_id' => $user->id,
    ]);

    expect($opportunity->fresh()->territorial_scope)->toBe(TerritorialScope::National);
});

test('casts territorial_scope to its backed enum', function (): void {
    $user = User::factory()->create();

    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando test',
        'deadline' => '2026-12-31',
        'territorial_scope' => TerritorialScope::Regional,
        'created_by' => $user->id,
        'responsible_user_id' => $user->id,
    ]);

    expect($opportunity->fresh()->territorial_scope)->toBe(TerritorialScope::Regional);
});

test('belongs to a creator and a responsible user', function (): void {
    $creator = User::factory()->create();
    $responsible = User::factory()->create();

    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando test',
        'deadline' => '2026-12-31',
        'created_by' => $creator->id,
        'responsible_user_id' => $responsible->id,
    ]);

    expect($opportunity->creator->is($creator))->toBeTrue();
    expect($opportunity->responsibleUser->is($responsible))->toBeTrue();
});

test('evaluated_by is nullable and set null on user delete', function (): void {
    $user = User::factory()->create();
    $evaluator = User::factory()->create();

    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando test',
        'deadline' => '2026-12-31',
        'created_by' => $user->id,
        'responsible_user_id' => $user->id,
        'evaluated_by' => $evaluator->id,
        'evaluated_at' => now(),
    ]);

    $evaluator->forceDelete();

    expect($opportunity->fresh()->evaluated_by)->toBeNull();
});
