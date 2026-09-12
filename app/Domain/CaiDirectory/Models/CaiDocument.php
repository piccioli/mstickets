<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use App\Domain\CaiDirectory\Enums\CaiDocumentAnalysisStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cai_runts_registration_id', 'document_type', 'year', 'title', 'file_path', 'file_name',
    'mime_type', 'size', 'hash', 'financial_analysis_status', 'raw_text_excerpt', 'extracted_via_ocr',
])]
class CaiDocument extends Model
{
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
        ];
    }

    /**
     * @return BelongsTo<CaiRuntsRegistration, $this>
     */
    public function runtsRegistration(): BelongsTo
    {
        return $this->belongsTo(CaiRuntsRegistration::class, 'cai_runts_registration_id', 'id_runts');
    }
}
