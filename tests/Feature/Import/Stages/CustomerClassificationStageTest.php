<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Models\User;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\CustomerClassificationStage;
use App\Import\Stages\ImportContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

function customerClassificationStageContext(bool $dryRun = false): ImportContext
{
    $importRun = ImportRun::create([
        'started_at' => now(),
        'dump_label' => 'test-dump',
        'stages' => [],
        'status' => ImportRunStatus::Running,
        'is_dry_run' => $dryRun,
    ]);

    return new ImportContext(importRun: $importRun, dryRun: $dryRun);
}

function makeCustomerUser(string $name): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->assignRole('customer');

    return $user;
}

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

test('GR/GP prefix classifies as GruppoRegionale and extracts the region', function (): void {
    $gr = makeCustomerUser('GR Lombardia');
    $gp = makeCustomerUser('GP Abruzzo');

    (new CustomerClassificationStage)->run(customerClassificationStageContext());

    expect($gr->fresh()->customer_type)->toBe(CustomerType::GruppoRegionale)
        ->and($gr->fresh()->region)->toBe(Region::Lombardia)
        ->and($gp->fresh()->customer_type)->toBe(CustomerType::GruppoRegionale)
        ->and($gp->fresh()->region)->toBe(Region::Abruzzo);
});

test('OTCO/SO prefix classifies as OrganoTecnicoStrutturaOperativa with no region', function (): void {
    $user = makeCustomerUser('OTCO/SO Commissione Centrale');

    (new CustomerClassificationStage)->run(customerClassificationStageContext());

    expect($user->fresh()->customer_type)->toBe(CustomerType::OrganoTecnicoStrutturaOperativa)
        ->and($user->fresh()->region)->toBeNull();
});

test('OTCO / SO with spaces around the slash is also recognized', function (): void {
    $user = makeCustomerUser('OTCO / SO Struttura');

    (new CustomerClassificationStage)->run(customerClassificationStageContext());

    expect($user->fresh()->customer_type)->toBe(CustomerType::OrganoTecnicoStrutturaOperativa);
});

test('a pipe-separated name classifies as Sezione and extracts the region, with or without the C.A.I. SEZ. prefix', function (): void {
    $withPrefix = makeCustomerUser('C.A.I. SEZ. Milano | Lombardia');
    $withoutPrefix = makeCustomerUser('Torino | Piemonte');

    (new CustomerClassificationStage)->run(customerClassificationStageContext());

    expect($withPrefix->fresh()->customer_type)->toBe(CustomerType::Sezione)
        ->and($withPrefix->fresh()->region)->toBe(Region::Lombardia)
        ->and($withoutPrefix->fresh()->customer_type)->toBe(CustomerType::Sezione)
        ->and($withoutPrefix->fresh()->region)->toBe(Region::Piemonte);
});

test('a Sezione with nothing after the pipe stays Sezione with a null region, never Generico', function (): void {
    $user = makeCustomerUser('Sezione Fantasma |');

    (new CustomerClassificationStage)->run(customerClassificationStageContext());

    expect($user->fresh()->customer_type)->toBe(CustomerType::Sezione)
        ->and($user->fresh()->region)->toBeNull();
});

test('a name matching no pattern classifies as Generico with no region', function (): void {
    $user = makeCustomerUser('Mario Rossi');

    (new CustomerClassificationStage)->run(customerClassificationStageContext());

    expect($user->fresh()->customer_type)->toBe(CustomerType::Generico)
        ->and($user->fresh()->region)->toBeNull();
});

test('region normalization handles case, apostrophe and hyphen variants from the v1 dump', function (): void {
    $trentino = makeCustomerUser('GR TRENTINO-ALTO ADIGE');
    $altoAdige = makeCustomerUser('GR ALTO ADIGE');
    $valleDAosta = makeCustomerUser("GR VALLE D'AOSTA");
    $friuli = makeCustomerUser('gr friuli-venezia giulia');

    (new CustomerClassificationStage)->run(customerClassificationStageContext());

    expect($trentino->fresh()->region)->toBe(Region::TrentinoAltoAdige)
        ->and($altoAdige->fresh()->region)->toBe(Region::TrentinoAltoAdige)
        ->and($valleDAosta->fresh()->region)->toBe(Region::ValleDAosta)
        ->and($friuli->fresh()->region)->toBe(Region::FriuliVeneziaGiulia);
});

test('an unnormalizable region logs a warning and leaves region null instead of throwing', function (): void {
    Log::spy();

    $user = makeCustomerUser('GR Atlantide');

    $result = (new CustomerClassificationStage)->run(customerClassificationStageContext());

    expect($user->fresh()->customer_type)->toBe(CustomerType::GruppoRegionale)
        ->and($user->fresh()->region)->toBeNull()
        ->and($result->updated)->toBe(1);

    Log::shouldHaveReceived('warning')->once();
});

test('a non-customer user is never touched', function (): void {
    $user = User::factory()->create(['name' => 'GR Lombardia']);
    $user->assignRole('manager');

    (new CustomerClassificationStage)->run(customerClassificationStageContext());

    expect($user->fresh()->customer_type)->toBeNull()
        ->and($user->fresh()->region)->toBeNull();
});

test('re-running the stage on the same data is idempotent: second run only skips', function (): void {
    makeCustomerUser('GR Lombardia');
    makeCustomerUser('Torino | Piemonte');
    makeCustomerUser('Mario Rossi');

    $stage = new CustomerClassificationStage;
    $first = $stage->run(customerClassificationStageContext());
    $second = $stage->run(customerClassificationStageContext());

    expect($first->read)->toBe(3)
        ->and($first->updated)->toBe(3)
        ->and($first->skipped)->toBe(0)
        ->and($second->read)->toBe(3)
        ->and($second->updated)->toBe(0)
        ->and($second->skipped)->toBe(3);
});

test('--dry-run does not persist any classification', function (): void {
    $user = makeCustomerUser('GR Lombardia');

    (new CustomerClassificationStage)->run(customerClassificationStageContext(dryRun: true));

    expect($user->fresh()->customer_type)->toBeNull()
        ->and($user->fresh()->region)->toBeNull();
});
