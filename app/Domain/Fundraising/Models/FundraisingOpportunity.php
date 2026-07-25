<?php

declare(strict_types=1);

namespace App\Domain\Fundraising\Models;

use App\Domain\Fundraising\Enums\TerritorialScope;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'official_url', 'endowment_fund', 'deadline', 'program_name', 'sponsor',
    'cofinancing_quota', 'max_contribution', 'territorial_scope', 'beneficiary_requirements',
    'lead_requirements', 'created_by', 'responsible_user_id', 'evaluated_by', 'evaluated_at',
    'evaluation_positive_total', 'evaluation_negative_total', 'evaluation_total',
])]
class FundraisingOpportunity extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'endowment_fund' => 'decimal:2',
            'cofinancing_quota' => 'decimal:2',
            'max_contribution' => 'decimal:2',
            'territorial_scope' => TerritorialScope::class,
            'evaluated_at' => 'datetime',
            'evaluation_positive_total' => 'integer',
            'evaluation_negative_total' => 'integer',
            'evaluation_total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    /**
     * @return HasMany<FundraisingEvaluationScore, $this>
     */
    public function evaluationScores(): HasMany
    {
        return $this->hasMany(FundraisingEvaluationScore::class);
    }

    /**
     * @return HasMany<FundraisingProject, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(FundraisingProject::class);
    }
}
