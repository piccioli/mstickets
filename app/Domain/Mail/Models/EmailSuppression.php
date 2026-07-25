<?php

declare(strict_types=1);

namespace App\Domain\Mail\Models;

use App\Domain\Mail\Enums\SuppressionReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
}
