<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use App\Domain\CaiDirectory\Enums\CaiDocumentAnalysisStatus;
use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Enums\CaiRuntsPresenceStatus;
use App\Domain\CaiDirectory\Export\CaiSnapshotExporter;
use App\Domain\CaiDirectory\Import\Concerns\DiffsAttributes;
use App\Domain\CaiDirectory\Models\CaiBoardMember;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Ripristina dal datapack le tabelle `snap_*` non-documento prodotte da {@see CaiSnapshotExporter}
 * (sezioni, sottosezioni, registrazioni RUNTS, bilanci, cariche). Upsert per chiave naturale; `user_id`
 * non è mai nello snapshot e non viene mai sovrascritto su righe esistenti (sulle righe nuove si ricostruisce
 * per email, con la stessa logica dell'import legacy). Righe con genitore assente → saltate (mai FK violata).
 * `--dry-run`: stessi conteggi, nessuna scrittura (un genitore che verrebbe creato conta come presente).
 * Nessuna chiamata di rete e nessun job accodato.
 */
final class CaiSnapshotImporter
{
    use DiffsAttributes;

    private const SKIP_COLUMNS = ['created_at', 'updated_at'];

    /**
     * @param  array<string, int>  $usersByLowerEmail
     * @return array<string, CaiImportTableResult> vuoto se il datapack non ha lo snapshot
     */
    public function import(Connection $datapack, bool $dryRun, array $usersByLowerEmail, string $datapackDir): array
    {
        if (! $datapack->getSchemaBuilder()->hasTable('snap_cai_sections')) {
            return [];
        }

        $sectionCodes = array_fill_keys(CaiSection::query()->pluck('codice_cai')->map(fn ($v): string => (string) $v)->all(), true);
        $registrationIds = array_fill_keys(CaiRuntsRegistration::query()->pluck('id_runts')->map(fn ($v): string => (string) $v)->all(), true);

        $sections = $this->importSections($datapack, $dryRun, $usersByLowerEmail, $sectionCodes);
        $registrations = $this->importRegistrations($datapack, $dryRun, $sectionCodes, $registrationIds);

        return [
            'snapshot_sezioni' => $sections,
            'snapshot_registrazioni' => $registrations,
            'snapshot_bilanci' => $this->importFinancialStatements($datapack, $dryRun, $sectionCodes, $registrationIds),
            'snapshot_cariche' => $this->importBoardMembers($datapack, $dryRun, $registrationIds),
            'snapshot_documenti' => $this->importDocuments($datapack, $dryRun, $datapackDir, $sectionCodes, $registrationIds),
        ];
    }

    /**
     * Sezioni e sottosezioni sono riportate in un'unica riga di riepilogo.
     *
     * @param  array<string, int>  $usersByLowerEmail
     * @param  array<string, true>  $sectionCodes
     */
    private function importSections(Connection $datapack, bool $dryRun, array $usersByLowerEmail, array &$sectionCodes): CaiImportTableResult
    {
        $counts = new SnapshotCounts;

        foreach ($datapack->table('snap_cai_sections')->orderBy('codice_cai')->cursor() as $row) {
            $attributes = $this->attributes($row, 'snap_cai_sections', ['codice_cai']);
            $attributes['runts_presence_status'] = $this->presenceStatus($attributes['runts_presence_status'] ?? null);

            $this->upsert(
                $counts,
                $dryRun,
                CaiSection::query()->find((string) $row->codice_cai),
                $attributes,
                fn (array $attrs) => CaiSection::create([
                    'codice_cai' => $row->codice_cai,
                    ...$attrs,
                    'user_id' => CaiSectionFieldMapper::matchUserId($attrs['email'] ?? null, $usersByLowerEmail),
                ]),
            );

            $sectionCodes[(string) $row->codice_cai] = true;
        }

        foreach ($datapack->table('snap_cai_subsections')->orderBy('cai_codice')->cursor() as $row) {
            if (! isset($sectionCodes[(string) $row->cai_section_id])) {
                $counts->orphans++;
                $counts->skipped++;
                $counts->read++;

                continue;
            }

            $attributes = $this->attributes($row, 'snap_cai_subsections', ['cai_codice']);

            $this->upsert(
                $counts,
                $dryRun,
                CaiSubsection::query()->find((string) $row->cai_codice),
                $attributes,
                fn (array $attrs) => CaiSubsection::create([
                    'cai_codice' => $row->cai_codice,
                    ...$attrs,
                    'user_id' => CaiSectionFieldMapper::matchUserId($attrs['email'] ?? null, $usersByLowerEmail),
                ]),
            );
        }

        return $counts->toResult('sezioni/sottosezioni');
    }

    /**
     * @param  array<string, true>  $sectionCodes
     * @param  array<string, true>  $registrationIds
     */
    private function importRegistrations(Connection $datapack, bool $dryRun, array $sectionCodes, array &$registrationIds): CaiImportTableResult
    {
        $counts = new SnapshotCounts;

        foreach ($datapack->table('snap_cai_runts_registrations')->orderBy('id_runts')->cursor() as $row) {
            if (! isset($sectionCodes[(string) $row->cai_section_id])) {
                $counts->orphans++;
                $counts->skipped++;
                $counts->read++;

                continue;
            }

            $this->upsert(
                $counts,
                $dryRun,
                CaiRuntsRegistration::query()->find((string) $row->id_runts),
                $this->attributes($row, 'snap_cai_runts_registrations', ['id_runts']),
                fn (array $attrs) => CaiRuntsRegistration::create(['id_runts' => $row->id_runts, ...$attrs]),
            );

            $registrationIds[(string) $row->id_runts] = true;
        }

        return $counts->toResult('registrazioni');
    }

    /**
     * @param  array<string, true>  $sectionCodes
     * @param  array<string, true>  $registrationIds
     */
    private function importFinancialStatements(Connection $datapack, bool $dryRun, array $sectionCodes, array $registrationIds): CaiImportTableResult
    {
        $counts = new SnapshotCounts;

        foreach ($datapack->table('snap_cai_financial_statements')->orderBy('rowid')->cursor() as $row) {
            $registration = $this->nullable($row->cai_runts_registration_id);
            $section = $this->nullable($row->cai_section_id);

            // Invariante: esattamente uno dei due genitori, e deve esistere.
            $parentColumn = match (true) {
                $registration !== null && $section === null && isset($registrationIds[(string) $registration]) => 'cai_runts_registration_id',
                $section !== null && $registration === null && isset($sectionCodes[(string) $section]) => 'cai_section_id',
                default => null,
            };

            if ($parentColumn === null) {
                $counts->orphans++;
                $counts->skipped++;
                $counts->read++;

                continue;
            }

            $attributes = $this->attributes($row, 'snap_cai_financial_statements', []);

            $this->upsert(
                $counts,
                $dryRun,
                CaiFinancialStatement::query()
                    ->where($parentColumn, $attributes[$parentColumn])
                    ->where('year', $attributes['year'])
                    ->first(),
                $attributes,
                fn (array $attrs) => CaiFinancialStatement::create($attrs),
            );
        }

        return $counts->toResult('bilanci');
    }

    /**
     * @param  array<string, true>  $registrationIds
     */
    private function importBoardMembers(Connection $datapack, bool $dryRun, array $registrationIds): CaiImportTableResult
    {
        $counts = new SnapshotCounts;

        foreach ($datapack->table('snap_cai_board_members')->orderBy('rowid')->cursor() as $row) {
            if (! isset($registrationIds[(string) $row->cai_runts_registration_id])) {
                $counts->orphans++;
                $counts->skipped++;
                $counts->read++;

                continue;
            }

            $attributes = $this->attributes($row, 'snap_cai_board_members', []);
            $validFrom = $attributes['valid_from'] ?? null;

            $existing = CaiBoardMember::query()
                ->where('cai_runts_registration_id', $attributes['cai_runts_registration_id'])
                ->where('role', $attributes['role'])
                ->where('full_name', $attributes['full_name'])
                ->when(
                    $validFrom === null,
                    fn ($query) => $query->whereNull('valid_from'),
                    fn ($query) => $query->whereDate('valid_from', substr((string) $validFrom, 0, 10)),
                )
                ->first();

            $this->upsert($counts, $dryRun, $existing, $attributes, fn (array $attrs) => CaiBoardMember::create($attrs));
        }

        return $counts->toResult('cariche');
    }

    /**
     * Documenti RUNTS con file nel datapack. Dedup per (genitore, hash, source): un documento già presente
     * (anche dall'import legacy `allegati`) non si duplica, se ne allinea solo l'esito di analisi. Le righe
     * nuove si pianificano senza scrivere, si controlla lo spazio e solo allora si copiano i file.
     *
     * @param  array<string, true>  $sectionCodes
     * @param  array<string, true>  $registrationIds
     */
    private function importDocuments(Connection $datapack, bool $dryRun, string $datapackDir, array $sectionCodes, array $registrationIds): CaiImportTableResult
    {
        $counts = new SnapshotCounts;
        $warnings = [];
        /** @var list<array{attributes: array<string, mixed>, source: string, destination: string}> $plan */
        $plan = [];
        $planned = [];
        $bytes = 0;

        $rows = $datapack->table('snap_cai_documents')
            ->where('source', CaiDocumentSource::Runts->value)
            ->where('file_in_datapack', 1)
            ->orderBy('rowid')
            ->cursor();

        foreach ($rows as $row) {
            $counts->read++;

            $registration = $this->nullable($row->cai_runts_registration_id);
            $section = $this->nullable($row->cai_section_id);

            $parent = match (true) {
                $registration !== null && $section === null && isset($registrationIds[(string) $registration]) => ['cai_runts_registration_id', (string) $registration],
                $section !== null && $registration === null && isset($sectionCodes[(string) $section]) => ['cai_section_id', (string) $section],
                default => null,
            };

            if ($parent === null) {
                $counts->orphans++;
                $counts->skipped++;

                continue;
            }

            $analysis = [
                'financial_analysis_status' => CaiDocumentAnalysisStatus::tryFrom((string) $this->nullable($row->financial_analysis_status)),
                'raw_text_excerpt' => $this->nullable($row->raw_text_excerpt),
                'extracted_via_ocr' => (bool) $row->extracted_via_ocr,
            ];

            $hash = $this->nullable($row->hash);
            $fileName = basename((string) $row->file_name);

            $existing = CaiDocument::query()
                ->where($parent[0], $parent[1])
                ->where('source', CaiDocumentSource::Runts)
                ->when(
                    $hash !== null,
                    fn ($query) => $query->where('hash', $hash),
                    fn ($query) => $query->whereNull('hash')->where('file_name', $fileName),
                )
                ->first();

            if ($existing !== null) {
                if ($this->attributesDiffer($existing, $analysis)) {
                    if (! $dryRun) {
                        $existing->update($analysis);
                    }
                    $counts->updated++;
                } else {
                    $counts->skipped++;
                }

                continue;
            }

            $key = $parent[0].'|'.$parent[1].'|'.($hash ?? $fileName);
            if (isset($planned[$key])) {
                $counts->skipped++;

                continue;
            }

            $source = $datapackDir.'/'.ltrim((string) $row->snapshot_file, '/');
            $size = is_file($source) ? filesize($source) : false;

            if ($size === false) {
                $counts->skipped++;
                $warnings[] = "File mancante nel datapack: {$row->snapshot_file}";

                continue;
            }

            $planned[$key] = true;
            $bytes += $size;
            $plan[] = [
                'attributes' => [
                    $parent[0] => $parent[1],
                    'document_type' => $this->nullable($row->document_type),
                    'year' => CaiSectionFieldMapper::toInt($this->nullable($row->year)),
                    'title' => $this->nullable($row->title),
                    'file_name' => $fileName,
                    'mime_type' => $this->nullable($row->mime_type),
                    'size' => CaiSectionFieldMapper::toInt($this->nullable($row->size)),
                    'hash' => $hash,
                    'source' => CaiDocumentSource::Runts,
                    ...$analysis,
                ],
                'source' => $source,
                'destination' => $parent[1].'/'.Str::uuid().'-'.$fileName,
            ];
        }

        if ($dryRun) {
            $counts->created = count($plan);

            return $this->documentsResult($counts, $warnings, $bytes);
        }

        $error = $this->spaceError($bytes);
        if ($error !== null) {
            $counts->skipped += count($plan);

            return $this->documentsResult($counts, $warnings, 0, $error);
        }

        $copied = 0;
        foreach ($plan as $item) {
            try {
                $this->copyFile($item['source'], $item['destination']);
                CaiDocument::create([...$item['attributes'], 'file_path' => $item['destination']]);
                $counts->created++;
                $copied += (int) filesize($item['source']);
            } catch (Throwable $e) {
                $counts->skipped++;
                $warnings[] = "Documento non importato ({$item['destination']}): {$e->getMessage()}";
            }
        }

        return $this->documentsResult($counts, $warnings, $copied);
    }

    /**
     * @param  list<string>  $warnings
     */
    private function documentsResult(SnapshotCounts $counts, array $warnings, int $bytes, ?string $error = null): CaiImportTableResult
    {
        $result = $counts->toResult('documenti');

        return new CaiImportTableResult(
            read: $result->read,
            created: $result->created,
            updated: $result->updated,
            skipped: $result->skipped,
            warnings: [...$result->warnings, ...$warnings],
            bytes: $bytes,
            error: $error,
        );
    }

    /**
     * Messaggio d'errore se lo spazio libero dello storage non copre i byte da copiare più il margine.
     */
    private function spaceError(int $bytes): ?string
    {
        if ($bytes === 0) {
            return null;
        }

        $root = Storage::disk('cai-documents')->path('');
        while (! is_dir($root) && dirname($root) !== $root) {
            $root = dirname($root);
        }

        $free = @disk_free_space($root);
        $margin = (float) config('cai_directory.snapshot.disk_margin_percent', 10);
        $required = $bytes * (1 + $margin / 100);

        if ($free === false || $free >= $required) {
            return null;
        }

        return sprintf(
            'Spazio insufficiente per importare i documenti: servono %s MB (compreso il margine del %s%%), disponibili %s MB. Nessun file è stato copiato.',
            number_format($required / 1048576, 1, ',', '.'),
            rtrim(rtrim(number_format($margin, 1, ',', ''), '0'), ','),
            number_format($free / 1048576, 1, ',', '.'),
        );
    }

    private function copyFile(string $source, string $destination): void
    {
        $stream = fopen($source, 'rb');

        if ($stream === false) {
            throw new RuntimeException('File sorgente non leggibile.');
        }

        try {
            if (! Storage::disk('cai-documents')->put($destination, $stream)) {
                throw new RuntimeException('Scrittura su disco non riuscita.');
            }
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  callable(array<string, mixed>): mixed  $create
     */
    private function upsert(SnapshotCounts $counts, bool $dryRun, ?Model $existing, array $attributes, callable $create): void
    {
        $counts->read++;

        if ($existing === null) {
            if (! $dryRun) {
                $create($attributes);
            }
            $counts->created++;

            return;
        }

        if ($this->attributesDiffer($existing, $attributes)) {
            if (! $dryRun) {
                $existing->update($attributes);
            }
            $counts->updated++;

            return;
        }

        $counts->skipped++;
    }

    /**
     * Valori grezzi della riga snapshot, senza chiave naturale/timestamp; stringhe vuote → null, interi e
     * coordinate normalizzati come nell'import legacy.
     *
     * @param  list<string>  $exclude
     * @return array<string, mixed>
     */
    private function attributes(object $row, string $table, array $exclude): array
    {
        $attributes = [];

        foreach (array_keys(CaiSnapshotExporter::definitions()[$table]['columns']) as $column) {
            if (in_array($column, self::SKIP_COLUMNS, true) || in_array($column, $exclude, true)) {
                continue;
            }

            $value = $this->nullable($row->{$column} ?? null);

            $attributes[$column] = match (true) {
                $value === null => null,
                in_array($column, ['founded_year', 'members_count', 'year'], true) => CaiSectionFieldMapper::toInt($value),
                in_array($column, ['latitude', 'longitude'], true) => CaiSectionFieldMapper::toCoordinate($value),
                default => $value,
            };
        }

        return $attributes;
    }

    private function nullable(mixed $value): mixed
    {
        return $value === '' ? null : $value;
    }

    private function presenceStatus(mixed $value): ?CaiRuntsPresenceStatus
    {
        return is_string($value) ? CaiRuntsPresenceStatus::tryFrom($value) : null;
    }
}
