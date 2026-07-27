<?php

declare(strict_types=1);

test('il bottone "Accedi con l\'account CAI" usa la palette --stone-* (disattivato) invece dei colori del bottone outline attivo', function (): void {
    $css = file_get_contents(resource_path('css/marketing.css'));

    preg_match('/\.mkt-btn--outline\.mkt-auth__cai-btn \{([^}]*)\}/', $css, $matches);

    expect($matches)->toHaveCount(2)
        ->and($matches[1])->toContain('var(--stone-500)')
        ->and($matches[1])->toContain('var(--stone-300)')
        ->and($matches[1])->toContain('cursor: not-allowed');
});

test('la modale informativa CAI usa solo token già esistenti per superficie, radius e ombra', function (): void {
    $css = file_get_contents(resource_path('css/marketing.css'));

    preg_match('/\.mkt-auth__cai-modal-dialog \{([^}]*)\}/', $css, $matches);

    expect($matches)->toHaveCount(2)
        ->and($matches[1])->toContain('var(--surface-card)')
        ->and($matches[1])->toContain('var(--radius-card)')
        ->and($matches[1])->toContain('var(--shadow-md)');
});
