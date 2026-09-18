<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Actions;

use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Enums\CaiDocumentType;
use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\Identity\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Unico punto di ingresso per caricare a mano un `CaiDocument` (gate:
 * `Permission::CaiDirectoryUploadDocument`, US aggiunta di seguito a US-804): si collega SEMPRE
 * direttamente alla `CaiSection`, mai a una `CaiRuntsRegistration` — le registrazioni RUNTS nascono solo
 * dalla sync live (`cai:sync-runts-all`/`cai:sync-runts-section`), mai da un percorso manuale. `$actor` non
 * è ancora usato per un log/evento dedicato (nessuno esiste oggi per `CaiDocument`), ma resta nella firma
 * per coerenza con la convenzione di dominio (ogni Action che scrive richiede l'attore esplicito) e per un
 * futuro log di audit senza rompere questa firma.
 */
final class UploadCaiDocumentManually
{
    private const DOCUMENTS_DISK = 'cai-documents';

    public static function run(
        User $actor,
        CaiSection $section,
        CaiDocumentType $documentType,
        ?int $year,
        ?string $title,
        UploadedFile $file,
    ): CaiDocument {
        $storedFileName = Str::uuid()->toString().'-'.$file->getClientOriginalName();
        $destinationPath = "{$section->codice_cai}/{$storedFileName}";

        Storage::disk(self::DOCUMENTS_DISK)->putFileAs($section->codice_cai, $file, $storedFileName);

        $document = CaiDocument::create([
            'cai_section_id' => $section->codice_cai,
            'document_type' => $documentType->value,
            'year' => $year,
            'title' => $title,
            'file_path' => $destinationPath,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'hash' => hash_file('sha256', $file->getRealPath()) ?: null,
            'source' => CaiDocumentSource::Manual,
        ]);

        if ($documentType->triggersFinancialAnalysis()) {
            AnalyzeCaiFinancialStatementDocument::dispatch($document->id)->onQueue('cai-runts-analysis');
        }

        return $document;
    }
}
