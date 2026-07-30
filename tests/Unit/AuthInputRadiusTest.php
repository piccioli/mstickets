<?php

declare(strict_types=1);

test('i campi input del form auth usano il token di border-radius previsto dal design', function (): void {
    $css = file_get_contents(resource_path('css/marketing.css'));

    preg_match('/\.mkt-field \.fi-input-wrp \{([^}]*)\}/', $css, $matches);

    expect($matches)->toHaveCount(2);
    expect($matches[1])->toContain('border-radius: var(--radius-md)')
        ->and($matches[1])->not->toContain('var(--radius-sm)');
});
