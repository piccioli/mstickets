<?php

declare(strict_types=1);

it('ogni riferimento test_automatico nel manifest esiste davvero nel codice', function () {
    $manifest = require base_path('docs/collaudo/fase-0-1.php');

    foreach ($manifest['topics'] as $topic) {
        foreach ($topic['test'] as $test) {
            $file = $test['test_automatico'];
            expect(base_path($file))->toBeFile("Riferimento mancante per {$test['id']}: {$file}");
        }
    }
});
