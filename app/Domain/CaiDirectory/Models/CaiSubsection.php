<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cai_codice', 'cai_section_id', 'name', 'email', 'phone_office', 'phone', 'address', 'website',
    'office_hours', 'notices', 'founded_year', 'members_count', 'latitude', 'longitude', 'user_id',
])]
class CaiSubsection extends Model
{
    protected $primaryKey = 'cai_codice';

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
     * @return BelongsTo<CaiSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CaiSection::class, 'cai_section_id', 'codice_cai');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
