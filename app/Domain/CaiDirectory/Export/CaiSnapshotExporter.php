<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Export;

use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

/**
 * Fotografa nel datapack RUNTS-CAI (SQLite) tutte le righe del dominio CAI del DB corrente, in tabelle
 * `snap_*` (DROP + CREATE + INSERT in una sola transazione). Legge con il query builder (mai Eloquent):
 * i valori sono scritti nella forma grezza di colonna, non quella castata. `user_id` non entra mai nello
 * snapshot (il collegamento utente si ricostruisce per email in import). Le tabelle originali del datapack
 * (`sezioni_cai`, `enti`, `bilanci`, ...) non vengono toccate.
 */
final class CaiSnapshotExporter
{
    private const TEXT = 'TEXT';

    private const INT = 'INTEGER';

    /**
     * Tabella snapshot => [tabella sorgente, colonna d'ordinamento, colonne => tipo SQLite].
     * Il tipo TEXT conserva anche decimali e date come stringhe grezze (nessuna perdita di precisione).
     *
     * @return array<string, array{source: string, order: string, columns: array<string, string>}>
     */
    public static function definitions(): array
    {
        $timestamps = ['created_at' => self::TEXT, 'updated_at' => self::TEXT];

        return [
            'snap_cai_sections' => [
                'source' => 'cai_sections',
                'order' => 'codice_cai',
                'columns' => [
                    'codice_cai' => self::TEXT, 'name' => self::TEXT, 'tax_code' => self::TEXT, 'vat_number' => self::TEXT,
                    'email' => self::TEXT, 'pec' => self::TEXT, 'phone_office' => self::TEXT, 'phone' => self::TEXT,
                    'fax' => self::TEXT, 'address' => self::TEXT, 'postal_address' => self::TEXT, 'website' => self::TEXT,
                    'office_hours' => self::TEXT, 'notices' => self::TEXT, 'founded_year' => self::INT,
                    'members_count' => self::INT, 'latitude' => self::TEXT, 'longitude' => self::TEXT, 'region' => self::TEXT,
                    'cai_last_synced_at' => self::TEXT, 'runts_presence_checked_at' => self::TEXT,
                    'runts_presence_status' => self::TEXT,
                ] + $timestamps,
            ],
            'snap_cai_subsections' => [
                'source' => 'cai_subsections',
                'order' => 'cai_codice',
                'columns' => [
                    'cai_codice' => self::TEXT, 'cai_section_id' => self::TEXT, 'name' => self::TEXT, 'email' => self::TEXT,
                    'phone_office' => self::TEXT, 'phone' => self::TEXT, 'address' => self::TEXT, 'website' => self::TEXT,
                    'office_hours' => self::TEXT, 'notices' => self::TEXT, 'founded_year' => self::INT,
                    'members_count' => self::INT, 'latitude' => self::TEXT, 'longitude' => self::TEXT,
                    'cai_last_synced_at' => self::TEXT,
                ] + $timestamps,
            ],
            'snap_cai_runts_registrations' => [
                'source' => 'cai_runts_registrations',
                'order' => 'id_runts',
                'columns' => [
                    'id_runts' => self::TEXT, 'cai_section_id' => self::TEXT, 'tax_code' => self::TEXT, 'name' => self::TEXT,
                    'legal_form' => self::TEXT, 'legal_nature' => self::TEXT, 'address' => self::TEXT,
                    'street_number' => self::TEXT, 'municipality' => self::TEXT, 'province' => self::TEXT,
                    'region' => self::TEXT, 'postal_code' => self::TEXT, 'latitude' => self::TEXT, 'longitude' => self::TEXT,
                    'registration_date' => self::TEXT, 'register_section' => self::TEXT, 'activity_sectors' => self::TEXT,
                    'legal_representative' => self::TEXT, 'website' => self::TEXT, 'pec' => self::TEXT,
                    'official_page_url' => self::TEXT, 'runts_last_synced_at' => self::TEXT,
                ] + $timestamps,
            ],
            'snap_cai_financial_statements' => [
                'source' => 'cai_financial_statements',
                'order' => 'id',
                'columns' => [
                    'cai_runts_registration_id' => self::TEXT, 'cai_section_id' => self::TEXT, 'year' => self::INT,
                    'general_interest_expenses' => self::TEXT, 'other_activities_expenses' => self::TEXT,
                    'fundraising_expenses' => self::TEXT, 'financial_expenses' => self::TEXT,
                    'overhead_expenses' => self::TEXT, 'total_expenses' => self::TEXT,
                    'general_interest_revenues' => self::TEXT, 'other_activities_revenues' => self::TEXT,
                    'fundraising_revenues' => self::TEXT, 'financial_revenues' => self::TEXT,
                    'overhead_revenues' => self::TEXT, 'total_revenues' => self::TEXT, 'pre_tax_result' => self::TEXT,
                    'taxes' => self::TEXT, 'net_result' => self::TEXT, 'total_assets' => self::TEXT,
                    'total_liabilities' => self::TEXT, 'net_equity' => self::TEXT,
                ] + $timestamps,
            ],
            'snap_cai_board_members' => [
                'source' => 'cai_board_members',
                'order' => 'id',
                'columns' => [
                    'cai_runts_registration_id' => self::TEXT, 'role' => self::TEXT, 'full_name' => self::TEXT,
                    'tax_code' => self::TEXT, 'valid_from' => self::TEXT, 'valid_to' => self::TEXT,
                ] + $timestamps,
            ],
            'snap_cai_documents' => [
                'source' => 'cai_documents',
                'order' => 'id',
                'columns' => [
                    'cai_runts_registration_id' => self::TEXT, 'cai_section_id' => self::TEXT, 'document_type' => self::TEXT,
                    'year' => self::INT, 'title' => self::TEXT, 'file_path' => self::TEXT, 'file_name' => self::TEXT,
                    'mime_type' => self::TEXT, 'size' => self::INT, 'hash' => self::TEXT,
                    'financial_analysis_status' => self::TEXT, 'raw_text_excerpt' => self::TEXT,
                    'extracted_via_ocr' => self::INT, 'source' => self::TEXT,
                    'snapshot_file' => self::TEXT, 'file_in_datapack' => self::INT,
                ] + $timestamps,
            ],
        ];
    }

