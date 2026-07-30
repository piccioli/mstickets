<?php

declare(strict_types=1);

use App\Console\Commands\CollaudoGenerateCommand;

it('genera la versione dettagliata del pdf quando i file del manuale operativo esistono', function () {
    $path = app(CollaudoGenerateCommand::class)->buildDetailedPdf('0-1');

    expect($path)->toBeFile();

    $contents = file_get_contents($path);
    expect($contents)->toStartWith('%PDF');
    // Soglia ricalibrata nel Task 6 (v0.3.2, migrazione dompdf → LaTeX): il PDF LaTeX reale
    // prodotto da questo test (8 file di docs/collaudo/, incluse le due sorgenti Markdown da
    // 213KB/302KB) misura ~1.42MB (1422724 byte, osservato più volte di seguito nello stesso
    // ambiente Docker ms-latex-spike:1 con contenuto invariato — può oscillare leggermente da
    // build a build per metadati/timestamp interni al PDF, ma non di ordini di grandezza). La
    // vecchia soglia (200_000, tarata su dompdf/HTML) non è più un riferimento valido: pdfLaTeX
    // incorpora i font in modo più compatto di dompdf, ma qui il documento è anche molto più
    // grande (8 sezioni invece del solo manifest), quindi il risultato netto è comunque un PDF
    // più pesante, non più leggero. 1_000_000 lascia margine sotto il valore osservato pur
    // restando una verifica indiretta solida che tutte le sezioni siano state incluse, non solo
    // la copertina.
    expect(strlen($contents))->toBeGreaterThan(1_000_000);
});

it('collaudo:generate usa la versione dettagliata per la fase 0-1 quando i file esistono', function () {
    $this->artisan('collaudo:generate', ['fase' => '0-1'])
        ->assertSuccessful();
});
