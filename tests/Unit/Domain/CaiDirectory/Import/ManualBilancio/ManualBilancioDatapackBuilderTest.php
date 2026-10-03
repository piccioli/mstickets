<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\ManualBilancio\ManualBilancioAnomaly as A;
use App\Domain\CaiDirectory\Import\ManualBilancio\ManualBilancioDatapackBuilder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

function makeBilanciStaging(): string
{
    $dir = sys_get_temp_dir().'/bilanci-'.bin2hex(random_bytes(4)).'/bilanci-sezioni-2026';
    $put = static function (string $rel, string $content) use ($dir): void {
        $path = $dir.'/normalized/'.$rel;
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, $content);
    };

    $put('Toscana/9226005 - CAI Carrara/9226005 - CAI Carrara - Mod D - Rendiconto per cassa.pdf', '%PDF-1.4 uno');
    $put('Toscana/9226005 - CAI Carrara/9226005 - CAI Carrara - Bilancio consuntivo.pdf', '%PDF-1.4 due');
    $put('Toscana/9226005 - CAI Carrara/9226005 - CAI Carrara - Copia.pdf', '%PDF-1.4 due');
    $put('Toscana/9226005 - CAI Carrara/.DS_Store', 'x');
    $put('Toscana/.DS_Store', 'x');
    $put('Lombardia/9216031 - CAI Bergamo/9216031 - CAI Bergamo - Nota integrativa.pdf', 'b');
    $put('Lombardia/9216031 - CAI Bergamo/nome libero.pdf', 'c');
    $put('Lazio/9258001 - CAI Roma/9258001 - CAI Roma - Quote associative 2026.pdf', 'd');
    $put('Lazio/9299999 - CAI Fantasma/9299999 - CAI Fantasma - Bilancio consuntivo.pdf', 'e');
    $put('Lazio/9258002 - CAI Latina/9258002 - CAI Latina - Bilancio consuntivo.pdf', 'f');

    $writer = new Writer;
    $writer->openToFile($dir.'/2026_Campagna_Sezioni.xlsx');
    $writer->getCurrentSheet()->setName('Sezioni');
    foreach ([
        ['', 'Nome', 'Regione', 'Bilanci'],
        ['9226005', 'CAI Carrara', 'TOSCANA', 'RICEVUTO'],
        ['9216031', 'CAI Bergamo', 'LOMBARDIA', 'DA VERIFICARE'],
        ['9258001', 'CAI Roma', 'LAZIO', 'RICEVUTO'],
        ['9216157', 'CAI Iseo', 'LOMBARDIA', 'RICEVUTO'],
        ['9212045', 'CAI Fuori', 'LOMBARDIA', 'RICEVUTO'],
        ['9299999', 'CAI Fantasma', 'LAZIO', 'RICEVUTO'],
    ] as $row) {
        $writer->addRow(Row::fromValues($row));
    }
    $writer->close();

    return $dir;
}

test('ManualBilancioDatapackBuilder builds rows and reports every anomaly code', function (): void {
    $dir = makeBilanciStaging();
    $inDatapack = ['9226005', '9216031', '9258001', '9216157', '9258002'];

    $result = ManualBilancioDatapackBuilder::build($dir, $inDatapack);

    // Ordine: regione, codice, nome file. Il duplicato resta (avviso), il resto è scartato.
    expect(array_map(fn ($r) => $r->regione.'|'.$r->fileName, $result->rows))->toBe([
        'Lazio|9258001 - CAI Roma - Quote associative 2026.pdf',
        'Lazio|9258002 - CAI Latina - Bilancio consuntivo.pdf',
        'Lombardia|9216031 - CAI Bergamo - Nota integrativa.pdf',
        'Toscana|9226005 - CAI Carrara - Bilancio consuntivo.pdf',
        'Toscana|9226005 - CAI Carrara - Copia.pdf',
        'Toscana|9226005 - CAI Carrara - Mod D - Rendiconto per cassa.pdf',
    ])->and($result->filesCount())->toBe(6)
        ->and($result->sectionsCount())->toBe(4);

    $modD = $result->rows[5];
    expect($modD->codiceCai)->toBe('9226005')
        ->and($modD->tipo)->toBe('mod_d')
        ->and($modD->titolo)->toBe('Mod D - Rendiconto per cassa')
        ->and($modD->anno)->toBe(2025)
        ->and($modD->path)->toBe('bilanci-sezioni-2026/normalized/Toscana/9226005 - CAI Carrara/9226005 - CAI Carrara - Mod D - Rendiconto per cassa.pdf')
        ->and($modD->size)->toBe(strlen('%PDF-1.4 uno'))
        ->and($modD->hashSha256)->toBe(hash('sha256', '%PDF-1.4 uno'))
        ->and($modD->mimeType)->not->toBe('');
    expect($result->rows[0]->anno)->toBe(2026);

    $counts = $result->anomalyCounts();
    ksort($counts);
    expect($counts)->toBe([
        A::CODICE_EXCEL_NON_NEL_DATAPACK => 2,
        A::FILE_SENZA_RICEVUTO => 1,
        A::HASH_DUPLICATO_STESSA_SEZIONE => 1,
        A::NOME_NON_CONFORME => 1,
        A::RICEVUTO_SENZA_FILE => 1,
        A::SEZIONE_NON_IN_EXCEL => 1,
        A::SEZIONE_NON_NEL_DATAPACK => 1,
    ]);
    expect($result->anomaliesOf(A::RICEVUTO_SENZA_FILE)[0]->codiceCai)->toBe('9216157')
        ->and($result->anomaliesOf(A::FILE_SENZA_RICEVUTO)[0]->codiceCai)->toBe('9216031')
        ->and($result->anomaliesOf(A::SEZIONE_NON_IN_EXCEL)[0]->codiceCai)->toBe('9258002')
        ->and($result->anomaliesOf(A::SEZIONE_NON_NEL_DATAPACK)[0]->codiceCai)->toBe('9299999');
});

test('ManualBilancioDatapackBuilder skips the datapack check when codes are null and rejects a missing staging', function (): void {
    $dir = makeBilanciStaging();

    $result = ManualBilancioDatapackBuilder::build($dir, null);

    expect($result->anomaliesOf(A::SEZIONE_NON_NEL_DATAPACK))->toBe([])
        ->and($result->anomaliesOf(A::CODICE_EXCEL_NON_NEL_DATAPACK))->toBe([])
        ->and($result->filesCount())->toBe(7);

    expect(fn () => ManualBilancioDatapackBuilder::build('/tmp/inesistente-'.uniqid(), null))
        ->toThrow(InvalidArgumentException::class);
});
