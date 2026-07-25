<?php

declare(strict_types=1);

namespace App\Domain\Fundraising\Models;

use App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['fundraising_opportunity_id', 'criterion_key', 'score', 'notes'])]
class FundraisingEvaluationScore extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'criterion_key' => FundraisingEvaluationCriterion::class,
            'score' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<FundraisingOpportunity, $this>
     */
    public function fundraisingOpportunity(): BelongsTo
    {
        return $this->belongsTo(FundraisingOpportunity::class);
    }
}
