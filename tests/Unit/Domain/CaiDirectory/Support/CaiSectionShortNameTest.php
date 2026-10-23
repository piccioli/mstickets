<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Support\CaiSectionShortName;

test('a section name becomes the short form of the trails list', function (string $name, string $short): void {
    expect(CaiSectionShortName::of($name))->toBe($short);
})->with([
    'nome semplice' => ['SEZ. PARMA', 'CAI Parma'],
    'più parole e preposizioni' => ['SEZ. DOMEGGE DI CADORE', 'CAI Domegge di Cadore'],
    'sigla puntata' => ['SEZ. S.A.T.', 'SAT'],
    'sigla e città' => ['SEZ. U.L.E. GENOVA', 'ULE Genova'],
    'suffisso Alto Adige' => ['SEZ. MERANO C.A.I. A.A.', 'CAI Merano'],
    'suffisso senza spazio' => ['SEZ. VAL GARDENA C.A.I.A.A.', 'CAI Val Gardena'],
    'apostrofo' => ["SEZ. VALLELONGA - COPPO DELL'ORSO", "CAI Vallelonga - Coppo dell'Orso"],
    'trattino' => ['SEZ. VERBANO-VERBANIA', 'CAI Verbano-Verbania'],
    'punto dentro la parola' => ['SEZ. SESTO S.GIOVANNI', 'CAI Sesto S.Giovanni'],
    'senza prefisso' => ['ASSOC.GUIDE ALPINE ITAL.', 'CAI Assoc.Guide Alpine Ital.'],
]);
