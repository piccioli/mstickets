<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\ManualBilancio\CampagnaSezioniIndexReader;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * @param  list<list<mixed>>  $rows
 */
function makeCampagnaXlsx(array $rows, string $sheetName = 'Sezioni'): string
{
    $path = tempnam(sys_get_temp_dir(), 'campagna').'.xlsx';
    $writer = new Writer;
    $writer->openToFile($path);
    $writer->getCurrentSheet()->setName($sheetName);
    foreach ($rows as $row) {
        $writer->addRow(Row::fromValues($row));
    }
    $writer->close();

    return $path;
}

test('CampagnaSezioniIndexReader reads columns by header and normalizes the code', function (): void {
    $path = makeCampagnaXlsx([
        ['', 'Altro', 'Nome', 'Regione', 'Bilanci', 'Link al bilancio', 'Link al bilancio 2'],
        ['9226005', 'x', 'CAI Carrara', 'TOSCANA', 'RICEVUTO', 'https://t.example/1', 'https://t.example/2'],
        [9216157.0, 'x', 'CAI Udine', 'FRIULI-VENEZIA GIULIA', null, null, null],
        [9216158, 'x', 'CAI Trieste', 'FRIULI-VENEZIA GIULIA', 'DA VERIFICARE', null, 'https://t.example/3'],
        [null, 'x', 'Senza codice', 'LAZIO', 'RICEVUTO', null, null],
        ['123', 'x', 'Codice corto', 'LAZIO', 'RICEVUTO', null, null],
    ]);

    $rows = CampagnaSezioniIndexReader::read($path);
    unlink($path);

    // PHP converte le chiavi numeriche in int: confronto sul valore stringa.
    expect(array_map('strval', array_keys($rows)))->toBe(['9226005', '9216157', '9216158'])
        ->and($rows['9226005']->name)->toBe('CAI Carrara')
        ->and($rows['9226005']->region)->toBe('TOSCANA')
        ->and($rows['9226005']->statoBilanci)->toBe('RICEVUTO')
        ->and($rows['9226005']->links)->toBe(['https://t.example/1', 'https://t.example/2'])
        ->and($rows['9216157']->region)->toBe('FRIULI-VENEZIA GIULIA')
        ->and($rows['9216157']->statoBilanci)->toBeNull()
        ->and($rows['9216157']->links)->toBe([])
        ->and($rows['9216158']->links)->toBe(['https://t.example/3']);
});

test('CampagnaSezioniIndexReader throws when the Sezioni sheet is missing', function (): void {
    $path = makeCampagnaXlsx([['', 'Nome']], 'Altro');

    try {
        CampagnaSezioniIndexReader::read($path);
    } finally {
        unlink($path);
    }
})->throws(InvalidArgumentException::class, 'Foglio "Sezioni" assente');

test('CampagnaSezioniIndexReader throws when the file is missing', function (): void {
    CampagnaSezioniIndexReader::read('/tmp/non-esiste-campagna.xlsx');
})->throws(InvalidArgumentException::class, 'File indice della campagna non trovato');
