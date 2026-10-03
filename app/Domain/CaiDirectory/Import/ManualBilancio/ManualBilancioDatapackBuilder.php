<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import\ManualBilancio;

use InvalidArgumentException;

/**
 * Percorre `<staging>/normalized/<Regione>/<codice - Nome>/<file>` e produce le righe di `bilanci_manuali`,
 * confrontandole con l'Excel indice della campagna. Le anomalie sono avvisi: solo `nome_non_conforme` e
 * `sezione_non_nel_datapack` scartano la riga.
 */
final class ManualBilancioDatapackBuilder
{
    private const INDEX_FILE = '2026_Campagna_Sezioni.xlsx';

    private const RICEVUTO = 'RICEVUTO';

    /**
     * @param  list<string>|null  $sectionCodesInDatapack  `null` = nessun controllo di presenza nel datapack
     */
    public static function build(string $stagingDir, ?array $sectionCodesInDatapack): ManualBilancioBuildResult
    {
        $stagingDir = rtrim($stagingDir, '/');
        $normalizedDir = $stagingDir.'/normalized';

        if (! is_dir($normalizedDir)) {
            throw new InvalidArgumentException("Cartella dei bilanci normalizzati non trovata: {$normalizedDir}");
        }

        $index = CampagnaSezioniIndexReader::read($stagingDir.'/'.self::INDEX_FILE);
        $inDatapack = $sectionCodesInDatapack === null ? null : array_flip(array_map('strval', $sectionCodesInDatapack));
        $relativeBase = basename($stagingDir).'/normalized';

        $rows = [];
        $anomalies = [];
        $codesWithFiles = [];
        $notInDatapackReported = [];

        foreach (self::visibleEntries($normalizedDir, dirs: true) as $regione) {
            foreach (self::visibleEntries($normalizedDir.'/'.$regione, dirs: true) as $sectionDir) {
                $folderCode = preg_match('/^(\d{7}) - /u', $sectionDir, $m) === 1 ? $m[1] : null;
                $sectionPath = $normalizedDir.'/'.$regione.'/'.$sectionDir;
                $hashes = [];

                foreach (self::visibleEntries($sectionPath, dirs: false) as $fileName) {
                    $parsed = ManualBilancioFilenameParser::parse($fileName);

                    if ($folderCode === null || $parsed === null || $parsed->codiceCai !== $folderCode) {
                        $anomalies[] = new ManualBilancioAnomaly(
                            ManualBilancioAnomaly::NOME_NON_CONFORME,
                            $folderCode,
                            "File scartato, nome non conforme: {$regione}/{$sectionDir}/{$fileName}",
                        );

                        continue;
                    }

                    if ($inDatapack !== null && ! isset($inDatapack[$folderCode])) {
                        if (! isset($notInDatapackReported[$folderCode])) {
                            $notInDatapackReported[$folderCode] = true;
                            $anomalies[] = new ManualBilancioAnomaly(
                                ManualBilancioAnomaly::SEZIONE_NON_NEL_DATAPACK,
                                $folderCode,
                                "Sezione {$folderCode} assente dal datapack: file scartati ({$sectionDir}).",
                            );
                        }

                        continue;
                    }

                    $fullPath = $sectionPath.'/'.$fileName;
                    $hash = hash_file('sha256', $fullPath);
                    $classification = ManualBilancioTypeMapper::map($parsed->label);

                    if (isset($hashes[$hash])) {
                        $anomalies[] = new ManualBilancioAnomaly(
                            ManualBilancioAnomaly::HASH_DUPLICATO_STESSA_SEZIONE,
                            $folderCode,
                            "Contenuto identico nella sezione {$folderCode}: {$hashes[$hash]} e {$fileName}.",
                        );
                    } else {
                        $hashes[$hash] = $fileName;
                    }

                    $codesWithFiles[$folderCode] = true;
                    $rows[] = new ManualBilancioRow(
                        codiceCai: $folderCode,
                        regione: $regione,
                        anno: $classification->year,
                        tipo: $classification->type->value,
                        titolo: $classification->title,
                        fileName: $fileName,
                        path: "{$relativeBase}/{$regione}/{$sectionDir}/{$fileName}",
                        mimeType: self::mimeType($fullPath),
                        size: (int) filesize($fullPath),
                        hashSha256: $hash,
                    );
                }
            }
        }

        foreach (array_keys($codesWithFiles) as $code) {
            $code = (string) $code;
            if (! isset($index[$code])) {
                $anomalies[] = new ManualBilancioAnomaly(
                    ManualBilancioAnomaly::SEZIONE_NON_IN_EXCEL,
                    $code,
                    "Sezione {$code} con file normalizzati ma assente dall'Excel indice.",
                );
            } elseif ($index[$code]->statoBilanci !== self::RICEVUTO) {
                $anomalies[] = new ManualBilancioAnomaly(
                    ManualBilancioAnomaly::FILE_SENZA_RICEVUTO,
                    $code,
                    "Sezione {$code} ha file normalizzati ma lo stato nell'Excel è ".($index[$code]->statoBilanci ?? '(vuoto)').'.',
                );
            }
        }

        foreach ($index as $code => $row) {
            $code = (string) $code;
            if ($inDatapack !== null && ! isset($inDatapack[$code])) {
                $anomalies[] = new ManualBilancioAnomaly(
                    ManualBilancioAnomaly::CODICE_EXCEL_NON_NEL_DATAPACK,
                    $code,
                    "Codice {$code} ({$row->name}) presente nell'Excel ma non nel datapack: ignorato.",
                );
            } elseif ($row->statoBilanci === self::RICEVUTO && ! isset($codesWithFiles[$code])) {
                $anomalies[] = new ManualBilancioAnomaly(
                    ManualBilancioAnomaly::RICEVUTO_SENZA_FILE,
                    $code,
                    "Sezione {$code} ({$row->name}) risulta RICEVUTO ma non ha file normalizzati.",
                );
            }
        }

        return new ManualBilancioBuildResult($rows, $anomalies);
    }

    /**
     * Nomi (non percorsi) delle voci non nascoste, ordinati in modo deterministico.
     *
     * @return list<string>
     */
    private static function visibleEntries(string $dir, bool $dirs): array
    {
        $names = [];
        foreach (scandir($dir) ?: [] as $name) {
            if (str_starts_with($name, '.')) {
                continue;
            }
            if (is_dir($dir.'/'.$name) === $dirs) {
                $names[] = $name;
            }
        }
        sort($names, SORT_STRING);

        return $names;
    }

    private static function mimeType(string $path): string
    {
        $mime = function_exists('mime_content_type') ? @mime_content_type($path) : false;

        return is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream';
    }
}
