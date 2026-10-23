<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use App\Domain\CaiDirectory\Enums\CaiRuntsPresenceStatus;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'codice_cai', 'name', 'tax_code', 'vat_number', 'email', 'pec', 'phone_office', 'phone', 'fax',
    'address', 'postal_address', 'website', 'office_hours', 'notices', 'founded_year', 'members_count',
    'latitude', 'longitude', 'region', 'user_id', 'cai_last_synced_at',
    'runts_presence_status', 'runts_presence_checked_at',
])]
class CaiSection extends Model
{
    protected $primaryKey = 'codice_cai';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'founded_year' => 'integer',
            'members_count' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'cai_last_synced_at' => 'datetime',
            'runts_presence_status' => CaiRuntsPresenceStatus::class,
            'runts_presence_checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CaiSubsection, $this>
     */
    public function subsections(): HasMany
    {
        return $this->hasMany(CaiSubsection::class, 'cai_section_id', 'codice_cai');
    }

    /**
     * @return HasMany<CaiRuntsRegistration, $this>
     */
    public function runtsRegistrations(): HasMany
    {
        return $this->hasMany(CaiRuntsRegistration::class, 'cai_section_id', 'codice_cai');
    }

    /**
     * Documenti collegati DIRETTAMENTE alla sezione (mai passando da una `CaiRuntsRegistration`):
     * upload manuale/Veryfico per una sezione senza (ancora) presenza RUNTS — vedi
     * {@see CaiDocument::section()}. La maggior parte dei documenti
     * resta raggiungibile solo via `runtsRegistrations->flatMap->documents` (sync live, US-804).
     *
     * @return HasMany<CaiDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CaiDocument::class, 'cai_section_id', 'codice_cai');
    }

    /**
     * @return HasMany<CaiFinancialStatement, $this>
     */
    public function financialStatements(): HasMany
    {
        return $this->hasMany(CaiFinancialStatement::class, 'cai_section_id', 'codice_cai');
    }

    /**
     * Rifugi di rifugi.cai.it intestati alla Sezione (`cai_huts.branch_code` = `codice_cai`, US-1031).
     *
     * @return HasMany<CaiHut, $this>
     */
    public function huts(): HasMany
    {
        return $this->hasMany(CaiHut::class, 'branch_code', 'codice_cai');
    }

    /**
     * Sentieri osm2cai gestiti dalla Sezione (`cai_trail_section.section_code` = `codice_cai`, US-1052); un sentiero
     * condiviso compare fra i sentieri di ognuna delle sue Sezioni.
     *
     * @return BelongsToMany<CaiTrail, $this>
     */
    public function trails(): BelongsToMany
    {
        return $this->belongsToMany(CaiTrail::class, 'cai_trail_section', 'section_code', 'cai_trail_id', 'codice_cai', 'id');
    }
}
