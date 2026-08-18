<?php

declare(strict_types=1);

/**
 * §7.6 (problema 14, US-320): ogni chiave __()/trans() usata dal codice
 * della pipeline email (Action/Listener/Mailable + viste Blade del
 * catalogo E1-E9) deve esistere in ENTRAMBE le lingue supportate
 * (lang/it.json, lang/en.json), con un valore non vuoto — mai una chiave
 * grezza mostrata al destinatario. Un fallimento qui blocca la CI, non un
 * semplice warning.
 */
function extractTranslationKeysFromDirectory(string $directory): array
{
    $keys = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        preg_match_all('/\b(?:__|trans)\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $contents, $matches);

        foreach ($matches[1] as $key) {
            $keys[stripslashes($key)] = true;
        }
    }

    return array_keys($keys);
}

test('every translation key used by the Fase 3 mail pipeline exists, non-empty, in both it.json and en.json', function (): void {
    $keys = [
        ...extractTranslationKeysFromDirectory(app_path('Domain/Mail')),
        ...extractTranslationKeysFromDirectory(resource_path('views/emails')),
        ...extractTranslationKeysFromDirectory(resource_path('views/components/emails')),
    ];

    expect($keys)->not->toBeEmpty();

    $it = json_decode(file_get_contents(base_path('lang/it.json')), true);
    $en = json_decode(file_get_contents(base_path('lang/en.json')), true);

    $missing = [];

    foreach (array_unique($keys) as $key) {
        if (! array_key_exists($key, $it) || trim((string) $it[$key]) === '') {
            $missing[] = "it.json: {$key}";
        }

        if (! array_key_exists($key, $en) || trim((string) $en[$key]) === '') {
            $missing[] = "en.json: {$key}";
        }
    }

    expect($missing)->toBe([]);
});
