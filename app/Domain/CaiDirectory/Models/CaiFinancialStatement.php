<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cai_runts_registration_id', 'year', 'general_interest_expenses', 'other_activities_expenses',
    'fundraising_expenses', 'financial_expenses', 'overhead_expenses', 'total_expenses',
    'general_interest_revenues', 'other_activities_revenues', 'fundraising_revenues',
    'financial_revenues', 'overhead_revenues', 'total_revenues', 'pre_tax_result', 'taxes', 'net_result',
])]
class CaiFinancialStatement extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'general_interest_expenses' => 'decimal:2',
            'other_activities_expenses' => 'decimal:2',
            'fundraising_expenses' => 'decimal:2',
            'financial_expenses' => 'decimal:2',
            'overhead_expenses' => 'decimal:2',
            'total_expenses' => 'decimal:2',
            'general_interest_revenues' => 'decimal:2',
            'other_activities_revenues' => 'decimal:2',
            'fundraising_revenues' => 'decimal:2',
            'financial_revenues' => 'decimal:2',
            'overhead_revenues' => 'decimal:2',
            'total_revenues' => 'decimal:2',
            'pre_tax_result' => 'decimal:2',
            'taxes' => 'decimal:2',
            'net_result' => 'decimal:2',
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
