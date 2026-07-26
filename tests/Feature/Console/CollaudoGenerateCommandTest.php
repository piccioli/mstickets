<?php

declare(strict_types=1);

use App\Console\Commands\CollaudoGenerateCommand;

it('genera un pdf di collaudo con copertina e sezioni per topic', function () {
    $manifest = [
        'fase' => '0-1',
        'titolo' => 'Fase 0 + Fase 1',
        'parte_1' => [
            'app_url' => 'https://ticket-uat.montagnaservizi.com',
            'mailpit_url' => 'https://mailpit-ticket-uat.montagnaservizi.com',
            'credenziali' => [
                ['ruolo' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password'],
            ],
        ],
        'topics' => [
            [
                'titolo' => 'Autenticazione',
                'test' => [
                    [
                        'id' => 'F0-01',
                        'descrizione' => 'Login con utente admin',
                        'test_automatico' => 'tests/Feature/Filament/AdminAccessTest.php::it can access panel',
                    ],
                ],
            ],
        ],
    ];

    $path = app(CollaudoGenerateCommand::class)->buildPdf('0-1', $manifest);

    expect($path)->toBeFile();
    expect(file_get_contents($path))->toStartWith('%PDF');
});
