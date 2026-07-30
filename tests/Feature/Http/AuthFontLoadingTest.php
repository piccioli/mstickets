<?php

declare(strict_types=1);

test('il login carica il font Manrope (font-face reale + preload), non un fallback silenzioso', function (): void {
    $response = $this->get('/admin/login');

    $response->assertSuccessful();
    $response->assertSee('font-family: "Manrope"', escape: false);
    $response->assertSee('rel="preload" as="font"', escape: false);
    $response->assertSee('manrope-400-normal', escape: false);
});
