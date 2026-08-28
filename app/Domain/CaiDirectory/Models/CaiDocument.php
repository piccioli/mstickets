<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cai_runts_registration_id', 'document_type', 'year', 'title', 'file_path', 'file_name',
    'mime_type', 'size', 'hash',
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
