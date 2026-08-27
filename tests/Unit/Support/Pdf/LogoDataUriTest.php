<?php

declare(strict_types=1);

use App\Support\Pdf\LogoDataUri;

test('returns null when the configured path is missing, empty, or not a file', function (): void {
    expect(LogoDataUri::resolve(null))->toBeNull()
        ->and(LogoDataUri::resolve(''))->toBeNull()
        ->and(LogoDataUri::resolve('/path/does/not/exist.png'))->toBeNull();
});

test('embeds an existing file as a base64 data URI with the mime type derived from its extension', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'logo').'.png';
    file_put_contents($path, 'fake-png-bytes');

    $dataUri = LogoDataUri::resolve($path);

    unlink($path);

    expect($dataUri)->toBe('data:image/png;base64,'.base64_encode('fake-png-bytes'));
});

test('derives the mime type from svg/jpg/webp extensions, defaulting to png otherwise', function (): void {
    foreach (['svg' => 'image/svg+xml', 'jpg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/png'] as $extension => $expectedMime) {
        $path = tempnam(sys_get_temp_dir(), 'logo').'.'.$extension;
        file_put_contents($path, 'x');

        expect(LogoDataUri::resolve($path))->toStartWith('data:'.$expectedMime.';base64,');

        unlink($path);
    }
});