    public function __construct(private readonly CaiSnapshotFileExporter $files = new CaiSnapshotFileExporter) {}

    /**
     * @return array{tables: array<string, array{exported: int, database: int}>, files: array{copied: int, present: int, missing: int, missing_paths: list<string>, bytes: int, distinct: int}}
     */
    public function export(string $datapackPath, bool $dryRun = false): array
    {
        // Prima i file (controllo spazio incluso): se manca spazio, nulla viene scritto nel datapack.
        $files = $this->files->export(dirname($datapackPath), $dryRun);

        $pdo = new PDO('sqlite:'.$datapackPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $summary = [];
        $pdo->beginTransaction();

        try {
            foreach (self::definitions() as $snapTable => $definition) {
                $summary[$snapTable] = [
                    'exported' => $this->exportTable($pdo, $snapTable, $definition, $files['map']),
                    'database' => (int) DB::table($definition['source'])->count(),
                ];
            }

            // Dry-run: lo stesso percorso di scrittura (per conteggi identici) ma annullato.
            $dryRun ? $pdo->rollBack() : $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }

        return ['tables' => $summary, 'files' => $files['summary']];
    }

    /**
     * @param  array{source: string, order: string, columns: array<string, string>}  $definition
     * @param  array<string, string>  $fileMap  file_path => percorso relativo del file copiato
     */
    private function exportTable(PDO $pdo, string $snapTable, array $definition, array $fileMap): int
    {
        $columns = array_keys($definition['columns']);
        $columnDdl = [];
        foreach ($definition['columns'] as $name => $type) {
            $columnDdl[] = "{$name} {$type}";
        }

        $pdo->exec("DROP TABLE IF EXISTS {$snapTable}");
        $pdo->exec("CREATE TABLE {$snapTable} (".implode(', ', $columnDdl).')');

        $insert = $pdo->prepare(
            "INSERT INTO {$snapTable} (".implode(', ', $columns).') VALUES ('.implode(', ', array_fill(0, count($columns), '?')).')',
        );

        // Colonne snapshot assenti dalla sorgente (snapshot_file, file_in_datapack) hanno un valore fisso.
        $sourceColumns = array_values(array_filter($columns, static fn (string $c): bool => ! in_array($c, ['snapshot_file', 'file_in_datapack'], true)));
        $isDocuments = $snapTable === 'snap_cai_documents';

        $count = 0;
        foreach (DB::table($definition['source'])->select($sourceColumns)->orderBy($definition['order'])->cursor() as $row) {
            $values = [];
            foreach ($columns as $column) {
                $values[] = match ($column) {
                    'snapshot_file' => $isDocuments ? ($fileMap[$row->file_path] ?? null) : null,
                    'file_in_datapack' => $isDocuments && isset($fileMap[$row->file_path]) ? 1 : 0,
                    default => $this->raw($row->{$column}),
                };
            }

            $insert->execute($values);
            $count++;
        }

        return $count;
    }

    private function raw(mixed $value): string|int|float|null
    {
        return match (true) {
            is_bool($value) => (int) $value,
            is_int($value), is_float($value), is_string($value), $value === null => $value,
            default => (string) $value,
        };
    }
}
