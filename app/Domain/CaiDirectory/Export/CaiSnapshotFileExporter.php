<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Export;

use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Copia nel datapack (`<cartella datapack>/snapshot-files/<hash[0..1]>/<hash>.<ext>`) i file dei documenti
 * RUNTS, content-addressed: il nome è lo sha256 del contenuto (ricalcolato dal file, mai da
 * `cai_documents.hash`), quindi documenti con lo stesso contenuto condividono un solo file. I documenti
 * manuali non si copiano (arrivano da `bilanci_manuali`). Tutto in streaming: nessun PDF intero in memoria.
 */
final class CaiSnapshotFileExporter
{
    public const DIRECTORY = 'snapshot-files';

    private const DISK = 'cai-documents';

    /**
     * @param  (Closure(string): (float|int|false))|null  $freeSpace  Spazio libero in byte per una cartella (default `disk_free_space`); iniettabile nei test.
     */
    public function __construct(private readonly ?Closure $freeSpace = null) {}

    /**
     * Calcola il piano (hash, duplicati, mancanti, spazio), verifica lo spazio libero e — se non `$dryRun` —
     * copia i file. Non scrive nulla prima del controllo spazio.
     *
     * @return array{map: array<string, string>, summary: array{copied: int, present: int, missing: int, missing_paths: list<string>, bytes: int, distinct: int}}
     */
    public function export(string $datapackDirectory, bool $dryRun): array
    {
        $root = rtrim($datapackDirectory, '/').'/'.self::DIRECTORY;
        $disk = Storage::disk(self::DISK);

        /** @var array<string, string> $map file_path => percorso relativo in snapshot-files */
        $map = [];
        /** @var array<string, array{relative: string, path: string, size: int}> $files hash => file da copiare */
        $files = [];
        $missing = [];

        $documents = DB::table('cai_documents')
            ->where('source', CaiDocumentSource::Runts->value)
            ->select(['file_path', 'file_name'])
            ->orderBy('id')
            ->cursor();

        foreach ($documents as $document) {
            $path = (string) $document->file_path;

            try {
                $stream = $disk->readStream($path);
                if ($stream === null) {
                    throw new RuntimeException('File non leggibile.');
                }

                $context = hash_init('sha256');
                hash_update_stream($context, $stream);
                fclose($stream);
                $hash = hash_final($context);
                $size = (int) $disk->size($path);
            } catch (Throwable) {
                $missing[] = $path;

                continue;
            }

            $relative = substr($hash, 0, 2).'/'.$hash.'.'.$this->extension((string) $document->file_name, $path);
            $map[$path] = self::DIRECTORY.'/'.$relative;
            $files[$hash] ??= ['relative' => $relative, 'path' => $path, 'size' => $size];
        }

        $toCopy = [];
        $present = 0;
        $required = 0;
        foreach ($files as $file) {
            $destination = $root.'/'.$file['relative'];
            if (is_file($destination) && filesize($destination) === $file['size']) {
                $present++;

                continue;
            }

            $toCopy[] = $file;
            $required += $file['size'];
        }

        $this->assertFreeSpace($datapackDirectory, $required);

        $copied = 0;
        foreach ($toCopy as $file) {
            if ($dryRun) {
                $copied++;

                continue;
            }

            try {
                $this->copy($disk->readStream($file['path']), $root.'/'.$file['relative']);
                $copied++;
            } catch (Throwable) {
                // Mai un'eccezione che ferma l'export: il documento resta senza file.
                $missing[] = $file['path'];
                foreach (array_keys($map, self::DIRECTORY.'/'.$file['relative'], true) as $path) {
                    unset($map[$path]);
                }
            }
        }

        return [
            'map' => $map,
            'summary' => [
                'copied' => $copied,
                'present' => $present,
                'missing' => count($missing),
                'missing_paths' => $missing,
                'bytes' => $required,
                'distinct' => count($files),
            ],
        ];
    }

    private function assertFreeSpace(string $directory, int $required): void
    {
        $free = $this->freeSpace !== null ? ($this->freeSpace)($directory) : disk_free_space($directory);

        if ($free !== false && $free < $required) {
            throw new RuntimeException(sprintf(
                'Spazio insufficiente per copiare i file dei documenti: servono %.1f MB, disponibili %.1f MB in %s. Nessun file è stato scritto.',
                $required / 1048576,
                $free / 1048576,
                $directory,
            ));
        }
    }

    /**
     * @param  resource|null  $source
     */
    private function copy($source, string $destination): void
    {
        if ($source === null) {
            throw new RuntimeException('File non leggibile.');
        }

        if (! is_dir(dirname($destination)) && ! mkdir(dirname($destination), 0775, true) && ! is_dir(dirname($destination))) {
            throw new RuntimeException('Cartella non creabile: '.dirname($destination));
        }

        $partial = $destination.'.part';
        $target = fopen($partial, 'wb');
        if ($target === false) {
            throw new RuntimeException('File non scrivibile: '.$partial);
        }

        try {
            stream_copy_to_stream($source, $target);
        } finally {
            fclose($source);
            fclose($target);
        }

        rename($partial, $destination);
    }

    private function extension(string $fileName, string $path): string
    {
        foreach ([$fileName, $path] as $candidate) {
            $extension = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($candidate, PATHINFO_EXTENSION)));
            if ($extension !== '') {
                return $extension;
            }
        }

        return 'bin';
    }
}
