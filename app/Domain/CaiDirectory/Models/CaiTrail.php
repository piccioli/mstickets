<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sentiero del catasto osm2cai (US-1052), sola lettura: si scrive solo da `cai:import-datapack` (tabelle
 * `snap_osm2cai_*`). `id` = id osm2cai, `osmid` = id della relazione OpenStreetMap, `geometry` = GeoJSON
 * MultiLineString semplificato, `min_lat`…`max_lng` = suo riquadro (US-1064, calcolato all'import). Un sentiero può appartenere a più Sezioni (`cai_trail_section`).
 *
 * @property array<string, mixed>|null $geometry
 */
#[Fillable([
    'id', 'osmid', 'ref', 'name', 'from_place', 'to_place', 'cai_scale', 'length_km', 'ascent', 'descent', 'status',
    'issues_status', 'practicability', 'issues_last_update', 'validation_date', 'geometry', 'min_lat', 'min_lng', 'max_lat',
    'max_lng',
])]
class CaiTrail extends Model
{
    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'osmid' => 'integer',
            'length_km' => 'decimal:3',
            'ascent' => 'integer',
            'descent' => 'integer',
            'status' => CaiTrailStatus::class,
            'practicability' => CaiTrailPracticability::class,
            'issues_last_update' => 'date',
            'validation_date' => 'date',
            'geometry' => 'array',
            'min_lat' => 'decimal:6',
            'min_lng' => 'decimal:6',
            'max_lat' => 'decimal:6',
            'max_lng' => 'decimal:6',
        ];
    }

    /**
     * Sezioni che gestiscono il sentiero (club osm2cai = `cai_sections.codice_cai`); i codici osm2cai senza Sezione
     * restano in `cai_trail_section` ma non compaiono qui.
     *
     * @return BelongsToMany<CaiSection, $this>
     */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(CaiSection::class, 'cai_trail_section', 'cai_trail_id', 'section_code', 'id', 'codice_cai');
    }

    /**
     * Legami con regioni, province e settori attraversati, con la percentuale (US-1062).
     *
     * @return HasMany<CaiTrailTerritory, $this>
     */
    public function territories(): HasMany
    {
        return $this->hasMany(CaiTrailTerritory::class, 'cai_trail_id');
    }
}
