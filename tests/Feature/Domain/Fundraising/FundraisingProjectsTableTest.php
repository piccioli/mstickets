<?php

declare(strict_types=1);

use App\Domain\Fundraising\Enums\FundraisingProjectStatus;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function makeFundraisingProject(): FundraisingProject
{
    $user = User::factory()->create();
    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando test',
        'deadline' => '2026-12-31',
        'created_by' => $user->id,
        'responsible_user_id' => $user->id,
    ]);

    return FundraisingProject::create([
        'title' => 'Progetto test',
        'fundraising_opportunity_id' => $opportunity->id,
        'created_by' => $user->id,
    ]);
}

test('fundraising_projects table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('fundraising_projects', [
        'id', 'title', 'fundraising_opportunity_id', 'lead_user_id', 'created_by', 'responsible_user_id',
        'description', 'status', 'requested_amount', 'approved_amount', 'submitted_at', 'decided_at',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('defaults status to draft', function (): void {
    $project = makeFundraisingProject();

    expect($project->fresh()->status)->toBe(FundraisingProjectStatus::Draft);
});

test('cascades on opportunity delete', function (): void {
    $project = makeFundraisingProject();

    $project->fundraisingOpportunity->delete();

    expect(FundraisingProject::find($project->id))->toBeNull();
});

test('fundraising_project_partners table has a unique constraint on the pair', function (): void {
    $project = makeFundraisingProject();
    $partner = User::factory()->create();

    DB::table('fundraising_project_partners')->insert([
        'fundraising_project_id' => $project->id,
        'user_id' => $partner->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('fundraising_project_partners')->insert([
        'fundraising_project_id' => $project->id,
        'user_id' => $partner->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('partners relation attaches users via the pivot table', function (): void {
    $project = makeFundraisingProject();
    $partner = User::factory()->create();

    $project->partners()->attach($partner);

    expect($project->partners()->pluck('users.id')->all())->toBe([$partner->id]);
});

test('a ticket can be linked to a fundraising project', function (): void {
    $project = makeFundraisingProject();

    $ticket = Ticket::create([
        'title' => 'Ticket collegato a bando',
        'status_changed_at' => now(),
        'fundraising_project_id' => $project->id,
    ]);

    expect($ticket->fundraisingProject->is($project))->toBeTrue()
        ->and($project->tickets()->pluck('id')->all())->toBe([$ticket->id]);

    $project->delete();

    expect($ticket->fresh()->fundraising_project_id)->toBeNull();
});
