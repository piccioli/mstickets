<?php

declare(strict_types=1);

test('l\'eyebrow del form di login usa text-transform uppercase', function (): void {
    $css = file_get_contents(resource_path('css/marketing.css'));

    preg_match('/\.mkt-auth__form-eyebrow \{([^}]*)\}/', $css, $matches);

    expect($matches)->toHaveCount(2)
        ->and($matches[1])->toContain('text-transform: uppercase');
});
