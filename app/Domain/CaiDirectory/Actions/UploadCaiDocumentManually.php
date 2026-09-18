<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Actions;

use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\Identity\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Unico punto di ingresso per caricare a mano un `CaiDocument` (gate:
 * `Permission::CaiDirectoryUploadDocument`, US aggiunta di seguito a US-804): se la sezione ha almeno una
 * `CaiRuntsRegistration`, il documento può collegarsi a una di quelle (stesso schema del sync live) OPPURE
 * direttamente alla sezione — una sezione senza ALCUNA registrazione RUNTS ha solo questa seconda opzione.
 * `$actor` non è ancora usato per un log/evento dedicato (nessuno esiste oggi per `CaiDocument`), ma resta
 * nella firma per coerenza con la convenzione di dominio (ogni Action che scrive richiede l'attore
 * esplicito) e per un futuro log di audit senza rompere questa firma.
 */
final class UploadCaiDocumentManually
{
    private const DOCUMENTS_DISK = 'cai-documents';

    public static function run(
        User $actor,
        CaiSection $section,
        ?CaiRuntsRegistration $registration,
        string $documentType,
        ?int $year,
        ?string $title,
        UploadedFile $file,
    ): CaiDocument {
        self::guardRegistrationBelongsToSection($section, $registration);

        $parentKey = $registration === null ? $section->codice_cai : $registration->id_runts;
        $storedFileName = Str::uuid()->toString().'-'.$file->getClientOriginalName();
        $destinationPath = "{$parentKey}/{$storedFileName}";

        Storage::disk(self::DOCUMENTS_DISK)->putFileAs($parentKey, $file, $storedFileName);

        $document = CaiDocument::create([
            'cai_runts_registration_id' => $registration?->id_runts,
            'cai_section_id' => $registration === null ? $section->codice_cai : null,
            'document_type' => $documentType,
            'year' => $year,
            'title' => $title,
            'file_path' => $destinationPath,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'hash' => hash_file('sha256', $file->getRealPath()) ?: null,
            'source' => CaiDocumentSource::Manual,
        ]);

        if (in_array($documentType, CaiDocument::BILANCIO_DOCUMENT_TYPES, true)) {
            AnalyzeCaiFinancialStatementDocument::dispatch($document->id)->onQueue('cai-runts-analysis');
        }

        return $document;
    }

    private static function guardRegistrationBelongsToSection(CaiSection $section, ?CaiRuntsRegistration $registration): void
    {
        if ($registration !== null && $registration->cai_section_id !== $section->codice_cai) {
            throw ValidationException::withMessages([
                'registration_id' => ['La registrazione RUNTS selezionata non appartiene a questa sezione.'],
            ]);
        }
    }
}
