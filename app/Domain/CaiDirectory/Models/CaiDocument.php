<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use App\Domain\CaiDirectory\Enums\CaiDocumentAnalysisStatus;
use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Enums\CaiDocumentType;
use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cai_runts_registration_id', 'cai_section_id', 'document_type', 'year', 'title', 'file_path',
    'file_name', 'mime_type', 'size', 'hash', 'financial_analysis_status', 'raw_text_excerpt',
    'extracted_via_ocr', 'source',
])]
class CaiDocument extends Model
{
    /**
     * Tipi documento RUNTS (vocabolario libero della fonte, mai il vocabolario
     * {@see CaiDocumentType} dell'upload manuale) che alimentano
     * l'estrazione automatica delle cifre finanziarie
     * ({@see AnalyzeCaiFinancialStatementDocument}) — usato SOLO da `SyncCaiRuntsRegistration`.
     * `UploadCaiDocumentManually` decide con un criterio indipendente
     * (`CaiDocumentType::triggersFinancialAnalysis()`), perché i due percorsi non condividono lo stesso
     * vocabolario di tipo documento.
     *
     * @var list<string>
     */
    public const BILANCIO_DOCUMENT_TYPES = ['bilancio_esercizio'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'size' => 'integer',
            'financial_analysis_status' => CaiDocumentAnalysisStatus::class,
            'extracted_via_ocr' => 'boolean',
            'source' => CaiDocumentSource::class,
        ];
    }

    /**
     * @return BelongsTo<CaiRuntsRegistration, $this>
     */
    public function runtsRegistration(): BelongsTo
    {
        return $this->belongsTo(CaiRuntsRegistration::class, 'cai_runts_registration_id', 'id_runts');
    }

    /**
     * Popolato SOLO quando il documento non è collegato ad alcuna `CaiRuntsRegistration` (upload
     * manuale/Veryfico per una sezione senza presenza RUNTS) — i due genitori sono mutuamente
     * esclusivi, mai entrambi popolati sulla stessa riga (invariante imposta dalle Action che scrivono
     * questo modello, non da un vincolo a livello DB — vedi la migrazione che ha introdotto la colonna).
     *
     * @return BelongsTo<CaiSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CaiSection::class, 'cai_section_id', 'codice_cai');
    }
}
