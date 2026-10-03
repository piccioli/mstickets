<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiSection;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Importa i documenti caricati "a mano" dalla tabella `bilanci_manuali` del datapack (generata da
 * `cai:build-manual-bilanci-datapack`) come `CaiDocument` con `source = Manual`, collegati direttamente
 * alla `CaiSection` (stesso schema di `UploadCaiDocumentManually`, ma senza analisi finanziaria accodata).
 * Richiamato da {@see CaiDatapackImporter::import()} dopo sezioni/registrazioni, sulla stessa connessione
 * read-only. Idempotente: un documento già presente con stessa sezione + hash viene saltato.
 */
final class CaiManualBilanciImporter
{
    private const TABLE = 'bilanci_manuali';

    private const DOCUMENTS_DISK = 'cai-documents';

    public function import(Connection $connection, string $datapackDir, bool $dryRun): CaiImportTableResult
    {
        if (! $connection->getSchemaBuilder()->hasTable(self::TABLE)) {
            return new CaiImportTableResult;
        }

        $rows = $connection->table(self::TABLE)->orderBy('id')->get();

        // In dry-run le sezioni non sono state scritte: il datapack è l'unica fonte di verità.
        $knownSectionCodes = $dryRun
            ? $connection->table('sezioni_cai')->pluck('codice_cai')->map(fn ($code): string => (string) $code)->flip()->all()
            : CaiSection::query()->pluck('codice_cai')->map(fn ($code): string => (string) $code)->flip()->all();

        $read = 0;
        $created = 0;
        $skipped = 0;
        $warnings = [];

        foreach ($rows as $row) {
            $read++;
            $codiceCai = (string) $row->codice_cai;

            if (! isset($knownSectionCodes[$codiceCai])) {
                $skipped++;

                continue;
            }

            $hash = (string) $row->hash_sha256;

            $alreadyImported = CaiDocument::query()
                ->where('source', CaiDocumentSource::Manual)
                ->where('cai_section_id', $codiceCai)
                ->where('hash', $hash)
                ->exists();

            if ($alreadyImported) {
                $skipped++;

                continue;
            }

            $sourcePath = $datapackDir.'/'.ltrim((string) $row->path, '/');

            if (! is_file($sourcePath)) {
                $skipped++;
                $warnings[] = "documenti_manuali: file mancante per la sezione {$codiceCai}: {$row->path}";

                continue;
            }

            if ($dryRun) {
                $created++;

                continue;
            }

            try {
                $fileName = basename((string) $row->filename);
                $destinationPath = "{$codiceCai}/".Str::uuid()->toString().'-'.$fileName;

                $this->copyFile($sourcePath, $destinationPath);

                CaiDocument::create([
                    'cai_section_id' => $codiceCai,
                    'cai_runts_registration_id' => null,
                    'document_type' => $row->tipo,
                    'year' => CaiSectionFieldMapper::toInt($row->anno),
                    'title' => $row->titolo,
                    'file_path' => $destinationPath,
                    'file_name' => $fileName,
                    'mime_type' => $row->mime,
                    'size' => CaiSectionFieldMapper::toInt($row->size),
                    'hash' => $hash,
                    'source' => CaiDocumentSource::Manual,
                ]);
                $created++;
            } catch (Throwable $e) {
                $skipped++;
                $warnings[] = "documenti_manuali: errore sul file {$row->path}: {$e->getMessage()}";
            }
        }

        return new CaiImportTableResult(read: $read, created: $created, skipped: $skipped, warnings: $warnings);
    }

    private function copyFile(string $sourceAbsolutePath, string $destinationRelativePath): void
    {
        $stream = fopen($sourceAbsolutePath, 'rb');

        if ($stream === false) {
            throw new \RuntimeException("Impossibile aprire {$sourceAbsolutePath}");
        }

        try {
            Storage::disk(self::DOCUMENTS_DISK)->put($destinationRelativePath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
