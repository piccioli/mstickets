<?php

declare(strict_types=1);

test('le label dei campi del form auth usano un peso tipografico marcato', function (): void {
    $css = file_get_contents(resource_path('css/marketing.css'));

    preg_match('/\.mkt-field label \{([^}]*)\}/', $css, $matches);

    expect($matches)->toHaveCount(2);
    expect($matches[1])->toContain('font-weight: var(--fw-bold)')
        ->and($matches[1])->not->toContain('var(--fw-semibold)');
});
