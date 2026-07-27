<?php

declare(strict_types=1);

use App\Support\Collaudo\CollaudoTestReference;

it('ogni riferimento test_automatico nel manifest fase-0-1 esiste davvero nel codice', function () {
    assertManifestReferencesResolve(base_path('docs/collaudo/fase-0-1.php'));
});

it('ogni riferimento test_automatico nel manifest fase-1a esiste davvero nel codice', function () {
    assertManifestReferencesResolve(base_path('docs/collaudo/fase-1a.php'));
});

/**
 * Riusata dai due manifest attivi (fase-0-1.php, fase-1a.php): estrarre in una funzione evita di
 * duplicare il doppio controllo (esistenza file + presenza della descrizione citata) quando una
 * fase futura introdurrà un terzo manifest.
 */
function assertManifestReferencesResolve(string $manifestPath): void
{
    $manifest = require $manifestPath;

    foreach ($manifest['topics'] as $topic) {
        foreach ($topic['test'] as $test) {
            $reference = $test['test_automatico'];
            $file = base_path(CollaudoTestReference::file($reference));

            expect($file)->toBeFile("Riferimento mancante per {$test['id']}: {$reference}");

            // Verifica per-descrizione: la descrizione citata deve esistere davvero
            // dentro il file (non basta l'esistenza del file, §"Processo di collaudo").
            $description = CollaudoTestReference::description($reference);
            expect($description)->not->toBeNull("Il riferimento {$test['id']} deve avere un suffisso ::descrizione: {$reference}");
            expect(str_contains((string) file_get_contents($file), (string) $description))->toBeTrue(
                "La descrizione del test {$test['id']} non è presente in {$file}: «{$description}»",
            );
        }
    }
}
