<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

test('v1:import exposes the options required by the PRD (§11.2)', function (): void {
    $definition = Artisan::all()['v1:import']->getDefinition();

    expect($definition->hasOption('dry-run'))->toBeTrue()
        ->and($definition->hasOption('stage'))->toBeTrue()
        ->and($definition->hasOption('from-stage'))->toBeTrue()
        ->and($definition->hasOption('limit'))->toBeTrue()
        ->and($definition->hasOption('truncate'))->toBeTrue()
        ->and($definition->hasOption('anonymize'))->toBeTrue();
});

test('rejects using --stage and --from-stage together', function (): void {
    $this->artisan('v1:import', ['--stage' => 'users', '--from-stage' => 'organizations'])
        ->expectsOutputToContain('--stage e --from-stage non possono essere usate insieme')
        ->assertFailed()
        ->run();
});

test('--truncate is refused outright in a production environment', function (): void {
    app()->instance('env', 'production');

    $this->artisan('v1:import', ['--truncate' => true])
        ->expectsOutputToContain('non è consentito in ambiente di produzione')
        ->assertFailed()
        ->run();
});

test('--truncate outside production asks for interactive confirmation', function (): void {
    $this->artisan('v1:import', ['--truncate' => true])
        ->expectsConfirmation('Sei sicuro di voler troncare le tabelle di destinazione prima di importare?', 'no')
        ->expectsOutputToContain('Import annullato')
        ->assertFailed()
        ->run();
});
