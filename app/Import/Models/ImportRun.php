<?php

declare(strict_types=1);

namespace App\Import\Models;

use App\Import\Enums\ImportRunStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'started_at', 'finished_at', 'dump_label', 'stages',
    'status', 'is_dry_run', 'notes',
])]
class ImportRun extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'stages' => 'array',
            'status' => ImportRunStatus::class,
            'is_dry_run' => 'boolean',
        ];
    }
}
