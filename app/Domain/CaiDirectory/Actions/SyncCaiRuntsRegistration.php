<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Actions;

use App\Domain\CaiDirectory\Import\CaiRuntsRegistrationFieldMapper;
use App\Domain\CaiDirectory\Import\Concerns\DiffsAttributes;
use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use App\Domain\CaiDirectory\Models\CaiBoardMember;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Support\CaiRuntsScraperClient;
use App\Domain\CaiDirectory\Support\SyncCaiRuntsRegistrationResult;
use App\Filament\Pages\CustomerDashboard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Sincronizza dal vivo la registrazione RUNTS (+ cariche sociali + documenti, Task 7) di UNA `CaiSection`
 * (Fase 9, storia 3, design doc §4.3): entry point del bottone "Sincronizza dati RUNTS" della dashboard
 * cliente ({@see CustomerDashboard::syncRuntsDataAction()}, wiring in Task 8).
 */
final class SyncCaiRuntsRegistration
{
    use DiffsAttributes;

    private const DOCUMENTS_DISK = 'cai-documents';

    private const BILANCIO_DOCUMENT_TYPES = ['bilancio_esercizio'];

    public function __construct(private readonly CaiRuntsScraperClient $client) {}

    public function run(CaiSection $section): SyncCaiRuntsRegistrationResult
    {
        if ($section->tax_code === null || trim($section->tax_code) === '') {
            return SyncCaiRuntsRegistrationResult::notFound();
        }

        $response = $this->client->scrapeEntity($section->tax_code);

        if (($response['found'] ?? false) !== true) {
            return SyncCaiRuntsRegistrationResult::notFound();
        }

        $registration = $this->syncRegistration($section->codice_cai, (object) $response['entity']);
        $this->syncBoardMembers($registration->id_runts, $response['board_members'] ?? []);
        $newBilancioDocuments = $this->syncDocuments($registration->id_runts, $response['documents'] ?? []);

        foreach ($newBilancioDocuments as $document) {
            AnalyzeCaiFinancialStatementDocument::dispatch($document->id)->onQueue('cai-runts-analysis');
        }

        return SyncCaiRuntsRegistrationResult::synced($registration, queuedAnalysisCount: count($newBilancioDocuments));
    }

    private function syncRegistration(string $sectionCode, object $row): CaiRuntsRegistration
    {
        $attributes = CaiRuntsRegistrationFieldMapper::mapRegistration($row, $sectionCode);
        $idRunts = (string) $row->id_runts;
        $registration = CaiRuntsRegistration::find($idRunts);

        if ($registration === null) {
            $registration = CaiRuntsRegistration::create(['id_runts' => $idRunts, ...$attributes]);
        } else {
            $registration->fill($attributes);
        }

        $registration->runts_last_synced_at = Carbon::now();
        $registration->save();

        return $registration;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncBoardMembers(string $idRunts, array $rows): void
    {
        foreach ($rows as $raw) {
            $attributes = CaiRuntsRegistrationFieldMapper::mapBoardMember((object) $raw, $idRunts);

            $existing = CaiBoardMember::query()
                ->where('cai_runts_registration_id', $attributes['cai_runts_registration_id'])
                ->where('role', $attributes['role'])
                ->when(
                    $attributes['tax_code'] === null,
                    fn ($query) => $query->whereNull('tax_code'),
                    fn ($query) => $query->where('tax_code', $attributes['tax_code']),
                )
                ->when(
                    $attributes['valid_from'] === null,
                    fn ($query) => $query->whereNull('valid_from'),
                    fn ($query) => $query->where('valid_from', $attributes['valid_from']),
                )
                ->first();

            if ($existing === null) {
                CaiBoardMember::create($attributes);
            } elseif ($this->attributesDiffer($existing, $attributes)) {
                $existing->update($attributes);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<CaiDocument> i documenti di bilancio NUOVI (mai quelli già presenti né gli altri tipi)
     */
    private function syncDocuments(string $idRunts, array $rows): array
    {
        $newBilancioDocuments = [];

        foreach ($rows as $raw) {
            if (($raw['skip_reason'] ?? null) !== null) {
                continue;
            }

            $contentBase64 = $raw['content_base64'] ?? null;
            $fileName = $raw['filename'] ?? null;

            if ($contentBase64 === null || $fileName === null) {
                continue;
            }

            $existing = CaiDocument::query()
                ->where('cai_runts_registration_id', $idRunts)
                ->where('file_name', $fileName)
                ->first();

            if ($existing !== null) {
                continue;
            }

            $decoded = base64_decode((string) $contentBase64, true);

            if ($decoded === false) {
                continue;
            }

            $destinationPath = "{$idRunts}/{$fileName}";
            Storage::disk(self::DOCUMENTS_DISK)->put($destinationPath, $decoded);

            $document = CaiDocument::create([
                'cai_runts_registration_id' => $idRunts,
                'document_type' => $raw['tipo'] ?? null,
                'year' => $raw['anno'] ?? null,
                'title' => $raw['documento'] ?? null,
                'file_path' => $destinationPath,
                'file_name' => $fileName,
                'mime_type' => $raw['mime'] ?? null,
                'size' => $raw['size'] ?? null,
                'hash' => $raw['hash_sha256'] ?? null,
            ]);

            if (in_array($raw['tipo'] ?? null, self::BILANCIO_DOCUMENT_TYPES, true)) {
                $newBilancioDocuments[] = $document;
            }
        }

        return $newBilancioDocuments;
    }
}
