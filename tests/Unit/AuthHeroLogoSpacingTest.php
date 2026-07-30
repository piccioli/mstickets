<?php

declare(strict_types=1);

test('il selettore dello sfondo fotografico del pannello hero è scoped al figlio diretto', function (): void {
    $css = file_get_contents(resource_path('css/marketing.css'));

    // Regressione: `.mkt-auth__panel img` (discendente generico) intercetta anche
    // l'img.mkt-logo annidato dentro .mkt-auth__panel-content, forzandolo con
    // position:absolute/inset:0 e togliendolo dal flusso — qualunque margin-bottom
    // sul logo diventa quindi ininfluente. Lo sfondo fotografico è sempre figlio
    // diretto di .mkt-auth__panel nel markup: il combinatore `>` lo mantiene isolato.
    expect($css)->not->toContain('.mkt-auth__panel img {');
    expect($css)->toContain('.mkt-auth__panel > img {');
});

test('il logo del pannello hero ha un margine inferiore a desktop', function (): void {
    $css = file_get_contents(resource_path('css/marketing.css'));

    preg_match(
        '/@media \(min-width: 900px\) \{\s*\.mkt-auth__panel-content img\.mkt-logo \{([^}]*)\}/',
        $css,
        $matches
    );

    expect($matches)->toHaveCount(2);
    expect($matches[1])->toContain('margin-bottom:')
        ->and($matches[1])->not->toContain('margin-bottom: 0;');
});
