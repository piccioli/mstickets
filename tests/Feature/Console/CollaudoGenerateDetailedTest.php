<?php

declare(strict_types=1);

use App\Console\Commands\CollaudoGenerateCommand;

it('genera la versione dettagliata del pdf quando i file del manuale operativo esistono', function () {
    $path = app(CollaudoGenerateCommand::class)->buildDetailedPdf('0-1');

    expect($path)->toBeFile();

    $contents = file_get_contents($path);
    expect($contents)->toStartWith('%PDF');
    // Il manuale dettagliato (istruzioni + matrice + 130 test + registro + verbale) produce un
    // PDF sensibilmente più grande della versione leggera basata solo sul manifest: usato come
    // verifica indiretta che tutte le sezioni siano state incluse, non solo la copertina.
    expect(strlen($contents))->toBeGreaterThan(200_000);
});

it('collaudo:generate usa la versione dettagliata per la fase 0-1 quando i file esistono', function () {
    $this->artisan('collaudo:generate', ['fase' => '0-1'])
        ->assertSuccessful();
});
