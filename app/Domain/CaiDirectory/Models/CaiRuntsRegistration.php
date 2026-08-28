<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'id_runts', 'cai_section_id', 'tax_code', 'name', 'legal_form', 'legal_nature', 'address',
    'street_number', 'municipality', 'province', 'region', 'postal_code', 'latitude', 'longitude',
    'registration_date', 'register_section', 'activity_sectors', 'legal_representative', 'website',
    'pec', 'official_page_url',
])]
class CaiRuntsRegistration extends Model
{
    protected $primaryKey = 'id_runts';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'registration_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<CaiSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CaiSection::class, 'cai_section_id', 'codice_cai');
    }

    /**
     * @return HasMany<CaiFinancialStatement, $this>
     */
    public function financialStatements(): HasMany
    {
        return $this->hasMany(CaiFinancialStatement::class, 'cai_runts_registration_id', 'id_runts');
    }

    /**
     * @return HasMany<CaiBoardMember, $this>
     */
    public function boardMembers(): HasMany
    {
        return $this->hasMany(CaiBoardMember::class, 'cai_runts_registration_id', 'id_runts');
    }

    /**
     * @return HasMany<CaiDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CaiDocument::class, 'cai_runts_registration_id', 'id_runts');
    }
}
