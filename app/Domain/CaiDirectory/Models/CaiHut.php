<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Enums\CaiHutType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rifugio di rifugi.cai.it (US-1031), sola lettura: si scrive solo da `cai:import-datapack` (tabella `snap_cai_huts`).
 * `branch_code` è il `branch_id` della fonte, uguale a `cai_sections.codice_cai` per i rifugi di una Sezione.
 *
 * @property array<string, mixed>|null $management
 */
#[Fillable([
    'id_cai', 'branch_code', 'branch_name', 'name', 'alias', 'slug', 'type', 'status', 'latitude', 'longitude',
    'altitude', 'locality', 'municipality', 'province', 'region', 'valley', 'massif', 'protected_area', 'beds',
    'winter_beds', 'opening_times', 'photos', 'services', 'contacts', 'management', 'fields', 'source_updated_at',
])]
class CaiHut extends Model
{
    protected $primaryKey = 'id_cai';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id_cai' => 'integer',
            'type' => CaiHutType::class,
            'status' => CaiHutStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'altitude' => 'integer',
            'beds' => 'integer',
            'winter_beds' => 'integer',
            'opening_times' => 'array',
            'photos' => 'array',
            'services' => 'array',
            'contacts' => 'array',
            'management' => 'array',
            'fields' => 'array',
            'source_updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CaiSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CaiSection::class, 'branch_code', 'codice_cai');
    }
}
