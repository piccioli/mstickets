<?php

declare(strict_types=1);

namespace App\Domain\Mail\Models;

use App\Domain\Mail\Enums\SuppressionReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['email', 'reason', 'bounce_count', 'notes', 'expires_at'])]
class EmailSuppression extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => SuppressionReason::class,
            'bounce_count' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Una soppressione senza scadenza o non ancora scaduta (§7.3.4/§7.3.9 del
     * PRD): una riga con `expires_at` nel passato non blocca più il mittente.
     *
     * @param  Builder<EmailSuppression>  $query
     * @return Builder<EmailSuppression>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())
        );
    }
}
