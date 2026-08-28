<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'codice_cai', 'name', 'tax_code', 'vat_number', 'email', 'pec', 'phone_office', 'phone', 'fax',
    'address', 'postal_address', 'website', 'office_hours', 'notices', 'founded_year', 'members_count',
    'latitude', 'longitude', 'region', 'user_id',
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
}
